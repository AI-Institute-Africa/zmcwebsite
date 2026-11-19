<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LevyPaymentReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $financialYear;
    public $dueDate;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $financialYear, $dueDate = null)
    {
        $this->user = $user;
        $this->financialYear = $financialYear;
        $this->dueDate = $dueDate ?? now()->endOfMonth();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Annual Levy Payment Reminder - ' . $this->financialYear,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.levy_payment_reminder',
            with: [
                'user' => $this->user,
                'financialYear' => $this->financialYear,
                'dueDate' => $this->dueDate,
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
