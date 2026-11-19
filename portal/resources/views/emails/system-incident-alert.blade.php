<!doctype html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f4f6f9;font-family:Arial,sans-serif;color:#172033;">
    <main style="max-width:640px;margin:auto;padding:28px;background:#fff;border:1px solid #e3e8ef;border-radius:12px;">
        <p style="margin:0 0 8px;color:#9a7100;font-size:12px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;">Zimbabwe Media Commission · IT alert</p>
        @if($testMessage)
            <h1 style="margin:0 0 14px;font-size:22px;">Alert email delivery is configured</h1>
            <p style="color:#526071;line-height:1.6;">This test confirms the portal can send monitoring alerts to this address.</p>
        @else
            @php
                $eventLabel = match($event) {
                    'recovered' => 'System recovered',
                    'escalated' => 'Incident severity increased',
                    default => 'System issue detected',
                };
            @endphp
            <h1 style="margin:0 0 14px;font-size:22px;">{{ $eventLabel }}</h1>
            <div style="padding:16px;border:1px solid #e3e8ef;border-radius:10px;background:#fafbfc;">
                <p style="margin:0 0 8px;"><strong>Incident:</strong> {{ $incident->title }}</p>
                <p style="margin:0 0 8px;"><strong>Severity:</strong> {{ ucfirst($incident->severity) }}</p>
                <p style="margin:0 0 8px;"><strong>Module:</strong> {{ $incident->module ?: 'General system' }}</p>
                <p style="margin:0;"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $incident->status)) }}</p>
            </div>
            @if($incident->description)
                <p style="color:#526071;line-height:1.6;"><strong>Details:</strong> {{ $incident->description }}</p>
            @endif
            @php($eventTime = $event === 'recovered' ? $incident->resolved_at : $incident->created_at)
            <p style="color:#526071;line-height:1.6;">{{ $eventLabel }} at {{ ($eventTime ?: now())->timezone(config('app.timezone'))->format('d M Y H:i T') }}.</p>
        @endif
        <p style="margin:22px 0 0;color:#526071;font-size:12px;">Review current system health and incident details in the IT Admin dashboard.</p>
    </main>
</body>
</html>
