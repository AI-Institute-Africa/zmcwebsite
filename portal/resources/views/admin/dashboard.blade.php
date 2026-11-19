@extends('layouts.portal')

@section('title', 'Executive Dashboard')

@section('content')
@php
    $stats = $stats ?? [];
    $stages = $applicationsByStage ?? [];
    $alerts = $alerts ?? [];
    $trend = $trendData['datasets'] ?? [];
    $trendLabels = $trendData['labels'] ?? [];
    $pipeline = (int)($stats['pending_applications'] ?? 0);
    $total = max(1, (int)($stats['total_applications'] ?? 0));
    $stageTotal = max(1, array_sum($stages));
    $attention = (int)($alerts['slaBreaches'] ?? 0) + (int)($alerts['stuckApplications'] ?? 0) + (int)($alerts['failedPayments'] ?? 0);
    $exec = $executiveMetrics ?? [];
    $decisionQueue = $executiveAttention ?? collect();
    $exec = $executiveMetrics ?? [];
    $decisionQueue = $executiveAttention ?? collect();
@endphp

<style>
    .exec-shell{--ink:var(--zmc-text-dark);--muted:var(--zmc-gray-dark);--green:var(--zmc-primary);--gold:var(--zmc-yellow);font-family:var(--font-primary);background:var(--zmc-gray-bg);margin:-1.5rem;padding:2rem;min-height:calc(100vh - 5rem)}
    .exec-hero{background:linear-gradient(120deg,var(--zmc-primary-darker),var(--zmc-primary));border-radius:22px;color:#fff;padding:2rem 2.25rem;display:flex;justify-content:space-between;gap:1rem;align-items:end;box-shadow:var(--shadow);border-bottom:3px solid var(--zmc-yellow)}
    .exec-hero h1{font-size:clamp(1.65rem,3vw,2.35rem);font-weight:800;margin:0}.exec-hero p{color:#d8e5e7;margin:.5rem 0 0}.exec-chip{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);padding:.55rem .8rem;border-radius:999px;font-size:.8rem;white-space:nowrap}
    .exec-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;margin:1.25rem 0}.exec-card{background:var(--zmc-white);border:1px solid var(--zmc-gray);border-radius:16px;padding:1.15rem;box-shadow:0 5px 16px var(--zmc-dark-08)}.exec-card .label{color:var(--muted);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}.exec-card .value{font-size:1.85rem;font-weight:800;color:var(--ink);margin:.35rem 0}.exec-card .hint{color:var(--muted);font-size:.8rem}.exec-layout{display:grid;grid-template-columns:1.45fr 1fr;gap:1rem}.exec-panel{background:var(--zmc-white);border:1px solid var(--zmc-gray);border-radius:16px;padding:1.25rem;box-shadow:0 5px 16px var(--zmc-dark-08)}.exec-panel h2{font-size:1.05rem;color:var(--ink);font-weight:800;margin:0}.exec-panel .sub{font-size:.8rem;color:var(--muted);margin:.25rem 0 1.1rem}.stage-row{margin:.95rem 0}.stage-row .top{display:flex;justify-content:space-between;font-size:.85rem;color:var(--ink);font-weight:700}.stage-track{height:9px;background:var(--zmc-gray);border-radius:10px;overflow:hidden;margin-top:.4rem}.stage-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,var(--zmc-primary),var(--zmc-yellow))}.attention{display:flex;justify-content:space-between;align-items:center;padding:.8rem;border-radius:12px;background:var(--zmc-yellow-15);margin:.6rem 0}.attention strong{color:var(--zmc-text-dark)}.attention span{font-weight:800;color:var(--zmc-text-dark)}.exec-table{width:100%;font-size:.82rem}.exec-table td{padding:.65rem .2rem;border-bottom:1px solid var(--zmc-gray)}.exec-table td:last-child{text-align:right}.status{display:inline-flex;border-radius:999px;padding:.28rem .55rem;font-size:.7rem;font-weight:700;background:rgba(16,185,129,.12);color:var(--zmc-primary)}.exec-links{display:flex;gap:.65rem;flex-wrap:wrap;margin-top:1rem}.exec-links a{border:1px solid var(--zmc-gray);border-radius:10px;color:var(--ink);padding:.55rem .75rem;font-size:.8rem;text-decoration:none;background:var(--zmc-white)}.exec-links a:hover{border-color:var(--zmc-yellow);color:var(--zmc-text-dark)}.trend-bars{height:150px;display:flex;gap:5px;align-items:end;border-bottom:1px solid var(--zmc-gray);padding-top:1rem}.trend-bars i{flex:1;min-width:3px;border-radius:4px 4px 0 0;background:var(--zmc-primary);opacity:.85}.trend-legend{display:flex;justify-content:space-between;color:var(--muted);font-size:.7rem;margin-top:.5rem}
    @media(max-width:900px){.exec-grid{grid-template-columns:repeat(2,1fr)}.exec-layout{grid-template-columns:1fr}.exec-hero{align-items:start;flex-direction:column}}@media(max-width:520px){.exec-shell{margin:-1rem;padding:1rem}.exec-grid{grid-template-columns:1fr 1fr}.exec-card .value{font-size:1.45rem}}
</style>

<div class="exec-shell">
    <div class="exec-hero">
        <div><div class="exec-chip">Executive oversight · {{ $year ?? now()->year }}</div><h1>Accreditation &amp; Registration Executive Dashboard</h1><p>A concise view of demand, pipeline health and decisions requiring leadership attention.</p></div>
        <div class="text-end"><div class="small opacity-75">Last refreshed</div><strong>{{ now()->format('d M Y, H:i') }}</strong></div>
    </div>

    <div class="exec-grid">
        <div class="exec-card"><div class="label">Total registered media houses</div><div class="value">{{ number_format($exec['registeredMediaHouses'] ?? 0) }}</div><div class="hint">Active registered entities</div></div>
        <div class="exec-card"><div class="label">Total media practitioners</div><div class="value">{{ number_format($exec['activeAccreditations'] ?? 0) }}</div><div class="hint">Active accreditations</div></div>
        <div class="exec-card"><div class="label">Total revenue received</div><div class="value">$ {{ number_format($exec['totalRevenue'] ?? 0, 2) }}</div><div class="hint">Confirmed payments · {{ $year ?? now()->year }}</div></div>
        <div class="exec-card"><div class="label">Decisions requiring attention</div><div class="value">{{ number_format($exec['decisionCount'] ?? 0) }}</div><div class="hint">Service-time and workflow risks</div></div>
    </div>

    <div class="exec-layout">
        <section class="exec-panel"><h2>Where applications are now</h2><div class="sub">The current workload across the accreditation and registration journey.</div>
            @foreach(['Officer','Accounts','Registrar','Production'] as $stage)
                @php $count=(int)($stages[$stage] ?? 0); $width=min(100, round(($count/$stageTotal)*100)); @endphp
                <div class="stage-row"><div class="top"><span>{{ $stage }}</span><span>{{ number_format($count) }} <small class="text-muted">({{ $width }}%)</small></span></div><div class="stage-track"><div class="stage-fill" style="width:{{ max($count ? 4 : 0,$width) }}%"></div></div></div>
            @endforeach
            <div class="exec-links"><a href="{{ route('admin.accreditation.index') }}">Review accreditation</a><a href="{{ route('admin.mediahouse.index') }}">Review registrations</a></div>
        </section>
        <section class="exec-panel"><h2>Leadership attention</h2><div class="sub">Signals that may affect turnaround or service delivery.</div>
            <div class="attention"><strong>SLA breaches</strong><span>{{ number_format($alerts['slaBreaches'] ?? 0) }}</span></div><div class="attention"><strong>Stuck over 7 days</strong><span>{{ number_format($alerts['stuckApplications'] ?? 0) }}</span></div><div class="attention"><strong>Failed payments</strong><span>{{ number_format($alerts['failedPayments'] ?? 0) }}</span></div>
            @if(!$attention)<div class="alert alert-success mb-0"><i class="ri-checkbox-circle-line me-1"></i> No immediate pipeline alerts.</div>@endif
        </section>
    </div>

    <div class="exec-layout mt-3">
        <section class="exec-panel"><h2>Application demand</h2><div class="sub">Recent volume for accreditation and registration.</div>
            @php $acc=$trend['accreditation'] ?? []; $reg=$trend['registration'] ?? []; $max=max(1, ...array_merge($acc,$reg)); $bars=array_slice($trendLabels, -30); @endphp
            <div class="trend-bars">@foreach(array_slice($acc,-30) as $i=>$v)<i title="{{ $bars[$i] ?? '' }}" style="height:{{ max($v?4:1, round(($v/$max)*100)) }}%;background:var(--zmc-primary)"></i>@endforeach</div><div class="trend-legend"><span>Accreditation</span><span>Last 30 days</span></div>
        </section>
        <section class="exec-panel"><h2>Accreditation &amp; registration mix</h2><div class="sub">Demand balance and active service coverage for executive planning.</div>@php $accCount=(int)($stats['journalist_accreditations'] ?? 0); $regCount=(int)($stats['mediahouse_registrations'] ?? 0); $mixTotal=max(1,$accCount+$regCount); $accPct=round(($accCount/$mixTotal)*100); $regPct=100-$accPct; @endphp<div class="stage-row"><div class="top"><span>Media practitioners</span><span>{{ number_format($accCount) }} <small class="text-muted">({{ $accPct }}%)</small></span></div><div class="stage-track"><div class="stage-fill" style="width:{{ max($accCount ? 4 : 0,$accPct) }}%"></div></div></div><div class="stage-row"><div class="top"><span>Media houses</span><span>{{ number_format($regCount) }} <small class="text-muted">({{ $regPct }}%)</small></span></div><div class="stage-track"><div class="stage-fill" style="width:{{ max($regCount ? 4 : 0,$regPct) }}%"></div></div></div><table class="exec-table mt-3"><tbody><tr><td>Active practitioners</td><td><strong>{{ number_format($exec['activeAccreditations'] ?? 0) }}</strong></td></tr><tr><td>Active media houses</td><td><strong>{{ number_format($exec['registeredMediaHouses'] ?? 0) }}</strong></td></tr><tr><td>Renewals within 90 days</td><td><strong>{{ number_format(($exec['accreditationsExpiring'] ?? 0) + ($exec['registrationsExpiring'] ?? 0)) }}</strong></td></tr></tbody></table></section>
    </div>
</div>
@endsection
