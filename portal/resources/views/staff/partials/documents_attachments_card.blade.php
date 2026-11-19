@php
  $docs = $application->documents ?? collect();
  $paymentSubs = $application->paymentSubmissions ?? collect();

  $docGroups = [
    'Identity & Personal' => ['id_scan', 'passport_photo', 'passport_biodata_page', 'national_id'],
    'Employment & Professional' => ['employment_letter', 'employer_letter', 'reference_letter', 'educational_certificate'],
    'Accreditation' => ['current_card', 'clearance_letter', 'affidavit', 'police_report'],
    'Media House (AP4)' => [
      'letter_from_parent_org', 'passport_copies', 'work_permits', 'proof_of_zim_address',
      'certificate_of_incorporation', 'directors_ids', 'mission_statement',
      'code_of_ethics', 'code_of_conduct', 'style_book', 'journalists_list'
    ],
    'Payment & Supporting' => ['payment_proof', 'payment_supporting', 'waiver_document', 'receipt'],
  ];

  $docLabels = [
    'id_scan' => 'National ID Scan',
    'passport_photo' => 'Passport Photo',
    'passport_biodata_page' => 'Passport Bio-Data Page',
    'national_id' => 'National ID',
    'employment_letter' => 'Employment Letter',
    'employer_letter' => 'Employer Letter',
    'reference_letter' => 'Reference / Testimonial / Affidavit',
    'educational_certificate' => 'Educational Certificate',
    'current_card' => 'Current Accreditation Card',
    'clearance_letter' => 'Clearance Letter',
    'affidavit' => 'Affidavit',
    'police_report' => 'Police Report',
    'letter_from_parent_org' => 'Letter from Parent Organization',
    'passport_copies' => 'Passport Copies',
    'work_permits' => 'Work Permits',
    'proof_of_zim_address' => 'Proof of Zimbabwe Address',
    'certificate_of_incorporation' => 'Certificate of Incorporation',
    'directors_ids' => 'Directors IDs',
    'mission_statement' => 'Mission Statement',
    'code_of_ethics' => 'Code of Ethics',
    'code_of_conduct' => 'Code of Conduct',
    'style_book' => 'Style Book',
    'journalists_list' => 'Journalists List',
    'payment_proof' => 'Payment Proof',
    'payment_supporting' => 'Payment Supporting Document',
    'waiver_document' => 'Waiver Document',
    'receipt' => 'Receipt',
  ];

  $groupedDocs = [];
  $ungrouped = [];
  foreach($docs as $doc) {
    $placed = false;
    foreach($docGroups as $group => $types) {
      if(in_array($doc->doc_type, $types)) {
        $groupedDocs[$group][] = $doc;
        $placed = true;
        break;
      }
    }
    if(!$placed) $ungrouped[] = $doc;
  }
  if(count($ungrouped)) $groupedDocs['Other Documents'] = $ungrouped;
@endphp

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
    <span><i class="ri-attachment-2 me-1"></i> Documents & Attachments</span>
    <span class="badge bg-primary-subtle text-primary border border-primary">{{ $docs->count() }} uploaded</span>
  </div>
  <div class="card-body p-0">
    @if($docs->count())
      @foreach($groupedDocs as $groupName => $groupDocs)
        <div class="border-bottom">
          <div class="px-3 py-2 bg-light">
            <span class="small fw-bold text-muted text-uppercase">{{ $groupName }}</span>
          </div>
          <div class="list-group list-group-flush">
            @foreach($groupDocs as $doc)
              @php
                $isImage = str_starts_with($doc->mime ?? '', 'image/');
                $isPdf = str_contains($doc->mime ?? '', 'pdf');
                $icon = $isImage ? 'ri-image-line' : ($isPdf ? 'ri-file-pdf-line' : 'ri-file-line');
                $statusColor = match($doc->status ?? 'pending') {
                  'accepted' => 'success',
                  'rejected' => 'danger',
                  default => 'secondary',
                };
              @endphp
              <div class="list-group-item px-3 py-2">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="d-flex align-items-start gap-2 flex-grow-1">
                    @if($isImage)
                      <a href="{{ $doc->url }}" target="_blank" class="flex-shrink-0">
                        <img src="{{ $doc->url }}" alt="{{ $doc->document_type }}" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                      </a>
                    @else
                      <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded border bg-light" style="width: 50px; height: 50px;">
                        <i class="{{ $icon }} text-muted" style="font-size: 1.3rem;"></i>
                      </div>
                    @endif
                    <div>
                      <div class="fw-semibold small">{{ $docLabels[$doc->doc_type] ?? ($doc->document_type ?? ucwords(str_replace('_',' ', (string)$doc->doc_type))) }}</div>
                      <div class="text-muted" style="font-size: 0.75rem;">
                        {{ $doc->original_name ?? basename($doc->file_path ?? 'document') }}
                        @if($doc->size)
                          &bull; {{ number_format($doc->size / 1024, 0) }}KB
                        @endif
                      </div>
                      <div class="text-muted" style="font-size: 0.7rem;">
                        Uploaded {{ $doc->created_at?->format('d M Y H:i') }}
                      </div>
                      @if($doc->review_notes)
                        <div class="mt-1 small text-{{ $statusColor }}">
                          <i class="ri-chat-1-line me-1"></i>{{ $doc->review_notes }}
                        </div>
                      @endif
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-1">
                    <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border border-{{ $statusColor }}">{{ ucfirst($doc->status ?? 'pending') }}</span>
                    <a href="{{ $doc->url }}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-1" title="View / Download">
                      <i class="ri-eye-line"></i>
                    </a>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    @else
      <div class="p-4 text-center text-muted">
        <i class="ri-folder-open-line d-block mb-2" style="font-size: 2rem;"></i>
        <div class="small">No documents uploaded yet.</div>
      </div>
    @endif
  </div>
</div>

@if($paymentSubs->count())
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
    <span><i class="ri-bank-card-line me-1"></i> Payment Submissions & Proof</span>
    <span class="badge bg-primary-subtle text-primary border border-primary">{{ $paymentSubs->count() }} submission(s)</span>
  </div>
  <div class="card-body p-0">
    @foreach($paymentSubs as $ps)
      @php
        $psColor = match($ps->status) {
          'verified' => 'success',
          'rejected' => 'danger',
          'submitted' => 'warning',
          default => 'secondary',
        };
      @endphp
      <div class="border-bottom p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="fw-bold small">{{ $ps->getStageLabel() }}</span>
            <span class="badge bg-{{ $psColor }}-subtle text-{{ $psColor }} border border-{{ $psColor }} ms-2">{{ strtoupper($ps->status) }}</span>
          </div>
          <span class="badge bg-dark text-white">{{ $ps->getMethodLabel() }}</span>
        </div>

        <div class="row g-2 small">
          <div class="col-6 col-md-3">
            <div class="text-muted">Amount</div>
            <div class="fw-bold">{{ $ps->amount ? number_format($ps->amount, 2) : '—' }} {{ $ps->currency ?? 'USD' }}</div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Reference</div>
            <div class="fw-bold">{{ $ps->reference ?? '—' }}</div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Submitted</div>
            <div>{{ $ps->submitted_at?->format('d M Y H:i') ?? $ps->created_at?->format('d M Y H:i') ?? '—' }}</div>
          </div>
          <div class="col-6 col-md-3">
            <div class="text-muted">Verified</div>
            <div>{{ $ps->verified_at?->format('d M Y H:i') ?? 'Pending' }}</div>
          </div>
        </div>

        @if($ps->proof_path)
          <div class="mt-2 p-2 bg-light rounded d-flex align-items-center gap-2">
            <i class="ri-file-image-line text-primary" style="font-size: 1.2rem;"></i>
            <div class="flex-grow-1">
              <div class="small fw-semibold">Payment Proof Uploaded</div>
              <div style="font-size: 0.7rem;" class="text-muted">{{ basename($ps->proof_path) }}</div>
            </div>
            <a href="{{ asset('storage/' . $ps->proof_path) }}" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2">
              <i class="ri-eye-line me-1"></i>View Proof
            </a>
          </div>
        @endif

        @if($ps->waiver_path)
          <div class="mt-2 p-2 bg-light rounded d-flex align-items-center gap-2">
            <i class="ri-file-shield-line text-warning" style="font-size: 1.2rem;"></i>
            <div class="flex-grow-1">
              <div class="small fw-semibold">Waiver Document</div>
              <div style="font-size: 0.7rem;" class="text-muted">{{ basename($ps->waiver_path) }}</div>
            </div>
            <a href="{{ asset('storage/' . $ps->waiver_path) }}" target="_blank" class="btn btn-xs btn-outline-warning py-0 px-2">
              <i class="ri-eye-line me-1"></i>View Waiver
            </a>
          </div>
        @endif

        @if($ps->rejection_reason)
          <div class="mt-2 p-2 bg-danger-subtle rounded small text-danger">
            <i class="ri-error-warning-line me-1"></i><strong>Rejection Reason:</strong> {{ $ps->rejection_reason }}
          </div>
        @endif
      </div>
    @endforeach
  </div>
</div>
@endif

@php
  $appProofPath = $application->payment_proof_path ?? null;
  $appPaynowRef = $application->paynow_reference ?? null;
  $appReceiptNo = $application->receipt_number ?? null;
  $appWaiverPath = $application->waiver_path ?? null;
  $hasDirectPayment = $appProofPath || $appPaynowRef || $appReceiptNo || $appWaiverPath;
@endphp

@if($hasDirectPayment && $paymentSubs->count() === 0)
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold">
    <i class="ri-money-dollar-circle-line me-1"></i> Payment Information
  </div>
  <div class="card-body">
    <div class="row g-2 small">
      @if($appPaynowRef)
        <div class="col-md-6">
          <div class="text-muted">Paynow Reference</div>
          <div class="fw-bold">{{ $appPaynowRef }}</div>
        </div>
      @endif
      @if($appReceiptNo)
        <div class="col-md-6">
          <div class="text-muted">Receipt Number</div>
          <div class="fw-bold">{{ $appReceiptNo }}</div>
        </div>
      @endif
    </div>
    @if($appProofPath)
      <div class="mt-2 p-2 bg-light rounded d-flex align-items-center gap-2">
        <i class="ri-file-image-line text-primary" style="font-size: 1.2rem;"></i>
        <div class="flex-grow-1">
          <div class="small fw-semibold">Payment Proof</div>
        </div>
        <a href="{{ asset('storage/' . $appProofPath) }}" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2">
          <i class="ri-eye-line me-1"></i>View
        </a>
      </div>
    @endif
    @if($appWaiverPath)
      <div class="mt-2 p-2 bg-light rounded d-flex align-items-center gap-2">
        <i class="ri-file-shield-line text-warning" style="font-size: 1.2rem;"></i>
        <div class="flex-grow-1">
          <div class="small fw-semibold">Waiver Document</div>
        </div>
        <a href="{{ asset('storage/' . $appWaiverPath) }}" target="_blank" class="btn btn-xs btn-outline-warning py-0 px-2">
          <i class="ri-eye-line me-1"></i>View
        </a>
      </div>
    @endif
  </div>
</div>
@endif
