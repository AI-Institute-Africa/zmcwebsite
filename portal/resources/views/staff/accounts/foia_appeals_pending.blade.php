@extends('layouts.portal')
@section('title','FOIA Appeals - Payment Verification')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-checkbox-circle-fill fs-5"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-error-warning-fill fs-5"></i> {{ session('error') }}
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border border-slate-100">
        <div>
            <h1 class="fw-bold m-0" style="letter-spacing:-0.02em; color:#0f172a;">
                <i class="ri-money-dollar-circle-line me-2" style="color:#ea580c;"></i>FOIA Appeals Payment Verification
            </h1>
            <p class="text-muted m-0 small mt-1">Verify payment proofs for FOIA appeals</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('staff.accounts.dashboard') }}" class="btn btn-light btn-sm rounded-pill">
                <i class="ri-arrow-left-line"></i> Back to Dashboard
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#fef3c7;">
                    <i class="ri-time-line" style="font-size:22px;color:#d97706;"></i>
                </div>
                <div>
                    <div class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Pending Verification</div>
                    <div class="fw-bold" style="font-size:22px;color:#0f172a;">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#dcfce7;">
                    <i class="ri-checkbox-circle-line" style="font-size:22px;color:#16a34a;"></i>
                </div>
                <div>
                    <div class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Approved</div>
                    <div class="fw-bold" style="font-size:22px;color:#0f172a;">{{ $stats['approved'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#fee2e2;">
                    <i class="ri-close-circle-line" style="font-size:22px;color:#dc2626;"></i>
                </div>
                <div>
                    <div class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Rejected</div>
                    <div class="fw-bold" style="font-size:22px;color:#0f172a;">{{ $stats['rejected'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Appeals Table --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white p-4 border-0">
            <h2 class="fw-bold m-0"><i class="ri-list-check me-2" style="color:#ea580c;"></i>Pending Payment Verifications</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Appeal Ref</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Appellant</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Payment Reference</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Payment Proof</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Submitted</th>
                        <th class="fw-semibold text-uppercase text-end pe-4" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appeals as $appeal)
                    <tr>
                        <td class="ps-4">
                            <code class="text-primary fw-bold" style="font-size:12px;">APPEAL-{{ str_pad($appeal->id, 6, '0', STR_PAD_LEFT) }}</code>
                        </td>
                        <td>
                            <div class="fw-semibold" style="color:#334155;">{{ $appeal->full_name }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $appeal->email }}</div>
                        </td>
                        <td>
                            <code class="text-dark" style="font-size:11px;">{{ $appeal->payment_ref ?? 'N/A' }}</code>
                        </td>
                        <td>
                            @if($appeal->payment_proof_path)
                                <a href="{{ route('staff.accounts.foia_appeals.payment_proof', $appeal) }}" class="btn btn-sm btn-outline-primary rounded-pill" target="_blank">
                                    <i class="ri-file-download-line me-1"></i>{{ Str::limit($appeal->payment_proof_original_name ?? 'Download', 20) }}
                                </a>
                            @else
                                <span class="text-muted small">No file uploaded</span>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:12px;">
                            {{ $appeal->created_at->format('d M Y') }}<br>
                            <span style="font-size:10px;">{{ $appeal->created_at->format('H:i') }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex gap-1 justify-content-end">
                                <button class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#approveModal{{ $appeal->id }}">
                                    <i class="ri-checkbox-line me-1"></i>Approve
                                </button>
                                <button class="btn btn-sm btn-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $appeal->id }}">
                                    <i class="ri-close-line me-1"></i>Reject
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Approve Modal --}}
                    <div class="modal fade" id="approveModal{{ $appeal->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('staff.accounts.foia_appeals.approve_payment', $appeal) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-success bg-opacity-10 border-0 p-4">
                                        <h3 class="modal-title fw-bold"><i class="ri-checkbox-circle-line me-2"></i>Approve Payment</h3>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p>Approve payment for <strong>APPEAL-{{ str_pad($appeal->id, 6, '0', STR_PAD_LEFT) }}</strong>?</p>
                                        <p class="small text-muted mb-3">Appellant: {{ $appeal->full_name }} ({{ $appeal->email }})</p>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Notes (Optional)</label>
                                            <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Add any notes about this payment verification..."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-0 p-4">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold">
                                            <i class="ri-checkbox-line me-1"></i>Approve Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Reject Modal --}}
                    <div class="modal fade" id="rejectModal{{ $appeal->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form action="{{ route('staff.accounts.foia_appeals.reject_payment', $appeal) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-danger bg-opacity-10 border-0 p-4">
                                        <h4 class="modal-title fw-bold"><i class="ri-close-circle-line me-2"></i>Reject Payment</h4>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <p>Reject payment for <strong>APPEAL-{{ str_pad($appeal->id, 6, '0', STR_PAD_LEFT) }}</strong>?</p>
                                        <p class="small text-muted mb-3">Appellant: {{ $appeal->full_name }} ({{ $appeal->email }})</p>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Reason for Rejection *</label>
                                            <textarea name="reason" class="form-control rounded-3" rows="3" required placeholder="Please provide a reason for rejecting this payment..."></textarea>
                                        </div>
                                        <div class="alert alert-warning small mb-0">
                                            <i class="ri-alert-line me-1"></i>The appellant will be notified of the rejection and reason.
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-0 p-4">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">
                                            <i class="ri-close-line me-1"></i>Reject Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ri-checkbox-circle-line d-block mx-auto mb-2" style="font-size:32px;color:#cbd5e1;"></i>
                            No pending payment verifications. All caught up!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appeals->hasPages())
        <div class="p-3 border-top">
            {{ $appeals->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
