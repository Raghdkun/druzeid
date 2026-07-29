<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly database + media backup (Sundays, 03:00). Requires the scheduler to
// be running — see the Hostinger cron guide in BACKUPS.md. `withoutOverlapping`
// guards against a slow run colliding with the next tick.
Schedule::command('backup:run')
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping();
