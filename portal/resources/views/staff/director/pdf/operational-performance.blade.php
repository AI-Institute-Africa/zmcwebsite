<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Operational Performance Report</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #facc15; padding-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #1e293b; color: white; padding: 8px; text-align: left; }
        td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Zimbabwe Media Commission</h1>
        <div>Operational Performance Report - {{ $month }}</div>
    </div>
    <p><strong>Generated:</strong> {{ $generated_at }}</p>
    <h2>Accreditation Performance</h2>
    <table>
        <thead><tr><th>Staff Member</th><th>Applications Processed</th></tr></thead>
        <tbody>
            @foreach($applications_processed as $officer)
            <tr>
                <td>{{ $officer->name }}</td>
                <td>{{ $officer->processed_count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
