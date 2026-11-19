<!doctype html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZMC Digital Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Montserrat:wght@400;500;600;700;800;900&family=Open+Sans:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --gold: #f5c518;
            --gold-dark: #d4a910;
            --dark: #1a1a1a;
            --dark-medium: #2a2a2a;
            --text-white: #ffffff;
            --text-light: rgba(255,255,255,0.85);
            --text-muted: rgba(255,255,255,0.6);
            --font-heading: 'Montserrat', 'Inter', Helvetica, sans-serif;
            --font-body: 'Source Sans Pro', 'Open Sans', 'Inter', Helvetica, sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Montserrat', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .bg-layer {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: #0a0a0a url('/zmc_building.png') no-repeat center center;
            background-size: cover;
            z-index: 0;
        }

        .bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(
                160deg,
                rgba(0, 0, 0, 0.68) 0%,
                rgba(10, 10, 10, 0.58) 40%,
                rgba(20, 20, 20, 0.48) 100%
            );
            z-index: 1;
        }

        .page-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        .brand {
            text-align: center;
            margin-bottom: 8px;
        }

        .brand .logo-circle {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 6px;
        }

        .brand .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header-label {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 12px;
            padding: 5px 14px;
            border: 1px solid rgba(250, 204, 21, 0.3);
            border-radius: 20px;
            background: rgba(250, 204, 21, 0.08);
        }

        .header-title {
            font-size: 32px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 10px;
            color: var(--text-white);
            line-height: 1.15;
            font-family: var(--font-heading);
        }

        .header-title .highlight-green { color: #facc15; }
        .header-title .highlight-gold { color: #ffffff; }

        .header-subtitle {
            font-size: 15px;
            color: var(--text-muted);
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            width: 100%;
            max-width: 900px;
            margin-bottom: 32px;
        }

        .portal-form {
            display: flex;
        }

        .portal-card {
            font-family: inherit;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 36px 32px 32px;
            cursor: pointer;
            text-align: left;
            color: var(--text-white);
            width: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            text-decoration: none;
        }

        .portal-card:hover {
            background: rgba(255, 255, 255, 0.10);
            border-color: var(--gold);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(250, 204, 21, 0.2);
        }

        .portal-card:focus-visible {
            outline: 3px solid var(--gold);
            outline-offset: 3px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 22px;
        }

        .card-icon.accreditation {
            background: rgba(245, 197, 24, 0.12);
            color: var(--gold);
        }

        .card-icon.registration {
            background: rgba(250, 204, 21, 0.15);
            color: var(--gold);
        }

        .card-icon.media-hub {
            background: rgba(96, 165, 250, 0.16);
            color: #93c5fd;
        }

        .card-title {
            font-size: 17px;
            font-weight: 700;
            text-transform: none;
            letter-spacing: normal;
            margin-bottom: 8px;
            color: var(--text-white);
            font-family: var(--font-heading);
        }

        .card-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
            flex: 1;
            font-family: var(--font-body);
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-stream {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
        }

        .card-stream strong {
            display: block;
            font-size: 13px;
            color: var(--text-light);
            letter-spacing: 0;
            margin-top: 2px;
            text-transform: none;
        }

        .card-arrow {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .portal-card:hover .card-arrow {
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(250, 204, 21, 0.4);
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 32px;
            flex-wrap: wrap;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
        }

        .feature-item i {
            color: var(--gold);
            font-size: 14px;
        }

        .staff-link {
            position: fixed;
            bottom: 20px;
            right: 24px;
            z-index: 10;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.2s ease;
        }

        .staff-link:hover {
            color: var(--gold);
            border-color: rgba(250, 204, 21, 0.3);
        }

        .staff-link i { margin-right: 4px; }

        @media (max-width: 768px) {
            .cards-grid { grid-template-columns: 1fr; max-width: 420px; }
            .header-title { font-size: 28px; }
            .page-wrapper { padding: 30px 16px; }
            .features { gap: 16px; }
            .portal-card { padding: 28px 24px 24px; }
        }

        @media (max-width: 480px) {
            .header-title { font-size: 22px; }
            .header-subtitle { font-size: 13px; }
        }
    </style>
<style>
    .zmc-pilot-notice{position:fixed;top:14px;left:50%;transform:translateX(-50%);z-index:3000;width:min(820px,calc(100% - 24px));display:flex;align-items:center;gap:12px;padding:14px 18px;border:1px solid rgba(212,175,55,0.4);border-radius:16px;background:#ffffff;box-shadow:0 10px 30px rgba(0,0,0,0.08);color:#1a1a1a;font:500 13px/1.6 Inter,sans-serif}.zmc-pilot-notice .notice-icon{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,rgba(46,125,86,0.12)0%,rgba(212,175,55,0.12)100%);flex:0 0 auto}.zmc-pilot-notice .notice-icon i{color:#2e7d56;font-size:20px}.zmc-pilot-notice .notice-content{flex:1 1 auto;display:flex;flex-direction:column;gap:2px}.zmc-pilot-notice .notice-title{font-weight:700;color:#1a2e22;font-size:13px}.zmc-pilot-notice .notice-text{color:#4a4a4a;font-size:12.5px}.zmc-pilot-notice .notice-close{margin-left:auto;background:transparent;border:none;color:#6b6b6b;cursor:pointer;font-size:20px;line-height:1;padding:4px 8px;border-radius:8px}.zmc-pilot-notice .notice-close:hover{background:rgba(0,0,0,0.04);color:#1a1a1a}@media(max-width:600px){.zmc-pilot-notice{top:8px;padding:10px 12px;font-size:12px}.zmc-pilot-notice .notice-icon{width:36px;height:36px}.zmc-pilot-notice .notice-icon i{font-size:18px}}
</style>
  @include('layouts.favicon')
</head>
<body class="landing-page">
    <div class="zmc-pilot-notice" role="status" id="zmcPilotNotice"><div class="notice-icon"><i class="ri-flask-line"></i></div><div class="notice-content"><div class="notice-title">Portal Testing Notice</div><div class="notice-text">This portal is currently in a testing phase and is not yet accepting applications. In the meantime, please continue to submit applications at the Commission's head office or Bulawayo regional office. For further information or guidance, kindly contact the Commission.</div></div><button type="button" id="zmcNoticeClose" class="notice-close" aria-label="Close">&times;</button></div>
    <script>try{var n=document.getElementById("zmcPilotNotice");if(localStorage.getItem("zmc_pilot_notice_closed")==="1"&&n){n.style.display="none";}var b=document.getElementById("zmcNoticeClose");if(b){b.onclick=function(){try{localStorage.setItem("zmc_pilot_notice_closed","1");}catch(e){}if(n){n.style.display="none";}};}}catch(e){}</script>

    

<div class="bg-layer"></div>
<div class="bg-overlay"></div>

<div class="page-wrapper">
    <div class="brand">
        <div class="logo-circle"><img src="/zmc_logo_fresh_nobg.png" alt="ZMC Logo"></div>
    </div>

    <div class="header">
        <div class="header-label">Digital Services Portal</div>
        <h1 class="header-title">
            Zimbabwe <span class="highlight-green">Media</span> <span class="highlight-gold">Commission</span>
        </h1>
        <p class="header-subtitle">Select a service or resource below to get started.</p>
    </div>

    <div class="cards-grid">
        <form method="POST" action="{{ route('public.choose_portal') }}" class="portal-form">
            @csrf
            <input type="hidden" name="portal" value="journalist">
            <button type="submit" class="portal-card">
                <div class="card-icon accreditation">
                    <i class="ri-id-card-line"></i>
                </div>
                <div class="card-title">Accreditation</div>
                <div class="card-desc">Apply for a new media card, renew or replace an existing one, and manage your media practitioner profile.</div>
                <div class="card-footer">
                    <div class="card-stream">
                        Stream
                        <strong>Media Practitioner</strong>
                    </div>
                    <div class="card-arrow">
                        <i class="ri-arrow-right-line"></i>
                    </div>
                </div>
            </button>
        </form>

        <form method="POST" action="{{ route('public.choose_portal') }}" class="portal-form">
            @csrf
            <input type="hidden" name="portal" value="mass_media">
            <button type="submit" class="portal-card">
                <div class="card-icon registration">
                    <i class="ri-building-2-line"></i>
                </div>
                <div class="card-title">Registration</div>
                <div class="card-desc">Register a mass media service, renew licenses, make statutory payments, and manage your media house profile.</div>
                <div class="card-footer">
                    <div class="card-stream">
                        Stream
                        <strong>Media House</strong>
                    </div>
                    <div class="card-arrow">
                        <i class="ri-arrow-right-line"></i>
                    </div>
                </div>
            </button>
        </form>

        <a href="{{ config('services.media_hub.url') }}" target="_blank" rel="noopener noreferrer" class="portal-card" aria-label="Open the ZMC Media Hub in a new tab">
            <div class="card-icon media-hub">
                <i class="ri-links-line"></i>
            </div>
            <div class="card-title">ZMC Media Hub</div>
            <div class="card-desc">Explore ZMC media resources, learning materials, and public information in the Media Hub.</div>
            <div class="card-footer">
                <div class="card-stream">
                    Resource Centre
                    <strong>Media Hub</strong>
                </div>
                <div class="card-arrow">
                    <i class="ri-external-link-line"></i>
                </div>
            </div>
        </a>

    </div>

    <div class="features">
        <div class="feature-item"><i class="ri-shield-check-fill"></i> Secure</div>
        <div class="feature-item"><i class="ri-time-fill"></i> Real-time Tracking</div>
        <div class="feature-item"><i class="ri-verified-badge-fill"></i> Verified</div>
        <div class="feature-item"><i class="ri-24-hours-fill"></i> 24/7 Access</div>
    </div>
</div>

</body>
</html>
