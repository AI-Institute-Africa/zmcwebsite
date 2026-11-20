@extends('layouts.portal')

@section('title', 'Media House Portal Dashboard')

@section('content')
@php
  use Illuminate\Support\Facades\Route;
  use Illuminate\Support\Str;
  use App\Models\Application;

  $detailsUrlTemplate = Route::has('portal.applications.details')
    ? route('portal.applications.details', ['application' => '__ID__'])
    : url('/portal/applications/__ID__/details');

  $apps = $recentApplications ?? [];
@endphp

<style>
  .mh-dashboard{--mh-ink:#0B0F17;--mh-muted:#6B7A90;--mh-line:#E9EDF3;--mh-gold:#D4A017;--mh-gold-lt:#F2C94C;--mh-radius:20px;color:#334155;font-family:'Roboto',system-ui,-apple-system,'Segoe UI',sans-serif;padding-bottom:8px}
  .mh-dashboard *{box-sizing:border-box}
  .mh-dashboard .zd-hero{position:relative;overflow:hidden;border-radius:26px;padding:34px 34px 30px;color:#fff;margin-bottom:22px;background:radial-gradient(120% 180% at 100% 0%,#26313F 0%,#0B0F17 58%),#0B0F17;box-shadow:0 30px 70px -38px rgba(11,15,23,.85)}
  .mh-dashboard .zd-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(52% 90% at 88% 6%,rgba(212,160,23,.42),transparent 62%),radial-gradient(45% 85% at 4% 105%,rgba(56,189,248,.14),transparent 68%);pointer-events:none}
  .mh-dashboard .zd-hero:after{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:52px 52px;mask-image:radial-gradient(80% 90% at 20% 0%,#000 0%,transparent 75%);pointer-events:none}
  .mh-dashboard .zd-hero-inner{position:relative;z-index:2}.mh-dashboard .zd-eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;padding:6px 12px;border-radius:999px;color:#F6DC93;background:rgba(212,160,23,.14);border:1px solid rgba(212,160,23,.32)}
  .mh-dashboard .zd-hero-title{font-size:clamp(22px,2.4vw,30px);font-weight:900;letter-spacing:-.02em;margin:14px 0 6px;line-height:1.15}.mh-dashboard .zd-hero-sub{margin:0;font-size:14px;line-height:1.6;color:rgba(255,255,255,.68);max-width:62ch}.mh-dashboard .zd-hero-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}
  .mh-dashboard .zd-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 17px;border-radius:13px;font-size:13px;font-weight:700;line-height:1;text-decoration:none;border:1px solid transparent;cursor:pointer;white-space:nowrap;transition:.18s}.mh-dashboard .zd-btn-ghost{background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.16);color:#fff;backdrop-filter:blur(10px)}.mh-dashboard .zd-btn-ghost:hover{background:rgba(255,255,255,.15);color:#fff}.mh-dashboard .zd-btn-gold{background:linear-gradient(135deg,var(--mh-gold-lt),var(--mh-gold));color:#171203;box-shadow:0 14px 30px -14px rgba(212,160,23,.95)}.mh-dashboard .zd-btn-gold:hover{color:#000;filter:brightness(1.05)}
  .mh-dashboard .zd-card,.mh-dashboard .zd-stat{background:#fff;border:1px solid var(--mh-line);border-radius:var(--mh-radius);box-shadow:0 1px 2px rgba(16,24,40,.04),0 20px 46px -36px rgba(16,24,40,.45)}.mh-dashboard .zd-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 22px;border-bottom:1px solid var(--mh-line)}.mh-dashboard .zd-card-title{display:flex;align-items:center;gap:10px;margin:0;font-size:14.5px;font-weight:800;color:var(--mh-ink)}.mh-dashboard .zd-card-title i{color:var(--mh-gold);font-size:17px}.mh-dashboard .zd-link{font-size:12px;font-weight:700;color:var(--mh-muted);text-decoration:none;padding:6px 11px;border-radius:9px;border:1px solid var(--mh-line);background:#fff}.mh-dashboard .zd-list{padding:8px 10px 12px}.mh-dashboard .zd-list-item{display:flex;gap:12px;padding:12px;border-radius:13px}.mh-dashboard .zd-list-dot{flex:0 0 8px;width:8px;height:8px;border-radius:50%;margin-top:7px;background:linear-gradient(135deg,var(--mh-gold-lt),var(--mh-gold));box-shadow:0 0 0 3px rgba(212,160,23,.14)}.mh-dashboard .zd-list-title{font-size:13.5px;font-weight:700;color:var(--mh-ink);line-height:1.4}.mh-dashboard .zd-list-meta{font-size:12px;color:var(--mh-muted);line-height:1.5;margin-top:2px}.mh-dashboard .zd-date-chip{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:52px;padding:7px 9px;border-radius:11px;background:#F8FAFC;border:1px solid var(--mh-line);line-height:1.1}.mh-dashboard .zd-date-chip .d{font-size:15px;font-weight:900;color:var(--mh-ink)}.mh-dashboard .zd-date-chip .m{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--mh-muted)}
  .mh-dashboard .zd-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:22px}.mh-dashboard .zd-stat{position:relative;overflow:hidden;padding:20px 20px 18px;transition:.25s}.mh-dashboard .zd-stat:after{content:"";position:absolute;right:-30px;top:-30px;width:110px;height:110px;border-radius:50%;background:radial-gradient(circle,rgba(212,160,23,.09),transparent 70%)}.mh-dashboard .zd-stat-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.mh-dashboard .zd-stat-label{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--mh-muted)}.mh-dashboard .zd-stat-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px}.mh-dashboard .is-slate{background:#F1F5F9;color:#475569}.mh-dashboard .is-info{background:#E8F1FE;color:#1D4ED8}.mh-dashboard .is-ok{background:#E7F7EF;color:#047857}.mh-dashboard .is-gold{background:#FDF7E7;color:#9A7208}.mh-dashboard .zd-stat-value{font-size:34px;font-weight:900;letter-spacing:-.03em;color:var(--mh-ink);line-height:1;margin-top:16px}.mh-dashboard .zd-stat-foot{font-size:11.5px;color:var(--mh-muted);margin-top:8px}
  .mh-dashboard .zd-grid-2{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:22px}.mh-dashboard .zd-empty{padding:38px 24px;text-align:center;color:var(--mh-muted)}.mh-dashboard .zd-empty-icon{width:58px;height:58px;margin:0 auto 12px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:#F4F6FA;color:#94A3B8;font-size:27px}.mh-dashboard .zd-empty h6{margin:0 0 5px;font-size:14px;font-weight:800;color:var(--mh-ink)}
  .mh-dashboard .zd-grid-2 > [class*="col-"]{width:auto;max-width:none;padding-left:0;padding-right:0}
  .mh-dashboard .zd-alert{position:relative;overflow:hidden;border:1px solid #F7E3B4;background:linear-gradient(180deg,#FFFCF3 0%,#fff 60%);border-radius:var(--mh-radius);box-shadow:0 1px 2px rgba(16,24,40,.04),0 20px 46px -38px rgba(180,120,10,.55);margin-bottom:22px}.mh-dashboard .zd-alert:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:linear-gradient(180deg,#F2C94C,#C9901A)}.mh-dashboard .zd-alert-icon{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#FDEEC4,#F7DA92);color:#8A6205;font-size:22px}.mh-dashboard .zd-alert-title{font-size:16px;font-weight:900;color:var(--mh-ink)}.mh-dashboard .zd-alert-sub{margin:4px 0 0;font-size:13px;color:var(--mh-muted);line-height:1.6}.mh-dashboard .zd-table-wrap{overflow-x:auto}.mh-dashboard .zd-table{width:100%;border-collapse:separate;border-spacing:0;margin:0}.mh-dashboard .zd-table thead th{font-size:10.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--mh-muted);background:#FAFBFD;padding:12px 18px;border-bottom:1px solid var(--mh-line);white-space:nowrap}.mh-dashboard .zd-table tbody td{padding:14px 18px;border-bottom:1px solid #F1F4F8;font-size:13.5px;vertical-align:middle;color:#3F4C60}.mh-dashboard .zd-table tbody tr:last-child td{border-bottom:0}.mh-dashboard .zd-ref{display:inline-flex;align-items:center;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;font-weight:700;color:var(--mh-ink);background:#F4F6FA;border:1px solid #E6EAF1;padding:4px 9px;border-radius:8px}.mh-dashboard .zd-pill{display:inline-flex;align-items:center;gap:7px;padding:5px 12px 5px 10px;border-radius:999px;font-size:11.5px;font-weight:800;white-space:nowrap}.mh-dashboard .zd-pill:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}.mh-dashboard .zd-pill-info{background:#EAF2FE;color:#1D4ED8}.mh-dashboard .zd-pill-warn{background:#FFF6E6;color:#B45309}.mh-dashboard .zd-pill-ok{background:#E7F7EF;color:#047857}.mh-dashboard .zd-pill-slate{background:#F1F5F9;color:#475569}.mh-dashboard .zd-pill-danger{background:#FDEDEE;color:#B42318}
  .mh-dashboard .mh-registration-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-bottom:22px}.mh-dashboard .mh-registration-card{padding:22px 24px;border-radius:var(--mh-radius);color:#fff;position:relative;overflow:hidden;box-shadow:0 20px 46px -34px rgba(11,15,23,.6)}.mh-dashboard .mh-registration-card.status{background:linear-gradient(135deg,#0B0F17,#1b5e20)}.mh-dashboard .mh-registration-card.valid{background:linear-gradient(135deg,#1b5e20,#2e7d32)}.mh-dashboard .mh-registration-card .label{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.72)}.mh-dashboard .mh-registration-card .value{font-size:25px;font-weight:900;margin-top:10px}.mh-dashboard .mh-registration-card .meta{font-size:12px;color:rgba(255,255,255,.72);margin-top:7px}.mh-dashboard .mh-registration-card i.bg-icon{position:absolute;right:22px;top:22px;font-size:54px;color:rgba(255,255,255,.16)}
  @media(max-width:767px){.mh-dashboard .zd-hero{padding:26px 22px;border-radius:22px}.mh-dashboard .zd-hero-actions .zd-btn{flex:1 1 calc(50% - 10px);justify-content:center}.mh-dashboard .zd-stat-value{font-size:28px}}
</style>

<div class="zmc-dashboard-wrapper mh-dashboard">
  @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-warning" role="alert">{{ session('error') }}</div>@endif

  <div class="zd-hero">
    <div class="zd-hero-inner">
      <span class="zd-eyebrow"><i class="ri-building-2-line"></i> Mass Media Service Portal</span>
      <h1 class="zd-hero-title">Registration Dashboard</h1>
      <p class="zd-hero-sub">Track your media-house registrations, renewals, replacements and annual levies — complete the next step whenever action is required.</p>
      <div class="zd-hero-actions">
        <button type="button" class="zd-btn zd-btn-ghost" onclick="showRequirementsModal()"><i class="ri-file-list-3-line"></i> View Requirements</button>
        <a href="{{ route('mediahouse.renewals.index') }}" class="zd-btn zd-btn-ghost"><i class="ri-refresh-line"></i> Renew / Replace</a>
        <a href="{{ route('mediahouse.levy.index') }}" class="zd-btn zd-btn-ghost"><i class="ri-money-dollar-circle-line"></i> Annual Levy</a>
        <a href="{{ route('mediahouse.new') }}" class="zd-btn zd-btn-gold"><i class="ri-add-line"></i> New Registration</a>
      </div>
    </div>
  </div>

  @if($registration)
    <div class="mh-registration-summary">
      <div class="mh-registration-card status"><i class="ri-building-2-line bg-icon"></i><div class="label">Registration status</div><div class="value">@if($registration->status === 'active')<i class="ri-checkbox-circle-fill me-1" style="color:#F2C94C"></i>Active @else{{ ucwords(str_replace('_',' ',(string)$registration->status)) }}@endif</div><div class="meta">Registration no. {{ $registration->registration_no ?: 'Pending issuance' }}</div></div>
      <div class="mh-registration-card valid"><i class="ri-calendar-check-line bg-icon"></i><div class="label">Valid until</div><div class="value">{{ $registration->expires_at ? $registration->expires_at->format('d M Y') : 'Not issued' }}</div><div class="meta">@if($yearsRemaining !== null){{ $yearsRemaining <= 0 ? 'Expired' : number_format($yearsRemaining,1).' years remaining' }}@else Renewal date will appear after issuance @endif</div></div>
    </div>
  @endif

  <div class="zd-stats">
    <div class="zd-stat"><div class="zd-stat-top"><span class="zd-stat-label">Drafts</span><span class="zd-stat-icon is-slate"><i class="ri-draft-line"></i></span></div><div class="zd-stat-value">{{ $stats['drafts'] ?? 0 }}</div><div class="zd-stat-foot">Drafts expire after 14 days</div></div>
    <div class="zd-stat"><div class="zd-stat-top"><span class="zd-stat-label">Active</span><span class="zd-stat-icon is-info"><i class="ri-folders-line"></i></span></div><div class="zd-stat-value">{{ $stats['active'] ?? 0 }}</div><div class="zd-stat-foot">Registrations currently in progress</div></div>
    <div class="zd-stat"><div class="zd-stat-top"><span class="zd-stat-label">Approved</span><span class="zd-stat-icon is-ok"><i class="ri-verified-badge-line"></i></span></div><div class="zd-stat-value">{{ $stats['approved'] ?? 0 }}</div><div class="zd-stat-foot">Successfully registered</div></div>
    <div class="zd-stat"><div class="zd-stat-top"><span class="zd-stat-label">Pending review</span><span class="zd-stat-icon is-gold"><i class="ri-time-line"></i></span></div><div class="zd-stat-value">{{ $stats['pending'] ?? 0 }}</div><div class="zd-stat-foot">Awaiting officer or registrar action</div></div>
  </div>

  {{-- Application Fee Payment Alert --}}
  @php
    $pendingAppFee = $recentApplications->first(function($app) {
      $hasPending = $app->relationLoaded('paymentSubmissions') && $app->paymentSubmissions->contains(fn($payment) => in_array(strtolower((string) $payment->status), ['submitted', 'pending'], true));
      return $app->status === Application::SUBMITTED && !$hasPending;
    });
  @endphp

  @if($pendingAppFee)
    <div class="zd-alert">
      <div class="d-flex align-items-start gap-3">
        <div class="zd-alert-icon" style="background:#E7F7EF;color:#047857;">
          <i class="ri-information-line fs-3"></i>
        </div>
        <div class="flex-grow-1">
          <div class="zd-alert-title">Application Fee Payment Required</div>
          <p class="mb-2 text-muted small">
            Your application <strong>{{ $pendingAppFee->reference }}</strong> is submitted. Please pay the initial application fee to start the officer review process.
          </p>
          <button type="button" 
                  class="zd-btn zd-btn-gold js-pay-now" 
                  data-app-id="{{ $pendingAppFee->id }}"
                  data-app-ref="{{ $pendingAppFee->reference }}"
                  data-payment-stage="application_fee"
                  data-application-type="mediahouse"
                  style="background:#15803d;color:#fff;">
            <i class="ri-bank-card-line me-1"></i> Pay Application Fee
          </button>
        </div>
      </div>
    </div>
  @endif

  {{-- Registration Fee Payment Alert --}}
  @php
    $pendingRegFee = $recentApplications->first(function($app) {
      $hasPending = $app->relationLoaded('paymentSubmissions') && $app->paymentSubmissions->contains(fn($payment) => in_array(strtolower((string) $payment->status), ['submitted', 'pending'], true));
      return ($app->status === Application::REGISTRAR_APPROVED_PENDING_REG_FEE || $app->status === Application::AWAITING_REG_FEE) && !$hasPending;
    });
  @endphp

  @if($pendingRegFee)
    <div class="zd-alert">
      <div class="row g-3 align-items-center">
        <div class="col-auto">
          <div class="zd-alert-icon"><i class="ri-alert-line fs-3"></i></div>
        </div>
        <div class="col">
          <div class="zd-alert-title">Registration Fee Payment Required</div>
          <p class="mb-2 text-muted small">
            Your application <strong>{{ $pendingRegFee->reference }}</strong> has been approved by the Registrar. 
            Please download your official approval letter and proceed to pay the final registration fee.
          </p>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            @php
               $letter = $pendingRegFee->documents()->where('doc_type','official_approval_letter')->first();
            @endphp
            @if($letter)
              <a href="{{ route('mediahouse.download-official-letter', $pendingRegFee) }}" 
                 class="zd-btn zd-btn-ghost shadow-sm fw-bold" style="background:#0B0F17;color:#fff;">
                <i class="ri-download-line me-1"></i> Download Approval Letter
              </a>
            @endif
            
            <button type="button" 
                    class="zd-btn zd-btn-gold js-pay-now" 
                    data-app-id="{{ $pendingRegFee->id }}"
                    data-app-ref="{{ $pendingRegFee->reference }}"
                    data-payment-stage="registration_fee"
                    data-application-type="mediahouse"
                    data-media-category="{{ $pendingRegFee->media_house_category_code ?? data_get($pendingRegFee->form_data, 'media_house_category_code', 'default') }}"
                    style="background:#facc15;color:#000;">
              <i class="ri-bank-card-line me-1"></i> Pay Registration Fee
            </button>
          </div>
        </div>
        <div class="col-12 col-lg-auto text-lg-end"><span class="badge rounded-pill text-bg-light border px-3 py-2">Next step: payment</span></div>
      </div>
    </div>
  @endif

  <div class="zd-grid-2">
    <div class="col-12 col-lg-6">
      <div class="zd-card h-100">
        <div class="zd-card-head">
          <h2 class="zd-card-title"><i class="ri-megaphone-line"></i>Notices</h2>
          <a href="{{ route('mediahouse.notices') }}" class="zd-link">View all</a>
        </div>

        <div class="zd-list">
          @forelse(($notices ?? collect())->take(4) as $n)
            <div class="zd-list-item">
              <span class="zd-list-dot"></span>
              <div>
                <div class="zd-list-title">{{ $n->title }}</div>
                <div class="zd-list-meta">{{ Str::limit(strip_tags($n->body), 90) }}</div>
              </div>
            </div>
          @empty
            <div class="zd-empty"><div class="zd-empty-icon"><i class="ri-megaphone-line"></i></div><h3>No notices yet</h3><p>Announcements from ZMC will appear here.</p></div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="zd-card h-100">
        <div class="zd-card-head">
          <h4 class="zd-card-title"><i class="ri-calendar-event-line"></i>Events</h4>
          <a href="{{ route('mediahouse.notices') }}" class="zd-link">View all</a>
        </div>

        <div class="zd-list">
          @forelse(($events ?? collect())->take(4) as $e)
            <div class="zd-list-item">
              <div class="zd-date-chip"><span class="d">{{ optional($e->starts_at)->format('d') ?? '--' }}</span><span class="m">{{ optional($e->starts_at)->format('M') ?? 'TBA' }}</span></div>
              <div>
                <div class="zd-list-title">{{ $e->title }}</div>
                <div class="zd-list-meta">
                  {{ optional($e->starts_at)->format('d M Y') ?? 'TBA' }}
                  {{ $e->location ? (' • ' . $e->location) : '' }}
                </div>
              </div>
            </div>
          @empty
            <div class="zd-empty"><div class="zd-empty-icon"><i class="ri-calendar-event-line"></i></div><h5>No events yet</h5><p>Upcoming ZMC events will be listed here.</p></div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  <div class="zd-card p-0 mb-4">
    <div class="zd-card-head">
      <h6 class="zd-card-title"><i class="ri-history-line"></i>Recent Applications</h6>
      @if($apps->count() > 5)<span class="text-muted" style="font-size:11.5px;">Showing latest 5</span>@endif
    </div>

    <div class="zd-table-wrap">
      <table class="zd-table">
        <thead>
          <tr>
            <th><i class="ri-hashtag me-1"></i> Ref</th>
            <th><i class="ri-file-text-line me-1"></i> Form</th>
            <th><i class="ri-calendar-line me-1"></i> Date</th>
            <th><i class="ri-flag-line me-1"></i> Status</th>
            <th class="text-end" style="min-width:140px;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($apps as $app)
            @php
              $status = strtolower((string)($app->status ?? ''));
              $badge = match(true) {
                (bool)($app->is_draft) => 'secondary',
                str_contains($status, 'rejected') => 'danger',
                str_contains($status, 'approved') || $status === 'issued' => 'success',
                in_array($status, [
                    'needs_correction',
                    'correction_requested',
                    'registrar_raised_fix_request',
                    'returned_to_officer'
                ], true) => 'warning',
                in_array($status, [
                    'submitted',
                    'submitted_to_accreditation_officer',
                    'officer_review',
                    'approved_by_accreditation_officer_awaiting_payment',
                    'approved_by_officer_awaiting_payment_and_registrar_master',
                    'verified_by_officer_pending_registrar',
                    'registrar_review',
                    'registrar_approved_pending_registration_fee_payment',
                    'awaiting_reg_fee',
                    'accounts_review',
                    'awaiting_accounts_verification',
                    'registration_fee_awaiting_verification'
                ], true) => 'info',
                default => 'warning',
              };
              $pillClass = match($badge) {
                'success' => 'zd-pill-ok',
                'info' => 'zd-pill-info',
                'danger' => 'zd-pill-danger',
                'secondary' => 'zd-pill-slate',
                default => 'zd-pill-warn',
              };

              $date = $app->is_draft
                ? 'Draft'
                : (($app->submitted_at?->format('d M Y H:i')) ?? ($app->created_at?->format('d M Y H:i') ?? '—'));

              $ft = $app->form_type ?? null;
              $form = match(true) {
                $app->request_type === 'renewal' => 'AP5 - Renewal',
                $app->request_type === 'replacement' => 'AP5 - Replacement',
                $ft === 'AP2' => 'AP2 - News Agency',
                $ft === 'AP4' => 'AP4 - Foreign Representative Office',
                default => 'AP1 - Mass Media Service'
              };
            @endphp

            <tr>
              <td><span class="zd-ref">{{ $app->reference ?? ('APP-' . $app->id) }}</span></td>
              <td>{{ $form }}</td>
              <td class="small text-muted">{{ $date }}</td>
              <td>
                <span class="zd-pill {{ $pillClass }}">
                  {{ ucwords(str_replace('_',' ', $status ?: ($app->is_draft ? 'draft' : 'processing'))) }}
                </span>
              </td>
              <td class="text-end">
                <div class="zmc-action-strip">
                  @if($app->is_draft)
                    @php
                      $editRoute = match($app->form_type) {
                        'AP2' => route('mediahouse.ap2.create'),
                        'AP4' => route('mediahouse.ap4.create'),
                        default => route('mediahouse.ap1.create'),
                      };
                    @endphp
                    <a class="btn btn-sm zmc-icon-btn btn-outline-secondary" href="{{ $editRoute }}" title="Continue">
                      <i class="fa-regular fa-pen-to-square"></i>
                    </a>
                    <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-danger js-delete-draft" data-app-id="{{ $app->id }}" title="Delete Draft">
                      <i class="fa-regular fa-trash-can"></i>
                    </button>
                  @else
                    <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-primary js-view-more" data-app-id="{{ $app->id }}" title="View">
                      <i class="fa-regular fa-eye"></i>
                    </button>
                    @if(in_array($status, [
                      'correction_requested', 
                      'returned_to_applicant', 
                      'officer_rejected', 
                      'registrar_rejected', 
                      'payment_rejected',
                      'submitted',
                      'submitted_to_accreditation_officer',
                      'officer_review',
                      'registrar_review',
                      'accounts_review',
                      'awaiting_accounts_verification'
                    ], true))
                      <a href="{{ route('mediahouse.applications.edit', $app) }}" class="btn btn-sm btn-warning fw-bold" title="Edit & Resubmit">
                        <i class="ri-edit-2-line me-1"></i> Edit
                      </a>
                    @endif
                    @php
                      $hasPendingPayment = $app->relationLoaded('paymentSubmissions')
                        ? $app->paymentSubmissions->contains(fn($payment) => in_array(strtolower((string) $payment->status), ['submitted', 'pending'], true))
                        : (($app->proof_status ?? null) === 'submitted');
                      $canPay = in_array($app->status, [
                        Application::ACCOUNTS_REVIEW,
                        Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
                        Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                        Application::AWAITING_REG_FEE,
                        Application::SUBMITTED,
                        Application::PAYMENT_REJECTED
                      ], true);
                      $canPay = $canPay && !$hasPendingPayment;
                      
                      $stage = 'application_fee';
                      if ($app->status === Application::REGISTRAR_APPROVED_PENDING_REG_FEE || $app->status === Application::AWAITING_REG_FEE) {
                        $stage = 'registration_fee';
                      }
                    @endphp
                    @if($canPay)
                      <button type="button" class="btn btn-sm btn-success fw-bold js-pay-now" 
                              data-app-id="{{ $app->id }}" 
                              data-app-ref="{{ $app->reference }}"
                              data-payment-stage="{{ $stage }}"
                              data-application-type="mediahouse"
                              data-media-category="{{ $app->media_house_category_code ?? data_get($app->form_data, 'media_house_category_code', 'default') }}"
                              title="Pay Now">
                        <i class="ri-bank-card-line me-1"></i> Pay
                      </button>
                    @elseif($hasPendingPayment)
                      <span class="badge rounded-pill text-bg-light border" title="Payment proof is awaiting Accounts verification">
                        <i class="ri-time-line me-1"></i> Payment submitted
                      </span>
                    @endif
                    @if(in_array($status, ['submitted','officer_review','registrar_review','accounts_review']))
                      <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-danger js-withdraw-app" data-app-id="{{ $app->id }}" title="Withdraw Application">
                        <i class="ri-arrow-go-back-line"></i>
                      </button>
                    @endif
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-5 text-muted">No applications found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- Global Details Modal (View) --}}
<div class="modal fade zmc-modal-pop" id="appDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header zmc-modal-header">
        <div>
          <div class="zmc-modal-title">
            <i class="fa-regular fa-file-lines me-2" style="color:var(--zmc-accent-dark)"></i>
            Application Details
          </div>
          <div class="zmc-modal-sub" id="mdl_meta">—</div>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="mdl_loading" class="d-none text-center py-5">
          <div class="spinner-border" style="color:var(--zmc-accent-dark)"></div>
          <div class="text-muted mt-2" style="font-size:12px;">Loading…</div>
        </div>

        <div id="mdl_error" class="alert alert-danger d-none"></div>
        <div id="mdl_content_area" class="d-none"></div>
      </div>

      <div class="modal-footer zmc-modal-footer">
        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@include('portal.partials.payment_modal')

@push('scripts')
<script>
  const ZMC_DETAILS_URL = @json($detailsUrlTemplate);

  function zmcFmt(v){
    if(v === null || v === undefined) return '—';
    const s = String(v).trim();
    if(s === '') return '—';
    const d = document.createElement('span');
    d.textContent = s;
    return d.innerHTML;
  }

  function zmcOpenModal(selector){
    const el = document.querySelector(selector);
    if(!el) return;
    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
      bootstrap.Modal.getOrCreateInstance(el).show();
    }
  }

  function zmcBlock(titleHtml, bodyHtml){
    return `<div class="zmc-mdl-block"><div class="zmc-mdl-title">${titleHtml}</div>${bodyHtml}</div>`;
  }

  function zmcInput(label, value, col = 4){
    return `<div class="col-12 col-md-${col}"><label class="form-label zmc-lbl">${label}</label><input type="text" class="form-control zmc-input" value="${zmcFmt(value)}" readonly></div>`;
  }

  function zmcTextarea(label, value, col = 12){
    return `<div class="col-12 col-md-${col}"><label class="form-label zmc-lbl">${label}</label><textarea class="form-control zmc-input" rows="3" readonly>${zmcFmt(value)}</textarea></div>`;
  }

  async function loadApplicationDetails(appId) {
    const loader = document.getElementById('mdl_loading');
    const area   = document.getElementById('mdl_content_area');
    const meta   = document.getElementById('mdl_meta');
    const errBox = document.getElementById('mdl_error');

    if (errBox){ errBox.classList.add('d-none'); errBox.textContent = ''; }
    if (loader) loader.classList.remove('d-none');
    if (area){ area.classList.add('d-none'); area.innerHTML = ''; }
    if (meta) meta.textContent = '—';

    try {
      const url = ZMC_DETAILS_URL.replace('__ID__', appId);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.ok === false) throw new Error(data.message || 'Failed to load details');

      const app = data.application || {};
      const formCode = String(app.form_code || '').toUpperCase();
      const ref = app.reference || app.application_number || ('APP-' + (app.id || appId));
      const status = app.status || '—';

      if (meta) {
        meta.innerHTML = `<span class="badge bg-light text-dark border me-2">${zmcFmt(formCode || 'Form')}</span>Ref: <span class="fw-bold">${zmcFmt(ref)}</span> • Status: <span class="fw-bold" style="color:var(--zmc-accent-dark)">${zmcFmt(status)}</span>`;
      }

      let html = '';

      const correctionComments = Array.isArray(data.correction_comments) ? data.correction_comments : [];
      if (correctionComments.length) {
        const correctionsHtml = correctionComments.map(comment => `
          <div class="alert alert-warning mb-2">
            <div class="fw-bold">${zmcFmt(String(comment.type || 'correction').replace(/_/g, ' '))}</div>
            <div class="mt-1">${zmcFmt(comment.body)}</div>
            <small class="text-muted">${zmcFmt(comment.author || 'ZMC')} · ${zmcFmt(comment.created_at || '')}</small>
          </div>
        `).join('');
        html += zmcBlock('<i class="ri-error-warning-line"></i> Requested corrections', correctionsHtml);
      }

      const fd = data.form_data || {};
      const labels = data.labels || {};
      const exclude = ['current_step', 'registration_scope', 'journalist_scope', 'directors', 'managers', 'senior_managers', 'directors_rows', 'managers_rows', 'ap1', 'staff_members', 'org_owners', 'form_type', 'declaration_agreed', 'rep_office_shareholders'];

      let fieldsHtml = '';
      for (const [key, val] of Object.entries(fd)) {
        if (exclude.includes(key) || !val) continue;
        const label = labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        if (typeof val === 'object' && val !== null) {
          if (Array.isArray(val)) continue;
          fieldsHtml += zmcTextarea(label, JSON.stringify(val, null, 2));
        } else {
          fieldsHtml += zmcInput(label, val);
        }
      }

      if (fieldsHtml) {
        html += zmcBlock(`<i class="fa-regular fa-file-lines"></i> Application details`, `<div class="row g-3">${fieldsHtml}</div>`);
      }

      const directors = Array.isArray(data.directors) ? data.directors : (Array.isArray(fd.directors) ? fd.directors : []);
      if (directors.length > 0) {
        let dRows = '';
        directors.forEach(d => {
          dRows += `<tr>
            <td>${zmcFmt(d.name || d.director_name || d.full_name)}</td>
            <td>${zmcFmt(d.nationality || d.director_nationality)}</td>
            <td>${zmcFmt(d.id_number || d.id_passport || d.national_id || '')}</td>
            <td>${zmcFmt(d.address || d.director_address)}</td>
          </tr>`;
        });
        html += zmcBlock(
          `<i class="fa-solid fa-people-group"></i> Directors / Proprietors`,
          `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>Nationality</th><th>ID/Passport</th><th>Address</th></tr></thead><tbody>${dRows}</tbody></table></div>`
        );
      }

      const orgOwners = Array.isArray(fd.org_owners) ? fd.org_owners : [];
      if (orgOwners.length > 0) {
        let oRows = '';
        orgOwners.forEach(o => {
          oRows += `<tr>
            <td>${zmcFmt(o.name)}</td>
            <td>${zmcFmt(o.nationality)}</td>
          </tr>`;
        });
        html += zmcBlock(
          `<i class="fa-solid fa-building"></i> Organisation Owners`,
          `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>Nationality</th></tr></thead><tbody>${oRows}</tbody></table></div>`
        );
      }

      const staffMembers = Array.isArray(fd.staff_members) ? fd.staff_members : [];
      if (staffMembers.length > 0) {
        let sRows = '';
        staffMembers.forEach(s => {
          sRows += `<tr>
            <td>${zmcFmt(s.name)}</td>
            <td>${zmcFmt(s.nationality)}</td>
            <td>${zmcFmt(s.position)}</td>
            <td>${zmcFmt(s.passport_id)}</td>
            <td>${zmcFmt(s.work_permit)}</td>
          </tr>`;
        });
        html += zmcBlock(
          `<i class="fa-solid fa-users"></i> Staff in Zimbabwe`,
          `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>Nationality</th><th>Position</th><th>Passport/ID</th><th>Work Permit</th></tr></thead><tbody>${sRows}</tbody></table></div>`
        );
      }

      const managers = Array.isArray(data.managers) ? data.managers : (Array.isArray(fd.senior_managers) ? fd.senior_managers : []);
      if (managers.length > 0) {
        let mRows = '';
        managers.forEach(m => {
          mRows += `<tr>
            <td>${zmcFmt(m.full_name || m.name || [m.first_name, m.surname].filter(Boolean).join(' '))}</td>
            <td>${zmcFmt(m.nationality || m.manager_nationality)}</td>
            <td>${zmcFmt(m.qualifications || m.qualification)}</td>
            <td>${zmcFmt(m.occupation || m.manager_occupation || m.position || m.experience)}</td>
            <td>${zmcFmt(m.address || m.manager_address)}</td>
          </tr>`;
        });
        html += zmcBlock(
          `<i class="fa-solid fa-user-gear"></i> Managers`,
          `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>Nationality</th><th>Qualifications</th><th>Occupation / Experience</th><th>Address</th></tr></thead><tbody>${mRows}</tbody></table></div>`
        );
      }

      const docs = Array.isArray(data.documents) ? data.documents : [];
      let docRows = docs.length ? '' : `<tr><td colspan="3" class="text-muted text-center">—</td></tr>`;
      docs.forEach(doc => {
        const open = doc.url
          ? `<a href="${doc.url}" target="_blank" class="btn btn-sm btn-outline-primary" title="Open document"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>`
          : `<span class="text-muted">—</span>`;
        docRows += `<tr><td class="fw-bold">${zmcFmt(doc.document_type)}</td><td>${zmcFmt(doc.original_name || doc.file_name)}</td><td class="text-end">${open}</td></tr>`;
      });

        html += zmcBlock(
          `<i class="fa-regular fa-folder-open"></i> Attachments`,
          `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Type</th><th>File</th><th class="text-end">Open</th></tr></thead><tbody>${docRows}</tbody></table></div>`
        );

        const prevApps = Array.isArray(data.previous_applications) ? data.previous_applications : [];
        if (prevApps.length > 0) {
          let prevRows = prevApps.map(pa => `
            <tr>
              <td>${zmcFmt(pa.reference)}</td>
              <td class="text-capitalize">${zmcFmt(pa.type)}</td>
              <td><span class="badge bg-light text-dark border">${zmcFmt(pa.status)}</span></td>
              <td>${zmcFmt(pa.date)}</td>
            </tr>
          `).join('');

          html += zmcBlock(
            `<i class="fa-solid fa-history"></i> Previous Applications`,
            `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Reference</th><th>Type</th><th>Status</th><th>Date</th></tr></thead><tbody>${prevRows}</tbody></table></div>`
          );
        }

        // Previous Payments Block
        const prevPays = Array.isArray(data.previous_payments) ? data.previous_payments : [];
        if (prevPays.length > 0) {
          let payRows = prevPays.map(p => `
            <tr>
              <td>${zmcFmt(p.reference)}</td>
              <td>${zmcFmt(p.amount)} ${zmcFmt(p.currency)}</td>
              <td class="text-capitalize">${zmcFmt(p.method)}</td>
              <td><span class="badge bg-light text-dark border text-capitalize">${zmcFmt(p.status)}</span></td>
              <td>${zmcFmt(p.date)}</td>
            </tr>
          `).join('');

          html += zmcBlock(
            `<i class="fa-solid fa-money-bill-transfer"></i> Payment History`,
            `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>${payRows}</tbody></table></div>`
          );
        }

      if (area) {
        area.innerHTML = html;
        area.classList.remove('d-none');
      }

    } catch (e) {
      if (errBox) {
        errBox.textContent = 'Error loading details: ' + (e.message || 'Unknown error');
        errBox.classList.remove('d-none');
      }
    } finally {
      if (loader) loader.classList.add('d-none');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', async function(e){
      const btn = e.target.closest('.js-view-more');
      if(btn){
        const appId = btn.getAttribute('data-app-id');
        if(!appId) return;
        zmcOpenModal('#appDetailsModal');
        loadApplicationDetails(appId);
        return;
      }

      // Delete Draft
      const delBtn = e.target.closest('.js-delete-draft');
      if(delBtn){
        const appId = delBtn.getAttribute('data-app-id');
        if(!confirm('Are you sure you want to delete this draft? This action cannot be undone.')) return;
        
        try {
          const res = await fetch(`/portal/mediahouse/applications/${appId}`, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            }
          });
          const data = await res.json();
          if(data.success){
            window.location.reload();
          } else {
            alert(data.message || 'Error deleting draft.');
          }
        } catch(err) {
          alert('Failed to connect to server.');
        }
        return;
      }

      // Withdraw Application
      const withBtn = e.target.closest('.js-withdraw-app');
      if(withBtn){
        const appId = withBtn.getAttribute('data-app-id');
        if(!confirm('Are you sure you want to withdraw this application? It will be moved back to your drafts.')) return;

        try {
          const res = await fetch(`/portal/mediahouse/applications/${appId}/withdraw`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            }
          });
          const data = await res.json();
          if(data.success){
            window.location.reload();
          } else {
            alert(data.message || 'Error withdrawing application.');
          }
        } catch(err) {
          alert('Failed to connect to server.');
        }
      }

      // Registration Fee Pay Button in Alert
      const regFeeBtn = e.target.closest('.js-pay-registration-fee');
      if(regFeeBtn){
        const appId = regFeeBtn.getAttribute('data-app-id');
        const ref = regFeeBtn.getAttribute('data-app-ref');
        
        // Trigger the payment modal
        if(window.initPaymentModal){
          window.initPaymentModal(appId, ref, 'registration_fee', 'mediahouse');
        } else {
          // Fallback if initPaymentModal is not yet loaded
          const payModal = document.getElementById('paymentModal');
          if(payModal){
             document.getElementById('modal_app_id').value = appId;
             document.getElementById('modal_app_ref_display').textContent = ref;
             new bootstrap.Modal(payModal).show();
          }
        }
      }
    });

    // Show Media House welcome modal on every page load
    const modalEl = document.getElementById('mediaHouseWelcomeModal');
    if (modalEl) {
      setTimeout(() => {
        const m = bootstrap.Modal.getOrCreateInstance(modalEl);
        m.show();
      }, 800);
    }
  });

  // Function to manually show requirements modal
  function showRequirementsModal() {
    const modalEl = document.getElementById('mediaHouseWelcomeModal');
    if (modalEl) {
      const m = bootstrap.Modal.getOrCreateInstance(modalEl);
      m.show();
    }
  }
</script>
@endpush
</div>

{{-- Media House Requirements Welcome Modal --}}
<div class="modal fade" id="mediaHouseWelcomeModal" tabindex="-1" aria-labelledby="mhWelcomeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content overflow-hidden border-0 shadow-lg" style="border-radius: 24px;">
      <div class="modal-body p-0">
        <div class="row g-0">
          <div class="col-md-3 d-none d-md-block position-relative" style="background: url('/zmc_building.png') center center / cover no-repeat;">
            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(135deg, rgba(0, 0, 0, 0.82), rgba(0, 0, 0, 0.88)); z-index: 1;"></div>
            <div class="h-100 d-flex flex-column justify-content-center p-4 text-white position-relative" style="z-index: 2;">
              <i class="ri-building-2-line mb-4" style="font-size: 64px; color: #facc15;"></i>
              <h3 class="fw-black mb-3" style="color: #ffffff;">Welcome!</h3>
              <p class="small" style="color: rgba(255,255,255,0.9);">Registering your media house is a significant step. Please ensure you have all the necessary documents ready before you begin.</p>
            </div>
          </div>
          <div class="col-md-9">
            <div class="p-4 p-md-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-black m-0" id="mhWelcomeModalLabel" style="color: #1e293b;">Registration Requirements</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <div class="border rounded-3 p-3 h-100" style="background: #f0fdf4;">
                    <div class="fw-bold mb-2 d-flex align-items-center" style="color: #166534; font-size: 13px;">
                      <i class="ri-home-4-line me-2" style="font-size:18px;"></i> LOCAL MEDIA HOUSE (AP1 / AP2)
                    </div>
                    <ul class="list-unstyled mb-0" style="font-size:12px;">
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Certificate of Incorporation</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Memorandum of Association</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>CR14 Form (Latest list of directors)</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Certified Copies of Directors' IDs</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Editorial Charter</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Code of Ethics</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Code of Conduct for Employees</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>In-house Style Book</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Projected Cash Flow Statement (3 Yrs)</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Projected Balance Sheet (3 Yrs)</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Market Analysis</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Mission Statement</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #166534;"></i>Dummy Copy of Publication (if applicable)</li>
                    </ul>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="border rounded-3 p-3 h-100" style="background: #eff6ff;">
                    <div class="fw-bold mb-2 d-flex align-items-center" style="color: #1e40af; font-size: 13px;">
                      <i class="ri-global-line me-2" style="font-size:18px;"></i> FOREIGN REPRESENTATIVE OFFICE (AP4)
                    </div>
                    <ul class="list-unstyled mb-0" style="font-size:12px;">
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Letter from Parent Organisation</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Passport Copies of Representative(s)</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Work Permit(s) for Foreign Staff</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Proof of Zimbabwe Address</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Clearance Letter (Permission to operate in Zimbabwe)</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Certificate of Incorporation (Country of Domicile)</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Certified Copy of National Identity for Each Director</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>Mission Statement</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>In-house Code of Ethics</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>In-house Code of Conduct for Employees</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>In-house Style Book</li>
                      <li class="mb-1" style="color: #334155;"><i class="ri-checkbox-circle-fill me-1" style="color: #1e40af;"></i>List of Journalists Employed in the Representative Office</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="d-grid">
                <button type="button" class="btn btn-lg py-3 rounded-pill fw-bold shadow-sm" data-bs-dismiss="modal" style="background: #1a1a1a; border-color: #facc15; color: #facc15;">
                  I Understand, Let's Begin <i class="ri-arrow-right-line ms-2"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .fw-black { font-weight: 900 !important; }
</style>
@endsection
