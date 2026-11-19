@extends('layouts.portal')

@section('title', 'Security Management - IT Admin')

@section('content')
<style>
    .security-page {
        --security-card: var(--c-card, #fff);
        --security-bg: var(--c-bg, #f6f8fb);
        --security-border: var(--c-border, #e5eaf1);
        --security-text: var(--c-text, #172033);
        --security-muted: var(--c-muted, #68758a);
        --security-accent: #d39b06;
        --security-accent-soft: #fff7dc;
        min-height: 100%;
        padding: clamp(16px, 2vw, 30px);
        color: var(--security-text);
        background: var(--security-bg);
        font-family: Inter, sans-serif;
    }
    .security-page * { box-sizing: border-box; }
    .security-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin: 0 auto 22px;
        max-width: 1600px;
    }
    .security-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 9px;
        color: var(--security-accent);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .11em;
        text-transform: uppercase;
    }
    .security-heading h1 {
        margin: 0;
        color: var(--security-text);
        font-size: clamp(25px, 3vw, 34px);
        font-weight: 800;
        letter-spacing: -.04em;
    }
    .security-heading p { margin: 7px 0 0; color: var(--security-muted); font-size: 14px; }
    .security-actions { display: flex; flex-wrap: wrap; gap: 9px; }
    .security-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 0 15px;
        border: 1px solid var(--security-border);
        border-radius: 10px;
        background: var(--security-card);
        color: var(--security-text);
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .security-action:hover { transform: translateY(-1px); border-color: #d5b34a; box-shadow: 0 5px 16px rgba(22,34,54,.08); color: var(--security-text); }
    .security-action--primary { border-color: #172033; background: #172033; color: #fff; }
    .security-action--primary:hover { border-color: #283954; background: #283954; color: #fff; }
    .security-grid { display: grid; grid-template-columns: minmax(0, 1.8fr) minmax(280px, .82fr); gap: 18px; max-width: 1600px; margin: 0 auto; align-items: start; }
    .security-main, .security-side { display: grid; gap: 18px; min-width: 0; }
    .security-card { overflow: hidden; border: 1px solid var(--security-border); border-radius: 16px; background: var(--security-card); box-shadow: 0 3px 14px rgba(24,39,75,.035); }
    .security-card-head { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 19px 21px; border-bottom: 1px solid var(--security-border); }
    .security-card-title { display: flex; align-items: center; gap: 11px; min-width: 0; }
    .security-card-icon { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 11px; background: var(--security-accent-soft); color: #9a7100; font-size: 19px; }
    .security-card-title h2 { margin: 0; color: var(--security-text); font-size: 14px; font-weight: 800; }
    .security-card-title p { margin: 4px 0 0; color: var(--security-muted); font-size: 11px; }
    .security-live { display: inline-flex; align-items: center; gap: 6px; color: var(--security-muted); font-size: 11px; white-space: nowrap; }
    .security-live::before { width: 7px; height: 7px; border-radius: 50%; background: #16a36a; content: ''; }
    .security-metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .security-metric { position: relative; overflow: hidden; padding: 18px; border: 1px solid var(--security-border); border-radius: 14px; background: var(--security-card); }
    .security-metric::after { position: absolute; top: -20px; right: -15px; width: 84px; height: 84px; border-radius: 50%; background: var(--metric-tint, #fff7dc); content: ''; opacity: .75; }
    .security-metric-label { position: relative; z-index: 1; display: flex; align-items: center; gap: 7px; color: var(--security-muted); font-size: 11px; font-weight: 700; }
    .security-metric-value { position: relative; z-index: 1; margin-top: 10px; color: var(--security-text); font-size: 29px; font-weight: 800; letter-spacing: -.04em; line-height: 1; }
    .security-metric-note { position: relative; z-index: 1; margin-top: 8px; color: var(--security-muted); font-size: 10px; }
    .security-metric--danger { --metric-tint: #fff0ee; }
    .security-metric--danger .security-metric-value, .security-metric--danger .security-metric-label i { color: #c34338; }
    .security-metric--neutral { --metric-tint: #edf4ff; }
    .security-table-wrap { overflow-x: auto; }
    .security-table { width: 100%; min-width: 650px; margin: 0; border-collapse: collapse; }
    .security-table th { padding: 12px 16px; border-bottom: 1px solid var(--security-border); background: color-mix(in srgb, var(--security-bg) 68%, var(--security-card)); color: var(--security-muted); font-size: 9px; font-weight: 800; letter-spacing: .09em; text-align: left; text-transform: uppercase; white-space: nowrap; }
    .security-table td { padding: 14px 16px; border-bottom: 1px solid var(--security-border); color: var(--security-text); font-size: 11px; vertical-align: middle; }
    .security-table tbody tr:last-child td { border-bottom: 0; }
    .security-table tbody tr:hover { background: color-mix(in srgb, var(--security-bg) 55%, var(--security-card)); }
    .security-table th:first-child, .security-table td:first-child { padding-left: 21px; }
    .security-table th:last-child, .security-table td:last-child { padding-right: 21px; }
    .security-account { display: flex; align-items: center; gap: 9px; font-weight: 700; }
    .security-account-icon { display: grid; width: 29px; height: 29px; flex: 0 0 29px; place-items: center; border-radius: 9px; background: #f2f4f7; color: #667085; font-size: 14px; }
    .security-time { color: var(--security-muted) !important; white-space: nowrap; }
    .security-ip { color: #475467 !important; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 10px !important; white-space: nowrap; }
    .security-reason { display: inline-flex; align-items: center; gap: 5px; color: #9b3c34; font-size: 10px; font-weight: 700; }
    .security-empty { padding: 38px 20px; color: var(--security-muted); text-align: center; }
    .security-empty i { display: block; margin-bottom: 9px; color: #16a36a; font-size: 25px; }
    .security-empty strong { display: block; margin-bottom: 4px; color: var(--security-text); font-size: 12px; }
    .security-empty span { font-size: 11px; }
    .security-pagination { padding: 12px 18px; border-top: 1px solid var(--security-border); }
    .security-pagination nav { display: flex; justify-content: flex-end; }
    .security-posture { padding: 19px; }
    .security-posture h2 { display: flex; align-items: center; gap: 9px; margin: 0 0 6px; color: var(--security-text); font-size: 14px; font-weight: 800; }
    .security-posture > p { margin: 0 0 16px; color: var(--security-muted); font-size: 11px; line-height: 1.6; }
    .security-policy { display: flex; align-items: flex-start; gap: 10px; padding: 12px 0; border-top: 1px solid var(--security-border); }
    .security-policy-icon { display: grid; width: 30px; height: 30px; flex: 0 0 30px; place-items: center; border-radius: 9px; background: #f1f5f9; color: #596579; font-size: 15px; }
    .security-policy strong { display: block; color: var(--security-text); font-size: 11px; }
    .security-policy span { display: block; margin-top: 3px; color: var(--security-muted); font-size: 10px; line-height: 1.45; }
    .security-notice { display: flex; gap: 10px; padding: 13px; border: 1px solid #eadcae; border-radius: 12px; background: #fffaf0; color: #725a1b; font-size: 10px; line-height: 1.55; }
    .security-notice i { flex: 0 0 auto; font-size: 15px; }
    .security-session-copy { padding: 14px 20px; border-top: 1px solid var(--security-border); color: var(--security-muted); font-size: 10px; }
    .security-session-account strong { display: block; color: var(--security-text); font-size: 11px; }
    .security-session-account span { display: block; margin-top: 3px; color: var(--security-muted); font-size: 10px; }
    .security-session-count { color: var(--security-muted); font-size: 10px; font-weight: 700; white-space: nowrap; }
    .security-unavailable { margin: 16px; padding: 14px; border: 1px solid #eadcae; border-radius: 11px; background: #fffaf0; color: #725a1b; font-size: 11px; line-height: 1.55; }
    .security-page .pagination { margin: 0; }
    .security-page .page-link { color: #475467; }
    .security-config-form { display: grid; gap: 10px; margin-top: 14px; }
    .security-config-form label { color: var(--security-muted); font-size: 10px; font-weight: 700; }
    .security-config-form .form-control { min-height: 38px; border-color: var(--security-border); border-radius: 9px; font-size: 11px; }
    .security-config-button { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 0 13px; border: 0; border-radius: 9px; background: #172033; color: #fff; font-size: 11px; font-weight: 700; }
    .security-config-button:hover { background: #283954; color: #fff; }
    .security-config-button--danger { background: #fff; color: #b42318; border: 1px solid #f1c7c4; }
    .security-config-button--danger:hover { background: #fff1f0; color: #912018; }
    .security-block-list { display: grid; gap: 9px; margin-top: 14px; }
    .security-block-item { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid var(--security-border); border-radius: 10px; }
    .security-block-item strong { display: block; color: var(--security-text); font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 10px; }
    .security-block-item span { display: block; margin-top: 4px; color: var(--security-muted); font-size: 9px; line-height: 1.4; }
    .security-tls-list { display: grid; gap: 8px; margin: 14px 0; }
    .security-tls-item { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 9px 10px; border: 1px solid var(--security-border); border-radius: 9px; }
    .security-tls-item strong { color: var(--security-text); font-size: 10px; overflow-wrap: anywhere; }
    .security-tls-badge { flex: 0 0 auto; padding: 4px 7px; border-radius: 999px; background: #eef2f6; color: #596579; font-size: 9px; font-weight: 800; }
    .security-tls-badge.is-verified { background: #e8f7ef; color: #167447; }
    .security-tls-badge.is-failed { background: #fff0ee; color: #a52c25; }
    .security-throttle-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin: 14px 0; }
    .security-throttle-stat { padding: 11px; border: 1px solid var(--security-border); border-radius: 10px; background: var(--security-bg); }
    .security-throttle-stat strong { display: block; color: var(--security-text); font-size: 20px; line-height: 1.1; }
    .security-throttle-stat span { display: block; margin-top: 5px; color: var(--security-muted); font-size: 9px; line-height: 1.35; }
    .security-throttle-status { display: inline-flex; align-items: center; gap: 6px; margin-top: 11px; padding: 5px 8px; border-radius: 999px; background: #e8f7ef; color: #167447; font-size: 9px; font-weight: 800; }
    .security-throttle-status.is-disabled { background: #fff0ee; color: #a52c25; }
    .security-throttle-status::before { width: 6px; height: 6px; border-radius: 50%; background: currentColor; content: ''; }
    .security-flash { max-width: 1600px; margin: 0 auto 16px; padding: 11px 14px; border-radius: 10px; font-size: 12px; }
    .security-flash--success { border: 1px solid #b9e4cb; background: #effaf3; color: #17633b; }
    .security-flash--error { border: 1px solid #f1c7c4; background: #fff5f4; color: #912018; }
    @media (max-width: 1050px) { .security-grid { grid-template-columns: minmax(0, 1fr); } .security-side { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 680px) { .security-heading { align-items: flex-start; flex-direction: column; } .security-actions { width: 100%; } .security-actions .security-action { flex: 1; } .security-side { grid-template-columns: minmax(0, 1fr); } .security-card-head { align-items: flex-start; } .security-metric { padding: 15px; } }
</style>

<main class="security-page">
    <header class="security-heading">
        <div>
            <div class="security-eyebrow"><i class="ri-shield-check-line"></i> IT Admin · Security</div>
            <h1>Security overview</h1>
            <p>Review login activity, session visibility, and the portal’s configured protections.</p>
        </div>
        <div class="security-actions">
            <a href="{{ route('staff.it.dashboard') }}" class="security-action"><i class="ri-arrow-left-line"></i> Dashboard</a>
            <a href="{{ route('staff.it.security-mgmt') }}" class="security-action security-action--primary"><i class="ri-refresh-line"></i> Refresh</a>
        </div>
    </header>

    @if(session('success'))<div class="security-flash security-flash--success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="security-flash security-flash--error" role="alert">{{ session('error') }}</div>@endif
    @error('ip_address')<div class="security-flash security-flash--error" role="alert">{{ $message }}</div>@enderror

    <section class="security-grid" aria-label="Security monitoring">
        <div class="security-main">
            <div class="security-metrics" aria-label="Failed login summary">
                @if(!empty($loginActivityAvailable))
                    <article class="security-metric security-metric--danger">
                        <div class="security-metric-label"><i class="ri-error-warning-line"></i> Failed logins · 15 minutes</div>
                        <div class="security-metric-value">{{ number_format($failedLoginsLast15Minutes) }}</div>
                        <div class="security-metric-note">Recorded authentication failures</div>
                    </article>
                    <article class="security-metric security-metric--neutral">
                        <div class="security-metric-label"><i class="ri-history-line"></i> Failed logins · 24 hours</div>
                        <div class="security-metric-value">{{ number_format($failedLoginsLast24Hours) }}</div>
                        <div class="security-metric-note">Rolling total from login activity</div>
                    </article>
                @else
                    <article class="security-metric security-metric--neutral" style="grid-column: 1 / -1;">
                        <div class="security-metric-label"><i class="ri-database-2-line"></i> Login activity unavailable</div>
                        <div class="security-metric-note">The database does not expose the expected login activity fields.</div>
                    </article>
                @endif
            </div>

            <section class="security-card">
                <div class="security-card-head">
                    <div class="security-card-title">
                        <span class="security-card-icon"><i class="ri-login-box-line"></i></span>
                        <div>
                            <h2>Failed login attempts</h2>
                            <p>Most recent attempts recorded by the portal</p>
                        </div>
                    </div>
                    @if(!empty($loginActivityAvailable))<span class="security-live">Live records</span>@endif
                </div>
                @if(empty($loginActivityAvailable))
                    <div class="security-unavailable">Login activity is unavailable because this database does not have the expected login activity fields.</div>
                @else
                    <div class="security-table-wrap">
                        <table class="security-table">
                            <thead>
                                <tr><th>Time</th><th>Attempted account</th><th>Source IP</th><th>Result</th></tr>
                            </thead>
                            <tbody>
                                @forelse($failedLogins as $attempt)
                                    <tr>
                                        <td class="security-time">{{ $attempt->login_at?->timezone(config('app.timezone'))->format('d M Y · H:i') ?? 'Unknown' }}</td>
                                        <td><div class="security-account"><span class="security-account-icon"><i class="ri-user-line"></i></span>{{ $attempt->account_name ?: 'Unknown account' }}</div></td>
                                        <td class="security-ip">{{ $attempt->ip_address ?: 'Unavailable' }}</td>
                                        <td><span class="security-reason"><i class="ri-close-circle-line"></i>{{ $attempt->failure_reason ?: 'Invalid credentials' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><div class="security-empty"><i class="ri-shield-check-line"></i><strong>No failed attempts recorded</strong><span>There are no failed login events to show in this activity list.</span></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($failedLogins->hasPages())<div class="security-pagination">{{ $failedLogins->links() }}</div>@endif
                @endif
            </section>

            <section class="security-card">
                <div class="security-card-head">
                    <div class="security-card-title">
                        <span class="security-card-icon"><i class="ri-device-line"></i></span>
                        <div>
                            <h2>Active sessions</h2>
                            <p>Newest sessions, ordered by their last activity</p>
                        </div>
                    </div>
                    @if(!empty($databaseSessionsAvailable))
                        <span class="security-session-count">{{ $sessions->total() }} latest</span>
                    @endif
                </div>
                @if(empty($databaseSessionsAvailable))
                    <div class="security-unavailable">{{ $sessionTrackingMessage ?? 'Session tracking is currently unavailable.' }}</div>
                @else
                    <div class="security-table-wrap">
                        <table class="security-table">
                            <thead><tr><th>Account</th><th>Source IP</th><th>Browser / device</th><th>Last active</th><th></th></tr></thead>
                            <tbody>
                                @forelse($sessions as $session)
                                    <tr>
                                        <td class="security-session-account">
                                            <strong>{{ $session->user_name ?: 'Guest session' }}</strong>
                                            <span>{{ $session->user_email ?: ($session->user_id ? 'Account email unavailable' : 'Not signed in') }}</span>
                                        </td>
                                        <td class="security-ip">{{ $session->ip_address ?: 'Unavailable' }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($session->user_agent ?: 'Unknown device', 72) }}</td>
                                        <td class="security-time">{{ Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('staff.it.security.session.logout', $session->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Terminate session"><i class="ri-logout-box-r-line"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="security-empty"><i class="ri-device-line"></i><strong>No active database sessions</strong><span>No session records were found.</span></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($sessions->hasPages())<div class="security-pagination">{{ $sessions->links() }}</div>@endif
                    <div class="security-session-copy">
                        Showing {{ $sessions->firstItem() ?? 0 }}–{{ $sessions->lastItem() ?? 0 }} of {{ $sessions->total() }} latest sessions, 10 per page. Use the action button to terminate a session.
                    </div>
                @endif
            </section>
        </div>

        <aside class="security-side" aria-label="Security configuration">
            <section class="security-card security-posture">
                <h2><i class="ri-speed-up-line" style="color:var(--security-accent);"></i> Request throttling</h2>
                <p>A portal-wide baseline limits each IP to {{ number_format($globalRateLimitPerMinute) }} requests per minute. Route-specific sign-in and OTP limits remain active independently.</p>
                <span class="security-throttle-status {{ $globalRateLimitEnabled ? '' : 'is-disabled' }}">
                    Global baseline {{ $globalRateLimitEnabled ? 'enabled' : 'disabled' }}
                </span>
                <div class="security-throttle-stats">
                    <div class="security-throttle-stat">
                        <strong>{{ $throttledIpsLast24Hours === null ? '—' : number_format($throttledIpsLast24Hours) }}</strong>
                        <span>Unique IPs throttled in the last 24 hours</span>
                    </div>
                    <div class="security-throttle-stat">
                        <strong>{{ $throttleEventsLast24Hours === null ? '—' : number_format($throttleEventsLast24Hours) }}</strong>
                        <span>Recorded throttle incidents, deduplicated per IP each minute</span>
                    </div>
                </div>
                @if(empty($rateLimitEventsAvailable))
                    <div class="security-unavailable">Apply the rate-limit event migration to enable the 24-hour IP and event counts.</div>
                @endif
                <form action="{{ route('staff.it.security.toggle_rate_limiting') }}" method="POST" class="security-config-form">
                    @csrf
                    <button class="security-config-button {{ $globalRateLimitEnabled ? 'security-config-button--danger' : '' }}" type="submit">
                        <i class="{{ $globalRateLimitEnabled ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                        {{ $globalRateLimitEnabled ? 'Disable global baseline' : 'Enable global baseline' }}
                    </button>
                </form>
                <div class="security-policy">
                    <span class="security-policy-icon"><i class="ri-lock-password-line"></i></span>
                    <div><strong>Staff sign-in</strong><span>5 attempts per minute · always enforced</span></div>
                </div>
                <div class="security-policy">
                    <span class="security-policy-icon"><i class="ri-key-2-line"></i></span>
                    <div><strong>OTP verification</strong><span>10 attempts per minute · always enforced</span></div>
                </div>
                <div class="security-policy">
                    <span class="security-policy-icon"><i class="ri-mail-send-line"></i></span>
                    <div><strong>OTP resend</strong><span>3 requests per 5 minutes · always enforced</span></div>
                </div>
            </section>

            <section class="security-card security-posture">
                <h2><i class="ri-forbid-2-line" style="color:var(--security-accent);"></i> Portal IP blocklist</h2>
                <p>
                    @if(!empty($ipBlockingAvailable))
                        Blocks matching addresses from Laravel web routes. Your current address is protected from being blocked here. This does not modify the VPS firewall or CDN.
                    @elseif(!empty($ipBlockTableAvailable))
                        The blocklist is disabled by the <code>PORTAL_IP_BLOCKING_ENABLED</code> setting.
                    @else
                        Apply the security blocklist migration before portal IP blocking can take effect.
                    @endif
                </p>
                @if(!empty($ipBlockingAvailable))
                <form class="security-config-form" action="{{ route('staff.it.security.ip.block') }}" method="POST">
                    @csrf
                    <div>
                        <label for="security-ip-address">IP address</label>
                        <input class="form-control" id="security-ip-address" name="ip_address" value="{{ old('ip_address') }}" placeholder="e.g. 203.0.113.10" required>
                    </div>
                    <div>
                        <label for="security-ip-reason">Reason (optional)</label>
                        <input class="form-control" id="security-ip-reason" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Why is this address blocked?">
                    </div>
                    <button class="security-config-button" type="submit"><i class="ri-forbid-2-line"></i> Add IP block</button>
                </form>
                @endif
                <div class="security-block-list">
                    @if(!empty($ipBlockTableAvailable))
                        @forelse($blockedIps as $block)
                            <div class="security-block-item">
                                <div>
                                    <strong>{{ $block->ip_address }}</strong>
                                    <span>{{ $block->reason ?: 'No reason provided' }} · {{ $block->created_at?->diffForHumans() }}</span>
                                </div>
                                @if(!empty($ipBlockingAvailable))
                                    <form action="{{ route('staff.it.security.ip.unblock', $block) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="security-config-button security-config-button--danger" aria-label="Unblock {{ $block->ip_address }}">Remove</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div class="security-block-item"><div><strong>No addresses blocked</strong><span>Adding an address applies the block to portal web requests.</span></div></div>
                        @endforelse
                    @endif
                </div>
            </section>

            <section class="security-card security-posture">
                <h2><i class="ri-shield-check-line" style="color:var(--security-accent);"></i> Public TLS checks</h2>
                <p>Checks HTTPS from this server and verifies certificates using the system trust store. It does not change certificates or proxy settings.</p>
                <div class="security-tls-list">
                    @foreach($tlsAuditHosts as $host)
                        @php($tlsResult = collect($tlsAuditResults)->firstWhere('host', $host))
                        <div class="security-tls-item">
                            <strong>{{ $host }}</strong>
                            @if($tlsResult)
                                <span class="security-tls-badge {{ $tlsResult['verified'] ? 'is-verified' : 'is-failed' }}">{{ $tlsResult['verified'] ? 'Certificate valid' : 'TLS check failed' }}</span>
                            @else
                                <span class="security-tls-badge">Not checked</span>
                            @endif
                        </div>
                        @if($tlsResult)<div class="small text-muted mb-1">{{ $tlsResult['message'] }} · {{ $tlsResult['checked_at'] }}</div>@endif
                    @endforeach
                </div>
                <form action="{{ route('staff.it.security.ssl_audit') }}" method="POST">
                    @csrf
                    <button class="security-config-button" type="submit"><i class="ri-refresh-line"></i> Run TLS checks</button>
                </form>
            </section>
        </aside>
    </section>
</main>
@endsection
