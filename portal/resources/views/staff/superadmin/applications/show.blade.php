@extends('layouts.portal')
@section('title', 'Superadmin Review - ' . $application->reference)

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold m-0">{{ $application->reference }}</h1>
            <div class="text-muted small">
                {{ strtoupper($application->application_type) }} •
                <span class="badge bg-primary-subtle text-primary border-primary">
                    {{ strtoupper(str_replace('_',' ', $application->status)) }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.superadmin.applications.index') }}" class="btn btn-light border btn-sm">
                <i class="ri-arrow-left-line me-1"></i> Back to Dashboard
            </a>
            <a href="{{ route('staff.superadmin.applications.index') }}" class="btn btn-light border btn-sm">Dashboard</a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        {{-- Left Column: Applicant Details & Workspace --}}
        <div class="col-lg-8">
            {{-- A) Applicant Details Panel --}}
            @include('staff.partials.application_details_card', ['application' => $application])
            @include('staff.partials.custom_fields_display', ['application' => $application])

            @include('staff.partials.documents_attachments_card', ['application' => $application])

            @include('staff.partials.application_comments', ['application' => $application])

            {{-- B) Category Validation Panel --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">
                    <i class="ri-award-line me-1"></i> Category Validation
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label class="small text-muted d-block">Currently Assigned Category</label>
                            <span class="h5 fw-bold text-primary">
                                {{ $application->accreditation_category_code ?? $application->media_house_category_code ?? 'NOT ASSIGNED' }}
                            </span>
                        </div>
                        @if(in_array($application->status, ['paid_confirmed', 'registrar_review']))
                        <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reassignModal">
                            <i class="ri-edit-line"></i> Reassign Category
                        </button>
                        @endif
                    </div>

                    <div class="p-3 bg-light rounded">
                        <h2 class="small fw-bold mb-2">Supporting Evidence Summary</h2>
                        <ul class="small mb-0">
                            <li>Designation: {{ $application->form_data['designation'] ?? '—' }}</li>
                            <li>Medium Type: {{ $application->form_data['medium_type'] ?? '—' }}</li>
                            <li>Scope: {{ $application->journalist_scope ?? '—' }}</li>
                        </ul>
                    </div>
                </div>
            </div>



            {{-- 4) Full Application Audit Trail --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">
                    <i class="ri-history-line me-1"></i> Full Audit Trail (Timeline)
                </div>
                <div class="card-body">
                    <div class="timeline-v2">
                        @foreach($auditTrail as $log)
                            <div class="timeline-item pb-3 mb-3 border-bottom border-light">
                                <div class="d-flex justify-content-between">
                                    <span class="small fw-bold text-dark">{{ strtoupper(str_replace('_',' ', $log->action)) }}</span>
                                    <span class="small text-muted">{{ $log->created_at->format('d M Y H:i') }}</span>
                                </div>
                                <div class="small text-muted">
                                    By: {{ $log->user?->name ?? 'System' }} ({{ $log->user_role ?? '—' }})
                                </div>
                                @if($log->meta)
                                    <div class="mt-1 p-2 bg-light rounded small text-dark">
                                        @foreach($log->meta as $mk => $mv)
                                            @if(is_string($mv))
                                                <div><strong>{{ $mk }}:</strong> {{ $mv }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        {{ $auditTrail->links() }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Oversight & Actions --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold text-success">
                    <i class="ri-bank-card-line me-1"></i> Payment Validation
                </div>
                <div class="card-body">
                    @php
                        $lastPayment = $application->payments->last();
                        $lastSub = $application->paymentSubmissions->last();
                    @endphp
                    @if($lastPayment)
                        <div class="mb-3">
                            <label class="small text-muted d-block">Status</label>
                            <span class="badge bg-success-subtle text-success border-success px-3">
                                {{ strtoupper($lastPayment->status) }}
                            </span>
                        </div>
                        <div class="mb-2 small">
                            <strong>Method:</strong> {{ strtoupper($lastPayment->method) }}<br>
                            <strong>Amount:</strong> {{ $lastPayment->amount }} {{ $lastPayment->currency }}<br>
                            <strong>Reference:</strong> {{ $lastPayment->reference }}<br>
                            <strong>Date:</strong> {{ $lastPayment->confirmed_at?->format('d M Y H:i') ?? '—' }}
                        </div>
                        <hr>
                        <div class="small text-muted">
                            <i class="ri-checkbox-circle-line me-1"></i> Verified by Accounts
                        </div>
                    @elseif($lastSub)
                        <div class="mb-3">
                            <label class="small text-muted d-block">Status</label>
                            @php $subColor = match($lastSub->status) { 'verified' => 'success', 'rejected' => 'danger', 'submitted' => 'warning', default => 'secondary' }; @endphp
                            <span class="badge bg-{{ $subColor }}-subtle text-{{ $subColor }} border-{{ $subColor }} px-3">
                                {{ strtoupper($lastSub->status) }}
                            </span>
                        </div>
                        <div class="mb-2 small">
                            <strong>Stage:</strong> {{ $lastSub->getStageLabel() }}<br>
                            <strong>Method:</strong> {{ $lastSub->getMethodLabel() }}<br>
                            <strong>Amount:</strong> {{ $lastSub->amount ? number_format($lastSub->amount, 2) : '—' }} {{ $lastSub->currency ?? 'USD' }}<br>
                            <strong>Reference:</strong> {{ $lastSub->reference ?? '—' }}<br>
                            <strong>Submitted:</strong> {{ $lastSub->submitted_at?->format('d M Y H:i') ?? '—' }}
                        </div>
                        @if($lastSub->proof_path)
                          <div class="mt-2 p-2 bg-light rounded">
                            <a href="{{ asset('storage/' . $lastSub->proof_path) }}" target="_blank" class="btn btn-sm btn-outline-success w-100">
                              <i class="ri-eye-line me-1"></i> View Payment Proof
                            </a>
                          </div>
                        @endif
                        @if($lastSub->waiver_path)
                          <div class="mt-2 p-2 bg-light rounded">
                            <a href="{{ asset('storage/' . $lastSub->waiver_path) }}" target="_blank" class="btn btn-sm btn-outline-warning w-100">
                              <i class="ri-eye-line me-1"></i> View Waiver Document
                            </a>
                          </div>
                        @endif
                    @else
                        @php
                            $docProof = $application->documents->whereIn('doc_type', ['proof_of_payment', 'payment_proof'])->last();
                            $docWaiver = $application->documents->whereIn('payment_waiver', ['payment_waiver', 'waiver'])->last();
                        @endphp
                        {{-- Check for legacy/direct proof on application model or documents --}}
                        @if($application->payment_proof_path || $application->proof_original_name || $docProof)
                            <div class="mb-3">
                                <label class="small text-muted d-block">Status (Payment Proof Detected)</label>
                                @php 
                                    $pStatus = $application->proof_status ?? 'submitted';
                                    $pColor = match($pStatus) { 'approved' => 'success', 'rejected' => 'danger', 'submitted' => 'warning', default => 'secondary' }; 
                                @endphp
                                <span class="badge bg-{{ $pColor }}-subtle text-{{ $pColor }} border-{{ $pColor }} px-3">
                                    {{ strtoupper($pStatus) }}
                                </span>
                            </div>
                            <div class="mb-2 small">
                                <strong>Amount:</strong> {{ $application->proof_amount_paid ? number_format($application->proof_amount_paid, 2) : '—' }} USD<br>
                                <strong>Bank:</strong> {{ $application->proof_bank_name ?? '—' }}<br>
                                <strong>Date:</strong> {{ $application->proof_payment_date ? $application->proof_payment_date->format('d M Y') : '—' }}
                            </div>
                            @php 
                                $pPath = $application->payment_proof_path ?: ($docProof ? $docProof->file_path : null);
                            @endphp
                            @if($pPath)
                                <div class="mt-2 p-2 bg-light rounded">
                                    <a href="{{ asset('storage/' . $pPath) }}" target="_blank" class="btn btn-sm btn-outline-success w-100">
                                        <i class="ri-eye-line me-1"></i> View Payment Proof
                                    </a>
                                </div>
                            @endif
                        @elseif($application->waiver_path || $docWaiver)
                            <div class="mb-3">
                                <label class="small text-muted d-block">Status (Waiver Detected)</label>
                                <span class="badge bg-warning-subtle text-warning border-warning px-3">WAIVER UPLOADED</span>
                            </div>
                            @php 
                                $wPath = $application->waiver_path ?: ($docWaiver ? $docWaiver->file_path : null);
                            @endphp
                            @if($wPath)
                                <div class="mt-2 p-2 bg-light rounded">
                                    <a href="{{ asset('storage/' . $wPath) }}" target="_blank" class="btn btn-sm btn-outline-warning w-100">
                                        <i class="ri-eye-line me-1"></i> View Waiver Document
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="alert alert-warning small mb-0">No payment records found.</div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- 5) Issuance & Print Tracking --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">
                    <i class="ri-printer-line me-1"></i> Issuance & Printing
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-muted d-block">Current Status</label>
                        <span class="fw-bold">{{ strtoupper(str_replace('_',' ', $application->status)) }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block">Print Count</label>
                        <span class="h4 fw-bold {{ $application->print_count > 1 ? 'text-danger' : '' }}">
                            {{ $application->print_count }}
                        </span>
                    </div>
                    @if($application->printLogs->count() > 0)
                        <div class="list-group list-group-flush border-top mt-2">
                            @foreach($application->printLogs as $pl)
                                <div class="list-group-item px-0 py-2 small">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ strtoupper($pl->document_type) }}</strong>
                                        <span>{{ $pl->printed_at->format('d M H:i') }}</span>
                                    </div>
                                    <div class="text-muted">By: {{ $pl->printedBy?->name }}</div>
                                    <div class="text-muted">Reason: {{ $pl->reason }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- E) Approval Controls --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">
                    <i class="ri-settings-3-line me-1"></i> Superadmin Actions
                </div>
                <div class="card-body">
                    @if($application->status === \App\Models\Application::SUPERADMIN_REVIEW)
                        <form method="POST" action="{{ route('staff.superadmin.applications.approve', $application) }}" class="mb-3">
                            @csrf
                            <label class="form-label small fw-bold">Internal Notes (Optional)</label>
                            <textarea class="form-control mb-2" name="decision_notes" rows="3" placeholder="Add any notes..."></textarea>

                            <button class="btn btn-success w-100 shadow-sm">
                                <i class="ri-check-line me-1"></i> Approve & Request Registration Fee
                            </button>
                        </form>

                        <div class="row g-2">
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="ri-close-line"></i> Reject & Return to Officer
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info small mb-0 text-center">
                            <i class="ri-information-line me-1"></i> No pending actions for Superadmin.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('staff.superadmin.applications.reject', $application) }}">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Reject Application</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Rejection Reason</label>
                    <textarea name="decision_notes" class="form-control" rows="4" required placeholder="Provide a clear reason for the applicant..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Reject</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>
    .timeline-v2 { position: relative; }
    .timeline-item { position: relative; padding-left: 20px; }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #2563eb;
    }
    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 15px;
        bottom: 0;
        width: 2px;
        background: #e5e7eb;
    }
</style>
@endpush
