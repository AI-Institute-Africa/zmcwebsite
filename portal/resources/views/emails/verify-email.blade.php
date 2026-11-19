<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background:#f4f4f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4; padding:40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:#1a1a1a; padding:30px 40px; text-align:center;">
                            <h1 style="color:#f5c518; margin:0; font-size:22px; letter-spacing:1px;">{{ $appName }}</h1>
                            <p style="color:#cccccc; margin:5px 0 0; font-size:13px;">Zimbabwe Media Commission</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:40px;">
                            <h2 style="color:#1a1a1a; margin:0 0 20px; font-size:20px;">Verify Your Email Address</h2>

                            <p style="color:#444; font-size:15px; line-height:1.6; margin:0 0 15px;">
                                Dear <strong>{{ $user->name }}</strong>,
                            </p>

                            <p style="color:#444; font-size:15px; line-height:1.6; margin:0 0 25px;">
                                Thank you for registering on the ZMC Portal. Please click the button below to verify your email address and activate your account.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:10px 0 30px;">
                                        <a href="{{ $verificationUrl }}" style="display:inline-block; background:#f5c518; color:#1a1a1a; text-decoration:none; padding:14px 40px; border-radius:6px; font-weight:bold; font-size:15px; letter-spacing:0.5px;">
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#666; font-size:13px; line-height:1.6; margin:0 0 15px;">
                                If the button above does not work, copy and paste the following link into your browser:
                            </p>
                            <p style="color:#2563eb; font-size:12px; word-break:break-all; background:#f8f9fa; padding:12px; border-radius:4px; margin:0 0 25px;">
                                {{ $verificationUrl }}
                            </p>

                            <p style="color:#666; font-size:13px; line-height:1.6; margin:0;">
                                If you did not create an account on the ZMC Portal, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8f9fa; padding:20px 40px; text-align:center; border-top:1px solid #eee;">
                            <p style="color:#999; font-size:12px; margin:0;">
                                &copy; {{ date('Y') }} Zimbabwe Media Commission. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
