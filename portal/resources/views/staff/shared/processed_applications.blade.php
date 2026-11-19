@extends('layouts.portal')
@section('title', 'Processed Applications')

@section('content')
@include('staff.partials.dashboard_theme')
@php
  $processedDashboardRoute = match (session('active_staff_role')) {
    'accounts_payments' => route('staff.accounts.dashboard'),
    'accreditation_officer' => route('staff.officer.dashboard'),
    default => route('staff.registrar.dashboard'),
  };
@endphp
<div class="registrar-theme">
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <style>
    .processed-hero{position:relative;overflow:hidden;padding:26px 30px;border-radius:22px;background:linear-gradient(120deg,#0e1b2b,#173b3d 55%,#1f6f57);color:#fff;box-shadow:0 16px 34px rgba(20,58,46,.15);margin-bottom:22px}.processed-hero:after{content:"";position:absolute;right:-55px;top:-105px;width:230px;height:230px;border-radius:50%;background:rgba(242,201,76,.16)}.processed-hero>*{position:relative;z-index:1}.processed-hero h1{font-size:clamp(1.55rem,2.4vw,2.1rem);font-weight:850;margin:0;letter-spacing:-.03em}.processed-hero p{color:rgba(255,255,255,.76);margin:7px 0 0;font-size:.9rem}.processed-hero .hero-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}.processed-hero .btn{border-radius:10px;font-weight:750}.processed-summary{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.processed-summary span{border:1px solid rgba(250,204,21,.35);background:#f8fbf9;border-radius:999px;padding:7px 12px;font-size:.76rem;color:#5e706a;font-weight:700}.processed-filter{border:1px solid #e3ece7!important;border-radius:18px!important;background:linear-gradient(135deg,#f8fbf9,#f2f7f4)!important;padding:18px!important;box-shadow:none!important}.processed-filter .form-control,.processed-filter .form-select{height:40px;border-radius:10px;border-color:#dce7e1}.processed-filter .btn{height:40px;border-radius:10px;font-weight:750}.processed-statuses{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:18px}.processed-status{border:1px solid #e5ede9;border-radius:15px;background:#fff;padding:14px 12px;box-shadow:0 6px 18px rgba(20,58,46,.05)}.processed-status .number{font-size:1.45rem;font-weight:850;color:#17352c;line-height:1}.processed-status .label{font-size:.7rem;color:#73817b;font-weight:750;margin-top:6px;line-height:1.2}.processed-table{border:1px solid #e5ede9!important;border-radius:19px!important;overflow:hidden;box-shadow:0 9px 26px rgba(20,58,46,.07)!important}.processed-table .table thead th{background:#f4f8f6}.processed-table .table tbody tr:hover{background:#fbfdfc}@media(max-width:1050px){.processed-statuses{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:600px){.processed-statuses{grid-template-columns:repeat(2,minmax(0,1fr))}.processed-hero{padding:22px 20px}}
  </style>
  <div class="processed-hero"><div class="text-uppercase small fw-bold" style="letter-spacing:.14em;opacity:.72"><i class="ri-stack-line me-1"></i> Downstream tracking</div><h1>Processed Applications</h1><p>Track applications moving through production, printing, and certificate or card issuance.</p><div class="hero-actions"><a href="{{ url()->current() }}" class="btn btn-light"><i class="ri-refresh-line me-1"></i> Refresh</a><a href="{{ $processedDashboardRoute }}" class="btn btn-outline-light"><i class="ri-arrow-left-line me-1"></i> Dashboard</a></div></div>
  <div class="processed-summary"><span><i class="ri-file-list-3-line me-1"></i>{{ $applications->total() }} records in view</span><span><i class="ri-printer-line me-1"></i>Production to issuance</span><span><i class="ri-eye-line me-1"></i>Read-only tracking</span></div>

  {{-- Filter Section --}}
  <div class="processed-filter zmc-card mb-4">
    <form method="GET" action="{{ route('staff.registrar.processed') }}" id="filterForm">
      {{-- Row 1: Core Filters --}}
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label zmc-lbl">Search Reference / Applicant</label>
          <input type="text" name="search" class="form-control zmc-input" placeholder="Ref, Name, Email, Phone..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label zmc-lbl">Application Type</label>
          <select name="type" class="form-select zmc-input" id="applicationTypeSelect">
            <option value="">All Types</option>
            <option value="accreditation" {{ request('type') === 'accreditation' ? 'selected' : '' }}>Accreditation</option>
            <option value="registration" {{ request('type') === 'registration' ? 'selected' : '' }}>Registration</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label zmc-lbl">Application Status</label>
          <select name="status" class="form-select zmc-input">
            <option value="">All Statuses</option>
            @foreach($processedStatusLabels as $val => $label)
              <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label zmc-lbl">Date From</label>
          <input type="date" name="dateFrom" class="form-control zmc-input" value="{{ request('dateFrom') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label zmc-lbl">Date To</label>
          <input type="date" name="dateTo" class="form-control zmc-input" value="{{ request('dateTo') }}">
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-dark btn-sm w-100">Apply</button>
        </div>
      </div>

      {{-- Advanced Filters Collapsible --}}
      <div class="mt-2 d-flex justify-content-between align-items-center">
        <div>
          <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="false" aria-controls="advancedFilters">
            <i class="ri-equalizer-line me-1"></i> Advanced Filters
            @php $advCount = count(request()->only(['foreignOrLocal', 'applicationNature', 'collectionRegion', 'paymentStatus', 'assignedOfficer', 'category', 'registrationForm', 'mediaHouseType', 'massMediaCategory', 'levyYear', 'levyPaymentStatus', 'accreditationCategory', 'mediumType', 'designation', 'expiryStatus'])); @endphp
            @if($advCount > 0)
              <span class="badge bg-primary ms-1">{{ $advCount }} applied</span>
            @endif
          </button>
          <a href="{{ route('staff.registrar.processed') }}" class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Reset All</a>
        </div>
      </div>

      <div class="collapse {{ $advCount > 0 ? 'show' : '' }} mt-3" id="advancedFilters">
        <div class="row g-2">
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Foreign or Local</label>
            <select name="foreignOrLocal" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="local" {{ request('foreignOrLocal') === 'local' ? 'selected' : '' }}>Local</option>
              <option value="foreign" {{ request('foreignOrLocal') === 'foreign' ? 'selected' : '' }}>Foreign</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Nature</label>
            <select name="applicationNature" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="new" {{ request('applicationNature') === 'new' ? 'selected' : '' }}>New</option>
              <option value="renewal" {{ request('applicationNature') === 'renewal' ? 'selected' : '' }}>Renewal</option>
              <option value="replacement" {{ request('applicationNature') === 'replacement' ? 'selected' : '' }}>Replacement</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Collection Region</label>
            <select name="collectionRegion" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="harare" {{ request('collectionRegion') === 'harare' ? 'selected' : '' }}>Harare</option>
              <option value="bulawayo" {{ request('collectionRegion') === 'bulawayo' ? 'selected' : '' }}>Bulawayo</option>
              <option value="gweru" {{ request('collectionRegion') === 'gweru' ? 'selected' : '' }}>Gweru</option>
              <option value="mutare" {{ request('collectionRegion') === 'mutare' ? 'selected' : '' }}>Mutare</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Payment Status</label>
            <select name="paymentStatus" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="paid" {{ request('paymentStatus') === 'paid' ? 'selected' : '' }}>Paid</option>
              <option value="pending" {{ request('paymentStatus') === 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="failed" {{ request('paymentStatus') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Assigned Officer</label>
            <select name="assignedOfficer" class="form-select zmc-input">
              <option value="">Any</option>
              @foreach($officers as $off)
                <option value="{{ $off->id }}" {{ request('assignedOfficer') == $off->id ? 'selected' : '' }}>{{ $off->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Category</label>
            <select name="category" class="form-select zmc-input">
              <option value="">Any</option>
              <optgroup label="Accreditation">
                @foreach(\App\Models\Application::accreditationCategories() as $code => $label)
                  <option value="{{ $code }}" {{ request('category') === $code ? 'selected' : '' }}>{{ $code }}</option>
                @endforeach
              </optgroup>
              <optgroup label="Registration">
                @foreach(\App\Models\Application::massMediaCategories() as $code => $label)
                  <option value="{{ $code }}" {{ request('category') === $code ? 'selected' : '' }}>{{ $code }}</option>
                @endforeach
              </optgroup>
            </select>
          </div>
        </div>

        {{-- Registration Specific Row --}}
        <div id="registrationFilters" class="row g-2 mt-2 {{ request('type') === 'registration' ? '' : 'd-none' }}">
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Reg Form</label>
            <select name="registrationForm" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="AP1" {{ request('registrationForm') === 'AP1' ? 'selected' : '' }}>AP1</option>
              <option value="AP2" {{ request('registrationForm') === 'AP2' ? 'selected' : '' }}>AP2</option>
              <option value="AP4" {{ request('registrationForm') === 'AP4' ? 'selected' : '' }}>AP4</option>
              <option value="AP6" {{ request('registrationForm') === 'AP6' ? 'selected' : '' }}>AP6</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label zmc-lbl">Mass Media Category</label>
            <select name="massMediaCategory" class="form-select zmc-input">
              <option value="">Any</option>
              @foreach(\App\Models\Application::massMediaCategories() as $code => $label)
                <option value="{{ $code }}" {{ request('massMediaCategory') === $code ? 'selected' : '' }}>{{ $code }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Levy Year</label>
            <select name="levyYear" class="form-select zmc-input">
              <option value="">Any</option>
              @foreach(\App\Models\LevyRecord::yearOptions() as $y)
                <option value="{{ $y }}" {{ request('levyYear') == $y ? 'selected' : '' }}>{{ $y }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Accreditation Specific Row --}}
        <div id="accreditationFilters" class="row g-2 mt-2 {{ request('type') === 'accreditation' ? '' : 'd-none' }}">
          <div class="col-md-3">
            <label class="form-label zmc-lbl">Accreditation Category</label>
            <select name="accreditationCategory" class="form-select zmc-input">
              <option value="">Any</option>
              @foreach(\App\Models\Application::accreditationCategories() as $code => $label)
                <option value="{{ $code }}" {{ request('accreditationCategory') === $code ? 'selected' : '' }}>{{ $code }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Medium Type</label>
            <select name="mediumType" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="News Agency" {{ request('mediumType') === 'News Agency' ? 'selected' : '' }}>News Agency</option>
              <option value="Newspaper" {{ request('mediumType') === 'Newspaper' ? 'selected' : '' }}>Newspaper</option>
              <option value="Television" {{ request('mediumType') === 'Television' ? 'selected' : '' }}>Television</option>
              <option value="Radio" {{ request('mediumType') === 'Radio' ? 'selected' : '' }}>Radio</option>
              <option value="Magazine" {{ request('mediumType') === 'Magazine' ? 'selected' : '' }}>Magazine</option>
              <option value="Online" {{ request('mediumType') === 'Online' ? 'selected' : '' }}>Online</option>
              <option value="Other" {{ request('mediumType') === 'Other' ? 'selected' : '' }}>Other</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Expiry Status</label>
            <select name="expiryStatus" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="active" {{ request('expiryStatus') === 'active' ? 'selected' : '' }}>Active</option>
              <option value="expiring_soon" {{ request('expiryStatus') === 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
              <option value="expired" {{ request('expiryStatus') === 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
          </div>
        </div>
      </div>
    </form>
  </div>

  @php
    $statusColors = [
      'production_queue' => '#f59e0b',
      'card_generated' => '#3b82f6',
      'certificate_generated' => '#6366f1',
      'produced_ready' => '#8b5cf6',
      'printed' => '#10b981',
      'issued' => '#059669',
    ];
  @endphp

  <div class="processed-statuses">
    @foreach($processedStatusLabels as $key => $label)
      @php $cnt = $applications->where('status', $key)->count(); @endphp
      <div class="processed-status">
        <div class="number" style="color:{{ $statusColors[$key] ?? '#64748b' }};">
          {{ \App\Models\Application::whereIn('status', [$key])->count() }}
        </div>
        <div class="label">{{ $label }}</div>
      </div>
    @endforeach
  </div>

  <div class="processed-table zmc-card p-0">
    <div class="p-3 border-bottom fw-bold"><i class="ri-stack-line me-2" style="color:var(--zmc-accent)"></i> Processed Applications</div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
          @forelse($applications as $i => $app)
            @php
              $role = session('active_staff_role');
              if ($role === 'registrar') {
                  $showUrl = route('staff.registrar.applications.show', $app->id);
              } elseif ($role === 'accounts_payments') {
                  $showUrl = route('staff.accounts.applications.show', $app->id);
              } else {
                  $showUrl = route('staff.officer.applications.show', $app->id);
              }
            @endphp
            <tr>
              @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
              <td class="text-end">
                <a class="btn btn-sm zmc-icon-btn btn-outline-primary" href="{{ $showUrl }}" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
              </td>
            </tr>
          @empty
            <tr><td colspan="15" class="text-center text-muted py-5">No processed applications found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $applications->links() }}</div>
  </div>
</div>
</div>
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('applicationTypeSelect');
    const regFilters = document.getElementById('registrationFilters');
    const accFilters = document.getElementById('accreditationFilters');

    function toggleFilters(val) {
      if (val === 'registration') {
        regFilters.classList.remove('d-none');
        accFilters.classList.add('d-none');
      } else if (val === 'accreditation') {
        accFilters.classList.remove('d-none');
        regFilters.classList.add('d-none');
      } else {
        regFilters.classList.add('d-none');
        accFilters.classList.add('d-none');
      }
    }

    if(typeSelect) {
      typeSelect.addEventListener('change', function() { toggleFilters(this.value); });
      toggleFilters(typeSelect.value);
    }

    // Clean up empty params on submit
    const filterForm = document.getElementById('filterForm');
    if(filterForm) {
      filterForm.addEventListener('submit', function(e) {
        const inputs = this.querySelectorAll('input, select');
        inputs.forEach(input => {
          if (!input.value) {
            input.name = ''; // Don't send empty fields
          }
        });
      });
    }
  });
</script>
@endpush
@endsection
