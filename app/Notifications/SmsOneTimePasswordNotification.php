<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification;

class SmsOneTimePasswordNotification extends OneTimePasswordNotification
{
    /**
     * SMS is sent synchronously: the production host has no queue worker.
     *
     * Without Vonage credentials the local/testing environment falls back to the log channel,
     * so the flow stays testable in Docker without spending an SMS.
     */
    public function via(object $notifiable): string|array
    {
        if (config('vonage.api_key')) {
            return ['vonage'];
        }

        if (app()->environment(['local', 'testing'])) {
            return ['log'];
        }

        throw new RuntimeException('VONAGE_KEY is not configured.');
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        return (new VonageMessage)->content($this->message());
    }

    public function toLog(object $notifiable): string
    {
        return $this->message();
    }

    /** ASCII keeps the message inside one GSM-7 segment (one billed SMS). */
    private function message(): string
    {
        return sprintf(
            '%s : votre code de connexion est %s. Valable %d minutes.',
            Str::ascii((string) config('app.name')),
            $this->oneTimePassword->password,
            (int) config('one-time-passwords.default_expires_in_minutes'),
        );
    }
}
