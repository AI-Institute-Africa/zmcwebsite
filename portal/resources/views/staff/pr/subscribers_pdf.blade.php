<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Subscribers - {{ date('Y-m-d') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            color: #1e293b;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2563eb;
        }
        
        .header h1 {
            color: #0f172a;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #64748b;
            font-size: 14px;
        }
        
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: #f1f5f9;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            border-bottom: 2px solid #cbd5e1;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        
        tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
        
        .email-link {
            color: #2563eb;
            text-decoration: none;
        }
        
        @media print {
            body {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Website Subscribers Report</h1>
        <p>Zimbabwe Media Commission Newsletter Subscribers</p>
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">Total Subscribers</div>
            <div class="stat-value">{{ number_format($subscribers->count()) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">This Month</div>
            <div class="stat-value">{{ number_format($subscribers->where('created_at', '>=', now()->startOfMonth())->count()) }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">This Week</div>
            <div class="stat-value">{{ number_format($subscribers->where('created_at', '>=', now()->startOfWeek())->count()) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 30%;">Full Name</th>
                <th style="width: 40%;">Email Address</th>
                <th style="width: 25%;">Subscribed Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subscribers as $index => $subscriber)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $subscriber->full_name }}</strong></td>
                <td><a href="mailto:{{ $subscriber->email }}" class="email-link">{{ $subscriber->email }}</a></td>
                <td>{{ $subscriber->created_at->format('M d, Y - h:i A') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p><strong>Zimbabwe Media Commission</strong></p>
        <p>This report contains {{ number_format($subscribers->count()) }} newsletter subscribers as of {{ now()->format('F d, Y') }}</p>
        <p style="margin-top: 10px;">For inquiries, contact: {{ config('organization.public_email') }}</p>
    </div>
</body>
</html>
