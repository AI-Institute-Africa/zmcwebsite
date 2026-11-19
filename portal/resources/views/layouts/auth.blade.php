<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Secure Access') | Zimbabwe Media Commission</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --auth-ink: #17191c;
            --auth-muted: #667085;
            --auth-border: #e4e7ec;
            --auth-accent: #f4c928;
            --auth-accent-dark: #dfae0b;
            --auth-card: #fff;
            --auth-radius: 18px;
            --auth-shadow: 0 24px 70px rgba(0, 0, 0, .24);
            --auth-focus: 0 0 0 4px rgba(244, 201, 40, .2);
            --ease-out: cubic-bezier(0.22, 0.85, 0.3, 1);
            --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
            --dur-fast: 160ms;
            --dur-base: 220ms;
            --dur-slow: 500ms;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            min-height: 100vh; margin: 0; padding: 32px 18px 22px; display: grid; place-items: center;
            color: var(--auth-ink); background: #101216 url("{{ asset('zmc_building.png') }}") center / cover fixed no-repeat;
            font: 400 14px/1.55 Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased; position: relative;
        }
        body::before { content: ""; position: fixed; inset: 0; z-index: 0; pointer-events: none; background: radial-gradient(ellipse at 50% 12%, rgba(244, 201, 40, .2), transparent 36%), linear-gradient(135deg, rgba(8, 11, 15, .82), rgba(10, 13, 17, .72)); }
        button, input { font: inherit; }
        a { color: inherit; }
        .auth-shell { position: relative; z-index: 1; width: min(100%, 360px); margin: auto; }
        .auth-brand { display: flex; flex-direction: column; align-items: center; gap: 4px; margin: 0 auto 9px; text-align: center; text-decoration: none; }
        .auth-logo { width: 50px; height: 50px; display: grid; place-items: center; overflow: hidden; padding: 5px; border-radius: 50%; background: #fff; box-shadow: 0 8px 30px rgba(0,0,0,.3); }
        .auth-logo img { width: 100%; height: 100%; object-fit: contain; }
        .auth-brand-eyebrow { color: #ffdc56; font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .auth-brand-title { margin: 0; color: #fff; font: 800 clamp(19px, 4.6vw, 25px)/1.2 Montserrat, Inter, sans-serif; letter-spacing: .015em; text-shadow: 0 2px 10px rgba(0,0,0,.3); text-transform: uppercase; }
        .auth-brand-title span { color: var(--auth-accent); }
        .auth-card { position: relative; overflow: visible; padding: 16px; border: 1px solid rgba(255,255,255,.7); border-radius: var(--auth-radius); background: var(--auth-card); box-shadow: var(--auth-shadow); }
        .auth-page-header { margin: 0 0 11px; text-align: center; }
        .auth-eyebrow { display: inline-flex; align-items: center; gap: 6px; margin-bottom: 10px; padding: 5px 11px; border: 1px solid #f5e6a7; border-radius: 99px; background: #fff9df; color: #5d4b08; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .auth-title { margin: 0 0 5px; color: #17191c; font-size: 24px; font-weight: 750; letter-spacing: -.035em; line-height: 1.25; }
        .auth-subtitle { margin: 0; color: var(--auth-muted); font-size: 13px; }
        .auth-field { min-width: 0; margin-bottom: 7px; }
        .auth-label { display: block; margin-bottom: 3px; color: #282b30; font-size: 11px; font-weight: 700; }
        .auth-input-wrap { position: relative; }
        .auth-input-icon { position: absolute; top: 50%; left: 13px; z-index: 1; color: #858b95; font-size: 17px; transform: translateY(-50%); pointer-events: none; }
        .auth-input { display: block; width: 100%; height: 36px; padding: 0 36px; border: 1px solid var(--auth-border); border-radius: 9px; outline: none; background: #fff; color: #202329; transition: border-color var(--dur-base) var(--ease-out), background-color var(--dur-base) var(--ease-out), box-shadow var(--dur-base) var(--ease-out); }
        .auth-input::placeholder { color: #a1a6af; }
        .auth-input:focus { border-color: var(--auth-accent-dark); box-shadow: var(--auth-focus); }
        .auth-input-wrap:focus-within .auth-input-icon { color: var(--auth-accent-dark); }
        .auth-input[readonly] { background: #f8f9fa; color: #555b65; }
        .auth-input.is-invalid { border-color: #dc3545; }
        .auth-input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(220,53,69,.12); }
        .auth-toggle { position: absolute; top: 50%; right: 5px; display: grid; width: 36px; height: 36px; place-items: center; padding: 0; border: 0; border-radius: 9px; background: transparent; color: #747b86; cursor: pointer; transform: translateY(-50%); transition: background-color var(--dur-base) var(--ease-out), color var(--dur-base) var(--ease-out); }
        .auth-toggle:hover, .auth-toggle:focus-visible { background: #f4f5f6; color: #25282d; outline: none; }
        .auth-toggle i { font-size: 18px; transition: transform var(--dur-base) var(--ease-out), opacity var(--dur-fast) var(--ease-out); }
        .auth-toggle[aria-pressed="true"] i { transform: rotate(12deg) scale(1.04); }
        .auth-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .auth-error { display: flex; align-items: flex-start; gap: 5px; margin-top: 5px; color: #bd2838; font-size: 11px; line-height: 1.4; }
        .auth-error i { margin-top: 1px; font-size: 13px; }
        .auth-alert { display: flex; align-items: flex-start; gap: 9px; margin: 0 0 16px; padding: 11px 12px; border: 1px solid #c9e8d2; border-radius: 11px; background: #f0fbf3; color: #176b36; font-size: 12px; }
        .auth-alert.error { border-color: #f3c8cd; background: #fff3f4; color: #a82331; }
        .auth-alert i { font-size: 17px; }
        .auth-pair-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: -2px 0 7px; }
        .auth-pair-row .auth-label { margin: 0; }
        .auth-link { color: #373b42; font-size: 12px; font-weight: 700; text-decoration: underline; text-decoration-color: transparent; transition: color var(--dur-base) var(--ease-out), text-decoration-color var(--dur-base) var(--ease-out); }
        .auth-link:hover { color: #9a7600; text-decoration-color: currentColor; }
        .auth-check-row { display: flex; align-items: center; gap: 8px; margin: 1px 0 11px; color: var(--auth-muted); font-size: 12px; }
        .auth-check-row input { width: 15px; height: 15px; margin: 0; accent-color: #27292d; }
        .auth-note { margin: 13px 0 0; color: var(--auth-muted); font-size: 12px; text-align: center; }
        .auth-note strong { color: #25282d; }
        .auth-button { display: flex; width: 100%; min-height: 46px; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; padding: 10px 14px; border: 1px solid #176b42; border-radius: 10px; background: #1f7a4d; color: #fff; cursor: pointer; font-size: 14px; font-weight: 800; letter-spacing: .01em; box-shadow: 0 6px 16px rgba(31, 122, 77, .24); transition: background-color var(--dur-base) var(--ease-out), color var(--dur-base) var(--ease-out), box-shadow var(--dur-base) var(--ease-out), transform var(--dur-base) var(--ease-out), border-color var(--dur-base) var(--ease-out); }
        .auth-button:hover { transform: translateY(-1px); border-color: var(--auth-accent); background: var(--auth-accent); color: #222; box-shadow: 0 8px 22px rgba(244, 201, 40, .3); }
        .auth-button:focus-visible { outline: none; box-shadow: var(--auth-focus); }
        .auth-button:active { transform: translateY(1px); }
        .auth-divider { display: flex; align-items: center; gap: 10px; margin: 18px 0 12px; color: #8a9099; font-size: 11px; }
        .auth-divider::before, .auth-divider::after { content: ""; height: 1px; flex: 1; background: var(--auth-border); }
        .auth-footer-text { margin-top: 10px; color: var(--auth-muted); font-size: 12px; text-align: center; }
        .auth-footer-text-compact { margin-top: 0; }
        .auth-footer-text a { color: #24272c; font-weight: 700; text-decoration: underline; text-decoration-color: transparent; transition: color var(--dur-base) var(--ease-out), text-decoration-color var(--dur-base) var(--ease-out); }
        .auth-footer-text a:hover { color: #957100; text-decoration-color: currentColor; }
        .auth-copyright { margin-top: 16px; color: rgba(255,255,255,.73); font-size: 11px; text-align: center; }
        @keyframes card-rise { from { opacity: 0; transform: translateY(16px) scale(.99); } to { opacity: 1; transform: none; } }
        @keyframes auth-shake { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-4px); } 40% { transform: translateX(4px); } 60% { transform: translateX(-3px); } 80% { transform: translateX(3px); } }
        @keyframes alert-in { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
        @media (prefers-reduced-motion: no-preference) {
            .auth-card { animation: card-rise var(--dur-slow) var(--ease-out) both; }
            .auth-input-icon { transition: color var(--dur-base) var(--ease-out); }
            .auth-error, .auth-alert.error { animation: auth-shake 350ms var(--ease-in-out); }
            .auth-alert:not(.error) { animation: alert-in var(--dur-base) var(--ease-out) both; }
        }
        @media (max-width: 560px) {
            body { padding: 20px 13px 16px; background-attachment: scroll; }
            .auth-shell { max-width: 360px; }
            .auth-brand { margin-bottom: 10px; }
            .auth-logo { width: 46px; height: 46px; }
            .auth-card { padding: 15px 13px; border-radius: 16px; }
            .auth-title { font-size: 20px; }
            .auth-field { margin-bottom: 8px; }
        }
        @media (max-width: 380px) { .auth-grid { grid-template-columns: 1fr; gap: 0; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; animation: none !important; transition-duration: .01ms !important; } }
    </style>
    @stack('styles')
</head>
<body>
    <main class="auth-shell">
        <a class="auth-brand" href="{{ url('/') }}" aria-label="Zimbabwe Media Commission home">
            <span class="auth-logo"><img src="{{ asset('zmc_logo_fresh_nobg.png') }}" alt="Zimbabwe Media Commission logo"></span>
            <span class="auth-brand-eyebrow">Digital Services Portal</span>
            <span class="auth-brand-title">Zimbabwe <span>Media</span> Commission</span>
        </a>
        <section class="auth-card" aria-label="@yield('title', 'Secure Access')">
            @yield('content')
        </section>
        <footer class="auth-copyright">&copy; {{ date('Y') }} Zimbabwe Media Commission</footer>
    </main>
    @stack('scripts')
</body>
</html>
