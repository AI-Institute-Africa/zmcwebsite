<?php

namespace App\Mail;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriberWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome — You\'re subscribed to ZMC Updates',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.subscriber_welcome',
            with: [
                'fullName' => $this->subscriber->full_name,
                'email'    => $this->subscriber->email,
            ],
        );
    }
}
