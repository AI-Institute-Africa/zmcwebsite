@extends('layouts.portal')

@php
  $presetType = $ap5Type ?? null;
  $pageTitle = $presetType === 'renewal' ? 'Renewal of Accreditation Card'
    : ($presetType === 'replacement' ? 'Replacement of Accreditation Card'
    : 'Renewal / Replacement of Accreditation Card');
@endphp

@section('title', $presetType ? ucfirst($presetType) : 'Renewal / Replacement')
@section('page_title', strtoupper($pageTitle))

@section('content')
@include('portal.partials.existing_documents')
<div id="renewal-page">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark m-0" style="font-size:18px;">{{ $pageTitle }}</h4>
    <a class="btn btn-secondary" href="{{ route('accreditation.home') }}">
      <i class="ri-arrow-left-line me-1"></i>Back to Tracker
    </a>
  </div>

  @if(isset($drafts) && $drafts->count())
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="ri-draft-line me-1"></i>My Saved Drafts</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Saved</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($drafts as $d)
                <tr>
                  <td class="fw-semibold">{{ $d->reference }}</td>
                  <td class="text-capitalize">{{ $d->request_type }}</td>
                  <td class="text-muted">{{ $d->created_at?->format('d/m/Y H:i') }}</td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-primary" href="{{ route($d->request_type === 'replacement' ? 'accreditation.replacement' : 'accreditation.renewal', ['draft' => $d->reference]) }}">
                      <i class="ri-edit-line me-1"></i>Continue Editing
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

  <div class="form-container">
    <div class="form-header">
      <h1>Application for {{ $presetType ? ucfirst($presetType) : 'Renewal or Replacement' }} of Accreditation</h1>
      <p>{{ config('legislation.registration_regulations') }}</p>
    </div>

    <div class="form-steps-container">
      <div class="step-progress">
        <div class="step-progress-bar">
          @if(!$presetType)
          <div class="step active" data-step="1"><div class="step-number">1</div><div class="step-label">Type</div></div>
          <div class="step" data-step="2"><div class="step-number">2</div><div class="step-label">Search</div></div>
          <div class="step" data-step="3"><div class="step-number">3</div><div class="step-label">Verify Details</div></div>
          <div class="step" data-step="4"><div class="step-number">4</div><div class="step-label">Documents</div></div>
          <div class="step" data-step="5"><div class="step-number">5</div><div class="step-label">Declaration</div></div>
          @else
          <div class="step active" data-step="1"><div class="step-number">1</div><div class="step-label">Search</div></div>
          <div class="step" data-step="2"><div class="step-number">2</div><div class="step-label">Verify Details</div></div>
          <div class="step" data-step="3"><div class="step-number">3</div><div class="step-label">Documents</div></div>
          <div class="step" data-step="4"><div class="step-number">4</div><div class="step-label">Declaration</div></div>
          @endif
        </div>
      </div>

      <form id="ap5Form" onsubmit="return false;" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="draft_reference" value="{{ $draft->reference ?? '' }}">
        <input type="hidden" name="current_step" id="ap5_current_step" value="{{ data_get($draft, 'form_data.current_step', 1) }}">
        <input type="hidden" id="ap5_type" name="request_type" value="{{ $presetType ?? '' }}" required>

        @if(!$presetType)
        <div class="step-content active" id="ap5-step-type">
          <h3 class="step-title">Select Application Type</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Choose Renewal or Replacement. For Replacement, select the reason.
          </div>

          <div class="app-type-container mt-3">
            <div class="app-type-cards">
              <div class="app-type-card" data-type="renewal">
                <i class="ri-refresh-line"></i>
                <h4>Renewal</h4>
                <p>Annual renewal of your accreditation card.</p>
              </div>

              <div class="app-type-card" data-type="replacement">
                <i class="ri-exchange-line"></i>
                <h4>Replacement</h4>
                <p>Lost / damaged / stolen card replacement.</p>
              </div>
            </div>
          </div>

          <div id="ap5-replacement-reason" style="display:none; margin-top:20px;">
            <label class="form-label required">Reason for Replacement</label>
            <div class="checkbox-group">
              <div class="checkbox-item">
                <input type="radio" id="ap5-reason-lost" name="replacement_reason" value="lost">
                <label for="ap5-reason-lost">Lost</label>
              </div>
              <div class="checkbox-item">
                <input type="radio" id="ap5-reason-damaged" name="replacement_reason" value="damaged">
                <label for="ap5-reason-damaged">Damaged</label>
              </div>
              <div class="checkbox-item">
                <input type="radio" id="ap5-reason-stolen" name="replacement_reason" value="stolen">
                <label for="ap5-reason-stolen">Stolen</label>
              </div>
            </div>
          </div>
        </div>
        @endif

        @if($presetType === 'replacement')
        <div class="step-content active" id="ap5-step-replacement-reason-inline" style="display:none;"></div>
        @endif

        <div class="step-content{{ $presetType ? ' active' : '' }}" id="ap5-step-search">
          <h3 class="step-title">Find Your Accreditation Record</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Search using your National ID number or Accreditation number. Either field is sufficient.
          </div>

          @if($presetType === 'replacement')
          <div class="alert alert-info mb-3">
            <i class="ri-exchange-line me-2"></i>
            <strong>Replacement Application</strong> &mdash; Select the reason for replacement below.
          </div>
          <div class="mb-4" id="ap5-replacement-reason-inline">
            <label class="form-label required">Reason for Replacement</label>
            <div class="checkbox-group">
              <div class="checkbox-item">
                <input type="radio" id="ap5-reason-lost-inline" name="replacement_reason" value="lost">
                <label for="ap5-reason-lost-inline">Lost</label>
              </div>
              <div class="checkbox-item">
                <input type="radio" id="ap5-reason-damaged-inline" name="replacement_reason" value="damaged">
                <label for="ap5-reason-damaged-inline">Damaged</label>
              </div>
              <div class="checkbox-item">
                <input type="radio" id="ap5-reason-stolen-inline" name="replacement_reason" value="stolen">
                <label for="ap5-reason-stolen-inline">Stolen</label>
              </div>
            </div>
          </div>
          @endif

          <div class="form-row">
            <div class="form-field">
              <label class="form-label">National ID Number</label>
              <input type="text" class="form-control" id="ap5_search_nid" placeholder="63-1234567X50">
              <small class="text-muted">Enter your National ID number</small>
            </div>
            <div class="form-field">
              <label class="form-label">Accreditation Number</label>
              <input type="text" class="form-control" id="ap5_search_accnum" placeholder="e.g. ZMC-{{ now()->year }}-0001">
              <small class="text-muted">Enter your ZMC accreditation/reference number</small>
            </div>
          </div>

          <div class="mt-3">
            <button type="button" class="btn btn-primary" id="ap5SearchBtn">
              <i class="ri-search-line me-1"></i> Search
            </button>
          </div>

          <div id="ap5SearchResult" class="mt-3" style="display:none;"></div>
          <div id="ap5SearchNotFound" class="mt-3" style="display:none;">
            <div class="alert alert-warning">
              <i class="ri-error-warning-line me-2"></i>
              No matching record found. Please check your details or fill in your information manually below.
            </div>
          </div>

          <div id="ap5ManualEntry" style="display:none; margin-top:20px; padding-top:20px; border-top:1px solid #e2e8f0;">
            <h6 class="fw-bold mb-3">Enter Your Details Manually</h6>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Surname</label>
                <input type="text" class="form-control" name="surname" required>
              </div>
              <div class="form-field">
                <label class="form-label required">First Name</label>
                <input type="text" class="form-control" name="first_name" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label">Other Names</label>
                <input type="text" class="form-control" name="other_names">
              </div>
              <div class="form-field">
                <label class="form-label required">Sex</label>
                <div class="checkbox-group">
                  <div class="checkbox-item">
                    <input type="radio" id="ap5-sex-male" name="gender" value="male" required>
                    <label for="ap5-sex-male">Male</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="radio" id="ap5-sex-female" name="gender" value="female" required>
                    <label for="ap5-sex-female">Female</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Date of Birth</label>
                <input type="date" class="form-control" name="dob" required>
              </div>
              <div class="form-field">
                <label class="form-label required">Nationality</label>
                <select class="form-control" name="nationality" required>
                  <option value="Zimbabwean" selected>Zimbabwean (Local)</option>
                  <option value="Foreign">Foreign</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Accreditation Number</label>
                <input type="text" class="form-control" name="accreditation_number" placeholder="e.g. ZMC-{{ now()->year }}-0001" required>
              </div>
              <div class="form-field">
                <label class="form-label required">National ID / Passport Number</label>
                <input type="text" class="form-control" name="national_id" placeholder="63-1234567X50" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Practitioner Type</label>
                <div class="checkbox-group">
                  <div class="checkbox-item">
                    <input type="radio" id="ap5-type-employed-manual" name="practitioner_type" value="employed" checked required>
                    <label for="ap5-type-employed-manual">Employed</label>
                  </div>
                  <div class="checkbox-item">
                    <input type="radio" id="ap5-type-freelancer-manual" name="practitioner_type" value="freelancer" required>
                    <label for="ap5-type-freelancer-manual">Freelancer</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="step-content" id="ap5-step-verify">
          <h3 class="step-title">Verify Your Personal Details</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Review the details below. Tick the checkbox next to any field you need to update, then make your changes.
          </div>

          <div class="mb-4">
            <label class="form-label required">Are there any changes to your personal details?</label>
            <div class="checkbox-group">
              <div class="checkbox-item">
                <input type="radio" id="ap5-changes-no" name="has_changes" value="no" required>
                <label for="ap5-changes-no">No, all details are correct</label>
              </div>
              <div class="checkbox-item">
                <input type="radio" id="ap5-changes-yes" name="has_changes" value="yes" required>
                <label for="ap5-changes-yes">Yes, I need to update some details</label>
              </div>
            </div>
          </div>

          <div id="ap5DetailsGrid" class="mt-3">
            <div class="table-responsive">
              <table class="table table-bordered" id="ap5DetailsTable">
                <thead>
                  <tr>
                    <th style="width:30px;" id="ap5ChangeCol" class="d-none">Edit</th>
                    <th style="width:35%;">Field</th>
                    <th>Value</th>
                  </tr>
                </thead>
                <tbody>
                  <tr data-field="surname">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">Surname</td>
                    <td><span class="ap5-display-val"></span><input type="text" class="form-control form-control-sm ap5-edit-val" name="surname" style="display:none;"></td>
                  </tr>
                  <tr data-field="first_name">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">First Name</td>
                    <td><span class="ap5-display-val"></span><input type="text" class="form-control form-control-sm ap5-edit-val" name="first_name" style="display:none;"></td>
                  </tr>
                  <tr data-field="other_names">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">Other Names</td>
                    <td><span class="ap5-display-val"></span><input type="text" class="form-control form-control-sm ap5-edit-val" name="other_names" style="display:none;"></td>
                  </tr>
                  <tr data-field="gender">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">Sex</td>
                    <td>
                      <span class="ap5-display-val"></span>
                      <select class="form-control form-control-sm ap5-edit-val" name="gender" style="display:none;">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                      </select>
                    </td>
                  </tr>
                  <tr data-field="dob">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">Date of Birth</td>
                    <td><span class="ap5-display-val"></span><input type="date" class="form-control form-control-sm ap5-edit-val" name="dob" style="display:none;"></td>
                  </tr>
                  <tr data-field="nationality">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">Nationality</td>
                    <td><span class="ap5-display-val"></span><input type="text" class="form-control form-control-sm ap5-edit-val" name="nationality" style="display:none;"></td>
                  </tr>
                  <tr data-field="national_id">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">National ID</td>
                    <td><span class="ap5-display-val"></span><input type="text" class="form-control form-control-sm ap5-edit-val" name="national_id" style="display:none;"></td>
                  </tr>
                  <tr data-field="accreditation_number">
                    <td class="ap5-change-check d-none"><input type="checkbox" class="form-check-input ap5-field-toggle"></td>
                    <td class="fw-semibold">Accreditation Number</td>
                    <td><span class="ap5-display-val"></span><input type="text" class="form-control form-control-sm ap5-edit-val" name="accreditation_number" style="display:none;" readonly></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <input type="hidden" name="practitioner_type" id="ap5_practitioner_type" value="employed">
        </div>

        <div class="step-content" id="ap5-step-docs">
          <h3 class="step-title">Required Documents</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Renewal: Employer letter only. Replacement: Affidavit + Employer letter. Police report only if stolen.
          </div>

          <div id="ap5-renewal-docs" style="display:none;">
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Employer Letter (Renewal)</label>
                <div class="upload-area">
                  <i class="ri-file-text-line"></i>
                  <h5>Upload Employer Letter</h5>
                  <p>PDF / JPG / PNG</p>
                  <input type="file" name="renewal_employer_letter" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                  <button type="button" class="upload-btn">Choose File</button>
                </div>
                <div class="uploaded-files" data-doc-type="renewal_employer_letter"></div>
              </div>
            </div>
          </div>

          <div id="ap5-replacement-docs" style="display:none;">
            <div class="alert alert-warning mb-3">
              <i class="ri-alert-line me-2"></i>
              Affidavit is required for replacement. Police report is required for stolen.
            </div>

            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Affidavit</label>
                <div class="upload-area">
                  <i class="ri-scales-line"></i>
                  <h5>Upload Affidavit</h5>
                  <p>PDF / JPG / PNG</p>
                  <input type="file" name="replacement_affidavit" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                  <button type="button" class="upload-btn">Choose File</button>
                </div>
                <div class="uploaded-files" data-doc-type="replacement_affidavit"></div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Employer Letter (Replacement)</label>
                <div class="upload-area">
                  <i class="ri-file-text-line"></i>
                  <h5>Upload Employer Letter</h5>
                  <p>PDF / JPG / PNG</p>
                  <input type="file" name="replacement_employer_letter" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                  <button type="button" class="upload-btn">Choose File</button>
                </div>
                <div class="uploaded-files" data-doc-type="replacement_employer_letter"></div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-field">
                <label class="form-label" id="ap5-police-label">Police Report (Only if stolen)</label>
                <div class="upload-area">
                  <i class="ri-shield-line"></i>
                  <h5>Upload Police Report</h5>
                  <p>Required if stolen</p>
                  <input type="file" name="replacement_police_report" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                  <button type="button" class="upload-btn">Choose File</button>
                </div>
                <div class="uploaded-files" data-doc-type="replacement_police_report"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="step-content" id="ap5-step-declaration">
          <h3 class="step-title">Declaration</h3>
          <div class="current-step-info"><i class="ri-information-line me-2"></i>Confirm and submit.</div>

          <div class="alert alert-warning">
            <h6 class="fw-bold"><i class="ri-file-text-line me-2"></i>Declaration</h6>
            <p class="mb-2">I declare that all the information given above, to the best of my knowledge is true and complete.</p>
            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" id="ap5-declare" name="declaration_confirmed" value="1" required>
              <label class="form-check-label" for="ap5-declare">I confirm that I have read and agree to this declaration.</label>
            </div>
          </div>
        </div>

        <div class="form-buttons">
          <button type="button" class="btn btn-secondary" id="ap5PrevBtn">
            <i class="ri-arrow-left-line"></i> Previous
          </button>

          <button type="button" class="btn btn-outline-primary" id="ap5SaveDraftBtn">
            <i class="ri-save-3-line"></i> Save as Draft
          </button>

          <button type="button" class="btn btn-primary" id="ap5NextBtn">
            Next <i class="ri-arrow-right-line"></i>
          </button>
        </div>
      </form>

      <div class="modal fade" id="ap5ReviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title fw-bold">Review Before Submitting</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="ap5ReviewBody"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Edit</button>
              <button type="button" class="btn btn-primary" id="ap5ConfirmSubmitBtn">
                <i class="ri-check-line"></i> Confirm & Submit
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const ap5PresetType = @json($presetType ?? null);
  let ap5Step = 1;
  let ap5Type = ap5PresetType || '';
  let ap5FoundData = null;
  let ap5ManualMode = false;

  const ap5Steps = document.querySelectorAll('#renewal-page .step');
  const ap5Contents = ap5PresetType
    ? [
        document.getElementById('ap5-step-search'),
        document.getElementById('ap5-step-verify'),
        document.getElementById('ap5-step-docs'),
        document.getElementById('ap5-step-declaration'),
      ]
    : [
        document.getElementById('ap5-step-type'),
        document.getElementById('ap5-step-search'),
        document.getElementById('ap5-step-verify'),
        document.getElementById('ap5-step-docs'),
        document.getElementById('ap5-step-declaration'),
      ];

  const ap5PrevBtn = document.getElementById('ap5PrevBtn');
  const ap5NextBtn = document.getElementById('ap5NextBtn');

  const replacementReasonBlock = document.getElementById('ap5-replacement-reason');
  const renewalDocs = document.getElementById('ap5-renewal-docs');
  const replacementDocs = document.getElementById('ap5-replacement-docs');

  function isVisible(el){
    return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
  }

  function ap5Show(step){
    ap5Contents.forEach((el, idx) => { if(el) el.classList.toggle('active', idx === (step-1)); });

    ap5Steps.forEach(s => {
      const n = parseInt(s.dataset.step, 10);
      s.classList.remove('active','completed');
      if(n === step) s.classList.add('active');
      if(n < step){
        s.classList.add('completed');
        s.querySelector('.step-number').innerHTML = '<i class="ri-check-line"></i>';
      }else{
        s.querySelector('.step-number').textContent = n;
      }
    });

    ap5PrevBtn.style.display = step === 1 ? 'none' : 'inline-block';
    ap5NextBtn.innerHTML = step === ap5Contents.length
      ? 'Submit Application <i class="ri-send-plane-line"></i>'
      : 'Next <i class="ri-arrow-right-line"></i>';
  }

  function applyAp5DocRequirements(){
    document.querySelectorAll('#renewal-page input[type="file"]').forEach(f => f.required = false);

    if(ap5Type === 'renewal'){
      const f = document.querySelector('input[name="renewal_employer_letter"]');
      if(f) f.required = true;
    }

    if(ap5Type === 'replacement'){
      const aff = document.querySelector('input[name="replacement_affidavit"]');
      const emp = document.querySelector('input[name="replacement_employer_letter"]');
      if(aff) aff.required = true;
      if(emp) emp.required = true;

      const reason = document.querySelector('input[name="replacement_reason"]:checked')?.value;
      const police = document.querySelector('input[name="replacement_police_report"]');
      if(police) police.required = (reason === 'stolen');

      const label = document.getElementById('ap5-police-label');
      if(label){
        label.textContent = (reason === 'stolen')
          ? 'Police Report (Required for stolen cards)'
          : 'Police Report (Only if stolen)';
      }
    }
  }

  function ap5Validate(step){
    const current = ap5Contents[step-1];
    if(!current) return true;

    if(!ap5PresetType && step === 1){
      if(!ap5Type){ alert('Please select Renewal or Replacement'); return false; }
      if(ap5Type === 'replacement'){
        const reason = document.querySelector('input[name="replacement_reason"]:checked');
        if(!reason){ alert('Please select a reason for replacement'); return false; }
      }
      return true;
    }

    const searchStep = ap5PresetType ? 1 : 2;
    const verifyStep = ap5PresetType ? 2 : 3;
    const docsStep = ap5PresetType ? 3 : 4;
    const declareStep = ap5PresetType ? 4 : 5;

    if(step === searchStep){
      if(!ap5FoundData && !ap5ManualMode){
        alert('Please search for your record first, or fill in your details manually.');
        return false;
      }
      if(ap5ManualMode){
        const manualFields = document.querySelectorAll('#ap5ManualEntry [required]');
        for(const field of manualFields){
          if(field.type === 'radio'){
            const group = document.querySelectorAll(`#ap5ManualEntry [name="${field.name}"]`);
            const anyChecked = Array.from(group).some(r => r.checked);
            if(!anyChecked){ alert('Please complete all required fields.'); return false; }
          } else if(!String(field.value || '').trim()){
            alert('Please complete all required fields.');
            field.focus();
            return false;
          }
        }
      }
      if(ap5Type === 'replacement'){
        const reason = document.querySelector('input[name="replacement_reason"]:checked');
        if(!reason){ alert('Please select a reason for replacement.'); return false; }
      }
      return true;
    }

    if(step === verifyStep){
      const hasChanges = document.querySelector('input[name="has_changes"]:checked');
      if(!hasChanges){ alert('Please indicate whether you have any changes to your details.'); return false; }
      return true;
    }

    if(step === docsStep){
      applyAp5DocRequirements();
      const required = current.querySelectorAll('[required]');
      for(const field of required){
        if(!isVisible(field)) continue;
        if(field.type === 'file' && (!field.files || !field.files.length)){
          alert('Please upload required documents.');
          return false;
        }
      }
      return true;
    }

    if(step === declareStep){
      const dec = document.getElementById('ap5-declare');
      if(!dec?.checked){ alert('Please accept the declaration terms.'); return false; }
      return true;
    }

    return true;
  }

  function setupUploads(root){
    root.querySelectorAll('.upload-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const input = btn.parentElement.querySelector('input[type="file"]');
        if(input) input.click();
      });
    });

    root.querySelectorAll('.upload-area input[type="file"]').forEach(input => {
      input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        const area = input.closest('.upload-area');
        const list = area?.parentElement?.querySelector('.uploaded-files');
        if(!file || !area || !list) return;

        area.style.borderColor = '#10b981';
        area.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';

        const fileName = file.name.length > 40 ? file.name.slice(0,40)+'...' : file.name;
        const fileSize = (file.size/1024).toFixed(1)+' KB';

        list.innerHTML = `
          <div class="uploaded-file d-flex align-items-center justify-content-between p-2 border rounded mb-2">
            <div class="d-flex align-items-center gap-2">
              <i class="ri-file-text-line file-icon"></i>
              <div>
                <div class="file-name fw-semibold" style="font-size:13px;">${fileName}</div>
                <div class="file-size text-muted" style="font-size:11px;">${fileSize}</div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-light">Remove</button>
          </div>
        `;

        list.querySelector('button')?.addEventListener('click', () => {
          input.value = '';
          list.innerHTML = '';
          area.style.borderColor = '';
          area.style.backgroundColor = '';
        });
      });
    });
  }

  function populateVerifyTable(data){
    const rows = document.querySelectorAll('#ap5DetailsTable tbody tr');
    rows.forEach(row => {
      const field = row.dataset.field;
      if(!field) return;
      const val = (data[field] !== undefined && data[field] !== null) ? data[field] : '';
      const displaySpan = row.querySelector('.ap5-display-val');
      const editInput = row.querySelector('.ap5-edit-val');
      if(displaySpan) displaySpan.textContent = val || '-';
      if(editInput) editInput.value = val;
    });
  }

  document.querySelectorAll('input[name="has_changes"]').forEach(r => {
    r.addEventListener('change', () => {
      const showEditing = r.value === 'yes' && r.checked;
      const checkCol = document.getElementById('ap5ChangeCol');
      const checkCells = document.querySelectorAll('.ap5-change-check');

      if(showEditing){
        checkCol?.classList.remove('d-none');
        checkCells.forEach(c => c.classList.remove('d-none'));
      } else {
        checkCol?.classList.add('d-none');
        checkCells.forEach(c => c.classList.add('d-none'));
        document.querySelectorAll('.ap5-field-toggle').forEach(cb => {
          cb.checked = false;
          const row = cb.closest('tr');
          if(row){
            row.querySelector('.ap5-display-val').style.display = '';
            row.querySelector('.ap5-edit-val').style.display = 'none';
          }
        });
      }
    });
  });

  document.addEventListener('change', (e) => {
    if(!e.target.classList.contains('ap5-field-toggle')) return;
    const row = e.target.closest('tr');
    if(!row) return;
    const displaySpan = row.querySelector('.ap5-display-val');
    const editInput = row.querySelector('.ap5-edit-val');
    if(e.target.checked){
      displaySpan.style.display = 'none';
      editInput.style.display = '';
      editInput.focus();
    } else {
      displaySpan.style.display = '';
      editInput.style.display = 'none';
    }
  });

  async function ap5DoSearch(){
    const nid = document.getElementById('ap5_search_nid')?.value?.trim();
    const accnum = document.getElementById('ap5_search_accnum')?.value?.trim();

    if(!nid && !accnum){
      alert('Please enter your National ID or Accreditation Number.');
      return;
    }

    const searchType = nid ? 'national_id' : 'accreditation_number';
    const searchValue = nid || accnum;

    const resultDiv = document.getElementById('ap5SearchResult');
    const notFoundDiv = document.getElementById('ap5SearchNotFound');
    const manualDiv = document.getElementById('ap5ManualEntry');

    resultDiv.style.display = 'none';
    notFoundDiv.style.display = 'none';
    manualDiv.style.display = 'none';

    try{
      const res = await fetch('{{ route("accreditation.lookupPractitioner") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ search_type: searchType, search_value: searchValue })
      });

      const data = await res.json();

      if(data.found){
        ap5FoundData = data.data;
        ap5ManualMode = false;
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = `
          <div class="alert alert-success">
            <i class="ri-checkbox-circle-fill me-2"></i>
            <strong>Record found!</strong> ${data.data.first_name} ${data.data.surname} (${data.data.accreditation_number})
            <br><small class="text-muted">Your details will be pre-filled in the next step. Click Next to continue.</small>
          </div>
        `;
        populateVerifyTable(data.data);
      } else {
        notFoundDiv.style.display = 'block';
        manualDiv.style.display = 'block';
        ap5FoundData = null;
        ap5ManualMode = true;
      }
    }catch(e){
      console.error(e);
      notFoundDiv.style.display = 'block';
      manualDiv.style.display = 'block';
      ap5FoundData = null;
      ap5ManualMode = true;
    }
  }

  document.getElementById('ap5SearchBtn')?.addEventListener('click', ap5DoSearch);

  async function submitAp5(){
    const form = document.getElementById('ap5Form');
    const fd = new FormData(form);
    fd.set('request_type', ap5Type);

    if(ap5FoundData && !ap5ManualMode){
      const hasChanges = document.querySelector('input[name="has_changes"]:checked')?.value;
      if(hasChanges === 'no'){
        Object.entries(ap5FoundData).forEach(([key, val]) => {
          if(!fd.has(key) || !fd.get(key)) fd.set(key, val);
        });
      }
    }

    try{
      const res = await fetch('{{ route("accreditation.submitAp5") }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: fd
      });

      const data = await res.json().catch(() => ({}));

      if(!res.ok){
        alert(data.message || 'Submission failed. Please check required fields.');
        return;
      }

      alert('Application submitted successfully! Reference: ' + (data.reference || '—'));
      window.location.href = "{{ route('accreditation.home') }}";
    }catch(e){
      console.error(e);
      alert('Network error submitting application.');
    }
  }

  function listSelectedFiles(){
    const items = [];
    document.querySelectorAll('#ap5Form input[type="file"]').forEach(inp => {
      if (inp.files && inp.files[0]) {
        const f = inp.files[0];
        items.push({
          label: (inp.getAttribute('name') || 'document').replaceAll('_',' '),
          name: f.name,
          size: (f.size/1024).toFixed(1) + ' KB'
        });
      }
    });
    return items;
  }

  function buildReviewHtml(){
    const fd = new FormData(document.getElementById('ap5Form'));
    const files = listSelectedFiles();

    const displayData = ap5FoundData || {};
    const rows = [
      ['Application Type', ap5Type || '-'],
      ['Surname', fd.get('surname') || displayData.surname || '-'],
      ['First Name', fd.get('first_name') || displayData.first_name || '-'],
      ['Other Names', fd.get('other_names') || displayData.other_names || '-'],
      ['Sex', fd.get('gender') || displayData.gender || '-'],
      ['Date of Birth', fd.get('dob') || displayData.dob || '-'],
      ['Nationality', fd.get('nationality') || displayData.nationality || '-'],
      ['National ID', fd.get('national_id') || displayData.national_id || '-'],
      ['Accreditation No', fd.get('accreditation_number') || displayData.accreditation_number || '-'],
    ];

    if(ap5Type === 'replacement'){
      rows.push(['Reason', fd.get('replacement_reason') || '-']);
    }

    const table = `
      <div class="table-responsive">
        <table class="table table-sm">
          <tbody>
            ${rows.map(r=>`<tr><th style="width:38%">${r[0]}</th><td>${(r[1]||'-')}</td></tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    const filesHtml = files.length ? `
      <div class="mt-3">
        <h6 class="fw-bold mb-2">Uploaded Documents</h6>
        <div class="list-group">
          ${files.map(f=>`
            <div class="list-group-item d-flex align-items-center">
              <i class="ri-file-text-line text-primary me-3 fs-5"></i>
              <div>
                <div class="fw-semibold">${f.label}</div>
                <small class="text-muted">${f.name} (${f.size})</small>
              </div>
              <i class="ri-checkbox-circle-fill text-success ms-auto"></i>
            </div>
          `).join('')}
        </div>
      </div>` : `<div class="alert alert-danger mt-3 mb-0">No documents selected.</div>`;

    return table + filesHtml;
  }

  async function saveDraftAp5(){
    const form = document.getElementById('ap5Form');
    const fd = new FormData(form);
    fd.set('request_type', ap5Type);

    try{
      const res = await fetch('{{ route('accreditation.renewals.saveDraft') }}', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: fd
      });
      const data = await res.json().catch(()=>({}));
      if(!res.ok){
        alert(data.message || 'Draft save failed.');
        return;
      }
      alert('Draft saved! Reference: ' + (data.reference || '—'));
    }catch(e){
      console.error(e);
      alert('Network error saving draft.');
    }
  }

  (function() {
    document.querySelectorAll('#renewal-page .app-type-card').forEach(card => {
      card.style.cursor = 'pointer';
      card.style.position = 'relative';
      card.style.zIndex = '10';
      card.addEventListener('click', () => {
        document.querySelectorAll('#renewal-page .app-type-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        ap5Type = card.dataset.type;
        document.getElementById('ap5_type').value = ap5Type;

        if(replacementReasonBlock) replacementReasonBlock.style.display = (ap5Type === 'replacement') ? 'block' : 'none';
        if(renewalDocs) renewalDocs.style.display = (ap5Type === 'renewal') ? 'block' : 'none';
        if(replacementDocs) replacementDocs.style.display = (ap5Type === 'replacement') ? 'block' : 'none';

        applyAp5DocRequirements();
      });
    });

    document.querySelectorAll('input[name="replacement_reason"]').forEach(r => {
      r.addEventListener('change', () => applyAp5DocRequirements());
    });

    ap5PrevBtn.addEventListener('click', () => {
      ap5Step = Math.max(1, ap5Step - 1);
      ap5Show(ap5Step);
    });

    ap5NextBtn.addEventListener('click', () => {
      applyAp5DocRequirements();

      if(!ap5Validate(ap5Step)) return;

      if(ap5Step === ap5Contents.length){
        const body = document.getElementById('ap5ReviewBody');
        if (body) body.innerHTML = buildReviewHtml();
        const modalEl = document.getElementById('ap5ReviewModal');
        if (modalEl && window.bootstrap) {
          const m = bootstrap.Modal.getOrCreateInstance(modalEl);
          m.show();
        } else {
          if (confirm('Submit your application now?')) submitAp5();
        }
        return;
      }

      if(ap5ManualMode){
        const searchStepIdx = ap5PresetType ? 0 : 1;
        if(ap5Step === searchStepIdx + 1){
          const manualEntryDiv = document.getElementById('ap5ManualEntry');
          const manualData = {};
          manualEntryDiv.querySelectorAll('input, select').forEach(el => {
            if(el.name && el.type === 'radio'){
              if(el.checked) manualData[el.name] = el.value;
            } else if(el.name){
              manualData[el.name] = el.value;
            }
          });
          populateVerifyTable(manualData);
        }
      }

      ap5Step = Math.min(ap5Contents.length, ap5Step + 1);
      ap5Show(ap5Step);
    });

    ap5Show(1);
    setupUploads(document.getElementById('renewal-page'));

    if(ap5PresetType){
      if(renewalDocs) renewalDocs.style.display = (ap5PresetType === 'renewal') ? 'block' : 'none';
      if(replacementDocs) replacementDocs.style.display = (ap5PresetType === 'replacement') ? 'block' : 'none';
      applyAp5DocRequirements();
    }

    @if(isset($draft) && $draft)
      const draftData = @json($draft->form_data ?? []);
      if (draftData) {
        if (draftData.request_type) {
          const card = document.querySelector(`.app-type-card[data-type="${draftData.request_type}"]`);
          if (card) card.click();
        }

        Object.keys(draftData).forEach(key => {
          const el = document.querySelector(`#renewal-page [name="${key}"]`);
          if (!el) return;

          if (el.type === 'radio') {
            const r = document.querySelector(`#renewal-page [name="${key}"][value="${draftData[key]}"]`);
            if (r) r.checked = true;
          } else if (el.type === 'checkbox') {
            el.checked = String(draftData[key]) === '1' || draftData[key] === true;
          } else {
            el.value = draftData[key];
          }
        });

        applyAp5DocRequirements();
      }

      // Show existing uploaded documents with remove buttons
      const existingDocs = @json($draft->documents ?? []);
      const deleteBaseUrl = @json(route('accreditation.documents.delete', ['document' => '__ID__']));
      if (existingDocs && existingDocs.length) {
        existingDocs.forEach(doc => {
          const container = document.querySelector(`.uploaded-files[data-doc-type="${doc.doc_type}"]`);
          if (!container) return;
          const docName = doc.original_name || doc.doc_type.replace(/_/g, ' ');
          const docSize = doc.size ? ((doc.size / 1024).toFixed(1) + ' KB') : '';
          const deleteUrl = deleteBaseUrl.replace('__ID__', doc.id);
          const isImage = (doc.mime || '').startsWith('image/');
          const iconClass = isImage ? 'ri-image-line' : 'ri-file-text-line';
          const el = document.createElement('div');
          el.className = 'existing-doc-item';
          el.innerHTML = `
            <div class="doc-info">
              <i class="${iconClass}"></i>
              <div>
                <div class="doc-name">${docName}</div>
                <div class="doc-meta">${docSize}</div>
              </div>
            </div>
            <button type="button" class="btn-remove-doc" onclick="zmcRemoveDocument(${doc.id}, this, '${deleteUrl}')">
              <i class="ri-delete-bin-line"></i> Remove
            </button>
          `;
          container.appendChild(el);

          const fileInput = container.closest('.form-field')?.querySelector('input[type="file"]');
          if (fileInput) fileInput.removeAttribute('required');
        });
      }
    @endif

    document.getElementById('ap5SaveDraftBtn')?.addEventListener('click', () => {
      applyAp5DocRequirements();
      if (!ap5Type) {
        alert('Please select Renewal or Replacement first.');
        return;
      }
      saveDraftAp5();
    });

    document.getElementById('ap5ConfirmSubmitBtn')?.addEventListener('click', () => {
      submitAp5();
    });
  })();
</script>
@endpush
