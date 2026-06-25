<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $type,
        private string $subject,
        private string $headline,
        private array  $details = [],
        private string $severity = 'warning' // info | warning | critical
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $colorMap = [
            'info'     => '#3B82F6',
            'warning'  => '#F59E0B',
            'critical' => '#EF4444',
        ];

        $severityLabel = ucfirst($this->severity);
        $color         = $colorMap[$this->severity] ?? $colorMap['warning'];

        $mail = (new MailMessage)
            ->from(config('mail.from.address', 'info@feraclinic.com'), config('mail.from.name', 'FeRa Clinic'))
            ->subject("[{$severityLabel}] {$this->subject} — FeRa Clinic SMS")
            ->greeting("SMS Platform Alert: {$this->headline}");

        foreach ($this->details as $label => $value) {
            $mail->line("**{$label}:** {$value}");
        }

        $mail->line('---')
             ->line('This is an automated alert from the FeRa Clinic SMS Platform.')
             ->line('Timestamp: ' . now()->toDateTimeString() . ' UTC');

        return $mail;
    }

    public static function sendToAdmins(
        string $type,
        string $subject,
        string $headline,
        array  $details = [],
        string $severity = 'warning'
    ): void {
        try {
            $admins = \App\Models\User::where('role', 'admin')
                ->where('is_active', true)
                ->get();

            if ($admins->isEmpty()) return;

            $notification = new self($type, $subject, $headline, $details, $severity);
            \Illuminate\Support\Facades\Notification::send($admins, $notification);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("AdminAlertNotification failed: {$e->getMessage()}", ['type' => $type]);
        }
    }
}
