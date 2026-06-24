<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification
{
    use Queueable;

    public function __construct(private string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(config('mail.from.address', 'info@feraclinic.com'), config('mail.from.name', 'FeRa Clinic'))
            ->subject('Your FeRa Clinic Login Code')
            ->view('emails.otp', [
                'otp'      => $this->otp,
                'userName' => $notifiable->name,
                'expiry'   => 5,
            ]);
    }
}
