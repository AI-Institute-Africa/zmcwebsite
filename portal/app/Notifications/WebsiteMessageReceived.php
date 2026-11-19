<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WebsiteMessageReceived extends Notification
{
    use Queueable;

    public function __construct(public Complaint $message) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New website message',
            'message' => $this->message->subject ?: 'Website enquiry',
            'sender' => $this->message->full_name,
            'reference' => $this->reference(),
            'url' => route('staff.pr.messages.show', $this->message),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New contact form message: ' . $this->reference())
            ->greeting('New website contact message')
            ->line('A new enquiry has been submitted on the ZMC website.')
            ->line('From: ' . $this->message->full_name)
            ->line('Email: ' . $this->message->email)
            ->line('Subject: ' . ($this->message->subject ?: 'Website enquiry'))
            ->line('Reference: ' . $this->reference())
            ->line($this->message->message)
            ->action('Open Contact Messages', route('staff.pr.messages.show', $this->message));
    }

    private function reference(): string
    {
        return $this->message->reference_number ?: ('ENQ-' . str_pad($this->message->id, 6, '0', STR_PAD_LEFT));
    }
}
