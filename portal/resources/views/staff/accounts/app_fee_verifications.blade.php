@extends('layouts.portal')
@section('title', 'Application Fee Verifications')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Application Fee Verifications</h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">Review Application Fee payments and verify proof of payment.</div>
    </div>
    <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-refresh-line me-1"></i> Refresh</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <div class="zmc-card p-0">
    <div class="p-3 border-bottom fw-bold"><i class="ri-time-line me-2" style="color:var(--zmc-accent)"></i> Queue</div>
    <form id="bulkProofsForm" method="POST" action="{{ route('staff.accounts.proofs.bulk-download') }}">
      @csrf
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 zmc-mini-table">
          <thead>
            <tr>
              <th style="width: 40px;">
                <input type="checkbox" id="selectAllProofs" class="form-check-input">
              </th>
              <th style="width:50px;">#</th>
              <th>Ref</th>
              <th>Applicant</th>
              <th>Collection Region</th>
              <th>Date & Time</th>
              <th>New, Renewal or Replacement</th>
              <th>Foreign or Local</th>
              <th>Status</th>
              <th>Payment</th>
              <th>Category</th>
              <th>Officer</th>
              <th class="text-end" style="min-width:180px;">Action</th>
            </tr>
          </thead>
        <tbody>
          @forelse($applications as $i => $app)
            <tr>
              <td><input type="checkbox" name="application_ids[]" value="{{ $app->id }}" class="form-check-input proof-checkbox"></td>
              @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
              <td class="text-end">
                <a class="btn btn-sm zmc-icon-btn btn-outline-primary" href="{{ route('staff.accounts.applications.show', $app->id) }}" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>

                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveProof{{ $app->id }}" data-bs-toggle="tooltip" title="Approve">
                  <i class="ri-check-line"></i>
                </button>
                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectProof{{ $app->id }}" data-bs-toggle="tooltip" title="Reject">
                  <i class="ri-close-line"></i>
                </button>
              </td>
            </tr>

            @push('zmc_modals')
              <div class="modal fade" id="approveProof{{ $app->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <form class="modal-content" method="POST" action="{{ route('staff.accounts.proofs.approve', $app->id) }}">
                    @csrf
                    <div class="modal-header zmc-modal-header">
                      <div>
                        <div class="zmc-modal-title"><i class="ri-check-line me-2" style="color:var(--zmc-accent)"></i> Approve proof <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span></div>
                        <div class="zmc-modal-sub">Confirm this proof is valid. Optional: capture PayNow reference/status.</div>
                      </div>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="row g-2">
                        <div class="col-12 col-md-6">
                          <label class="form-label">PayNow reference (optional)</label>
                          <input class="form-control" name="paynow_reference" value="{{ $app->paynow_reference }}" maxlength="200">
                        </div>
                        <div class="col-12 col-md-6">
                          <label class="form-label">Payment status (optional)</label>
                          <select class="form-select" name="payment_status">
                            <option value="">Leave unchanged</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="failed">Failed</option>
                            <option value="reversed">Reversed</option>
                          </select>
                        </div>
                        <div class="col-12">
                          <label class="form-label">Notes (optional)</label>
                          <textarea class="form-control" name="proof_review_notes" rows="4" maxlength="5000" placeholder="e.g., proof matches PayNow, amount correct, reference OK"></textarea>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-success"><i class="ri-check-line me-1"></i> Approve</button>
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>

              <div class="modal fade" id="rejectProof{{ $app->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <form class="modal-content" method="POST" action="{{ route('staff.accounts.proofs.reject', $app->id) }}">
                    @csrf
                    <div class="modal-header zmc-modal-header">
                      <div>
                        <div class="zmc-modal-title"><i class="ri-close-line me-2" style="color:#dc2626"></i> Reject proof <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span></div>
                        <div class="zmc-modal-sub">Rejection reason is mandatory.</div>
                      </div>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <label class="form-label">Reason</label>
                      <textarea class="form-control" name="proof_review_notes" rows="4" maxlength="5000" required placeholder="e.g., wrong amount, missing reference, duplicate proof, unclear image"></textarea>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-danger"><i class="ri-close-line me-1"></i> Reject</button>
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>
            @endpush
          @empty
            <tr><td colspan="9" class="text-center text-muted p-4">No pending proofs.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    </form>
    <div class="p-3 d-flex justify-content-between align-items-center">
      <div>{{ $applications->links() }}</div>
      <button type="submit" form="bulkProofsForm" class="btn btn-primary" id="downloadSelectedBtn" disabled>
        <i class="ri-download-2-line me-1"></i> Bulk Download Selection
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectAll = document.getElementById('selectAllProofs');
  const checkboxes = document.querySelectorAll('.proof-checkbox');
  const downloadBtn = document.getElementById('downloadSelectedBtn');

  function updateDownloadBtn() {
    const checkedCount = document.querySelectorAll('.proof-checkbox:checked').length;
    downloadBtn.disabled = checkedCount === 0;
    downloadBtn.innerHTML = `<i class="ri-download-2-line me-1"></i> Bulk Download (${checkedCount})`;
  }

  if (selectAll) {
    selectAll.addEventListener('change', function() {
      checkboxes.forEach(cb => cb.checked = selectAll.checked);
      updateDownloadBtn();
    });
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', updateDownloadBtn);
  });
});
</script>

@stack('zmc_modals')
@endsection
