@extends('layouts.portal')
@section('title', 'Waiver Requests')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Waiver Requests</h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">Review waiver requests and supporting documents, then approve or reject with notes.</div>
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
    <div class="p-3 border-bottom fw-bold"><i class="ri-inbox-line me-2" style="color:var(--zmc-accent)"></i> Pending waivers</div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
          @forelse($applications as $i => $app)
            <tr>
              @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
              <td class="text-end">
                <a class="btn btn-sm zmc-icon-btn btn-outline-primary" href="{{ route('staff.accounts.applications.show', $app->id) }}" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveWaiver{{ $app->id }}" data-bs-toggle="tooltip" title="Approve"><i class="ri-check-line"></i></button>
                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectWaiver{{ $app->id }}" data-bs-toggle="tooltip" title="Reject"><i class="ri-close-line"></i></button>
              </td>
            </tr>

            @push('zmc_modals')
              <div class="modal fade" id="approveWaiver{{ $app->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <form class="modal-content" method="POST" action="{{ route('staff.accounts.waivers.approve', $app->id) }}">
                    @csrf
                    <div class="modal-header zmc-modal-header">
                      <div>
                        <div class="zmc-modal-title"><i class="ri-check-line me-2" style="color:var(--zmc-accent)"></i> Approve waiver <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span></div>
                        <div class="zmc-modal-sub">Approving a waiver should be backed by policy/authority. Notes are optional but recommended.</div>
                      </div>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <label class="form-label">Notes (optional)</label>
                      <textarea class="form-control" name="waiver_review_notes" rows="4" maxlength="5000" placeholder="e.g., approved under policy section X, authorised by ..."></textarea>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-success"><i class="ri-check-line me-1"></i> Approve</button>
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>

              <div class="modal fade" id="rejectWaiver{{ $app->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <form class="modal-content" method="POST" action="{{ route('staff.accounts.waivers.reject', $app->id) }}">
                    @csrf
                    <div class="modal-header zmc-modal-header">
                      <div>
                        <div class="zmc-modal-title"><i class="ri-close-line me-2" style="color:#dc2626"></i> Reject waiver <span class="ms-2 text-muted" style="font-weight:800;font-size: var(--font-size-sm);">{{ $ref }}</span></div>
                        <div class="zmc-modal-sub">Rejection reason is mandatory.</div>
                      </div>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <label class="form-label">Reason</label>
                      <textarea class="form-control" name="waiver_review_notes" rows="4" maxlength="5000" required placeholder="e.g., not eligible, missing authority letter, insufficient documentation"></textarea>
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
            <tr><td colspan="8" class="text-center text-muted p-4">No waiver requests.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $applications->links() }}</div>
  </div>
</div>
@stack('zmc_modals')
@endsection
