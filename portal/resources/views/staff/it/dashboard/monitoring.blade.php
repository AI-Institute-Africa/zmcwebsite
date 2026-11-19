@extends('layouts.portal')

@section('title', 'System Monitoring - IT Admin')

@section('content')
<style>
    .monitoring-page {
        --monitor-bg: var(--zmc-gray-bg, #f5f5f5);
        --monitor-card: var(--zmc-white, #fff);
        --monitor-border: var(--zmc-gray, #e0e0e0);
        --monitor-text: var(--zmc-primary, #1a1a1a);
        --monitor-muted: var(--zmc-gray-dark, #6b7280);
        --monitor-accent: var(--zmc-yellow, #f5c518);
        --monitor-accent-dark: var(--zmc-yellow-dark, #d4a910);
        min-height: 100%;
        padding: clamp(16px, 2vw, 30px);
        background: var(--monitor-bg);
        color: var(--monitor-text);
        font-family: Inter, sans-serif;
    }
    .monitoring-page * { box-sizing: border-box; }
    .monitoring-shell { width: min(100%, 1500px); margin: 0 auto; }
    .monitoring-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 20px; }
    .monitoring-kicker { display: inline-flex; align-items: center; gap: 7px; margin-bottom: 7px; padding: 5px 8px; border-radius: 6px; background: var(--monitor-accent); color: var(--monitor-text); font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .monitoring-header h1 { margin: 0; color: var(--monitor-text); font-size: clamp(25px, 3vw, 34px); font-weight: 800; letter-spacing: -.04em; }
    .monitoring-header p { margin: 6px 0 0; color: var(--monitor-muted); font-size: 12px; }
    .monitoring-actions { display: flex; flex-wrap: wrap; gap: 8px; }
    .monitoring-button { display: inline-flex; min-height: 38px; align-items: center; justify-content: center; gap: 7px; padding: 0 13px; border: 1px solid var(--monitor-border); border-radius: 10px; background: #fff; color: var(--monitor-text); font-size: 11px; font-weight: 700; text-decoration: none; }
    .monitoring-button--primary { border-color: var(--monitor-accent); background: var(--monitor-text); color: var(--monitor-accent); }
    .monitoring-button:hover { border-color: var(--monitor-accent-dark); color: var(--monitor-text); }
    .monitoring-button--primary:hover { border-color: var(--monitor-text); background: var(--monitor-accent); color: var(--monitor-text); box-shadow: 0 4px 12px rgba(245,197,24,.28); }
    .monitoring-flash { margin-bottom: 14px; padding: 11px 13px; border: 1px solid #b9e4cb; border-radius: 10px; background: #effaf3; color: #17633b; font-size: 12px; }
    .monitoring-flash--error { border-color: #f1c7c4; background: #fff5f4; color: #912018; }
    .monitoring-stale { margin-bottom: 14px; padding: 11px 13px; border: 1px solid #eadcae; border-radius: 10px; background: #fffaf0; color: #725a1b; font-size: 11px; }
    .monitoring-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .monitoring-stat, .monitoring-card { border: 1px solid var(--monitor-border); border-radius: 14px; background: var(--monitor-card); box-shadow: 0 3px 14px rgba(0,0,0,.04); }
    .monitoring-stat { padding: 16px; }
    .monitoring-stat-label { display: flex; align-items: center; gap: 7px; color: var(--monitor-muted); font-size: 10px; font-weight: 750; }
    .monitoring-stat-value { margin-top: 8px; color: var(--monitor-text); font-size: 27px; font-weight: 800; letter-spacing: -.04em; line-height: 1; }
    .monitoring-stat-note { margin-top: 6px; color: var(--monitor-muted); font-size: 9px; }
    .monitoring-layout { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(290px, .8fr); gap: 16px; align-items: start; }
    .monitoring-column { display: grid; min-width: 0; gap: 16px; }
    .monitoring-card { overflow: hidden; }
    .monitoring-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 17px 19px; border-bottom: 1px solid var(--monitor-border); }
    .monitoring-card-head h2 { margin: 0; color: var(--monitor-text); font-size: 14px; font-weight: 800; }
    .monitoring-card-head p { margin: 4px 0 0; color: var(--monitor-muted); font-size: 10px; }
    .monitoring-card-body { padding: 17px 19px; }
    .monitoring-freshness { color: var(--monitor-muted); font-size: 10px; text-align: right; white-space: nowrap; }
    .monitoring-category + .monitoring-category { margin-top: 19px; }
    .monitoring-category-title { margin: 0 0 9px; color: #64748b; font-size: 9px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .monitoring-module-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
    .monitoring-module { min-width: 0; padding: 12px; border: 1px solid var(--monitor-border); border-radius: 11px; background: var(--monitor-card); }
    .monitoring-module-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .monitoring-module-name { color: var(--monitor-text); font-size: 11px; font-weight: 750; line-height: 1.35; }
    .monitoring-badge { flex: 0 0 auto; padding: 4px 7px; border-radius: 999px; font-size: 8px; font-weight: 800; text-transform: uppercase; }
    .monitoring-badge--healthy { background: #e8f7ef; color: #167447; }
    .monitoring-badge--warning { background: #fff6df; color: #916700; }
    .monitoring-badge--critical { background: #fff0ee; color: #a52c25; }
    .monitoring-badge--unknown, .monitoring-badge--offline { background: #eef2f6; color: #596579; }
    .monitoring-module-meta { display: flex; flex-wrap: wrap; gap: 5px 10px; margin-top: 8px; color: var(--monitor-muted); font-size: 9px; }
    .monitoring-resource { margin-top: 10px; }
    .monitoring-resource-value { color: var(--monitor-text); font-size: 18px; font-weight: 800; letter-spacing: -.03em; }
    .monitoring-meter { height: 6px; margin-top: 7px; overflow: hidden; border-radius: 999px; background: #edf1f4; }
    .monitoring-meter span { display: block; height: 100%; border-radius: inherit; background: #38a169; }
    .monitoring-module:has(.monitoring-badge--warning) .monitoring-meter span { background: #d6a11d; }
    .monitoring-module:has(.monitoring-badge--critical) .monitoring-meter span { background: #d94d43; }
    .monitoring-resource-details { display: flex; flex-wrap: wrap; gap: 4px 10px; margin-top: 6px; color: var(--monitor-muted); font-size: 9px; }
    .monitoring-process-count { margin-top: 9px; color: var(--monitor-muted); font-size: 9px; }
    .monitoring-process-count strong { color: var(--monitor-text); font-size: 12px; }
    .monitoring-uptime { margin-top: 9px; color: var(--monitor-text); font-size: 16px; font-weight: 800; }
    .monitoring-cause { margin-top: 8px; padding: 8px 9px; border-radius: 8px; background: #fff8e8; color: #755914; font-size: 9px; line-height: 1.45; overflow-wrap: anywhere; }
    .monitoring-empty { padding: 28px 16px; color: var(--monitor-muted); font-size: 11px; text-align: center; }
    .monitoring-health-state { display: inline-flex; align-items: center; gap: 6px; padding: 5px 8px; border-radius: 999px; background: #e8f7ef; color: #167447; font-size: 9px; font-weight: 800; }
    .monitoring-health-state.is-warning { background: #fff6df; color: #916700; }
    .monitoring-health-state.is-critical { background: #fff0ee; color: #a52c25; }
    .monitoring-health-state.is-unknown { background: #eef2f6; color: #596579; }
    .monitoring-alert-form { display: grid; gap: 11px; }
    .monitoring-toggle { display: flex; align-items: flex-start; gap: 9px; padding: 11px; border: 1px solid var(--monitor-border); border-radius: 10px; }
    .monitoring-toggle input { margin-top: 2px; }
    .monitoring-toggle strong { display: block; color: var(--monitor-text); font-size: 10px; }
    .monitoring-toggle span { display: block; margin-top: 3px; color: var(--monitor-muted); font-size: 9px; line-height: 1.45; }
    .monitoring-label { color: var(--monitor-text); font-size: 10px; font-weight: 750; }
    .monitoring-input { width: 100%; min-height: 86px; margin-top: 5px; padding: 10px; border: 1px solid var(--monitor-border); border-radius: 9px; background: var(--monitor-card); color: var(--monitor-text); font-size: 10px; }
    .monitoring-input:focus { border-color: var(--monitor-accent-dark); outline: 3px solid rgba(245,197,24,.22); }
    .monitoring-button:disabled { cursor: not-allowed; opacity: .55; }
    .monitoring-help { color: var(--monitor-muted); font-size: 9px; line-height: 1.45; }
    .monitoring-mail-warning { padding: 10px; border: 1px solid #eadcae; border-radius: 9px; background: #fffaf0; color: #725a1b; font-size: 9px; line-height: 1.5; }
    .monitoring-divider { height: 1px; margin: 15px 0; background: var(--monitor-border); }
    .monitoring-side-stat { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--monitor-border); color: var(--monitor-muted); font-size: 10px; }
    .monitoring-side-stat strong { color: var(--monitor-text); font-size: 13px; }
    .monitoring-side-stat:last-child { border-bottom: 0; }
    @media (max-width: 1050px) { .monitoring-layout { grid-template-columns: minmax(0, 1fr); } .monitoring-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 680px) { .monitoring-header { align-items: flex-start; flex-direction: column; } .monitoring-actions { width: 100%; } .monitoring-actions > * { flex: 1; } .monitoring-summary { gap: 8px; } .monitoring-stat { padding: 13px; } .monitoring-stat-value { font-size: 23px; } .monitoring-module-grid { grid-template-columns: minmax(0, 1fr); } .monitoring-card-head { flex-direction: column; } .monitoring-freshness { text-align: left; } }
</style>

<main class="monitoring-page">
    <div class="monitoring-shell">
        <header class="monitoring-header">
            <div>
                <div class="monitoring-kicker"><i class="ri-pulse-line"></i> IT Admin · Operations</div>
                <h1>System monitoring</h1>
                <p>Linux VM resource and service checks run every minute. Email alerts are sent when monitored status changes.</p>
            </div>
            <div class="monitoring-actions">
                <a href="{{ route('staff.it.incidents') }}" class="monitoring-button"><i class="ri-alarm-warning-line"></i> Alerts &amp; incidents</a>
                <a href="{{ route('staff.it.monitoring') }}" class="monitoring-button monitoring-button--primary"><i class="ri-refresh-line"></i> Refresh</a>
            </div>
        </header>

        @if(session('success'))<div class="monitoring-flash" role="status">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="monitoring-flash monitoring-flash--error" role="alert">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="monitoring-flash monitoring-flash--error" role="alert">{{ $errors->first() }}</div>@endif
        @if($monitoringStale)<div class="monitoring-stale" role="status"><i class="ri-time-line"></i> Health data is older than two minutes or has not run yet. Confirm the Laravel scheduler is active on the server.</div>@endif

        @php
            $overallState = $criticalCount > 0 ? 'critical' : ($warningCount > 0 ? 'warning' : ($reportedModules->isEmpty() ? 'unknown' : 'healthy'));
            $overallLabel = ['healthy' => 'All clear', 'warning' => 'Needs attention', 'critical' => 'Critical issues', 'unknown' => 'Awaiting checks'][$overallState];
            $categoryLabels = [
                'portal' => 'Portal services',
                'core' => 'Core services',
                'production' => 'Production services',
                'payment' => 'Payment & financial services',
                'vps' => 'VPS resources',
                'infrastructure' => 'VPS services & infrastructure',
            ];
            $statusClass = ['healthy' => 'healthy', 'warning' => 'warning', 'critical' => 'critical', 'offline' => 'offline', 'unknown' => 'unknown'];
            $groupedModules = $modules->groupBy('category');
        @endphp

        <section class="monitoring-summary" aria-label="System health summary">
            <article class="monitoring-stat">
                <div class="monitoring-stat-label"><i class="ri-heart-pulse-line"></i> Overall health</div>
                <div class="monitoring-stat-value">{{ $reportedModules->isEmpty() ? '—' : $healthScore . '%' }}</div>
                <div class="monitoring-stat-note">{{ $overallLabel }}</div>
            </article>
            <article class="monitoring-stat">
                <div class="monitoring-stat-label"><i class="ri-checkbox-circle-line"></i> Healthy</div>
                <div class="monitoring-stat-value" style="color:#167447;">{{ number_format($healthyCount) }}</div>
                <div class="monitoring-stat-note">Services passing checks</div>
            </article>
            <article class="monitoring-stat">
                <div class="monitoring-stat-label"><i class="ri-error-warning-line"></i> Warnings</div>
                <div class="monitoring-stat-value" style="color:#916700;">{{ number_format($warningCount) }}</div>
                <div class="monitoring-stat-note">Services needing attention</div>
            </article>
            <article class="monitoring-stat">
                <div class="monitoring-stat-label"><i class="ri-alarm-warning-line"></i> Critical</div>
                <div class="monitoring-stat-value" style="color:#a52c25;">{{ number_format($criticalCount) }}</div>
                <div class="monitoring-stat-note">Open incidents: {{ number_format($openIncidentCount) }}</div>
            </article>
        </section>

        <section class="monitoring-layout">
            <div class="monitoring-column">
                <section class="monitoring-card">
                    <header class="monitoring-card-head">
                        <div><h2>Service health</h2><p>Latest result from each configured health probe</p></div>
                        <div class="monitoring-freshness">Last check<br><strong>{{ $lastCheckAt ? \Carbon\Carbon::parse($lastCheckAt)->timezone(config('app.timezone'))->format('d M Y · H:i T') : 'No checks recorded' }}</strong></div>
                    </header>
                    <div class="monitoring-card-body">
                        @if($modules->isEmpty())
                            <div class="monitoring-empty">No health checks have run yet. The scheduled monitor will populate this page on its next run.</div>
                        @else
                            @foreach($categoryLabels as $category => $label)
                                @if($groupedModules->has($category))
                                    <section class="monitoring-category">
                                        <h3 class="monitoring-category-title">{{ $label }}</h3>
                                        <div class="monitoring-module-grid">
                                            @foreach($groupedModules[$category] as $module)
                                                @php($moduleMeta = is_array($module->meta) ? $module->meta : [])
                                                <article class="monitoring-module">
                                                    <div class="monitoring-module-top">
                                                        <div class="monitoring-module-name">{{ $module->display_name }}</div>
                                                        <span class="monitoring-badge monitoring-badge--{{ $statusClass[$module->status] ?? 'unknown' }}">{{ $module->last_checked_at ? ucfirst($module->status) : 'Awaiting check' }}</span>
                                                    </div>
                                                    <div class="monitoring-module-meta">
                                                        <span><i class="ri-time-line"></i> {{ $module->last_checked_at?->diffForHumans() ?? 'Never checked' }}</span>
                                                        @if(isset($moduleMeta['response_time_ms']))<span><i class="ri-speed-line"></i> {{ number_format((int) $moduleMeta['response_time_ms']) }} ms</span>@endif
                                                    </div>
                                                    @if($module->module_name === 'database' && isset($moduleMeta['database_size_bytes']))
                                                        <div class="monitoring-resource">
                                                            <div class="monitoring-resource-value">{{ number_format($moduleMeta['database_size_bytes'] / 1024 ** 2, 1) }} MB</div>
                                                            <div class="monitoring-resource-details"><span>Database data + indexes</span></div>
                                                            @if(isset($moduleMeta['database_filesystem_free_bytes'], $moduleMeta['database_filesystem_total_bytes']))
                                                                <div class="monitoring-resource-details">
                                                                    <span>MySQL data filesystem free: {{ number_format($moduleMeta['database_filesystem_free_bytes'] / 1024 ** 3, 1) }} GB of {{ number_format($moduleMeta['database_filesystem_total_bytes'] / 1024 ** 3, 1) }} GB</span>
                                                                </div>
                                                                <div class="monitoring-meter" role="meter" aria-label="MySQL data filesystem used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100, max(0, (float) ($moduleMeta['database_filesystem_used_percent'] ?? 0))) }}"><span style="width:{{ min(100, max(0, (float) ($moduleMeta['database_filesystem_used_percent'] ?? 0))) }}%"></span></div>
                                                            @else
                                                                <div class="monitoring-resource-details"><span>MySQL data filesystem free space unavailable from this application host</span></div>
                                                            @endif
                                                            <div class="monitoring-resource-details"><span>Database size is not a configured storage quota</span></div>
                                                        </div>
                                                    @elseif(isset($moduleMeta['usage_percent']))
                                                        <div class="monitoring-resource">
                                                            <div class="monitoring-resource-value">{{ $moduleMeta['display_value'] ?? number_format((float) $moduleMeta['usage_percent'], 1) . '%' }}</div>
                                                            <div class="monitoring-meter" role="meter" aria-label="{{ $module->display_name }} utilization" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100, max(0, (float) $moduleMeta['usage_percent'])) }}"><span style="width:{{ min(100, max(0, (float) $moduleMeta['usage_percent'])) }}%"></span></div>
                                                            <div class="monitoring-resource-details">
                                                                @if(isset($moduleMeta['used_mb'], $moduleMeta['total_mb']))<span>{{ number_format($moduleMeta['used_mb']) }} / {{ number_format($moduleMeta['total_mb']) }} MB used</span>@endif
                                                                @if(isset($moduleMeta['free_gb'], $moduleMeta['total_gb']))<span>{{ number_format($moduleMeta['free_gb'], 1) }} / {{ number_format($moduleMeta['total_gb'], 1) }} GB free</span>@endif
                                                                @if(isset($moduleMeta['load_1m']))<span>Load {{ number_format($moduleMeta['load_1m'], 2) }} · 1m</span>@endif
                                                                @if(isset($moduleMeta['cpu_cores']))<span>{{ number_format($moduleMeta['cpu_cores']) }} cores</span>@endif
                                                            </div>
                                                        </div>
                                                    @elseif(isset($moduleMeta['display_value']))
                                                        <div class="monitoring-uptime">{{ $moduleMeta['display_value'] }}</div>
                                                        <div class="monitoring-resource-details">
                                                            @if(isset($moduleMeta['load_1m']))<span>Load {{ number_format($moduleMeta['load_1m'], 2) }} · 1m</span>@endif
                                                            @if(isset($moduleMeta['load_5m']))<span>{{ number_format($moduleMeta['load_5m'], 2) }} · 5m</span>@endif
                                                            @if(isset($moduleMeta['load_15m']))<span>{{ number_format($moduleMeta['load_15m'], 2) }} · 15m</span>@endif
                                                        </div>
                                                    @elseif(isset($moduleMeta['process_count']))
                                                        <div class="monitoring-process-count">Running processes: <strong>{{ number_format($moduleMeta['process_count']) }}</strong></div>
                                                    @endif
                                                    @if($module->probable_cause && $module->status !== 'healthy')
                                                        <div class="monitoring-cause">{{ $module->probable_cause }}</div>
                                                    @endif
                                                </article>
                                            @endforeach
                                        </div>
                                    </section>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </section>
            </div>

            <aside class="monitoring-column" aria-label="Alert delivery and incident summary">
                <section class="monitoring-card">
                    <header class="monitoring-card-head">
                        <div><h2>Email alert routing</h2><p>Notify IT when a monitored service fails or recovers</p></div>
                        <span class="monitoring-health-state {{ $alertDeliveryReady ? '' : 'is-warning' }}">{{ !$alertsEnabled ? 'Paused' : (!$mailCanDeliver ? 'Mailer unavailable' : (empty($alertEmails) ? 'No recipients' : 'Ready')) }}</span>
                    </header>
                    <div class="monitoring-card-body">
                        @if(!$mailCanDeliver)
                            <div class="monitoring-mail-warning">The current mailer is <strong>{{ $mailDriver }}</strong> and cannot deliver external email. Configure a delivery mailer on the server before relying on these alerts.</div>
                        @endif
                        @if($alertsEnabled && $mailCanDeliver && empty($alertEmails))
                            <div class="monitoring-mail-warning mb-3">No alert recipients are available yet. Add email addresses below or create active IT Admin/Super Admin accounts.</div>
                        @endif
                        <form class="monitoring-alert-form" action="{{ route('staff.it.monitoring.alerts.save') }}" method="POST">
                            @csrf
                            <input type="hidden" name="email_enabled" value="0">
                            <label class="monitoring-toggle">
                                <input type="checkbox" name="email_enabled" value="1" {{ $alertsEnabled ? 'checked' : '' }}>
                                <span><strong>Send system alert emails</strong><span>Notifies on new warning/critical incidents, severity escalation, and recovery.</span></span>
                            </label>
                            <label class="monitoring-label" for="system-alert-emails">Recipient email addresses</label>
                            <textarea id="system-alert-emails" class="monitoring-input" name="emails" placeholder="it@example.org, admin@example.org">{{ old('emails', implode(', ', $alertEmails)) }}</textarea>
                            <div class="monitoring-help">Separate addresses with commas or new lines. Leave blank to use the configured alert addresses or active IT Admin and Super Admin accounts.</div>
                            <button type="submit" class="monitoring-button monitoring-button--primary"><i class="ri-save-line"></i> Save alert settings</button>
                        </form>
                        <div class="monitoring-divider"></div>
                        <form action="{{ route('staff.it.monitoring.alerts.test') }}" method="POST">
                            @csrf
                            <button type="submit" class="monitoring-button" {{ !$alertDeliveryReady ? 'disabled' : '' }}><i class="ri-mail-send-line"></i> Send test email</button>
                        </form>
                    </div>
                </section>

                <section class="monitoring-card">
                    <header class="monitoring-card-head">
                        <div><h2>Monitoring cadence</h2><p>Current health-check schedule</p></div>
                    </header>
                    <div class="monitoring-card-body">
                        <div class="monitoring-side-stat"><span>Health checks</span><strong>Every minute</strong></div>
                        <div class="monitoring-side-stat"><span>Open incidents</span><strong>{{ number_format($openIncidentCount) }}</strong></div>
                        <div class="monitoring-side-stat"><span>Email transport</span><strong>{{ strtoupper($mailDriver) }}</strong></div>
                        <div class="monitoring-divider"></div>
                        <a href="{{ route('staff.it.incidents') }}" class="monitoring-button"><i class="ri-arrow-right-line"></i> Review alerts &amp; incidents</a>
                    </div>
                </section>
            </aside>
        </section>
    </div>
</main>
@endsection
