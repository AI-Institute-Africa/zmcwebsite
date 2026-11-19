@extends('layouts.portal')
@section('title', 'Accreditation Officer Dashboard')

@section('content')
@include('staff.partials.tabs', ['label' => 'Accreditation officer sections', 'tabs' => [['staff.officer.dashboard','Overview','ri-dashboard-3-line'],['staff.officer.applications.new-apps','New','ri-add-circle-line'],['staff.officer.applications.renewals','Renewals','ri-refresh-line'],['staff.officer.applications.replacements','Replacements','ri-swap-line'],['staff.officer.applications.pending','Pending accounts review','ri-time-line'],['staff.officer.applications.approved','Approved','ri-checkbox-circle-line'],['staff.officer.applications.rejected','Returned','ri-arrow-go-back-line'],['staff.officer.processed','Processed','ri-stack-line']]])
@include('staff.partials.dashboard_theme')
<div class="staff-dashboard-theme">
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Accreditation Officer Dashboard</h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Review new submissions and request corrections. Approved items go to <b>Registrar</b>.
      </div>
    </div>

    @php
      $myRegionNames = auth()->user()->assignedRegions()->pluck('name')->filter()->values();
    @endphp
    <div class="d-flex align-items-center gap-2">
      <span class="zmc-pill zmc-pill-dark">
        <i class="ri-map-pin-user-line"></i>
        <span>Region: {{ $myRegionNames->isNotEmpty() ? $myRegionNames->join(', ') : 'NOT SET' }}</span>
      </span>
      <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Refresh">
        <i class="ri-refresh-line me-1"></i> Refresh
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size: var(--font-size-lg); line-height: 1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @php
    $items = collect(method_exists($applications, 'items') ? $applications->items() : $applications);
    $summaryTotal = $kpis['total_applications'] ?? (method_exists($applications, 'total') ? $applications->total() : $items->count());

    $summaryPending = $kpis['pending_applications'] ?? $items->filter(fn($x) => in_array(strtolower((string)($x->status ?? '')), [
      'submitted', 'officer_review', 'under_officer_review', 'needs_correction', 'returned_from_payments', 'returned_from_registrar'
    ], true))->count();

    $summaryCorrections = $kpis['corrections'] ?? $items->filter(fn($x) => in_array(strtolower((string)($x->status ?? '')), ['correction_requested','corrections_requested','needs_correction'], true))->count();

    $summaryNewThisWeek = $kpis['new_this_week'] ?? 0;
    $summaryFixRequests = $kpis['pending_fix_requests'] ?? 0;

    $detailsUrlTemplate = route('staff.applications.details', ['application' => '__ID__']);
    $approveUrl = fn($id) => route('staff.officer.applications.approve', $id);
    $correctionUrl = fn($id) => route('staff.officer.applications.requestCorrection', $id);
  @endphp

  {{-- KPI Cards Row --}}
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">Total Applications</div>
            <div class="h3 fw-black mb-0">{{ $summaryTotal }}</div>
          </div>
          <div class="icon-box text-primary"><i class="ri-folders-line"></i></div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="zmc-card h-100 border-warning">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">Pending Action</div>
            <div class="h3 fw-black mb-0 text-warning">{{ $summaryPending }}</div>
          </div>
          <div class="icon-box text-warning"><i class="ri-time-line"></i></div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="zmc-card h-100 border-danger">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">Corrections</div>
            <div class="h3 fw-black mb-0 text-danger">{{ $summaryCorrections }}</div>
          </div>
          <div class="icon-box text-danger"><i class="ri-chat-check-line"></i></div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">New This Week</div>
            <div class="h3 fw-black mb-0 text-primary">{{ $summaryNewThisWeek }}</div>
          </div>
          <div class="icon-box text-primary"><i class="ri-calendar-event-line"></i></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">Returned</div>
            <div class="h3 fw-black mb-0">{{ $summaryFixRequests }}</div>
          </div>
          <div class="icon-box text-info"><i class="ri-tools-line"></i></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Incoming Applications Table --}}
  <div class="zmc-card p-0 shadow-sm border-0 mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0">
        <i class="ri-list-check-2 me-2" style="color:var(--zmc-accent)"></i> Recent Applications
      </h6>
      @if(method_exists($applications, 'currentPage'))
        <div class="small text-muted">Page {{ $applications->currentPage() }} of {{ $applications->lastPage() }}</div>
      @endif
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
          @forelse($applications as $i => $app)
            @php
              $status = strtolower((string)($app->status ?? ''));
              $canDecide = in_array($status, ['submitted','officer_review','under_officer_review','returned_from_payments','returned_from_registrar'], true);
            @endphp
            <tr>
              @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
              <td class="text-end">
                <div class="d-flex gap-2 justify-content-end">
                  <a href="{{ route('staff.officer.applications.show', $app) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="tooltip" title="View Application">
                    <i class="ri-eye-line"></i>
                  </a>
                  @if($canDecide)
                    <button class="btn btn-sm zmc-icon-btn btn-outline-success js-open-modal" data-target="#approveModal{{ $app->id }}" data-bs-toggle="tooltip" title="Approve">
                      <i class="ri-check-line"></i>
                    </button>
                    <button class="btn btn-sm zmc-icon-btn btn-outline-warning js-open-modal" data-target="#corrModal{{ $app->id }}" data-bs-toggle="tooltip" title="Request Correction">
                      <i class="ri-chat-check-line"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>

            @if($canDecide)
            @push('zmc_modals')
              {{-- Correction Modal --}}
              <div class="modal fade zmc-chat-modal" id="corrModal{{ $app->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <form class="modal-content" method="POST" action="{{ $correctionUrl($app->id) }}">
                    @csrf

                    <div class="modal-header zmc-modal-header">
                      <div class="d-flex align-items-center gap-2">
                        <div class="zmc-avatar"><i class="fa-solid fa-user"></i></div>
                        <div>
                          <div class="zmc-modal-title">Request correction <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $app->reference ?? ('APP-' . str_pad((int)$app->id, 5, '0', STR_PAD_LEFT)) }}</span></div>
                          <div class="zmc-modal-sub">Applicant: <span class="fw-bold">{{ $app->applicant?->name ?? '—' }}</span></div>
                        </div>
                      </div>
                      <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                      <label class="form-label zmc-lbl mb-2">Message / corrections <span class="text-danger">*</span></label>
                      <textarea name="notes" class="form-control zmc-chat-input" rows="4" required placeholder="State what needs to be corrected / provided…"></textarea>
                      <div class="form-text mt-2">This note is visible to the applicant.</div>
                    </div>

                    <div class="modal-footer zmc-modal-footer">
                      <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-dark fw-bold">
                        <i class="fa-solid fa-paper-plane me-1"></i>Send
                      </button>
                    </div>
                  </form>
                </div>
              </div>

              {{-- Approve Modal --}}
              <div class="modal fade" id="approveModal{{ $app->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <form class="modal-content" method="POST" action="{{ $approveUrl($app->id) }}">
                    @csrf
                    <div class="modal-header zmc-modal-header">
                      <div>
                        <div class="zmc-modal-title"><i class="fa-solid fa-check me-2" style="color:var(--zmc-accent-dark)"></i>Approve application <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $app->reference ?? ('APP-' . str_pad((int)$app->id, 5, '0', STR_PAD_LEFT)) }}</span></div>
                        <div class="zmc-modal-sub">Choose the appropriate category, confirm, and send to Registrar.</div>
                      </div>
                      <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      @php
                        $isRegistration = ($app->application_type ?? '') === 'registration';
                        $cats = $isRegistration ? \App\Models\Application::massMediaCategories() : \App\Models\Application::accreditationCategories();
                        $label = $isRegistration ? 'Mass Media Category' : 'Accreditation Category';
                      @endphp

                      <label class="form-label zmc-lbl">{{ $label }} <span class="text-danger">*</span></label>
                      <select name="category_code" class="form-select zmc-input" required>
                        <option value="">-- Select category --</option>
                        @foreach($cats as $code => $name)
                          <option value="{{ $code }}">{{ $code }} - {{ $name }}</option>
                        @endforeach
                      </select>

                      <div class="mt-3">
                        <label class="form-label zmc-lbl">Notes (optional)</label>
                        <textarea name="decision_notes" class="form-control zmc-input" rows="3" placeholder="Add any notes (optional)"></textarea>
                      </div>
                    </div>
                    <div class="modal-footer zmc-modal-footer">
                      <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-success fw-bold"><i class="fa-solid fa-check me-1"></i>Confirm & Send</button>
                    </div>
                  </form>
                </div>
              </div>

            @endpush
            @endif
          @empty
            <tr><td colspan="8" class="text-center text-muted py-5">
              <i class="ri-inbox-line text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
              <p class="mt-2 mb-0">No applications found.</p>
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mb-4">{{ $applications->links() }}</div>

  @stack('zmc_modals')

  {{-- Records Management Section --}}
  <div class="row g-3 mb-4">
    <div class="col-12">
      <h6 class="fw-bold mb-3">
        <i class="ri-file-list-3-line me-2" style="color:var(--zmc-accent)"></i> Records Management
      </h6>
    </div>
    <div class="col-12 col-md-6">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h6 class="fw-bold m-0">Media Practitioners</h6>
            <div class="text-muted small">Total media practitioners vs accredited</div>
          </div>
          <div class="icon-box text-primary"><i class="ri-user-star-line"></i></div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="border rounded p-2 text-center">
              <div class="h4 fw-bold mb-0">{{ $kpis['media_practitioners_total'] ?? 0 }}</div>
              <div class="small text-muted">Total</div>
            </div>
          </div>
          <div class="col-6">
            <div class="border rounded p-2 text-center">
              <div class="h4 fw-bold mb-0 text-success">{{ $kpis['media_practitioners_accredited'] ?? 0 }}</div>
              <div class="small text-muted">Accredited</div>
            </div>
          </div>
        </div>
        <a href="{{ route('staff.officer.records.accredited-journalists') }}" class="btn btn-outline-primary w-100">
          <i class="ri-eye-line me-1"></i> View All Media Practitioners
        </a>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h6 class="fw-bold m-0">Media Houses</h6>
            <div class="text-muted small">Total media houses vs registered</div>
          </div>
          <div class="icon-box text-success"><i class="ri-building-line"></i></div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <div class="border rounded p-2 text-center">
              <div class="h4 fw-bold mb-0">{{ $kpis['media_houses_total'] ?? 0 }}</div>
              <div class="small text-muted">Total</div>
            </div>
          </div>
          <div class="col-6">
            <div class="border rounded p-2 text-center">
              <div class="h4 fw-bold mb-0 text-success">{{ $kpis['media_houses_registered'] ?? 0 }}</div>
              <div class="small text-muted">Registered</div>
            </div>
          </div>
        </div>
        <a href="{{ route('staff.officer.records.registered-mediahouses') }}" class="btn btn-outline-success w-100">
          <i class="ri-eye-line me-1"></i> View All Media Houses
        </a>
      </div>
    </div>
  </div>

  {{-- Renewals Due Section --}}
  @if(($expiringJournalists && $expiringJournalists->count() > 0) || ($expiringMediaHouses && $expiringMediaHouses->count() > 0))
    <div class="zmc-card mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0">
          <i class="ri-calendar-todo-line me-2" style="color:var(--zmc-accent)"></i> Renewals Due (Next 90 Days)
        </h6>
        <a href="{{ route('staff.officer.renewals.expiring') }}" class="btn btn-sm btn-outline-primary">
          <i class="ri-eye-line me-1"></i> View All
        </a>
      </div>

      <div class="row g-3">
        @if($expiringJournalists && $expiringJournalists->count() > 0)
          <div class="col-12 col-lg-6">
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold small text-muted">Media Practitioners ({{ $expiringJournalists->count() }})</div>
                <form method="POST" action="{{ route('staff.officer.renewals.send-reminders') }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="record_type" value="accreditation">
                  <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="ri-mail-send-line me-1"></i> Send All Reminders
                  </button>
                </form>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>Name</th>
                      <th>Certificate</th>
                      <th>Expires</th>
                      <th class="text-end">Days</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($expiringJournalists as $rec)
                      @php
                        $days = $rec->expires_at ? now()->startOfDay()->diffInDays($rec->expires_at->startOfDay(), false) : null;
                        $urgency = $days !== null && $days <= 30 ? 'danger' : ($days <= 60 ? 'warning' : 'info');
                      @endphp
                      <tr>
                        <td class="small">{{ $rec->holder->name ?? '—' }}</td>
                        <td class="small">{{ $rec->certificate_no ?? '—' }}</td>
                        <td class="small">{{ optional($rec->expires_at)->format('d M Y') ?? '—' }}</td>
                        <td class="text-end">
                          <span class="badge bg-{{ $urgency }}-subtle text-{{ $urgency }} border border-{{ $urgency }}-subtle">
                            {{ $days ?? '—' }} days
                          </span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        @endif

        @if($expiringMediaHouses && $expiringMediaHouses->count() > 0)
          <div class="col-12 col-lg-6">
            <div class="border rounded p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-bold small text-muted">Media Houses ({{ $expiringMediaHouses->count() }})</div>
                <form method="POST" action="{{ route('staff.officer.renewals.send-reminders') }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="record_type" value="registration">
                  <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="ri-mail-send-line me-1"></i> Send All Reminders
                  </button>
                </form>
              </div>
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="bg-light">
                    <tr>
                      <th>Entity</th>
                      <th>Reg No</th>
                      <th>Expires</th>
                      <th class="text-end">Days</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($expiringMediaHouses as $rec)
                      @php
                        $days = $rec->expires_at ? now()->startOfDay()->diffInDays($rec->expires_at->startOfDay(), false) : null;
                        $urgency = $days !== null && $days <= 30 ? 'danger' : ($days <= 60 ? 'warning' : 'info');
                      @endphp
                      <tr>
                        <td class="small">{{ $rec->entity_name ?? '—' }}</td>
                        <td class="small">{{ $rec->registration_no ?? '—' }}</td>
                        <td class="small">{{ optional($rec->expires_at)->format('d M Y') ?? '—' }}</td>
                        <td class="text-end">
                          <span class="badge bg-{{ $urgency }}-subtle text-{{ $urgency }} border border-{{ $urgency }}-subtle">
                            {{ $days ?? '—' }} days
                          </span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  @endif

  {{-- Inactive Journalists Section --}}
  @if($inactiveJournalists && $inactiveJournalists->count() > 0)
    <div class="zmc-card mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold m-0">
          <i class="ri-user-unfollow-line me-2" style="color:#dc3545"></i> Inactive Media Practitioners (2-3 Years)
        </h6>
        <span class="badge bg-danger">{{ $inactiveJournalists->count() }} inactive</span>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th>Name</th>
              <th>Certificate No</th>
              <th>Last Login</th>
              <th class="text-end">Days Inactive</th>
            </tr>
          </thead>
          <tbody>
            @foreach($inactiveJournalists as $rec)
              @php
                $lastActivity = $rec->last_activity ? \Carbon\Carbon::parse($rec->last_activity) : null;
                $daysInactive = $lastActivity ? now()->startOfDay()->diffInDays($lastActivity->startOfDay()) : null;
              @endphp
              <tr>
                <td class="small">{{ $rec->holder->name ?? '—' }}</td>
                <td class="small">{{ $rec->certificate_no ?? '—' }}</td>
                <td class="small">{{ $lastActivity ? $lastActivity->format('d M Y H:i') : 'Never' }}</td>
                <td class="text-end">
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    {{ $daysInactive ?? '—' }} days
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-3 text-muted small">
        <i class="ri-information-line me-1"></i> Showing media practitioners who haven't logged in for 2-3 years. Consider sending reactivation reminders.
      </div>
    </div>
  @endif

  {{-- Reports Section --}}
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      @include('staff.partials.accreditation-summary-report')
    </div>
  </div>

  {{-- Analytics Overview Section --}}
  @include('staff.partials.analytics-overview')
</div>
</div>

{{-- Global Details Modal --}}
<div class="modal fade zmc-modal-pop" id="appDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header zmc-modal-header">
        <div>
          <div class="zmc-modal-title"><i class="fa-regular fa-file-lines me-2" style="color:var(--zmc-accent-dark)"></i>Application Review</div>
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
        <div id="mdl_content_area" class="d-none"></div>
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

  function zmcOpenModal(selector){
    const el = document.querySelector(selector);
    if(!el) return;
    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
      bootstrap.Modal.getOrCreateInstance(el).show();
      return;
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
      const ref = app.reference || ('APP-' + (app.id || appId));
      const status = app.status || '—';

      if (meta) {
        meta.innerHTML = `<span class="badge bg-light text-dark border me-2">${zmcFmt(formCode || 'Form')}</span>Ref: <span class="fw-bold">${zmcFmt(ref)}</span> • Status: <span class="fw-bold" style="color:var(--zmc-accent-dark)">${zmcFmt(status)}</span>`;
      }

      let html = '';



      if (formCode === 'AP1') {
        const s = data.ap1 || {};

        html += zmcBlock(
          `<i class="fa-regular fa-building"></i> Organisation details`,
          `<div class="row g-3">
            ${zmcInput('Category', s.category)}
            ${zmcInput('Service name', s.service_name)}
            ${zmcInput('Operating model', s.operating_model)}
            ${zmcInput('Organisation', s.org_name)}
            ${zmcInput('Reg no', s.reg_no)}
            ${zmcInput('Website', s.website)}
            ${zmcTextarea('Head office', s.head_office, 6)}
            ${zmcTextarea('Postal address', s.postal_address, 6)}
          </div>`
        );

        html += zmcBlock(
          `<i class="fa-regular fa-address-book"></i> Contact`,
          `<div class="row g-3">
            ${zmcInput('Contact person', s.contact_person, 4)}
            ${zmcInput('Contact email', s.contact_email, 4)}
            ${zmcInput('Contact phone', s.contact_phone, 4)}
          </div>`
        );

        const directors = Array.isArray(data.directors) ? data.directors : [];
        const managers  = Array.isArray(data.managers) ? data.managers : [];

        let dRows = directors.length ? '' : `<tr><td colspan="5" class="text-muted text-center">—</td></tr>`;
        directors.forEach(d => {
          dRows += `<tr><td>${zmcFmt(d.full_name || (d.name ? (d.name + ' ' + (d.surname||'')) : ''))}</td><td>${zmcFmt(d.id_passport)}</td><td>${zmcFmt(d.nationality)}</td><td>${zmcFmt(d.role || d.occupation)}</td><td>${zmcFmt(d.shareholding)}</td></tr>`;
        });

        html += zmcBlock(
          `<i class="fa-solid fa-people-group"></i> Directors`,
          `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Full name</th><th>ID / Passport</th><th>Nationality</th><th>Role</th><th>Shareholding</th></tr></thead><tbody>${dRows}</tbody></table></div>`
        );

        let mRows = managers.length ? '' : `<tr><td colspan="4" class="text-muted text-center">—</td></tr>`;
        managers.forEach(m => {
          mRows += `<tr><td>${zmcFmt(m.full_name)}</td><td>${zmcFmt(m.position)}</td><td>${zmcFmt(m.qualification)}</td><td>${zmcFmt(m.experience)}</td></tr>`;
        });

        html += zmcBlock(
          `<i class="fa-solid fa-user-gear"></i> Managers`,
          `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Full name</th><th>Position</th><th>Qualification</th><th>Experience</th></tr></thead><tbody>${mRows}</tbody></table></div>`
        );
      }

      if (formCode === 'AP4') {
        const fd = data.form_data || {};
        html += zmcBlock(
          `<i class="fa-regular fa-address-book"></i> Contact Person`,
          `<div class="row g-3">
            ${zmcInput('Name', fd.contact_name)}
            ${zmcInput('Surname', fd.contact_surname)}
            ${zmcInput('Phone', (fd.contact_phone_country_code || '') + ' ' + (fd.contact_phone || ''))}
            ${zmcInput('Email', fd.contact_email)}
            ${zmcTextarea('Address', fd.contact_address, 6)}
            ${zmcInput('Collection Office', fd.collection_region)}
          </div>`
        );

        html += zmcBlock(
          `<i class="fa-regular fa-building"></i> Foreign Organisation`,
          `<div class="row g-3">
            ${zmcInput('Organisation Name', fd.org_name)}
            ${zmcInput('Country of Origin', fd.org_country)}
            ${zmcInput('Head Office Location', fd.org_head_office)}
            ${zmcTextarea('Mailing Address', fd.org_mailing_address, 6)}
            ${zmcInput('Media House Type', fd.foreign_media_type)}
          </div>`
        );

        const orgOwners = Array.isArray(fd.org_owners) ? fd.org_owners : [];
        if (orgOwners.length > 0) {
          let oRows = '';
          orgOwners.forEach(o => {
            oRows += `<tr><td>${zmcFmt(o.name)}</td><td>${zmcFmt(o.nationality)}</td></tr>`;
          });
          html += zmcBlock(
            `<i class="fa-solid fa-building"></i> Organisation Owners`,
            `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>Nationality</th></tr></thead><tbody>${oRows}</tbody></table></div>`
          );
        }

        html += zmcBlock(
          `<i class="fa-solid fa-hotel"></i> Representative Office`,
          `<div class="row g-3">
            ${zmcInput('Office Name', fd.rep_office_name)}
            ${zmcInput('Email', fd.rep_office_email)}
            ${zmcTextarea('Address in Zimbabwe', fd.rep_office_address, 6)}
            ${zmcTextarea('Activities in Zimbabwe', fd.rep_office_activities, 6)}
          </div>`
        );

        const staffMembers = Array.isArray(fd.staff_members) ? fd.staff_members : [];
        if (staffMembers.length > 0) {
          let sRows = '';
          staffMembers.forEach(s => {
            sRows += `<tr><td>${zmcFmt(s.name)}</td><td>${zmcFmt(s.nationality)}</td><td>${zmcFmt(s.position)}</td><td>${zmcFmt(s.passport_id)}</td><td>${zmcFmt(s.work_permit)}</td></tr>`;
          });
          html += zmcBlock(
            `<i class="fa-solid fa-users"></i> Staff in Zimbabwe`,
            `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>Nationality</th><th>Position</th><th>Passport/ID</th><th>Work Permit</th></tr></thead><tbody>${sRows}</tbody></table></div>`
          );
        }
      }

      if (formCode === 'AP2') {
        const fd = data.form_data || {};
        html += zmcBlock(
          `<i class="fa-regular fa-address-book"></i> Contact Person`,
          `<div class="row g-3">
            ${zmcInput('Name', fd.contact_name)}
            ${zmcInput('Surname', fd.contact_surname)}
            ${zmcInput('Phone', (fd.contact_phone_country_code || '') + ' ' + (fd.contact_phone || ''))}
            ${zmcInput('Email', fd.contact_email)}
            ${zmcTextarea('Address', fd.contact_address, 6)}
            ${zmcInput('Collection Office', fd.collection_region)}
          </div>`
        );

        html += zmcBlock(
          `<i class="fa-regular fa-building"></i> News Agency Details`,
          `<div class="row g-3">
            ${zmcInput('Agency Name', fd.agency_name)}
            ${zmcTextarea('Postal Address', fd.agency_postal_address, 6)}
            ${zmcTextarea('Physical Address', fd.agency_physical_address, 6)}
            ${zmcInput('Telephone', fd.agency_telephone)}
            ${zmcInput('Fax', fd.agency_fax)}
            ${zmcInput('Email', fd.agency_email)}
            ${zmcInput('Website', fd.agency_website)}
            ${zmcInput('Owner(s)', fd.agency_owners)}
            ${zmcInput('Owner Nationality', fd.agency_owners_nationality)}
            ${zmcTextarea('News Gathering Activities', fd.agency_activities)}
            ${zmcInput('Media Houses Served', fd.agency_clients)}
            ${zmcInput('Number of Staff', fd.agency_staff_count)}
            ${zmcInput('Date Established', fd.agency_date_established)}
          </div>`
        );

        const directors = Array.isArray(data.directors) ? data.directors : [];
        if (directors.length > 0) {
          let dRows = '';
          directors.forEach(d => {
            dRows += `<tr><td>${zmcFmt(d.full_name || d.name)}</td><td>${zmcFmt(d.id_passport)}</td><td>${zmcFmt(d.nationality)}</td><td>${zmcFmt(d.address)}</td></tr>`;
          });
          html += zmcBlock(
            `<i class="fa-solid fa-people-group"></i> Directors / Proprietors`,
            `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>ID/Passport</th><th>Nationality</th><th>Address</th></tr></thead><tbody>${dRows}</tbody></table></div>`
          );
        }

        const managers = Array.isArray(data.managers) ? data.managers : [];
        if (managers.length > 0) {
          let mRows = '';
          managers.forEach(m => {
            mRows += `<tr><td>${zmcFmt(m.full_name || m.name)}</td><td>${zmcFmt(m.position)}</td><td>${zmcFmt(m.qualification)}</td><td>${zmcFmt(m.experience)}</td></tr>`;
          });
          html += zmcBlock(
            `<i class="fa-solid fa-user-gear"></i> Managers`,
            `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Name</th><th>Position</th><th>Qualification</th><th>Experience</th></tr></thead><tbody>${mRows}</tbody></table></div>`
          );
        }
      }

      // "All Submitted Fields" — grouped into form sections, in the order the applicant filled them
      const fdAll = (data.form_data && typeof data.form_data === 'object') ? data.form_data : {};
      const flatFd = Object.assign({}, fdAll);
      if (flatFd.ap1 && typeof flatFd.ap1 === 'object' && !Array.isArray(flatFd.ap1)) {
        for (const k in flatFd.ap1) if (!(k in flatFd)) flatFd[k] = flatFd.ap1[k];
      }
      const skipKeys = new Set(['current_step','registration_scope','journalist_scope','ap1',
        'directors','managers','senior_managers','directors_rows','managers_rows','custom_fields']);

      const scope = (fdAll.journalist_scope || fdAll.registration_scope || '').toString().toLowerCase();
      const isLocal = scope === 'local';

      // Section definitions mirror the form's step order
      const SECTIONS = [
        ['Personal Information', ['title','surname','first_name','other_names','dob','date_of_birth','birth_place','marital_status','gender','sex','nationality','origin']],
        ['Identification', ['national_reg_no','national_id','id_number','id_passport_number','passport_no','passport_expiry','passport_issued_at']],
        ['Contact Details', ['address','residential_address','address_in_zimbabwe','zim_address','zim_local_address','drivers_licence_no','phone_country_code','phone','phone_number','cell_number','email','email_address','collection_region']],
        ['Employment & Professional', ['employment_type','medium_type','medium_type_other','designation','designation_other','media_org','media_org_address','media_org_phone','media_org_email','editor_publisher_name','immediate_supervisor','string_for_orgs','string_for_details','employer_name','assignment_brief']],
        ['Foreign Travel', ['journalist_based_country','first_time_in_zim','last_in_zim_when','arrived_on','arrival_date','arrival_mode','departing_on','departure_date','port_of_entry','port_entry','special_assignment']],
        ['Organisation Details', ['org_name','organization_name','media_house_name','reg_no','registration_number','website','head_office','organization_address','postal_address','org_head_office','org_mailing_address','category','media_type','operating_model','mass_media_category','mass_media_activity','entity_name','mass_media_activities']],
        ['Representative Office', ['rep_office_name','rep_office_email','rep_office_address','rep_office_activities','rep_office_wholly_owned']],
        ['CEO Details', ['ceo_name','ceo_surname','ceo_nationality','ceo_qualifications','ceo_experience']],
        ['Contact Person', ['contact_person','contact_name','contact_surname','contact_email','contact_phone','contact_address','contact_phone_country_code']],
        ['Renewal / Replacement', ['previous_reference','changes','changes_details','replacement_reason','return_damaged']],
        ['Declarations & Conduct', ['q_convicted','q_convicted_details','q_judgment_debt','q_judgment_debt_details','q_insolvent_a','q_insolvent_b','q_insolvent_c','q_insolvent_d','q_insolvent_details','declaration_agreed','declaration_confirmed','declaration_date','other_relevant_info']],
      ];

      const isFilled = (v) => {
        if (v === null || v === undefined || v === '') return false;
        if (Array.isArray(v) && v.length === 0) return false;
        if (typeof v === 'object' && !Array.isArray(v) && Object.keys(v).length === 0) return false;
        return true;
      };

      const rowHtml = (k, v) => `<tr><td class="bg-light fw-semibold text-muted ps-3" style="width:35%;">${zmcEsc(zmcHumanize(k))}</td><td class="ps-3">${zmcRenderValue(v)}</td></tr>`;

      const covered = new Set();
      let sectionsHtml = '';

      // Render fields per section in the order the applicant filled them
      SECTIONS.forEach(([title, keys]) => {
        if (isLocal && title === 'Foreign Travel') return;
        const allowed = new Set(keys);
        const rows = [];
        // Iterate in form_data insertion order to preserve fill order
        Object.keys(flatFd).forEach(k => {
          if (!allowed.has(k) || covered.has(k)) return;
          const v = flatFd[k];
          if (!isFilled(v)) return;
          rows.push(rowHtml(k, v));
          covered.add(k);
        });
        if (rows.length) {
          sectionsHtml += `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-folder-open"></i> ${zmcEsc(title)}</div><div class="table-responsive"><table class="table table-sm align-middle mb-0 zmc-table-lite"><tbody class="small">${rows.join('')}</tbody></table></div></div>`;
        }
      });

      // Indexed groups: referees, academic, professional, past work links
      const collectIndexed = (prefixes) => {
        const idxSet = new Set();
        Object.keys(flatFd).forEach(k => {
          prefixes.forEach(p => {
            const m = k.match(new RegExp('^' + p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '_(\\d+)$'));
            if (m) idxSet.add(parseInt(m[1], 10));
          });
        });
        return [...idxSet].sort((a,b) => a-b);
      };

      const buildIndexedTable = (title, indices, columns) => {
        if (!indices.length) return '';
        const head = '<tr>' + columns.map(c => `<th>${zmcEsc(c.label)}</th>`).join('') + '</tr>';
        const body = indices.map(i => '<tr>' + columns.map(c => {
          const key = c.key.replace('{i}', i);
          covered.add(key);
          const v = flatFd[key];
          return `<td>${isFilled(v) ? zmcRenderValue(v) : '<span class="text-muted">—</span>'}</td>`;
        }).join('') + '</tr>').join('');
        return `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-folder-open"></i> ${zmcEsc(title)}</div><div class="table-responsive"><table class="table table-sm align-middle mb-0 zmc-table-lite"><thead>${head}</thead><tbody class="small">${body}</tbody></table></div></div>`;
      };

      const academicIdx = collectIndexed(['highest_academic_year','highest_academic_institution','highest_academic_qualification']);
      sectionsHtml += buildIndexedTable('Highest Academic Qualifications', academicIdx, [
        {label:'Year', key:'highest_academic_year_{i}'},
        {label:'Institution', key:'highest_academic_institution_{i}'},
        {label:'Qualification', key:'highest_academic_qualification_{i}'},
      ]);

      const professionalIdx = collectIndexed(['professional_year','professional_institution','professional_qualification']);
      sectionsHtml += buildIndexedTable('Professional Qualifications', professionalIdx, [
        {label:'Year', key:'professional_year_{i}'},
        {label:'Institution', key:'professional_institution_{i}'},
        {label:'Qualification', key:'professional_qualification_{i}'},
      ]);

      const pastWorkIdx = collectIndexed(['past_work_link_url','past_work_link_desc']);
      sectionsHtml += buildIndexedTable('Past Work Links', pastWorkIdx, [
        {label:'URL', key:'past_work_link_url_{i}'},
        {label:'Description', key:'past_work_link_desc_{i}'},
      ]);

      const refereeIdx = collectIndexed(['referee_name','referee_address','referee_phone']);
      sectionsHtml += buildIndexedTable('Referees', refereeIdx, [
        {label:'Name', key:'referee_name_{i}'},
        {label:'Address', key:'referee_address_{i}'},
        {label:'Phone', key:'referee_phone_{i}'},
      ]);

      // Anything else not yet rendered → "Other Information" in fill order
      const leftoverRows = [];
      Object.keys(flatFd).forEach(k => {
        if (skipKeys.has(k) || covered.has(k)) return;
        const v = flatFd[k];
        if (!isFilled(v)) return;
        leftoverRows.push(rowHtml(k, v));
      });
      if (leftoverRows.length) {
        sectionsHtml += `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-folder-open"></i> Other Information</div><div class="table-responsive"><table class="table table-sm align-middle mb-0 zmc-table-lite"><tbody class="small">${leftoverRows.join('')}</tbody></table></div></div>`;
      }

      if (sectionsHtml) {
        html += `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-rectangle-list"></i> All Submitted Fields</div>${sectionsHtml}</div>`;
      }

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

      const docs = Array.isArray(data.documents) ? data.documents : [];
      let docRows = docs.length ? '' : `<tr><td colspan="3" class="text-muted text-center">—</td></tr>`;
      docs.forEach(doc => {
        const open = doc.url
          ? `<a href="${doc.url}" target="_blank" class="btn btn-sm zmc-icon-btn btn-outline-primary" title="Open document"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>`
          : `<span class="text-muted">—</span>`;
        docRows += `<tr><td class="fw-bold">${zmcFmt(doc.document_type)}</td><td>${zmcFmt(doc.original_name || doc.file_name)}</td><td class="text-end">${open}</td></tr>`;
      });

      html += zmcBlock(
        `<i class="fa-regular fa-folder-open"></i> Attachments`,
        `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Type</th><th>File</th><th class="text-end">Open</th></tr></thead><tbody>${docRows}</tbody></table></div>`
      );

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
    if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }

    document.addEventListener('click', function(e){
      const btn = e.target.closest('.js-open-modal');
      if(!btn) return;
      const target = btn.getAttribute('data-target');
      if(target) zmcOpenModal(target);
    });

    document.addEventListener('click', function(e){
      const btn = e.target.closest('.js-view-more');
      if(!btn) return;
      const appId = btn.getAttribute('data-app-id');
      if(!appId) return;

      zmcOpenModal('#appDetailsModal');
      loadApplicationDetails(appId);
    });
  });
</script>
@endpush
@endsection
