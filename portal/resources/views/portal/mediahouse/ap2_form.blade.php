@extends('layouts.portal')

@section('title', 'New Registration — AP2 News Agency')

@section('content')
@unless($formBuilderPreview ?? false)
  @include('portal.partials.existing_documents')
@endunless
<div id="ap2-registration-page">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark m-0" style="font-size:18px;">
      New Registration — News Agency (AP2) — Digital Form
    </h4>
    <a href="{{ route('mediahouse.new') }}" class="btn btn-outline-secondary btn-sm">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>

  <div class="form-container">
    <div class="form-header">
      <h1>AP2 — Application for Registration of a News Agency</h1>
      <p class="m-0">Complete this digital AP2 form. Ensure all details are accurate before submission.</p>
    </div>

    <div class="form-steps-container">
      <div class="step-progress">
        <div class="step-progress-bar">
          <div class="step active" data-step="1"><div class="step-number">1</div><div class="step-label">Contact Details</div></div>
          <div class="step" data-step="2"><div class="step-number">2</div><div class="step-label">Agency Details</div></div>
          <div class="step" data-step="3"><div class="step-number">3</div><div class="step-label">Directors</div></div>
          <div class="step" data-step="4"><div class="step-number">4</div><div class="step-label">Uploads</div></div>
          <div class="step" data-step="5"><div class="step-number">5</div><div class="step-label">Declaration</div></div>
        </div>
      </div>

      <form id="ap2Form" onsubmit="return false;" enctype="multipart/form-data">
        <input type="hidden" name="form_type" value="AP2">

        {{-- STEP 1: Contact --}}
        <div class="step-content active" id="ap2-step-1">
          <h2 class="step-title">Contact Person Details</h2>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Provide the contact person details and collection office preference.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-user-3-line me-2"></i>Contact Person</div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Name</label>
                <input type="text" class="form-control" name="contact_name" required>
              </div>
              <div class="form-field">
                <label class="form-label required">Surname</label>
                <input type="text" class="form-control" name="contact_surname" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Physical Address</label>
                <textarea class="form-control" name="contact_address" rows="2" required></textarea>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Phone No.</label>
                <div class="d-flex gap-2">
                  <select class="form-control zmc-phone-code-select" name="contact_phone_country_code" style="max-width:200px;" required>
                    <option value="">Dial Code</option>
                  </select>
                  <input type="text" class="form-control" name="contact_phone" placeholder="e.g. 771234567" required>
                </div>
              </div>
              <div class="form-field">
                <label class="form-label required">Email Address</label>
                <input type="email" class="form-control" name="contact_email" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Collection Office</label>
                <select class="form-control" name="collection_region" required>
                  <option value="">Select collection office</option>
                  <option value="harare">Harare Head Office - ZMC House, {{ config('organization.registered_address') }}</option>
                  <option value="bulawayo">Bulawayo Regional Office - 17 Rylanders Court, Windsor Park Complex, 16th Avenue, Famona, Bulawayo</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        {{-- STEP 2: Agency Details --}}
        <div class="step-content" id="ap2-step-2">
          <h3 class="step-title">News Agency Details</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Provide the details of the news agency to be registered.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-newspaper-line me-2"></i>Agency Information</div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Name of News Agency</label>
                <input type="text" class="form-control" name="agency_name" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Postal Address</label>
                <textarea class="form-control" name="agency_postal_address" rows="2" required></textarea>
              </div>
              <div class="form-field">
                <label class="form-label required">Physical Address</label>
                <textarea class="form-control" name="agency_physical_address" rows="2" required></textarea>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Telephone</label>
                <input type="text" class="form-control" name="agency_telephone" required>
              </div>
              <div class="form-field">
                <label class="form-label">Fax</label>
                <input type="text" class="form-control" name="agency_fax">
              </div>
              <div class="form-field">
                <label class="form-label required">Email Address</label>
                <input type="email" class="form-control" name="agency_email" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label">Website</label>
                <input type="url" class="form-control" name="agency_website" placeholder="https://">
              </div>
            </div>
          </div>

          <div class="section-card mt-3">
            <div class="section-title"><i class="ri-briefcase-4-line me-2"></i>Ownership & Operations</div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Name(s) of Owner(s)</label>
                <textarea class="form-control" name="agency_owners" rows="2" required placeholder="List each owner, one per line"></textarea>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                @include('portal.partials.country_selector', [
                  'selectorId' => 'ap2_owners_nationality',
                  'fieldName' => 'agency_owners_nationality',
                  'fieldLabel' => 'Nationality of Owner(s)',
                  'isRequired' => true,
                  'currentValue' => ''
                ])
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Nature of News Gathering & Distribution Activities</label>
                <textarea class="form-control" name="agency_activities" rows="3" required></textarea>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Media Houses Served / Clients</label>
                <textarea class="form-control" name="agency_clients" rows="2" required placeholder="List media houses served"></textarea>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Number of Staff</label>
                <input type="number" class="form-control" name="agency_staff_count" min="1" required>
              </div>
              <div class="form-field">
                <label class="form-label required">Date Established</label>
                <input type="date" class="form-control" name="agency_date_established" required>
              </div>
            </div>
          </div>
        </div>

        {{-- STEP 3: Directors --}}
        <div class="step-content" id="ap2-step-3">
          <h3 class="step-title">Directors / Proprietors</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Add all directors or proprietors of the news agency.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-team-line me-2"></i>Directors / Proprietors</div>
            <div class="table-responsive">
              <table class="table table-bordered align-middle" id="ap2DirectorsTable">
                <thead>
                  <tr>
                    <th>Full Name</th>
                    <th>Nationality</th>
                    <th>ID / Passport No.</th>
                    <th>Physical Address</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Gender</th>
                    <th style="width:70px;">Action</th>
                  </tr>
                </thead>
                <tbody id="ap2DirectorsRows"></tbody>
              </table>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="ap2AddDirector()">
              <i class="ri-add-line me-1"></i> Add Director
            </button>
          </div>
        </div>

        @include('portal.partials.custom_form_fields', ['customFormType' => 'ap2', 'customFieldStep' => null])

        {{-- STEP 4: Uploads --}}
        <div class="step-content" id="ap2-step-4">
          <h3 class="step-title">Supporting Documents</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Upload the required supporting documents.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-attachment-2 me-2"></i>Required Documents</div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Certificate of Incorporation</label>
                <input type="file" class="form-control" name="documents[certificate_of_incorporation]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="certificate_of_incorporation"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">Memorandum of Association</label>
                <input type="file" class="form-control" name="documents[memorandum_of_association]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="memorandum_of_association"></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">CR14 Form</label>
                <input type="file" class="form-control" name="documents[cr14_form]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="cr14_form"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">Directors' ID Copies</label>
                <input type="file" class="form-control" name="documents[directors_ids]" accept=".pdf,.jpg,.jpeg,.png,.zip">
                <div class="uploaded-files" data-doc-type="directors_ids"></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label">Editorial Charter</label>
                <input type="file" class="form-control" name="documents[editorial_charter]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="editorial_charter"></div>
              </div>
              <div class="form-field">
                <label class="form-label">Code of Ethics</label>
                <input type="file" class="form-control" name="documents[code_of_ethics]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="code_of_ethics"></div>
              </div>
            </div>
          </div>
        </div>

        {{-- STEP 5: Declaration --}}
        <div class="step-content" id="ap2-step-5">
          <h3 class="step-title">Declaration</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Review and confirm the declaration before submitting.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-checkbox-circle-line me-2"></i>Declaration</div>
            <div class="declaration-text p-3 border rounded bg-light mb-3" style="font-size:13px; max-height:200px; overflow-y:auto;">
              <p>I/We, the undersigned, do hereby declare that the particulars given in this application are true and correct. I/We understand that any false or misleading information may render this application null and void and may result in prosecution under the relevant laws of Zimbabwe.</p>
              <p>I/We further declare that the news agency described herein will operate in compliance with all applicable laws, regulations, and the standards set by the Zimbabwe Media Commission.</p>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="declaration_agreed" id="ap2_declaration" value="1">
              <label class="form-check-label fw-bold" for="ap2_declaration">
                I confirm that the information provided is true and accurate.
              </label>
            </div>
          </div>
        </div>

        {{-- Navigation --}}
        <div class="form-navigation d-flex justify-content-between mt-4">
          <button type="button" class="btn btn-outline-secondary" id="ap2PrevBtn" style="display:none;" onclick="ap2Navigate(-1)">
            <i class="ri-arrow-left-line me-1"></i> Previous
          </button>
          <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-outline-dark" id="ap2SaveDraftBtn" onclick="ap2SaveDraft()">
              <i class="ri-save-line me-1"></i> Save Draft
            </button>
            <button type="button" class="btn btn-dark" id="ap2NextBtn" onclick="ap2Navigate(1)">
              Next <i class="ri-arrow-right-line ms-1"></i>
            </button>
            <button type="button" class="btn btn-success" id="ap2SubmitBtn" style="display:none;" onclick="ap2Submit()">
              <i class="ri-check-line me-1"></i> Submit Application
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@include('portal.partials.nid_validator')

@push('scripts')
<script>
  const csrfToken = '{{ csrf_token() }}';
  let ap2CurrentStep = 1;
  const ap2TotalSteps = 5;
  let ap2DirectorCount = 0;

  function ap2AddDirector() {
    ap2DirectorCount++;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><input type="text" class="form-control form-control-sm" name="directors[${ap2DirectorCount}][name]" required></td>
      <td><input type="text" class="form-control form-control-sm" name="directors[${ap2DirectorCount}][nationality]" required></td>
      <td><input type="text" class="form-control form-control-sm zmc-nid-input" name="directors[${ap2DirectorCount}][id_number]" required placeholder="XX-XXXXXXXXXXX"></td>
      <td><input type="text" class="form-control form-control-sm" name="directors[${ap2DirectorCount}][address]" required></td>
      <td><input type="email" class="form-control form-control-sm" name="directors[${ap2DirectorCount}][email]" required></td>
      <td><input type="tel" class="form-control form-control-sm" name="directors[${ap2DirectorCount}][phone_number]" required></td>
      <td><select class="form-control form-control-sm" name="directors[${ap2DirectorCount}][gender]" required><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option></select></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="ri-delete-bin-line"></i></button></td>
    `;
    document.getElementById('ap2DirectorsRows').appendChild(row);
    if (window.zmcAttachNIDValidation) zmcAttachNIDValidation('.zmc-nid-input:last-of-type');
  }

  function ap2Navigate(dir) {
    const next = ap2CurrentStep + dir;
    if (next < 1 || next > ap2TotalSteps) return;

    document.getElementById(`ap2-step-${ap2CurrentStep}`).classList.remove('active');
    document.getElementById(`ap2-step-${next}`).classList.add('active');
    ap2CurrentStep = next;

    document.querySelectorAll('.step-progress-bar .step').forEach((s, i) => {
      s.classList.toggle('active', i + 1 <= ap2CurrentStep);
    });

    document.getElementById('ap2PrevBtn').style.display = ap2CurrentStep > 1 ? '' : 'none';
    document.getElementById('ap2NextBtn').style.display = ap2CurrentStep < ap2TotalSteps ? '' : 'none';
    document.getElementById('ap2SubmitBtn').style.display = ap2CurrentStep === ap2TotalSteps ? '' : 'none';

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function ap2GetFormData() {
    const form = document.getElementById('ap2Form');
    const fd = {};
    const inputs = form.querySelectorAll('input:not([type=file]):not([type=checkbox]), select, textarea');
    inputs.forEach(el => {
      if (el.name && !el.name.startsWith('directors')) fd[el.name] = el.value;
    });

    const chk = form.querySelector('[name="declaration_agreed"]');
    fd.declaration_agreed = chk && chk.checked ? true : false;
    fd.registration_scope = 'local';
    fd.form_type = 'AP2';

    const directors = [];
    document.querySelectorAll('#ap2DirectorsRows tr').forEach(tr => {
      const inputs = tr.querySelectorAll('input, select');
      if (inputs.length >= 7) {
        directors.push({
          name: inputs[0].value, nationality: inputs[1].value,
          id_number: inputs[2].value, address: inputs[3].value,
          email: inputs[4].value, phone_number: inputs[5].value,
          gender: inputs[6].value
        });
      }
    });
    fd.directors = directors;
    return fd;
  }

  // Skip files already uploaded in a previous successful save.
  const ap2SavedFileSigs = new Set();
  function ap2FileSig(input, file) {
    return input.name + '|' + file.name + '|' + file.size + '|' + file.lastModified;
  }

  async function ap2SaveDraft() {
    const data = ap2GetFormData();
    const region = document.querySelector('select[name="collection_region"]')?.value || 'harare';
    const btn = document.getElementById('ap2SaveDraftBtn');
    try {
      btn.disabled = true;
      btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving...';
      const fd = new FormData();
      fd.append('collection_region', region);
      fd.append('form_data', JSON.stringify(data));
      const pendingAp2FileSigs = [];
      document.querySelectorAll('#ap2Form input[type="file"]').forEach(input => {
        if (input.files && input.files[0]) {
          const sig = ap2FileSig(input, input.files[0]);
          if (ap2SavedFileSigs.has(sig)) return;
          fd.append(input.name, input.files[0]);
          pendingAp2FileSigs.push(sig);
        }
      });
      const res = await fetch('{{ route("mediahouse.saveDraft") }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd
      });
      const json = await res.json();
      if (json.success) pendingAp2FileSigs.forEach(sig => ap2SavedFileSigs.add(sig));
      alert(json.success ? 'Draft saved successfully.' : (json.message || 'Failed.'));
    } catch(e) { alert('Error: ' + e.message); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i> Save Draft'; }
  }

  async function ap2Submit() {
    const data = ap2GetFormData();
    if (!data.declaration_agreed) { alert('Please agree to the declaration.'); return; }
    if (!data.directors || data.directors.length === 0) { alert('Please add at least one director.'); return; }
    const region = document.querySelector('select[name="collection_region"]')?.value || '';
    if (!region) { alert('Please select a collection office.'); return; }

    const btn = document.getElementById('ap2SubmitBtn');
    try {
      btn.disabled = true;
      btn.innerHTML = '<i class="ri-loader-4-line"></i> Submitting...';
      const fd = new FormData();
      fd.append('collection_region', region);
      fd.append('form_data', JSON.stringify(data));
      document.querySelectorAll('#ap2Form input[type="file"]').forEach(input => {
        if (input.files && input.files[0]) fd.append(input.name, input.files[0]);
      });
      const res = await fetch('{{ route("mediahouse.submit") }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd
      });
      const json = await res.json();
      if (json.success) {
        alert('AP2 Application submitted successfully! Reference: ' + json.reference);
        window.location.href = '{{ route("mediahouse.portal") }}';
      } else {
        alert(json.message || 'Submission failed.');
      }
    } catch(e) { alert('Error: ' + e.message); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="ri-check-line"></i> Submit Application'; }
  }

  document.addEventListener('DOMContentLoaded', function() {
    ap2AddDirector();
    zmcInitCountrySelector('ap2_owners_nationality_wrapper');

    @if($draft && $draft->form_data)
    const draftData = @json($draft->form_data);
    if (draftData) {
      Object.entries(draftData).forEach(([key, val]) => {
        if (key === 'directors' || key === 'declaration_agreed') return;
        const el = document.querySelector(`[name="${key}"]`);
        if (el) el.value = val || '';
      });
      if (Array.isArray(draftData.directors)) {
        document.getElementById('ap2DirectorsRows').innerHTML = '';
        ap2DirectorCount = 0;
        draftData.directors.forEach(d => {
          ap2DirectorCount++;
          const row = document.createElement('tr');
          const fields = [
            {name: `directors[${ap2DirectorCount}][name]`, val: d.name||'', cls: ''},
            {name: `directors[${ap2DirectorCount}][nationality]`, val: d.nationality||'', cls: ''},
            {name: `directors[${ap2DirectorCount}][id_number]`, val: d.id_number||'', cls: 'zmc-nid-input', ph: 'XX-XXXXXXXXXXX'},
            {name: `directors[${ap2DirectorCount}][address]`, val: d.address||'', cls: ''},
            {name: `directors[${ap2DirectorCount}][email]`, val: d.email||'', cls: '', type: 'email'},
            {name: `directors[${ap2DirectorCount}][phone_number]`, val: d.phone_number||'', cls: '', type: 'tel'},
            {name: `directors[${ap2DirectorCount}][gender]`, val: d.gender||'', cls: '', type: 'select', options: ['', 'Male', 'Female', 'Other']}
          ];
          fields.forEach(f => {
            const td = document.createElement('td');
            let element;
            if (f.type === 'select') {
              element = document.createElement('select');
              element.className = 'form-control form-control-sm' + (f.cls ? ' ' + f.cls : '');
              f.options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.text = opt || 'Select';
                element.appendChild(option);
              });
              element.value = f.val;
            } else {
              element = document.createElement('input');
              element.type = f.type || 'text';
              element.className = 'form-control form-control-sm' + (f.cls ? ' ' + f.cls : '');
              element.value = f.val;
              if (f.ph) element.placeholder = f.ph;
            }
            element.name = f.name;
            element.required = true;
            td.appendChild(element);
            row.appendChild(td);
          });
          const actionTd = document.createElement('td');
          const delBtn = document.createElement('button');
          delBtn.type = 'button'; delBtn.className = 'btn btn-sm btn-outline-danger';
          delBtn.innerHTML = '<i class="ri-delete-bin-line"></i>';
          delBtn.onclick = function() { this.closest('tr').remove(); };
          actionTd.appendChild(delBtn);
          row.appendChild(actionTd);
          document.getElementById('ap2DirectorsRows').appendChild(row);
        });
      }
      zmcSyncCountrySelector('ap2_owners_nationality_wrapper');
      if (window.zmcAttachNIDValidation) {
        document.querySelectorAll('.zmc-nid-input').forEach(inp => {
          if (!inp.dataset.nidBound) {
            inp.dataset.nidBound = '1';
            const feedback = document.createElement('div');
            feedback.style.cssText = 'font-size:11px; margin-top:2px;';
            inp.parentElement.appendChild(feedback);
            inp.addEventListener('blur', function() {
              if (!this.value.trim()) { feedback.textContent = ''; feedback.className = ''; return; }
              const result = window.zmcValidateNID(this.value);
              if (result.valid) {
                this.value = result.formatted;
                feedback.textContent = result.message; feedback.className = 'text-success';
                this.classList.remove('is-invalid'); this.classList.add('is-valid');
              } else {
                feedback.textContent = result.message; feedback.className = 'text-danger';
                this.classList.remove('is-valid'); this.classList.add('is-invalid');
              }
            });
          }
        });
      }

      // Show existing uploaded documents with remove buttons
      const existingDocs = @json($draft->documents ?? []);
      const deleteBaseUrl = @json(route('mediahouse.documents.delete', ['document' => '__ID__']));
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
        });
      }
    }
    @endif
  });
</script>
@endpush
@endsection
