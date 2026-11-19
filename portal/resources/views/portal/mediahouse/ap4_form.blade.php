@extends('layouts.portal')

@section('title', 'New Registration — AP4 Foreign Representative Office')

@section('content')
@unless($formBuilderPreview ?? false)
  @include('portal.partials.existing_documents')
@endunless
<div id="ap4-registration-page">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark m-0" style="font-size:18px;">
      New Registration — Foreign Representative Office (AP4) — Digital Form
    </h4>
    <a href="{{ route('mediahouse.new') }}" class="btn btn-outline-secondary btn-sm">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>

  <div class="form-container">
    <div class="form-header">
      <h1>AP4 — Application for Registration of a Representative Office of a Foreign Mass Media Service</h1>
      <p class="m-0">Complete this digital AP4 form. Ensure all details are accurate before submission.</p>
    </div>

    <div class="form-steps-container">
      <div class="step-progress">
        <div class="step-progress-bar">
          <div class="step active" data-step="1"><div class="step-number">1</div><div class="step-label">Contact Details</div></div>
          <div class="step" data-step="2"><div class="step-number">2</div><div class="step-label">Organisation</div></div>
          <div class="step" data-step="3"><div class="step-number">3</div><div class="step-label">Rep Office & Staff</div></div>
          <div class="step" data-step="4"><div class="step-number">4</div><div class="step-label">Uploads</div></div>
          <div class="step" data-step="5"><div class="step-number">5</div><div class="step-label">Declaration</div></div>
        </div>
      </div>

      <form id="ap4Form" onsubmit="return false;" enctype="multipart/form-data">
        <input type="hidden" name="form_type" value="AP4">
        <input type="hidden" name="registration_scope" value="foreign">

        {{-- STEP 1: Contact --}}
        <div class="step-content active" id="ap4-step-1">
          <h3 class="step-title">Contact Person Details</h3>
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

        {{-- STEP 2: Organisation --}}
        <div class="step-content" id="ap4-step-2">
          <h3 class="step-title">Foreign Organisation Details</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Provide the details of the foreign mass media organisation.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-global-line me-2"></i>Organisation Represented</div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Name of Media Organisation</label>
                <input type="text" class="form-control" name="org_name" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                @include('portal.partials.country_selector', [
                  'selectorId' => 'ap4_org_country',
                  'fieldName' => 'org_country',
                  'fieldLabel' => 'Country of Origin',
                  'isRequired' => true,
                  'currentValue' => ''
                ])
              </div>
              <div class="form-field">
                <label class="form-label required">Location of Head Office</label>
                <input type="text" class="form-control" name="org_head_office" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Mailing Address of Head Office</label>
                <textarea class="form-control" name="org_mailing_address" rows="2" required></textarea>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label required" style="font-weight:700; font-size:13px;">Owner(s)</label>
              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-2" style="font-size:13px;" id="ap4OwnersTable">
                  <thead style="background:#f8fafc;">
                    <tr>
                      <th style="min-width:200px;">Name of Owner</th>
                      <th style="min-width:200px;">Nationality</th>
                      <th style="width:60px;">Action</th>
                    </tr>
                  </thead>
                  <tbody id="ap4OwnersRows">
                  </tbody>
                </table>
              </div>
              <button type="button" class="btn btn-outline-secondary btn-sm" id="ap4AddOwnerBtn">
                <i class="ri-add-line me-1"></i> Add Owner
              </button>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Media House Type</label>
                <div class="d-flex gap-3 mt-2">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="foreign_media_type" value="Electronic" id="ap4_fmt_electronic" required>
                    <label class="form-check-label" for="ap4_fmt_electronic">Electronic</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="foreign_media_type" value="Print" id="ap4_fmt_print">
                    <label class="form-check-label" for="ap4_fmt_print">Print</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="foreign_media_type" value="Both" id="ap4_fmt_both">
                    <label class="form-check-label" for="ap4_fmt_both">Both</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- STEP 3: Rep Office & Staff --}}
        <div class="step-content" id="ap4-step-3">
          <h3 class="step-title">Representative Office & Staff in Zimbabwe</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Provide the details of the representative office and staff operating in Zimbabwe.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-hotel-line me-2"></i>Representative Office</div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Name of Representative Office</label>
                <input type="text" class="form-control" name="rep_office_name" required>
              </div>
              <div class="form-field">
                <label class="form-label required">Email Address</label>
                <input type="email" class="form-control" name="rep_office_email" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Physical Address in Zimbabwe</label>
                <textarea class="form-control" name="rep_office_address" rows="2" required></textarea>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">Nature of Activities in Zimbabwe</label>
                <textarea class="form-control" name="rep_office_activities" rows="3" required></textarea>
              </div>
            </div>
          </div>

          <div class="section-card mt-3">
            <div class="section-title"><i class="ri-team-line me-2"></i>Staff in Zimbabwe</div>
            <div class="table-responsive">
              <table class="table table-bordered align-middle" id="ap4StaffTable">
                <thead>
                  <tr>
                    <th>Full Name</th>
                    <th>Nationality</th>
                    <th>Position</th>
                    <th>Passport / ID No.</th>
                    <th>Work Permit No.</th>
                    <th style="width:70px;">Action</th>
                  </tr>
                </thead>
                <tbody id="ap4StaffRows"></tbody>
              </table>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="ap4AddStaff()">
              <i class="ri-add-line me-1"></i> Add Staff Member
            </button>
          </div>
        </div>

        @include('portal.partials.custom_form_fields', ['customFormType' => 'ap4', 'customFieldStep' => null])

        {{-- STEP 4: Uploads --}}
        <div class="step-content" id="ap4-step-4">
          <h3 class="step-title">Supporting Documents</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Upload the required supporting documents for the foreign representative office.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-attachment-2 me-2"></i>Required Documents</div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">1. Letter from Parent Organisation</label>
                <input type="file" class="form-control" name="documents[letter_from_parent_org]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="letter_from_parent_org"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">2. Passport Copies of Representative(s)</label>
                <input type="file" class="form-control" name="documents[passport_copies]" accept=".pdf,.jpg,.jpeg,.png,.zip">
                <div class="uploaded-files" data-doc-type="passport_copies"></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label">3. Work Permit(s) for Foreign Staff</label>
                <input type="file" class="form-control" name="documents[work_permits]" accept=".pdf,.jpg,.jpeg,.png,.zip">
                <div class="uploaded-files" data-doc-type="work_permits"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">4. Proof of Zimbabwe Address</label>
                <input type="file" class="form-control" name="documents[proof_of_zim_address]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="proof_of_zim_address"></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">5. Clearance Letter (Permission to operate a Representative Office in Zimbabwe)</label>
                <input type="file" class="form-control" name="documents[clearance_letter]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="clearance_letter"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">6. Certificate of Incorporation (Country of Domicile)</label>
                <input type="file" class="form-control" name="documents[certificate_of_incorporation]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="certificate_of_incorporation"></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">7. Certified Copy of National Identity for Each Director</label>
                <input type="file" class="form-control" name="documents[directors_ids]" accept=".pdf,.jpg,.jpeg,.png,.zip">
                <div class="uploaded-files" data-doc-type="directors_ids"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">8. Mission Statement</label>
                <input type="file" class="form-control" name="documents[mission_statement]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="mission_statement"></div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">9. In-house Code of Ethics</label>
                <input type="file" class="form-control" name="documents[code_of_ethics]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="code_of_ethics"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">10. In-house Code of Conduct for Employees</label>
                <input type="file" class="form-control" name="documents[code_of_conduct]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="code_of_conduct"></div>
                <small class="text-muted" style="font-size:11px;">If different from Code of Ethics</small>
              </div>
            </div>
            <div class="form-row">
              <div class="form-field">
                <label class="form-label required">11. In-house Style Book</label>
                <input type="file" class="form-control" name="documents[style_book]" accept=".pdf,.jpg,.jpeg,.png">
                <div class="uploaded-files" data-doc-type="style_book"></div>
              </div>
              <div class="form-field">
                <label class="form-label required">12. List of Names and Addresses of Journalists Employed in the Representative Office</label>
                <input type="file" class="form-control" name="documents[journalists_list]" accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.csv">
                <div class="uploaded-files" data-doc-type="journalists_list"></div>
              </div>
            </div>
          </div>
        </div>

        {{-- STEP 5: Declaration --}}
        <div class="step-content" id="ap4-step-5">
          <h3 class="step-title">Declaration</h3>
          <div class="current-step-info">
            <i class="ri-information-line me-2"></i>
            Review and confirm the declaration before submitting.
          </div>

          <div class="section-card">
            <div class="section-title"><i class="ri-checkbox-circle-line me-2"></i>Declaration</div>
            <div class="declaration-text p-3 border rounded bg-light mb-3" style="font-size:13px;">
              <p class="mb-0">I declare that all the information given above, to the best of my knowledge is true and complete.</p>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="declaration_agreed" id="ap4_declaration" value="1">
              <label class="form-check-label fw-bold" for="ap4_declaration">
                I confirm that the information provided is true and accurate.
              </label>
            </div>
          </div>
        </div>

        {{-- Navigation --}}
        <div class="form-navigation d-flex justify-content-between mt-4">
          <button type="button" class="btn btn-outline-secondary" id="ap4PrevBtn" style="display:none;" onclick="ap4Navigate(-1)">
            <i class="ri-arrow-left-line me-1"></i> Previous
          </button>
          <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-outline-dark" id="ap4SaveDraftBtn" onclick="ap4SaveDraft()">
              <i class="ri-save-line me-1"></i> Save Draft
            </button>
            <button type="button" class="btn btn-dark" id="ap4NextBtn" onclick="ap4Navigate(1)">
              Next <i class="ri-arrow-right-line ms-1"></i>
            </button>
            <button type="button" class="btn btn-success" id="ap4SubmitBtn" style="display:none;" onclick="ap4Submit()">
              <i class="ri-check-line me-1"></i> Submit Application
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
  const csrfToken = '{{ csrf_token() }}';
  let ap4CurrentStep = 1;
  const ap4TotalSteps = 5;
  let ap4StaffCount = 0;
  let ap4OwnerCount = 0;

  function ap4AddOwner() {
    ap4OwnerCount++;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><input type="text" class="form-control form-control-sm" name="owners[${ap4OwnerCount}][name]" placeholder="Full name" required></td>
      <td><input type="text" class="form-control form-control-sm" name="owners[${ap4OwnerCount}][nationality]" placeholder="e.g. South African" required></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="ri-delete-bin-line"></i></button></td>
    `;
    document.getElementById('ap4OwnersRows').appendChild(row);
  }

  function ap4AddStaff() {
    ap4StaffCount++;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td><input type="text" class="form-control form-control-sm" name="staff[${ap4StaffCount}][name]" required></td>
      <td><input type="text" class="form-control form-control-sm" name="staff[${ap4StaffCount}][nationality]" required></td>
      <td><input type="text" class="form-control form-control-sm" name="staff[${ap4StaffCount}][position]" required></td>
      <td><input type="text" class="form-control form-control-sm" name="staff[${ap4StaffCount}][passport_id]" required></td>
      <td><input type="text" class="form-control form-control-sm" name="staff[${ap4StaffCount}][work_permit]"></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="ri-delete-bin-line"></i></button></td>
    `;
    document.getElementById('ap4StaffRows').appendChild(row);
  }

  function ap4Navigate(dir) {
    const next = ap4CurrentStep + dir;
    if (next < 1 || next > ap4TotalSteps) return;

    document.getElementById(`ap4-step-${ap4CurrentStep}`).classList.remove('active');
    document.getElementById(`ap4-step-${next}`).classList.add('active');
    ap4CurrentStep = next;

    document.querySelectorAll('.step-progress-bar .step').forEach((s, i) => {
      s.classList.toggle('active', i + 1 <= ap4CurrentStep);
    });

    document.getElementById('ap4PrevBtn').style.display = ap4CurrentStep > 1 ? '' : 'none';
    document.getElementById('ap4NextBtn').style.display = ap4CurrentStep < ap4TotalSteps ? '' : 'none';
    document.getElementById('ap4SubmitBtn').style.display = ap4CurrentStep === ap4TotalSteps ? '' : 'none';

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function ap4GetFormData() {
    const form = document.getElementById('ap4Form');
    const fd = {};
    const inputs = form.querySelectorAll('input:not([type=file]):not([type=checkbox]), select, textarea');
    inputs.forEach(el => {
      if (el.name && !el.name.startsWith('staff')) {
        if (el.type === 'radio') {
          if (el.checked) fd[el.name] = el.value;
        } else {
          fd[el.name] = el.value;
        }
      }
    });

    const chk = form.querySelector('[name="declaration_agreed"]');
    fd.declaration_agreed = chk && chk.checked ? true : false;
    fd.form_type = 'AP4';
    fd.registration_scope = 'foreign';

    const owners = [];
    document.querySelectorAll('#ap4OwnersRows tr').forEach(tr => {
      const cells = tr.querySelectorAll('input');
      if (cells.length >= 2) {
        const name = cells[0].value.trim();
        const nationality = cells[1].value.trim();
        if (name) owners.push({ name, nationality });
      }
    });
    fd.org_owners = owners;

    const staff = [];
    document.querySelectorAll('#ap4StaffRows tr').forEach(tr => {
      const cells = tr.querySelectorAll('input');
      if (cells.length >= 4) {
        staff.push({
          name: cells[0].value, nationality: cells[1].value,
          position: cells[2].value, passport_id: cells[3].value,
          work_permit: cells[4]?.value || ''
        });
      }
    });
    fd.staff_members = staff;
    return fd;
  }

  // Skip files already uploaded in a previous successful save.
  const ap4SavedFileSigs = new Set();
  function ap4FileSig(input, file) {
    return input.name + '|' + file.name + '|' + file.size + '|' + file.lastModified;
  }

  async function ap4SaveDraft() {
    const data = ap4GetFormData();
    const region = document.querySelector('select[name="collection_region"]')?.value || 'harare';
    const btn = document.getElementById('ap4SaveDraftBtn');
    try {
      btn.disabled = true;
      btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving...';
      const fd = new FormData();
      fd.append('collection_region', region);
      fd.append('form_data', JSON.stringify(data));
      const pendingAp4FileSigs = [];
      document.querySelectorAll('#ap4Form input[type="file"]').forEach(input => {
        if (input.files && input.files[0]) {
          const sig = ap4FileSig(input, input.files[0]);
          if (ap4SavedFileSigs.has(sig)) return;
          fd.append(input.name, input.files[0]);
          pendingAp4FileSigs.push(sig);
        }
      });
      const res = await fetch('{{ route("mediahouse.saveDraft") }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd
      });
      const json = await res.json();
      if (json.success) pendingAp4FileSigs.forEach(sig => ap4SavedFileSigs.add(sig));
      alert(json.success ? 'Draft saved successfully.' : (json.message || 'Failed.'));
    } catch(e) { alert('Error: ' + e.message); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="ri-save-line"></i> Save Draft'; }
  }

  async function ap4Submit() {
    const data = ap4GetFormData();
    if (!data.declaration_agreed) { alert('Please agree to the declaration.'); return; }
    if (!data.staff_members || data.staff_members.length === 0) { alert('Please add at least one staff member.'); return; }
    const region = document.querySelector('select[name="collection_region"]')?.value || '';
    if (!region) { alert('Please select a collection office.'); return; }

    const btn = document.getElementById('ap4SubmitBtn');
    try {
      btn.disabled = true;
      btn.innerHTML = '<i class="ri-loader-4-line"></i> Submitting...';
      const fd = new FormData();
      fd.append('collection_region', region);
      fd.append('form_data', JSON.stringify(data));
      document.querySelectorAll('#ap4Form input[type="file"]').forEach(input => {
        if (input.files && input.files[0]) fd.append(input.name, input.files[0]);
      });
      const res = await fetch('{{ route("mediahouse.submit") }}', {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd
      });
      const json = await res.json();
      if (json.success) {
        alert('AP4 Application submitted successfully! Reference: ' + json.reference);
        window.location.href = '{{ route("mediahouse.portal") }}';
      } else {
        alert(json.message || 'Submission failed.');
      }
    } catch(e) { alert('Error: ' + e.message); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="ri-check-line"></i> Submit Application'; }
  }

  document.addEventListener('DOMContentLoaded', function() {
    ap4AddOwner();
    ap4AddStaff();
    document.getElementById('ap4AddOwnerBtn').addEventListener('click', ap4AddOwner);
    zmcInitCountrySelector('ap4_org_country_wrapper');

    @if($draft && $draft->form_data)
    const draftData = @json($draft->form_data);
    if (draftData) {
      Object.entries(draftData).forEach(([key, val]) => {
        if (key === 'staff_members' || key === 'declaration_agreed' || key === 'org_owners') return;
        const el = document.querySelector(`[name="${key}"]`);
        if (el) {
          if (el.type === 'radio') {
            const radio = document.querySelector(`[name="${key}"][value="${val}"]`);
            if (radio) radio.checked = true;
          } else {
            el.value = val || '';
          }
        }
      });
      if (Array.isArray(draftData.org_owners) && draftData.org_owners.length > 0) {
        document.getElementById('ap4OwnersRows').innerHTML = '';
        ap4OwnerCount = 0;
        draftData.org_owners.forEach(o => {
          ap4OwnerCount++;
          const row = document.createElement('tr');
          row.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" name="owners[${ap4OwnerCount}][name]" value="${(o.name||'').replace(/"/g,'&quot;')}" placeholder="Full name" required></td>
            <td><input type="text" class="form-control form-control-sm" name="owners[${ap4OwnerCount}][nationality]" value="${(o.nationality||'').replace(/"/g,'&quot;')}" placeholder="e.g. South African" required></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="ri-delete-bin-line"></i></button></td>
          `;
          document.getElementById('ap4OwnersRows').appendChild(row);
        });
      }
      if (Array.isArray(draftData.staff_members)) {
        document.getElementById('ap4StaffRows').innerHTML = '';
        ap4StaffCount = 0;
        draftData.staff_members.forEach(s => {
          ap4StaffCount++;
          const row = document.createElement('tr');
          const fields = [
            {name: `staff[${ap4StaffCount}][name]`, val: s.name||'', req: true},
            {name: `staff[${ap4StaffCount}][nationality]`, val: s.nationality||'', req: true},
            {name: `staff[${ap4StaffCount}][position]`, val: s.position||'', req: true},
            {name: `staff[${ap4StaffCount}][passport_id]`, val: s.passport_id||'', req: true},
            {name: `staff[${ap4StaffCount}][work_permit]`, val: s.work_permit||'', req: false}
          ];
          fields.forEach(f => {
            const td = document.createElement('td');
            const inp = document.createElement('input');
            inp.type = 'text'; inp.className = 'form-control form-control-sm';
            inp.name = f.name; inp.value = f.val; if (f.req) inp.required = true;
            td.appendChild(inp);
            row.appendChild(td);
          });
          const actionTd = document.createElement('td');
          const delBtn = document.createElement('button');
          delBtn.type = 'button'; delBtn.className = 'btn btn-sm btn-outline-danger';
          delBtn.innerHTML = '<i class="ri-delete-bin-line"></i>';
          delBtn.onclick = function() { this.closest('tr').remove(); };
          actionTd.appendChild(delBtn);
          row.appendChild(actionTd);
          document.getElementById('ap4StaffRows').appendChild(row);
        });
      }
      zmcSyncCountrySelector('ap4_org_country_wrapper');

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
