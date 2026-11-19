<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f44336; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
        .box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #f44336; }
        .reason-box { background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 30px; background: #f44336; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table td { padding: 8px; border-bottom: 1px solid #eee; }
        table td:first-child { font-weight: bold; width: 40%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin:0;">⚠ Payment Rejected</h1>
            <p style="margin:5px 0 0;">Action Required</p>
        </div>
        
        <div class="content">
            <p>Dear {{ $levy->user->name }},</p>
            
            <p>We regret to inform you that your annual levy payment submission has been <strong>rejected</strong> by our Accounts Department.</p>
            
            <div class="box">
                <h3 style="margin-top:0; color:#f44336;">Submission Details</h3>
                <table>
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
                        <td>Levy Amount:</td>
                        <td>{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Submitted:</td>
                        <td>{{ $levy->created_at->format('d F Y H:i') }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="reason-box">
                <h3 style="margin-top:0; color:#f57c00;">Reason for Rejection:</h3>
                <p style="margin:10px 0 0;">{{ $reason }}</p>
            </div>
            
            <p><strong>What You Need to Do:</strong></p>
            <ol>
                <li>Review the rejection reason above carefully</li>
                <li>Correct the issues mentioned</li>
                <li>Resubmit your levy payment with correct documentation</li>
                <li>Ensure payment proof is clear and legible</li>
            </ol>
            
            <div style="background:#ffebee; padding:15px; border-radius:5px; margin:20px 0;">
                <p style="margin:0;"><strong>⚠ Important:</strong> Your registration may be marked as expired if the levy is not paid. Please resubmit as soon as possible to maintain your valid status.</p>
            </div>
            
            <div style="text-align:center;">
                <a href="{{ route('mediahouse.levy.create') }}" class="button">Resubmit Levy Payment</a>
            </div>
            
            <p style="margin-top:30px;"><strong>Need Help?</strong><br>
            If you have questions about this rejection, please contact our Accounts Department at <a href="mailto:{{ config('organization.accounts_email') }}">{{ config('organization.accounts_email') }}</a> or call {{ config('organization.accounts_phone_short') }}.</p>
            
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
