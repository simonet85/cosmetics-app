<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification;

/**
 * The sign-in code sent by e-mail. It is notified directly by EmailLoginService, so the package-wide
 * notification setting (the SMS one) stays untouched.
 */
class EmailOneTimePasswordNotification extends OneTimePasswordNotification
{
    public function via(object $notifiable): string|array
    {
        return 'mail';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('one-time-passwords.default_expires_in_minutes');

        return (new MailMessage)
            ->subject(sprintf('%s est votre code de connexion - %s', $this->oneTimePassword->password, config('app.name')))
            ->greeting('Bonjour,')
            ->line('Voici votre code de connexion :')
            ->line('**'.$this->oneTimePassword->password.'**')
            ->line(sprintf('Il est valable %d minutes et ne peut être utilisé qu\'une fois.', $minutes))
            ->line('Si vous n\'avez pas demandé ce code, ignorez ce message : votre compte reste protégé.')
            ->salutation('L\'équipe '.config('app.name'));
    }
}
