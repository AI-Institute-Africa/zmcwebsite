<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registrar Operational Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h2 { margin: 0 0 8px; }
        .meta { margin: 0 0 10px; }
        .cards { margin: 0 0 12px; }
        .cards span { margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Registrar Operational Report</h1>
    <div class="meta">
        Date range: {{ request('date_from', 'N/A') }} to {{ request('date_to', 'N/A') }}<br>
        Generated: {{ now()->format('Y-m-d H:i:s') }}
    </div>
    <div class="cards">
        <span><strong>Approvals:</strong> {{ $approvals }}</span>
        <span><strong>Reassignments:</strong> {{ $reassignments }}</span>
        <span><strong>Total Prints:</strong> {{ $totalPrints }}</span>
        <span><strong>Cert Edits:</strong> {{ $edits }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Applicant</th>
                <th>Status</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentApprovals as $app)
                <tr>
                    <td>{{ $app->reference }}</td>
                    <td>{{ $app->applicant?->name ?? '—' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', (string)$app->status)) }}</td>
                    <td>{{ $app->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No records in selected date range.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
