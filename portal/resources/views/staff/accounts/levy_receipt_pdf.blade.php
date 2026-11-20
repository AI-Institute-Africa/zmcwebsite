<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Levy Payment Receipt - {{ $levy->receipt_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            color: #333;
            padding: 40px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 22pt;
            margin-bottom: 5px;
            color: #1a1a1a;
        }
        .header p {
            font-size: 10pt;
            color: #666;
        }
        .receipt-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            margin: 30px 0;
            color: #1a1a1a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .receipt-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .receipt-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-info td {
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        .receipt-info td:first-child {
            font-weight: bold;
            width: 40%;
            color: #555;
        }
        .receipt-info td:last-child {
            color: #1a1a1a;
        }
        .amount-box {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
            border-radius: 5px;
        }
        .amount-box .label {
            font-size: 10pt;
            color: #666;
            margin-bottom: 5px;
        }
        .amount-box .amount {
            font-size: 24pt;
            font-weight: bold;
            color: #2e7d32;
        }
        .details-section {
            margin: 30px 0;
        }
        .details-section h3 {
            font-size: 13pt;
            margin-bottom: 15px;
            color: #1a1a1a;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 5px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .details-table td:first-child {
            font-weight: bold;
            width: 30%;
            color: #555;
        }
        .calculation-table {
            width: 60%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        .calculation-table tr {
            border-bottom: 1px solid #eee;
        }
        .calculation-table td {
            padding: 10px;
        }
        .calculation-table td:first-child {
            text-align: right;
            color: #666;
        }
        .calculation-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .calculation-table tr:last-child {
            border-top: 2px solid #1a1a1a;
            border-bottom: 2px solid #1a1a1a;
        }
        .calculation-table tr:last-child td {
            font-size: 14pt;
            color: #1a1a1a;
            padding: 15px 10px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .signature-section {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
        }
        .signature-box .line {
            border-top: 1px solid #333;
            margin: 40px 20px 5px;
        }
        .signature-box .label {
            font-size: 9pt;
            color: #666;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100pt;
            color: rgba(76, 175, 80, 0.05);
            z-index: -1;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="watermark">PAID</div>

    <div class="header">
        <h1>ZIMBABWE MEDIA COMMISSION</h1>
        <p>{{ config('organization.head_office_address') }}</p>
        <p>Tel: {{ config('organization.accounts_phone') }} | Email: {{ config('organization.public_email') }} | Web: {{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</p>
    </div>

    <div class="receipt-title">Annual Levy Payment Receipt</div>

    <div class="receipt-info">
        <table>
            <tr>
                <td>Receipt Number:</td>
                <td style="font-weight: bold; font-size: 12pt;">{{ $levy->receipt_number }}</td>
            </tr>
            <tr>
                <td>Levy Reference:</td>
                <td>{{ $levy->reference }}</td>
            </tr>
            <tr>
                <td>Date Issued:</td>
                <td>{{ $levy->payment_date ? $levy->payment_date->format('d F Y') : now()->format('d F Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="details-section">
        <h2>Media Entity Details</h2>
        <table class="details-table">
            <tr>
                <td>Entity Name:</td>
                <td>{{ $levy->entity_name }}</td>
            </tr>
            @if($levy->business_address)
            <tr>
                <td>Business Address:</td>
                <td>{{ $levy->business_address }}</td>
            </tr>
            @endif
            @if($levy->telephone)
            <tr>
                <td>Telephone:</td>
                <td>{{ $levy->telephone }}</td>
            </tr>
            @endif
            @if($levy->user)
            <tr>
                <td>Contact Person:</td>
                <td>{{ $levy->user->name }}</td>
            </tr>
            <tr>
                <td>Email:</td>
                <td>{{ $levy->user->email }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="details-section">
        <h3>Payment Details</h3>
        <table class="details-table">
            <tr>
                <td>Financial Year:</td>
                <td>{{ $levy->financial_year }}</td>
            </tr>
            <tr>
                <td>Payment Method:</td>
                <td>{{ ucwords(str_replace('_', ' ', $levy->payment_method ?? 'N/A')) }}</td>
            </tr>
            @if($levy->payment_reference)
            <tr>
                <td>Payment Reference:</td>
                <td>{{ $levy->payment_reference }}</td>
            </tr>
            @endif
            <tr>
                <td>Payment Date:</td>
                <td>{{ $levy->payment_date ? $levy->payment_date->format('d F Y, H:i') : '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="details-section">
        <h3>Levy Calculation</h3>
        <table class="calculation-table">
            <tr>
                <td>Gross Annual Turnover:</td>
                <td>{{ $levy->currency }} {{ number_format($levy->gross_annual_turnover, 2) }}</td>
            </tr>
            <tr>
                <td>Levy Rate:</td>
                <td>{{ number_format($levy->levy_rate * 100, 2) }}%</td>
            </tr>
            <tr>
                <td><strong>LEVY AMOUNT PAID:</strong></td>
                <td><strong>{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="amount-box">
        <div class="label">Amount Paid</div>
        <div class="amount">{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="line"></div>
            <div class="label">Accounts Officer</div>
            <div class="label">Zimbabwe Media Commission</div>
        </div>
        <div class="signature-box">
            <div class="line"></div>
            <div class="label">Date</div>
        </div>
    </div>

    <div class="footer">
        <p><strong>This is an official receipt for annual levy payment as required under the Zimbabwe Media Commission Act.</strong></p>
        <p>For inquiries, please contact the Accounts Department at {{ config('organization.accounts_email') }} or {{ config('organization.accounts_phone_short') }}</p>
        <p style="margin-top: 10px; font-size: 8pt;">Generated on {{ now()->format('d F Y H:i:s') }}</p>
    </div>
</body>
</html>
