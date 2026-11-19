<?php

namespace App\Services;

use App\Mail\SystemIncidentAlertMail;
use App\Models\SystemConfig;
use App\Models\SystemIncident;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SystemAlertNotifier
{
    public function isEnabled(): bool
    {
        $settings = SystemConfig::getValue('system_alerts', []);

        return is_array($settings)
            ? (bool) ($settings['email_enabled'] ?? config('monitoring.alerts.email_enabled', true))
            : (bool) config('monitoring.alerts.email_enabled', true);
    }

    public function recipients(): array
    {
        $settings = SystemConfig::getValue('system_alerts', []);
        $configured = is_array($settings) ? ($settings['emails'] ?? []) : [];
        $configured = array_values(array_unique(array_filter(array_map(
            'trim',
            is_array($configured) ? $configured : []
        ), fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)));

        if ($configured !== []) {
            return $configured;
        }

        $environmentRecipients = config('monitoring.alerts.emails', []);
        if ($environmentRecipients !== []) {
            return array_values(array_unique(array_filter($environmentRecipients,
                fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            )));
        }

        return User::query()
            ->where('account_status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['it_admin', 'super_admin']))
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    public function send(SystemIncident $incident, string $event = 'detected'): bool
    {
        return $this->deliver(new SystemIncidentAlertMail($incident, $event));
    }

    public function sendTest(): bool
    {
        return $this->deliver(new SystemIncidentAlertMail(null, 'test', true));
    }

    private function deliver(SystemIncidentAlertMail $mail): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        if (in_array(config('mail.default'), ['log', 'array', 'null'], true)) {
            Log::warning('System alert email is enabled, but the configured mailer does not deliver external email.', [
                'mailer' => config('mail.default'),
            ]);

            return false;
        }

        $recipients = $this->recipients();
        if ($recipients === []) {
            Log::warning('System alert email was not sent because no IT alert recipients are configured.');

            return false;
        }

        try {
            Mail::to($recipients)->send($mail);

            return true;
        } catch (Throwable $exception) {
            Log::error('System alert email delivery failed.', [
                'recipient_count' => count($recipients),
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
