@extends('layouts.portal')
@section('title', 'Renewals Payment Verification')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Renewals Payment Verification</h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">Verify renewal payment submissions.</div>
    </div>
    <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-refresh-line me-1"></i> Refresh</a>
  </div>

  <style>
    .rnw-kpi-label { font-size: 11px; font-weight: 700; color: #64748b; white-space: nowrap; }
    .rnw-kpi-value { font-size: 22px; font-weight: 900; margin: 0; }
    .rnw-kpi-icon  { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
  </style>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rnw-kpi-label">Pending Verification</div>
            <div class="rnw-kpi-value" style="color: #b45309;">{{ $kpis['pending'] }}</div>
          </div>
          <div class="rnw-kpi-icon text-warning"><i class="ri-time-line"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rnw-kpi-label">Verified Today</div>
            <div class="rnw-kpi-value text-success">{{ $kpis['verified_today'] }}</div>
          </div>
          <div class="rnw-kpi-icon text-success"><i class="ri-check-double-line"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rnw-kpi-label">PayNow</div>
            <div class="rnw-kpi-value text-primary">{{ $kpis['paynow'] }}</div>
          </div>
          <div class="rnw-kpi-icon text-primary"><i class="ri-bank-card-line"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rnw-kpi-label">Proof Upload</div>
            <div class="rnw-kpi-value" style="color: #7c3aed;">{{ $kpis['proof'] }}</div>
          </div>
          <div class="rnw-kpi-icon" style="color:#7c3aed;"><i class="ri-image-line"></i></div>
        </div>
      </div>
    </div>
  </div>

  <div class="zmc-card mb-4">
    <form method="GET" action="{{ route('staff.accounts.renewals.queue') }}" class="d-flex flex-wrap gap-2 align-items-end">
      <div>
        <select name="payment_method" class="form-select form-select-sm" style="min-width:160px;">
          <option value="">All Payment Methods</option>
          <option value="PAYNOW" {{ request('payment_method') === 'PAYNOW' ? 'selected' : '' }}>PayNow</option>
          <option value="PROOF_UPLOAD" {{ request('payment_method') === 'PROOF_UPLOAD' ? 'selected' : '' }}>Proof Upload</option>
        </select>
      </div>
      <div>
        <select name="renewal_type" class="form-select form-select-sm" style="min-width:160px;">
          <option value="">All Types</option>
          <option value="accreditation" {{ request('renewal_type') === 'accreditation' ? 'selected' : '' }}>Accreditation</option>
          <option value="registration" {{ request('renewal_type') === 'registration' ? 'selected' : '' }}>Registration</option>
          <option value="permission" {{ request('renewal_type') === 'permission' ? 'selected' : '' }}>Permission</option>
        </select>
      </div>
      <button type="submit" class="btn btn-sm px-4" style="background:var(--zmc-primary);color:var(--zmc-accent);font-weight:700;">
        <i class="ri-filter-3-line me-1"></i> Filter
      </button>
      @if(request()->hasAny(['payment_method','renewal_type']))
        <a href="{{ route('staff.accounts.renewals.queue') }}" class="btn btn-sm btn-outline-secondary px-3">
          <i class="ri-close-line me-1"></i> Reset
        </a>
      @endif
    </form>
  </div>

  @if($renewals->count() > 0)
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 fw-bold py-3">
      <i class="ri-file-list-3-line me-1" style="color:var(--zmc-accent)"></i> Renewal Applications
      <span class="text-muted fw-normal ms-2" style="font-size:11px;">Page {{ $renewals->currentPage() }} of {{ $renewals->lastPage() }}</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-3">Type</th>
              <th>Applicant</th>
              <th>Original Number</th>
              <th>Payment Method</th>
              <th>Submitted</th>
              <th class="text-end pe-3">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($renewals as $renewal)
            <tr>
              <td class="ps-3"><span class="badge bg-light text-dark border">{{ ucfirst($renewal->renewal_type) }}</span></td>
              <td>
                <div class="fw-bold" style="font-size:12px;">{{ $renewal->applicant->name ?? '—' }}</div>
                <div class="text-muted" style="font-size:10px;">{{ $renewal->applicant->email ?? '' }}</div>
              </td>
              <td>{{ $renewal->original_number ?? '—' }}</td>
              <td>
                @if($renewal->payment_method === 'PAYNOW')
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">PayNow</span>
                @else
                  <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $renewal->getPaymentMethodLabel() }}</span>
                @endif
              </td>
              <td>{{ $renewal->payment_submitted_at ? $renewal->payment_submitted_at->format('d M Y H:i') : '—' }}</td>
              <td class="text-end pe-3">
                <a href="{{ route('staff.accounts.renewals.show', $renewal) }}" class="btn btn-sm btn-outline-dark px-3">
                  <i class="ri-eye-line me-1"></i> Review
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="mt-3">{{ $renewals->links() }}</div>
  @else
  <div class="zmc-card text-center py-4">
    <i class="ri-check-line" style="font-size:32px; color:var(--zmc-accent)"></i>
    <div class="fw-bold mt-2">No renewals pending verification</div>
    <div class="text-muted small">All renewal payment submissions have been processed.</div>
  </div>
  @endif
</div>
@endsection
