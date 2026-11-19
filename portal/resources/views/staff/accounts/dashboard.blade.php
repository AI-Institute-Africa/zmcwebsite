@extends('layouts.portal')
@section('title', 'Accounts & Payments Dashboard')

@section('content')
@include('staff.partials.tabs', ['label' => 'Accounts and payments sections', 'tabs' => [['staff.accounts.dashboard','Overview','ri-dashboard-3-line'],['staff.accounts.app-fee-verifications','App fees','ri-time-line'],['staff.accounts.reg-fee-verifications','Reg fees','ri-checkbox-circle-line'],['staff.accounts.paynow.transactions','PayNow','ri-bank-card-line'],['staff.accounts.reconciliation','Reconciliation','ri-git-merge-line'],['staff.accounts.waivers.requests','Waivers','ri-inbox-line']]])
@include('staff.partials.dashboard_theme')
<div class="staff-dashboard-theme">
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
        Accounts & Payments Dashboard
      </h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Confirm payments and pass successful items to <b>Production</b>.
      </div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <form action="{{ route('staff.accounts.dashboard') }}" method="GET" id="yearFilterForm" class="me-2">
        <select name="year" class="form-select fw-bold" style="height: 38px; min-width: 130px; background-color: #fff; color: #1a1a2e; border: 2px solid #f59e0b; border-radius: 8px; font-size: 14px; padding: 0 12px; cursor: pointer; box-shadow: 0 2px 6px rgba(245,158,11,0.25);" onchange="document.getElementById('yearFilterForm').submit()">
            @foreach($availableYears ?? [] as $y)
                <option value="{{ $y }}" {{ (isset($year) && $year == $y) ? 'selected' : '' }}>
                    Year: {{ $y }}
                </option>
            @endforeach
        </select>
      </form>
      @php
        $acctUser = auth()->user();
        $acctRegion = $acctUser?->region ?: 'All Regions';
      @endphp
      <span class="zmc-pill zmc-pill-dark">
        <i class="ri-map-pin-user-line"></i>
        <span>Region: {{ $acctRegion }}</span>
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
    $summaryTotal = method_exists($applications, 'total') ? $applications->total() : $items->count();

    $summaryPending = $items->filter(fn($x) => in_array(strtolower((string)($x->status ?? '')), [
      'accounts_review','paid_confirmed','returned_to_accounts'
    ], true))->count();

    $summaryPaid = $items->filter(fn($x) => strtolower((string)($x->status ?? '')) === 'paid_confirmed')->count();
    $summaryReturned = $items->filter(fn($x) => strtolower((string)($x->status ?? '')) === 'returned_to_accounts')->count();

    $detailsUrlTemplate = route('staff.applications.details', ['application' => '__ID__']);
    $commentUrlTemplate = url('/staff/applications/__ID__/comment');

    $paidUrl = fn($id) => route('staff.accounts.applications.paid', $id);
    $returnUrl = fn($id) => route('staff.accounts.applications.return', $id);

    // Accounts officers see a stripped-down modal: only Payment Details and
    // the applicant message thread. The /staff/accounts route is restricted
    // to `accounts_payments`, so this is effectively always true here, but we
    // keep the explicit check for defense-in-depth if route guards change.
    $activeStaffRole = session('active_staff_role');
    $accountsOnlyView = $activeStaffRole === 'accounts_payments';
  @endphp

  {{-- Summary cards --}}
  <style>
    .acct-kpi-label { font-size: 11px; font-weight: 700; color: #64748b; white-space: nowrap; }
    .acct-kpi-value { font-size: 22px; font-weight: 900; margin: 0; }
    .acct-kpi-icon  { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
  </style>
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="acct-kpi-label">Total Applications</div>
            <div class="acct-kpi-value">{{ $summaryTotal }}</div>
          </div>
          <div class="acct-kpi-icon text-primary"><i class="ri-folders-line"></i></div>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg">
      <a href="{{ route('staff.accounts.apps.pending') }}" class="text-decoration-none">
        <div class="zmc-card h-100">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="acct-kpi-label">Pending Action</div>
              <div class="acct-kpi-value">{{ $summaryPending }}</div>
            </div>
            <div class="acct-kpi-icon" style="border-left:3px solid var(--zmc-accent);border-radius:0;">
              <i class="ri-time-line" style="color:var(--zmc-accent)"></i>
            </div>
          </div>
        </div>
      </a>
    </div>

    {{-- Removed 'Special Cases' per request --}}
    {{--
    @if(isset($kpis) && isset($kpis['special_cases']))
    <div class="col-6 col-lg">
      <div class="zmc-card h-100" style="border-left: 3px solid #facc15;">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="acct-kpi-label">Special Cases</div>
            <div class="acct-kpi-value" style="color: #b45309;">{{ $kpis['special_cases'] }}</div>
          </div>
          <div class="acct-kpi-icon" style="background: rgba(250, 204, 21, 0.1);">
            <i class="ri-alert-line" style="color: #b45309;"></i>
          </div>
        </div>
      </div>
    </div>
    @endif
    --}}

    <div class="col-6 col-lg">
      <a href="{{ route('staff.accounts.apps.paid') }}" class="text-decoration-none">
        <div class="zmc-card h-100">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="acct-kpi-label">Approved (Paid)</div>
              <div class="acct-kpi-value text-success">{{ $kpis['summary_paid'] ?? 0 }}</div>
            </div>
            <div class="acct-kpi-icon text-success"><i class="ri-check-double-line"></i></div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-lg">
      <a href="{{ route('staff.accounts.levies.pending') }}" class="text-decoration-none">
        <div class="zmc-card h-100">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="acct-kpi-label">Pending Levies</div>
              <div class="acct-kpi-value" style="color: #f59e0b;">
                @php
                  $pendingLevies = \App\Models\LevyRecord::where('payment_status', 'pending')->count();
                @endphp
                {{ $pendingLevies }}
              </div>
            </div>
            <div class="acct-kpi-icon" style="color: #f59e0b;"><i class="ri-money-dollar-circle-line"></i></div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-6 col-lg">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="acct-kpi-label">Returned for Correction</div>
            <div class="acct-kpi-value">{{ $summaryReturned }}</div>
          </div>
          <div class="acct-kpi-icon text-warning"><i class="ri-arrow-go-back-line"></i></div>
        </div>
      </div>
    </div>
  </div>

  {{-- Quick Links --}}
  <div class="zmc-card mb-4 border-0 bg-light">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="fw-bold small text-muted me-2"><i class="ri-links-line me-1"></i>Quick Access:</span>
      <a href="{{ route('staff.accounts.paynow.transactions') }}" class="btn btn-sm btn-outline-dark"><i class="ri-bank-card-line me-1"></i>PayNow Transactions</a>
      <a href="{{ route('staff.accounts.app-fee-verifications') }}" class="btn btn-sm btn-outline-dark"><i class="ri-upload-2-line me-1"></i>App Fee Verifs</a>
      <a href="{{ route('staff.accounts.reg-fee-verifications') }}" class="btn btn-sm btn-outline-dark"><i class="ri-upload-2-line me-1"></i>Reg Fee Verifs</a>
      <a href="{{ route('staff.accounts.apps.pending') }}" class="btn btn-sm btn-outline-warning"><i class="ri-hourglass-line me-1"></i>Pending Payments</a>
      <a href="{{ route('staff.accounts.apps.paid') }}" class="btn btn-sm btn-outline-success"><i class="ri-shield-check-line me-1"></i>Approved (Paid)</a>
      <a href="{{ route('staff.accounts.levies.pending') }}" class="btn btn-sm btn-outline-warning"><i class="ri-money-dollar-circle-line me-1"></i>Pending Levies</a>
      <a href="{{ route('staff.accounts.levies.verified') }}" class="btn btn-sm btn-outline-success"><i class="ri-check-double-line me-1"></i>Verified Levies</a>
      <a href="{{ route('staff.accounts.reports.revenue') }}" class="btn btn-sm btn-outline-primary"><i class="ri-funds-line me-1"></i>Revenue Reports</a>
    </div>
  </div>

  {{-- Filter Section --}}
  @if(isset($kpis))
  <div class="zmc-card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('staff.accounts.dashboard') }}" class="row g-3 align-items-end">
        @if(isset($year))
          <input type="hidden" name="year" value="{{ $year }}">
        @endif
        <div class="col-md-3">
          <label class="form-label small fw-bold">Payment Submission Method</label>
          <select name="submission_method" class="form-select form-select-sm">
            <option value="">All Methods</option>
            <option value="paynow_reference" {{ request('submission_method') === 'paynow_reference' ? 'selected' : '' }}>
              PayNow ({{ $kpis['paynow_submissions'] ?? 0 }})
            </option>
            <option value="proof_upload" {{ request('submission_method') === 'proof_upload' ? 'selected' : '' }}>
              Proof Upload ({{ $kpis['proof_submissions'] ?? 0 }})
            </option>
            <option value="waiver" {{ request('submission_method') === 'waiver' ? 'selected' : '' }}>
              Waiver ({{ $kpis['waiver_submissions'] ?? 0 }})
            </option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="ri-filter-line me-1"></i>Filter
          </button>
        </div>
        <div class="col-md-2">
          <a href="{{ route('staff.accounts.dashboard') }}" class="btn btn-light border btn-sm w-100">
            <i class="ri-refresh-line me-1"></i>Reset
          </a>
        </div>
        <div class="col-md-5 text-end">
          <div class="small text-muted">
            <span class="badge bg-light text-dark border me-2">
              <i class="ri-inbox-line me-1"></i>No Submission: {{ $kpis['no_submission'] ?? 0 }}
            </span>
            <span class="badge bg-light text-dark border">
              <i class="ri-folder-line me-1"></i>Total: {{ $kpis['total_pending'] ?? 0 }}
            </span>
          </div>
        </div>
      </form>
    </div>
  </div>
  @endif

  {{-- Table --}}
  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0">
        <i class="ri-list-check-2 me-2" style="color:var(--zmc-accent)"></i> Accounts queue
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
            // Match the same payment-review states accepted by markPaid().
            // Previously the icon was disabled for newly submitted proofs
            // still carrying the awaiting_accounts_verification status.
            $canAct = in_array($status, [
              'accounts_review',
              'awaiting_accounts_verification',
              'returned_to_accounts',
              'pending_accounts_from_registrar',
              'registrar_approved',
              'registrar_approved_pending_reg_fee',
            ], true);

            $ref = $app->reference ?? ('APP-' . str_pad((int)$app->id, 5, '0', STR_PAD_LEFT));

            // Resolve uploaded payment proof (column on applications, or attached document)
            $proofPath = $app->payment_proof_path ?? null;
            if (!$proofPath) {
              $proofDoc = $app->documents?->firstWhere('doc_type', 'proof_of_payment');
              $proofPath = $proofDoc?->file_path;
            }
            $proofUrl = $proofPath ? asset('storage/' . ltrim($proofPath, '/')) : null;
          @endphp

          <tr>
            @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
            <td class="text-end">
              <div class="zmc-action-strip">

                {{-- Return to Officer --}}
                <button
                  type="button"
                  class="btn btn-sm zmc-icon-btn btn-outline-dark js-open-modal"
                  data-target="#returnModal{{ $app->id }}"
                  @if(!$canAct) disabled @endif
                  data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Return to Officer"
                >
                  <i class="fa-regular fa-comment-dots"></i>
                </button>

                {{-- View Payment Proof --}}
                @if($proofUrl)
                  <a
                    href="{{ $proofUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-sm zmc-icon-btn btn-outline-info"
                    data-bs-toggle="tooltip" data-bs-placement="top"
                    title="View uploaded payment proof"
                  >
                    <i class="ri-attachment-2"></i>
                  </a>
                @else
                  <button
                    type="button"
                    class="btn btn-sm zmc-icon-btn btn-outline-secondary"
                    disabled
                    data-bs-toggle="tooltip" data-bs-placement="top"
                    title="No payment proof uploaded"
                  >
                    <i class="ri-attachment-2"></i>
                  </button>
                @endif

                {{-- View --}}
                <button
                  type="button"
                  class="btn btn-sm zmc-icon-btn btn-outline-primary js-view-more"
                  data-app-id="{{ $app->id }}"
                  data-bs-toggle="tooltip" data-bs-placement="top"
                  title="View application"
                >
                  <i class="fa-regular fa-eye"></i>
                </button>

                {{-- Mark Paid --}}
                <button
                  type="button"
                  class="btn btn-sm zmc-icon-btn btn-outline-success js-open-modal"
                  data-target="#paidModal{{ $app->id }}"
                  @if(!$canAct) disabled @endif
                  data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Mark paid"
                >
                  <i class="fa-solid fa-check"></i>
                </button>

              </div>
            </td>
          </tr>

          @push('zmc_modals')
            {{-- Return Modal --}}
            <div class="modal fade" id="returnModal{{ $app->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <form class="modal-content" method="POST" action="{{ $returnUrl($app->id) }}">
                  @csrf

                  <div class="modal-header zmc-modal-header">
                    <div>
                      <div class="zmc-modal-title">
                        <i class="fa-regular fa-message me-2" style="color:var(--zmc-accent-dark)"></i>
                        Return to Officer
                        <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span>
                      </div>
                      <div class="zmc-modal-sub">Send a note back to the Accreditation Officer.</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    <label class="form-label zmc-lbl">Notes <span class="text-danger">*</span></label>
                    <textarea name="notes" class="form-control zmc-input" rows="4" required placeholder="Why is this being returned? What needs to be corrected?"></textarea>
                  </div>

                  <div class="modal-footer zmc-modal-footer">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark fw-bold">
                      <i class="fa-solid fa-paper-plane me-1"></i>Send / return
                    </button>
                  </div>
                </form>
              </div>
            </div>

            {{-- Paid Modal --}}
            <div class="modal fade" id="paidModal{{ $app->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="POST" action="{{ $paidUrl($app->id) }}">
                  @csrf

                  <div class="modal-header zmc-modal-header">
                    <div>
                      <div class="zmc-modal-title">
                        <i class="fa-solid fa-check me-2" style="color:var(--zmc-yellow)"></i>
                        Confirm payment
                        <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span>
                      </div>
                      <div class="zmc-modal-sub">This will move the application to Registrar review.</div>
                    </div>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <div class="modal-body">
                    <label class="form-label zmc-lbl">Notes (optional)</label>
                    <textarea name="notes" class="form-control zmc-input" rows="4" placeholder="Add any notes (optional)"></textarea>
                  </div>

                  <div class="modal-footer zmc-modal-footer">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">
                      <i class="fa-solid fa-check me-1"></i>Mark paid
                    </button>
                  </div>
                </form>
              </div>
            </div>
          @endpush

        @empty
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">No applications found.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $applications->links() }}
  </div>

  @stack('zmc_modals')
  
  {{-- Recently Confirmed Payments --}}
  <div class="zmc-card p-0 shadow-sm border-0 mt-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0">
        <i class="ri-shield-check-line me-2" style="color:var(--zmc-success)"></i> Recently Confirmed Payments
      </h6>
      <a href="{{ route('staff.accounts.apps.paid') }}" class="small text-decoration-none">View All Paid <i class="ri-arrow-right-s-line"></i></a>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        <thead>
          <tr>
            <th class="ps-3" style="width: 50px;">#</th>
            <th>Reference</th>
            <th>Applicant</th>
            <th>Receipt #</th>
            <th>Date &amp; Time Confirmed</th>
            <th>Receipted By</th>
            <th class="text-end pe-3">Action</th>
          </tr>
        </thead>
        <tbody>
        @forelse($recentPaidApplications ?? [] as $i => $app)
          @php
            $latestPayment = $app->payments->first();
            $confirmedAt   = $latestPayment?->confirmed_at ?? $latestPayment?->created_at;
            $receiptedBy   = $latestPayment?->recorder?->name ?? '—';
          @endphp
          <tr>
            <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
            <td class="fw-bold">{{ $app->reference }}</td>
            <td>{{ $app->applicant?->name ?? '—' }}</td>
            <td><span class="badge bg-light text-dark border">{{ $app->receipt_number ?: ($latestPayment?->reference ?? '—') }}</span></td>
            <td class="small">{{ $confirmedAt?->format('d M Y H:i') ?? '—' }}</td>
            <td class="small text-muted">{{ $receiptedBy }}</td>
            <td class="text-end pe-3">
              <a href="{{ route('staff.accounts.applications.receipt', $app->id) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Download Receipt">
                <i class="ri-file-download-line me-1"></i> Receipt
              </a>
              <a href="{{ route('staff.accounts.applications.show', $app->id) }}" class="btn btn-sm btn-outline-primary ms-1" data-bs-toggle="tooltip" title="View Details">
                <i class="ri-eye-line"></i>
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">No recently paid applications.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Reports Section --}}
  <div class="row g-3 mt-4 mb-4">
    <div class="col-md-4">
      @include('staff.partials.accreditation-summary-report')
    </div>
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
  const ZMC_COMMENT_URL = @json($commentUrlTemplate);
  const ZMC_CSRF_TOKEN  = @json(csrf_token());
  const ZMC_ACCOUNTS_ONLY = @json($accountsOnlyView);

  function zmcFmt(v){
    if(v === null || v === undefined) return '—';
    const s = String(v).trim();
    return s === '' ? '—' : s;
  }

  // HTML-escape arbitrary strings before injecting into innerHTML / attributes.
  function zmcEsc(v){
    if(v === null || v === undefined) return '';
    return String(v)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#39;');
  }

  // Validate that a URL is safe to use in href (http/https or same-origin path).
  // Returns the URL string if safe, or null if not.
  function zmcSafeUrl(u){
    if(u === null || u === undefined) return null;
    const s = String(u).trim();
    if(s === '') return null;
    // Same-origin absolute path (e.g. "/storage/foo.jpg")
    if(/^\/[^/]/.test(s)) return s;
    // Absolute http(s) URL only
    if(/^https?:\/\//i.test(s)) return s;
    return null;
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


      // Accounts officers only need to see payment info + messaging. Other
      // roles (super_admin, etc.) hitting this dashboard get the full view.

      // Previous Applications Block
      const prevApps = Array.isArray(data.previous_applications) ? data.previous_applications : [];
      if (!ZMC_ACCOUNTS_ONLY && prevApps.length > 0) {
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
      if (!ZMC_ACCOUNTS_ONLY && prevPays.length > 0) {
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

      if (!ZMC_ACCOUNTS_ONLY && formCode === 'AP1') {
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
              <td>${zmcFmt(d.full_name || (d.name ? (d.name + ' ' + (d.surname||'')) : ''))}</td>
              <td>${zmcFmt(d.id_passport)}</td>
              <td>${zmcFmt(d.nationality)}</td>
              <td>${zmcFmt(d.role || d.occupation)}</td>
              <td>${zmcFmt(d.shareholding)}</td>
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
              <td>${zmcFmt(m.full_name)}</td>
              <td>${zmcFmt(m.position)}</td>
              <td>${zmcFmt(m.qualification)}</td>
              <td>${zmcFmt(m.experience)}</td>
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

      // ===== Payment Details (submission method, paynow ref, proof file) =====
      const pay = data.payment || {};
      const subs = Array.isArray(pay.submissions) ? pay.submissions : [];

      // Map both lower- and upper-case enum values to friendly labels.
      const methodLabel = (m) => {
        if (!m) return '—';
        const key = String(m).toLowerCase();
        const map = {
          paynow_reference: 'PayNow',
          paynow:           'PayNow',
          proof_upload:     'Proof Upload',
          proof:            'Proof Upload',
          waiver:           'Waiver',
        };
        return map[key] || m;
      };

      // Build a safe link button. URL is validated; label is HTML-escaped.
      const proofLink = (url, label) => {
        const safe = zmcSafeUrl(url);
        if (!safe) return `<span class="text-muted">No file</span>`;
        return `<a href="${zmcEsc(safe)}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
             <i class="ri-attachment-2 me-1"></i>${zmcEsc(label || 'Open file')}
           </a>`;
      };

      const paySummary = `
        <div class="row g-3">
          ${zmcInput('Submission method', methodLabel(pay.submission_method))}
          ${zmcInput('PayNow reference',  pay.paynow_reference)}
          ${zmcInput('Payment status',    pay.payment_status)}
          ${zmcInput('Proof status',      pay.proof_status)}
          ${zmcInput('Proof uploaded at', pay.proof_uploaded_at)}
          ${zmcInput('Receipt number',    pay.receipt_number)}
          ${pay.waiver_status ? zmcInput('Waiver status', pay.waiver_status) : ''}
        </div>
        <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
          <span class="fw-bold small text-muted">Applicant proof of payment:</span>
          ${proofLink(pay.proof_url, 'View payment proof')}
          ${pay.waiver_url ? `<span class="fw-bold small text-muted ms-3">Waiver:</span>${proofLink(pay.waiver_url, 'View waiver')}` : ''}
        </div>
      `;

      let subsRows = subs.length
        ? ''
        : `<tr><td colspan="7" class="text-muted text-center">No payment submissions on file.</td></tr>`;
      subs.forEach(s => {
        const fileBtn = s.proof_url
          ? proofLink(s.proof_url, 'Proof')
          : (s.waiver_url ? proofLink(s.waiver_url, 'Waiver') : `<span class="text-muted">—</span>`);
        const amt = (s.amount !== null && s.amount !== undefined && s.amount !== '')
          ? `${zmcEsc(zmcFmt(s.currency || 'USD'))} ${zmcEsc(zmcFmt(s.amount))}`
          : '—';
        subsRows += `
          <tr>
            <td>${zmcEsc(zmcFmt(s.stage))}</td>
            <td>${zmcEsc(methodLabel(s.method))}</td>
            <td class="text-muted">${zmcEsc(zmcFmt(s.reference))}</td>
            <td>${amt}</td>
            <td><span class="badge bg-light text-dark border">${zmcEsc(zmcFmt(s.status))}</span></td>
            <td class="small text-muted">${zmcEsc(zmcFmt(s.submitted_at))}</td>
            <td class="text-end">${fileBtn}</td>
          </tr>
        `;
      });

      html += zmcBlock(
        `<i class="fa-solid fa-money-check-dollar"></i> Payment Details`,
        paySummary + `
        <div class="table-responsive mt-3">
          <table class="table table-sm align-middle zmc-table-lite">
            <thead>
              <tr>
                <th>Stage</th><th>Method</th><th>Reference</th><th>Amount</th>
                <th>Status</th><th>Submitted</th><th class="text-end">File</th>
              </tr>
            </thead>
            <tbody>${subsRows}</tbody>
          </table>
        </div>
        `
      );

      if (!ZMC_ACCOUNTS_ONLY) {
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
      }

      // ===== Messages with applicant (Accounts ↔ applicant thread) =====
      const msgs = Array.isArray(data.payment_comments) ? data.payment_comments : [];
      const canPost = !!data.can_post_payment_message;
      const threadId = 'zmcMsgThread_' + appId;
      const formId   = 'zmcMsgForm_' + appId;
      const inputId  = 'zmcMsgInput_' + appId;
      const errId    = 'zmcMsgErr_'   + appId;

      const renderMsg = (m) => {
        const fromApplicant = (m.user_role === 'applicant' || m.visibility === 'applicant_to_staff');
        const align = fromApplicant ? 'start' : 'end';
        const bg    = fromApplicant ? '#f1f5f9' : '#dbeafe';
        return `
          <div class="d-flex justify-content-${align} mb-2">
            <div style="max-width: 78%; background:${bg}; border-radius:10px; padding:8px 12px;">
              <div class="small fw-bold text-muted mb-1">
                ${zmcEsc(zmcFmt(m.user_name))}
                <span class="text-muted fw-normal">· ${zmcEsc(zmcFmt(m.created_human || m.created_at))}</span>
              </div>
              <div style="white-space: pre-wrap; word-break: break-word;">${zmcEsc(zmcFmt(m.body))}</div>
            </div>
          </div>
        `;
      };

      const threadHtml = msgs.length
        ? msgs.map(renderMsg).join('')
        : `<div class="text-muted text-center small py-3">No messages yet.</div>`;

      const formHtml = canPost
        ? `
          <form id="${formId}" class="mt-3 zmc-msg-form" data-app-id="${appId}">
            <div class="mb-2">
              <textarea id="${inputId}" class="form-control" rows="3"
                placeholder="Write a message to the applicant about the payment or proof…"
                required maxlength="5000"></textarea>
            </div>
            <div id="${errId}" class="text-danger small mb-2 d-none"></div>
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-paper-plane me-1"></i> Send to applicant
              </button>
            </div>
          </form>
        `
        : `<div class="alert alert-info small mb-0 mt-3">You don't have permission to message the applicant on this application.</div>`;

      html += zmcBlock(
        `<i class="fa-regular fa-comments"></i> Messages with applicant`,
        `
          <div id="${threadId}" class="zmc-msg-thread"
               style="max-height: 320px; overflow-y: auto; padding: 4px 2px; border:1px solid #e2e8f0; border-radius:8px; background:#fff;">
            ${threadHtml}
          </div>
          ${formHtml}
        `
      );

      if (area) {
        area.innerHTML = html;
        area.classList.remove('d-none');

        // Wire up the messaging form (if present).
        const form = document.getElementById(formId);
        if (form) {
          form.addEventListener('submit', async function (ev) {
            ev.preventDefault();
            const input  = document.getElementById(inputId);
            const errEl  = document.getElementById(errId);
            const thread = document.getElementById(threadId);
            const submitBtn = form.querySelector('button[type="submit"]');
            const body = (input?.value || '').trim();
            if (!body) return;
            errEl?.classList.add('d-none');
            if (submitBtn) submitBtn.disabled = true;

            try {
              const url = ZMC_COMMENT_URL.replace('__ID__', encodeURIComponent(appId));
              const fd = new FormData();
              fd.append('_token', ZMC_CSRF_TOKEN);
              fd.append('body', body);
              fd.append('comment_type', 'payment');
              fd.append('visibility', 'applicant');

              const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: fd,
              });

              if (!resp.ok) {
                const txt = await resp.text();
                throw new Error('Server error (' + resp.status + ')' + (txt ? ': ' + txt.slice(0, 200) : ''));
              }
              const json = await resp.json();
              if (!json || !json.ok || !json.comment) {
                throw new Error('Unexpected response from server.');
              }

              // Append to thread.
              if (thread) {
                if (msgs.length === 0) thread.innerHTML = '';
                thread.insertAdjacentHTML('beforeend', renderMsg(json.comment));
                thread.scrollTop = thread.scrollHeight;
                msgs.push(json.comment);
              }
              if (input) input.value = '';
            } catch (err) {
              if (errEl) {
                errEl.textContent = 'Could not send message: ' + (err.message || 'Unknown error');
                errEl.classList.remove('d-none');
              }
            } finally {
              if (submitBtn) submitBtn.disabled = false;
            }
          });
        }

        // Auto-scroll thread to the latest message on open.
        const thread = document.getElementById(threadId);
        if (thread) thread.scrollTop = thread.scrollHeight;
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
