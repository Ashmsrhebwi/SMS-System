<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmail
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage())
            ->from('info@feraclinic.com', 'FeRa Clinic')
            ->subject('Verify Your Email – FeRa Clinic')
            ->view('emails.verify-email', [
                'user'            => $this->notifiable,
                'verificationUrl' => $url,
            ]);
    }
}
