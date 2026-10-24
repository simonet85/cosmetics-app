<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url)
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour,')
            ->line('Vous avez demandé à réinitialiser le mot de passe de votre compte '.config('app.name').'.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line("Ce lien est valable {$minutes} minutes.")
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre mot de passe reste inchangé.')
            ->salutation('L\'équipe '.config('app.name'));
    }
}
