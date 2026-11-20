@extends('layouts.portal')

@section('title', 'Media Practitioner Portal Dashboard')

@section('content')
@php
  use Illuminate\Support\Facades\Route;
  use Illuminate\Support\Str;
  use App\Models\Application;

  $detailsUrlTemplate = Route::has('portal.applications.details')
    ? route('portal.applications.details', ['application' => '__ID__'])
    : url('/portal/applications/__ID__/details');

  $apps = $recentApplications ?? [];

  /**
   * Maps an application status to a semantic "tone" used by the
   * redesigned status pills.
   */
  $toneFor = function ($status, $isDraft = false) {
      $status = strtolower((string) $status);
      if ($isDraft) return 'slate';
      if (str_contains($status, 'rejected') || str_contains($status, 'correction')) return 'danger';
      if (in_array($status, [
          'approved_by_officer_awaiting_payment_and_registrar_master',
          'approved_by_accreditation_officer_awaiting_payment',
          'approved_by_ao_awaiting_payment_and_registrar_master',
          'approved_awaiting_payment',
      ], true)) return 'gold';
      if (in_array($status, [
          'submitted',
          'submitted_to_accreditation_officer',
          'officer_review',
          'registrar_review',
          'accounts_review',
          'awaiting_accounts_verification',
          'registrar_raised_fix_request',
          'returned_to_officer',
      ], true)) return 'info';
      return 'warn';
  };
@endphp

<style>
  /* ============================================================
     ZMC Portal Dashboard — Modern redesign
     Palette: ink black + commission gold + soft neutral canvas
     ============================================================ */
  .zmc-dashboard-wrapper{
    --zd-ink:#0B0F17;
    --zd-ink-2:#161E2B;
    --zd-line:#E9EDF3;
    --zd-canvas:#F5F7FA;
    --zd-muted:#6B7A90;
    --zd-gold:#D4A017;
    --zd-gold-lt:#F2C94C;
    --zd-gold-bg:#FDF7E7;
    --zd-radius:20px;
    --zmc-accent:#D4A017;
    --zmc-accent-dark:#9A7208;
    font-family:'Roboto',system-ui,-apple-system,'Segoe UI',sans-serif;
    color:#334155;
    padding-bottom:8px;
  }
  .zmc-dashboard-wrapper *{box-sizing:border-box}

  /* ---------- Hero ---------- */
  .zd-hero{
    position:relative;
    overflow:hidden;
    border-radius:26px;
    padding:34px 34px 30px;
    color:#fff;
    margin-bottom:22px;
    background:
      radial-gradient(120% 180% at 100% 0%, #26313F 0%, #0B0F17 58%),
      #0B0F17;
    box-shadow:0 30px 70px -38px rgba(11,15,23,.85);
  }
  .zd-hero::before{
    content:"";
    position:absolute; inset:0;
    background:
      radial-gradient(52% 90% at 88% 6%, rgba(212,160,23,.42), transparent 62%),
      radial-gradient(45% 85% at 4% 105%, rgba(56,189,248,.14), transparent 68%);
    pointer-events:none;
  }
  .zd-hero::after{
    content:"";
    position:absolute; inset:0;
    background-image:
      linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
    background-size:52px 52px;
    mask-image:radial-gradient(80% 90% at 20% 0%, #000 0%, transparent 75%);
    -webkit-mask-image:radial-gradient(80% 90% at 20% 0%, #000 0%, transparent 75%);
    pointer-events:none;
  }
  .zd-hero-inner{position:relative; z-index:2}
  .zd-eyebrow{
    display:inline-flex; align-items:center; gap:8px;
    font-size:11px; font-weight:800; letter-spacing:.14em; text-transform:uppercase;
    padding:6px 12px; border-radius:999px;
    color:#F6DC93;
    background:rgba(212,160,23,.14);
    border:1px solid rgba(212,160,23,.32);
  }
  .zd-hero-title{
    font-size:clamp(22px, 2.4vw, 30px);
    font-weight:900;
    letter-spacing:-.02em;
    margin:14px 0 6px;
    line-height:1.15;
  }
  .zd-hero-sub{
    margin:0;
    font-size:14px;
    line-height:1.6;
    color:rgba(255,255,255,.68);
    max-width:56ch;
  }
  .zd-hero-actions{
    display:flex; flex-wrap:wrap; gap:10px;
    margin-top:22px;
  }

  /* ---------- Buttons ---------- */
  .zd-btn{
    display:inline-flex; align-items:center; gap:8px;
    padding:11px 17px;
    border-radius:13px;
    font-size:13px; font-weight:700;
    line-height:1;
    text-decoration:none;
    border:1px solid transparent;
    cursor:pointer;
    white-space:nowrap;
    transition:transform .18s ease, background .18s ease, box-shadow .18s ease, color .18s ease;
  }
  .zd-btn:active{transform:translateY(1px)}
  .zd-btn i{font-size:15px}
  .zd-btn-ghost{
    background:rgba(255,255,255,.07);
    border-color:rgba(255,255,255,.16);
    color:#fff;
    backdrop-filter:blur(10px);
  }
  .zd-btn-ghost:hover{background:rgba(255,255,255,.15); color:#fff}
  .zd-btn-gold{
    background:linear-gradient(135deg, var(--zd-gold-lt), var(--zd-gold));
    color:#171203;
    box-shadow:0 14px 30px -14px rgba(212,160,23,.95);
  }
  .zd-btn-gold:hover{color:#000; filter:brightness(1.05)}
  .zd-btn-ink{
    background:var(--zd-ink); color:#fff;
  }
  .zd-btn-ink:hover{background:#000; color:#fff}
  .zd-btn-soft{
    background:#fff; border-color:var(--zd-line); color:#334155;
  }
  .zd-btn-soft:hover{border-color:#cbd5e1; color:var(--zd-ink)}
  .zd-btn-xs{padding:8px 12px; font-size:12px; border-radius:11px}

  /* ---------- Cards ---------- */
  .zd-card{
    background:#fff;
    border:1px solid var(--zd-line);
    border-radius:var(--zd-radius);
    box-shadow:0 1px 2px rgba(16,24,40,.04), 0 20px 46px -36px rgba(16,24,40,.45);
  }
  .zd-card-pad{padding:22px 24px}
  .zd-card-head{
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:18px 22px;
    border-bottom:1px solid var(--zd-line);
  }
  .zd-card-title{
    display:flex; align-items:center; gap:10px;
    margin:0; font-size:14.5px; font-weight:800; color:var(--zd-ink);
    letter-spacing:-.01em;
  }
  .zd-card-title i{color:var(--zd-gold); font-size:17px}
  .zd-link{
    font-size:12px; font-weight:700; color:var(--zd-muted);
    text-decoration:none; padding:6px 11px; border-radius:9px;
    border:1px solid var(--zd-line); background:#fff;
    transition:.18s;
  }
  .zd-link:hover{color:var(--zd-ink); border-color:#cbd5e1}

  /* ---------- Action required ---------- */
  .zd-alert{
    position:relative;
    overflow:hidden;
    border:1px solid #F7E3B4;
    background:linear-gradient(180deg, #FFFCF3 0%, #FFFFFF 60%);
    border-radius:var(--zd-radius);
    box-shadow:0 1px 2px rgba(16,24,40,.04), 0 20px 46px -38px rgba(180,120,10,.55);
    margin-bottom:22px;
  }
  .zd-alert::before{
    content:""; position:absolute; left:0; top:0; bottom:0; width:5px;
    background:linear-gradient(180deg,#F2C94C,#C9901A);
  }
  .zd-alert-head{display:flex; gap:16px; padding:22px 26px 16px; align-items:flex-start}
  .zd-alert-icon{
    flex:0 0 46px; width:46px; height:46px; border-radius:14px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#FDEEC4,#F7DA92);
    color:#8A6205; font-size:22px;
    box-shadow:inset 0 0 0 1px rgba(201,144,26,.22);
  }
  .zd-alert-title{font-size:16px; font-weight:900; color:var(--zd-ink); letter-spacing:-.01em}
  .zd-alert-sub{margin:4px 0 0; font-size:13px; color:var(--zd-muted); line-height:1.6; max-width:80ch}

  /* ---------- Reminders ---------- */
  .zd-reminder{
    display:flex; align-items:flex-start; gap:14px;
    padding:16px 18px;
    border-radius:16px;
    background:linear-gradient(180deg,#FFFCF3,#fff);
    border:1px solid #F7E3B4;
    box-shadow:0 14px 34px -30px rgba(180,120,10,.7);
    margin-bottom:12px;
  }
  .zd-reminder-icon{
    flex:0 0 38px; width:38px; height:38px; border-radius:11px;
    display:flex; align-items:center; justify-content:center;
    background:#FDF1D3; color:#8A6205; font-size:19px;
  }
  .zd-reminder-title{font-size:13.5px; font-weight:800; color:var(--zd-ink)}
  .zd-reminder-msg{font-size:13px; color:#475569; line-height:1.55; margin-top:3px}

  /* ---------- Stats ---------- */
  .zd-stats{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(215px, 1fr));
    gap:16px;
    margin-bottom:22px;
  }
  .zd-stat{
    position:relative;
    overflow:hidden;
    padding:20px 20px 18px;
    background:#fff;
    border:1px solid var(--zd-line);
    border-radius:var(--zd-radius);
    box-shadow:0 1px 2px rgba(16,24,40,.04), 0 18px 40px -36px rgba(16,24,40,.55);
    transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease;
  }
  .zd-stat:hover{
    transform:translateY(-3px);
    border-color:#DCE3EC;
    box-shadow:0 1px 2px rgba(16,24,40,.05), 0 28px 54px -34px rgba(16,24,40,.6);
  }
  .zd-stat::after{
    content:""; position:absolute; right:-30px; top:-30px;
    width:110px; height:110px; border-radius:50%;
    background:radial-gradient(circle, rgba(212,160,23,.09), transparent 70%);
    pointer-events:none;
  }
  .zd-stat-top{display:flex; align-items:flex-start; justify-content:space-between; gap:12px}
  .zd-stat-label{
    font-size:11px; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
    color:var(--zd-muted);
  }
  .zd-stat-icon{
    width:40px; height:40px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:19px;
  }
  .zd-stat-icon.is-slate{background:#F1F5F9; color:#475569}
  .zd-stat-icon.is-info {background:#E8F1FE; color:#1D4ED8}
  .zd-stat-icon.is-ok   {background:#E7F7EF; color:#047857}
  .zd-stat-icon.is-gold {background:var(--zd-gold-bg); color:#9A7208}
  .zd-stat-value{
    font-size:34px; font-weight:900; letter-spacing:-.03em;
    color:var(--zd-ink); line-height:1; margin-top:16px;
  }
  .zd-stat-foot{font-size:11.5px; color:var(--zd-muted); margin-top:8px}

  /* ---------- Two column ---------- */
  .zd-grid-2{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));
    gap:16px;
    margin-bottom:22px;
  }

  /* ---------- List items (notices / events) ---------- */
  .zd-list{padding:8px 10px 12px}
  .zd-list-item{
    display:flex; gap:12px;
    padding:12px 12px;
    border-radius:13px;
    transition:background .18s ease;
  }
  .zd-list-item:hover{background:#F8FAFC}
  .zd-list-dot{
    flex:0 0 8px; width:8px; height:8px; border-radius:50%;
    margin-top:7px;
    background:linear-gradient(135deg,var(--zd-gold-lt),var(--zd-gold));
    box-shadow:0 0 0 3px rgba(212,160,23,.14);
  }
  .zd-list-title{font-size:13.5px; font-weight:700; color:var(--zd-ink); line-height:1.4}
  .zd-list-meta{font-size:12px; color:var(--zd-muted); line-height:1.5; margin-top:2px}
  .zd-date-chip{
    flex:0 0 auto;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    min-width:52px; padding:7px 9px;
    border-radius:11px;
    background:#F8FAFC; border:1px solid var(--zd-line);
    line-height:1.1;
  }
  .zd-date-chip .d{font-size:15px; font-weight:900; color:var(--zd-ink)}
  .zd-date-chip .m{font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:var(--zd-muted)}

  /* ---------- Tables ---------- */
  .zd-table-wrap{overflow-x:auto}
  .zd-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    margin:0;
  }
  .zd-table thead th{
    font-size:10.5px; font-weight:800; letter-spacing:.1em; text-transform:uppercase;
    color:var(--zd-muted);
    background:#FAFBFD;
    padding:12px 18px;
    border-bottom:1px solid var(--zd-line);
    white-space:nowrap;
  }
  .zd-table thead th i{font-size:13px; opacity:.7; margin-right:5px}
  .zd-table tbody td{
    padding:14px 18px;
    border-bottom:1px solid #F1F4F8;
    font-size:13.5px;
    vertical-align:middle;
    color:#3F4C60;
  }
  .zd-table tbody tr:last-child td{border-bottom:0}
  .zd-table tbody tr{transition:background .16s ease}
  .zd-table tbody tr:hover{background:#FAFCFF}
  .zd-ref{
    display:inline-flex; align-items:center;
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    font-size:12.5px; font-weight:700; color:var(--zd-ink);
    background:#F4F6FA; border:1px solid #E6EAF1;
    padding:4px 9px; border-radius:8px;
    letter-spacing:.01em;
  }
  .zd-tag{
    display:inline-flex; align-items:center;
    font-size:11.5px; font-weight:700;
    padding:5px 11px; border-radius:999px;
    background:#F4F6FA; border:1px solid #E6EAF1; color:#42506A;
    white-space:nowrap;
  }
  .zd-muted{color:var(--zd-muted)}
  .zd-xs{font-size:11.5px}

  /* ---------- Status pills ---------- */
  .zd-pill{
    display:inline-flex; align-items:center; gap:7px;
    padding:5px 12px 5px 10px;
    border-radius:999px;
    font-size:11.5px; font-weight:800;
    letter-spacing:.01em;
    white-space:nowrap;
    border:1px solid transparent;
  }
  .zd-pill::before{
    content:""; width:6px; height:6px; border-radius:50%;
    background:currentColor; flex:0 0 6px;
  }
  .zd-pill-slate{background:#F1F5F9; color:#475569; border-color:#E2E8F0}
  .zd-pill-info {background:#EAF2FE; color:#1D4ED8; border-color:#D6E5FD}
  .zd-pill-gold {background:#FDF7E7; color:#8A6205; border-color:#F3E3B8}
  .zd-pill-warn {background:#FFF6E6; color:#B45309; border-color:#FAE3BE}
  .zd-pill-danger{background:#FDEDEE; color:#B42318; border-color:#F8D7DA}
  .zd-pill-ok  {background:#E7F7EF; color:#047857; border-color:#CFEFDF}

  /* ---------- Action strip ---------- */
  .zd-actions{display:flex; justify-content:flex-end; align-items:center; gap:7px; flex-wrap:wrap}
  .zd-icon-btn{
    width:34px; height:34px; padding:0;
    display:inline-flex; align-items:center; justify-content:center;
    border-radius:10px; border:1px solid var(--zd-line);
    background:#fff; color:#475569; font-size:14px;
    transition:.18s; cursor:pointer;
  }
  .zd-icon-btn:hover{background:#F8FAFC; color:var(--zd-ink); border-color:#CBD5E1}
  .zd-icon-btn.is-danger:hover{background:#FDEDEE; color:#B42318; border-color:#F8D7DA}
  .zd-icon-btn.is-primary:hover{background:#EAF2FE; color:#1D4ED8; border-color:#D6E5FD}

  /* ---------- Tabs (segmented) ---------- */
  .zd-tabs{
    display:inline-flex; gap:4px; padding:5px;
    background:#F4F6FA; border:1px solid var(--zd-line);
    border-radius:14px;
  }
  .zd-tabs .nav-link{
    display:inline-flex; align-items:center; gap:7px;
    border:0; border-radius:10px;
    padding:9px 15px;
    font-size:12.5px; font-weight:800;
    color:var(--zd-muted);
    background:transparent;
    transition:.18s;
  }
  .zd-tabs .nav-link:hover{color:var(--zd-ink)}
  .zd-tabs .nav-link.active{
    background:#fff; color:var(--zd-ink);
    box-shadow:0 1px 2px rgba(16,24,40,.06), 0 8px 20px -14px rgba(16,24,40,.5);
  }
  .zd-tabs .nav-link i{font-size:15px}
  .zd-tab-count{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:20px; height:20px; padding:0 6px;
    border-radius:999px;
    background:linear-gradient(135deg,var(--zd-gold-lt),var(--zd-gold));
    color:#171203; font-size:10.5px; font-weight:900;
  }

  /* ---------- Progress ---------- */
  .zd-progress{
    flex:1 1 auto; height:6px; border-radius:999px;
    background:#EEF2F7; overflow:hidden;
  }
  .zd-progress > span{display:block; height:100%; border-radius:999px}

  /* ---------- Empty states ---------- */
  .zd-empty{padding:52px 24px; text-align:center}
  .zd-empty-icon{
    width:66px; height:66px; margin:0 auto 14px;
    border-radius:20px;
    display:flex; align-items:center; justify-content:center;
    background:#F4F6FA; color:#94A3B8; font-size:30px;
  }
  .zd-empty h6{margin:0 0 5px; font-size:14.5px; font-weight:800; color:var(--zd-ink)}
  .zd-empty p{margin:0; font-size:12.5px; color:var(--zd-muted)}

  /* ---------- Details modal polish ---------- */
  .zmc-modal-pop .modal-content{
    border:0; border-radius:22px; overflow:hidden;
    box-shadow:0 40px 100px -45px rgba(11,15,23,.75);
  }
  .zmc-modal-pop .zmc-modal-header{
    background:
      radial-gradient(90% 200% at 100% 0%, rgba(212,160,23,.35), transparent 60%),
      #0B0F17;
    border:0; padding:20px 24px; color:#fff;
  }
  .zmc-modal-pop .zmc-modal-title{
    font-size:15px; font-weight:900; color:#fff; letter-spacing:-.01em;
  }
  .zmc-modal-pop .zmc-modal-title i{color:var(--zd-gold-lt) !important}
  .zmc-modal-pop .zmc-modal-sub{
    font-size:12px; color:rgba(255,255,255,.62); margin-top:3px;
  }
  .zmc-modal-pop .zmc-modal-sub .badge{
    background:rgba(255,255,255,.1) !important; color:#fff !important;
    border:1px solid rgba(255,255,255,.18) !important;
  }
  .zmc-modal-pop .zmc-modal-footer{
    border-top:1px solid var(--zd-line); padding:14px 22px; background:#FAFBFD;
  }
  .zmc-modal-pop .btn-close{filter:invert(1) grayscale(1) brightness(2); opacity:.7}
  .zmc-modal-pop .btn-close:hover{opacity:1}

  .zmc-mdl-block{
    border:1px solid var(--zd-line); border-radius:16px;
    padding:18px 20px; margin-bottom:16px; background:#fff;
  }
  .zmc-mdl-title{
    display:flex; align-items:center; gap:9px;
    font-size:12px; font-weight:900; letter-spacing:.09em; text-transform:uppercase;
    color:var(--zd-ink); margin-bottom:14px;
    padding-bottom:11px; border-bottom:1px solid var(--zd-line);
  }
  .zmc-mdl-title i{color:var(--zd-gold); font-size:15px}
  .zmc-lbl{
    font-size:10.5px; font-weight:800; letter-spacing:.07em; text-transform:uppercase;
    color:var(--zd-muted); margin-bottom:5px;
  }
  .zmc-input{
    font-size:13px; border-radius:11px; border-color:var(--zd-line);
    background:#FAFBFD; color:var(--zd-ink);
  }
  .zmc-input:focus{border-color:var(--zd-gold); box-shadow:0 0 0 3px rgba(212,160,23,.14)}
  .zmc-table-lite thead th{
    font-size:10.5px; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
    color:var(--zd-muted); background:#FAFBFD; border-bottom:1px solid var(--zd-line);
  }
  .zmc-table-lite tbody td{
    font-size:13px; border-bottom:1px solid #F1F4F8; vertical-align:middle;
  }

  /* ---------- Responsive ---------- */
  @media (max-width: 767.98px){
    .zd-hero{padding:26px 22px; border-radius:22px}
    .zd-hero-actions .zd-btn{flex:1 1 calc(50% - 10px); justify-content:center}
    .zd-stat-value{font-size:28px}
    .zd-table thead th,
    .zd-table tbody td{padding:12px 14px}
    .zd-alert-head{padding:18px 20px 12px}
  }
</style>

<div class="zmc-dashboard-wrapper">

  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-warning" role="alert">{{ session('error') }}</div>
  @endif

  {{-- ============================ HERO ============================ --}}
  <div class="zd-hero">
    <div class="zd-hero-inner">
      <span class="zd-eyebrow"><i class="ri-shield-star-line"></i> Media Practitioner Portal</span>

      <h1 class="zd-hero-title">Accreditation Dashboard</h1>
      <p class="zd-hero-sub">
        Track your new accreditation, renewal and replacement submissions — review statuses,
        respond to officer feedback and keep your credentials current.
      </p>

      <div class="zd-hero-actions">
        <button type="button" class="zd-btn zd-btn-ghost" onclick="showRequirementsModal()">
          <i class="ri-file-list-3-line"></i> View Requirements
        </button>
        <a href="{{ route('accreditation.renewal') }}" class="zd-btn zd-btn-ghost">
          <i class="ri-refresh-line"></i> Renew
        </a>
        <a href="{{ route('accreditation.replacement') }}" class="zd-btn zd-btn-ghost">
          <i class="ri-exchange-line"></i> Replace
        </a>
        <a href="{{ route('accreditation.new') }}" class="zd-btn zd-btn-gold">
          <i class="ri-add-line"></i> New Accreditation
        </a>
      </div>
    </div>
  </div>

  {{-- ====================== ACTION REQUIRED ====================== --}}
  @if(isset($actionRequired) && $actionRequired->count())
    <div class="zd-alert">
      <div class="zd-alert-head">
        <div class="zd-alert-icon"><i class="ri-error-warning-line"></i></div>
        <div>
          <div class="zd-alert-title">Action required</div>
          <p class="zd-alert-sub">
            You have <strong>{{ $actionRequired->count() }}</strong> application(s) returned for your
            attention. Review the comments, make the necessary corrections and resubmit.
          </p>
        </div>
      </div>

      <div class="zd-table-wrap">
        <table class="zd-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Type</th>
              <th>Status</th>
              <th>Returned</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($actionRequired as $ar)
              @php
                $arStatus = strtolower((string)$ar->status);
                $arType = $ar->request_type === 'new'
                  ? 'New Accreditation'
                  : ($ar->request_type === 'renewal' ? 'Renewal' : 'Replacement');
                $statusLabel = ucwords(str_replace('_',' ', $arStatus));
                $stage = match(true) {
                  in_array($arStatus, ['correction_requested','returned_to_applicant','officer_rejected'], true) => 'Officer',
                  $arStatus === 'registrar_rejected' => 'Registrar',
                  $arStatus === 'payment_rejected' => 'Accounts',
                  default => 'ZMC',
                };
              @endphp
              <tr>
                <td><span class="zd-ref">{{ $ar->reference }}</span></td>
                <td><span class="zd-tag">{{ $arType }}</span></td>
                <td>
                  <span class="zd-pill zd-pill-warn">{{ $statusLabel }}</span>
                  <div class="zd-muted zd-xs mt-1">by {{ $stage }}</div>
                </td>
                <td class="zd-muted zd-xs">{{ $ar->updated_at?->diffForHumans() ?? '—' }}</td>
                <td>
                  <div class="zd-actions">
                    <button type="button" class="zd-btn zd-btn-soft zd-btn-xs js-view-more" data-app-id="{{ $ar->id }}">
                      <i class="ri-eye-line"></i> View details
                    </button>
                    <a href="{{ route('accreditation.applications.edit', $ar) }}" class="zd-btn zd-btn-gold zd-btn-xs">
                      <i class="ri-edit-2-line"></i> Edit &amp; Resubmit
                    </a>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  {{-- ========================= REMINDERS ========================= --}}
  @if(isset($reminders) && $reminders->count())
    <div class="mb-4">
      @foreach($reminders as $reminder)
        <div class="zd-reminder" id="reminder-{{ $reminder->id }}">
          <div class="zd-reminder-icon"><i class="ri-alarm-warning-line"></i></div>

          <div class="flex-grow-1">
            <div class="zd-reminder-title">{{ ucfirst(str_replace('_', ' ', $reminder->reminder_type)) }}</div>
            <div class="zd-reminder-msg">{{ $reminder->message }}</div>
            <div class="zd-muted zd-xs mt-1">
              From: {{ $reminder->creator?->name ?? 'ZMC' }} &bull; {{ $reminder->created_at?->diffForHumans() }}
            </div>
          </div>

          <form method="POST" action="{{ route('accreditation.reminders.acknowledge', $reminder->id) }}">
            @csrf
            <button type="submit" class="zd-btn zd-btn-soft zd-btn-xs">
              <i class="ri-check-line"></i> Acknowledge
            </button>
          </form>
        </div>
      @endforeach
    </div>
  @endif

  {{-- =========================== STATS =========================== --}}
  <div class="zd-stats">
    <div class="zd-stat">
      <div class="zd-stat-top">
        <span class="zd-stat-label">Drafts</span>
        <span class="zd-stat-icon is-slate"><i class="ri-draft-line"></i></span>
      </div>
      <div class="zd-stat-value">{{ $stats['drafts'] ?? 0 }}</div>
      <div class="zd-stat-foot">Drafts expire after 14 days</div>
    </div>

    <div class="zd-stat">
      <div class="zd-stat-top">
        <span class="zd-stat-label">Active</span>
        <span class="zd-stat-icon is-info"><i class="ri-folders-line"></i></span>
      </div>
      <div class="zd-stat-value">{{ $stats['active'] ?? 0 }}</div>
      <div class="zd-stat-foot">Applications currently in progress</div>
    </div>

    <div class="zd-stat">
      <div class="zd-stat-top">
        <span class="zd-stat-label">Approved</span>
        <span class="zd-stat-icon is-ok"><i class="ri-verified-badge-line"></i></span>
      </div>
      <div class="zd-stat-value">{{ $stats['approved'] ?? 0 }}</div>
      <div class="zd-stat-foot">Successfully accredited</div>
    </div>

    <div class="zd-stat">
      <div class="zd-stat-top">
        <span class="zd-stat-label">Pending review</span>
        <span class="zd-stat-icon is-gold"><i class="ri-time-line"></i></span>
      </div>
      <div class="zd-stat-value">{{ $stats['pending'] ?? 0 }}</div>
      <div class="zd-stat-foot">Awaiting officer or registrar action</div>
    </div>
  </div>

  {{-- ===================== NOTICES & EVENTS ===================== --}}
  <div class="zd-grid-2">
    <div class="zd-card">
      <div class="zd-card-head">
        <h2 class="zd-card-title"><i class="ri-megaphone-line"></i> Notices</h2>
        <a href="{{ route('accreditation.notices') }}" class="zd-link">View all</a>
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
          <div class="zd-empty py-4">
            <div class="zd-empty-icon"><i class="ri-megaphone-line"></i></div>
            <h3>No notices yet</h3>
            <p>Announcements from ZMC will appear here.</p>
          </div>
        @endforelse
      </div>
    </div>

    <div class="zd-card">
      <div class="zd-card-head">
        <h4 class="zd-card-title"><i class="ri-calendar-event-line"></i> Events</h4>
        <a href="{{ route('accreditation.notices') }}" class="zd-link">View all</a>
      </div>

      <div class="zd-list">
        @forelse(($events ?? collect())->take(4) as $e)
          <div class="zd-list-item">
            <div class="zd-date-chip">
              <span class="d">{{ optional($e->starts_at)->format('d') ?? '--' }}</span>
              <span class="m">{{ optional($e->starts_at)->format('M') ?? 'TBA' }}</span>
            </div>
            <div>
              <div class="zd-list-title">{{ $e->title }}</div>
              <div class="zd-list-meta">
                {{ optional($e->starts_at)->format('d M Y') ?? 'Date to be announced' }}
                {{ $e->location ? (' • ' . $e->location) : '' }}
              </div>
            </div>
          </div>
        @empty
          <div class="zd-empty py-4">
            <div class="zd-empty-icon"><i class="ri-calendar-event-line"></i></div>
            <h5>No events yet</h5>
            <p>Upcoming ZMC events will be listed here.</p>
          </div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- ==================== RECENT APPLICATIONS ==================== --}}
  <div class="zd-card mb-4">
    <div class="zd-card-head">
      <h6 class="zd-card-title"><i class="ri-history-line"></i> Recent Applications</h6>
      @if($apps->count() > 5)
        <span class="zd-muted zd-xs">Showing latest 5</span>
      @endif
    </div>

    @if($apps->count())
      <div class="zd-table-wrap">
        <table class="zd-table">
          <thead>
            <tr>
              <th><i class="ri-hashtag"></i>Reference</th>
              <th><i class="ri-file-text-line"></i>Type</th>
              <th><i class="ri-calendar-line"></i>Submitted</th>
              <th><i class="ri-flag-line"></i>Status</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($apps->take(5) as $app)
              @php
                $status = strtolower((string)($app->status ?? ''));
                $tone   = $toneFor($status, (bool)($app->is_draft));
                $typeLabel = $app->request_type === 'new'
                  ? 'New Accreditation'
                  : ($app->request_type === 'renewal' ? 'Renewal' : 'Replacement');
                $submittedDate = $app->submitted_at?->format('d M Y H:i') ?? ($app->created_at?->format('d M Y H:i') ?? '—');
              @endphp

              <tr>
                <td><span class="zd-ref">{{ $app->reference ?? ('APP-' . $app->id) }}</span></td>
                <td><span class="zd-tag">{{ $typeLabel }}</span></td>
                <td class="zd-muted zd-xs">{{ $submittedDate }}</td>
                <td>
                  <span class="zd-pill zd-pill-{{ $tone }}">
                    {{ ucwords(str_replace('_',' ', $status ?: 'processing')) }}
                  </span>
                </td>
                <td>
                  <div class="zd-actions">
                    <button type="button" class="zd-icon-btn is-primary js-view-more" data-app-id="{{ $app->id }}" title="View Details">
                      <i class="ri-eye-line"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="zd-empty">
        <div class="zd-empty-icon"><i class="ri-inbox-line"></i></div>
        <h6>No recent applications yet</h6>
        <p>Start a new accreditation request to see it here.</p>
        <a href="{{ route('accreditation.new') }}" class="zd-btn zd-btn-ink zd-btn-xs mt-3">
          <i class="ri-file-add-line"></i> New Accreditation
        </a>
      </div>
    @endif
  </div>

  {{-- ==================== DRAFTS / ALL APPS ==================== --}}
  <div class="zd-card">
    <div class="zd-card-head flex-wrap gap-3">
      <ul class="nav zd-tabs mb-0" id="appTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="drafts-tab" data-bs-toggle="tab" data-bs-target="#drafts-pane" type="button" role="tab">
            <i class="ri-draft-line"></i> Drafts
            @if(isset($drafts) && $drafts->count())
              <span class="zd-tab-count">{{ $drafts->count() }}</span>
            @endif
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="all-apps-tab" data-bs-toggle="tab" data-bs-target="#all-apps-pane" type="button" role="tab">
            <i class="ri-list-check-2"></i> All Applications
          </button>
        </li>
      </ul>
    </div>

    <div class="tab-content">
      {{-- ---------------------- DRAFTS PANE ---------------------- --}}
      <div class="tab-pane fade show active" id="drafts-pane" role="tabpanel">
        @if(isset($drafts) && $drafts->count())
          <div class="zd-table-wrap">
            <table class="zd-table">
              <thead>
                <tr>
                  <th><i class="ri-hashtag"></i>Reference</th>
                  <th><i class="ri-file-text-line"></i>Type</th>
                  <th><i class="ri-time-line"></i>Last Updated</th>
                  <th><i class="ri-percent-line"></i>Progress</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($drafts as $draft)
                  @php
                    $draftType = $draft->request_type === 'new'
                      ? 'New Accreditation'
                      : ($draft->request_type === 'renewal' ? 'Renewal (AP5)' : 'Replacement (AP5)');

                    $formData = $draft->form_data ?? [];
                    if (is_string($formData)) $formData = json_decode($formData, true) ?: [];
                    $progressFields = array_keys($formData);
                    $requiredUploads = [];
                    if ($draft->application_type === 'accreditation' && $draft->request_type === 'new') {
                      $progressFields = [
                        'title','surname','first_name','dob','marital_status','gender','nationality','address',
                        'phone_country_code','phone','email','employment_type','medium_type','designation','declaration_date',
                        'referee_name_1','referee_address_1','referee_phone_1','referee_name_2','referee_address_2','referee_phone_2',
                        'referee_name_3','referee_address_3','referee_phone_3',
                      ];
                      if (($draft->journalist_scope ?? 'local') === 'local') {
                        $progressFields = array_merge($progressFields, ['province','national_reg_no','collection_region']);
                        $requiredUploads[] = 'id_scan';
                        $requiredUploads[] = (($formData['employment_type'] ?? '') === 'employed') ? 'employment_letter' : 'reference_letter';
                      } else {
                        $progressFields = array_merge($progressFields, ['passport_no','passport_expiry','passport_issued_at','first_time_in_zim','address_in_zimbabwe','arrived_on','arrival_mode','port_of_entry','departing_on','special_assignment']);
                        $requiredUploads = ['passport_biodata_page','clearance_letter'];
                      }
                      $requiredUploads[] = 'passport_photo';
                    }
                    $completedFields = collect($progressFields)->filter(fn($key) => !empty($formData[$key]) && $formData[$key] !== 'N/A')->count();
                    $documentTypes = $draft->documents->where('is_current', true)->pluck('doc_type');
                    $completedUploads = collect($requiredUploads)->filter(function ($type) use ($documentTypes) {
                      return $documentTypes->contains($type);
                    })->count();
                    $progress = min(100, (int) round((($completedFields + $completedUploads) / max(1, count($progressFields) + count($requiredUploads))) * 100));

                    $barColor = $progress >= 75 ? '#059669' : ($progress >= 40 ? '#D4A017' : '#94A3B8');

                    $continueRoute = $draft->request_type === 'new'
                      ? route('accreditation.new', ['draft' => $draft->reference])
                      : ($draft->request_type === 'replacement'
                        ? route('accreditation.replacement', ['draft' => $draft->reference])
                        : route('accreditation.renewal', ['draft' => $draft->reference]));
                  @endphp
                  <tr>
                    <td><span class="zd-ref">{{ $draft->reference }}</span></td>
                    <td><span class="zd-tag">{{ $draftType }}</span></td>
                    <td class="zd-muted zd-xs">{{ $draft->updated_at?->diffForHumans() ?? '—' }}</td>
                    <td style="min-width:150px;">
                      <div class="d-flex align-items-center gap-2">
                        <div class="zd-progress">
                          <span style="width:{{ $progress }}%; background:{{ $barColor }};"></span>
                        </div>
                        <span class="zd-muted zd-xs">{{ $progress }}%</span>
                      </div>
                    </td>
                    <td>
                      <div class="zd-actions">
                        <a class="zd-btn zd-btn-soft zd-btn-xs" href="{{ $continueRoute }}" title="Continue Editing">
                          <i class="ri-edit-line"></i> Continue
                        </a>
                        <button type="button" class="zd-icon-btn is-danger js-delete-draft" data-app-id="{{ $draft->id }}" title="Delete Draft">
                          <i class="ri-delete-bin-line"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="zd-empty">
            <div class="zd-empty-icon"><i class="ri-draft-line"></i></div>
            <h6>No drafts in progress</h6>
            <p>Start a new application and save as draft to see it here.</p>
            <a href="{{ route('accreditation.new') }}" class="zd-btn zd-btn-ink zd-btn-xs mt-3">
              <i class="ri-file-add-line"></i> New Accreditation
            </a>
          </div>
        @endif
      </div>

      {{-- --------------------- ALL APPS PANE --------------------- --}}
      <div class="tab-pane fade" id="all-apps-pane" role="tabpanel">
        <div class="zd-table-wrap">
          <table class="zd-table">
            <thead>
              <tr>
                <th><i class="ri-hashtag"></i>Reference</th>
                <th><i class="ri-file-text-line"></i>Type</th>
                <th><i class="ri-calendar-line"></i>Date</th>
                <th><i class="ri-flag-line"></i>Status</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($apps as $app)
                @php
                  $status = strtolower((string)($app->status ?? ''));
                  $tone   = $toneFor($status, (bool)($app->is_draft));

                  $date = $app->is_draft
                    ? 'Draft'
                    : (($app->submitted_at?->format('d M Y H:i')) ?? ($app->created_at?->format('d M Y H:i') ?? '—'));

                  $typeLabel = $app->request_type === 'new'
                    ? 'New Accreditation'
                    : ($app->request_type === 'renewal' ? 'Renewal' : 'Replacement');
                @endphp

                <tr>
                  <td><span class="zd-ref">{{ $app->reference ?? ('APP-' . $app->id) }}</span></td>
                  <td><span class="zd-tag">{{ $typeLabel }}</span></td>
                  <td class="zd-muted zd-xs">{{ $date }}</td>
                  <td>
                    <span class="zd-pill zd-pill-{{ $tone }}">
                      {{ ucwords(str_replace('_',' ', $status ?: ($app->is_draft ? 'draft' : 'processing'))) }}
                    </span>
                  </td>
                  <td>
                    <div class="zd-actions">
                      @if($app->is_draft)
                        <a class="zd-icon-btn" href="{{ route('accreditation.new', ['draft' => $app->reference]) }}" title="Continue">
                          <i class="ri-edit-line"></i>
                        </a>
                        <button type="button" class="zd-icon-btn is-danger js-delete-draft" data-app-id="{{ $app->id }}" title="Delete Draft">
                          <i class="ri-delete-bin-line"></i>
                        </button>
                      @else
                        <button type="button" class="zd-icon-btn is-primary js-view-more" data-app-id="{{ $app->id }}" title="View">
                          <i class="ri-eye-line"></i>
                        </button>

                        @if(in_array($status, ['correction_requested', 'returned_to_applicant', 'officer_rejected', 'registrar_rejected', 'payment_rejected'], true))
                          <a href="{{ route('accreditation.applications.edit', $app) }}" class="zd-btn zd-btn-gold zd-btn-xs" title="Edit & Resubmit">
                            <i class="ri-edit-2-line"></i> Edit &amp; Resubmit
                          </a>
                        @endif

                        @if($app->request_type === 'new' && in_array($status, ['submitted','officer_review','registrar_review','accounts_review','payment_verified','production_queue'], true))
                          <button type="button" class="zd-btn zd-btn-soft zd-btn-xs js-amendment-request" data-app-id="{{ $app->id }}" data-app-ref="{{ $app->reference }}" data-bs-toggle="modal" data-bs-target="#amendmentRequestModal">
                            <i class="ri-edit-2-line"></i> Request amendment
                          </button>
                        @endif

                        @php
                          $hasPendingPayment = $app->relationLoaded('paymentSubmissions')
                            ? $app->paymentSubmissions->contains(fn($submission) => in_array(strtolower((string) $submission->status), ['submitted', 'pending'], true))
                            : (($app->proof_status ?? null) === 'submitted');
                          $canPay = in_array($app->status, [
                            'accounts_review',
                            'approved_awaiting_payment',
                            'approved_by_officer_awaiting_payment_and_registrar_master',
                            'approved_by_accreditation_officer_awaiting_payment',
                            'approved_by_ao_awaiting_payment_and_registrar_master',
                            'submitted',
                            'awaiting_accounts_verification',
                            'payment_rejected'
                          ], true);
                          $canPay = $canPay && !$hasPendingPayment;
                        @endphp

                        @if($canPay)
                          <button type="button" class="zd-btn zd-btn-ink zd-btn-xs js-pay-now"
                                  data-app-id="{{ $app->id }}"
                                  data-app-ref="{{ $app->reference }}"
                                  data-accreditation-scope="{{ $app->journalist_scope ?? data_get($app->form_data, 'journalist_scope', 'local') }}"
                                  title="Pay Now">
                            <i class="ri-bank-card-line"></i> Pay
                          </button>
                        @elseif($hasPendingPayment)
                          <span class="zd-btn zd-btn-soft zd-btn-xs" title="Payment proof is awaiting Accounts verification">
                            <i class="ri-time-line"></i> Payment submitted
                          </span>
                        @endif

                        @if(in_array($status, ['submitted','officer_review','registrar_review','accounts_review']))
                          <button type="button" class="zd-icon-btn is-danger js-withdraw-app" data-app-id="{{ $app->id }}" title="Withdraw Application">
                            <i class="ri-arrow-go-back-line"></i>
                          </button>
                        @endif
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5">
                    <div class="zd-empty">
                      <div class="zd-empty-icon"><i class="ri-inbox-archive-line"></i></div>
                      <h6>No applications yet</h6>
                      <p>Once you submit an application it will appear here.</p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

</div>

<div class="modal fade" id="amendmentRequestModal" tabindex="-1" aria-labelledby="amendmentRequestTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="amendmentRequestForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="amendmentRequestTitle">Request an amendment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Application <strong id="amendmentApplicationRef"></strong> stays unchanged while ZMC reviews your request.</p>
          <label class="form-label fw-bold" for="amendmentReason">Explain the change you need and why</label>
          <textarea class="form-control" id="amendmentReason" name="reason" rows="4" minlength="{{ config('portal.limits.reason_min_length') }}" maxlength="{{ config('portal.limits.reason_max_length') }}" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Submit request</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ==================== GLOBAL DETAILS MODAL ==================== --}}
<div class="modal fade zmc-modal-pop" id="appDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header zmc-modal-header">
        <div>
          <div class="zmc-modal-title">
            <i class="fa-regular fa-file-lines me-2"></i>
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
        <button type="button" class="zd-btn zd-btn-soft" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@include('portal.partials.payment_modal')

{{-- =============== WELCOME / REQUIREMENTS MODAL =============== --}}
<div class="modal fade" id="welcomeRequirementsModal" tabindex="-1" aria-labelledby="practitionerWelcomeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content overflow-hidden border-0 shadow-lg" style="border-radius: 24px;">
      <div class="modal-body p-0">
        <div class="row g-0">

          <div class="col-md-4 d-none d-md-block position-relative" style="background: url('/zmc_building.png') center center / cover no-repeat;">
            <div style="position:absolute; inset:0; background: linear-gradient(150deg, rgba(11,15,23,.90), rgba(11,15,23,.96)), radial-gradient(60% 60% at 80% 15%, rgba(212,160,23,.55), transparent 65%); z-index:1;"></div>

            <div class="h-100 d-flex flex-column justify-content-center p-5 text-white position-relative" style="z-index:2;">
              <div style="width:74px;height:74px;border-radius:22px;display:flex;align-items:center;justify-content:center;background:rgba(212,160,23,.16);border:1px solid rgba(212,160,23,.38);margin-bottom:22px;">
                <i class="ri-user-star-line" style="font-size:38px; color:#F2C94C;"></i>
              </div>
              <h2 class="fw-black mb-3" style="color:#fff; font-size:26px; letter-spacing:-.02em;">Welcome, Practitioner!</h2>
              <p style="color:rgba(255,255,255,.72); font-size:13.5px; line-height:1.7;">
                Getting accredited is an important step in your media career. Ensure you have all
                necessary documents ready for a smooth application process.
              </p>
            </div>
          </div>

          <div class="col-md-8">
            <div class="p-4 p-md-5">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-black m-0" id="practitionerWelcomeModalLabel" style="color:#0B0F17; letter-spacing:-.02em;">
                  Media Practitioner Accreditation Requirements
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="row g-4 mb-4">
                <div class="col-md-6">
                  <div class="fw-bold mb-3" style="color:#0B0F17;"><i class="ri-user-line me-2" style="color:#D4A017;"></i>Local Applicants</div>
                  <ul class="list-unstyled small mb-0">
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>National ID</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>Media related qualifications or</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>5 samples of past work</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>One passport sized photo (white background)</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>Relevant attachments (reference letter, testimonial and citations)</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>Application fee</li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <div class="fw-bold mb-3" style="color:#0B0F17;"><i class="ri-global-line me-2" style="color:#D4A017;"></i>Foreign Applicants</div>
                  <ul class="list-unstyled small mb-0">
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>Valid passport</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>One passport size photo</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>Clearance letter</li>
                    <li class="mb-2" style="color:#334155;"><i class="ri-checkbox-circle-fill me-2" style="color:#D4A017;"></i>Application fee</li>
                  </ul>
                </div>
              </div>

              <div class="mb-4" style="background:#FDF7E7; border:1px solid #F3E3B8; border-left:4px solid #D4A017; border-radius:14px; padding:16px 18px;">
                <div class="d-flex align-items-start gap-2">
                  <i class="ri-information-line" style="color:#9A7208; font-size:1.15rem; margin-top:2px;"></i>
                  <div class="small" style="color:#4B5563; line-height:1.65;">
                    <strong style="color:#0B0F17;">Important:</strong> Please ensure all documents are clear, legible, and in an
                    accessible format (PDF, JPG, or PNG). Where required, provide certified copies of your national ID and
                    media qualifications. Applicants without formal media qualifications must submit professional reference
                    letters and samples of past work.
                  </div>
                </div>
              </div>

              <div class="d-grid">
                <button type="button" class="zd-btn zd-btn-ink justify-content-center py-3" data-bs-dismiss="modal" style="border-radius:999px; font-size:14px;">
                  I Understand, Let's Begin <i class="ri-arrow-right-line"></i>
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

@push('scripts')
<script>
  document.querySelectorAll('.js-amendment-request').forEach(button => {
    button.addEventListener('click', () => {
      const form = document.getElementById('amendmentRequestForm');
      form.action = `/portal/accreditation/applications/${button.dataset.appId}/amendment-request`;
      document.getElementById('amendmentApplicationRef').textContent = button.dataset.appRef || '';
      document.getElementById('amendmentReason').value = '';
    });
  });

  const ZMC_DETAILS_URL = @json($detailsUrlTemplate);

  function zmcFmt(v){
    if(v === null || v === undefined) return '—';
    const s = String(v).trim();
    return s === '' ? '—' : s;
  }

  function zmcEsc(v){
    return String(v ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
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
        meta.innerHTML = `<span class="badge me-2">${zmcFmt(formCode || 'Form')}</span>Ref: <span class="fw-bold">${zmcFmt(ref)}</span> • Status: <span class="fw-bold" style="color:var(--zmc-accent-dark)">${zmcFmt(status)}</span>`;
      }

      let html = '';

      const correctionComments = Array.isArray(data.correction_comments) ? data.correction_comments : [];
      if (correctionComments.length) {
        const correctionRows = correctionComments.map(comment => `
          <div class="alert alert-warning mb-2">
            <div class="fw-bold">${zmcEsc(String(comment.type || 'correction').replace(/_/g, ' '))}</div>
            <div class="mt-1">${zmcEsc(comment.body)}</div>
            <small class="text-muted">${zmcEsc(comment.author || 'ZMC')} · ${zmcEsc(comment.created_at || '')}</small>
          </div>
        `).join('');
        html += zmcBlock('<i class="ri-error-warning-line"></i> Requested corrections', correctionRows);
      }

      const fd = data.form_data || {};
      const labels = data.labels || {};
      const exclude = ['current_step', 'registration_scope', 'journalist_scope', 'directors', 'managers', 'directors_rows', 'managers_rows', 'ap1'];

      let fieldsHtml = '';
      for (const [key, val] of Object.entries(fd)) {
        if (exclude.includes(key) || !val) continue;
        const label = labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        if (typeof val === 'object' && val !== null) {
          fieldsHtml += zmcTextarea(label, JSON.stringify(val, null, 2));
        } else {
          fieldsHtml += zmcInput(label, val);
        }
      }

      if (fieldsHtml) {
        html += zmcBlock(`<i class="fa-regular fa-id-card"></i> Application details`, `<div class="row g-3">${fieldsHtml}</div>`);
      }

      const docs = Array.isArray(data.documents) ? data.documents : [];
      let docRows = docs.length ? '' : `<tr><td colspan="3" class="text-muted text-center">—</td></tr>`;
      docs.forEach(doc => {
        const open = doc.url
          ? `<a href="${doc.url}" target="_blank" class="zd-icon-btn is-primary" title="Open document"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>`
          : `<span class="text-muted">—</span>`;
        docRows += `<tr><td class="fw-bold">${zmcFmt(doc.document_type)}</td><td>${zmcFmt(doc.original_name || doc.file_name)}</td><td class="text-end">${open}</td></tr>`;
      });

      html += zmcBlock(`<i class="fa-regular fa-folder-open"></i> Attachments`, `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Type</th><th>File</th><th class="text-end">Open</th></tr></thead><tbody>${docRows}</tbody></table></div>`);

      const prevApps = Array.isArray(data.previous_applications) ? data.previous_applications : [];
      if (prevApps.length > 0) {
        let prevRows = prevApps.map(pa => `
          <tr>
            <td>${zmcFmt(pa.reference)}</td>
            <td class="text-capitalize">${zmcFmt(pa.type)}</td>
            <td><span class="zd-tag">${zmcFmt(pa.status)}</span></td>
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
            <td><span class="zd-tag text-capitalize">${zmcFmt(p.status)}</span></td>
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
          const res = await fetch(`/portal/accreditation/applications/${appId}`, {
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
          const res = await fetch(`/portal/accreditation/applications/${appId}/withdraw`, {
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
    });

    // Show welcome modal on every page load
    const modalEl = document.getElementById('welcomeRequirementsModal');
    if (modalEl) {
      setTimeout(() => {
        const m = bootstrap.Modal.getOrCreateInstance(modalEl);
        m.show();
      }, 800);
    }
  });

  // Function to manually show requirements modal
  function showRequirementsModal() {
    const modalEl = document.getElementById('welcomeRequirementsModal');
    if (modalEl) {
      const m = bootstrap.Modal.getOrCreateInstance(modalEl);
      m.show();
    }
  }
</script>
@endpush

@endsection
