<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Creates, lists, streams and prunes database backups.
 *
 * Each backup is a ZIP on the private `backups` disk containing `database.sql`
 * (a dump of the active connection) and, by default, the uploaded media. When a
 * password is configured the archive is AES-256 encrypted. Only the newest
 * `backup.keep` archives are retained.
 */
class BackupService
{
    /** A backup file name always matches this — the basis of the traversal guard. */
    private const NAME_PATTERN = '/^backup-\d{4}-\d{2}-\d{2}_\d{6}\.zip$/';

    public function __construct(private readonly DatabaseDumper $dumper) {}

    /**
     * Create a new backup and prune old ones. Returns the archive file name.
     */
    public function create(string $timestamp): string
    {
        $disk = Storage::disk(config('backup.disk'));
        $disk->makeDirectory('');

        $name = "backup-{$timestamp}.zip";
        $zipPath = $disk->path($name);

        $dumpFile = tempnam(sys_get_temp_dir(), 'dbdump_');

        if ($dumpFile === false) {
            throw new RuntimeException('Could not allocate a temporary dump file.');
        }

        try {
            $this->dumper->dump($dumpFile);
            $this->buildArchive($zipPath, $dumpFile);
        } finally {
            @unlink($dumpFile);
        }

        $this->prune();

        return $name;
    }

    /**
     * @return list<array{name: string, size: int, createdAt: string}> newest first
     */
    public function list(): array
    {
        $disk = Storage::disk(config('backup.disk'));

        $backups = [];

        foreach ($disk->files() as $file) {
            if (preg_match(self::NAME_PATTERN, $file) !== 1) {
                continue;
            }

            $backups[] = [
                'name' => $file,
                'size' => $disk->size($file),
                'createdAt' => date('c', $disk->lastModified($file)),
            ];
        }

        // Newest first — the timestamped name sorts chronologically.
        usort($backups, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $backups;
    }

    /**
     * Absolute path of a backup for streaming, or null if the name is invalid
     * or the file is missing. The strict name pattern blocks path traversal.
     */
    public function path(string $name): ?string
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            return null;
        }

        $disk = Storage::disk(config('backup.disk'));

        return $disk->exists($name) ? $disk->path($name) : null;
    }

    public function delete(string $name): bool
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            return false;
        }

        return Storage::disk(config('backup.disk'))->delete($name);
    }

    public function encrypted(): bool
    {
        return filled(config('backup.password')) && defined('ZipArchive::EM_AES_256');
    }

    private function buildArchive(string $zipPath, string $dumpFile): void
    {
        $password = (string) config('backup.password', '');
        $encrypt = $this->encrypted();

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the backup archive.');
        }

        if ($encrypt) {
            $zip->setPassword($password);
        }

        $this->addFile($zip, $dumpFile, 'database.sql', $encrypt);

        if ((bool) config('backup.include_media')) {
            $this->addMedia($zip, $encrypt);
        }

        if (! $zip->close()) {
            throw new RuntimeException('Could not write the backup archive.');
        }
    }

    private function addMedia(ZipArchive $zip, bool $encrypt): void
    {
        $disk = Storage::disk(config('backup.media_disk'));
        $base = (string) config('backup.media_path');

        if (! $disk->exists($base)) {
            return;
        }

        foreach ($disk->allFiles($base) as $file) {
            $entry = 'media/'.ltrim(substr($file, strlen($base)), '/');
            $this->addFile($zip, $disk->path($file), $entry, $encrypt);
        }
    }

    private function addFile(ZipArchive $zip, string $absolutePath, string $entry, bool $encrypt): void
    {
        $zip->addFile($absolutePath, $entry);

        if ($encrypt) {
            $zip->setEncryptionName($entry, ZipArchive::EM_AES_256);
        }
    }

    /**
     * Keep only the newest `backup.keep` archives.
     */
    private function prune(): void
    {
        $keep = (int) config('backup.keep', 3);
        $backups = $this->list();

        foreach (array_slice($backups, $keep) as $stale) {
            $this->delete($stale['name']);
        }
    }
}
