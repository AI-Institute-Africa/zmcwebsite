<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ExpiryReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $recordType,
        public string $recordNumber,
        public ?string $expiresOn,
        public int $daysRemaining,
    ) {}

    public function via($notifiable): array
    {
        // Mail will only send if mail is configured; database always works if notifications table exists.
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Accreditation/Registration Expiry Reminder')
            ->greeting('Hello '.$notifiable->name)
            ->line("Your {$this->recordType} record ({$this->recordNumber}) is expiring soon.")
            ->line("Expiry date: {$this->expiresOn} ({$this->daysRemaining} day(s) remaining).")
            ->line('Please submit a renewal request or contact the Media Commission if you need assistance.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'expiry_reminder',
            'record_type' => $this->recordType,
            'record_number' => $this->recordNumber,
            'expires_on' => $this->expiresOn,
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
