<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email | ZMC Online Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">

    <link href="{{ asset('css/green-theme.css') }}" rel="stylesheet">

    <style>
        :root{
            --bg: #fafafa;
            --card: #ffffff;
            --accent: #f5c518;
            --text: #1a1a1a;
            --muted: #6b7280;
        }
        *{ box-sizing:border-box; margin:0; padding:0; }
        html,body{ height:100%; }
        body{
            font-family:'Inter',sans-serif;
            background: var(--bg);
            display:flex;
            align-items:center;
            justify-content:center;
            min-height:100vh;
            background-image: url("{{ asset('images/zmc_bg.jpg') }}");
            background-size: cover;
            background-position: center;
        }
        .overlay{
            position:fixed; inset:0;
            background:rgba(0,0,0,0.35);
            z-index:0;
        }
        .card{
            position:relative; z-index:1;
            background: var(--card);
            border-radius:16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            max-width:520px;
            width:90%;
            padding:50px 40px;
            text-align:center;
        }
        .logo-wrap{
            margin-bottom:24px;
        }
        .logo-wrap img{
            width:80px; height:80px;
            border-radius:50%;
            border:3px solid #eee;
        }
        .portal-label{
            display:inline-block;
            background:#f0f0f0;
            color:#555;
            font-size:11px;
            font-weight:600;
            letter-spacing:1.5px;
            text-transform:uppercase;
            padding:5px 16px;
            border-radius:20px;
            margin-bottom:16px;
        }
        h1{
            font-family:'Montserrat','Inter',sans-serif;
            font-size:24px;
            font-weight:700;
            color:var(--text);
            margin-bottom:8px;
        }
        .subtitle{
            color:var(--muted);
            font-size:14px;
            line-height:1.6;
            margin-bottom:24px;
        }
        .email-highlight{
            display:inline-block;
            background:#f8f9fa;
            border:1px solid #e5e7eb;
            border-radius:8px;
            padding:12px 20px;
            font-weight:600;
            color:var(--text);
            font-size:15px;
            margin-bottom:24px;
            word-break:break-all;
        }
        .icon-mail{
            width:64px;
            height:64px;
            margin:0 auto 20px;
            background: linear-gradient(135deg, #f5c518 0%, #e0a800 100%);
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .icon-mail svg{
            width:32px;
            height:32px;
            fill:#1a1a1a;
        }
        .steps{
            text-align:left;
            background:#f8f9fa;
            border-radius:10px;
            padding:20px 24px;
            margin-bottom:24px;
        }
        .steps li{
            color:#444;
            font-size:13px;
            line-height:1.8;
            list-style:none;
            padding-left:24px;
            position:relative;
        }
        .steps li::before{
            content:'';
            position:absolute;
            left:0;
            top:8px;
            width:12px;
            height:12px;
            border-radius:50%;
            background:var(--accent);
        }
        .resend-form{
            margin-top:20px;
        }
        .btn-resend{
            display:inline-block;
            background:var(--text);
            color:var(--accent);
            border:none;
            padding:12px 32px;
            border-radius:8px;
            font-weight:600;
            font-size:14px;
            cursor:pointer;
            transition:background 0.2s;
        }
        .btn-resend:hover{
            background:#333;
        }
        .link-login{
            display:block;
            margin-top:16px;
            color:var(--muted);
            font-size:13px;
            text-decoration:none;
        }
        .link-login:hover{
            color:var(--text);
        }
        .alert{
            padding:10px 16px;
            border-radius:8px;
            font-size:13px;
            margin-bottom:16px;
            text-align:left;
        }
        .alert-success{ background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-error{ background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        .alert-info{ background:#dbeafe; color:#1e40af; border:1px solid #93c5fd; }
    </style>
  @include('layouts.favicon')
</head>
<body>
    <div class="overlay"></div>

    <div class="card">
        <div class="logo-wrap">
            <img src="{{ asset('images/zmc_logo.png') }}" alt="ZMC" onerror="this.style.display='none'">
        </div>

        <span class="portal-label">Email Verification</span>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        <div class="icon-mail">
            <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        </div>

        <h1>Check Your Email</h1>
        <p class="subtitle">
            We've sent a verification link to your email address. Please check your inbox and click the link to activate your account.
        </p>

        @if($email)
            <div class="email-highlight">{{ $email }}</div>
        @endif

        <ul class="steps">
            <li>Open your email inbox</li>
            <li>Look for an email from ZMC Portal</li>
            <li>Click the "Verify Email Address" button</li>
            <li>You'll be logged in automatically</li>
        </ul>

        <p style="color:#999; font-size:12px; margin-bottom:16px;">
            Didn't receive the email? Check your spam folder or request a new one below.
        </p>

        <form method="POST" action="{{ route('verification.resend') }}" class="resend-form">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="btn-resend">Resend Verification Email</button>
        </form>

        <a href="{{ route('login') }}" class="link-login">Back to Sign In</a>
    </div>
</body>
</html>
