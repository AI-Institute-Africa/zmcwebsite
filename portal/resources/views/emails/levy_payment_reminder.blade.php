<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ff9800; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
        .box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #ff9800; }
        .button { display: inline-block; padding: 12px 30px; background: #ff9800; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        .highlight { background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin:0;">📅 Annual Levy Payment Due</h1>
            <p style="margin:5px 0 0;">Zimbabwe Media Commission</p>
        </div>
        
        <div class="content">
            <p>Dear {{ $user->name }},</p>
            
            <p>This is a friendly reminder that your annual levy payment for the <strong>{{ $financialYear }}</strong> financial year is now due.</p>
            
            <div class="highlight">
                <p style="margin:0; font-size:16px;"><strong>📆 Due Date: {{ \Carbon\Carbon::parse($dueDate)->format('d F Y') }}</strong></p>
            </div>
            
            <div class="box">
                <h2 style="margin-top:0; color:#ff9800;">About the Annual Levy</h2>
                <p>Under the Zimbabwe Media Commission Act, every registered media house is required to pay an annual levy equal to <strong>0.5% of gross annual turnover</strong>.</p>
                
                <p><strong>Why This Matters:</strong></p>
                <ul style="margin:10px 0;">
                    <li>Maintains your media house registration validity</li>
                    <li>Ensures compliance with ZMC regulations</li>
                    <li>Prevents your registration from being marked as expired</li>
                </ul>
            </div>
            
            <p><strong>How to Submit Your Levy:</strong></p>
            <ol>
                <li>Log in to your Media House Portal</li>
                <li>Navigate to "Annual Levy (AP6)" section</li>
                <li>Complete the AP6 levy declaration form</li>
                <li>Upload required documents:
                    <ul>
                        <li>Audited financial statements</li>
                        <li>Proof of payment</li>
                    </ul>
                </li>
                <li>Submit for verification</li>
            </ol>
            
            <p><strong>Payment Options:</strong></p>
            <ul>
                <li>PayNow (Online payment gateway)</li>
                <li>Bank transfer</li>
                <li>EcoCash / Mobile money</li>
                <li>Cash payment at ZMC offices</li>
            </ul>
            
            <div style="text-align:center;">
                <a href="{{ route('mediahouse.levy.create') }}" class="button">Submit Levy Declaration Now</a>
            </div>
            
            <div style="background:#ffebee; padding:15px; border-radius:5px; margin:20px 0;">
                <p style="margin:0;"><strong>⚠ Important:</strong> Failure to pay the annual levy by the due date may result in your registration being marked as expired. This could affect your ability to operate legally.</p>
            </div>
            
            <p><strong>Already Paid?</strong><br>
            If you have already submitted your levy payment, please disregard this reminder. Your submission is being processed by our Accounts Department.</p>
            
            <p style="margin-top:30px;"><strong>Questions?</strong><br>
            Contact our Accounts Department at <a href="mailto:{{ config('organization.accounts_email') }}">{{ config('organization.accounts_email') }}</a> or call {{ config('organization.accounts_phone_short') }}.</p>
            
            <div class="footer">
                <p><strong>Zimbabwe Media Commission</strong><br>
                {{ config('organization.head_office_address') }}<br>
                Tel: {{ config('organization.accounts_phone') }} | Email: {{ config('organization.accounts_email') }}<br>
                Web: {{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</p>
                
                <p style="margin-top:15px;">This is an automated reminder. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
