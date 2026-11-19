@extends('layouts.portal')
@section('title', 'Pending Levy Payments — Accounts')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
        <i class="ri-money-dollar-circle-line me-2"></i>Pending Levy Payments
      </h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Verify annual levy payment proofs from media houses
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('staff.accounts.levies.verified') }}" class="btn btn-success btn-sm px-3">
        <i class="ri-check-double-line me-1"></i> Verified
      </a>
      <a href="{{ route('staff.accounts.levies.rejected') }}" class="btn btn-danger btn-sm px-3">
        <i class="ri-close-circle-line me-1"></i> Rejected
      </a>
      <a href="{{ route('staff.accounts.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-arrow-left-line me-1"></i> Dashboard
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

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="zmc-card text-center p-3 h-100 border-warning">
        <div class="h3 fw-black mb-0 text-warning">{{ $stats['pending_count'] }}</div>
        <div class="small text-muted fw-bold">Pending Review</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="zmc-card text-center p-3 h-100">
        <div class="h3 fw-black mb-0" style="color:#1a1a1a;">${{ number_format($stats['pending_amount'], 2) }}</div>
        <div class="small text-muted fw-bold">Total Amount</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="zmc-card text-center p-3 h-100 border-success">
        <div class="h3 fw-black mb-0 text-success">{{ $stats['verified_today'] }}</div>
        <div class="small text-muted fw-bold">Verified Today</div>
      </div>
    </div>
  </div>

  <div class="zmc-card mb-4">
    <h6 class="fw-bold mb-3"><i class="ri-filter-3-line me-2"></i> Filters</h6>
    <form method="GET" action="{{ route('staff.accounts.levies.pending') }}" id="filterForm">
      <div class="row g-2">
        <div class="col-12 col-md-4 col-lg-3">
          <label class="form-label small fw-bold">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Entity name, Reference..." value="{{ request('search') }}">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Financial Year</label>
          <select name="financial_year" class="form-select form-select-sm">
            <option value="">All Years</option>
            @foreach($availableYears as $yr)
            <option value="{{ $yr }}" {{ request('financial_year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Currency</label>
          <select name="currency" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="USD" {{ request('currency') === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="ZWG" {{ request('currency') === 'ZWG' ? 'selected' : '' }}>ZWG</option>
            <option value="ZWL" {{ request('currency') === 'ZWL' ? 'selected' : '' }}>ZWL</option>
            <option value="ZiG" {{ request('currency') === 'ZiG' ? 'selected' : '' }}>ZiG</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <div class="col-12 col-md-3 col-lg-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i> Filter
          </button>
        </div>
      </div>
      @if(request()->hasAny(['search','financial_year','currency','date_from','date_to']))
        <div class="mt-2">
          <a href="{{ route('staff.accounts.levies.pending') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ri-close-line me-1"></i> Clear Filters
          </a>
        </div>
      @endif
    </form>
  </div>

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0"><i class="ri-file-list-3-line me-2" style="color:#f59e0b;"></i>Levy Submissions</h6>
      <form method="POST" action="{{ route('staff.accounts.levies.bulk-download') }}" class="d-inline" id="bulkDownloadForm">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-primary" id="bulkDownloadBtn" disabled>
          <i class="ri-download-cloud-line me-1"></i> Download Selected (<span id="selectedCount">0</span>)
        </button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size:13px;">
        <thead class="bg-light">
          <tr>
            <th class="ps-3">
              <input type="checkbox" id="selectAll" class="form-check-input">
            </th>
            <th>Reference</th>
            <th>Entity Name</th>
            <th>Financial Year</th>
            <th>Turnover</th>
            <th>Levy Amount</th>
            <th>Currency</th>
            <th>Submitted</th>
            <th>Contact</th>
            <th class="text-center">Documents</th>
            <th class="pe-3 text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($levies as $levy)
            <tr>
              <td class="ps-3">
                <input type="checkbox" name="levy_ids[]" value="{{ $levy->id }}" class="form-check-input levy-checkbox" form="bulkDownloadForm">
              </td>
              <td class="fw-bold font-monospace">{{ $levy->reference }}</td>
              <td>
                <div class="fw-semibold">{{ $levy->entity_name }}</div>
                @if($levy->user)
                  <div class="small text-muted">{{ $levy->user->name }}</div>
                @endif
              </td>
              <td>
                <span class="badge bg-primary-subtle text-primary">{{ $levy->financial_year }}</span>
              </td>
              <td>{{ $levy->currency }} {{ number_format($levy->gross_annual_turnover, 2) }}</td>
              <td class="fw-bold text-success">{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</td>
              <td>
                <span class="badge bg-secondary-subtle text-secondary">{{ $levy->currency }}</span>
              </td>
              <td class="small text-muted">{{ $levy->created_at->format('d M Y H:i') }}</td>
              <td class="small">
                @if($levy->user)
                  <div>{{ $levy->user->email }}</div>
                  @if($levy->telephone)
                    <div class="text-muted">{{ $levy->telephone }}</div>
                  @endif
                @endif
              </td>
              <td class="text-center">
                @php
                  $proofDoc = $levy->documents->firstWhere('document_type', 'proof_of_payment');
                  $financialDoc = $levy->documents->firstWhere('document_type', 'audited_financials');
                @endphp
                <div class="d-flex gap-1 justify-content-center">
                  @if($proofDoc)
                    <a href="{{ route('staff.accounts.levies.documents.download', $proofDoc->id) }}" 
                       class="btn btn-sm btn-success" title="Download Payment Proof">
                      <i class="ri-file-check-line"></i>
                    </a>
                  @endif
                  @if($financialDoc)
                    <a href="{{ route('staff.accounts.levies.documents.download', $financialDoc->id) }}" 
                       class="btn btn-sm btn-info" title="Download Financial Statements">
                      <i class="ri-file-chart-line"></i>
                    </a>
                  @endif
                </div>
              </td>
              <td class="pe-3 text-end">
                <button type="button" class="btn btn-sm btn-success" 
                        data-bs-toggle="modal" 
                        data-bs-target="#verifyModal{{ $levy->id }}">
                  <i class="ri-check-line me-1"></i> Verify
                </button>
                <button type="button" class="btn btn-sm btn-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#rejectModal{{ $levy->id }}">
                  <i class="ri-close-line me-1"></i> Reject
                </button>
              </td>
            </tr>

            {{-- Verify Modal --}}
            <div class="modal fade" id="verifyModal{{ $levy->id }}" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="ri-check-double-line me-2"></i>Verify Levy Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST" action="{{ route('staff.accounts.levies.verify', $levy->id) }}">
                    @csrf
                    <div class="modal-body">
                      <div class="alert alert-info">
                        <strong>{{ $levy->entity_name }}</strong><br>
                        <small>{{ $levy->reference }} | {{ $levy->financial_year }}</small><br>
                        <strong class="text-success">Levy Amount: {{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</strong>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                          <option value="">Select method...</option>
                          <option value="bank_transfer">Bank Transfer</option>
                          <option value="cash">Cash</option>
                          <option value="pos">POS/Card</option>
                          <option value="mobile_money">Mobile Money</option>
                          <option value="cheque">Cheque</option>
                          <option value="paynow">PayNow</option>
                          <option value="other">Other</option>
                        </select>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Payment Reference</label>
                        <input type="text" name="payment_reference" class="form-control" 
                               placeholder="Transaction ID, Bank Reference..." 
                               value="{{ $levy->reference }}">
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Receipt Number</label>
                        <input type="text" name="receipt_number" class="form-control" 
                               placeholder="Leave blank to auto-generate">
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Verification Notes</label>
                        <textarea name="verification_notes" class="form-control" rows="3" 
                                  placeholder="Optional notes about this verification..."></textarea>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-success">
                        <i class="ri-check-double-line me-1"></i> Confirm Verification
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            {{-- Reject Modal --}}
            <div class="modal fade" id="rejectModal{{ $levy->id }}" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="ri-close-circle-line me-2"></i>Reject Levy Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="POST" action="{{ route('staff.accounts.levies.reject', $levy->id) }}">
                    @csrf
                    <div class="modal-body">
                      <div class="alert alert-warning">
                        <strong>{{ $levy->entity_name }}</strong><br>
                        <small>{{ $levy->reference }} | {{ $levy->financial_year }}</small>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required
                                  placeholder="Explain why this payment proof is being rejected..."></textarea>
                        <small class="text-muted">Be specific so the media house can resubmit correctly.</small>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-danger">
                        <i class="ri-close-circle-line me-1"></i> Confirm Rejection
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          @empty
            <tr>
              <td colspan="11" class="text-center py-5 text-muted">
                <i class="ri-inbox-line d-block mb-2" style="font-size:48px; opacity:0.3;"></i>
                <div>No pending levy payments at this time</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($levies->hasPages())
      <div class="p-3 border-top">
        {{ $levies->links() }}
      </div>
    @endif
  </div>

</div>

@push('scripts')
<script>
  // Bulk selection
  document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.levy-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkDownloadButton();
  });

  document.querySelectorAll('.levy-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkDownloadButton);
  });

  function updateBulkDownloadButton() {
    const checked = document.querySelectorAll('.levy-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked;
    document.getElementById('bulkDownloadBtn').disabled = checked === 0;
  }
</script>
@endpush
@endsection
