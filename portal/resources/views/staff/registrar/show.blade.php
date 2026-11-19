@extends('layouts.portal')
@section('title', 'Registrar Review - ' . $application->reference)

@section('content')
@include('staff.partials.dashboard_theme')
@include('staff.partials.dashboard_theme')
<div class="registrar-theme">
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0">{{ $application->reference }}</h4>
            <div class="text-muted small">
                {{ strtoupper($application->application_type) }} •
                <span class="badge bg-primary-subtle text-primary border-primary">
                    {{ strtoupper(str_replace('_',' ', $application->status)) }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-light border btn-sm">
                <i class="ri-arrow-left-line me-1"></i> Back to Dashboard
            </a>
            <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-light border btn-sm">Dashboard</a>
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

            <div class="mb-4">
                <a href="{{ route('staff.registrar.applications.download-all-documents', $application) }}" class="btn btn-outline-primary shadow-sm w-100">
                    <i class="ri-download-2-line me-1"></i> Download All Documents (ZIP)
                </a>
            </div>

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
                        <h6 class="small fw-bold mb-2">Supporting Evidence Summary</h6>
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
                    <i class="ri-settings-3-line me-1"></i> Registrar Actions
                </div>
                <div class="card-body">
                    {{-- Special Case: Forwarded Without Approval --}}
                    @if($application->status === 'forwarded_to_registrar_no_approval')
                        <div class="alert" style="background: rgba(250, 204, 21, 0.1); border: 2px solid #facc15; color: #000;">
                            <div class="d-flex align-items-start">
                                <i class="ri-alert-line me-2" style="font-size: 1.5rem; color: #facc15;"></i>
                                <div>
                                    <h6 class="fw-bold mb-2" style="color: #000;">Awaiting Registrar Decision</h6>
                                    <p class="mb-2 small">This application has been forwarded to the Registrar for a final decision on whether to proceed to payment.</p>
                                    @if($application->forward_no_approval_reason)
                                        <div class="p-2 rounded mb-2" style="background: #fff; border: 1px solid #facc15;">
                                            <strong style="color: #000;">Officer's Reason:</strong>
                                            <div class="mt-1" style="color: #334155;">{{ $application->forward_no_approval_reason }}</div>
                                        </div>
                                    @endif
                                    <small class="text-muted">
                                        <i class="ri-user-line me-1"></i>
                                        Forwarded by: {{ $application->assignedOfficer?->name ?? 'Officer' }} on {{ $application->last_action_at?->format('d M Y H:i') }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('staff.registrar.applications.approve-special-case', $application) }}" class="mb-3">
                            @csrf
                            <label class="form-label small fw-bold">Review Notes (Optional)</label>
                            <textarea class="form-control mb-3" name="decision_notes" rows="3" placeholder="Add your review notes..."></textarea>
                            <button class="btn w-100 shadow-sm" style="background: #facc15; color: #000; font-weight: 600;">
                                <i class="ri-check-line me-1"></i> Approve & Send to Accounts
                            </button>
                            <div class="form-text smaller mt-2">
                                This will route the application to Accounts for payment verification.
                            </div>
                        </form>

                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#fixRequestModal">
                                    <i class="ri-tools-line"></i> Return for Correction
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="ri-close-line"></i> Reject
                                </button>
                            </div>
                        </div>
                        <hr class="my-4">
                    @endif

                    {{-- Media House Two-Stage Payment: Official Letter Upload --}}
                    @if($application->status === 'verified_by_officer_pending_registrar' && $application->application_type === 'registration')
                        <div class="alert" style="background: rgba(250, 204, 21, 0.1); border: 2px solid #facc15; color: #000;">
                            <div class="d-flex align-items-start">
                                <i class="ri-file-text-line me-2" style="font-size: 1.5rem; color: #facc15;"></i>
                                <div>
                                    <h6 class="fw-bold mb-2" style="color: #000;">Media House Registration - Official Letter Required</h6>
                                    <p class="mb-0 small">This media house application requires an official approval letter to be uploaded before approval.</p>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('staff.registrar.applications.approve-with-letter', $application) }}" enctype="multipart/form-data" class="mb-3">
                            @csrf
                            
                            {{-- Category Selection --}}
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Media House Category <span class="text-danger">*</span></label>
                                <select name="category_code" class="form-select" required>
                                    <option value="">Select Category...</option>
                                    @foreach(\App\Models\Application::massMediaCategories() as $code => $name)
                                        <option value="{{ $code }}" {{ ($application->media_house_category_code ?? '') == $code ? 'selected' : '' }}>
                                            {{ $code }} - {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Official Letter Upload --}}
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Official Approval Letter <span class="text-danger">*</span></label>
                                <input type="file" name="official_letter" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                <div class="form-text smaller">
                                    <i class="ri-information-line me-1"></i>
                                    Upload the official approval letter (PDF or image, max 5MB). This letter will be sent to the applicant.
                                </div>
                            </div>

                            {{-- Decision Notes --}}
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Decision Notes (Optional)</label>
                                <textarea class="form-control" name="decision_notes" rows="3" placeholder="Add any internal notes about this approval..."></textarea>
                            </div>

                            <button type="submit" class="btn w-100 shadow-sm" style="background: #facc15; color: #000; font-weight: 600;">
                                <i class="ri-check-line me-1"></i> Approve & Upload Official Letter
                            </button>
                            <div class="form-text smaller mt-2">
                                After approval, the applicant will be prompted to pay the registration fee.
                            </div>
                        </form>

                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#fixRequestModal">
                                    <i class="ri-tools-line"></i> Return for Correction
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-danger w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="ri-close-line"></i> Reject
                                </button>
                            </div>
                        </div>
                        <hr class="my-4">
                    @endif

                    @php
                        $registrarActionableStatuses = [
                            'officer_approved', 'registrar_review', 'paid_confirmed',
                            'verified_by_officer', 'forwarded_to_registrar',
                        ];
                        $isPaynowPaid = ($application->payment_status === 'paid' && $application->paynow_reference);
                    @endphp

                    @if(in_array($application->status, $registrarActionableStatuses))
                        @php
                            $catCode = $application->accreditation_category_code ?? $application->media_house_category_code;
                            $isReg = ($application->application_type === 'registration');
                            $cats = $isReg ? \App\Models\Application::massMediaCategories() : \App\Models\Application::accreditationCategories();
                        @endphp

                        <form method="POST" action="{{ route('staff.registrar.applications.approve', $application) }}" class="mb-3" id="registrarApproveForm">
                            @csrf
                            <label class="form-label small fw-bold">{{ $isReg ? 'Media House Category' : 'Accreditation Category' }}</label>
                            <select name="category_code" class="form-select mb-2" required>
                                @foreach($cats as $code => $name)
                                    <option value="{{ $code }}" @selected($catCode === $code)>{{ $code }} - {{ $name }}</option>
                                @endforeach
                            </select>

                            @if($isReg)
                                <div class="bg-light p-3 rounded mb-3 border border-warning">
                                    <h6 class="fw-bold small mb-2"><i class="ri-list-check-2 me-1"></i> Physical Verification Checklist</h6>
                                    <p class="small text-muted mb-2">Before approving, confirm the following steps have been completed offline:</p>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input checklist-item" type="checkbox" id="chk_physical_office" required>
                                        <label class="form-check-label small" for="chk_physical_office">
                                            Physical office location has been verified.
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input checklist-item" type="checkbox" id="chk_equipment" required>
                                        <label class="form-check-label small" for="chk_equipment">
                                            Equipment and infrastructure have been verified.
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input checklist-item" type="checkbox" id="chk_interview" required>
                                        <label class="form-check-label small" for="chk_interview">
                                            Management interview has been conducted successfully.
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input checklist-item" type="checkbox" id="chk_docs" required>
                                        <label class="form-check-label small" for="chk_docs">
                                            Original hardcopy documents match digital uploads.
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <label class="form-label small fw-bold">Internal Notes (Optional)</label>
                            <textarea class="form-control mb-3" name="decision_notes" rows="3" placeholder="Add any notes..."></textarea>

                            <button type="submit" class="btn btn-success w-100 shadow-sm" id="btnApprove" {{ $isReg ? 'disabled' : '' }}>
                                <i class="ri-check-line me-1"></i> Approve & Request Registration Fee
                            </button>
                            <div class="form-text smaller mt-1">Applicant will be notified to pay the registration fee.</div>
                        </form>

                        @if($isReg)
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const checkboxes = document.querySelectorAll('.checklist-item');
                                    const btnApprove = document.getElementById('btnApprove');
                                    
                                    function checkAllChecked() {
                                        let allChecked = true;
                                        checkboxes.forEach(chk => {
                                            if (!chk.checked) allChecked = false;
                                        });
                                        btnApprove.disabled = !allChecked;
                                    }

                                    checkboxes.forEach(chk => {
                                        chk.addEventListener('change', checkAllChecked);
                                    });
                                });
                            </script>
                        @endif

                        <div class="row g-2">
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-warning w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#returnModal">
                                    <i class="ri-arrow-go-back-line"></i> Return to Accreditation Officer
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info small mb-0 text-center">
                            <i class="ri-information-line me-1"></i> No pending actions for Registrar.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODALS --}}

{{-- Approve For Payment Modal --}}
<div class="modal fade" id="approveForPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('staff.registrar.applications.approve-for-payment', $application) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Approve for Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will forward the application to the Accounts department for payment verification. The applicant will be notified to proceed with payment.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Internal Notes for Accounts</label>
                    <textarea name="decision_notes" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Forward to Accounts</button>
            </div>
        </form>
    </div>
</div>

{{-- Reassign Category Modal --}}
<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('staff.registrar.applications.reassign-category', $application) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reassign Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @php
                    $isRegistration = $application->application_type === 'registration';
                    $cats = $isRegistration ? \App\Models\Application::massMediaCategories() : \App\Models\Application::accreditationCategories();
                @endphp
                <div class="mb-3">
                    <label class="form-label small fw-bold">Select New Category</label>
                    <select name="category_code" class="form-select" required>
                        @foreach($cats as $code => $name)
                            <option value="{{ $code }}" {{ ($application->accreditation_category_code ?? $application->media_house_category_code) == $code ? 'selected' : '' }}>
                                {{ $code }} - {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason for Reassignment</label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="State why the category is being changed..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Reassign & Update</button>
            </div>
        </form>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('staff.registrar.applications.return', $application) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Return to Accreditation Officer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">This will send the application back to the Accreditation Officer for correction.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Notes / Required Fixes</label>
                    <textarea name="decision_notes" class="form-control" rows="4" required placeholder="Specify what needs to be fixed..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Send Back</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('staff.registrar.applications.reject', $application) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Reject Application</h5>
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

{{-- Returned / Correction Modal --}}
<div class="modal fade" id="fixRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('staff.registrar.applications.send-fix-request', $application) }}">
            @csrf
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="ri-tools-line me-2"></i>Return Application to Officer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    <i class="ri-information-line me-1"></i>
                    Use this to request the Accreditation Officer to correct application data.
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Return Reason Type</label>
                    <select name="request_type" class="form-select" required>
                        <option value="">Select type...</option>
                        <option value="data_correction">Data Correction</option>
                        <option value="category_change">Category Change</option>
                        <option value="document_issue">Document Issue</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="5" required placeholder="Describe what needs to be fixed..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ri-send-plane-line me-1"></i>Send Back
                </button>
            </div>
        </form>
    </div>
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
