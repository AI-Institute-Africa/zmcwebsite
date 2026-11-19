<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $receipt_number }}</title>
    <style>
        @page {
            margin: 0.5in;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
        }
        .header-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left {
            width: 70%;
            vertical-align: top;
        }
        .header-right {
            width: 30%;
            text-align: right;
            vertical-align: top;
        }
        .receipt-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000;
            text-transform: uppercase;
        }
        .company-name {
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 2px;
        }
        .company-details {
            color: #555;
            font-size: 12px;
        }
        .logo {
            max-height: 80px;
            margin-bottom: 10px;
        }
        .sub-header-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .customer-section {
            width: 60%;
            vertical-align: top;
        }
        .receipt-info-section {
            width: 40%;
            text-align: right;
            vertical-align: top;
        }
        .section-label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            color: #000;
        }
        .customer-details {
            font-size: 13px;
        }
        .receipt-meta {
            font-size: 14px;
            margin-bottom: 4px;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .main-table th {
            background-color: #f2f2f2;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        .main-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #eee;
        }
        .col-no { width: 5%; }
        .col-desc { width: 50%; }
        .col-qty { width: 10%; text-align: center; }
        .col-price { width: 15%; text-align: right; }
        .col-total { width: 20%; text-align: right; }
        .main-table th.col-qty { text-align: center; }
        .main-table th.col-price { text-align: right; }
        .main-table th.col-total { text-align: right; }

        .footer-table {
            width: 100%;
        }
        .note-section {
            width: 50%;
            vertical-align: top;
        }
        .totals-section {
            width: 50%;
            vertical-align: top;
        }
        .total-row {
            width: 100%;
            margin-bottom: 5px;
            display: table;
        }
        .total-label {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            padding-right: 15px;
        }
        .total-value {
            display: table-cell;
            text-align: right;
            white-space: nowrap;
            width: 1%;
        }
        .final-total-box {
            background-color: #1a1a1a;
            color: #fff;
            padding: 10px 15px;
            border-radius: 4px;
            margin-top: 10px;
            text-align: right;
            width: 80%;
            margin-left: auto;
        }
        .final-total-label {
            font-size: 16px;
            font-weight: bold;
            margin-right: 20px;
        }
        .final-total-value {
            font-size: 18px;
            font-weight: bold;
        }
        .note-box {
            border-top: 1px solid #eee;
            margin-top: 5px;
            padding-top: 5px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="receipt-title">Receipt</div>
                    <div class="company-name">{{ $company_name }}</div>
                    <div class="company-details">
                        {{ $company_email }}<br>
                        {{ $company_phone }}<br>
                        {{ $company_address }}
                    </div>
                </td>
                <td class="header-right">
                    @if(!empty($company_logo))
                        <img src="{{ $company_logo }}" class="logo">
                    @else
                        <img src="{{ public_path('zmc_logo.png') }}" class="logo">
                    @endif
                </td>
            </tr>
        </table>

        <!-- Sub Header -->
        <table class="sub-header-table">
            <tr>
                <td class="customer-section">
                    <div class="section-label">Customer Details:</div>
                    <div class="customer-details">
                        <strong>{{ $application->applicant?->name ?? 'N/A' }}</strong><br>
                        {{ $application->applicant?->email }}<br>
                        @if(isset($application->applicant?->profile_data['address']))
                            {{ $application->applicant?->profile_data['address'] }}
                        @elseif($application->form_data['address'] ?? null)
                            {{ $application->form_data['address'] }}
                        @endif
                    </div>
                </td>
                <td class="receipt-info-section">
                    <div class="receipt-meta"><strong>Receipt #:</strong> {{ $receipt_number }}</div>
                    <div class="receipt-meta"><strong>Receipt Date:</strong> {{ \Carbon\Carbon::parse($payment_date)->format('Y-m-d H:i') }}</div>
                    <div class="receipt-meta"><strong>Receipted by:</strong> {{ $receipted_by ?? 'Accounts Officer' }}</div>
                    <div class="receipt-meta"><strong>Generated on:</strong> {{ $generated_at ?? now()->format('d M Y H:i') }}</div>
                </td>
            </tr>
        </table>

        <!-- Main Table -->
        <table class="main-table">
            <thead>
                <tr>
                    <th class="col-no">No.</th>
                    <th class="col-desc">Description</th>
                    <th class="col-qty">Quantity</th>
                    <th class="col-price">Unit Price</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="col-no">1</td>
                    <td class="col-desc">
                        {{ strtoupper($application->application_type ?? 'Accreditation') }} Application Fee<br>
                        <small style="color: #666;">Reference: {{ $application->reference }}</small>
                    </td>
                    <td class="col-qty">1</td>
                    <td class="col-price">${{ number_format($amount, 2) }}</td>
                    <td class="col-total">${{ number_format($amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Footer -->
        <table class="footer-table">
            <tr>
                <td class="note-section">
                    <div class="section-label">Note:</div>
                    <div class="note-box">
                        Thank you for your payment. This receipt confirms the successful processing of your application fees for the Zimbabwe Media Commission. Please keep this for your records.
                    </div>
                </td>
                <td class="totals-section">
                    <div class="total-row">
                        <span class="total-label">Sub Total:</span>
                        <span class="total-value">${{ number_format($amount, 2) }}</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Tax (0%):</span>
                        <span class="total-value">$0.00</span>
                    </div>
                    <div class="final-total-box">
                        <span class="final-total-label">Total:</span>
                        <span class="final-total-value">${{ number_format($amount, 2) }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 100px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #eee; padding-top: 20px;">
            This is a computer-generated receipt and does not require a physical signature.<br>
            &copy; {{ date('Y') }} {{ $company_name }}
        </div>
    </div>
</body>
</html>
