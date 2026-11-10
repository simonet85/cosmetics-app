<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// MoneyFusion: reconcile payments whose notification never arrived (and expire abandoned ones).
// Needs the scheduler to be running: a cron entry for `php artisan schedule:run` every minute (see README).
// Never use runInBackground() here: Hostinger disables proc_open/exec, so the event would fail silently.
Schedule::command('moneyfusion:check-pending --age=10 --limit=100')
    ->everyTenMinutes()
    ->withoutOverlapping(15);

// Nightly sweep with a larger batch, for anything the frequent run did not get to
Schedule::command('moneyfusion:check-pending --age=1440 --limit=500')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Newsletter: a small batch every minute, under the 60-second cron tick (no background execution,
// no queue worker in production). The command resumes the campaign from its saved cursor.
Schedule::command('newsletter:send --limit=20 --pause=500 --max-seconds=50')
    ->everyMinute()
    ->withoutOverlapping(5);
