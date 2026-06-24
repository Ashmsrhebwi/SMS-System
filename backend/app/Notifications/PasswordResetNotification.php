<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetNotification extends ResetPassword
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->from(config('mail.from.address', 'info@feraclinic.com'), config('mail.from.name', 'FeRa Clinic'))
            ->subject('Reset Your FeRa Clinic Password')
            ->view('emails.password-reset', [
                'resetUrl' => $resetUrl,
                'userName' => $notifiable->name,
                'expiry'   => config('auth.passwords.users.expire', 30),
            ]);
    }
}
