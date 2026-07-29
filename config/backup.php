<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Backups live on a PRIVATE disk under storage/app/backups — never the
    | public disk, so an archive can only be reached through the admin-gated
    | download route, not a guessable URL.
    |
    */

    'disk' => 'backups',

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Only the newest N archives are kept; older ones are pruned after each
    | successful backup so storage never grows unbounded.
    |
    */

    'keep' => max(1, (int) env('BACKUP_KEEP', 3)),

    /*
    |--------------------------------------------------------------------------
    | Archive password (AES-256)
    |--------------------------------------------------------------------------
    |
    | When set, every archive is encrypted with AES-256 and this password is
    | required to open or restore it. Keep it in the ENVIRONMENT (not the
    | database) so a backup stays recoverable even if the database is lost.
    | When empty the archive is created unencrypted and the dashboard warns.
    |
    */

    'password' => env('BACKUP_ARCHIVE_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Contents
    |--------------------------------------------------------------------------
    |
    | The database is always dumped. Uploaded media (the public media folder)
    | is included by default so an archive is a complete restore point.
    |
    */

    'include_media' => (bool) env('BACKUP_INCLUDE_MEDIA', true),

    'media_disk' => 'public',

    'media_path' => 'media',

    /*
    |--------------------------------------------------------------------------
    | pg_dump path (PostgreSQL only)
    |--------------------------------------------------------------------------
    |
    | Used when the connection driver is pgsql. MySQL is dumped in pure PHP and
    | needs no binary — the reliable path on shared hosting.
    |
    */

    'pg_dump_path' => env('PG_DUMP_PATH', 'pg_dump'),
];
