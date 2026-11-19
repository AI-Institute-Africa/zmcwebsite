<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>ZMC Audit Log Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h2 { margin: 0 0 8px; }
        .meta { margin-bottom: 12px; }
        .meta div { margin-bottom: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <h2>ZMC Integrated Portal - Audit Log Report</h2>
    <div class="meta">
        <div><strong>Exported By:</strong> {{ $exportedBy }}</div>
        <div><strong>Exported At:</strong> {{ $exportedAt->format('Y-m-d H:i:s') }}</div>
        @foreach($filters as [$label, $value])
            <div><strong>{{ $label }}:</strong> {{ $value }}</div>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                <th>Log ID</th>
                <th>Time</th>
                <th>Actor</th>
                <th>Role</th>
                <th>Action</th>
                <th>Module</th>
                <th>Record Number</th>
                <th>Status</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php
                    $meta = is_array($log->meta) ? $log->meta : [];
                @endphp
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->actor?->name ?? ('User #' . ($log->actor_user_id ?? 'System')) }}</td>
                    <td>{{ $log->user_role ?? ($meta['actor_role'] ?? '—') }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $meta['module'] ?? ($log->model_type ? class_basename($log->model_type) : '—') }}</td>
                    <td>{{ $meta['record_number'] ?? $meta['reference'] ?? ($meta['application_reference'] ?? ($log->model_id ?? '—')) }}</td>
                    <td>{{ $meta['to_status'] ?? $meta['status'] ?? '—' }}</td>
                    <td>{{ $log->ip ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No logs for selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
