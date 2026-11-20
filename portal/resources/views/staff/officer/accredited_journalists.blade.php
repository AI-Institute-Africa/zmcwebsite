@extends('layouts.portal')
@section('title', 'Accredited Media Practitioners — Records')

@section('content')
  @include('staff.partials.dashboard_theme')
  <div class="staff-dashboard-theme">
  <div class="records-page zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <style>
      .records-page{display:flex;flex-direction:column}.records-heading{order:0;position:relative;overflow:hidden;padding:24px 28px;border-radius:24px;background:linear-gradient(120deg,#0e1b2b 0%,#173b3d 55%,#1f6f57 100%);color:#fff;box-shadow:0 18px 40px rgba(15,35,48,.18);margin-bottom:24px}.records-heading:after{content:"";position:absolute;right:-55px;top:-95px;width:220px;height:220px;border-radius:50%;background:rgba(242,201,76,.18)}.records-heading>*{position:relative;z-index:1}.records-heading h4{font-size:clamp(1.65rem,2.7vw,2.25rem)!important;letter-spacing:-.03em;color:#fff!important}.records-heading .text-muted{color:rgba(255,255,255,.76)!important}.records-heading .btn-white{background:rgba(255,255,255,.95);border-color:transparent;color:#173b3d}.records-heading .btn-outline-success{background:rgba(255,255,255,.95);border-color:transparent;color:#176a4f}.records-heading .btn{border-radius:10px;font-weight:700}.records-stats{order:1;display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-left:0!important;margin-right:0!important}.records-stats>div{width:auto!important;max-width:none!important;padding:0!important}.records-stats>div>.zmc-card{border:1px solid #e8edf1!important;border-radius:18px!important;padding:17px!important;box-shadow:0 10px 24px rgba(15,35,48,.07);background:#fff;min-height:124px;height:100%}.records-stats>div:nth-child(1)>.zmc-card,.records-stats>div:nth-child(2)>.zmc-card{border-top:4px solid var(--zmc-accent)!important}.records-stats>div:nth-child(3)>.zmc-card{border-top:4px solid #2876b8!important}.records-stats>div:nth-child(4)>.zmc-card{border-top:4px solid #b27b00!important}.records-stats>div:nth-child(5)>.zmc-card,.records-stats>div:nth-child(6)>.zmc-card{border-top:4px solid #2876b8!important}.records-stats>div:nth-child(7)>.zmc-card{border-top:4px solid #c2413b!important}.records-stats .h3{font-size:1.8rem}.records-chart-row{order:5}.records-chart-row .zmc-card{border-radius:22px!important;border:1px solid #e8edf1!important;box-shadow:0 10px 24px rgba(15,35,48,.07)!important}.records-tabs{order:2}.records-tabs ul{border-radius:14px!important}.records-tabs .nav-link{border-radius:10px!important;font-weight:750}.records-filter{order:3;border-radius:22px!important;border:1px solid #e8edf1!important;background:#f8faf9}.records-table{order:4;border-radius:22px!important;border:1px solid #e8edf1!important;box-shadow:0 10px 24px rgba(15,35,48,.07);overflow:hidden}.records-table .table thead th{background:#f4f8f6;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em}.records-table .table tbody tr:hover{background:#fbfdfc}@media(max-width:1050px){.records-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.records-stats{grid-template-columns:1fr}}
      .records-stats{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 24px!important}.record-kpi{display:flex;align-items:center;gap:13px;border:1px solid #e8edf1;border-radius:18px;background:linear-gradient(145deg,#fff 0%,#fbfdfc 100%);padding:17px 18px;min-height:112px;box-shadow:0 10px 24px rgba(15,35,48,.07);transition:transform .18s ease,box-shadow .18s ease;position:relative;overflow:hidden}.record-kpi:after{content:"";position:absolute;width:88px;height:88px;right:-38px;bottom:-42px;border-radius:50%;background:rgba(250,204,21,.1)}.record-kpi:hover{transform:translateY(-2px);box-shadow:0 15px 28px rgba(15,35,48,.11)}.record-kpi-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#fff8d9;color:#927300;font-size:1.2rem;flex:0 0 auto;position:relative;z-index:1}.record-kpi-icon.green{background:#e8f5ed;color:#247a54}.record-kpi-icon.blue{background:#e6f1fb;color:#2876b8}.record-kpi-icon.gold{background:#fff3d7;color:#a46d0a}.record-kpi-label{text-transform:uppercase;letter-spacing:.06em;color:#64756f;font-size:.68rem;font-weight:800;position:relative;z-index:1}.record-kpi-value{font-size:1.85rem;line-height:1.05;font-weight:850;color:#172b2b;margin-top:4px;position:relative;z-index:1}.record-kpi-note{font-size:.73rem;color:#83918c;margin-top:5px;position:relative;z-index:1}.records-secondary{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px!important}.records-secondary span{display:inline-flex;align-items:center;gap:8px;border:1px solid #e3ebe7;background:#f8fbf9;border-radius:999px;padding:8px 13px;color:#64756f;font-size:.78rem;font-weight:750}.records-secondary span i{color:#2876b8}.records-secondary span b{color:#172b2b}.records-secondary .danger{border-color:#f0d7d5;background:#fff7f6}.records-secondary .danger i,.records-secondary .danger b{color:#bd4b42}@media(max-width:1050px){.records-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.records-stats{grid-template-columns:1fr}}
    </style>

    <div class="records-heading d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div>
        <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
          Accredited Media Practitioners
        </h1>
        <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
          <i class="ri-information-line me-1"></i>
          Comprehensive database with analytics, filtering, and multi-format export. Historical yearly records preserved.
        </div>
      </div>

      <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="dropdown">
          <button class="btn btn-outline-success border shadow-sm btn-sm px-3 dropdown-toggle" type="button"
            data-bs-toggle="dropdown">
            <i class="ri-download-line me-1"></i> Export
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li>
              <a class="dropdown-item"
                href="{{ route('staff.officer.records.export', ['type' => 'journalists']) }}?format=csv&{{ http_build_query(request()->only(['search', 'category', 'sex', 'nationality', 'province', 'medium', 'organisation', 'year', 'collection_status', 'expiry_status', 'status_filter'])) }}">
                <i class="ri-file-text-line me-2 text-success"></i> CSV
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                href="{{ route('staff.officer.records.export', ['type' => 'journalists']) }}?format=excel&{{ http_build_query(request()->only(['search', 'category', 'sex', 'nationality', 'province', 'medium', 'organisation', 'year', 'collection_status', 'expiry_status', 'status_filter'])) }}">
                <i class="ri-file-excel-2-line me-2 text-success"></i> Excel
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                href="{{ route('staff.officer.records.export', ['type' => 'journalists']) }}?format=pdf&{{ http_build_query(request()->only(['search', 'category', 'sex', 'nationality', 'province', 'medium', 'organisation', 'year', 'collection_status', 'expiry_status', 'status_filter'])) }}"
                target="_blank">
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

    <div class="records-stats mb-3">
      <div class="record-kpi"><div class="record-kpi-icon"><i class="ri-database-2-line"></i></div><div><div class="record-kpi-label">Total records</div><div class="record-kpi-value">{{ number_format($stats['total'] ?? 0) }}</div><div class="record-kpi-note">All accredited practitioners</div></div></div>
      <div class="record-kpi"><div class="record-kpi-icon green"><i class="ri-shield-check-line"></i></div><div><div class="record-kpi-label">Active credentials</div><div class="record-kpi-value">{{ number_format($stats['active'] ?? 0) }}</div><div class="record-kpi-note">Currently valid</div></div></div>
      <div class="record-kpi"><div class="record-kpi-icon blue"><i class="ri-checkbox-circle-line"></i></div><div><div class="record-kpi-label">Collected</div><div class="record-kpi-value">{{ number_format($stats['collected'] ?? 0) }}</div><div class="record-kpi-note">Cards issued to holders</div></div></div>
      <div class="record-kpi"><div class="record-kpi-icon gold"><i class="ri-inbox-line"></i></div><div><div class="record-kpi-label">Awaiting collection</div><div class="record-kpi-value">{{ number_format($stats['uncollected'] ?? 0) }}</div><div class="record-kpi-note">Ready for collection</div></div></div>
    </div>
    <div class="records-secondary mb-4"><span><i class="ri-map-pin-line"></i> Harare <b>{{ $stats['harare'] ?? 0 }}</b></span><span><i class="ri-map-pin-2-line"></i> Bulawayo <b>{{ $stats['bulawayo'] ?? 0 }}</b></span><span class="danger"><i class="ri-alarm-warning-line"></i> Expired <b>{{ $stats['expired'] ?? 0 }}</b></span></div>

    <div class="records-chart-row row g-3 mb-4">
      <div class="col-12 col-lg-6">
        <div class="zmc-card p-3 h-100">
          <h2 class="fw-bold mb-3"><i class="ri-line-chart-line me-2"></i> Monthly Submissions Trend</h2>
          <canvas id="monthlyChart" height="200"></canvas>
        </div>
      </div>
      <div class="col-12 col-lg-6">
        <div class="zmc-card p-3 h-100">
          <h3 class="fw-bold mb-3"><i class="ri-bar-chart-grouped-line me-2"></i> Yearly Totals</h3>
          <canvas id="yearlyChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <div class="records-chart-row row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="zmc-card p-3 h-100">
          <h4 class="fw-bold mb-3"><i class="ri-pie-chart-line me-2"></i> By Category</h4>
          <canvas id="categoryChart" height="200"></canvas>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="zmc-card p-3 h-100">
          <h5 class="fw-bold mb-3"><i class="ri-donut-chart-line me-2"></i> By Sex</h5>
          <canvas id="sexChart" height="200"></canvas>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="zmc-card p-3 h-100">
          <h6 class="fw-bold mb-3"><i class="ri-donut-chart-line me-2"></i> Status Distribution</h6>
          <canvas id="statusChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <div class="zmc-tabs-wrapper mb-4">
      <ul class="nav nav-pills gap-2 p-2 bg-white rounded-pill d-inline-flex border shadow-sm">
        <li class="nav-item">
          <a class="nav-link rounded-pill px-4 fw-bold {{ $tab === 'local' ? 'active bg-primary text-white' : 'text-muted' }}"
            href="{{ route('staff.officer.records.accredited-journalists', array_merge(request()->query(), ['tab' => 'local'])) }}">
            <i class="ri-map-pin-user-line me-1"></i> Local Practitioners
            <span
              class="badge {{ $tab === 'local' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ number_format($tabCounts['local'] ?? 0) }}</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link rounded-pill px-4 fw-bold {{ $tab === 'foreign' ? 'active bg-primary text-white' : 'text-muted' }}"
            href="{{ route('staff.officer.records.accredited-journalists', array_merge(request()->query(), ['tab' => 'foreign'])) }}">
            <i class="ri-global-line me-1"></i> Foreign Practitioners
            <span
              class="badge {{ $tab === 'foreign' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ number_format($tabCounts['foreign'] ?? 0) }}</span>
          </a>
        </li>
      </ul>
    </div>

    <div class="records-filter zmc-card mb-4">
      <h6 class="fw-bold mb-3"><i class="ri-filter-3-line me-2"></i> Filters</h6>
      <form method="GET" action="{{ route('staff.officer.records.accredited-journalists') }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="row g-2">
          <div class="col-12 col-md-4 col-lg-3">
            <label class="form-label small fw-bold">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
              placeholder="Name, ID, Email, Accreditation #..." value="{{ request('search') }}">
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-bold">Category</label>
            <select name="category" class="form-select form-select-sm">
              <option value="">All</option>
              @foreach($filterOptions['categories'] ?? [] as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
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
            <label class="form-label small fw-bold">Sex</label>
            <select name="sex" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="Male" {{ request('sex') === 'Male' ? 'selected' : '' }}>Male</option>
              <option value="Female" {{ request('sex') === 'Female' ? 'selected' : '' }}>Female</option>
            </select>
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-bold">Extensions</label>
            <select name="is_extension" class="form-select form-select-sm">
              <option value="">All</option>
              <option value="yes" {{ request('is_extension') === 'yes' ? 'selected' : '' }}>Yes</option>
              <option value="no" {{ request('is_extension') === 'no' ? 'selected' : '' }}>No</option>
            </select>
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-bold">Accredited By</label>
            <select name="officer_id" class="form-select form-select-sm">
              <option value="">All Officers</option>
              @foreach($filterOptions['officers'] ?? [] as $offId => $offName)
                <option value="{{ $offId }}" {{ request('officer_id') == $offId ? 'selected' : '' }}>{{ $offName }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-bold">Month</label>
            <select name="month" class="form-select form-select-sm">
              <option value="">All Months</option>
              @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                  {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-bold">Organisation</label>
            <input type="text" name="organisation" class="form-select form-select-sm"
              value="{{ request('organisation') }}" placeholder="Type name...">
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-bold">Nationality</label>
            <input type="text" name="nationality" class="form-select form-select-sm" value="{{ request('nationality') }}"
              placeholder="Type country...">
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small fw-bold">Collection Region</label>
            <select name="collection_region" class="form-select form-select-sm">
              <option value="">All Regions</option>
              @foreach($filterOptions['collection_regions'] ?? [] as $reg)
                <option value="{{ $reg }}" {{ request('collection_region') === $reg ? 'selected' : '' }}>{{ $reg }}</option>
              @endforeach
            </select>
          </div>
          @if($hasRecords ?? false)
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
              <label class="form-label small fw-bold">Medium</label>
              <select name="medium" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($filterOptions['media'] ?? [] as $med)
                  <option value="{{ $med }}" {{ request('medium') === $med ? 'selected' : '' }}>{{ ucwords($med) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label small fw-bold">Collection</label>
              <select name="collection_status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="collected" {{ request('collection_status') === 'collected' ? 'selected' : '' }}>Collected
                </option>
                <option value="uncollected" {{ request('collection_status') === 'uncollected' ? 'selected' : '' }}>Uncollected
                </option>
              </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label small fw-bold">Expiry</label>
              <select name="expiry_status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="active" {{ request('expiry_status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="expiring_soon" {{ request('expiry_status') === 'expiring_soon' ? 'selected' : '' }}>Expiring
                  Soon (90d)</option>
                <option value="expired" {{ request('expiry_status') === 'expired' ? 'selected' : '' }}>Expired</option>
              </select>
            </div>
          @else
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label small fw-bold">Status</label>
              <select name="status_filter" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="approved" {{ request('status_filter') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="pending" {{ request('status_filter') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="returned" {{ request('status_filter') === 'returned' ? 'selected' : '' }}>Returned</option>
              </select>
            </div>
          @endif
          <div class="col-12 col-md-3 col-lg-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
              <i class="ri-search-line me-1"></i> Filter
            </button>
          </div>
        </div>
        @if(request()->hasAny(['search', 'category', 'sex', 'nationality', 'province', 'medium', 'organisation', 'year', 'collection_status', 'expiry_status', 'status_filter']))
          <div class="mt-2">
            <a href="{{ route('staff.officer.records.accredited-journalists') }}" class="btn btn-sm btn-outline-secondary">
              <i class="ri-close-line me-1"></i> Clear Filters
            </a>
          </div>
        @endif
      </form>
    </div>

    @if(($hasRecords ?? false) && $journalists->where('collected_at', null)->count() > 0)
      <div class="mb-3">
        <form method="POST" action="{{ route('staff.officer.records.send-collection-notification') }}" class="d-inline">
          @csrf
          <input type="hidden" name="record_type" value="accreditation">
          <button type="submit" class="btn btn-success btn-sm shadow-sm">
            <i class="ri-mail-send-line me-1"></i> Send Collection Reminders
          </button>
        </form>
      </div>
    @endif

    <div class="records-table zmc-card p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:11px;">
          <thead class="bg-light text-nowrap">
            <tr>
              <th class="ps-3">Name</th>
              <th>Reference Number</th>
              <th>Accreditation Number</th>
              <th>Organisation</th>
              <th>Category</th>
              <th>DOB</th>
              @if($tab === 'foreign')
                <th>Passport Number</th>
              @else
                <th>National ID</th>
              @endif
              <th>Nationality</th>
              <th>Sex</th>
              @if($tab === 'foreign')
                <th>Address in Zimbabwe</th>
              @else
                <th>Address</th>
                <th>Town / City</th>
                <th>Province</th>
              @endif
              <th>Collection Region</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Designation</th>
              <th>Application Date</th>
              @if($tab === 'foreign')
                <th>Valid From</th>
                <th class="pe-3">Valid To</th>
                <th>Extensions</th>
              @else
                <th>Accreditation Date</th>
              @endif
              <th>Accredited By</th>
              <th>Approved By</th>
              <th class="text-center pe-3" style="width: 80px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            @if($hasRecords ?? false)
              @foreach($journalists as $r)
                <tr>
                  <td class="ps-3 fw-bold text-nowrap">{{ $r->full_name }}</td>
                  <td class="text-nowrap">{{ $r->application?->reference ?? '—' }}</td>
                  <td class="text-nowrap">{{ $r->accreditation_number ?? $r->certificate_no ?? 'Pending' }}</td>
                  <td class="text-nowrap">{{ $r->organisation ?? '—' }}</td>
                  <td class="text-nowrap"><span class="badge bg-light text-dark border">{{ $r->category ?? '—' }}</span></td>
                  <td class="text-nowrap">{{ optional($r->date_of_birth)->format('d/m/Y') ?? '—' }}</td>
                  @if($tab === 'foreign')
                    <td class="text-nowrap">{{ $r->passport_number ?? '—' }}</td>
                  @else
                    <td class="text-nowrap">{{ $r->id_number ?? $r->passport_number ?? '—' }}</td>
                  @endif
                  <td class="text-nowrap">{{ $r->nationality ?? '—' }}</td>
                  <td class="text-nowrap">{{ $r->sex ?? '—' }}</td>

                  @if($tab === 'foreign')
                    <td class="text-nowrap text-truncate" style="max-width: 200px;"
                      title="{{ $r->zim_local_address ?? $r->physical_address }}">
                      {{ $r->zim_local_address ?? $r->physical_address ?? '—' }}</td>
                  @else
                    <td class="text-nowrap text-truncate" style="max-width: 200px;" title="{{ $r->physical_address }}">
                      {{ $r->physical_address ?? '—' }}</td>
                    <td class="text-nowrap">{{ $r->town ?? '—' }}</td>
                    <td class="text-nowrap">{{ $r->province ?? '—' }}</td>
                  @endif

                  <td class="text-nowrap">{{ $r->application?->collection_region ?? '—' }}</td>
                  <td class="text-nowrap">{{ $r->phone ?? $r->cell ?? '—' }}</td>
                  <td class="text-nowrap">{{ $r->email ?? '—' }}</td>
                  <td class="text-nowrap">{{ $r->designation ?? '—' }}</td>
                  <td class="text-nowrap">{{ optional($r->application?->submitted_at)->format('d/m/Y') ?? '—' }}</td>

                  @if($tab === 'foreign')
                    <td class="text-nowrap">{{ optional($r->issued_at)->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-nowrap">{{ optional($r->expires_at)->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-nowrap text-center">
                      @if(($r->extensions_count ?? 0) > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Yes</span>
                      @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">No</span>
                      @endif
                    </td>
                  @else
                    <td class="text-nowrap">{{ optional($r->issued_at)->format('d/m/Y') ?? '—' }}</td>
                  @endif

                  <td class="text-nowrap text-muted">{{ $r->application?->assignedOfficer?->name ?? '—' }}</td>
                  <td class="text-nowrap text-muted">
                    {{ $r->application?->registrarApprover?->name ?? $r->application?->registrar_reviewed_by ?? '—' }}</td>
                  <td class="pe-3 text-center">
                    <button class="btn btn-sm btn-outline-primary btn-edit-journalist px-2 py-1" 
                      style="font-size: 10px;"
                      data-id="{{ $r->id }}"
                      data-record-type="accreditation"
                      data-first-name="{{ $r->first_name }}"
                      data-surname="{{ $r->surname }}"
                      data-accreditation-number="{{ $r->accreditation_number ?? $r->certificate_no }}"
                      data-organisation="{{ $r->organisation }}"
                      data-category="{{ $r->category }}"
                      data-date-of-birth="{{ optional($r->date_of_birth)->format('Y-m-d') ?? '' }}"
                      data-id-number="{{ $r->id_number }}"
                      data-passport-number="{{ $r->passport_number }}"
                      data-nationality="{{ $r->nationality }}"
                      data-sex="{{ $r->sex }}"
                      data-physical-address="{{ $r->physical_address }}"
                      data-town="{{ $r->town }}"
                      data-province="{{ $r->province }}"
                      data-phone="{{ $r->phone ?? $r->cell }}"
                      data-email="{{ $r->email }}"
                      data-designation="{{ $r->designation }}"
                      data-status="{{ $r->status }}"
                      data-collection-region="{{ $r->application?->collection_region }}">
                      <i class="ri-edit-line"></i> Edit
                    </button>
                  </td>
                </tr>
              @endforeach
            @else
              @foreach($journalists as $app)
                @php
                  $fd = is_array($app->form_data) ? $app->form_data : (json_decode($app->form_data ?? '{}', true) ?? []);
                  $category = $app->media_house_category_code ?? $app->accreditation_category_code ?? $fd['category'] ?? $fd['medium_type'] ?? '—';
                  $dob = $fd['dob'] ?? $fd['date_of_birth'] ?? '—';
                  $idNum = $fd['id_number'] ?? $fd['national_id'] ?? $fd['id_passport_number'] ?? '—';
                  $passNum = $fd['passport_number'] ?? $fd['passport_no'] ?? $idNum;
                  $nationality = $fd['nationality'] ?? $fd['origin'] ?? '—';
                  $sex = $fd['sex'] ?? $fd['gender'] ?? '—';
                  $address = $fd['address'] ?? $fd['residential_address'] ?? '—';
                  $zimAddress = $fd['zim_local_address'] ?? $fd['address_in_zimbabwe'] ?? $address;
                  $town = $fd['town'] ?? $fd['city'] ?? $fd['zim_local_address'] ?? '—';
                  $province = $fd['province'] ?? '—';
                  $region = $app->collection_region ?? '—';
                  $phone = $fd['phone'] ?? $fd['phone_number'] ?? $fd['cell_number'] ?? '—';
                  $email = $app->applicant?->email ?? $fd['email'] ?? '—';
                  $designation = $fd['designation'] ?? $fd['position'] ?? '—';
                  $org = $fd['employer'] ?? $fd['organisation_name'] ?? $fd['media_org'] ?? '—';

                  $accNum = $app->card_number ?? $app->certificate_number ?? 'Pending';
                  $accDate = $app->approved_at ? $app->approved_at->format('d/m/Y') : '—';
                  $officer = $app->assignedOfficer?->name ?? '—';
                  $approver = $app->registrarApprover?->name ?? $app->registrar_reviewed_by ?? '—';

                  $validFrom = $app->approved_at ? $app->approved_at->format('d/m/Y') : '—';
                  $validTo = $app->expiry_date ? \Carbon\Carbon::parse($app->expiry_date)->format('d/m/Y') : '—';
                  $extensionsCount = $app->extensions_count ?? 0;
                @endphp
                <tr>
                  <td class="ps-3 fw-bold text-nowrap">
                    {{ $app->applicant?->name ?? ($fd['first_name'] ?? '') . ' ' . ($fd['surname'] ?? '') }}</td>
                  <td class="text-nowrap">{{ $app->reference ?? '#' . $app->id }}</td>
                  <td class="text-nowrap">{{ $accNum }}</td>
                  <td class="text-nowrap">{{ $org }}</td>
                  <td class="text-nowrap"><span class="badge bg-light text-dark border">{{ $category }}</span></td>
                  <td class="text-nowrap">{{ $dob }}</td>

                  @if($tab === 'foreign')
                    <td class="text-nowrap">{{ $passNum }}</td>
                  @else
                    <td class="text-nowrap">{{ $idNum }}</td>
                  @endif

                  <td class="text-nowrap">{{ $nationality }}</td>
                  <td class="text-nowrap">{{ $sex }}</td>

                  @if($tab === 'foreign')
                    <td class="text-nowrap text-truncate" style="max-width: 200px;" title="{{ $zimAddress }}">{{ $zimAddress }}
                    </td>
                  @else
                    <td class="text-nowrap text-truncate" style="max-width: 200px;" title="{{ $address }}">{{ $address }}</td>
                    <td class="text-nowrap">{{ $town }}</td>
                    <td class="text-nowrap">{{ $province }}</td>
                  @endif

                  <td class="text-nowrap">{{ $region }}</td>
                  <td class="text-nowrap">{{ $phone }}</td>
                  <td class="text-nowrap">{{ $email }}</td>
                  <td class="text-nowrap">{{ $designation }}</td>
                  <td class="text-nowrap">{{ optional($app->submitted_at)->format('d/m/Y') ?? '—' }}</td>

                  @if($tab === 'foreign')
                    <td class="text-nowrap">{{ $validFrom }}</td>
                    <td class="text-nowrap">{{ $validTo }}</td>
                    <td class="text-nowrap text-center">
                      @if($extensionsCount > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Yes</span>
                      @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">No</span>
                      @endif
                    </td>
                  @else
                    <td class="text-nowrap">{{ $accDate }}</td>
                  @endif

                  <td class="text-nowrap text-muted">{{ $officer }}</td>
                  <td class="text-nowrap text-muted">{{ $approver }}</td>
                  <td class="pe-3 text-center">
                    <button class="btn btn-sm btn-outline-primary btn-edit-journalist px-2 py-1" 
                      style="font-size: 10px;"
                      data-id="{{ $app->id }}"
                      data-record-type="application"
                      data-first-name="{{ $fd['first_name'] ?? '' }}"
                      data-surname="{{ $fd['surname'] ?? $fd['last_name'] ?? '' }}"
                      data-accreditation-number="{{ $accNum }}"
                      data-organisation="{{ $org }}"
                      data-category="{{ $category }}"
                      data-date-of-birth="{{ $dob }}"
                      data-id-number="{{ $idNum }}"
                      data-passport-number="{{ $passNum }}"
                      data-nationality="{{ $nationality }}"
                      data-sex="{{ $sex }}"
                      data-physical-address="{{ $address }}"
                      data-town="{{ $town }}"
                      data-province="{{ $province }}"
                      data-phone="{{ $phone }}"
                      data-email="{{ $email }}"
                      data-designation="{{ $designation }}"
                      data-status="{{ $app->status }}"
                      data-collection-region="{{ $region }}">
                      <i class="ri-edit-line"></i> Edit
                    </button>
                  </td>
                </tr>
              @endforeach
            @endif
          </tbody>
        </table>
      </div>

      @if($journalists->hasPages())
        <div class="p-3 border-top">
          {{ $journalists->links() }}
        </div>
      @endif
    </div>

  </div>

  <!-- EDIT JOURNALIST MODAL -->
  <div class="modal fade" id="editJournalistModal" tabindex="-1" aria-labelledby="editJournalistModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow border-0">
        <div class="modal-header bg-dark text-white py-3">
          <h5 class="modal-title fw-bold" id="editJournalistModalLabel">
            <i class="ri-edit-line me-2"></i>Edit Practitioner Record
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editJournalistForm" method="POST" action="">
          @csrf
          <input type="hidden" id="j_record_type" name="record_type" value="accreditation">
          <div class="modal-body p-4" style="max-height: calc(100vh - 210px); overflow-y: auto;">
            <div class="row g-3">
              <!-- Left Column: Personal details -->
              <div class="col-12 col-md-6 border-end pe-md-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Personal & Identity Details</h6>
                
                <div class="mb-3">
                  <label for="j_first_name" class="form-label small fw-bold">First Name</label>
                  <input type="text" class="form-control form-control-sm" id="j_first_name" name="first_name" required>
                </div>
                
                <div class="mb-3">
                  <label for="j_surname" class="form-label small fw-bold">Surname</label>
                  <input type="text" class="form-control form-control-sm" id="j_surname" name="surname" required>
                </div>

                <div class="mb-3">
                  <label for="j_date_of_birth" class="form-label small fw-bold">Date of Birth</label>
                  <input type="date" class="form-control form-control-sm" id="j_date_of_birth" name="date_of_birth">
                </div>

                <div class="mb-3">
                  <label for="j_sex" class="form-label small fw-bold">Sex</label>
                  <select class="form-select form-select-sm" id="j_sex" name="sex">
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="j_nationality" class="form-label small fw-bold">Nationality</label>
                  <input type="text" class="form-control form-control-sm" id="j_nationality" name="nationality">
                </div>

                <div class="mb-3">
                  <label for="j_id_number" class="form-label small fw-bold">National ID</label>
                  <input type="text" class="form-control form-control-sm" id="j_id_number" name="id_number">
                </div>

                <div class="mb-3">
                  <label for="j_passport_number" class="form-label small fw-bold">Passport Number</label>
                  <input type="text" class="form-control form-control-sm" id="j_passport_number" name="passport_number">
                </div>
              </div>

              <!-- Right Column: Professional & Location details -->
              <div class="col-12 col-md-6 ps-md-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Professional & Contact Details</h6>
                
                <div class="mb-3">
                  <label for="j_organisation" class="form-label small fw-bold">Employer / Organisation</label>
                  <input type="text" class="form-control form-control-sm" id="j_organisation" name="organisation">
                </div>

                <div class="mb-3">
                  <label for="j_designation" class="form-label small fw-bold">Designation</label>
                  <input type="text" class="form-control form-control-sm" id="j_designation" name="designation">
                </div>

                <div class="mb-3">
                  <label for="j_category" class="form-label small fw-bold">Accreditation Category</label>
                  <input type="text" class="form-control form-control-sm" id="j_category" name="category" placeholder="e.g. JE, JF...">
                </div>

                <div class="mb-3">
                  <label for="j_accreditation_number" class="form-label small fw-bold">Accreditation Number / Certificate Number</label>
                  <input type="text" class="form-control form-control-sm" id="j_accreditation_number" name="accreditation_number">
                </div>

                <div class="mb-3">
                  <label for="j_phone" class="form-label small fw-bold">Phone Number</label>
                  <input type="text" class="form-control form-control-sm" id="j_phone" name="phone">
                </div>

                <div class="mb-3">
                  <label for="j_email" class="form-label small fw-bold">Email Address</label>
                  <input type="email" class="form-control form-control-sm" id="j_email" name="email">
                </div>

                <div class="mb-3">
                  <label for="j_physical_address" class="form-label small fw-bold">Physical Address</label>
                  <textarea class="form-control form-control-sm" id="j_physical_address" name="physical_address" rows="1"></textarea>
                </div>

                <div class="row g-2">
                  <div class="col-6">
                    <label for="j_town" class="form-label small fw-bold">Town / City</label>
                    <input type="text" class="form-control form-control-sm" id="j_town" name="town">
                  </div>
                  <div class="col-6">
                    <label for="j_province" class="form-label small fw-bold">Province</label>
                    <input type="text" class="form-control form-control-sm" id="j_province" name="province">
                  </div>
                </div>

                <div class="row g-2 mt-2">
                  <div class="col-6">
                    <label for="j_collection_region" class="form-label small fw-bold">Collection Region</label>
                    <select class="form-select form-select-sm" id="j_collection_region" name="collection_region">
                      <option value="">Select</option>
                      <option value="Harare">Harare</option>
                      <option value="Bulawayo">Bulawayo</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <label for="j_status" class="form-label small fw-bold">Status</label>
                    <input type="text" class="form-control form-control-sm" id="j_status" name="status">
                  </div>
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

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const monthlyData = @json($analytics['monthly'] ?? []);
        const categoryData = @json($analytics['categories'] ?? []);
        const yearlyData = @json($analytics['yearly'] ?? []);
        const statusData = @json($analytics['statuses'] ?? []);
        const sexData = @json($analytics['sex'] ?? []);

        const colors = ['#f5c518', '#1a1a1a', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#f59e0b', '#6366f1', '#ec4899', '#14b8a6', '#f97316', '#06b6d4'];

        if (Object.keys(monthlyData).length > 0) {
          new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
              labels: Object.keys(monthlyData),
              datasets: [{
                label: 'Submissions',
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
          new Chart(document.getElementById('yearlyChart'), {
            type: 'bar',
            data: {
              labels: Object.keys(yearlyData),
              datasets: [{
                label: 'Applications',
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
          new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
              labels: Object.keys(categoryData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
              datasets: [{ data: Object.values(categoryData), backgroundColor: colors.slice(0, Object.keys(categoryData).length) }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8 } } } }
          });
        }

        if (Object.keys(sexData).length > 0) {
          new Chart(document.getElementById('sexChart'), {
            type: 'doughnut',
            data: {
              labels: Object.keys(sexData),
              datasets: [{ data: Object.values(sexData), backgroundColor: ['#3b82f6', '#ec4899', '#9ca3af'] }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8 } } } }
          });
        }

        if (Object.keys(statusData).length > 0) {
          new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
              labels: Object.keys(statusData).map(s => s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())),
              datasets: [{ data: Object.values(statusData), backgroundColor: colors.slice(0, Object.keys(statusData).length) }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 9 }, padding: 6 } } } }
          });
        }

        // Edit Journalist Modal logic
        const editModalEl = document.getElementById('editJournalistModal');
        const editModal = new bootstrap.Modal(editModalEl);
        const editForm = document.getElementById('editJournalistForm');

        document.querySelectorAll('.btn-edit-journalist').forEach(button => {
          button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const recordType = this.getAttribute('data-record-type');
            
            // Set action URL dynamically
            editForm.action = `/staff/accreditation-officer/records/journalists/${id}/update`;
            
            // Populate form fields
            document.getElementById('j_record_type').value = recordType;
            document.getElementById('j_first_name').value = this.getAttribute('data-first-name') || '';
            document.getElementById('j_surname').value = this.getAttribute('data-surname') || '';
            document.getElementById('j_date_of_birth').value = this.getAttribute('data-date-of-birth') || '';
            document.getElementById('j_sex').value = this.getAttribute('data-sex') || '';
            document.getElementById('j_nationality').value = this.getAttribute('data-nationality') || '';
            document.getElementById('j_id_number').value = this.getAttribute('data-id-number') || '';
            document.getElementById('j_passport_number').value = this.getAttribute('data-passport-number') || '';
            document.getElementById('j_organisation').value = this.getAttribute('data-organisation') || '';
            document.getElementById('j_designation').value = this.getAttribute('data-designation') || '';
            document.getElementById('j_category').value = this.getAttribute('data-category') || '';
            document.getElementById('j_accreditation_number').value = this.getAttribute('data-accreditation-number') || '';
            document.getElementById('j_phone').value = this.getAttribute('data-phone') || '';
            document.getElementById('j_email').value = this.getAttribute('data-email') || '';
            document.getElementById('j_physical_address').value = this.getAttribute('data-physical-address') || '';
            document.getElementById('j_town').value = this.getAttribute('data-town') || '';
            document.getElementById('j_province').value = this.getAttribute('data-province') || '';
            document.getElementById('j_collection_region').value = this.getAttribute('data-collection-region') || '';
            document.getElementById('j_status').value = this.getAttribute('data-status') || '';

            editModal.show();
          });
        });
      });
    </script>
  @endpush
  </div>
@endsection
