<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Ifsnop\Mysqldump\Mysqldump;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;

/**
 * Dumps the active database connection to a `.sql` file, choosing the method
 * from the driver:
 *  - MySQL/MariaDB → pure PHP (mysqldump-php), so it works on shared hosting
 *    with no `mysqldump` binary. This is the production path.
 *  - PostgreSQL → `pg_dump` (available in dev / on a VPS).
 *  - SQLite → a small pure-PHP dump (the test path).
 */
class DatabaseDumper
{
    public function dump(string $target): void
    {
        $connection = (string) config('database.default');
        $config = (array) config("database.connections.{$connection}");
        $driver = (string) ($config['driver'] ?? '');

        match ($driver) {
            'mysql', 'mariadb' => $this->dumpMysql($config, $target),
            'pgsql' => $this->dumpPostgres($config, $target),
            'sqlite' => $this->dumpSqlite($target),
            default => throw new RuntimeException("Backups do not support the '{$driver}' database driver."),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpMysql(array $config, string $target): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $config['database'] ?? '',
        );

        $dump = new Mysqldump($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            'add-drop-table' => true,
            'single-transaction' => true,
            'skip-comments' => false,
        ]);

        $dump->start($target);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dumpPostgres(array $config, string $target): void
    {
        $result = Process::forever()
            ->env(['PGPASSWORD' => (string) ($config['password'] ?? '')])
            ->timeout(600)
            ->run([
                (string) config('backup.pg_dump_path', 'pg_dump'),
                '--host='.($config['host'] ?? '127.0.0.1'),
                '--port='.($config['port'] ?? 5432),
                '--username='.($config['username'] ?? ''),
                '--dbname='.($config['database'] ?? ''),
                '--no-owner',
                '--no-privileges',
                '--clean',
                '--if-exists',
                '--file='.$target,
            ]);

        if (! $result->successful()) {
            throw new RuntimeException('pg_dump failed: '.$result->errorOutput());
        }
    }

    /**
     * Portable pure-PHP SQLite dump — schema plus INSERTs. Handles both
     * file-based and in-memory (test) databases.
     */
    private function dumpSqlite(string $target): void
    {
        $pdo = DB::connection()->getPdo();

        $out = fopen($target, 'w');

        if ($out === false) {
            throw new RuntimeException('Could not open the dump file for writing.');
        }

        fwrite($out, "PRAGMA foreign_keys=OFF;\n");

        $schema = $pdo->query(
            "SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        );

        if ($schema === false) {
            throw new RuntimeException('Could not read the SQLite schema.');
        }

        /** @var array<int, array{name: string, sql: string}> $tables */
        $tables = $schema->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $name = $table['name'];
            fwrite($out, "DROP TABLE IF EXISTS \"{$name}\";\n");
            fwrite($out, $table['sql'].";\n");

            $result = $pdo->query('SELECT * FROM "'.$name.'"');

            if ($result === false) {
                throw new RuntimeException("Could not read rows from table '{$name}'.");
            }

            $rows = $result->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $columns = implode(', ', array_map(fn ($c) => '"'.$c.'"', array_keys($row)));
                $values = implode(', ', array_map(
                    fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row),
                ));
                fwrite($out, "INSERT INTO \"{$name}\" ({$columns}) VALUES ({$values});\n");
            }
        }

        fclose($out);
    }
}
