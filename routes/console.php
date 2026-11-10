<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hostinger disables proc_open/exec/shell_exec, and Laravel runs *every* scheduled command event through
// Symfony Process. Scheduled callbacks run in-process instead, so the commands keep working on this host.
// Each callback gets a name because withoutOverlapping() needs one.

// MoneyFusion: reconcile payments whose notification never arrived (and expire abandoned ones).
// Needs the scheduler to be running: a cron entry for `php artisan schedule:run` every minute (see README).
Schedule::call(fn () => Artisan::call('moneyfusion:check-pending', ['--age' => 10, '--limit' => 100]))
    ->name('moneyfusion:check-pending')
    ->everyTenMinutes()
    ->withoutOverlapping(15);

// Nightly sweep with a larger batch, for anything the frequent run did not get to
Schedule::call(fn () => Artisan::call('moneyfusion:check-pending', ['--age' => 1440, '--limit' => 500]))
    ->name('moneyfusion:check-pending-nightly')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Newsletter: a small batch every minute, under the 60-second cron tick (no queue worker in production).
// The command resumes the campaign from its saved cursor.
Schedule::call(fn () => Artisan::call('newsletter:send', [
    '--limit' => 20,
    '--pause' => 500,
    '--max-seconds' => 50,
]))
    ->name('newsletter:send')
    ->everyMinute()
    ->withoutOverlapping(5);
