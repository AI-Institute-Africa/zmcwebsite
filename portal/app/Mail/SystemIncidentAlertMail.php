<?php

namespace App\Mail;

use App\Models\SystemIncident;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemIncidentAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?SystemIncident $incident,
        public string $event,
        public bool $testMessage = false,
    ) {}

    public function build(): self
    {
        if ($this->testMessage) {
            return $this->subject('ZMC IT alert email test')
                ->view('emails.system-incident-alert');
        }

        $label = match ($this->event) {
            'recovered' => 'RECOVERED',
            'escalated' => 'ESCALATED',
            default => strtoupper((string) $this->incident?->severity),
        };

        return $this->subject('[ZMC IT Alert][' . $label . '] ' . ($this->incident?->title ?? 'System incident'))
            ->view('emails.system-incident-alert');
    }
}
