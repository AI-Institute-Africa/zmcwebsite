<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Media House Status Report</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #facc15; padding-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Zimbabwe Media Commission</h1>
        <div>Media House Status Report</div>
    </div>
    <p><strong>Generated:</strong> {{ $generated_at }}</p>
    <h2>Status Summary</h2>
    <p>Active Media Houses: {{ $status_counts['active'] }}</p>
    <p>Suspended: {{ $status_counts['suspended'] }}</p>
    <p>New Registrations: {{ $status_counts['new_registrations'] }}</p>
    <p>Average Staff per House: {{ $average_staff }}</p>
</body>
</html>
