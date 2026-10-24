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
            ->subject(__('Réinitialisation de votre mot de passe'))
            ->greeting(__('Bonjour,'))
            ->line(__('Vous avez demandé à réinitialiser le mot de passe de votre compte :app.', ['app' => config('app.name')]))
            ->action(__('Choisir un nouveau mot de passe'), $url)
            ->line(__('Ce lien est valable :minutes minutes.', ['minutes' => $minutes]))
            ->line(__('Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre mot de passe reste inchangé.'))
            ->salutation(__('L\'équipe :app', ['app' => config('app.name')]));
    }
}
