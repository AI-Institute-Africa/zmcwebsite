@extends('layouts.portal')
@section('title', 'Payment Reconciliation')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Payment Reconciliation</h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        Match submitted payments against application records.
      </div>
    </div>
    <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-refresh-line me-1"></i> Refresh</a>
  </div>

  <style>
    .recon-kpi-label { font-size: 11px; font-weight: 700; color: #64748b; white-space: nowrap; }
    .recon-kpi-value { font-size: 22px; font-weight: 900; margin: 0; }
    .recon-kpi-icon  { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
  </style>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="recon-kpi-label">Matched payments</div>
            <div class="recon-kpi-value text-success">{{ $matched }}</div>
          </div>
          <div class="recon-kpi-icon text-success"><i class="ri-check-double-line"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="recon-kpi-label">Unmatched / missing ref</div>
            <div class="recon-kpi-value text-warning">{{ $unmatched }}</div>
          </div>
          <div class="recon-kpi-icon text-warning"><i class="ri-error-warning-line"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="recon-kpi-label">Unreconciled Records</div>
            <div class="recon-kpi-value text-danger">{{ $unreconciledCount }}</div>
          </div>
          <div class="recon-kpi-icon text-danger"><i class="ri-history-line"></i></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="recon-kpi-label">Accounts queue</div>
            <div class="recon-kpi-value text-primary">{{ $pending }}</div>
          </div>
          <div class="recon-kpi-icon text-primary"><i class="ri-folders-line"></i></div>
        </div>
      </div>
    </div>
  </div>

  @if($unreconciledPayments->count())
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 fw-bold py-3 d-flex justify-content-between align-items-center">
      <div>
        <i class="ri-history-line me-1 text-danger"></i> Unreconciled Payment Records
      </div>
      <button type="button" class="btn btn-sm btn-primary" onclick="submitReconciliationForm()">
        <i class="ri-check-line me-1"></i> Mark Selected as Reconciled
      </button>
    </div>
    <div class="card-body p-0">
      <form id="reconciliationForm" action="{{ route('staff.accounts.reconciliation.mark') }}" method="POST">
        @csrf
        <div class="table-responsive">
          <table class="table table-hover align-middle small mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-3" style="width: 40px;">
                  <input type="checkbox" class="form-check-input" id="selectAllPayments">
                </th>
                <th>Reference</th>
                <th>Receipt #</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Recorded By</th>
                <th class="text-end pe-3">Date</th>
              </tr>
            </thead>
            <tbody>
              @foreach($unreconciledPayments as $pay)
              <tr>
                <td class="ps-3">
                  <input type="checkbox" name="payment_ids[]" value="{{ $pay->id }}" class="form-check-input payment-checkbox">
                </td>
                <td class="fw-bold">{{ $pay->reference }}</td>
                <td><span class="badge bg-light text-dark border">{{ $pay->receipt_number ?? '—' }}</span></td>
                <td>{{ $pay->currency }} {{ number_format($pay->amount, 2) }}</td>
                <td>{{ ucfirst($pay->method) }}</td>
                <td>User ID: {{ $pay->recorded_by }}</td>
                <td class="text-end pe-3">{{ \Carbon\Carbon::parse($pay->created_at)->format('d M Y H:i') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </form>
    </div>
  </div>
  <div class="mb-4">{{ $unreconciledPayments->links() }}</div>
  
  <script>
    document.getElementById('selectAllPayments').addEventListener('change', function() {
      const isChecked = this.checked;
      document.querySelectorAll('.payment-checkbox').forEach(cb => {
        cb.checked = isChecked;
      });
    });

    function submitReconciliationForm() {
      const checked = document.querySelectorAll('.payment-checkbox:checked');
      if (checked.length === 0) {
        alert('Please select at least one payment record to reconcile.');
        return;
      }
      if (confirm('Mark ' + checked.length + ' payment(s) as reconciled?')) {
        document.getElementById('reconciliationForm').submit();
      }
    }
  </script>
  @endif

  @if($matchedApplications->count())
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 fw-bold py-3">
      <i class="ri-list-check-2 me-1" style="color:var(--zmc-accent)"></i> Matched Payment Records
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-3">Reference</th>
              <th>Applicant</th>
              <th>Type</th>
              <th>PayNow Ref</th>
              <th>Method</th>
              <th>Status</th>
              <th class="text-end pe-3">Confirmed</th>
            </tr>
          </thead>
          <tbody>
            @foreach($matchedApplications as $app)
            <tr>
              <td class="ps-3 fw-bold">{{ $app->reference }}</td>
              <td>{{ $app->applicant->name ?? '—' }}</td>
              <td><span class="badge bg-light text-dark border">{{ ucfirst($app->application_type) }}</span></td>
              <td><code>{{ $app->paynow_reference ?? '—' }}</code></td>
              <td>
                @if($app->paynow_reference)
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">PayNow</span>
                @elseif($app->proof_status === 'approved')
                  <span class="badge bg-success-subtle text-success border border-success-subtle">Proof</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary border">Other</span>
                @endif
              </td>
              <td>@include('staff.partials.status-chip', ['status' => $app->status])</td>
              <td class="text-end pe-3">{{ $app->paynow_confirmed_at ? \Carbon\Carbon::parse($app->paynow_confirmed_at)->format('d M Y H:i') : ($app->updated_at?->format('d M Y H:i') ?? '—') }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="mb-4">{{ $matchedApplications->links() }}</div>
  @endif

  @if($unmatchedApplications->count())
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 fw-bold py-3">
      <i class="ri-error-warning-line me-1 text-warning"></i> Unmatched Applications (missing payment ref)
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-3">Reference</th>
              <th>Applicant</th>
              <th>Type</th>
              <th>Payment Status</th>
              <th>Submission Method</th>
              <th class="text-end pe-3">Date</th>
            </tr>
          </thead>
          <tbody>
            @foreach($unmatchedApplications as $app)
            <tr>
              <td class="ps-3 fw-bold">{{ $app->reference }}</td>
              <td>{{ $app->applicant->name ?? '—' }}</td>
              <td><span class="badge bg-light text-dark border">{{ ucfirst($app->application_type) }}</span></td>
              <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ ucfirst($app->payment_status ?? 'Unpaid') }}</span></td>
              <td>{{ ucfirst(str_replace('_', ' ', $app->payment_submission_method ?? '—')) }}</td>
              <td class="text-end pe-3">{{ $app->created_at?->format('d M Y H:i') }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="mb-4">{{ $unmatchedApplications->links() }}</div>
  @endif

  @if(!$matchedApplications->count() && !$unmatchedApplications->count())
  <div class="zmc-card text-center py-4">
    <i class="ri-check-line" style="font-size:32px; color:var(--zmc-accent)"></i>
    <div class="fw-bold mt-2">All clear</div>
    <div class="text-muted small">No payment records to reconcile at this time.</div>
  </div>
  @endif
</div>
@endsection
