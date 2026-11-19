<?php

namespace App\Mail;

use App\Models\LevyRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LevyPaymentVerified extends Mailable
{
    use Queueable, SerializesModels;

    public $levy;

    /**
     * Create a new message instance.
     */
    public function __construct(LevyRecord $levy)
    {
        $this->levy = $levy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Levy Payment Verified - ' . $this->levy->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.levy_payment_verified',
            with: [
                'levy' => $this->levy,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
