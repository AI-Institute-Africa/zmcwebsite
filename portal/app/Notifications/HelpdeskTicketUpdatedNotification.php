<?php

namespace App\Notifications;

use App\Models\HelpdeskTicket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HelpdeskTicketUpdatedNotification extends Notification
{
    public function __construct(
        public HelpdeskTicket $ticket,
        public string $event,
        public ?string $details = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Helpdesk ticket update: ' . $this->ticket->subject)
            ->greeting('Hello ' . ($notifiable->name ?: 'colleague') . ',')
            ->line($this->event)
            ->line('Ticket: ' . $this->ticket->subject)
            ->line('Priority: ' . ucfirst($this->ticket->priority))
            ->line('Status: ' . ucfirst(str_replace('_', ' ', $this->ticket->status)));

        if ($this->details) {
            $mail->line($this->details);
        }

        return $mail
            ->action('Open Helpdesk', route('staff.it.helpdesk') . '#ticket-' . $this->ticket->id)
            ->line('This message was sent by the Zimbabwe Media Commission portal.');
    }
}
