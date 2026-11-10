<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Laravel has no notification channel that writes to the application log, and the shop has no
 * Vonage credentials locally. This channel keeps the SMS flow testable without sending anything.
 */
class LogChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toLog')) {
            return;
        }

        Log::debug((string) $notification->toLog($notifiable));
    }
}
