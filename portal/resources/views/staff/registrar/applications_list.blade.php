@extends('layouts.portal')
@section('title', 'Applications Management')

@section('content')
@include('staff.partials.dashboard_theme')
<div class="zmc-dashboard-wrapper">
  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold m-0">Applications Management</h4>
      <div class="text-muted small">Manage and review all applications across different stages.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-white border btn-sm px-3">
        <i class="ri-dashboard-line me-1"></i> Dashboard
      </a>
    </div>
  </div>

  {{-- Tabs --}}
  <div class="mb-4">
    <ul class="nav nav-tabs zmc-nav-tabs" id="applicationTabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link {{ $bucket === 'all' ? 'active' : '' }}" href="{{ route('staff.registrar.applications.list', ['bucket' => 'all'] + request()->except('bucket')) }}">
          <i class="ri-folder-line me-1"></i> All Applications
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $bucket === 'under-review' ? 'active' : '' }}" href="{{ route('staff.registrar.applications.list', ['bucket' => 'under-review'] + request()->except('bucket')) }}">
          <i class="ri-time-line me-1"></i> Under Review
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $bucket === 'approved' ? 'active' : '' }}" href="{{ route('staff.registrar.applications.list', ['bucket' => 'approved'] + request()->except('bucket')) }}">
          <i class="ri-checkbox-circle-line me-1"></i> Approved
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ $bucket === 'returned' ? 'active' : '' }}" href="{{ route('staff.registrar.applications.list', ['bucket' => 'returned'] + request()->except('bucket')) }}">
          <i class="ri-arrow-go-back-line me-1"></i> Returned for Correction
        </a>
      </li>
    </ul>
  </div>

  {{-- Filter Section --}}
  <div class="zmc-card mb-4 bg-light border-0 shadow-none">
    <form method="GET" action="{{ route('staff.registrar.applications.list', $bucket) }}" id="filterForm">
      {{-- Row 1: Core Filters --}}
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label zmc-lbl">Search Reference / Applicant</label>
          <input type="text" name="search" class="form-control zmc-input" placeholder="Ref, Name, Email, Phone..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label zmc-lbl">Application Type</label>
          <select name="applicationType" class="form-select zmc-input" id="applicationTypeSelect">
            <option value="">All Types</option>
            <option value="accreditation" {{ request('applicationType') === 'accreditation' ? 'selected' : '' }}>Accreditation</option>
            <option value="registration" {{ request('applicationType') === 'registration' ? 'selected' : '' }}>Registration</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label zmc-lbl">Application Status</label>
          <select name="status" class="form-select zmc-input">
            <option value="">All Statuses</option>
            @foreach($statuses as $val => $label)
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
          <a href="{{ route('staff.registrar.applications.list', ['bucket' => 'all']) }}" class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Reset All</a>
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
        <div id="registrationFilters" class="row g-2 mt-2 {{ request('applicationType') === 'registration' ? '' : 'd-none' }}">
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
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Levy Payment</label>
            <select name="levyPaymentStatus" class="form-select zmc-input">
              <option value="">Any</option>
              <option value="paid" {{ request('levyPaymentStatus') === 'paid' ? 'selected' : '' }}>Paid</option>
              <option value="pending" {{ request('levyPaymentStatus') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
          </div>
        </div>

        {{-- Accreditation Specific Row --}}
        <div id="accreditationFilters" class="row g-2 mt-2 {{ request('applicationType') === 'accreditation' ? '' : 'd-none' }}">
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
              <option value="News Agency">News Agency</option>
              <option value="Newspaper">Newspaper</option>
              <option value="Television">Television</option>
              <option value="Radio">Radio</option>
              <option value="Magazine">Magazine</option>
              <option value="Online">Online</option>
              <option value="Other">Other</option>
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

  {{-- Table --}}
  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0">
        <i class="ri-list-check-2 me-2" style="color:var(--zmc-accent)"></i>
        @switch($bucket)
          @case('all') All Applications @break
          @case('under-review') Under Review @break
          @case('approved') Approved Applications @break
          @case('returned') Returned for Correction @break
          @default Applications List
        @endswitch
      </h6>
      <div class="small text-muted">Showing {{ $applications->total() }} total items</div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
        @forelse($applications as $i => $app)
          @php
            $ref = $app->reference ?: ('APP-' . str_pad($app->id, 5, '0', STR_PAD_LEFT));
          @endphp
          <tr>
            @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
            <td class="text-end">
              <a href="{{ route('staff.registrar.applications.show', $app) }}" class="btn btn-sm btn-outline-secondary">
                <i class="ri-eye-line me-1"></i> Review
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="15" class="text-center py-5 text-muted">No applications found matching your criteria.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-4">
    {{ $applications->links() }}
  </div>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('applicationTypeSelect');
    const regFilters = document.getElementById('registrationFilters');
    const accFilters = document.getElementById('accreditationFilters');

    typeSelect.addEventListener('change', function() {
      const val = this.value;
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
    });

    // Clean up empty params on submit
    document.getElementById('filterForm').addEventListener('submit', function(e) {
      const inputs = this.querySelectorAll('input, select');
      inputs.forEach(input => {
        if (!input.value) {
          input.name = ''; // Don't send empty fields
        }
      });
    });
  });
</script>
@endpush

@endsection
