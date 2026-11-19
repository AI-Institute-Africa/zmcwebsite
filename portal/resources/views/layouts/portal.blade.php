<!doctype html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}" data-bs-theme="{{ auth()->user()?->theme ?? 'light' }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title') | {{ (request()->is('staff/*') || request()->routeIs('staff.*')) ? 'ZMC Staff Portal' : 'ZMC Applicant' }}</title>
  
  {{-- Bootstrap 5 (required for dropdowns/modals in dashboards) --}}
  <link href="{{ asset('vendor/zmc/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
  <script defer src="{{ asset('vendor/zmc/bootstrap/bootstrap.bundle.min.js') }}"></script>

  {{-- Chart Libraries for Director Media Development and Governance Dashboard --}}
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="{{ asset('js/director-dashboard-charts.js') }}"></script>

  {{-- Fonts & Icons --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Montserrat:wght@400;500;600;700;800;900&display=swap"
    rel="stylesheet">
  <link href="{{ asset('vendor/zmc/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@latest/css/all.min.css" rel="stylesheet">

  {{-- Green Theme CSS --}}
  <link href="{{ asset('css/green-theme.css') }}" rel="stylesheet">

  @if(request()->is('staff/*') || request()->routeIs('staff.*'))
  <style>
    /* Shared overview design for every staff dashboard page. */
    .staff-page-surface .zmc-dashboard-wrapper { max-width: 1480px; margin: 0 auto; }
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child {
      position: relative; overflow: hidden; padding: 24px 28px; margin-bottom: 24px !important;
      border-radius: 24px; color: #fff;
      background: linear-gradient(120deg, #0e1b2b 0%, #173b3d 55%, #1f6f57 100%);
      box-shadow: 0 18px 40px rgba(15,35,48,.16);
    }
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child::after {
      content: ''; position: absolute; width: 220px; height: 220px; right: -55px; top: -95px;
      border-radius: 50%; background: rgba(242,201,76,.18); pointer-events: none;
    }
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child > * { position: relative; z-index: 1; }
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child h4,
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child h3 { color: #fff !important; font-size: clamp(1.65rem,2.6vw,2.3rem) !important; line-height: 1.12; }
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child .text-muted { color: rgba(255,255,255,.76) !important; }
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child .zmc-pill-dark { background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22); color: #fff; }
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child .btn-white,
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child .btn-outline-primary,
    .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child .btn-outline-secondary { background: rgba(255,255,255,.94); border-color: transparent; color: #173b3d; }
    .staff-page-surface .zmc-card, .staff-page-surface .card { border: 1px solid #e8edf1; border-radius: 22px !important; overflow: hidden; background: #fff; box-shadow: 0 10px 24px rgba(15,35,48,.07); }
    .staff-page-surface .zmc-card { padding: 20px; }
    .staff-page-surface .zmc-card:hover, .staff-page-surface .card:hover { box-shadow: 0 16px 32px rgba(15,35,48,.11); }
    .staff-page-surface .zmc-card .icon-box, .staff-page-surface .acct-kpi-icon { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 13px; background: #eef7f2; color: #247b5a; font-size: 20px; }
    .staff-page-surface .table thead th { background: #f5f8f7; color: #48605d; border-bottom: 1px solid #e4ebe8; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
    .staff-page-surface .table tbody tr { border-color: #edf1f0; }
    .staff-page-surface .table tbody tr:hover { background: #fbfdfc; }
    .staff-page-surface .btn { border-radius: 10px; }
    .staff-page-surface .nav-tabs, .staff-page-surface .nav-pills { gap: 6px; border-bottom-color: #e2ebe7; }
    .staff-page-surface .nav-tabs .nav-link, .staff-page-surface .nav-pills .nav-link { border-radius: 10px; color: #48605d; font-weight: 700; }
    .staff-page-surface .nav-tabs .nav-link.active, .staff-page-surface .nav-pills .nav-link.active { background: #247b5a; border-color: #247b5a; color: #fff; }
    .staff-page-surface .form-control, .staff-page-surface .form-select { border-radius: 10px; border-color: #dbe6e1; }
    @media (max-width: 768px) { .staff-page-surface .zmc-dashboard-wrapper > .d-flex:first-child { padding: 20px; border-radius: 18px; } }
  </style>
  @endif

  {{-- Base theme variables + global dashboard CSS --}}
  <style>
    :root {
      /* Light mode base */
      --c-bg: #f7f9fc;
      --c-card: #ffffff;
      --c-border: #e7ecf3;
      --c-text: #1e293b;
      --c-muted: #64748b;

      /* Accents */
      --c-primary: #FBBF24;
      --c-success: #10B981;
      --c-danger: #EF4444;
      --c-info: #3B82F6;
      --c-slate: #64748B;

      /* Legacy variables mapped to new ones */
      --accent: var(--c-primary);
      --accent-dark: #eab308;
      --bg: var(--c-bg);
      --card: var(--c-card);
      --border: var(--c-border);
      --text: var(--c-text);
      --muted: var(--c-muted);
      
      --sidebar: #000000;
      --sidebar-2: #1a1a1a;
      --sidebar-text: #ffffff;
      --shadow: 0 14px 40px rgba(0, 0, 0, .08);
      --radius: 14px;
      
      --zmc-accent: var(--c-primary);
      --zmc-accent-dark: #eab308;
      --zmc-border: var(--c-border);
      --zmc-soft: var(--c-bg);
      --zmc-text: var(--c-text);
      --zmc-muted: var(--c-muted);
    }

    [data-bs-theme="dark"] {
      /* Dark mode base */
      --c-bg: #0F172A;
      --c-card: #1E293B;
      --c-border: #334155;
      --c-text: #F8FAFC;
      --c-muted: #94A3B8;

      /* Adjust accents for dark mode slightly if needed */
      --c-primary: #FBBF24;
      --c-success: #059669;
      --c-danger: #DC2626;
      --c-info: #2563EB;
      --c-slate: #475569;

      /* Map Bootstrap variables to our dark mode */
      --bs-body-bg: var(--c-bg);
      --bs-body-color: var(--c-text);
      --bs-border-color: var(--c-border);
      --bs-card-bg: var(--c-card);
      --bs-card-border-color: var(--c-border);
      --bs-secondary-bg: var(--c-border);
      --bs-tertiary-bg: var(--c-card);
      --bs-modal-bg: var(--c-card);
      --bs-dropdown-bg: var(--c-card);
      --bs-dropdown-link-color: var(--c-text);
      --bs-dropdown-link-hover-bg: var(--c-border);
    }

    /* ====== Layout (sidebar + main + topbar) ====== */
    html,
    body {
      height: 100%;
      margin: 0;
    }

    .app {
      display: flex;
      min-height: 100vh;
      background: var(--bg);
    }

    .main {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      background: var(--bg);
    }

    .content {
      padding: 18px 18px 40px;
      flex: 1;
      background: var(--bg);
    }

    /* Typography Utilities - Now leveraging variables from green-theme.css */
    .text-xs {
      font-size: var(--font-size-xs) !important;
    }

    .text-sm {
      font-size: var(--font-size-sm) !important;
    }

    .text-base {
      font-size: var(--font-size-base) !important;
    }

    .text-lg {
      font-size: var(--font-size-lg) !important;
    }

    .text-xl {
      font-size: var(--font-size-xl) !important;
    }

    .text-2xl {
      font-size: var(--font-size-2xl) !important;
    }

    .fw-normal {
      font-weight: var(--font-weight-normal) !important;
    }

    .fw-medium {
      font-weight: var(--font-weight-medium) !important;
    }

    .fw-semibold {
      font-weight: var(--font-weight-semibold) !important;
    }

    .fw-bold {
      font-weight: var(--font-weight-bold) !important;
    }

    .fw-black {
      font-weight: var(--font-weight-black) !important;
    }

    /* Sidebar */
    .vertical-menu {
      width: 270px;
      background: url('/zmc_building.png') center center / cover no-repeat;
      color: var(--sidebar-text);
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      height: 100%;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      z-index: 1000;
    }

    .vertical-menu::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.72), rgba(0, 0, 0, 0.78));
      z-index: 0;
      pointer-events: none;
    }

    .vertical-menu>* {
      position: relative;
      z-index: 1;
    }

    .main {
      margin-left: 270px;
    }

    .navbar-brand-box {
      padding: 24px 16px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, .08);
      justify-content: center;
    }

    .navbar-brand-box img {
      width: 78px;
      height: 78px;
      object-fit: contain;
      object-position: center center;
      background: #ffffff;
      border-radius: 50%;
      padding: 6px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      transition: transform 0.3s ease;
      display: block;
      margin: 0 auto;
    }

    .navbar-brand-box img:hover {
      transform: scale(1.05);
    }

    .logo-portal-name {
      color: #ffffff;
      font-size: 11px;
      font-weight: 700;
      text-align: center;
      margin-top: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      line-height: 1.2;
    }

    .logo-text {
      display: block;
      font-family: var(--font-heading, 'Montserrat', 'Inter', sans-serif);
      font-weight: var(--font-weight-black);
      font-size: 12px;
      letter-spacing: .3px;
      text-align: right;
      line-height: var(--line-height-tight);
    }

    .logo-text span.zimbabwe {
      color: #fff;
    }

    .logo-text span.media {
      color: #00682d;
    }

    .logo-text span.commission {
      color: #facc15;
    }

    .logo-sub {
      display: block;
      font-family: var(--font-primary);
      font-size: var(--font-size-xs);
      opacity: .75;
      margin-top: 2px;
      text-align: right;
    }

    .sidebar-menu {
      list-style: none;
      padding: 12px;
      margin: 0;
      flex: 1;
      overflow-y: auto;
    }

    .sidebar-menu li a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 12px;
      color: rgba(255, 255, 255, .86);
      text-decoration: none;
      font-family: var(--font-primary);
      font-weight: var(--font-weight-bold);
      font-size: 10px;
      transition: all 0.2s ease;
    }

    .sidebar-menu li a i {
      font-size: 16px;
    }

    .sidebar-menu li.active a,
    .sidebar-menu li a:hover {
      background: rgba(250, 204, 21, 0.15);
      color: #facc15;
      border-left: 3px solid #facc15;
    }

    .sidebar-menu li a:hover {
      background: rgba(250, 204, 21, 0.08);
    }

    .sidebar-user {
      padding: 10px 14px;
      border-top: 1px solid rgba(255, 255, 255, .08);
      display: flex;
      gap: 10px;
      align-items: center;
      margin-top: auto;
      background: rgba(0, 0, 0, .10);
    }

    .sidebar-user img {
      width: 32px;
      height: 32px;
      border-radius: 999px;
    }

    /* Collapsed sidebar */
    .app.sidebar-collapsed .vertical-menu {
      width: 78px;
    }

    .app.sidebar-collapsed .main {
      margin-left: 78px;
    }

    .app.sidebar-collapsed .logo-sub,
    .app.sidebar-collapsed .logo-text {
      display: none;
    }

    .app.sidebar-collapsed .sidebar-menu li a span {
      display: none;
    }

    .app.sidebar-collapsed .sidebar-user>div {
      display: none;
    }

    /* Mobile sidebar */
    @media (max-width:991px) {
      .vertical-menu {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }

      .app.sidebar-open .vertical-menu {
        transform: translateX(0);
      }

      .main {
        margin-left: 0 !important;
      }

      .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
      }

      .app.sidebar-open .sidebar-overlay {
        display: block;
      }
    }

    @media (min-width:992px) {
      .mobile-menu-btn {
        display: none !important;
      }

      .sidebar-overlay {
        display: none !important;
      }
    }

    /* Topbar */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 1030;
      background: #fff;
      border-bottom: 1px solid var(--border);
    }

    .topbar-row--main {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 14px;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .topbar-toggle {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .topbar-toggle i {
      font-size: 20px;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .icon-btn {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .icon-btn i {
      font-size: 18px;
      color: #0f172a;
    }

    .icon-badge {
      position: absolute;
      top: 4px;
      right: 4px;
      font-size: 9px;
      padding: 2px 5px;
      min-width: 18px;
      border-radius: 999px;
      color: #fff;
      font-weight: 900;
      text-align: center;
      line-height: 1.2;
    }

    .badge-blue {
      background: #2563eb;
    }

    .badge-red {
      background: #ef4444;
    }

    .hide-caret::after {
      display: none !important;
    }

    .logout-pill {
      height: 34px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: #111827;
      color: #fff;
      font-weight: 900;
      font-size: 9px;
      padding: 0 12px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    /* ====== ZMC dashboard components (cards, tables, modals) ====== */
    .zmc-card,
    .modal-content {
      border-radius: 0 !important;
    }

    .form-control,
    .btn:not(.zmc-icon-btn) {
      border-radius: 8px;
      font-family: var(--font-primary);
      font-size: 11px;
    }

    .dropdown-item {
      font-size: 11px !important;
    }

    .zmc-card {
      background: #ffffff !important;
      border: 1px solid var(--zmc-border);
      padding: 18px;
      transition: .2s;
    }

    .icon-box {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .zmc-mini-table thead th {
      background: var(--zmc-soft);
      color: #475569;
      font-family: var(--font-primary);
      font-size: var(--font-size-xs);
      font-weight: var(--font-weight-black);
      padding: 12px 12px;
      border-bottom: 2px solid #edf2f7;
      white-space: nowrap;
    }

    .zmc-mini-table tbody td {
      padding: 12px 12px;
      border-bottom: 1px solid #f1f5f9;
      font-family: var(--font-primary);
      font-size: var(--font-size-sm);
      font-weight: 400;
      vertical-align: middle;
      white-space: nowrap;
    }
    .zmc-mini-table .badge {
      font-weight: 400 !important;
    }

    .zmc-pill {
      padding: 5px 12px;
      font-family: var(--font-primary);
      font-size: var(--font-size-sm);
      font-weight: var(--font-weight-bold);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid var(--zmc-border);
      background: #fff;
      white-space: nowrap;
      border-radius: 999px;
    }

    .zmc-pill-dark {
      background: #111827;
      color: #fff;
      border-color: #111827;
    }

    .zmc-action-strip {
      display: flex;
      gap: 3px;
      justify-content: flex-end;
    }

    .zmc-icon-btn {
      width: 32px;
      height: 32px;
      padding: 0;
      border-radius: 999px !important;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-width: 1px;
      background: #fff;
      line-height: 1;
    }

    .zmc-icon-btn i {
      font-size: 15px;
      line-height: 1;
    }

    .zmc-icon-btn:hover {
      border-color: var(--zmc-accent-dark) !important;
      color: var(--zmc-accent-dark) !important;
    }

    .zmc-modal-header {
      position: relative;
      background: #ffffff !important;
      border-bottom: 1px solid rgba(15, 23, 42, .10) !important;
      padding: 14px 16px;
    }

    .zmc-modal-header::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: var(--zmc-accent-dark);
    }

    .zmc-modal-title {
      font-family: var(--font-primary);
      font-weight: var(--font-weight-black);
      color: var(--zmc-text);
      font-size: var(--font-size-lg);
      line-height: var(--line-height-tight);
    }

    .zmc-modal-sub {
      font-family: var(--font-primary);
      font-size: var(--font-size-sm);
      font-weight: var(--font-weight-bold);
      color: var(--zmc-muted);
      margin-top: 2px;
    }

    .zmc-modal-footer {
      border-top: 1px solid rgba(15, 23, 42, .08) !important;
    }

    .zmc-lbl {
      font-family: var(--font-primary);
      font-size: var(--font-size-xs);
      font-weight: var(--font-weight-black);
      color: #64748b;
      margin-bottom: 6px;
    }

    .zmc-input {
      font-family: var(--font-primary);
      font-size: var(--font-size-sm) !important;
      padding: 7px 10px !important;
      line-height: 1.25 !important;
    }

    .zmc-input[readonly] {
      background: #fff !important;
    }

    .zmc-mdl-block {
      padding: 0;
      margin-bottom: 16px;
    }

    .zmc-mdl-title {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: var(--font-primary);
      font-size: var(--font-size-sm);
      font-weight: var(--font-weight-black);
      color: var(--zmc-text);
      padding: 10px 0 10px;
      border-bottom: 1px solid rgba(15, 23, 42, .10);
      margin-bottom: 12px;
    }

    .zmc-mdl-title i {
      color: var(--zmc-accent-dark);
    }

    .zmc-table-lite thead th {
      background: rgba(248, 250, 252, .95);
      font-family: var(--font-primary);
      font-size: var(--font-size-xs);
      font-weight: var(--font-weight-bold);
      padding: 10px 10px;
      border-bottom: 2px solid rgba(15, 23, 42, .08) !important;
      border-top: none !important;
    }

    .zmc-table-lite tbody td {
      font-family: var(--font-primary);
      font-size: var(--font-size-sm);
      padding: 10px 10px;
      border-top: none !important;
      border-bottom: 1px solid rgba(15, 23, 42, .06) !important;
      vertical-align: middle;
    }

    .zmc-table-lite {
      margin-bottom: 0;
    }


    /* ====== Applicant Forms ====== */
    .form-container {
      background: #fff;
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      border-radius: 16px;
      overflow: hidden;
    }

    .form-header {
      padding: 18px 18px;
      background: linear-gradient(180deg, rgba(245, 197, 24, .1), rgba(255, 255, 255, 0));
      border-bottom: 1px solid rgba(0, 0, 0, .12);
    }

    .form-header h1 {
      font-size: 16px;
      font-weight: 900;
      margin: 0 0 6px;
      color: #000;
    }

    .form-header p {
      margin: 0;
      font-size: 12px;
      color: #666;
      font-weight: 700;
    }

    .form-steps-container {
      padding: 16px 18px 18px;
    }

    .step-progress {
      margin-bottom: 14px;
    }

    .step-progress-bar {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .step {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border: 1px solid #e0e0e0;
      border-radius: 14px;
      background: #fff;
      cursor: pointer;
      user-select: none;
      transition: all 0.2s ease;
    }

    .step .step-number {
      width: 28px;
      height: 28px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      background: rgba(245, 197, 24, .15);
      color: #1a1a1a;
    }

    .step .step-label {
      font-size: 12px;
      font-weight: 900;
      color: #424242;
    }

    .step.active {
      border-color: rgba(245, 197, 24, .5);
      box-shadow: 0 10px 25px rgba(245, 197, 24, .15);
      background: rgba(245, 197, 24, .05);
    }

    .step.active .step-number {
      background: rgba(245, 197, 24, .85);
      color: #1a1a1a;
    }

    .step-content {
      display: none;
    }

    .step-content.active {
      display: block;
    }

    .step-title {
      font-size: 14px;
      font-weight: 900;
      color: #000;
      margin: 6px 0 10px;
    }

    .current-step-info {
      font-size: 12px;
      font-weight: 700;
      color: #666;
      padding: 10px 12px;
      border: 1px dashed rgba(0, 0, 0, .2);
      background: rgba(245, 197, 24, .08);
      border-radius: 14px;
      margin-bottom: 12px;
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
      margin-bottom: 12px;
    }

    .form-row .form-field {
      min-width: 0;
    }

    @media (max-width:768px) {
      .form-row {
        grid-template-columns: 1fr;
      }

      .step {
        flex: 1 1 auto;
      }

      .app-type-cards {
        grid-template-columns: 1fr;
      }

      .form-container {
        padding: 10px;
      }

      .form-header {
        padding: 15px;
      }

      .form-header h1 {
        font-size: 16px;
      }

      .form-steps-container {
        padding: 10px;
      }

      .step-progress-bar {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        gap: 4px;
      }

      .step-label {
        font-size: 9px;
      }

      .step-number {
        width: 24px;
        height: 24px;
        font-size: 11px;
      }

      .d-flex.justify-content-between.align-items-center.mb-4 {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 10px;
      }

      .form-buttons {
        flex-wrap: wrap;
        gap: 8px;
      }

      .form-buttons .btn {
        flex: 1 1 auto;
        min-width: 100px;
      }

      .modal-dialog {
        margin: 10px;
      }

      .table-responsive {
        font-size: 13px;
      }

      .upload-area {
        padding: 15px;
      }

      .upload-area h5 {
        font-size: 14px;
      }
    }

    @media (max-width:480px) {
      .form-header h1 {
        font-size: 14px;
      }

      .step-progress-bar {
        gap: 2px;
      }

      .step-label {
        display: none;
      }

      .checkbox-group {
        flex-direction: column;
      }
    }

    .form-label {
      font-size: 11px;
      font-weight: 900;
      color: #475569;
    }

    .form-label.required::after {
      content: ' *';
      color: #ef4444;
      font-weight: 900;
    }

    .checkbox-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .checkbox-item {
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px 12px;
      background: #fff;
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .app-type-cards {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .app-type-card {
      border: 1px solid #e0e0e0;
      border-radius: 16px;
      padding: 14px;
      background: #fff;
      cursor: pointer;
      transition: .15s;
    }

    .app-type-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 25px rgba(245, 197, 24, .1);
      border-color: rgba(245, 197, 24, .3);
    }

    .app-type-card.selected {
      border-color: rgba(245, 197, 24, .6);
      box-shadow: 0 10px 25px rgba(0, 0, 0, .12);
      background: rgba(245, 197, 24, .05);
    }

    .app-type-card i {
      font-size: 22px;
      color: #000;
    }

    .app-type-card h4 {
      font-size: 13px;
      font-weight: 900;
      margin: 8px 0 6px;
      color: #000;
    }

    .app-type-card p {
      font-size: 12px;
      color: #666;
      font-weight: 700;
      margin: 0;
    }

    .form-buttons {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      margin-top: 14px;
    }

    .upload-area {
      border: 1px dashed rgba(15, 23, 42, .18);
      border-radius: 16px;
      padding: 14px;
      background: rgba(248, 250, 252, .8);
      text-align: left;
    }

    .upload-area h5 {
      font-size: 12px;
      font-weight: 900;
      margin: 8px 0 3px;
    }

    .upload-area p {
      font-size: 11px;
      color: var(--muted);
      font-weight: 700;
      margin: 0 0 8px;
    }

    .upload-btn {
      border: 1px solid var(--border);
      background: #fff;
      border-radius: 12px;
      padding: 8px 10px;
      font-size: 12px;
      font-weight: 900;
    }

    /* Structured registration forms (AP1, AP2 and AP4). */
    #new-registration-page .form-container,
    #ap2-registration-page .form-container,
    #ap4-registration-page .form-container {
      --registration-ink: #17202b;
      --registration-muted: #657386;
      --registration-line: #e5eaf0;
      --registration-gold: #f2c94c;
      margin: 18px auto 32px;
      max-width: 1120px;
      overflow: hidden;
      border: 1px solid var(--registration-line);
      border-radius: 22px;
      background: #fff;
      box-shadow: 0 18px 55px rgba(15, 23, 42, .08);
    }

    #new-registration-page > .d-flex h4,
    #ap2-registration-page > .d-flex h4,
    #ap4-registration-page > .d-flex h4 {
      color: #293647 !important;
      font-size: 16px !important;
      font-weight: 800 !important;
      letter-spacing: -.015em;
    }

    #ap2-registration-page > .d-flex .btn,
    #ap4-registration-page > .d-flex .btn {
      padding: 8px 12px;
      border-radius: 10px;
      font-weight: 700;
    }

    #new-registration-page .form-header,
    #ap2-registration-page .form-header,
    #ap4-registration-page .form-header {
      position: relative;
      isolation: isolate;
      padding: 30px 34px 27px;
      color: #fff;
      border: 0;
      background: linear-gradient(118deg, #111820 0%, #202b37 72%, #293541 100%);
    }

    #new-registration-page .form-header::after,
    #ap2-registration-page .form-header::after,
    #ap4-registration-page .form-header::after {
      content: "";
      position: absolute;
      z-index: -1;
      top: -80px;
      right: -35px;
      width: 260px;
      height: 220px;
      border: 1px solid rgba(242, 201, 76, .24);
      border-radius: 50%;
      box-shadow: 0 0 0 24px rgba(242, 201, 76, .035), 0 0 0 50px rgba(242, 201, 76, .025);
      pointer-events: none;
    }

    #new-registration-page .form-header h1,
    #ap2-registration-page .form-header h1,
    #ap4-registration-page .form-header h1 {
      position: relative;
      max-width: 850px;
      margin: 0 0 9px;
      color: #fff;
      font-size: clamp(20px, 2.2vw, 27px);
      font-weight: 800;
      line-height: 1.25;
      letter-spacing: -.025em;
    }

    #new-registration-page .form-header p,
    #ap2-registration-page .form-header p,
    #ap4-registration-page .form-header p {
      position: relative;
      max-width: 66ch;
      color: rgba(255, 255, 255, .72);
      font-size: 13px;
      font-weight: 500;
      line-height: 1.65;
    }

    #new-registration-page .form-steps-container,
    #ap2-registration-page .form-steps-container,
    #ap4-registration-page .form-steps-container {
      padding: 26px 32px 32px;
    }

    #new-registration-page .step-progress,
    #ap2-registration-page .step-progress,
    #ap4-registration-page .step-progress {
      margin: -1px 0 28px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--registration-line);
    }

    #new-registration-page .step-progress-bar,
    #ap2-registration-page .step-progress-bar,
    #ap4-registration-page .step-progress-bar {
      display: flex;
      align-items: stretch;
      gap: 9px;
      flex-wrap: nowrap;
      overflow-x: auto;
      padding: 3px 2px 7px;
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }

    #new-registration-page .step,
    #ap2-registration-page .step,
    #ap4-registration-page .step {
      flex: 1 0 max-content;
      min-width: 145px;
      min-height: 54px;
      justify-content: flex-start;
      padding: 9px 13px;
      border: 1px solid #e6ebf1;
      border-radius: 13px;
      background: #fbfcfd;
      transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    #new-registration-page .step:hover,
    #ap2-registration-page .step:hover,
    #ap4-registration-page .step:hover {
      border-color: #d4dbe4;
      background: #fff;
      transform: translateY(-1px);
    }

    #new-registration-page .step .step-number,
    #ap2-registration-page .step .step-number,
    #ap4-registration-page .step .step-number {
      flex: 0 0 30px;
      width: 30px;
      height: 30px;
      margin: 0;
      border: 0;
      border-radius: 10px;
      background: #edf1f5;
      color: #526173;
      font-size: 12px;
      font-weight: 800;
    }

    #new-registration-page .step .step-label,
    #ap2-registration-page .step .step-label,
    #ap4-registration-page .step .step-label {
      color: #637083;
      font-size: 11px;
      font-weight: 700;
      line-height: 1.35;
      white-space: normal;
    }

    #new-registration-page .step.active,
    #ap2-registration-page .step.active,
    #ap4-registration-page .step.active {
      border-color: #e7c44e;
      background: #fffaf0;
      box-shadow: 0 5px 16px rgba(184, 142, 20, .1);
    }

    #new-registration-page .step.active .step-number,
    #ap2-registration-page .step.active .step-number,
    #ap4-registration-page .step.active .step-number {
      background: #f2c94c;
      color: #202833;
    }

    #new-registration-page .step.active .step-label,
    #ap2-registration-page .step.active .step-label,
    #ap4-registration-page .step.active .step-label {
      color: #263241;
    }

    #new-registration-page .step.completed .step-number,
    #ap2-registration-page .step.completed .step-number,
    #ap4-registration-page .step.completed .step-number {
      background: #e7f5ee;
      color: #16804a;
    }

    #new-registration-page .step-title,
    #ap2-registration-page .step-title,
    #ap4-registration-page .step-title {
      margin: 0 0 8px;
      padding: 0;
      border: 0;
      color: #1b2633;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -.02em;
    }

    #new-registration-page .current-step-info,
    #ap2-registration-page .current-step-info,
    #ap4-registration-page .current-step-info {
      display: flex;
      align-items: flex-start;
      gap: 9px;
      margin: 0 0 22px;
      padding: 12px 14px;
      border: 1px solid #f0e5bd;
      border-radius: 12px;
      background: #fffaf0;
      color: #665529;
      font-size: 12px;
      font-weight: 500;
      line-height: 1.55;
    }

    #new-registration-page .current-step-info i,
    #ap2-registration-page .current-step-info i,
    #ap4-registration-page .current-step-info i {
      flex: 0 0 auto;
      margin: 1px 0 0;
      color: #a98013;
    }

    #new-registration-page .section-card,
    #ap2-registration-page .section-card,
    #ap4-registration-page .section-card {
      margin-bottom: 16px;
      padding: 20px;
      border: 1px solid #e7ecf2;
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 4px 14px rgba(15, 23, 42, .025);
    }

    #new-registration-page .section-title,
    #ap2-registration-page .section-title,
    #ap4-registration-page .section-title {
      gap: 9px;
      margin: 0 0 18px;
      padding: 0 0 13px;
      border-bottom: 1px solid #edf0f4;
      color: #263241;
      font-size: 14px;
      font-weight: 800;
    }

    #new-registration-page .section-title i,
    #ap2-registration-page .section-title i,
    #ap4-registration-page .section-title i {
      display: inline-grid;
      width: 30px;
      height: 30px;
      place-items: center;
      border-radius: 9px;
      background: #fff6d9;
      color: #8a6b14;
      font-size: 16px;
    }

    #new-registration-page .section-subcard,
    #ap2-registration-page .section-subcard,
    #ap4-registration-page .section-subcard {
      padding: 16px;
      border: 1px solid #e8edf3;
      border-radius: 13px;
      background: #f8fafc;
    }

    #new-registration-page .subcard-title,
    #ap2-registration-page .subcard-title,
    #ap4-registration-page .subcard-title {
      gap: 8px;
      color: #425064;
      font-size: 12px;
    }

    #new-registration-page .form-row,
    #ap2-registration-page .form-row,
    #ap4-registration-page .form-row {
      gap: 16px;
      margin-bottom: 14px;
    }

    #new-registration-page .form-field,
    #ap2-registration-page .form-field,
    #ap4-registration-page .form-field {
      margin-bottom: 4px;
    }

    #new-registration-page .form-label,
    #ap2-registration-page .form-label,
    #ap4-registration-page .form-label {
      margin-bottom: 7px;
      color: #344154;
      font-size: 12px;
      font-weight: 700;
    }

    #new-registration-page .form-control,
    #new-registration-page .form-select,
    #ap2-registration-page .form-control,
    #ap2-registration-page .form-select,
    #ap4-registration-page .form-control,
    #ap4-registration-page .form-select {
      min-height: 44px;
      padding: 10px 12px;
      border: 1px solid #dce3eb;
      border-radius: 10px;
      background-color: #fff;
      color: #253244;
      font-size: 13px;
      box-shadow: 0 1px 2px rgba(15, 23, 42, .025);
      transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }

    #new-registration-page .form-control:focus,
    #new-registration-page .form-select:focus,
    #ap2-registration-page .form-control:focus,
    #ap2-registration-page .form-select:focus,
    #ap4-registration-page .form-control:focus,
    #ap4-registration-page .form-select:focus {
      border-color: #d7b53e;
      background-color: #fffef9;
      box-shadow: 0 0 0 3px rgba(242, 201, 76, .2);
      outline: 0;
    }

    #new-registration-page .form-hint,
    #ap2-registration-page .form-hint,
    #ap4-registration-page .form-hint {
      margin-top: 6px;
      color: #738095;
      font-size: 11px;
      line-height: 1.5;
    }

    #new-registration-page .upload-area,
    #ap2-registration-page .upload-area,
    #ap4-registration-page .upload-area {
      padding: 20px;
      border: 1px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      text-align: center;
      transition: border-color .18s ease, background-color .18s ease;
    }

    #new-registration-page .upload-area:hover,
    #ap2-registration-page .upload-area:hover,
    #ap4-registration-page .upload-area:hover {
      border-color: #d4b33d;
      background: #fffdf6;
    }

    #new-registration-page .upload-area > i,
    #ap2-registration-page .upload-area > i,
    #ap4-registration-page .upload-area > i {
      color: #9b7c1f;
    }

    #new-registration-page .app-type-card,
    #ap2-registration-page .app-type-card,
    #ap4-registration-page .app-type-card {
      padding: 18px;
      border: 1px solid #e5eaf0;
      border-radius: 15px;
      background: #fff;
      transition: border-color .18s ease, background-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    #new-registration-page .app-type-card:hover,
    #ap2-registration-page .app-type-card:hover,
    #ap4-registration-page .app-type-card:hover {
      border-color: #e2c34d;
      box-shadow: 0 10px 24px rgba(15, 23, 42, .07);
      transform: translateY(-2px);
    }

    #new-registration-page .app-type-card.selected,
    #ap2-registration-page .app-type-card.selected,
    #ap4-registration-page .app-type-card.selected {
      border-color: #d4b33d;
      background: #fffaf0;
      box-shadow: 0 0 0 3px rgba(242, 201, 76, .16);
    }

    #new-registration-page .app-type-card > i,
    #ap2-registration-page .app-type-card > i,
    #ap4-registration-page .app-type-card > i {
      color: #927216;
    }

    #new-registration-page .form-buttons .btn-primary,
    #ap2-registration-page .form-buttons .btn-primary,
    #ap4-registration-page .form-buttons .btn-primary {
      border-color: #e2bb33;
      background: #f2c94c;
      color: #202833;
      font-weight: 800;
    }

    #new-registration-page .form-buttons .btn-primary:hover,
    #ap2-registration-page .form-buttons .btn-primary:hover,
    #ap4-registration-page .form-buttons .btn-primary:hover {
      border-color: #d3ad2d;
      background: #e9bf38;
      color: #17202b;
    }

    #new-registration-page .form-buttons,
    #ap2-registration-page .form-buttons,
    #ap4-registration-page .form-buttons {
      margin-top: 24px;
      padding-top: 18px;
      border-top: 1px solid #edf0f4;
    }

    @media (max-width: 768px) {
      #new-registration-page .form-container,
      #ap2-registration-page .form-container,
      #ap4-registration-page .form-container {
        margin: 12px 0 24px;
        border-radius: 16px;
      }

      #new-registration-page .form-header,
      #ap2-registration-page .form-header,
      #ap4-registration-page .form-header {
        padding: 23px 20px;
      }

      #new-registration-page .form-steps-container,
      #ap2-registration-page .form-steps-container,
      #ap4-registration-page .form-steps-container {
        padding: 20px 16px 24px;
      }

      #new-registration-page .step,
      #ap2-registration-page .step,
      #ap4-registration-page .step {
        flex: 0 0 190px;
        min-width: 190px;
        padding: 8px 10px;
      }

      #new-registration-page .section-card,
      #ap2-registration-page .section-card,
      #ap4-registration-page .section-card {
        padding: 15px;
        border-radius: 13px;
      }
    }
  </style>



  <style>
    :root {
      --zmc-primary: #1a1a1a;
      --zmc-primary-dark: #111111;
      --zmc-accent: #facc15;
      --zmc-accent-dark: #eab308;
    }

    .btn-primary,
    .btn-success {
      background: var(--zmc-primary) !important;
      border-color: var(--zmc-accent) !important;
      color: var(--zmc-accent) !important;
    }

    .btn-primary:hover,
    .btn-success:hover {
      background: var(--zmc-accent) !important;
      border-color: var(--zmc-primary) !important;
      color: var(--zmc-primary) !important;
    }

    .badge.bg-success-subtle {
      background: rgba(250, 204, 21, .15) !important;
      color: #78350f !important;
    }

    .text-success {
      color: var(--zmc-accent-dark) !important;
    }

    .bg-success {
      background-color: var(--zmc-accent) !important;
    }

    .border-success {
      border-color: var(--zmc-accent) !important;
    }

    .bg-success-subtle {
      background-color: rgba(250, 204, 21, .15) !important;
    }

    .border-success-subtle {
      border-color: rgba(250, 204, 21, .3) !important;
    }

    /* Nav tabs global styling */
    .nav-tabs .nav-link.active {
      border-bottom: 3px solid var(--zmc-accent) !important;
      color: var(--zmc-primary) !important;
    }

    .nav-tabs .nav-link:hover:not(.active) {
      border-bottom: 3px solid var(--zmc-accent-dark);
    }

    /* Alert styling */
    .alert-success {
      background-color: rgba(250, 204, 21, .1) !important;
      border-color: rgba(250, 204, 21, .3) !important;
      color: #78350f !important;
    }

    .alert-warning {
      background-color: rgba(255, 152, 0, .1) !important;
      border-color: rgba(255, 152, 0, .3) !important;
      color: #e65100 !important;
    }

    body {
      background: url('/zmc_building.png') no-repeat center center fixed !important;
      background-size: cover !important;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: transparent;
      z-index: -1;
    }

    .topbar {
      background: url('/zmc_building.png') center center / cover no-repeat !important;
      border-bottom: none !important;
      position: relative;
    }

    .topbar::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(90deg, rgba(0, 0, 0, 0.72), rgba(0, 0, 0, 0.78));
      z-index: 0;
    }

    .topbar>* {
      position: relative;
      z-index: 1;
    }

    .topbar .icon-btn {
      background: rgba(255, 255, 255, .1) !important;
      border-color: rgba(255, 255, 255, .2) !important;
    }

    .topbar .icon-btn i {
      color: #fff !important;
    }

    .topbar .logout-pill {
      background: rgba(255, 255, 255, .15) !important;
      border-color: rgba(255, 255, 255, .25) !important;
    }

    .topbar-toggle {
      background: rgba(255, 255, 255, .1) !important;
      border-color: rgba(255, 255, 255, .2) !important;
    }

    .topbar-toggle i {
      color: #fff !important;
    }

    /* ====== Dark Theme Overrides (Bootstrap 5.3 data-bs-theme) ====== */
    [data-bs-theme="dark"] {
      --bg: #0f172a;
      --card: #1e293b;
      --border: #334155;
      --text: #f1f5f9;
      --muted: #94a3b8;
      --zmc-soft: #1e293b;
      --zmc-text: #f1f5f9;
      --zmc-muted: #94a3b8;
      --zmc-border: #334155;
    }

    [data-bs-theme="dark"] body,
    [data-bs-theme="dark"] .app,
    [data-bs-theme="dark"] .main,
    [data-bs-theme="dark"] .content {
      background: #0f172a !important;
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .zmc-card,
    [data-bs-theme="dark"] .card {
      background: #1e293b !important;
      border-color: #334155 !important;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .card-header {
      background: rgba(255,255,255,0.04) !important;
      border-bottom-color: #334155 !important;
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .table {
      color: #f1f5f9 !important;
      border-color: #334155 !important;
    }

    [data-bs-theme="dark"] .table th,
    [data-bs-theme="dark"] .zmc-mini-table thead th,
    [data-bs-theme="dark"] .zmc-table-lite thead th {
      background: #0f172a !important;
      color: #94a3b8 !important;
      border-bottom-color: #334155 !important;
    }

    [data-bs-theme="dark"] .table td {
      color: #f1f5f9 !important;
      border-bottom-color: #1e293b !important;
    }

    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select,
    [data-bs-theme="dark"] .zmc-input {
      background: #0f172a !important;
      border-color: #334155 !important;
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .form-control:focus,
    [data-bs-theme="dark"] .form-select:focus {
      border-color: var(--zmc-accent) !important;
      box-shadow: 0 0 0 2px rgba(250, 204, 21, 0.1) !important;
    }

    [data-bs-theme="dark"] .form-label {
      color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .dropdown-menu {
      background: #1e293b !important;
      border: 1px solid #334155 !important;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4) !important;
    }

    [data-bs-theme="dark"] .dropdown-item {
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .dropdown-item:hover {
      background: #334155 !important;
    }

    [data-bs-theme="dark"] .dropdown-divider {
      border-top-color: #334155 !important;
    }

    [data-bs-theme="dark"] .modal-content {
      background: #1e293b !important;
      color: #f1f5f9 !important;
      border: 1px solid #334155 !important;
    }

    [data-bs-theme="dark"] .modal-header {
      border-bottom-color: #334155 !important;
      background: rgba(255,255,255,0.04) !important;
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .modal-footer {
      border-top-color: #334155 !important;
    }

    [data-bs-theme="dark"] .bg-light {
      background: #1e293b !important;
    }

    [data-bs-theme="dark"] .bg-white {
      background: #1e293b !important;
    }

    [data-bs-theme="dark"] .text-muted {
      color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .text-dark {
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .border-bottom,
    [data-bs-theme="dark"] .border-top,
    [data-bs-theme="dark"] .border {
      border-color: #334155 !important;
    }

    [data-bs-theme="dark"] .icon-btn,
    [data-bs-theme="dark"] .topbar-toggle {
      background: rgba(255,255,255,0.1) !important;
      border-color: rgba(255,255,255,0.2) !important;
    }

    [data-bs-theme="dark"] .icon-btn i,
    [data-bs-theme="dark"] .topbar-toggle i {
      color: #fff !important;
    }

    /* Fixes for stubborn components */
    [data-bs-theme="dark"] .pagination .page-link {
      background: #1e293b !important;
      border-color: #334155 !important;
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .pagination .page-item.active .page-link {
      background: var(--zmc-accent) !important;
      color: #000 !important;
    }

    [data-bs-theme="dark"] .alert-success {
      background: rgba(250, 204, 21, 0.05) !important;
      border-color: rgba(250, 204, 21, 0.2) !important;
      color: #facc15 !important;
    }

    [data-bs-theme="dark"] .btn-outline-secondary {
      color: #f1f5f9 !important;
      border-color: #334155 !important;
    }

    [data-bs-theme="dark"] .btn-outline-secondary:hover {
      background: #334155 !important;
    }

    [data-bs-theme="dark"] .list-group-item {
      background: #1e293b !important;
      border-color: #334155 !important;
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .nav-tabs .nav-link {
      color: #94a3b8 !important;
    }

    [data-bs-theme="dark"] .nav-tabs .nav-link.active {
      background: rgba(255,255,255,0.05) !important;
      color: #f1f5f9 !important;
      border-color: #334155 #334155 #1e293b !important;
    }

    [data-bs-theme="dark"] .zmc-card h4,
    [data-bs-theme="dark"] .zmc-card h5,
    [data-bs-theme="dark"] .zmc-card h6 {
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .dashboard-card .h3,
    [data-bs-theme="dark"] .dashboard-card .h4,
    [data-bs-theme="dark"] .dashboard-card .h5,
    [data-bs-theme="dark"] .dashboard-card .fw-black,
    [data-bs-theme="dark"] .dashboard-card .fw-bold,
    [data-bs-theme="dark"] .zmc-card .h3,
    [data-bs-theme="dark"] .zmc-card .h4,
    [data-bs-theme="dark"] .zmc-card .h5,
    [data-bs-theme="dark"] .zmc-card .fw-black,
    [data-bs-theme="dark"] .zmc-card .fw-bold {
      color: #f1f5f9 !important;
    }

    [data-bs-theme="dark"] .progress {
      background-color: #334155 !important;
    }

    [data-bs-theme="dark"] .step {
      border-color: #334155 !important;
      background: #1e293b !important;
    }

    [data-bs-theme="dark"] .step .step-label,
    [data-bs-theme="dark"] .step .step-number {
      color: #94a3b8 !important;
    }
  </style>

  {{-- Global Pagination Styles - Hide large arrows, keep text buttons --}}
  <style>
    /* FORCE HIDE all SVG arrows in pagination with maximum specificity */
    .pagination svg,
    nav[role="navigation"] svg,
    .page-link svg,
    .page-item svg,
    nav svg {
      display: none !important;
      visibility: hidden !important;
      width: 0 !important;
      height: 0 !important;
      opacity: 0 !important;
    }

    /* Hide any arrow-like elements */
    .pagination .page-link::before,
    .pagination .page-link::after {
      display: none !important;
    }

    /* Style pagination to show only Previous/Next text */
    .pagination .page-link {
      border-radius: 6px !important;
      margin: 0 2px !important;
      font-size: 13px !important;
      font-weight: 500 !important;
      padding: 6px 12px !important;
      color: var(--zmc-text) !important;
      border: 1px solid var(--zmc-border) !important;
    }

    .pagination .page-link:hover {
      background-color: var(--zmc-soft) !important;
      border-color: var(--zmc-accent) !important;
      color: var(--zmc-accent-dark) !important;
    }

    .pagination .page-item.active .page-link {
      background-color: var(--zmc-accent) !important;
      border-color: var(--zmc-accent) !important;
      color: #fff !important;
      font-weight: 600 !important;
    }

    .pagination .page-item.disabled .page-link {
      opacity: 0.5 !important;
      cursor: not-allowed !important;
    }

    /* Ensure Previous/Next buttons show text only */
    nav[role="navigation"] .page-link {
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
    }

    /* Additional specificity for stubborn arrows */
    nav[aria-label="Pagination Navigation"] svg,
    .pagination-wrapper svg,
    [role="navigation"] [aria-label*="Previous"] svg,
    [role="navigation"] [aria-label*="Next"] svg {
      display: none !important;
    }
  </style>

  @if($formBuilderPreview ?? false)
    <style>
      body.form-builder-readonly-preview .main { margin-left: 0 !important; width: 100%; }
      body.form-builder-readonly-preview .content { padding: 8px !important; }
      body.form-builder-readonly-preview a { pointer-events: none; }
    </style>
  @endif
<style>
  .zmc-pilot-notice{position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:3000;width:min(760px,calc(100% - 24px));display:flex;align-items:center;gap:10px;padding:10px 16px;border:1px solid rgba(250,204,21,.55);border-radius:12px;background:rgba(22,30,35,.96);box-shadow:0 8px 24px rgba(0,0,0,.22);color:#fff;font:600 12px/1.4 Inter,sans-serif}.zmc-pilot-notice i{color:#facc15;font-size:18px;flex:0 0 auto}.zmc-pilot-notice strong{color:#facc15}@media(max-width:600px){.zmc-pilot-notice{top:8px;padding:8px 11px;font-size:11px}}
</style>
<link rel="stylesheet" href="{{ asset('css/zmc-ux.css') }}?v={{ @filemtime(public_path('css/zmc-ux.css')) }}">
<style id="zmc-uiux-2026">
  /* UI/UX quick wins (2026-11-19): visible scrollbars, readable type, focus rings, tabs */
  :root { --font-size-xs: 11px; --font-size-sm: 12px; --font-size-base: 14px; }

  /* Scrollbars: thin, visible, consistent (page, sidebar, scrollable regions) */
  html { scrollbar-width: thin; scrollbar-color: #94a3b8 #e2e8f0; }
  html::-webkit-scrollbar, body *::-webkit-scrollbar { width: 10px; height: 10px; }
  html::-webkit-scrollbar-track, body *::-webkit-scrollbar-track { background: #e2e8f0; }
  html::-webkit-scrollbar-thumb, body *::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 8px; border: 2px solid #e2e8f0; }
  html::-webkit-scrollbar-thumb:hover, body *::-webkit-scrollbar-thumb:hover { background: #64748b; }
  .sidebar-menu { scrollbar-width: thin; scrollbar-color: #facc15 rgba(255,255,255,.15); padding-bottom: 28px;
    -webkit-mask-image: linear-gradient(to bottom, #000 calc(100% - 28px), transparent);
            mask-image: linear-gradient(to bottom, #000 calc(100% - 28px), transparent); }
  .sidebar-menu::-webkit-scrollbar { width: 8px; }
  .sidebar-menu::-webkit-scrollbar-track { background: rgba(255,255,255,.12); border-radius: 8px; border: 0; }
  .sidebar-menu::-webkit-scrollbar-thumb { background: #facc15; border-radius: 8px; border: 0; }
  .table-responsive { scrollbar-width: thin; }

  /* Readable sidebar and table text */
  .sidebar-menu li a { font-size: 13px !important; }
  .sidebar-menu li.menu-title { font-size: 11px !important; }
  .staff-page-surface .table thead th, .table thead th { font-size: 12px; }

  /* Keyboard focus is always visible */
  a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible,
  textarea:focus-visible, summary:focus-visible, [tabindex]:focus-visible {
    outline: 2px solid #facc15 !important; outline-offset: 2px !important; box-shadow: 0 0 0 4px rgba(26,26,26,.55) !important;
  }

  /* In-page tab bar (links or buttons) */
  .zmc-tabs { display: flex; gap: 4px; overflow-x: auto; margin: 0 0 16px; padding: 6px; background: #fff;
    border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 2px 8px rgba(15,35,48,.05); list-style: none; }
  .zmc-tabs a, .zmc-tabs button { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; padding: 9px 14px;
    border: 0; border-radius: 10px; background: transparent; color: #334155; font-size: 13px; font-weight: 600; text-decoration: none; }
  .zmc-tabs a:hover, .zmc-tabs button:hover { background: #f1f5f9; color: #0f172a; }
  .zmc-tabs a[aria-selected="true"], .zmc-tabs button[aria-selected="true"] { background: #1a1a1a; color: #facc15; }
  .zmc-tabs .zmc-tab-count { font-size: 11px; padding: 1px 7px; border-radius: 999px; background: rgba(100,116,139,.18); }
  .zmc-tabs [aria-selected="true"] .zmc-tab-count { background: rgba(250,204,21,.22); }

  /* Menu section titles (replaces repeated inline styles in sidebar_staff) */
  .sidebar-menu li.menu-title { padding: 10px 18px; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.7); }

  /* Colour semantics: success = green again (brand yellow stays for primary actions/accents) */
  :root { --zmc-success: #15803d; --zmc-success-dark: #166534; --zmc-success-soft: rgba(21,128,61,.12); }
  .btn-success { background: var(--zmc-success) !important; border-color: var(--zmc-success) !important; color: #fff !important; }
  .btn-success:hover, .btn-success:focus-visible { background: var(--zmc-success-dark) !important; border-color: var(--zmc-success-dark) !important; color: #fff !important; }
  .btn-outline-success { color: var(--zmc-success) !important; border-color: var(--zmc-success) !important; }
  .btn-outline-success:hover { background: var(--zmc-success) !important; color: #fff !important; }
  .text-success { color: var(--zmc-success) !important; }
  .bg-success { background-color: var(--zmc-success) !important; color: #fff; }
  .border-success { border-color: var(--zmc-success) !important; }
  .bg-success-subtle, .badge.bg-success-subtle { background-color: var(--zmc-success-soft) !important; color: var(--zmc-success-dark) !important; }
  .border-success-subtle { border-color: rgba(21,128,61,.3) !important; }
  /* accent yellow used as text on light backgrounds fails contrast: use a darker amber */
  .text-warning { color: #8a4b0a !important; }

  /* ===== LIGHT MODE CONTRAST FIXES (2026-11-19) ===== */
  .text-info { color: #0a6c8a !important; }
  .badge.bg-info, .btn-info, .bg-info:not(.bg-info-subtle) { color: #082f3a !important; }
  .pagination .page-item.active .page-link { color: #111 !important; }
  .alert-warning { color: #9a3412 !important; }

  /* ===== DARK MODE FIXES (2026-11-19) ===== */
  [data-bs-theme="dark"] { --dm-surface-2: #263449; --dm-text-strong: #f1f5f9; color-scheme: dark; }
  [data-bs-theme="dark"] body { background: var(--c-bg) !important; color: var(--c-text); }

  /* Inline light colours in views -> dark equivalents (generated, exact-terminated selectors) */
[data-bs-theme="dark"] [style*="background:#fff;"],
[data-bs-theme="dark"] [style*="background:#fff !important"],
[data-bs-theme="dark"] [style$="background:#fff"],
[data-bs-theme="dark"] [style*="background: #fff;"],
[data-bs-theme="dark"] [style*="background: #fff !important"],
[data-bs-theme="dark"] [style$="background: #fff"],
[data-bs-theme="dark"] [style*="background-color:#fff;"],
[data-bs-theme="dark"] [style*="background-color:#fff !important"],
[data-bs-theme="dark"] [style$="background-color:#fff"],
[data-bs-theme="dark"] [style*="background-color: #fff;"],
[data-bs-theme="dark"] [style*="background-color: #fff !important"],
[data-bs-theme="dark"] [style$="background-color: #fff"],
[data-bs-theme="dark"] [style*="background:#ffffff;"],
[data-bs-theme="dark"] [style*="background:#ffffff !important"],
[data-bs-theme="dark"] [style$="background:#ffffff"],
[data-bs-theme="dark"] [style*="background: #ffffff;"],
[data-bs-theme="dark"] [style*="background: #ffffff !important"],
[data-bs-theme="dark"] [style$="background: #ffffff"],
[data-bs-theme="dark"] [style*="background-color:#ffffff;"],
[data-bs-theme="dark"] [style*="background-color:#ffffff !important"],
[data-bs-theme="dark"] [style$="background-color:#ffffff"],
[data-bs-theme="dark"] [style*="background-color: #ffffff;"],
[data-bs-theme="dark"] [style*="background-color: #ffffff !important"],
[data-bs-theme="dark"] [style$="background-color: #ffffff"],
[data-bs-theme="dark"] [style*="background:white;"],
[data-bs-theme="dark"] [style*="background:white !important"],
[data-bs-theme="dark"] [style$="background:white"],
[data-bs-theme="dark"] [style*="background: white;"],
[data-bs-theme="dark"] [style*="background: white !important"],
[data-bs-theme="dark"] [style$="background: white"],
[data-bs-theme="dark"] [style*="background-color:white;"],
[data-bs-theme="dark"] [style*="background-color:white !important"],
[data-bs-theme="dark"] [style$="background-color:white"],
[data-bs-theme="dark"] [style*="background-color: white;"],
[data-bs-theme="dark"] [style*="background-color: white !important"],
[data-bs-theme="dark"] [style$="background-color: white"],
[data-bs-theme="dark"] [style*="background:#fafafa;"],
[data-bs-theme="dark"] [style*="background:#fafafa !important"],
[data-bs-theme="dark"] [style$="background:#fafafa"],
[data-bs-theme="dark"] [style*="background: #fafafa;"],
[data-bs-theme="dark"] [style*="background: #fafafa !important"],
[data-bs-theme="dark"] [style$="background: #fafafa"],
[data-bs-theme="dark"] [style*="background-color:#fafafa;"],
[data-bs-theme="dark"] [style*="background-color:#fafafa !important"],
[data-bs-theme="dark"] [style$="background-color:#fafafa"],
[data-bs-theme="dark"] [style*="background-color: #fafafa;"],
[data-bs-theme="dark"] [style*="background-color: #fafafa !important"],
[data-bs-theme="dark"] [style$="background-color: #fafafa"],
[data-bs-theme="dark"] [style*="background:#f9fafb;"],
[data-bs-theme="dark"] [style*="background:#f9fafb !important"],
[data-bs-theme="dark"] [style$="background:#f9fafb"],
[data-bs-theme="dark"] [style*="background: #f9fafb;"],
[data-bs-theme="dark"] [style*="background: #f9fafb !important"],
[data-bs-theme="dark"] [style$="background: #f9fafb"],
[data-bs-theme="dark"] [style*="background-color:#f9fafb;"],
[data-bs-theme="dark"] [style*="background-color:#f9fafb !important"],
[data-bs-theme="dark"] [style$="background-color:#f9fafb"],
[data-bs-theme="dark"] [style*="background-color: #f9fafb;"],
[data-bs-theme="dark"] [style*="background-color: #f9fafb !important"],
[data-bs-theme="dark"] [style$="background-color: #f9fafb"] {
  background-color: var(--c-card) !important; color: var(--c-text);
}
[data-bs-theme="dark"] [style*="background:#f8fafc;"],
[data-bs-theme="dark"] [style*="background:#f8fafc !important"],
[data-bs-theme="dark"] [style$="background:#f8fafc"],
[data-bs-theme="dark"] [style*="background: #f8fafc;"],
[data-bs-theme="dark"] [style*="background: #f8fafc !important"],
[data-bs-theme="dark"] [style$="background: #f8fafc"],
[data-bs-theme="dark"] [style*="background-color:#f8fafc;"],
[data-bs-theme="dark"] [style*="background-color:#f8fafc !important"],
[data-bs-theme="dark"] [style$="background-color:#f8fafc"],
[data-bs-theme="dark"] [style*="background-color: #f8fafc;"],
[data-bs-theme="dark"] [style*="background-color: #f8fafc !important"],
[data-bs-theme="dark"] [style$="background-color: #f8fafc"],
[data-bs-theme="dark"] [style*="background:#f1f5f9;"],
[data-bs-theme="dark"] [style*="background:#f1f5f9 !important"],
[data-bs-theme="dark"] [style$="background:#f1f5f9"],
[data-bs-theme="dark"] [style*="background: #f1f5f9;"],
[data-bs-theme="dark"] [style*="background: #f1f5f9 !important"],
[data-bs-theme="dark"] [style$="background: #f1f5f9"],
[data-bs-theme="dark"] [style*="background-color:#f1f5f9;"],
[data-bs-theme="dark"] [style*="background-color:#f1f5f9 !important"],
[data-bs-theme="dark"] [style$="background-color:#f1f5f9"],
[data-bs-theme="dark"] [style*="background-color: #f1f5f9;"],
[data-bs-theme="dark"] [style*="background-color: #f1f5f9 !important"],
[data-bs-theme="dark"] [style$="background-color: #f1f5f9"],
[data-bs-theme="dark"] [style*="background:#f3f4f6;"],
[data-bs-theme="dark"] [style*="background:#f3f4f6 !important"],
[data-bs-theme="dark"] [style$="background:#f3f4f6"],
[data-bs-theme="dark"] [style*="background: #f3f4f6;"],
[data-bs-theme="dark"] [style*="background: #f3f4f6 !important"],
[data-bs-theme="dark"] [style$="background: #f3f4f6"],
[data-bs-theme="dark"] [style*="background-color:#f3f4f6;"],
[data-bs-theme="dark"] [style*="background-color:#f3f4f6 !important"],
[data-bs-theme="dark"] [style$="background-color:#f3f4f6"],
[data-bs-theme="dark"] [style*="background-color: #f3f4f6;"],
[data-bs-theme="dark"] [style*="background-color: #f3f4f6 !important"],
[data-bs-theme="dark"] [style$="background-color: #f3f4f6"],
[data-bs-theme="dark"] [style*="background:#f7f9fc;"],
[data-bs-theme="dark"] [style*="background:#f7f9fc !important"],
[data-bs-theme="dark"] [style$="background:#f7f9fc"],
[data-bs-theme="dark"] [style*="background: #f7f9fc;"],
[data-bs-theme="dark"] [style*="background: #f7f9fc !important"],
[data-bs-theme="dark"] [style$="background: #f7f9fc"],
[data-bs-theme="dark"] [style*="background-color:#f7f9fc;"],
[data-bs-theme="dark"] [style*="background-color:#f7f9fc !important"],
[data-bs-theme="dark"] [style$="background-color:#f7f9fc"],
[data-bs-theme="dark"] [style*="background-color: #f7f9fc;"],
[data-bs-theme="dark"] [style*="background-color: #f7f9fc !important"],
[data-bs-theme="dark"] [style$="background-color: #f7f9fc"],
[data-bs-theme="dark"] [style*="background:#f5f8f7;"],
[data-bs-theme="dark"] [style*="background:#f5f8f7 !important"],
[data-bs-theme="dark"] [style$="background:#f5f8f7"],
[data-bs-theme="dark"] [style*="background: #f5f8f7;"],
[data-bs-theme="dark"] [style*="background: #f5f8f7 !important"],
[data-bs-theme="dark"] [style$="background: #f5f8f7"],
[data-bs-theme="dark"] [style*="background-color:#f5f8f7;"],
[data-bs-theme="dark"] [style*="background-color:#f5f8f7 !important"],
[data-bs-theme="dark"] [style$="background-color:#f5f8f7"],
[data-bs-theme="dark"] [style*="background-color: #f5f8f7;"],
[data-bs-theme="dark"] [style*="background-color: #f5f8f7 !important"],
[data-bs-theme="dark"] [style$="background-color: #f5f8f7"],
[data-bs-theme="dark"] [style*="background:#f4f6f8;"],
[data-bs-theme="dark"] [style*="background:#f4f6f8 !important"],
[data-bs-theme="dark"] [style$="background:#f4f6f8"],
[data-bs-theme="dark"] [style*="background: #f4f6f8;"],
[data-bs-theme="dark"] [style*="background: #f4f6f8 !important"],
[data-bs-theme="dark"] [style$="background: #f4f6f8"],
[data-bs-theme="dark"] [style*="background-color:#f4f6f8;"],
[data-bs-theme="dark"] [style*="background-color:#f4f6f8 !important"],
[data-bs-theme="dark"] [style$="background-color:#f4f6f8"],
[data-bs-theme="dark"] [style*="background-color: #f4f6f8;"],
[data-bs-theme="dark"] [style*="background-color: #f4f6f8 !important"],
[data-bs-theme="dark"] [style$="background-color: #f4f6f8"],
[data-bs-theme="dark"] [style*="background:#eef2f7;"],
[data-bs-theme="dark"] [style*="background:#eef2f7 !important"],
[data-bs-theme="dark"] [style$="background:#eef2f7"],
[data-bs-theme="dark"] [style*="background: #eef2f7;"],
[data-bs-theme="dark"] [style*="background: #eef2f7 !important"],
[data-bs-theme="dark"] [style$="background: #eef2f7"],
[data-bs-theme="dark"] [style*="background-color:#eef2f7;"],
[data-bs-theme="dark"] [style*="background-color:#eef2f7 !important"],
[data-bs-theme="dark"] [style$="background-color:#eef2f7"],
[data-bs-theme="dark"] [style*="background-color: #eef2f7;"],
[data-bs-theme="dark"] [style*="background-color: #eef2f7 !important"],
[data-bs-theme="dark"] [style$="background-color: #eef2f7"],
[data-bs-theme="dark"] [style*="background:#f8f9fa;"],
[data-bs-theme="dark"] [style*="background:#f8f9fa !important"],
[data-bs-theme="dark"] [style$="background:#f8f9fa"],
[data-bs-theme="dark"] [style*="background: #f8f9fa;"],
[data-bs-theme="dark"] [style*="background: #f8f9fa !important"],
[data-bs-theme="dark"] [style$="background: #f8f9fa"],
[data-bs-theme="dark"] [style*="background-color:#f8f9fa;"],
[data-bs-theme="dark"] [style*="background-color:#f8f9fa !important"],
[data-bs-theme="dark"] [style$="background-color:#f8f9fa"],
[data-bs-theme="dark"] [style*="background-color: #f8f9fa;"],
[data-bs-theme="dark"] [style*="background-color: #f8f9fa !important"],
[data-bs-theme="dark"] [style$="background-color: #f8f9fa"] {
  background-color: var(--dm-surface-2) !important; color: var(--c-text);
}
[data-bs-theme="dark"] [style*="color:#0f172a;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#0f172a !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#0f172a"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #0f172a;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #0f172a !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #0f172a"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#1e293b;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#1e293b !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#1e293b"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #1e293b;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #1e293b !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #1e293b"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#111827;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#111827 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#111827"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #111827;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #111827 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #111827"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#111;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#111 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#111"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #111;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #111 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #111"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#000;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#000 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#000"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #000;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #000 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #000"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#000000;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#000000 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#000000"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #000000;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #000000 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #000000"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#1a1a1a;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#1a1a1a !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#1a1a1a"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #1a1a1a;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #1a1a1a !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #1a1a1a"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#212529;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#212529 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#212529"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #212529;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #212529 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #212529"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#172b2b;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#172b2b !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#172b2b"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #172b2b;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #172b2b !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #172b2b"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#173b3d;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#173b3d !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#173b3d"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #173b3d;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #173b3d !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #173b3d"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]) {
  color: #f1f5f9 !important;
}
[data-bs-theme="dark"] [style*="color:#334155;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#334155 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#334155"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #334155;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #334155 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #334155"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#475569;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#475569 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#475569"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #475569;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #475569 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #475569"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#374151;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#374151 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#374151"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #374151;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #374151 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #374151"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#4b5563;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#4b5563 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#4b5563"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #4b5563;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #4b5563 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #4b5563"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#48605d;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#48605d !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#48605d"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #48605d;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #48605d !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #48605d"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]) {
  color: #cbd5e1 !important;
}
[data-bs-theme="dark"] [style*="color:#64748b;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#64748b !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#64748b"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #64748b;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #64748b !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #64748b"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#6b7280;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#6b7280 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#6b7280"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #6b7280;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #6b7280 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #6b7280"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#94a3b8;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color:#94a3b8 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color:#94a3b8"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #94a3b8;"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style*="color: #94a3b8 !important"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]),
[data-bs-theme="dark"] [style$="color: #94a3b8"]:not(.btn):not(.badge):not(.alert):not([class*="bg-warning"]):not([class*="bg-success"]):not([class*="bg-primary"]):not([class*="bg-danger"]):not([class*="bg-info"]):not([class*="bg-dark"]):not([style*="background"]) {
  color: var(--c-muted) !important;
}
[data-bs-theme="dark"] [style*="solid#e2e8f0;"],
[data-bs-theme="dark"] [style$="solid#e2e8f0"],
[data-bs-theme="dark"] [style*="border-color:#e2e8f0;"],
[data-bs-theme="dark"] [style*="border-color:#e2e8f0 "],
[data-bs-theme="dark"] [style*="solid #e2e8f0;"],
[data-bs-theme="dark"] [style$="solid #e2e8f0"],
[data-bs-theme="dark"] [style*="border-color: #e2e8f0;"],
[data-bs-theme="dark"] [style*="border-color: #e2e8f0 "],
[data-bs-theme="dark"] [style*="solid#e7ecf3;"],
[data-bs-theme="dark"] [style$="solid#e7ecf3"],
[data-bs-theme="dark"] [style*="border-color:#e7ecf3;"],
[data-bs-theme="dark"] [style*="border-color:#e7ecf3 "],
[data-bs-theme="dark"] [style*="solid #e7ecf3;"],
[data-bs-theme="dark"] [style$="solid #e7ecf3"],
[data-bs-theme="dark"] [style*="border-color: #e7ecf3;"],
[data-bs-theme="dark"] [style*="border-color: #e7ecf3 "],
[data-bs-theme="dark"] [style*="solid#e8edf1;"],
[data-bs-theme="dark"] [style$="solid#e8edf1"],
[data-bs-theme="dark"] [style*="border-color:#e8edf1;"],
[data-bs-theme="dark"] [style*="border-color:#e8edf1 "],
[data-bs-theme="dark"] [style*="solid #e8edf1;"],
[data-bs-theme="dark"] [style$="solid #e8edf1"],
[data-bs-theme="dark"] [style*="border-color: #e8edf1;"],
[data-bs-theme="dark"] [style*="border-color: #e8edf1 "],
[data-bs-theme="dark"] [style*="solid#e4ebe8;"],
[data-bs-theme="dark"] [style$="solid#e4ebe8"],
[data-bs-theme="dark"] [style*="border-color:#e4ebe8;"],
[data-bs-theme="dark"] [style*="border-color:#e4ebe8 "],
[data-bs-theme="dark"] [style*="solid #e4ebe8;"],
[data-bs-theme="dark"] [style$="solid #e4ebe8"],
[data-bs-theme="dark"] [style*="border-color: #e4ebe8;"],
[data-bs-theme="dark"] [style*="border-color: #e4ebe8 "],
[data-bs-theme="dark"] [style*="solid#e5e7eb;"],
[data-bs-theme="dark"] [style$="solid#e5e7eb"],
[data-bs-theme="dark"] [style*="border-color:#e5e7eb;"],
[data-bs-theme="dark"] [style*="border-color:#e5e7eb "],
[data-bs-theme="dark"] [style*="solid #e5e7eb;"],
[data-bs-theme="dark"] [style$="solid #e5e7eb"],
[data-bs-theme="dark"] [style*="border-color: #e5e7eb;"],
[data-bs-theme="dark"] [style*="border-color: #e5e7eb "],
[data-bs-theme="dark"] [style*="solid#eef2f7;"],
[data-bs-theme="dark"] [style$="solid#eef2f7"],
[data-bs-theme="dark"] [style*="border-color:#eef2f7;"],
[data-bs-theme="dark"] [style*="border-color:#eef2f7 "],
[data-bs-theme="dark"] [style*="solid #eef2f7;"],
[data-bs-theme="dark"] [style$="solid #eef2f7"],
[data-bs-theme="dark"] [style*="border-color: #eef2f7;"],
[data-bs-theme="dark"] [style*="border-color: #eef2f7 "],
[data-bs-theme="dark"] [style*="solid#dee2e6;"],
[data-bs-theme="dark"] [style$="solid#dee2e6"],
[data-bs-theme="dark"] [style*="border-color:#dee2e6;"],
[data-bs-theme="dark"] [style*="border-color:#dee2e6 "],
[data-bs-theme="dark"] [style*="solid #dee2e6;"],
[data-bs-theme="dark"] [style$="solid #dee2e6"],
[data-bs-theme="dark"] [style*="border-color: #dee2e6;"],
[data-bs-theme="dark"] [style*="border-color: #dee2e6 "],
[data-bs-theme="dark"] [style*="solid#cbd5e1;"],
[data-bs-theme="dark"] [style$="solid#cbd5e1"],
[data-bs-theme="dark"] [style*="border-color:#cbd5e1;"],
[data-bs-theme="dark"] [style*="border-color:#cbd5e1 "],
[data-bs-theme="dark"] [style*="solid #cbd5e1;"],
[data-bs-theme="dark"] [style$="solid #cbd5e1"],
[data-bs-theme="dark"] [style*="border-color: #cbd5e1;"],
[data-bs-theme="dark"] [style*="border-color: #cbd5e1 "] {
  border-color: var(--c-border) !important;
}

  /* Bootstrap components that had no dark override */
  [data-bs-theme="dark"] .table { --bs-table-color: var(--c-text); --bs-table-bg: transparent; --bs-table-border-color: var(--c-border);
    --bs-table-striped-bg: rgba(255,255,255,.04); --bs-table-hover-bg: rgba(250,204,21,.08); --bs-table-hover-color: var(--c-text); color: var(--c-text); }
  [data-bs-theme="dark"] .table-light, [data-bs-theme="dark"] .table > :not(caption) > * > .table-light,
  [data-bs-theme="dark"] thead.table-light th, [data-bs-theme="dark"] .table thead th {
    --bs-table-bg: var(--dm-surface-2); --bs-table-color: #e2e8f0; background-color: var(--dm-surface-2) !important; color: #e2e8f0 !important; border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .btn-light { background: var(--dm-surface-2) !important; border-color: var(--c-border) !important; color: var(--c-text) !important; }
  [data-bs-theme="dark"] .btn-light:hover { background: #324259 !important; }
  [data-bs-theme="dark"] .btn-outline-secondary, [data-bs-theme="dark"] .btn-outline-dark { color: #cbd5e1 !important; border-color: #475569 !important; }
  [data-bs-theme="dark"] .btn-outline-secondary:hover, [data-bs-theme="dark"] .btn-outline-dark:hover { background: #334155 !important; color: #fff !important; }
  [data-bs-theme="dark"] .btn-primary { border-color: #facc15 !important; }
  [data-bs-theme="dark"] .input-group-text { background: var(--dm-surface-2) !important; border-color: var(--c-border) !important; color: var(--c-muted) !important; }
  [data-bs-theme="dark"] .form-control::placeholder, [data-bs-theme="dark"] textarea::placeholder { color: #7c8aa0 !important; opacity: 1; }
  [data-bs-theme="dark"] .form-control:disabled, [data-bs-theme="dark"] .form-select:disabled, [data-bs-theme="dark"] .form-control[readonly] { background: #172033 !important; color: #94a3b8 !important; }
  [data-bs-theme="dark"] .form-check-input { background-color: var(--dm-surface-2); border-color: #64748b; }
  [data-bs-theme="dark"] .form-check-input:checked { background-color: #facc15; border-color: #facc15; }
  [data-bs-theme="dark"] .list-group { --bs-list-group-bg: var(--c-card); --bs-list-group-color: var(--c-text); --bs-list-group-border-color: var(--c-border); }
  [data-bs-theme="dark"] .list-group-item { background: var(--c-card) !important; color: var(--c-text) !important; border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .list-group-item-action:hover { background: var(--dm-surface-2) !important; }
  [data-bs-theme="dark"] .nav-tabs { border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .nav-tabs .nav-link, [data-bs-theme="dark"] .nav-pills .nav-link { color: #cbd5e1 !important; }
  [data-bs-theme="dark"] .nav-tabs .nav-link:hover { border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .nav-tabs .nav-link.active { background: var(--c-card) !important; color: #facc15 !important; border-color: var(--c-border) var(--c-border) var(--c-card) !important; }
  [data-bs-theme="dark"] .nav-pills .nav-link.active { background: #facc15 !important; color: #111 !important; }
  [data-bs-theme="dark"] .page-link { background: var(--c-card); border-color: var(--c-border); color: #cbd5e1; }
  [data-bs-theme="dark"] .pagination .page-item.active .page-link { background: #facc15 !important; border-color: #facc15 !important; color: #111 !important; }
  [data-bs-theme="dark"] .page-item.disabled .page-link { background: #172033; color: #64748b; }
  [data-bs-theme="dark"] .accordion { --bs-accordion-bg: var(--c-card); --bs-accordion-color: var(--c-text); --bs-accordion-border-color: var(--c-border);
    --bs-accordion-btn-bg: var(--c-card); --bs-accordion-btn-color: var(--c-text); --bs-accordion-active-bg: var(--dm-surface-2); --bs-accordion-active-color: #facc15; }
  [data-bs-theme="dark"] .breadcrumb-item, [data-bs-theme="dark"] .breadcrumb-item a { color: #cbd5e1; }
  [data-bs-theme="dark"] .breadcrumb-item.active { color: var(--c-muted); }
  [data-bs-theme="dark"] .progress { background: #334155; }
  [data-bs-theme="dark"] .popover, [data-bs-theme="dark"] .tooltip-inner { --bs-popover-bg: var(--dm-surface-2); --bs-popover-border-color: var(--c-border); --bs-popover-body-color: var(--c-text); --bs-popover-header-bg: #324259; --bs-popover-header-color: var(--c-text); }
  [data-bs-theme="dark"] hr { border-color: var(--c-border); opacity: 1; }
  [data-bs-theme="dark"] code { color: #fda4af; }
  [data-bs-theme="dark"] a:not(.btn):not(.nav-link):not(.dropdown-item):not(.page-link):not(.list-group-item):not([class*="menu"]) { --bs-link-color-rgb: 250, 204, 21; }
  [data-bs-theme="dark"] .bg-white, [data-bs-theme="dark"] .bg-body, [data-bs-theme="dark"] .bg-body-tertiary { background-color: var(--c-card) !important; color: var(--c-text); }
  [data-bs-theme="dark"] .bg-light, [data-bs-theme="dark"] .bg-body-secondary { background-color: var(--dm-surface-2) !important; color: var(--c-text); }
  [data-bs-theme="dark"] .text-body, [data-bs-theme="dark"] .text-black, [data-bs-theme="dark"] .text-dark, [data-bs-theme="dark"] .text-secondary { color: #e2e8f0 !important; }
  [data-bs-theme="dark"] .text-muted, [data-bs-theme="dark"] .text-body-secondary { color: var(--c-muted) !important; }
  [data-bs-theme="dark"] .border, [data-bs-theme="dark"] .border-light, [data-bs-theme="dark"] .border-bottom, [data-bs-theme="dark"] .border-top, [data-bs-theme="dark"] .border-end, [data-bs-theme="dark"] .border-start { border-color: var(--c-border) !important; }
  [data-bs-theme="dark"] .shadow-sm, [data-bs-theme="dark"] .shadow { box-shadow: 0 6px 20px rgba(0,0,0,.35) !important; }
  [data-bs-theme="dark"] .alert-success { --bs-alert-bg: rgba(34,197,94,.14); --bs-alert-color: #86efac; --bs-alert-border-color: rgba(34,197,94,.35); }
  [data-bs-theme="dark"] .alert-danger  { --bs-alert-bg: rgba(239,68,68,.14); --bs-alert-color: #fca5a5; --bs-alert-border-color: rgba(239,68,68,.35); }
  [data-bs-theme="dark"] .alert-warning { --bs-alert-bg: rgba(250,204,21,.13); --bs-alert-color: #fde68a; --bs-alert-border-color: rgba(250,204,21,.35);
    background-color: rgba(251,146,60,.14) !important; border-color: rgba(251,146,60,.4) !important; color: #fed7aa !important; }
  [data-bs-theme="dark"] .alert-danger { color: #fecaca !important; }
  [data-bs-theme="dark"] .alert-info    { --bs-alert-bg: rgba(59,130,246,.14); --bs-alert-color: #93c5fd; --bs-alert-border-color: rgba(59,130,246,.35); }
  [data-bs-theme="dark"] .bg-success-subtle { background-color: rgba(34,197,94,.16) !important; color: #86efac !important; }
  [data-bs-theme="dark"] .bg-danger-subtle  { background-color: rgba(239,68,68,.16) !important; color: #fca5a5 !important; }
  [data-bs-theme="dark"] .bg-warning-subtle { background-color: rgba(250,204,21,.15) !important; color: #fde68a !important; }
  [data-bs-theme="dark"] .bg-info-subtle, [data-bs-theme="dark"] .bg-primary-subtle { background-color: rgba(59,130,246,.16) !important; color: #93c5fd !important; }
  [data-bs-theme="dark"] .bg-secondary-subtle { background-color: rgba(148,163,184,.16) !important; color: #cbd5e1 !important; }
  [data-bs-theme="dark"] .badge.bg-success-subtle { color: #86efac !important; }
  [data-bs-theme="dark"] .text-success { color: #4ade80 !important; }
  [data-bs-theme="dark"] .text-danger  { color: #f87171 !important; }
  [data-bs-theme="dark"] .text-warning { color: #fbbf24 !important; }
  [data-bs-theme="dark"] .text-primary, [data-bs-theme="dark"] .text-info { color: #60a5fa !important; }
  [data-bs-theme="dark"] .btn-success { background: #15803d !important; border-color: #15803d !important; color: #fff !important; }
  [data-bs-theme="dark"] .btn-outline-success { color: #4ade80 !important; border-color: #4ade80 !important; }
  [data-bs-theme="dark"] .btn-outline-success:hover { background: #15803d !important; color: #fff !important; }
  [data-bs-theme="dark"] .bg-warning, [data-bs-theme="dark"] .btn-warning, [data-bs-theme="dark"] .bg-warning .text-dark,
  [data-bs-theme="dark"] .bg-warning.text-dark, [data-bs-theme="dark"] .bg-info.text-dark, [data-bs-theme="dark"] .badge.bg-info, [data-bs-theme="dark"] .btn-info { color: #111 !important; }
  [data-bs-theme="dark"] .btn-outline-primary { color: #93c5fd !important; border-color: #60a5fa !important; }
  [data-bs-theme="dark"] .btn-outline-primary:hover { background: #2563eb !important; color: #fff !important; }
  [data-bs-theme="dark"] .btn-outline-danger { color: #fca5a5 !important; border-color: #f87171 !important; }
  [data-bs-theme="dark"] .btn-outline-danger:hover { background: #dc2626 !important; color: #fff !important; }
  [data-bs-theme="dark"] .text-info { color: #67e8f9 !important; }
  [data-bs-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
  [data-bs-theme="dark"] .zmc-tabs { background: var(--c-card); border-color: var(--c-border); box-shadow: 0 2px 10px rgba(0,0,0,.3); }
  [data-bs-theme="dark"] .zmc-tabs a, [data-bs-theme="dark"] .zmc-tabs button { color: #cbd5e1; }
  [data-bs-theme="dark"] .zmc-tabs a:hover, [data-bs-theme="dark"] .zmc-tabs button:hover { background: var(--dm-surface-2); color: #fff; }
  [data-bs-theme="dark"] .zmc-tabs a[aria-selected="true"], [data-bs-theme="dark"] .zmc-tabs button[aria-selected="true"] { background: #facc15; color: #111; }
  [data-bs-theme="dark"] .zmc-tabs .zmc-tab-count { background: rgba(148,163,184,.25); }
  [data-bs-theme="dark"] html, html[data-bs-theme="dark"] { scrollbar-color: #475569 #0f172a; }
  html[data-bs-theme="dark"]::-webkit-scrollbar-track, [data-bs-theme="dark"] body *::-webkit-scrollbar-track { background: #0f172a; }
  html[data-bs-theme="dark"]::-webkit-scrollbar-thumb, [data-bs-theme="dark"] body *::-webkit-scrollbar-thumb { background: #475569; border-color: #0f172a; }
  html[data-bs-theme="dark"]::-webkit-scrollbar-thumb:hover, [data-bs-theme="dark"] body *::-webkit-scrollbar-thumb:hover { background: #64748b; }
  [data-bs-theme="dark"] .sidebar-menu::-webkit-scrollbar-track { background: rgba(255,255,255,.12); }
  [data-bs-theme="dark"] .sidebar-menu::-webkit-scrollbar-thumb { background: #facc15; }

  /* ===== MOTION (subtle; disabled by prefers-reduced-motion) ===== */
  @keyframes zmc-rise { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
  @keyframes zmc-fade { from { opacity: 0; } to { opacity: 1; } }
  @keyframes zmc-slide-in { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: none; } }
  @keyframes zmc-pop { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }

  .main > *:not(.topbar):not(.modal):not(.toast-container) { animation: zmc-rise .5s cubic-bezier(.2,.7,.2,1) both; }
  .main .row > [class*="col"] { animation: zmc-rise .5s cubic-bezier(.2,.7,.2,1) both; }
  .main .row > [class*="col"]:nth-child(1) { animation-delay: .04s; } .main .row > [class*="col"]:nth-child(2) { animation-delay: .09s; }
  .main .row > [class*="col"]:nth-child(3) { animation-delay: .14s; } .main .row > [class*="col"]:nth-child(4) { animation-delay: .19s; }
  .main .row > [class*="col"]:nth-child(5) { animation-delay: .24s; } .main .row > [class*="col"]:nth-child(n+6) { animation-delay: .29s; }
  .alert { animation: zmc-rise .4s ease-out both; }
  .dropdown-menu.show { animation: zmc-pop .16s ease-out; transform-origin: top; }
  .modal.fade .modal-dialog { transform: translateY(-14px) scale(.98); transition: transform .28s cubic-bezier(.2,.7,.2,1); }
  .modal.show .modal-dialog { transform: none; }
  .tab-pane.active.show, .tab-pane.fade.show { animation: zmc-fade .3s ease-out; }

  /* interactions */
  .sidebar-menu li a { transition: background-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease; }
  .sidebar-menu li:not(.menu-title) a:hover { transform: translateX(4px); }
  .sidebar-menu li.active a { box-shadow: inset 3px 0 0 #facc15; }
  .sidebar-menu li { animation: zmc-slide-in .4s cubic-bezier(.2,.7,.2,1) both; }
  .sidebar-menu li:nth-child(1) { animation-delay: .02s; } .sidebar-menu li:nth-child(2) { animation-delay: .05s; }
  .sidebar-menu li:nth-child(3) { animation-delay: .08s; } .sidebar-menu li:nth-child(4) { animation-delay: .11s; }
  .sidebar-menu li:nth-child(5) { animation-delay: .14s; } .sidebar-menu li:nth-child(6) { animation-delay: .17s; }
  .sidebar-menu li:nth-child(7) { animation-delay: .20s; } .sidebar-menu li:nth-child(8) { animation-delay: .23s; }
  .sidebar-menu li:nth-child(n+9) { animation-delay: .26s; }
  .main .row > [class*="col"] > .h-100 { transition: transform .22s ease, box-shadow .22s ease; }
  .main .row > [class*="col"] > .h-100:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(15,35,48,.14) !important; }
  [data-bs-theme="dark"] .main .row > [class*="col"] > .h-100:hover { box-shadow: 0 14px 30px rgba(0,0,0,.5) !important; }
  .btn { transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .12s ease, box-shadow .18s ease; }
  .btn:hover:not(:disabled) { transform: translateY(-1px); }
  .btn:active:not(:disabled) { transform: translateY(0) scale(.97); }
  .table tbody tr { transition: background-color .15s ease; }
  .zmc-tabs a, .zmc-tabs button { transition: background-color .22s ease, color .22s ease, transform .15s ease; }
  .zmc-tabs a:hover, .zmc-tabs button:hover { transform: translateY(-1px); }
  .zmc-tabs a[aria-selected="true"], .zmc-tabs button[aria-selected="true"] { animation: zmc-pop .25s ease-out; }
  .form-control, .form-select { transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease; }

  /* smooth light <-> dark switch (class added briefly by script when the theme attribute changes) */
  html.zmc-theme-anim, html.zmc-theme-anim *, html.zmc-theme-anim *::before, html.zmc-theme-anim *::after {
    transition: background-color .35s ease, color .35s ease, border-color .35s ease, box-shadow .35s ease, fill .35s ease !important; }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
  }
</style>
  @include('layouts.favicon')
</head>

@php
  $theme = auth()->check() ? (auth()->user()->theme ?? 'light') : 'light';
@endphp

<body class="{{ ($formBuilderPreview ?? false) ? 'form-builder-readonly-preview' : '' }}">
  <div class="zmc-pilot-notice" role="status" id="zmcPilotNotice"><i class="ri-information-line"></i><span>This portal is currently in a testing phase and is not yet accepting applications. In the meantime, please continue to submit applications at the Commission's head office or Bulawayo regional office. For further information or guidance, kindly contact the Commission.</span><button type="button" id="zmcNoticeClose" aria-label="Close" style="margin-left:auto;background:transparent;border:none;color:#facc15;cursor:pointer;font-size:22px;line-height:1;">&times;</button></div>
  <script>try{var n=document.getElementById("zmcPilotNotice");if(localStorage.getItem("zmc_pilot_notice_closed")=="1"&&n){n.style.display="none";}var b=document.getElementById("zmcNoticeClose");if(b){b.onclick=function(){try{localStorage.setItem("zmc_pilot_notice_closed","1");}catch(e){}if(n){n.style.display="none";}};}}catch(e){}</script>

  
  <div class="app">
    {{--
    ✅ Super Admin uses the STAFF theme/sidebar.
    So both /staff/* and /admin/* routes load the same staff sidebar.
    --}}
    @php
      $user = auth()->user();
      $isStaffRoute = request()->is('staff/*') || request()->routeIs('staff.*') || request()->is('admin/*') || request()->routeIs('admin.*');
      $showStaffSidebar = $isStaffRoute || ($user && ($user->hasRole('accreditation_officer') || $user->hasRole('accounts_payments') || $user->hasRole('registrar') || $user->hasRole('production') || $user->hasRole('auditor') || $user->hasRole('super_admin') || $user->hasRole('it_admin') || $user->hasRole('director')));
    @endphp

    @unless($formBuilderPreview ?? false)
      @if($showStaffSidebar)
        @include('layouts.sidebar_staff')
      @else
        @include('layouts.sidebar')
      @endif
      <div class="sidebar-overlay" id="sidebarOverlay"></div>
    @endunless
    <main class="main">
      @unless($formBuilderPreview ?? false)
        @include('layouts.topbar')
      @endunless

      <section class="content {{ $isStaffRoute ? 'staff-page-surface' : '' }}">
        @yield('content')
      </section>
    </main>
  </div>

  {{-- Page-specific scripts --}}
  @include('portal.partials.nid_validator')


  {{-- Sidebar toggle & scroll persistence --}}
  <script>
    // toggleZMCTheme is defined in topbar.blade.php (data-bs-theme on <html>)

    document.addEventListener('DOMContentLoaded', function () {
      // Smooth theme switch: add a short-lived class whenever data-bs-theme changes
      (function () {
        const root = document.documentElement; let t;
        new MutationObserver(function () {
          root.classList.add('zmc-theme-anim'); clearTimeout(t);
          t = setTimeout(function () { root.classList.remove('zmc-theme-anim'); }, 500);
        }).observe(root, { attributes: true, attributeFilter: ['data-bs-theme'] });
      })();
      // Accessibility: label the sidebar nav and mark the current page
      const sideNav = document.querySelector('.sidebar-menu');
      if (sideNav) {
        sideNav.setAttribute('role', 'navigation');
        sideNav.setAttribute('aria-label', 'Staff portal navigation');
        sideNav.querySelectorAll('li.active > a').forEach(a => a.setAttribute('aria-current', 'page'));
        sideNav.querySelectorAll('li > a > i').forEach(i => i.setAttribute('aria-hidden', 'true'));
      }
      // Sync theme icon on load based on data-bs-theme
      if (typeof toggleZMCTheme !== 'undefined') {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const icon = document.getElementById('themeIcon');
        if (icon) {
          icon.className = currentTheme === 'dark' ? 'ri-sun-line' : 'ri-moon-line';
        }
      }

      // Sidebar toggle handling (desktop: collapse, mobile: slide)
      const btn = document.getElementById('sidebarToggle');
      const overlay = document.getElementById('sidebarOverlay');
      if (btn) {
        btn.addEventListener('click', function () {
          const app = document.querySelector('.app');
          if (window.innerWidth < 992) {
            app?.classList.toggle('sidebar-open');
          } else {
            app?.classList.toggle('sidebar-collapsed');
          }
        });
      }
      if (overlay) {
        overlay.addEventListener('click', function () {
          document.querySelector('.app')?.classList.remove('sidebar-open');
        });
      }

      // Scroll persistence
      const sidebarMenu = document.querySelector('.sidebar-menu');
      if (sidebarMenu) {
        const savedScroll = sessionStorage.getItem('sidebar-scroll');
        if (savedScroll) {
          sidebarMenu.scrollTop = parseInt(savedScroll, 10);
        }
        sidebarMenu.addEventListener('scroll', function () {
          sessionStorage.setItem('sidebar-scroll', sidebarMenu.scrollTop);
        });
      }
    });
  </script>
  <script>window.ZMC_BADGES = @json($queue_badges ?? []);</script>
  <script defer src="{{ asset('js/zmc-ux.js') }}?v={{ @filemtime(public_path('js/zmc-ux.js')) }}"></script>
  @stack('scripts')
  @if($formBuilderPreview ?? false)
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const requestedStep = {{ (int) ($previewStep ?? 1) }};
        const stepPanels = [...document.querySelectorAll('.step-content[id*="-step-"]')];
        const activateStep = (step) => {
          const target = stepPanels.find((panel) => panel.id.endsWith(`-step-${step}`));
          if (!target) return;
          stepPanels.forEach((panel) => panel.classList.toggle('active', panel === target));
          document.querySelectorAll('.step[data-step], .af-step[data-step]').forEach((item) => {
            item.classList.toggle('active', Number(item.dataset.step) === Number(step));
          });
        };

        window.setTimeout(() => activateStep(requestedStep), 0);

        document.querySelectorAll('form').forEach((form) => {
          form.addEventListener('submit', (event) => {
            event.preventDefault();
            event.stopImmediatePropagation();
          }, true);
          form.querySelectorAll('input, select, textarea').forEach((control) => { control.disabled = true; });
          form.querySelectorAll('button').forEach((button) => {
            if (!/(?:next|prev|previous)btn$/i.test(button.id)) button.disabled = true;
          });
        });

        document.querySelectorAll('.step[data-step], .af-step[data-step]').forEach((item) => {
          item.addEventListener('click', () => window.setTimeout(() => {
            const activeStep = document.querySelector('.step-content.active')?.id.match(/-step-(\d+)$/)?.[1];
            if (activeStep && window.parent !== window) {
              window.parent.postMessage({ type: 'form-builder-preview-step', step: Number(activeStep) }, window.location.origin);
            }
          }, 0));
        });
      });
    </script>
  @endif
</body>

</html>
