<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to ZMC Updates</title>
    <style>
        body { margin: 0; padding: 0; background: #f1f5f9; font-family: 'Segoe UI', Arial, sans-serif; }
        .wrapper { max-width: 580px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .header { background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%); padding: 36px 40px; text-align: center; }
        .header img { height: 48px; margin-bottom: 16px; }
        .header h1 { margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
        .header p { margin: 6px 0 0; color: rgba(255,255,255,0.8); font-size: 14px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 18px; font-weight: 600; color: #0f172a; margin: 0 0 12px; }
        .text { font-size: 15px; color: #475569; line-height: 1.7; margin: 0 0 20px; }
        .highlight-box { background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 8px; padding: 16px 20px; margin: 24px 0; }
        .highlight-box p { margin: 0; font-size: 14px; color: #1e40af; line-height: 1.6; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 28px 0; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.6; }
        .footer a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Zimbabwe Media Commission</h1>
            <p>Official Regulator of Zimbabwe's Media Industry</p>
        </div>

        <div class="body">
            <p class="greeting">Hello {{ $fullName }},</p>

            <p class="text">
                Thank you for subscribing to ZMC updates! You're now on our mailing list and will be
                among the first to receive notifications about:
            </p>

            <ul style="color:#475569; font-size:15px; line-height:2; padding-left:20px; margin:0 0 20px;">
                <li>New <strong>job vacancies</strong> and career opportunities</li>
                <li>Open <strong>tenders and procurement</strong> notices</li>
                <li><strong>News, events</strong> and public announcements</li>
                <li>Regulatory <strong>notices and updates</strong></li>
            </ul>

            <div class="highlight-box">
                <p>
                    📧 Updates will be sent to <strong>{{ $email }}</strong>. If you did not sign up for
                    this, you can safely ignore this email — no further messages will be sent.
                </p>
            </div>

            <hr class="divider">

            <p class="text">
                You can visit the ZMC website at any time to view the latest updates and information
                about media regulation in Zimbabwe.
            </p>

            <p class="text" style="margin:0;">
                Warm regards,<br>
                <strong>Zimbabwe Media Commission</strong><br>
                <span style="color:#94a3b8; font-size:13px;">Public Relations Office</span>
            </p>
        </div>

        <div class="footer">
            <p>
                © {{ date('Y') }} Zimbabwe Media Commission. All rights reserved.<br>
                You received this email because you subscribed at <a href="{{ config('app.website_url') }}">{{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</a>.
            </p>
        </div>
    </div>
</body>
</html>
