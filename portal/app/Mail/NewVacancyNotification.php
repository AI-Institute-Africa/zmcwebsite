<?php

namespace App\Mail;

use App\Models\Vacancy;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewVacancyNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Vacancy $vacancy
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Vacancy: ' . $this->vacancy->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-vacancy',
            with: [
                'vacancy' => $this->vacancy,
                'websiteUrl' => config('app.website_url'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
