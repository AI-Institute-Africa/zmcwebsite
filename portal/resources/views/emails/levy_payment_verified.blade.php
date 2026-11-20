<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4caf50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
        .box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #4caf50; }
        .button { display: inline-block; padding: 12px 30px; background: #4caf50; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table td { padding: 8px; border-bottom: 1px solid #eee; }
        table td:first-child { font-weight: bold; width: 40%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin:0;">✓ Payment Verified</h1>
            <p style="margin:5px 0 0;">Zimbabwe Media Commission</p>
        </div>
        
        <div class="content">
            <p>Dear {{ $levy->user->name }},</p>
            
            <p>We are pleased to inform you that your annual levy payment has been <strong>successfully verified</strong>.</p>
            
            <div class="box">
                <h2 style="margin-top:0; color:#4caf50;">Payment Details</h2>
                <table>
                    <tr>
                        <td>Receipt Number:</td>
                        <td><strong>{{ $levy->receipt_number }}</strong></td>
                    </tr>
                    <tr>
                        <td>Levy Reference:</td>
                        <td>{{ $levy->reference }}</td>
                    </tr>
                    <tr>
                        <td>Entity Name:</td>
                        <td>{{ $levy->entity_name }}</td>
                    </tr>
                    <tr>
                        <td>Financial Year:</td>
                        <td>{{ $levy->financial_year }}</td>
                    </tr>
                    <tr>
                        <td>Amount Paid:</td>
                        <td><strong>{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Payment Date:</td>
                        <td>{{ $levy->payment_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Payment Method:</td>
                        <td>{{ ucwords(str_replace('_', ' ', $levy->payment_method)) }}</td>
                    </tr>
                </table>
            </div>
            
            <p><strong>What This Means:</strong></p>
            <ul>
                <li>Your levy payment for {{ $levy->financial_year }} is complete</li>
                <li>Your media house registration remains valid</li>
                <li>You can download your official receipt from the portal</li>
            </ul>
            
            <div style="text-align:center;">
                <a href="{{ route('mediahouse.levy.index') }}" class="button">View Levy History</a>
            </div>
            
            <p style="margin-top:30px;"><strong>Need a Receipt?</strong><br>
            Log in to your portal to download your official levy payment receipt.</p>
            
            <div class="footer">
                <p><strong>Zimbabwe Media Commission</strong><br>
                {{ config('organization.head_office_address') }}<br>
                Tel: {{ config('organization.accounts_phone') }} | Email: {{ config('organization.accounts_email') }}<br>
                Web: {{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</p>
                
                <p style="margin-top:15px;">This is an automated notification. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
