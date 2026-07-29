<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Creates an encrypted database (+ media) backup and prunes old ones. Run from
 * the dashboard button, the weekly schedule, or a Hostinger cron job.
 */
class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Create an encrypted database + media backup and prune old archives';

    public function handle(BackupService $service): int
    {
        try {
            $name = $service->create(now()->format('Y-m-d_His'));
        } catch (Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Backup created: {$name}".($service->encrypted() ? ' (encrypted)' : ' (NOT encrypted — set BACKUP_ARCHIVE_PASSWORD)'));

        return self::SUCCESS;
    }
}
