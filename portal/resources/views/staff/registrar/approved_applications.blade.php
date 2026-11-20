@extends('layouts.portal')
@section('title', 'Approved Applications')

@section('content')
@include('staff.partials.dashboard_theme')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="fw-bold m-0">Approved Applications</h1>
            <div class="text-muted small">
                Applications you have already approved. Tracks where each one is in the downstream pipeline.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-light border btn-sm">
                <i class="ri-arrow-left-line me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="zmc-card p-0 shadow-sm border-0">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="fw-bold m-0">
                <i class="ri-checkbox-circle-line me-2" style="color:#16a34a"></i>
                Approved &amp; In Progress
                <span class="badge bg-light text-dark border ms-2">{{ $applications->total() }}</span>
            </h2>

    {{-- Filter Section --}}
    <div class="zmc-card mb-4 bg-light border-0 shadow-none">
      <form method="GET" action="{{ route('staff.registrar.approved-applications') }}" id="filterForm">
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
              <option value="renewal" {{ request('type') === 'renewal' ? 'selected' : '' }}>Renewal</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label zmc-lbl">Application Status</label>
            <select name="status" class="form-select zmc-input">
              <option value="">All Statuses</option>
              @foreach($approvedStatusLabels as $val => $label)
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
            <a href="{{ route('staff.registrar.approved-applications') }}" class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Reset All</a>
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
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 zmc-mini-table">
                @include('staff.partials._application_table_headers')
                <tbody>
                    @forelse($applications as $i => $app)
                        <tr>
                            @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    {{-- Quick View (Modal) --}}
                                    <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-primary js-view-more" data-app-id="{{ $app->id }}" data-bs-toggle="tooltip" title="Quick View">
                                        <i class="ri-eye-line"></i>
                                    </button>

                                    {{-- Full Review (Page) --}}
                                    <a href="{{ route('staff.registrar.applications.show', $app) }}" class="btn btn-sm zmc-icon-btn btn-outline-secondary" data-bs-toggle="tooltip" title="Open full review page">
                                        <i class="ri-file-list-3-line"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center text-muted py-5">
                                <i class="ri-inbox-line d-block" style="font-size: 2rem;"></i>
                                No approved applications match these filters yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($applications->hasPages())
            <div class="p-3 border-top">
                {{ $applications->links() }}
            </div>
        @endif
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
            Application Review
          </div>
          <div class="zmc-modal-sub" id="mdl_meta">—</div>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="mdl_loading" class="d-none text-center py-5">
          <div class="spinner-border" style="color:var(--zmc-accent-dark)"></div>
          <div class="text-muted mt-2" style="font-size: var(--font-size-sm);">Loading…</div>
        </div>

        <div id="mdl_error" class="alert alert-danger d-none"></div>

        <div id="mdl_content_area" class="d-none">
          {{-- filled by JS --}}
        </div>
      </div>

      <div class="modal-footer zmc-modal-footer">
        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  const ZMC_DETAILS_URL = @json($detailsUrlTemplate);

  function zmcFmt(v){
    if(v === null || v === undefined) return '—';
    const s = String(v).trim();
    return s === '' ? '—' : s;
  }

  function zmcOpenModal(selector){
    const el = document.querySelector(selector);
    if(!el) return;
    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
      bootstrap.Modal.getOrCreateInstance(el).show();
      return;
    }
  }

  function zmcEsc(v){
    if(v === null || v === undefined) return '—';
    const s = String(v);
    if(s === '') return '—';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function zmcHumanize(key){
    return String(key).replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  function zmcRenderValue(v){
    if(v === null || v === undefined || v === '') return '<span class="text-muted">—</span>';
    if(typeof v === 'boolean') return v ? 'Yes' : 'No';
    if(Array.isArray(v)){
      if(v.length === 0) return '<span class="text-muted">—</span>';
      const allScalar = v.every(x => x === null || ['string','number','boolean'].includes(typeof x));
      if(allScalar) return v.map(zmcEsc).join(', ');
      return '<pre class="small mb-0" style="white-space:pre-wrap;">' + zmcEsc(JSON.stringify(v, null, 2)) + '</pre>';
    }
    if(typeof v === 'object'){
      let out = '';
      for(const k in v){
        if(!Object.prototype.hasOwnProperty.call(v,k)) continue;
        out += '<div><span class="fw-semibold">' + zmcEsc(zmcHumanize(k)) + ':</span> ' + zmcEsc(v[k] == null ? '' : (typeof v[k] === 'object' ? JSON.stringify(v[k]) : v[k])) + '</div>';
      }
      return out || '<span class="text-muted">—</span>';
    }
    return zmcEsc(v);
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
      const ref = app.reference || ('APP-' + (app.id || appId));
      const status = app.status || '—';

      if (meta) {
        meta.innerHTML = `
          <span class="badge bg-light text-dark border me-2">${zmcFmt(formCode || 'Form')}</span>
          Ref: <span class="fw-bold">${zmcFmt(ref)}</span> •
          Category: <span class="fw-bold text-primary">${zmcFmt(app.category_initials)}</span> •
          Status: <span class="fw-bold" style="color:var(--zmc-accent-dark)">${zmcFmt(status)}</span>
        `;
      }

      let html = '';
      const fdAll = (data.form_data && typeof data.form_data === 'object') ? data.form_data : {};
      const flatFd = Object.assign({}, fdAll);
      if (flatFd.ap1 && typeof flatFd.ap1 === 'object' && !Array.isArray(flatFd.ap1)) {
        for (const k in flatFd.ap1) if (!(k in flatFd)) flatFd[k] = flatFd.ap1[k];
      }

      const rowHtml = (k, v) => `<tr><td class="bg-light fw-semibold text-muted ps-3" style="width:35%;">${zmcEsc(zmcHumanize(k))}</td><td class="ps-3">${zmcRenderValue(v)}</td></tr>`;
      
      let rows = [];
      Object.keys(flatFd).forEach(k => {
          if (['current_step','ap1','directors','managers','custom_fields'].includes(k)) return;
          rows.push(rowHtml(k, flatFd[k]));
      });

      if (rows.length) {
        html += `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-rectangle-list"></i> Submitted Details</div><div class="table-responsive"><table class="table table-sm align-middle mb-0 zmc-table-lite"><tbody class="small">${rows.join('')}</tbody></table></div></div>`;
      }

      if (area) {
        area.innerHTML = html;
        area.classList.remove('d-none');
      }
    } catch (err) {
      if (errBox) {
        errBox.textContent = err.message;
        errBox.classList.remove('d-none');
      }
    } finally {
      if (loader) loader.classList.add('d-none');
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Tooltips
    if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }

    // Modal Trigger
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.js-view-more');
      if(!btn) return;
      const appId = btn.getAttribute('data-app-id');
      if(!appId) return;
      zmcOpenModal('#appDetailsModal');
      loadApplicationDetails(appId);
    });

    // Filter Toggling
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
