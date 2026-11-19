@extends('layouts.portal')
@section('title', 'Registrar Dashboard')

@section('content')
@include('staff.partials.tabs', ['label' => 'Registrar sections', 'tabs' => [['staff.registrar.dashboard','Overview','ri-dashboard-3-line'],['staff.registrar.applications.list','Applications','ri-file-list-3-line'],['staff.registrar.payment-oversight','Payment statuses','ri-eye-line'],['staff.registrar.processed','Processed','ri-stack-line']]])
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
        Registrar Dashboard
      </h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Review and supervise applications from the Accreditation Officer. Reviewed items proceed to <b>Accounts</b>.
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('staff.reports.index') }}" class="btn btn-outline-secondary btn-sm" title="Operational Reports">
        <i class="ri-bar-chart-line me-1"></i> Reports
      </a>
      <a href="{{ route('staff.registrar.notices-events') }}" class="btn btn-outline-primary btn-sm" title="Notices & Events">
        <i class="ri-newspaper-line me-1"></i> Notices & Events
      </a>
      <a href="{{ route('staff.registrar.news') }}" class="btn btn-outline-primary btn-sm" title="News & Press Statements">
        <i class="ri-newspaper-line me-1"></i> News
      </a>
      @php
        $regionNames = auth()->user()->assignedRegions()->pluck('name');
        $regionLabel = $regionNames->isNotEmpty()
            ? $regionNames->implode(', ')
            : 'All regions';
      @endphp
      <span class="zmc-pill zmc-pill-dark" title="{{ $regionLabel }}">
        <i class="ri-map-pin-user-line"></i>
        <span>Region: {{ \Illuminate\Support\Str::limit($regionLabel, 40) }}</span>
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

  @if(session('error'))
    <div class="alert alert-danger d-flex align-items-start gap-2">
      <i class="ri-error-warning-line" style="font-size: var(--font-size-lg); line-height: 1;"></i>
      <div>{{ session('error') }}</div>
    </div>
  @endif


  @php
    $k = $kpis ?? [
      'todays_applications' => 0,
      'pending_reviews' => 0,
      'renewals_due' => 0,
      'payments_pending' => 0,
    ];

    $detailsUrlTemplate = route('staff.applications.details', ['application' => '__ID__']);

    $returnUrl = fn($id) => route('staff.registrar.applications.return', $id);
    $approveUrl = fn($id) => route('staff.registrar.applications.approve', $id);
    $approveForPaymentUrl = fn($id) => route('staff.registrar.applications.approve-for-payment', $id);
    $rejectUrl = fn($id) => route('staff.registrar.applications.reject', $id);
@endphp

  {{-- KPIs --}}
  <div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">All Applications</div>
            <div class="h3 fw-black mb-0">{{ number_format($k['total_applications'] ?? 0) }}</div>
          </div>
          <div class="icon-box text-primary"><i class="ri-folders-line"></i></div>
        </div>
        <div class="mt-2 small text-muted">
          {{ $k['awaiting_registrar'] ?? 0 }} awaiting registrar
        </div>
        <div class="mt-2">
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="zmc-card h-100 border-success">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">Approved Today</div>
            <div class="h3 fw-black mb-0 text-success">{{ $k['approved_today'] ?? 0 }}</div>
          </div>
          <div class="icon-box text-success"><i class="ri-checkbox-circle-line"></i></div>
        </div>
        <div class="mt-2 small text-muted">This week: {{ $k['approved_this_week'] ?? 0 }}</div>
        <div class="mt-2">
          <a href="{{ route('staff.registrar.approved-applications') }}" class="btn btn-sm btn-outline-success w-100">
            <i class="ri-eye-line me-1"></i> View Approved
          </a>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="zmc-card h-100 border-warning">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small fw-bold">Returned for Correction</div>
            <div class="h3 fw-black mb-0">{{ $k['returned_to_officer'] ?? 0 }}</div>
          </div>
          <div class="icon-box text-warning"><i class="ri-arrow-go-back-line"></i></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Second row of KPIs --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="zmc-card h-100 border-primary">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-bold">Awaiting Payment Approval</div>
                    <div class="h3 fw-black mb-0 text-primary">{{ $k['awaiting_payment_approval'] ?? 0 }}</div>
                </div>
                <div class="icon-box text-primary"><i class="ri-money-dollar-circle-line"></i></div>
            </div>
            <div class="mt-2 small text-muted">Forward to Accounts</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="zmc-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-bold">Certificates Generated</div>
                    <div class="h3 fw-black mb-0">{{ $k['certificates_generated_today'] ?? 0 }}</div>
                </div>
                <div class="icon-box text-info"><i class="ri-file-shield-line"></i></div>
            </div>
            <div class="mt-2 small text-muted">Today</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="zmc-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-bold">Print Jobs Today</div>
                    <div class="h3 fw-black mb-0">{{ $k['prints_today'] ?? 0 }}</div>
                </div>
                <div class="icon-box text-primary"><i class="ri-printer-line"></i></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="zmc-card h-100 border-danger">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-bold">Flagged Reprints</div>
                    <div class="h2 fw-black mb-0 text-danger">{{ $k['flagged_reprints'] ?? 0 }}</div>
                </div>
                <div class="icon-box text-danger"><i class="ri-error-warning-fill"></i></div>
            </div>
            <div class="mt-1 small text-muted">Prints > Threshold</div>
        </div>
    </div>
  </div>

  {{-- Quick Search --}}
  <div class="zmc-card mb-4 bg-light border-0">
    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">

        <div class="col-md-2">
            <label class="small fw-bold text-muted">Application Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="accreditation" {{ request('type') == 'accreditation' ? 'selected' : '' }}>Accreditation</option>
                <option value="registration" {{ request('type') == 'registration' ? 'selected' : '' }}>Registration</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="small fw-bold text-muted">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, reference or media house..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-dark btn-sm w-100">Search</button>
        </div>
        <div class="col-md-1">
            <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-outline-dark btn-sm w-100">Reset</a>
        </div>
    </form>
  </div>



  {{-- Table --}}
  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0">
        <i class="ri-list-check-2 me-2" style="color:var(--zmc-accent)"></i> Registrar Queue
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
            $canDecide = in_array($status, ['registrar_review','paid_confirmed','accounts_review','returned_to_accounts'], true);
            $canApproveForPayment = $status === 'registrar_review' && $app->payment_status !== 'paid' && !$app->registrar_reviewed_at;
            $isRegistration = ($app->application_type ?? '') === 'registration';
            $cats = $isRegistration ? \App\Models\Application::massMediaCategories() : \App\Models\Application::accreditationCategories();
            $currentCat = $isRegistration ? $app->media_house_category_code : $app->accreditation_category_code;
            $ref = $app->reference ?: ('APP-' . str_pad($app->id, 5, '0', STR_PAD_LEFT));
          @endphp

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

                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-warning js-open-modal" data-target="#reassignModal{{ $app->id }}" data-bs-toggle="tooltip" title="Reassign Category">
                  <i class="fa-solid fa-award"></i>
                </button>

                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-success js-open-modal" data-target="{{ $canApproveForPayment ? '#approveForPaymentModal' . $app->id : '#approveModal' . $app->id }}" data-bs-toggle="tooltip" title="{{ $canApproveForPayment ? 'Approve for Payment' : 'Approve' }}">
                  <i class="fa-solid fa-check"></i>
                </button>

                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-danger js-open-modal" data-target="#rejectModal{{ $app->id }}" data-bs-toggle="tooltip" title="Return for Correction">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </td>
          </tr>

          {{-- Modals --}}
          @push('zmc_modals')
            {{-- Reassign Category Modal --}}
            <div class="modal fade" id="reassignModal{{ $app->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ route('staff.registrar.applications.reassign-category', $app) }}">
                  @csrf
                  <div class="modal-header zmc-modal-header">
                    <div>
                      <div class="zmc-modal-title">
                        <i class="fa-solid fa-award me-2" style="color:var(--zmc-accent-dark)"></i>
                        Reassign Category
                        <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span>
                      </div>
                      <div class="zmc-modal-sub">Change the assigned category for this application.</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label zmc-lbl">Current Category</label>
                      <input type="text" class="form-control zmc-input" value="{{ $currentCat ?? 'NOT ASSIGNED' }}" readonly>
                    </div>

                    <div class="mb-3">
                      <label class="form-label zmc-lbl">New Category <span class="text-danger">*</span></label>
                      <select name="category_code" class="form-select zmc-input" required>
                        <option value="">-- Select new category --</option>
                        @foreach($cats as $code => $name)
                          <option value="{{ $code }}" {{ $currentCat == $code ? 'selected' : '' }}>
                            {{ $code }}
                          </option>
                        @endforeach
                      </select>
                    </div>

                    <div class="mb-3">
                      <label class="form-label zmc-lbl">Reason for Reassignment <span class="text-danger">*</span></label>
                      <textarea name="reason" class="form-control zmc-input" rows="3" required placeholder="State why the category is being changed..."></textarea>
                    </div>
                  </div>
                  <div class="modal-footer zmc-modal-footer">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                      <i class="fa-solid fa-rotate me-1"></i>Reassign Category
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
                      <div class="zmc-modal-title">
                        <i class="fa-solid fa-check me-2" style="color:var(--zmc-accent-dark)"></i>
                        Approve application
                        <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span>
                      </div>
                      <div class="zmc-modal-sub">This will move the application to Production.</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    @php
                      $isRegistration = ($app->application_type ?? '') === 'registration';
                      $cats = $isRegistration ? \App\Models\Application::massMediaCategories() : \App\Models\Application::accreditationCategories();
                      $label = $isRegistration ? 'Mass Media Category' : 'Accreditation Category';
                      $currentCat = $isRegistration ? $app->media_house_category_code : $app->accreditation_category_code;
                    @endphp

                    <div class="mb-3">
                      <label class="form-label zmc-lbl">{{ $label }} <span class="text-danger">*</span></label>
                      <select name="category_code" class="form-select zmc-input" required>
                        <option value="">-- Select category --</option>
                        @foreach($cats as $code => $name)
                          <option value="{{ $code }}" {{ $currentCat == $code ? 'selected' : '' }}>{{ $code }} - {{ $name }}</option>
                        @endforeach
                      </select>
                    </div>

                    <label class="form-label zmc-lbl">Notes (optional)</label>
                    <textarea name="decision_notes" class="form-control zmc-input" rows="4" placeholder="Add any notes (optional)"></textarea>
                    <div class="form-text mt-2">Next stage: <b>Production</b> (card / certificate generation).</div>
                  </div>

                  <div class="modal-footer zmc-modal-footer">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                      <i class="fa-solid fa-check me-1"></i>Approve
                    </button>
                  </div>
                </form>
              </div>
            </div>

            {{-- Approve For Payment Modal --}}
            <div class="modal fade" id="approveForPaymentModal{{ $app->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ $approveForPaymentUrl($app->id) }}">
                  @csrf
                  <div class="modal-header zmc-modal-header">
                    <div>
                      <div class="zmc-modal-title">
                        <i class="fa-solid fa-money-bill me-2" style="color:var(--zmc-accent-dark)"></i>
                        Approve for Payment
                        <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span>
                      </div>
                      <div class="zmc-modal-sub">This will forward the application to Accounts for payment processing.</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    <label class="form-label zmc-lbl">Notes (optional)</label>
                    <textarea name="decision_notes" class="form-control zmc-input" rows="4" placeholder="Add any notes for Accounts..."></textarea>
                  </div>

                  <div class="modal-footer zmc-modal-footer">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                      <i class="fa-solid fa-check me-1"></i>Forward to Accounts
                    </button>
                  </div>
                </form>
              </div>
            </div>

            {{-- Reject Modal --}}
            <div class="modal fade" id="rejectModal{{ $app->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ $rejectUrl($app->id) }}">
                  @csrf
                  <div class="modal-header zmc-modal-header">
                    <div>
                      <div class="zmc-modal-title">
                        <i class="fa-solid fa-xmark me-2" style="color:var(--zmc-accent-dark)"></i>
                        Return for Correction
                        <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span>
                      </div>
                      <div class="zmc-modal-sub">Reason will be visible to the applicant.</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    <label class="form-label zmc-lbl">Reason / notes <span class="text-danger">*</span></label>
                    <textarea name="decision_notes" class="form-control zmc-input" rows="4" required placeholder="Provide a clear reason for return"></textarea>
                  </div>

                  <div class="modal-footer zmc-modal-footer">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">
                      <i class="fa-solid fa-xmark me-1"></i>Return for Correction
                    </button>
                  </div>
                </form>
              </div>
            </div>
          @endpush

        @empty
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">No applications in registrar queue.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $applications->links() }}
  </div>




  {{-- Activity feed --}}
  <div class="zmc-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="fw-bold"><i class="ri-pulse-line me-1"></i> Activity feed</div>
      <div class="small text-muted">Latest 15 high-value actions</div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead>
          <tr class="text-muted small">
            <th style="width:170px;">Time</th>
            <th style="width:160px;">Actor</th>
            <th style="width:180px;">Action</th>
            <th>Reference</th>
            <th style="width:120px;">Module</th>
            <th style="width:160px;">Status / Outcome</th>
          </tr>
        </thead>
        <tbody>
        @forelse(($activity ?? []) as $log)
          @php
            $ref = null;
            try { $ref = optional($log->entity)->reference; } catch (\Throwable $e) {}
            $ref = $ref ?: ('APP-' . str_pad((int)($log->entity_id ?? 0), 5, '0', STR_PAD_LEFT));
            $meta = is_array($log->meta ?? null) ? $log->meta : [];
            $module = $meta['module'] ?? ($log->entity_type ? class_basename($log->entity_type) : 'General');
            $outcome = $log->to_status ?: ($meta['status'] ?? 'Completed');
          @endphp
          <tr>
            <td class="small text-muted">{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y H:i') }}</td>
            <td class="small">{{ $log->user?->name ?? ($log->user_role ?? 'System') }}</td>
            <td class="small fw-bold">{{ str_replace('_',' ', (string)$log->action) }}</td>
            <td class="small">{{ $ref }}</td>
            <td class="small text-muted">{{ $module }}</td>
            <td class="small text-muted">{{ $outcome }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-muted small">No recent high-value activity.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @stack('zmc_modals')

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

  function zmcBlock(titleHtml, bodyHtml){

    return `
      <div class="zmc-mdl-block">
        <div class="zmc-mdl-title">${titleHtml}</div>
        ${bodyHtml}
      </div>
    `;
  }

  function zmcInput(label, value, col = 4){
    return `
      <div class="col-12 col-md-${col}">
        <label class="form-label zmc-lbl">${label}</label>
        <input type="text" class="form-control zmc-input" value="${zmcFmt(value)}" readonly>
      </div>
    `;
  }

  function zmcTextarea(label, value, col = 12){
    return `
      <div class="col-12 col-md-${col}">
        <label class="form-label zmc-lbl">${label}</label>
        <textarea class="form-control zmc-input" rows="3" readonly>${zmcFmt(value)}</textarea>
      </div>
    `;
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


        // Previous Applications Block
        const prevApps = Array.isArray(data.previous_applications) ? data.previous_applications : [];
        if (prevApps.length > 0) {
          let prevRows = prevApps.map(pa => `
            <tr>
              <td>${zmcFmt(pa.reference)}</td>
              <td class="text-capitalize">${zmcFmt(pa.type)}</td>
              <td class="text-capitalize">${zmcFmt(pa.scope)}</td>
              <td><span class="badge bg-light text-dark border">${zmcFmt(pa.status)}</span></td>
              <td>${zmcFmt(pa.date)}</td>
            </tr>
          `).join('');

          html += zmcBlock(
            `<i class="fa-solid fa-history"></i> Previous Applications`,
            `<div class="table-responsive"><table class="table table-sm align-middle zmc-table-lite"><thead><tr><th>Reference</th><th>Type</th><th>Scope</th><th>Status</th><th>Date</th></tr></thead><tbody>${prevRows}</tbody></table></div>`
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

      if (formCode === 'AP1') {
        const s = data.ap1 || {};

        html += zmcBlock(
          `<i class="fa-regular fa-building"></i> Organisation details`,
          `
          <div class="row g-3">
            ${zmcInput('Category', s.category)}
            ${zmcInput('Service name', s.service_name)}
            ${zmcInput('Operating model', s.operating_model)}
            ${zmcInput('Organisation', s.org_name)}
            ${zmcInput('Reg no', s.reg_no)}
            ${zmcInput('Website', s.website)}
            ${zmcTextarea('Head office', s.head_office, 6)}
            ${zmcTextarea('Postal address', s.postal_address, 6)}
          </div>
          `
        );

        html += zmcBlock(
          `<i class="fa-regular fa-address-book"></i> Contact`,
          `
          <div class="row g-3">
            ${zmcInput('Contact person', s.contact_person, 4)}
            ${zmcInput('Contact email', s.contact_email, 4)}
            ${zmcInput('Contact phone', s.contact_phone, 4)}
          </div>
          `
        );

        const directors = Array.isArray(data.directors) ? data.directors : [];
        const managers  = Array.isArray(data.managers) ? data.managers : [];

        let dRows = directors.length ? '' : `<tr><td colspan="5" class="text-muted text-center">—</td></tr>`;
        directors.forEach(d => {
          dRows += `
            <tr>
              <td>${zmcFmt(d.full_name || d.name || (d.first_name ? (d.first_name + ' ' + (d.surname||'')) : ''))}</td>
              <td>${zmcFmt(d.id_passport || d.id || d.id_number)}</td>
              <td>${zmcFmt(d.nationality)}</td>
              <td>${zmcFmt(d.role || d.occupation)}</td>
              <td>${zmcFmt(d.shareholding || d.shares)}</td>
            </tr>
          `;
        });

        html += zmcBlock(
          `<i class="fa-solid fa-people-group"></i> Directors`,
          `
          <div class="table-responsive">
            <table class="table table-sm align-middle zmc-table-lite">
              <thead>
                <tr>
                  <th>Full name</th><th>ID / Passport</th><th>Nationality</th><th>Role</th><th>Shareholding</th>
                </tr>
              </thead>
              <tbody>${dRows}</tbody>
            </table>
          </div>
          `
        );

        let mRows = managers.length ? '' : `<tr><td colspan="4" class="text-muted text-center">—</td></tr>`;
        managers.forEach(m => {
          mRows += `
            <tr>
              <td>${zmcFmt(m.full_name || m.name)}</td>
              <td>${zmcFmt(m.position || m.occupation)}</td>
              <td>${zmcFmt(m.qualification || m.qualifications)}</td>
              <td>${zmcFmt(m.experience || m.years_experience)}</td>
            </tr>
          `;
        });

        html += zmcBlock(
          `<i class="fa-solid fa-user-gear"></i> Managers`,
          `
          <div class="table-responsive">
            <table class="table table-sm align-middle zmc-table-lite">
              <thead>
                <tr>
                  <th>Full name</th><th>Position</th><th>Qualification</th><th>Experience</th>
                </tr>
              </thead>
              <tbody>${mRows}</tbody>
            </table>
          </div>
          `
        );
      }

      const docs = Array.isArray(data.documents) ? data.documents : [];
      let docRows = docs.length ? '' : `<tr><td colspan="3" class="text-muted text-center">—</td></tr>`;
      docs.forEach(doc => {
        const open = doc.url
          ? `<a href="${doc.url}" target="_blank" class="btn btn-sm zmc-icon-btn btn-outline-primary" title="Open document">
               <i class="fa-solid fa-arrow-up-right-from-square"></i>
             </a>`
          : `<span class="text-muted">—</span>`;
        docRows += `
          <tr>
            <td class="fw-bold">${zmcFmt(doc.document_type)}</td>
            <td>${zmcFmt(doc.original_name || doc.file_name)}</td>
            <td class="text-end">${open}</td>
          </tr>
        `;
      });

      html += zmcBlock(
        `<i class="fa-regular fa-folder-open"></i> Attachments`,
        `
        <div class="table-responsive">
          <table class="table table-sm align-middle zmc-table-lite">
            <thead>
              <tr><th>Type</th><th>File</th><th class="text-end">Open</th></tr>
            </thead>
            <tbody>${docRows}</tbody>
          </table>
        </div>
        `
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
