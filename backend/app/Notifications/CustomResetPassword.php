<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage())
            ->from('info@feraclinic.com', 'FeRa Clinic')
            ->subject('Reset Your Password – FeRa Clinic')
            ->view('emails.password-reset', [
                'user'     => $this->notifiable,
                'resetUrl' => $url,
            ]);
    }
}
