@extends('layouts.portal')
@section('title', 'Registered Media Houses — Records')

@section('content')
  @include('staff.partials.dashboard_theme')
  <div class="staff-dashboard-theme">
  <div class="records-page zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <style>
    .records-page{display:flex;flex-direction:column}.records-heading{order:0;position:relative;overflow:hidden;padding:24px 28px;border-radius:24px;background:linear-gradient(120deg,#0e1b2b 0%,#173b3d 55%,#1f6f57 100%);color:#fff;box-shadow:0 18px 40px rgba(15,35,48,.18);margin-bottom:24px}.records-heading:after{content:"";position:absolute;right:-55px;top:-95px;width:220px;height:220px;border-radius:50%;background:rgba(242,201,76,.18)}.records-heading>*{position:relative;z-index:1}.records-heading h4{font-size:clamp(1.65rem,2.7vw,2.25rem)!important;letter-spacing:-.03em;color:#fff!important}.records-heading .text-muted{color:rgba(255,255,255,.76)!important}.records-heading .btn-white,.records-heading .btn-outline-success{background:rgba(255,255,255,.95);border-color:transparent;color:#176a4f}.records-heading .btn{border-radius:10px;font-weight:700}
    .records-stats{order:1;display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 18px!important}.records-stats>div{width:auto!important;max-width:none!important;padding:0!important}.record-kpi{display:flex;align-items:center;gap:13px;border:1px solid #e8edf1;border-radius:18px;background:linear-gradient(145deg,#fff 0%,#fbfdfc 100%);padding:17px 18px;min-height:112px;box-shadow:0 10px 24px rgba(15,35,48,.07);position:relative;overflow:hidden}.record-kpi:after{content:"";position:absolute;width:88px;height:88px;right:-38px;bottom:-42px;border-radius:50%;background:rgba(250,204,21,.1)}.record-kpi-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#fff8d9;color:#927300;font-size:1.2rem;flex:0 0 auto;position:relative;z-index:1}.record-kpi-icon.green{background:#e8f5ed;color:#247a54}.record-kpi-icon.blue{background:#e6f1fb;color:#2876b8}.record-kpi-icon.gold{background:#fff3d7;color:#a46d0a}.record-kpi-label{text-transform:uppercase;letter-spacing:.06em;color:#64756f;font-size:.68rem;font-weight:800;position:relative;z-index:1}.record-kpi-value{font-size:1.85rem;line-height:1.05;font-weight:850;color:#172b2b;margin-top:4px;position:relative;z-index:1}.record-kpi-note{font-size:.73rem;color:#83918c;margin-top:5px;position:relative;z-index:1}.records-secondary{display:flex;flex-wrap:wrap;gap:10px;order:1;margin-bottom:24px!important}.records-secondary span{display:inline-flex;align-items:center;gap:8px;border:1px solid #e3ebe7;background:#f8fbf9;border-radius:999px;padding:8px 13px;color:#64756f;font-size:.78rem;font-weight:750}.records-secondary span i{color:#2876b8}.records-secondary span b{color:#172b2b}.records-secondary .danger{border-color:#f0d7d5;background:#fff7f6}.records-secondary .danger i,.records-secondary .danger b{color:#bd4b42}.records-chart-row{order:5}.records-chart-row .zmc-card{border-radius:22px!important;border:1px solid #e8edf1!important;box-shadow:0 10px 24px rgba(15,35,48,.07)!important}.records-filter{order:3;border-radius:22px!important;border:1px solid #e8edf1!important;background:#f8faf9}.records-table{order:4;border-radius:22px!important;border:1px solid #e8edf1!important;box-shadow:0 10px 24px rgba(15,35,48,.07);overflow:hidden}.records-table .table thead th{background:#f4f8f6;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em}.records-table .table tbody tr:hover{background:#fbfdfc}@media(max-width:1050px){.records-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.records-stats{grid-template-columns:1fr}}
  </style>

  <div class="records-heading d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
        Registered Media Houses
      </h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Comprehensive database with analytics, filtering, and multi-format export. Includes publication/service details.
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="dropdown">
        <button class="btn btn-outline-success border shadow-sm btn-sm px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
          <i class="ri-download-line me-1"></i> Export
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <a class="dropdown-item" href="{{ route('staff.officer.records.export', ['type' => 'mediahouses']) }}?format=csv&{{ http_build_query(request()->only(['search','category','province','year','registration_status','collection_status','expiry_status','pub_medium','pub_status','form_type','status_filter'])) }}">
              <i class="ri-file-text-line me-2 text-success"></i> CSV
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('staff.officer.records.export', ['type' => 'mediahouses']) }}?format=excel&{{ http_build_query(request()->only(['search','category','province','year','registration_status','collection_status','expiry_status','pub_medium','pub_status','form_type','status_filter'])) }}">
              <i class="ri-file-excel-2-line me-2 text-success"></i> Excel
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="{{ route('staff.officer.records.export', ['type' => 'mediahouses']) }}?format=pdf&{{ http_build_query(request()->only(['search','category','province','year','registration_status','collection_status','expiry_status','pub_medium','pub_status','form_type','status_filter'])) }}" target="_blank">
              <i class="ri-file-pdf-line me-2 text-danger"></i> PDF
            </a>
          </li>
        </ul>
      </div>
      <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-arrow-left-line me-1"></i> Back
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size: var(--font-size-lg); line-height: 1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <div class="records-stats">
    <div class="record-kpi"><div class="record-kpi-icon"><i class="ri-database-2-line"></i></div><div><div class="record-kpi-label">Registered media houses</div><div class="record-kpi-value">{{ number_format($mhStats['total'] ?? 0) }}</div><div class="record-kpi-note">All registration records</div></div></div>
    <div class="record-kpi"><div class="record-kpi-icon green"><i class="ri-shield-check-line"></i></div><div><div class="record-kpi-label">Active registrations</div><div class="record-kpi-value">{{ number_format($mhStats['active'] ?? 0) }}</div><div class="record-kpi-note">Currently valid</div></div></div>
    <div class="record-kpi"><div class="record-kpi-icon blue"><i class="ri-checkbox-circle-line"></i></div><div><div class="record-kpi-label">Collected</div><div class="record-kpi-value">{{ number_format($mhStats['collected'] ?? 0) }}</div><div class="record-kpi-note">Certificates issued</div></div></div>
    <div class="record-kpi"><div class="record-kpi-icon gold"><i class="ri-inbox-line"></i></div><div><div class="record-kpi-label">Awaiting collection</div><div class="record-kpi-value">{{ number_format($mhStats['uncollected'] ?? 0) }}</div><div class="record-kpi-note">Ready for collection</div></div></div>
  </div>
  <div class="records-secondary"><span class="danger"><i class="ri-alarm-warning-line"></i> Expired <b>{{ number_format($mhStats['expired'] ?? 0) }}</b></span></div>

  <div class="records-chart-row row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="zmc-card p-3 h-100">
        <h2 class="fw-bold mb-3"><i class="ri-line-chart-line me-2"></i> Monthly Registrations Trend</h2>
        <canvas id="mhMonthlyChart" height="200"></canvas>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="zmc-card p-3 h-100">
        <h3 class="fw-bold mb-3"><i class="ri-bar-chart-grouped-line me-2"></i> Yearly Totals</h3>
        <canvas id="mhYearlyChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <div class="records-chart-row row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="zmc-card p-3 h-100">
        <h4 class="fw-bold mb-3"><i class="ri-pie-chart-line me-2"></i> By Category</h4>
        <canvas id="mhCategoryChart" height="200"></canvas>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="zmc-card p-3 h-100">
        <h5 class="fw-bold mb-3"><i class="ri-donut-chart-line me-2"></i> By Province</h5>
        <canvas id="mhProvinceChart" height="200"></canvas>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="zmc-card p-3 h-100">
        <h6 class="fw-bold mb-3"><i class="ri-donut-chart-line me-2"></i> Status Distribution</h6>
        <canvas id="mhStatusChart" height="200"></canvas>
      </div>
    </div>
  </div>

  <div class="records-filter zmc-card mb-4">
    <h6 class="fw-bold mb-3"><i class="ri-filter-3-line me-2"></i> Filters</h6>
    <form method="GET" action="{{ route('staff.officer.records.registered-mediahouses') }}" id="filterForm">
      <input type="hidden" name="tab" id="activeTabInput" value="{{ request('tab', 'registration') }}">
      <div class="row g-2">
        <div class="col-12 col-md-4 col-lg-3">
          <label class="form-label small fw-bold">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Org name, Reg #, Directors, Email..." value="{{ request('search') }}">
        </div>
        @if($hasRecords ?? false)
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Category</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['categories'] ?? [] as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucwords($cat) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Province</label>
          <select name="province" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['provinces'] ?? [] as $prov)
            <option value="{{ $prov }}" {{ request('province') === $prov ? 'selected' : '' }}>{{ $prov }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Year</label>
          <select name="year" class="form-select form-select-sm">
            <option value="">All Years</option>
            @foreach($filterOptions['years'] ?? [] as $yr)
            <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Reg. Status</label>
          <select name="registration_status" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['statuses'] ?? [] as $st)
            <option value="{{ $st }}" {{ request('registration_status') === $st ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $st)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Collection</label>
          <select name="collection_status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="collected" {{ request('collection_status') === 'collected' ? 'selected' : '' }}>Collected</option>
            <option value="uncollected" {{ request('collection_status') === 'uncollected' ? 'selected' : '' }}>Uncollected</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Expiry</label>
          <select name="expiry_status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="active" {{ request('expiry_status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="expiring_soon" {{ request('expiry_status') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon (90d)</option>
            <option value="expired" {{ request('expiry_status') === 'expired' ? 'selected' : '' }}>Expired</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Pub Medium</label>
          <select name="pub_medium" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['pub_media'] ?? [] as $pm)
            <option value="{{ $pm }}" {{ request('pub_medium') === $pm ? 'selected' : '' }}>{{ ucwords($pm) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Pub Status</label>
          <select name="pub_status" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['pub_statuses'] ?? [] as $ps)
            <option value="{{ $ps }}" {{ request('pub_status') === $ps ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $ps)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Levy Status</label>
          <select name="levy_status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="paid" {{ request('levy_status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ request('levy_status') === 'unpaid' ? 'selected' : '' }}>Not Paid</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Operational Status</label>
          <select name="operational_status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="operational" {{ request('operational_status') === 'operational' ? 'selected' : '' }}>Operational</option>
            <option value="not_operational" {{ request('operational_status') === 'not_operational' ? 'selected' : '' }}>Not Operational</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Scope</label>
          <select name="scope" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['scopes'] ?? [] as $sc)
            <option value="{{ $sc }}" {{ request('scope') === $sc ? 'selected' : '' }}>{{ ucwords($sc) }}</option>
            @endforeach
          </select>
        </div>
        @else
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Form Type</label>
          <select name="form_type" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="AP1" {{ request('form_type') === 'AP1' ? 'selected' : '' }}>AP1 — Mass Media</option>
            <option value="AP2" {{ request('form_type') === 'AP2' ? 'selected' : '' }}>AP2 — News Agency</option>
            <option value="AP4" {{ request('form_type') === 'AP4' ? 'selected' : '' }}>AP4 — Foreign Rep</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Year</label>
          <select name="year" class="form-select form-select-sm">
            <option value="">All Years</option>
            @foreach($filterOptions['years'] ?? [] as $yr)
            <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Status</label>
          <select name="status_filter" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="approved" {{ request('status_filter') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="pending" {{ request('status_filter') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="returned" {{ request('status_filter') === 'returned' ? 'selected' : '' }}>Returned</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Category</label>
          <select name="category" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['categories'] ?? [] as $cat)
            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucwords($cat) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Province</label>
          <select name="province" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['provinces'] ?? [] as $prov)
            <option value="{{ $prov }}" {{ request('province') === $prov ? 'selected' : '' }}>{{ $prov }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Levy Status</label>
          <select name="levy_status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="paid" {{ request('levy_status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ request('levy_status') === 'unpaid' ? 'selected' : '' }}>Not Paid</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Operational Status</label>
          <select name="operational_status" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="operational" {{ request('operational_status') === 'operational' ? 'selected' : '' }}>Operational</option>
            <option value="not_operational" {{ request('operational_status') === 'not_operational' ? 'selected' : '' }}>Not Operational</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Scope</label>
          <select name="scope" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($filterOptions['scopes'] ?? [] as $sc)
            <option value="{{ $sc }}" {{ request('scope') === $sc ? 'selected' : '' }}>{{ ucwords($sc) }}</option>
            @endforeach
          </select>
        </div>
        @endif
        <div class="col-12 col-md-3 col-lg-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i> Filter
          </button>
        </div>
      </div>
      @if(request()->hasAny(['search','category','province','year','registration_status','collection_status','expiry_status','pub_medium','pub_status','form_type','status_filter','levy_status','operational_status','scope']))
        <div class="mt-2">
          <a href="{{ route('staff.officer.records.registered-mediahouses') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ri-close-line me-1"></i> Clear Filters
          </a>
        </div>
      @endif
    </form>
  </div>

  @if(($hasRecords ?? false) && $mediaHouses->where('collected_at', null)->count() > 0)
    <div class="mb-3">
      <form method="POST" action="{{ route('staff.officer.records.send-collection-notification') }}" class="d-inline">
        @csrf
        <input type="hidden" name="record_type" value="registration">
        <button type="submit" class="btn btn-success btn-sm shadow-sm">
          <i class="ri-mail-send-line me-1"></i> Send Collection Reminders
        </button>
      </form>
    </div>
  @endif

    {{-- Tab Navigation --}}
  <div class="records-table zmc-card p-0 shadow-sm border-0">
    <div class="border-bottom px-3 pt-3">
      <ul class="nav nav-tabs border-0" id="mediaHousesTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ request('tab', 'registration') === 'registration' ? 'active' : '' }} d-flex align-items-center gap-2"
            id="registration-tab" data-bs-toggle="tab" data-bs-target="#registration-panel"
            type="button" role="tab" onclick="setActiveTab('registration')">
            <i class="ri-building-4-line"></i>
            <span class="fw-semibold">Registration Details</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ request('tab') === 'contact' ? 'active' : '' }} d-flex align-items-center gap-2"
            id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-panel"
            type="button" role="tab" onclick="setActiveTab('contact')">
            <i class="ri-contacts-line"></i>
            <span class="fw-semibold">Contact Details</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link {{ request('tab') === 'publications' ? 'active' : '' }} d-flex align-items-center gap-2"
            id="publications-tab" data-bs-toggle="tab" data-bs-target="#publications-panel"
            type="button" role="tab" onclick="setActiveTab('publications')">
            <i class="ri-newspaper-line"></i>
            <span class="fw-semibold">Publications / Services</span>
          </button>
        </li>

      </ul>
    </div>

    <div class="tab-content" id="mediaHousesTabContent">

      {{-- ====================== TAB 1: REGISTRATION DETAILS ====================== --}}
      <div class="tab-pane fade {{ request('tab', 'registration') === 'registration' ? 'show active' : '' }}"
        id="registration-panel" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size:11px;">
            <thead class="bg-light text-nowrap">
              <tr>
                <th class="ps-3" style="min-width:200px;">Mass Media Service</th>
                <th style="min-width:80px;">Category</th>
                <th style="min-width:130px;">Registration Number</th>
                <th style="min-width:120px;">Registration Date</th>
                <th style="min-width:120px;">Registration Status</th>
                <th style="min-width:120px;">Registration Expiry</th>
                <th style="min-width:110px;">Annual Levy</th>
                <th style="min-width:200px;">Directors</th>
                <th style="min-width:200px;">Shareholding</th>
                <th class="text-center" style="min-width:110px;">Gender</th>
                <th style="min-width:120px;">Nationality</th>
                <th style="min-width:140px;">ID Number</th>
                <th style="min-width:140px;">Phone</th>
                <th style="min-width:180px;">Email</th>
                <th style="min-width:180px;">Address</th>
                <th style="min-width:140px;">Occupation</th>
                <th class="text-center" style="min-width:110px;">Operational</th>
                <th class="pe-3 text-end" style="min-width:80px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($mediaHouses as $record)
                @php
                  $isRegRecord = $record instanceof \App\Models\RegistrationRecord;
                  $app = $isRegRecord ? $record->application : $record;
                  $regRecord = $isRegRecord ? $record : ($record->registrationRecord ?? null);
                  $fd = $app ? (is_array($app->form_data) ? $app->form_data : (json_decode($app->form_data ?? '{}', true) ?? [])) : [];

                  $entityName = $isRegRecord ? ($record->organisation_name ?: ($record->entity_name ?: '—')) : ($app->applicant->name ?? ($fd['organisation_name'] ?? ($fd['org_name'] ?? ($fd['contact_name'] ?? '—'))));
                  $category = $isRegRecord ? $record->category : ($app->media_house_category_code ?? ($fd['category'] ?? ($fd['mass_media_category_code'] ?? '—')));
                  $regNo = $regRecord ? $regRecord->registration_no : '—';
                  $regDate = $regRecord && $regRecord->issued_at ? $regRecord->issued_at->format('d M Y') : '—';
                  $regExpiry = $regRecord && $regRecord->expires_at ? $regRecord->expires_at->format('d M Y') : '—';

                  $userId = $isRegRecord ? $record->contact_user_id : ($app ? $app->applicant_user_id : null);
                  $hasPaidLevy = false;
                  if ($userId) {
                      $hasPaidLevy = \App\Models\LevyRecord::where('user_id', $userId)
                          ->whereIn(\DB::raw('LOWER(payment_status)'), ['approved', 'paid', 'verified', 'completed'])
                          ->exists();
                  }

                  $isExpiredByTime = $regRecord && $regRecord->expires_at && $regRecord->expires_at->isPast();
                  $certStatus = ($isExpiredByTime || !$hasPaidLevy) ? 'Expired' : 'Not Expired';
                  
                  $isOperational = ($certStatus === 'Not Expired');

                  $hqAddress = $isRegRecord ? ($record->physical_address ?: ($record->address ?: '')) : ($fd['contact_address'] ?? ($fd['org_head_office'] ?? ($fd['rep_office_address'] ?? '')));
                  $phone = $isRegRecord ? ($record->phone ?: ($record->cell ?: '')) : trim(($fd['contact_phone_country_code'] ?? '') . ' ' . ($fd['contact_phone'] ?? ($fd['phone'] ?? '')));
                  $email = $isRegRecord ? $record->email : ($fd['contact_email'] ?? ($fd['email'] ?? ($fd['rep_office_email'] ?? '')));
                  $website = $isRegRecord ? $record->website : ($fd['website'] ?? ($fd['org_website'] ?? ''));

                  // Build directors array
                  $rawDirs = $fd['directors'] ?? [];
                  $mappedDirs = [];
                  if (is_array($rawDirs) && count($rawDirs) > 0) {
                    foreach ($rawDirs as $idx => $d) {
                      $dName  = trim(($d['name'] ?? '') . ' ' . ($d['surname'] ?? ''));
                      $share  = trim($d['shareholding'] ?? ($d['shareholding_percent'] ?? ''));
                      if ($share !== '' && !str_ends_with($share, '%')) $share .= '%';
                      $mappedDirs[] = [
                        'name'        => $dName ?: '—',
                        'gender'      => $d['gender'] ?? ($d['sex'] ?? '—'),
                        'nationality' => $d['nationality'] ?? ($d['country'] ?? '—'),
                        'shareholding'=> $share ?: '—',
                        'id_number'   => $d['id_number'] ?? ($d['passport_number'] ?? ($d['national_id'] ?? '—')),
                        'position'    => $d['position'] ?? ($d['role'] ?? ($d['designation'] ?? '—')),
                        'phone'       => trim(($d['phone_country_code'] ?? '') . ' ' . ($d['phone'] ?? ($d['contact_phone'] ?? ''))) ?: '—',
                        'email'       => $d['email'] ?? ($d['contact_email'] ?? '—'),
                        'address'     => $d['address'] ?? ($d['director_address'] ?? ($d['residential_address'] ?? '—')),
                        'occupation'  => $d['occupation'] ?? ($d['director_occupation'] ?? ($d['profession'] ?? '—')),
                      ];
                    }
                  } else {
                    // Fall back to registration record directors string
                    $dirStr = $isRegRecord ? ($record->directors ?? '') : '';
                    if ($dirStr) {
                      foreach (explode(',', $dirStr) as $dn) {
                        $dn = trim($dn);
                        if ($dn) $mappedDirs[] = [
                          'name' => $dn, 'gender' => '—', 'nationality' => '—',
                          'shareholding' => '—', 'id_number' => '—', 'position' => '—',
                          'phone' => '—', 'email' => '—', 'address' => '—', 'occupation' => '—',
                        ];
                      }
                    }
                  }

                  if (empty($mappedDirs)) {
                    $mappedDirs[] = [
                      'name' => '—', 'gender' => '—', 'nationality' => '—',
                      'shareholding' => '—', 'id_number' => '—', 'position' => '—',
                      'phone' => '—', 'email' => '—', 'address' => '—', 'occupation' => '—',
                    ];
                  }

                  $firstDir = $mappedDirs[0];

                  // Formatted lists for the edit modal
                  $directorNames = collect($mappedDirs)->pluck('name')->filter(fn($n) => $n !== '—')->implode(', ') ?: ($isRegRecord ? ($record->directors ?? '') : '');
                  $shareholding = collect($mappedDirs)->map(function($d) {
                      if ($d['name'] === '—' || $d['shareholding'] === '—') return null;
                      return $d['name'] . ' - ' . $d['shareholding'];
                  })->filter()->implode(', ') ?: ($isRegRecord ? ($record->shareholding ?? '') : '');
                @endphp
                <tr id="reg-row-{{ $loop->index }}">
                  <td class="ps-3 fw-bold">{{ $entityName }}</td>
                  <td>
                    @if($category && $category !== '—')
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ strtoupper($category) }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="font-monospace">{{ $regNo }}</td>
                  <td>{{ $regDate }}</td>
                  <td>
                    @if($certStatus === 'Not Expired')
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Not Expired</span>
                    @else
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Expired</span>
                    @endif
                  </td>
                  <td>{{ $regExpiry }}</td>
                  <td>
                    @if($hasPaidLevy)
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                    @else
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Not Paid</span>
                    @endif
                  </td>
                  {{-- Directors column --}}
                  <td style="max-width:220px;">
                    @if(count($mappedDirs) > 1)
                      <select class="form-select form-select-sm reg-dir-selector"
                        data-row-id="reg-row-{{ $loop->index }}"
                        onchange="changeRegistrationDirector(this)"
                        style="max-width:200px;font-size:11px;padding:2px 8px;">
                        @foreach($mappedDirs as $dIdx => $d)
                          <option value="{{ $dIdx }}"
                            data-gender="{{ $d['gender'] }}"
                            data-shareholding="{{ $d['shareholding'] }}"
                            data-nationality="{{ $d['nationality'] }}"
                            data-id-number="{{ $d['id_number'] }}"
                            data-phone="{{ $d['phone'] }}"
                            data-email="{{ $d['email'] }}"
                            data-address="{{ $d['address'] }}"
                            data-occupation="{{ $d['occupation'] }}">
                            {{ $d['name'] }}
                          </option>
                        @endforeach
                      </select>
                    @else
                      <span class="reg-dir-name-static text-truncate d-inline-block" style="max-width:180px;" title="{{ $firstDir['name'] }}">{{ Str::limit($firstDir['name'], 35) }}</span>
                    @endif
                  </td>
                  {{-- Shareholding column --}}
                  <td class="reg-dir-shareholding" style="max-width:220px;">
                    @if($firstDir['shareholding'] !== '—')
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $firstDir['shareholding'] }}</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  {{-- SHS Gender --}}
                  <td class="text-center reg-dir-gender">
                    @php $g = strtolower($firstDir['gender']); @endphp
                    @if(in_array($g, ['male', 'm']))
                      <span class="badge bg-info-subtle text-info border border-info-subtle">♂ Male</span>
                    @elseif(in_array($g, ['female', 'f']))
                      <span class="badge bg-warning-subtle text-warning border border-warning-subtle">♀ Female</span>
                    @else
                      <span class="text-muted">{{ $firstDir['gender'] }}</span>
                    @endif
                  </td>
                  {{-- Nationality column --}}
                  <td class="reg-dir-nationality text-center">
                    {{ $firstDir['nationality'] }}
                  </td>
                  {{-- ID Number column --}}
                  <td class="reg-dir-id-number text-center">
                    {{ $firstDir['id_number'] }}
                  </td>
                  {{-- Phone column --}}
                  <td class="reg-dir-phone text-center">
                    {{ $firstDir['phone'] }}
                  </td>
                  {{-- Email column --}}
                  <td class="reg-dir-email text-center">
                    @if($firstDir['email'] !== '—')
                      <a href="mailto:{{ $firstDir['email'] }}" class="text-decoration-none">{{ $firstDir['email'] }}</a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  {{-- Address column --}}
                  <td class="reg-dir-address" style="max-width:200px;">
                    <span title="{{ $firstDir['address'] }}">{{ Str::limit($firstDir['address'], 40) }}</span>
                  </td>
                  {{-- Occupation column --}}
                  <td class="reg-dir-occupation">
                    {{ $firstDir['occupation'] }}
                  </td>
                  {{-- Operational status --}}
                  <td class="text-center">
                    @if($isOperational)
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Operational</span>
                    @else
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Not Operational</span>
                    @endif
                  </td>
                  <td class="pe-3 text-end">
                    <button class="btn btn-outline-primary btn-xs edit-mh-btn"
                      data-id="{{ $isRegRecord ? $record->id : ($app ? $app->id : $record->id) }}"
                      data-record-type="{{ $isRegRecord ? 'registration' : 'application' }}"
                      data-entity-name="{{ $entityName }}"
                      data-category="{{ $category }}"
                      data-registration-no="{{ $regNo }}"
                      data-registration-date="{{ $regRecord && $regRecord->issued_at ? $regRecord->issued_at->format('Y-m-d') : ($app && $app->submitted_at ? $app->submitted_at->format('Y-m-d') : '') }}"
                      data-registration-expiry="{{ $regRecord && $regRecord->expires_at ? $regRecord->expires_at->format('Y-m-d') : '' }}"
                      data-status="{{ $certStatus }}"
                      data-physical-address="{{ $hqAddress }}"
                      data-phone="{{ $phone }}"
                      data-email="{{ $email }}"
                      data-website="{{ $website }}"
                      data-directors="{{ $directorNames }}"
                      data-shareholding="{{ $shareholding }}">
                      <i class="ri-edit-line"></i> Edit
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="18" class="text-center text-muted py-5">
                    <i class="ri-building-line" style="font-size:48px;opacity:0.3;display:block;"></i>
                    <div class="mt-2">No registered media houses found</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($mediaHouses->hasPages())
          <div class="p-3 border-top">
            {{ $mediaHouses->appends(request()->except('page'))->links() }}
          </div>
        @endif
      </div>

      {{-- ====================== TAB 2: CONTACT DETAILS ====================== --}}
      <div class="tab-pane fade {{ request('tab') === 'contact' ? 'show active' : '' }}"
        id="contact-panel" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size:11px;">
            <thead class="bg-light text-nowrap">
              <tr>
                <th style="min-width:120px;">Reg. Number</th>
                <th class="text-center" style="min-width:80px;">Registered</th>
                <th class="ps-3" style="min-width:200px;">Mass Media Service</th>
                <th style="min-width:230px;">Headquarters' Address</th>
                <th style="min-width:160px;">Phone Number</th>
                <th style="min-width:200px;">Email</th>
                <th style="min-width:180px;">Website</th>
                <th class="pe-3 text-end" style="min-width:80px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($mediaHouses as $record)
                @php
                  $isRegRecord = $record instanceof \App\Models\RegistrationRecord;
                  $app = $isRegRecord ? $record->application : $record;
                  $regRecord = $isRegRecord ? $record : ($record->registrationRecord ?? null);
                  $fd = $app ? (is_array($app->form_data) ? $app->form_data : (json_decode($app->form_data ?? '{}', true) ?? [])) : [];

                  $regNo = $isRegRecord ? $record->registration_no : ($regRecord->registration_no ?? ($app->reference ?? '—'));
                  $regYear = $regRecord && $regRecord->issued_at ? $regRecord->issued_at->format('Y') : ($app && $app->submitted_at ? $app->submitted_at->format('Y') : '—');
                  $isRegistered = !is_null($regRecord);
                  $category = $isRegRecord ? $record->category : ($app->media_house_category_code ?? ($fd['category'] ?? ($fd['mass_media_category_code'] ?? '—')));
                  $entityName = $isRegRecord ? ($record->organisation_name ?: ($record->entity_name ?: '—')) : ($app->applicant->name ?? ($fd['organisation_name'] ?? ($fd['org_name'] ?? ($fd['contact_name'] ?? '—'))));

                  $hqAddress = $isRegRecord ? ($record->physical_address ?: ($record->address ?: '—')) : ($fd['contact_address'] ?? ($fd['org_head_office'] ?? ($fd['rep_office_address'] ?? '—')));
                  $phone = $isRegRecord ? ($record->phone ?: ($record->cell ?: '—')) : trim(($fd['contact_phone_country_code'] ?? '') . ' ' . ($fd['contact_phone'] ?? ($fd['phone'] ?? '')));
                  $phone = trim($phone) ?: '—';
                  $email = $isRegRecord ? $record->email : ($fd['contact_email'] ?? ($fd['email'] ?? ($fd['rep_office_email'] ?? '—')));
                  $website = $isRegRecord ? $record->website : ($fd['website'] ?? ($fd['org_website'] ?? '—'));

                  if ($isRegRecord) {
                      $directorNames = $record->directors;
                      $shareholding = $record->shareholding;
                  } else {
                      $directors = collect($fd['directors'] ?? []);
                      $directorNames = $directors->map(fn($d) => trim(($d['name'] ?? '') . ' ' . ($d['surname'] ?? '')))->filter()->implode(', ');
                      $shareholding = $directors->map(fn($d) => trim(($d['name'] ?? '') . ' ' . ($d['surname'] ?? '')) . '-' . ($d['shareholding'] ?? '?') . '%')->filter()->implode(', ');
                  }
                  
                  $userId = $isRegRecord ? $record->contact_user_id : ($app ? $app->applicant_user_id : null);
                  $hasPaidLevy = false;
                  if ($userId) {
                      $hasPaidLevy = \App\Models\LevyRecord::where('user_id', $userId)
                          ->whereIn(\DB::raw('LOWER(payment_status)'), ['approved', 'paid', 'verified', 'completed'])
                          ->exists();
                  }
                  $isExpiredByTime = $regRecord && $regRecord->expires_at && $regRecord->expires_at->isPast();
                  $certStatus = ($isExpiredByTime || !$hasPaidLevy) ? 'Expired' : 'Not Expired';
                @endphp
                <tr>
                  <td>{{ $regNo }}</td>
                  <td class="text-center">
                    <input type="checkbox" class="form-check-input" {{ $isRegistered ? 'checked' : '' }} disabled>
                  </td>
                  <td class="fw-semibold">{{ $entityName }}</td>
                  <td>
                    <span title="{{ $hqAddress }}">{{ Str::limit($hqAddress, 60) }}</span>
                  </td>
                  <td>{{ $phone }}</td>
                  <td>
                    @if($email && $email !== '—')
                      <a href="mailto:{{ $email }}" class="text-truncate d-inline-block" style="max-width:180px;" title="{{ $email }}">{{ $email }}</a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    @if($website && $website !== '—')
                      <a href="{{ Str::startsWith($website, 'http') ? $website : 'https://' . $website }}" target="_blank"
                        class="text-truncate d-inline-block" style="max-width:160px;" title="{{ $website }}">{{ $website }}</a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="pe-3 text-end">
                    <button class="btn btn-outline-primary btn-xs edit-mh-btn"
                      data-id="{{ $isRegRecord ? $record->id : ($app ? $app->id : $record->id) }}"
                      data-record-type="{{ $isRegRecord ? 'registration' : 'application' }}"
                      data-entity-name="{{ $entityName }}"
                      data-category="{{ $category }}"
                      data-registration-no="{{ $regNo }}"
                      data-registration-date="{{ $regRecord && $regRecord->issued_at ? $regRecord->issued_at->format('Y-m-d') : ($app && $app->submitted_at ? $app->submitted_at->format('Y-m-d') : '') }}"
                      data-registration-expiry="{{ $regRecord && $regRecord->expires_at ? $regRecord->expires_at->format('Y-m-d') : '' }}"
                      data-status="{{ $certStatus }}"
                      data-physical-address="{{ $hqAddress }}"
                      data-phone="{{ $phone }}"
                      data-email="{{ $email }}"
                      data-website="{{ $website }}"
                      data-directors="{{ $directorNames }}"
                      data-shareholding="{{ $shareholding }}">
                      <i class="ri-edit-line"></i> Edit
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-5">
                    <i class="ri-contacts-line" style="font-size:48px;opacity:0.3;display:block;"></i>
                    <div class="mt-2">No contact details found</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($mediaHouses->hasPages())
          <div class="p-3 border-top">
            {{ $mediaHouses->appends(request()->except('page'))->links() }}
          </div>
        @endif
      </div>

      {{-- ====================== TAB 3: PUBLICATIONS ====================== --}}
      <div class="tab-pane fade {{ request('tab') === 'publications' ? 'show active' : '' }}"
        id="publications-panel" role="tabpanel">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size:11px;">
            <thead class="bg-light text-nowrap">
              <tr>
                <th style="min-width:200px;">Mass Media Service</th>
                <th style="min-width:200px;">Publication / Service</th>
                <th style="min-width:100px;">Type</th>
                <th style="min-width:120px;">Print/Digital/Dual</th>
                <th style="min-width:130px;">Editor</th>
                <th style="min-width:90px;">Frequency</th>
                <th style="min-width:90px;">Language</th>
                <th style="min-width:80px;">Focus</th>
                <th style="min-width:80px;">Scope</th>
                <th style="min-width:130px;">Coverage Area</th>
                <th style="min-width:120px;">Provincial Reach</th>
                <th style="min-width:140px;">Phone</th>
                <th style="min-width:180px;">Email</th>
                <th style="min-width:160px;">Website</th>
                <th class="text-center" style="min-width:90px;">Operational</th>
                <th class="pe-3 text-end" style="min-width:80px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($mediaHouses as $record)
                @php
                  $isRegRecord = $record instanceof \App\Models\RegistrationRecord;
                  $app = $isRegRecord ? $record->application : $record;
                  $regRecord = $isRegRecord ? $record : ($record->registrationRecord ?? null);
                  $fd = $app ? (is_array($app->form_data) ? $app->form_data : (json_decode($app->form_data ?? '{}', true) ?? [])) : [];

                  $entityName = $isRegRecord ? ($record->organisation_name ?: ($record->entity_name ?: '—')) : ($app->applicant->name ?? ($fd['organisation_name'] ?? ($fd['org_name'] ?? ($fd['contact_name'] ?? '—'))));

                  $pubs = [];
                  if ($isRegRecord && $record->publications->count() > 0) {
                      $pubs = $record->publications;
                  } else {
                      $services = $fd['services'] ?? $fd['publications'] ?? [null];
                      if (!is_array($services)) $services = [null];
                      foreach($services as $s) {
                          if ($s) {
                             $pubs[] = (object) [
                                'id' => null,
                                'publication_name' => $s['publication_name'] ?? ($s['radio_titles_published'] ?? ($s['mass_media_activity'] ?? '—')),
                                'type' => $s['mass_media_activity'] ?? ($s['foreign_media_type'] ?? '—'),
                                'medium' => $s['medium'] ?? '',
                                'editor_manager' => $s['editor_publisher_name'] ?? ($s['ceo_name'] ?? '—'),
                                'frequency' => $s['frequency'] ?? ($s['radio_frequency'] ?? '—'),
                                'language' => $s['language_of_publication'] ?? ($s['language'] ?? '—'),
                                'focus' => $s['focus'] ?? 'General',
                                'scope' => $s['scope'] ?? 'National',
                                'reach_cities' => $s['reach'] ?? 'National',
                                'reach_provinces' => $s['provincial_reach'] ?? 'All Provinces',
                                'pub_contacts' => trim(($s['contact_phone_country_code'] ?? '') . ' ' . ($s['contact_phone'] ?? ($s['phone'] ?? ''))),
                                'pub_email' => $s['contact_email'] ?? ($s['email'] ?? '—'),
                                'pub_website' => $s['website'] ?? '—',
                                'operational_status' => 'yes',
                             ];
                          } else {
                             $pubs[] = (object) [
                                'id' => null,
                                'publication_name' => $fd['publication_name'] ?? ($fd['radio_titles_published'] ?? ($fd['mass_media_activity'] ?? '—')),
                                'type' => $fd['mass_media_activity'] ?? ($fd['foreign_media_type'] ?? '—'),
                                'medium' => '',
                                'editor_manager' => $fd['editor_publisher_name'] ?? ($fd['ceo_name'] ?? '—'),
                                'frequency' => $fd['frequency'] ?? ($fd['radio_frequency'] ?? '—'),
                                'language' => $fd['language_of_publication'] ?? ($fd['language'] ?? '—'),
                                'focus' => $fd['focus'] ?? 'General',
                                'scope' => $fd['scope'] ?? 'National',
                                'reach_cities' => $fd['reach'] ?? 'National',
                                'reach_provinces' => $fd['provincial_reach'] ?? 'All Provinces',
                                'pub_contacts' => trim(($fd['contact_phone_country_code'] ?? '') . ' ' . ($fd['contact_phone'] ?? ($fd['phone'] ?? ''))),
                                'pub_email' => $fd['contact_email'] ?? ($fd['email'] ?? '—'),
                                'pub_website' => $fd['website'] ?? '—',
                                'operational_status' => 'yes',
                             ];
                          }
                      }
                  }

                  if (empty($pubs)) {
                      $pubs[] = (object) [
                          'id' => null,
                          'publication_name' => '—',
                          'type' => '—',
                          'medium' => '—',
                          'editor_manager' => '—',
                          'frequency' => '—',
                          'language' => '—',
                          'focus' => '—',
                          'scope' => '—',
                          'reach_cities' => '—',
                          'reach_provinces' => '—',
                          'pub_contacts' => '—',
                          'pub_email' => '—',
                          'pub_website' => '—',
                          'operational_status' => 'yes',
                      ];
                  }

                  $mappedPubs = [];
                  foreach ($pubs as $idx => $pub) {
                      $pubName = $pub->publication_name ?? '—';
                      $pubType = $pub->type ?? '—';
                      $pubMediumRaw = strtolower($pub->medium ?? '');
                      $hasPrint = !empty($fd['print_edition']) || in_array(strtolower($pubType), ['newspaper', 'magazine', 'print', 'both', 'dual']) || in_array($pubMediumRaw, ['print', 'both', 'dual']) || stripos($pubMediumRaw, 'print') !== false;
                      $hasOnline = !empty($fd['online_edition']) || in_array(strtolower($pubType), ['online publication', 'electronic', 'both', 'digital', 'internet based media service']) || in_array($pubMediumRaw, ['online', 'digital', 'both', 'dual']) || stripos($pubMediumRaw, 'online') !== false || stripos($pubMediumRaw, 'digital') !== false;
                      
                      $mediumVal = '—';
                      if ($hasPrint && $hasOnline) {
                          $mediumVal = 'Dual';
                      } elseif ($hasPrint) {
                          $mediumVal = 'Print';
                      } elseif ($hasOnline) {
                          $mediumVal = 'Digital';
                      }
                      
                      $mappedPubs[] = [
                          'edit_id' => $isRegRecord ? ($pub->id ?? '') : ($app->id . '-' . $idx),
                          'name' => $pubName,
                          'type' => $pubType,
                          'medium' => $mediumVal,
                          'editor' => $pub->editor_manager ?? '—',
                          'frequency' => $pub->frequency ?? '—',
                          'language' => $pub->language ?? '—',
                          'focus' => $pub->focus ?? '—',
                          'scope' => $pub->scope ?? '—',
                          'reach' => $pub->reach_cities ?? '—',
                          'provincial_reach' => $pub->reach_provinces ?? '—',
                          'phone' => trim($pub->pub_contacts ?? '') ?: '—',
                          'email' => $pub->pub_email ?? '—',
                          'website' => $pub->pub_website ?? '—',
                          'operational' => in_array(strtolower($pub->operational_status ?? ''), ['active', 'operational', 'yes']) ? 'yes' : 'no',
                      ];
                  }
                  
                  $firstPub = $mappedPubs[0] ?? null;
                @endphp
                <tr id="pub-row-{{ $loop->index }}">
                  <td class="fw-semibold"><span title="{{ $entityName }}">{{ Str::limit($entityName, 40) }}</span></td>
                  <td>
                    @if(count($mappedPubs) > 1)
                      <select class="form-select form-select-sm pub-selector" data-row-id="pub-row-{{ $loop->index }}" onchange="changePublication(this)" style="max-width: 200px; font-size: 11px; padding: 2px 8px;">
                        @foreach($mappedPubs as $pIdx => $p)
                          <option value="{{ $pIdx }}"
                            data-type="{{ $p['type'] }}"
                            data-medium="{{ $p['medium'] }}"
                            data-editor="{{ $p['editor'] }}"
                            data-frequency="{{ $p['frequency'] }}"
                            data-language="{{ $p['language'] }}"
                            data-focus="{{ $p['focus'] }}"
                            data-scope="{{ $p['scope'] }}"
                            data-reach="{{ $p['reach'] }}"
                            data-provincial-reach="{{ $p['provincial_reach'] }}"
                            data-phone="{{ $p['phone'] }}"
                            data-email="{{ $p['email'] }}"
                            data-website="{{ $p['website'] }}"
                            data-operational="{{ $p['operational'] }}"
                            data-edit-id="{{ $p['edit_id'] }}"
                            data-record-type="{{ $isRegRecord ? 'registration' : 'application' }}">
                            {{ $p['name'] }}
                          </option>
                        @endforeach
                      </select>
                    @else
                      <span class="pub-name-static" title="{{ $firstPub['name'] ?? '—' }}">{{ Str::limit($firstPub['name'] ?? '—', 40) }}</span>
                    @endif
                  </td>
                  <td class="pub-type">{{ $firstPub['type'] ?? '—' }}</td>
                  <td class="pub-medium">
                    @if(($firstPub['medium'] ?? '') === 'Dual')
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Dual</span>
                    @elseif(($firstPub['medium'] ?? '') === 'Print')
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Print</span>
                    @elseif(($firstPub['medium'] ?? '') === 'Digital')
                      <span class="badge bg-info-subtle text-info border border-info-subtle">Digital</span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="pub-editor">{{ $firstPub['editor'] ?? '—' }}</td>
                  <td class="pub-frequency">{{ $firstPub['frequency'] ?? '—' }}</td>
                  <td class="pub-language">{{ $firstPub['language'] ?? '—' }}</td>
                  <td class="pub-focus">{{ $firstPub['focus'] ?? '—' }}</td>
                  <td class="pub-scope">{{ $firstPub['scope'] ?? '—' }}</td>
                  <td class="pub-reach">{{ $firstPub['reach'] ?? '—' }}</td>
                  <td class="pub-provincial-reach">{{ $firstPub['provincial_reach'] ?? '—' }}</td>
                  <td class="pub-phone">{{ $firstPub['phone'] ?? '—' }}</td>
                  <td class="pub-email">
                    @if(($firstPub['email'] ?? '') && $firstPub['email'] !== '—')
                      <a href="mailto:{{ $firstPub['email'] }}" title="{{ $firstPub['email'] }}" class="text-truncate d-inline-block" style="max-width:160px;">{{ $firstPub['email'] }}</a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="pub-website">
                    @if(($firstPub['website'] ?? '') && $firstPub['website'] !== '—')
                      <a href="{{ Str::startsWith($firstPub['website'], 'http') ? $firstPub['website'] : 'https://' . $firstPub['website'] }}" target="_blank"
                        title="{{ $firstPub['website'] }}" class="text-truncate d-inline-block" style="max-width:150px;">{{ $firstPub['website'] }}</a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="form-check-input pub-operational" {{ ($firstPub['operational'] ?? '') === 'yes' ? 'checked' : '' }} disabled>
                  </td>
                  <td class="pe-3 text-end">
                    <button class="btn btn-outline-primary btn-xs edit-pub-btn"
                      data-id="{{ $firstPub['edit_id'] ?? '' }}"
                      data-record-type="{{ $isRegRecord ? 'registration' : 'application' }}"
                      data-publication-name="{{ $firstPub['name'] ?? '—' }}"
                      data-type="{{ $firstPub['type'] ?? '—' }}"
                      data-medium="{{ $firstPub['medium'] ?? '' }}"
                      data-editor-manager="{{ $firstPub['editor'] ?? '—' }}"
                      data-frequency="{{ $firstPub['frequency'] ?? '—' }}"
                      data-language="{{ $firstPub['language'] ?? '—' }}"
                      data-focus="{{ $firstPub['focus'] ?? '—' }}"
                      data-scope="{{ $firstPub['scope'] ?? '—' }}"
                      data-reach-cities="{{ $firstPub['reach'] ?? '—' }}"
                      data-reach-provinces="{{ $firstPub['provincial_reach'] ?? '—' }}"
                      data-pub-contacts="{{ $firstPub['phone'] ?? '—' }}"
                      data-pub-email="{{ $firstPub['email'] ?? '—' }}"
                      data-pub-website="{{ $firstPub['website'] ?? '—' }}"
                      data-operational-status="{{ $firstPub['operational'] ?? 'yes' }}">
                      <i class="ri-edit-line"></i> Edit
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="16" class="text-center text-muted py-5">
                    <i class="ri-newspaper-line" style="font-size:48px;opacity:0.3;display:block;"></i>
                    <div class="mt-2">No publications found</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if($mediaHouses->hasPages())
          <div class="p-3 border-top">
            {{ $mediaHouses->appends(request()->except('page'))->links() }}
          </div>
        @endif
      </div>



    </div>
  </div>

  <!-- EDIT MEDIA HOUSE MODAL -->
  <div class="modal fade" id="editMediaHouseModal" tabindex="-1" aria-labelledby="editMediaHouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow border-0">
        <div class="modal-header bg-dark text-white py-3">
          <h5 class="modal-title fw-bold" id="editMediaHouseModalLabel">
            <i class="ri-edit-line me-2"></i>Edit Media House Record
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editMediaHouseForm" method="POST" action="">
          @csrf
          <input type="hidden" id="mh_record_type" name="record_type" value="registration">
          <div class="modal-body p-4" style="max-height: calc(100vh - 210px); overflow-y: auto;">
            <div class="row g-3">
              <!-- Left Column: Registration details -->
              <div class="col-12 col-md-6 border-end pe-md-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Registration & Status</h6>
                
                <div class="mb-3">
                  <label for="mh_entity_name" class="form-label small fw-bold">Mass Media Service Name</label>
                  <input type="text" class="form-control form-control-sm" id="mh_entity_name" name="entity_name" required>
                </div>
                
                <div class="mb-3">
                  <label for="mh_category" class="form-label small fw-bold">Category</label>
                  <input type="text" class="form-control form-control-sm" id="mh_category" name="category">
                </div>
                
                <div class="mb-3">
                  <label for="mh_registration_no" class="form-label small fw-bold">Registration Number</label>
                  <input type="text" class="form-control form-control-sm" id="mh_registration_no" name="registration_no">
                </div>
                
                <div class="mb-3">
                  <label for="mh_registration_date" class="form-label small fw-bold">Registration Date</label>
                  <input type="date" class="form-control form-control-sm" id="mh_registration_date" name="registration_date">
                </div>
                
                <div class="mb-3">
                  <label for="mh_registration_expiry" class="form-label small fw-bold">Registration Expiry</label>
                  <input type="date" class="form-control form-control-sm" id="mh_registration_expiry" name="registration_expiry">
                </div>
                
                <div class="mb-3">
                  <label for="mh_status" class="form-label small fw-bold">Registration Status</label>
                  <select class="form-select form-select-sm" id="mh_status" name="status">
                    <option value="Not Expired">Not Expired</option>
                    <option value="Expired">Expired</option>
                  </select>
                </div>
              </div>

              <!-- Right Column: Contact details -->
              <div class="col-12 col-md-6 ps-md-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Contact & Ownership</h6>
                
                <div class="mb-3">
                  <label for="mh_physical_address" class="form-label small fw-bold">Headquarters Address</label>
                  <textarea class="form-control form-control-sm" id="mh_physical_address" name="physical_address" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                  <label for="mh_phone" class="form-label small fw-bold">Phone Number</label>
                  <input type="text" class="form-control form-control-sm" id="mh_phone" name="phone">
                </div>
                
                <div class="mb-3">
                  <label for="mh_email" class="form-label small fw-bold">Email</label>
                  <input type="email" class="form-control form-control-sm" id="mh_email" name="email">
                </div>
                
                <div class="mb-3">
                  <label for="mh_website" class="form-label small fw-bold">Website</label>
                  <input type="text" class="form-control form-control-sm" id="mh_website" name="website">
                </div>
                
                <div class="mb-3">
                  <label for="mh_directors" class="form-label small fw-bold">Directors</label>
                  <textarea class="form-control form-control-sm" id="mh_directors" name="directors" rows="2" placeholder="e.g. John Doe, Jane Smith"></textarea>
                </div>
                
                <div class="mb-3">
                  <label for="mh_shareholding" class="form-label small fw-bold">Shareholding Details</label>
                  <textarea class="form-control form-control-sm" id="mh_shareholding" name="shareholding" rows="2" placeholder="e.g. John Doe - 60%, Jane Smith - 40%"></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm px-3"><i class="ri-save-line me-1"></i>Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- EDIT PUBLICATION MODAL -->
  <div class="modal fade" id="editPublicationModal" tabindex="-1" aria-labelledby="editPublicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow border-0">
        <div class="modal-header bg-dark text-white py-3">
          <h5 class="modal-title fw-bold" id="editPublicationModalLabel">
            <i class="ri-edit-line me-2"></i>Edit Publication / Service Record
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editPublicationForm" method="POST" action="">
          @csrf
          <input type="hidden" id="pub_record_type" name="record_type" value="registration">
          <div class="modal-body p-4" style="max-height: calc(100vh - 210px); overflow-y: auto;">
            <div class="row g-3">
              <!-- Left Column: Details -->
              <div class="col-12 col-md-6 border-end pe-md-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Publication Info</h6>
                
                <div class="mb-3">
                  <label for="pub_publication_name" class="form-label small fw-bold">Publication / Service Name</label>
                  <input type="text" class="form-control form-control-sm" id="pub_publication_name" name="publication_name" required>
                </div>
                
                <div class="mb-3">
                  <label for="pub_type" class="form-label small fw-bold">Type (e.g. newspaper, radio, tv)</label>
                  <input type="text" class="form-control form-control-sm" id="pub_type" name="type" required>
                </div>

                <div class="mb-3">
                  <label for="pub_medium" class="form-label small fw-bold">Medium</label>
                  <select class="form-select form-select-sm" id="pub_medium" name="medium">
                    <option value="Print">Print</option>
                    <option value="Digital">Digital</option>
                    <option value="Dual">Dual</option>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label for="pub_editor_manager" class="form-label small fw-bold">Editor / Manager</label>
                  <input type="text" class="form-control form-control-sm" id="pub_editor_manager" name="editor_manager">
                </div>
                
                <div class="mb-3">
                  <label for="pub_frequency" class="form-label small fw-bold">Frequency</label>
                  <input type="text" class="form-control form-control-sm" id="pub_frequency" name="frequency">
                </div>

                <div class="mb-3">
                  <label for="pub_language" class="form-label small fw-bold">Language</label>
                  <input type="text" class="form-control form-control-sm" id="pub_language" name="language">
                </div>

                <div class="mb-3">
                  <label for="pub_operational_status" class="form-label small fw-bold">Operational Status</label>
                  <select class="form-select form-select-sm" id="pub_operational_status" name="operational_status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
              </div>

              <!-- Right Column: Focus, Scope, Contact -->
              <div class="col-12 col-md-6 ps-md-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Scope & Reach</h6>
                
                <div class="mb-3">
                  <label for="pub_focus" class="form-label small fw-bold">Focus</label>
                  <input type="text" class="form-control form-control-sm" id="pub_focus" name="focus">
                </div>

                <div class="mb-3">
                  <label for="pub_scope" class="form-label small fw-bold">Scope</label>
                  <input type="text" class="form-control form-control-sm" id="pub_scope" name="scope">
                </div>

                <div class="mb-3">
                  <label for="pub_reach_cities" class="form-label small fw-bold">Reach (Cities / Towns)</label>
                  <input type="text" class="form-control form-control-sm" id="pub_reach_cities" name="reach_cities">
                </div>

                <div class="mb-3">
                  <label for="pub_reach_provinces" class="form-label small fw-bold">Provincial Reach</label>
                  <input type="text" class="form-control form-control-sm" id="pub_reach_provinces" name="reach_provinces">
                </div>

                <div class="mb-3">
                  <label for="pub_contacts" class="form-label small fw-bold">Contact Phone</label>
                  <input type="text" class="form-control form-control-sm" id="pub_contacts" name="pub_contacts">
                </div>

                <div class="mb-3">
                  <label for="pub_email" class="form-label small fw-bold">Contact Email</label>
                  <input type="email" class="form-control form-control-sm" id="pub_email" name="pub_email">
                </div>

                <div class="mb-3">
                  <label for="pub_website" class="form-label small fw-bold">Contact Website</label>
                  <input type="text" class="form-control form-control-sm" id="pub_website" name="pub_website">
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm px-3"><i class="ri-save-line me-1"></i>Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  </div>
  </div>
@push('scripts')
<script>

function changePublication(selectElement) {
  const selectedOption = selectElement.options[selectElement.selectedIndex];
  const rowId = selectElement.getAttribute('data-row-id');
  const row = document.getElementById(rowId);
  if (!row) return;

  const type = selectedOption.getAttribute('data-type') || '—';
  const medium = selectedOption.getAttribute('data-medium') || '—';
  const editor = selectedOption.getAttribute('data-editor') || '—';
  const frequency = selectedOption.getAttribute('data-frequency') || '—';
  const language = selectedOption.getAttribute('data-language') || '—';
  const focus = selectedOption.getAttribute('data-focus') || '—';
  const scope = selectedOption.getAttribute('data-scope') || '—';
  const reach = selectedOption.getAttribute('data-reach') || '—';
  const provincialReach = selectedOption.getAttribute('data-provincial-reach') || '—';
  const phone = selectedOption.getAttribute('data-phone') || '—';
  const email = selectedOption.getAttribute('data-email') || '—';
  const website = selectedOption.getAttribute('data-website') || '—';
  const operational = selectedOption.getAttribute('data-operational') || 'yes';
  const editId = selectedOption.getAttribute('data-edit-id') || '';

  row.querySelector('.pub-type').textContent = type;
  
  const mediumCell = row.querySelector('.pub-medium');
  mediumCell.innerHTML = '';
  const medLower = medium.toLowerCase();
  if (medLower === 'both' || medLower === 'dual') {
    mediumCell.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle">Dual</span>';
  } else if (medLower === 'print') {
    mediumCell.innerHTML = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Print</span>';
  } else if (medLower === 'online' || medLower === 'digital') {
    mediumCell.innerHTML = '<span class="badge bg-info-subtle text-info border border-info-subtle">Digital</span>';
  } else {
    mediumCell.innerHTML = '<span class="text-muted">—</span>';
  }

  row.querySelector('.pub-editor').textContent = editor;
  row.querySelector('.pub-frequency').textContent = frequency;
  row.querySelector('.pub-language').textContent = language;
  row.querySelector('.pub-focus').textContent = focus;
  row.querySelector('.pub-scope').textContent = scope;
  row.querySelector('.pub-reach').textContent = reach;
  row.querySelector('.pub-provincial-reach').textContent = provincialReach;
  row.querySelector('.pub-phone').textContent = phone;
  
  const emailCell = row.querySelector('.pub-email');
  if (email && email !== '—') {
    emailCell.innerHTML = `<a href="mailto:${email}" title="${email}" class="text-truncate d-inline-block" style="max-width:160px;">${email}</a>`;
  } else {
    emailCell.innerHTML = '<span class="text-muted">—</span>';
  }

  const websiteCell = row.querySelector('.pub-website');
  if (website && website !== '—') {
    const url = website.startsWith('http') ? website : 'https://' + website;
    websiteCell.innerHTML = `<a href="${url}" target="_blank" title="${website}" class="text-truncate d-inline-block" style="max-width:150px;">${website}</a>`;
  } else {
    websiteCell.innerHTML = '<span class="text-muted">—</span>';
  }

  const opCheckbox = row.querySelector('.pub-operational');
  opCheckbox.checked = (operational === 'yes' || operational === 'active' || operational === 'operational');

  const recordType = selectedOption.getAttribute('data-record-type') || 'registration';
  const editBtn = row.querySelector('.edit-pub-btn');
  editBtn.setAttribute('data-id', editId);
  editBtn.setAttribute('data-record-type', recordType);
  editBtn.setAttribute('data-publication-name', selectedOption.text.trim());
  editBtn.setAttribute('data-type', type);
  editBtn.setAttribute('data-medium', medium);
  editBtn.setAttribute('data-editor-manager', editor);
  editBtn.setAttribute('data-frequency', frequency);
  editBtn.setAttribute('data-language', language);
  editBtn.setAttribute('data-focus', focus);
  editBtn.setAttribute('data-scope', scope);
  editBtn.setAttribute('data-reach-cities', reach);
  editBtn.setAttribute('data-reach-provinces', provincialReach);
  editBtn.setAttribute('data-pub-contacts', phone);
  editBtn.setAttribute('data-pub-email', email);
  editBtn.setAttribute('data-pub-website', website);
  editBtn.setAttribute('data-operational-status', operational);
}

function setActiveTab(tabName) {
  document.getElementById('activeTabInput').value = tabName;
}

document.addEventListener('DOMContentLoaded', function () {
  const tabMap = {
    'registration': 'registration-tab',
    'contact': 'contact-tab',
    'publications': 'publications-tab',
  };
  const activeTab = document.getElementById('activeTabInput') ? document.getElementById('activeTabInput').value : 'registration';
  const tabId = tabMap[activeTab];
  if (tabId) {
    const el = document.getElementById(tabId);
    if (el) {
      const bsTab = new bootstrap.Tab(el);
      bsTab.show();
    }
  }

  document.querySelectorAll('#mediaHousesTabs button[data-bs-toggle="tab"]').forEach(function(btn) {
    btn.addEventListener('shown.bs.tab', function (e) {
      const target = e.target.id.replace('-tab', '');
      document.getElementById('activeTabInput').value = target;
    });
  });

  // Edit Media House click handler
  const editMhModal = new bootstrap.Modal(document.getElementById('editMediaHouseModal'));
  const editMhForm = document.getElementById('editMediaHouseForm');
  const actionTemplateMh = "{{ route('staff.officer.records.mediahouses.update', ['record' => ':id']) }}";

  document.querySelectorAll('.edit-mh-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      editMhForm.action = actionTemplateMh.replace(':id', id);

      document.getElementById('mh_record_type').value = this.getAttribute('data-record-type') || 'registration';
      document.getElementById('mh_entity_name').value = this.getAttribute('data-entity-name') || '';
      document.getElementById('mh_category').value = this.getAttribute('data-category') || '';
      document.getElementById('mh_registration_no').value = this.getAttribute('data-registration-no') || '';
      document.getElementById('mh_registration_date').value = this.getAttribute('data-registration-date') || '';
      document.getElementById('mh_registration_expiry').value = this.getAttribute('data-registration-expiry') || '';
      document.getElementById('mh_status').value = this.getAttribute('data-status') || 'Null';
      document.getElementById('mh_physical_address').value = this.getAttribute('data-physical-address') || '';
      document.getElementById('mh_phone').value = this.getAttribute('data-phone') || '';
      document.getElementById('mh_email').value = this.getAttribute('data-email') || '';
      document.getElementById('mh_website').value = this.getAttribute('data-website') || '';
      document.getElementById('mh_directors').value = this.getAttribute('data-directors') || '';
      document.getElementById('mh_shareholding').value = this.getAttribute('data-shareholding') || '';

      editMhModal.show();
    });
  });

  // Edit Publication click handler
  const editPubModal = new bootstrap.Modal(document.getElementById('editPublicationModal'));
  const editPubForm = document.getElementById('editPublicationForm');
  const actionTemplatePub = "{{ route('staff.officer.records.publications.update', ['publication' => ':id']) }}";

  document.querySelectorAll('.edit-pub-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      editPubForm.action = actionTemplatePub.replace(':id', id);

      document.getElementById('pub_record_type').value = this.getAttribute('data-record-type') || 'registration';
      document.getElementById('pub_publication_name').value = this.getAttribute('data-publication-name') || '';
      document.getElementById('pub_type').value = this.getAttribute('data-type') || '';
      let med = this.getAttribute('data-medium') || '';
      if (med.toLowerCase() === 'both' || med.toLowerCase() === 'dual') med = 'Dual';
      else if (med.toLowerCase() === 'online' || med.toLowerCase() === 'digital') med = 'Digital';
      else if (med.toLowerCase() === 'print') med = 'Print';
      document.getElementById('pub_medium').value = med;
      document.getElementById('pub_editor_manager').value = this.getAttribute('data-editor-manager') || '';
      document.getElementById('pub_frequency').value = this.getAttribute('data-frequency') || '';
      document.getElementById('pub_language').value = this.getAttribute('data-language') || '';
      document.getElementById('pub_focus').value = this.getAttribute('data-focus') || '';
      document.getElementById('pub_scope').value = this.getAttribute('data-scope') || '';
      document.getElementById('pub_reach_cities').value = this.getAttribute('data-reach-cities') || '';
      document.getElementById('pub_reach_provinces').value = this.getAttribute('data-reach-provinces') || '';
      document.getElementById('pub_contacts').value = this.getAttribute('data-pub-contacts') || '';
      document.getElementById('pub_email').value = this.getAttribute('data-pub-email') || '';
      document.getElementById('pub_website').value = this.getAttribute('data-pub-website') || '';
      document.getElementById('pub_operational_status').value = this.getAttribute('data-operational-status') || 'active';

      editPubModal.show();
    });
  });
});



// Change registration director row when dropdown selection changes
function changeRegistrationDirector(sel) {
  const opt = sel.options[sel.selectedIndex];
  const rowId = sel.getAttribute('data-row-id');
  const row = document.getElementById(rowId);
  if (!row) return;

  const gender = opt.getAttribute('data-gender') || '—';
  const gLower = gender.toLowerCase();
  let genderHtml;
  if (gLower === 'male' || gLower === 'm') {
    genderHtml = '<span class="badge bg-info-subtle text-info border border-info-subtle">♂ Male</span>';
  } else if (gLower === 'female' || gLower === 'f') {
    genderHtml = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">♀ Female</span>';
  } else {
    genderHtml = '<span class="text-muted">' + gender + '</span>';
  }
  row.querySelector('.reg-dir-gender').innerHTML = genderHtml;

  const share = opt.getAttribute('data-shareholding') || '—';
  row.querySelector('.reg-dir-shareholding').innerHTML = (share !== '—')
    ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' + share + '</span>'
    : '<span class="text-muted">—</span>';

  const nationality = opt.getAttribute('data-nationality') || '—';
  row.querySelector('.reg-dir-nationality').textContent = nationality;

  const idNumber = opt.getAttribute('data-id-number') || '—';
  row.querySelector('.reg-dir-id-number').textContent = idNumber;

  const phone = opt.getAttribute('data-phone') || '—';
  row.querySelector('.reg-dir-phone').textContent = phone;

  const email = opt.getAttribute('data-email') || '—';
  const emailCell = row.querySelector('.reg-dir-email');
  if (email && email !== '—') {
    emailCell.innerHTML = `<a href="mailto:${email}" class="text-decoration-none">${email}</a>`;
  } else {
    emailCell.innerHTML = '<span class="text-muted">—</span>';
  }

  const address = opt.getAttribute('data-address') || '—';
  row.querySelector('.reg-dir-address').innerHTML = `<span title="${address}">${address.length > 40 ? address.substring(0, 40) + '…' : address}</span>`;

  const occupation = opt.getAttribute('data-occupation') || '—';
  row.querySelector('.reg-dir-occupation').textContent = occupation;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function togglePubs(id) {
  var row = document.getElementById('pubs-' + id);
  if (row) row.classList.toggle('d-none');
}

document.addEventListener('DOMContentLoaded', function() {
  const monthlyData = @json($analytics['monthly'] ?? []);
  const categoryData = @json($analytics['categories'] ?? []);
  const yearlyData = @json($analytics['yearly'] ?? []);
  const provinceData = @json($analytics['provinces'] ?? []);
  const statusData = @json($analytics['statuses'] ?? []);

  const colors = ['#f5c518','#1a1a1a','#3b82f6','#10b981','#ef4444','#8b5cf6','#f59e0b','#6366f1','#ec4899','#14b8a6','#f97316','#06b6d4'];

  if (Object.keys(monthlyData).length > 0) {
    new Chart(document.getElementById('mhMonthlyChart'), {
      type: 'line',
      data: {
        labels: Object.keys(monthlyData),
        datasets: [{
          label: 'Registrations',
          data: Object.values(monthlyData),
          borderColor: '#1a1a1a',
          backgroundColor: 'rgba(245,197,24,0.15)',
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#f5c518',
          pointBorderColor: '#1a1a1a',
          pointRadius: 4,
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }

  if (Object.keys(yearlyData).length > 0) {
    new Chart(document.getElementById('mhYearlyChart'), {
      type: 'bar',
      data: {
        labels: Object.keys(yearlyData),
        datasets: [{
          label: 'Registrations',
          data: Object.values(yearlyData),
          backgroundColor: '#f5c518',
          borderColor: '#1a1a1a',
          borderWidth: 1,
          borderRadius: 4,
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }

  if (Object.keys(categoryData).length > 0) {
    new Chart(document.getElementById('mhCategoryChart'), {
      type: 'doughnut',
      data: {
        labels: Object.keys(categoryData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{ data: Object.values(categoryData), backgroundColor: colors.slice(0, Object.keys(categoryData).length) }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8 } } } }
    });
  }

  if (Object.keys(provinceData).length > 0) {
    new Chart(document.getElementById('mhProvinceChart'), {
      type: 'doughnut',
      data: {
        labels: Object.keys(provinceData),
        datasets: [{ data: Object.values(provinceData), backgroundColor: colors.slice(0, Object.keys(provinceData).length) }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8 } } } }
    });
  }

  if (Object.keys(statusData).length > 0) {
    new Chart(document.getElementById('mhStatusChart'), {
      type: 'doughnut',
      data: {
        labels: Object.keys(statusData).map(s => s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())),
        datasets: [{ data: Object.values(statusData), backgroundColor: colors.slice(0, Object.keys(statusData).length) }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 9 }, padding: 6 } } } }
    });
  }
});
</script>
@endpush
@endsection
