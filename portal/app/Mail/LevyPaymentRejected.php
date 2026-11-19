<?php

namespace App\Mail;

use App\Models\LevyRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LevyPaymentRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $levy;
    public $reason;

    /**
     * Create a new message instance.
     */
    public function __construct(LevyRecord $levy, string $reason)
    {
        $this->levy = $levy;
        $this->reason = $reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Levy Payment Rejected - Action Required - ' . $this->levy->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.levy_payment_rejected',
            with: [
                'levy' => $this->levy,
                'reason' => $this->reason,
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
