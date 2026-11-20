@extends('layouts.portal')
@section('title', $pageTitle ?? 'Production')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">
        {{ $pageTitle ?? 'Production' }}
      </h1>
      @if(!empty($pageNote))
        <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
          <i class="ri-information-line me-1"></i> {!! $pageNote !!}
        </div>
      @endif
    </div>

    @php
      $myRegionNames = auth()->user()->assignedRegions()->pluck('name')->filter()->values();
    @endphp
    <div class="d-flex align-items-center gap-2">
      <span class="zmc-pill zmc-pill-dark">
        <i class="ri-map-pin-user-line"></i>
        <span>Region: {{ $myRegionNames->isNotEmpty() ? $myRegionNames->join(', ') : 'NOT SET' }}</span>
      </span>
      <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Refresh">
        <i class="ri-refresh-line me-1"></i> Refresh
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @if(($mode ?? '') === 'printing')
    <form method="GET" action="{{ url()->current() }}" class="zmc-card mb-3">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label small fw-bold">Search</label>
          <input class="form-control form-control-sm" name="q" value="{{ request('q') }}" placeholder="Reference or applicant">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold">Application type</label>
          <select class="form-select form-select-sm" name="application_type">
            <option value="">All</option>
            <option value="accreditation" @selected(request('application_type') === 'accreditation')>Accreditation</option>
            <option value="registration" @selected(request('application_type') === 'registration')>Registration</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold">Collection region</label>
          <select class="form-select form-select-sm" name="collection_region">
            <option value="">All assigned regions</option>
            @foreach(['harare'=>'Harare','bulawayo'=>'Bulawayo','mutare'=>'Mutare','masvingo'=>'Masvingo'] as $value => $label)
              <option value="{{ $value }}" @selected(request('collection_region') === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold">Category</label>
          <select class="form-select form-select-sm" name="category">
            <option value="">All categories</option>
            @foreach(['A','B','C','D','E'] as $category)
              <option value="{{ $category }}" @selected(request('category') === $category)>Category {{ $category }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold">Date from</label>
          <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small fw-bold">Date to</label>
          <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-12 col-md-1 d-flex gap-2">
          <button class="btn btn-sm btn-dark" type="submit" aria-label="Apply filters"><i class="ri-filter-line"></i></button>
          <a class="btn btn-sm btn-outline-secondary" href="{{ url()->current() }}" aria-label="Clear filters"><i class="ri-close-line"></i></a>
        </div>
      </div>
    </form>
  @endif

  @php
    $detailsUrlTemplate = route('staff.applications.details', ['application' => '__ID__']);
  @endphp

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h2 class="fw-bold m-0">
        <i class="ri-list-check-2 me-2" style="color:var(--zmc-accent)"></i>
        {{ $pageTitle ?? 'Items' }}
      </h2>

      @if(($mode ?? '') === 'printing')
        <form id="batchForm" method="POST" action="{{ route('staff.production.batch.print') }}" class="d-flex align-items-center gap-2">
          @csrf
          <button type="submit" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="tooltip" title="Marks selected items as printed">
            <i class="ri-printer-line me-1"></i> Batch Print (selected)
          </button>
        </form>
      @endif
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        <thead>
          <tr class="small text-muted text-uppercase bg-light">
            @if(($mode ?? '') === 'printing')
              <th style="width:40px;"></th>
            @endif
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
            <th class="text-end" style="min-width:220px;">Actions</th>
          </tr>
        </thead>

        <tbody>
        @forelse($applications as $i => $app)
          @php
            $status = strtolower((string)($app->status ?? ''));
            $rowNo = method_exists($applications,'firstItem') && $applications->firstItem()
              ? ($applications->firstItem() + $i)
              : ($i + 1);
            $ref = $app->reference ?? ('APP-' . str_pad((int)$app->id, 5, '0', STR_PAD_LEFT));
            $isAccred = ($app->application_type ?? '') === 'accreditation';
            $isReg    = ($app->application_type ?? '') === 'registration';
            $canBatch = in_array($status, ['production_queue','card_generated','certificate_generated'], true);
          @endphp

          <tr>
            @if(($mode ?? '') === 'printing')
              <td>
                <input class="form-check-input" type="checkbox" name="application_ids[]" value="{{ $app->id }}" form="batchForm" @if(!$canBatch) disabled @endif>
              </td>
            @endif
            @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
            <td class="text-end">
              <div class="zmc-action-strip">

                {{-- View details (modal) --}}
                <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-primary js-view-more" data-app-id="{{ $app->id }}" data-bs-toggle="tooltip" title="View application">
                  <i class="fa-regular fa-eye"></i>
                </button>

                {{-- Card preview --}}
                @if($isAccred)
                  <a href="{{ route('staff.production.applications.card.preview', $app) }}" target="_blank" class="btn btn-sm zmc-icon-btn btn-outline-success" data-bs-toggle="tooltip" title="Preview / edit card (then print)">
                    <i class="ri-id-card-line"></i>
                  </a>
                @else
                  <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-success" disabled data-bs-toggle="tooltip" title="Card applies to accreditation only">
                    <i class="ri-id-card-line"></i>
                  </button>
                @endif

                {{-- Certificate preview --}}
                @if($isReg)
                  <a href="{{ route('staff.production.applications.certificate.preview', $app) }}" target="_blank" class="btn btn-sm zmc-icon-btn btn-outline-success" data-bs-toggle="tooltip" title="Preview / edit certificate (then print)">
                    <i class="ri-award-line"></i>
                  </a>
                @else
                  <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-success" disabled data-bs-toggle="tooltip" title="Certificate applies to registration only">
                    <i class="ri-award-line"></i>
                  </button>
                @endif

                {{-- Mark Printed (single) --}}
                <form class="d-inline" method="POST" action="{{ route('staff.production.applications.print_single', $app) }}">
                  @csrf
                  <button type="submit" class="btn btn-sm zmc-icon-btn btn-outline-dark" data-bs-toggle="tooltip" title="Record that the item has been printed" @if(in_array($status, ['issued'], true)) disabled @endif>
                    <i class="ri-printer-line"></i>
                  </button>
                </form>

                {{-- Mark Issued --}}
                <form class="d-inline" method="POST" action="{{ route('staff.production.applications.issue', $app) }}">
                  @csrf
                  <button type="submit" class="btn btn-sm zmc-icon-btn btn-outline-success" data-bs-toggle="tooltip" title="Mark issued" @if($status === 'issued') disabled @endif>
                    <i class="ri-checkbox-circle-line"></i>
                  </button>
                </form>

              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="{{ ($mode ?? '') === 'printing' ? 9 : 8 }}" class="text-center py-5 text-muted">No applications found.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $applications->links() }}
  </div>
</div>

{{-- Global Details Modal (View) --}}
<div class="modal fade zmc-modal-pop" id="appDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header zmc-modal-header">
        <div>
          <div class="zmc-modal-title">
            <i class="fa-regular fa-file-lines me-2" style="color:var(--zmc-accent-dark)"></i>
            Application Review
          </div>
          <div class="zmc-modal-sub" id="mdl_meta">—</div>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div id="mdl_loading" class="d-none text-center py-5">
          <div class="spinner-border" style="color:var(--zmc-accent-dark)"></div>
          <div class="text-muted mt-2" style="font-size: var(--font-size-sm);">Loading…</div>
        </div>

        <div id="mdl_error" class="alert alert-danger d-none"></div>

        <div id="mdl_content_area" class="d-none">
          {{-- filled by JS --}}
        </div>
      </div>

      <div class="modal-footer zmc-modal-footer">
        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  const ZMC_DETAILS_URL = @json($detailsUrlTemplate);

  function zmcFmt(v){
    if(v === null || v === undefined) return '—';
    const s = String(v).trim();
    return s === '' ? '—' : s;
  }

  function zmcOpenModal(selector){
    const el = document.querySelector(selector);
    if(!el) return;
    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
      bootstrap.Modal.getOrCreateInstance(el).show();
      return;
    }
  }

  function zmcEsc(v){
    if(v === null || v === undefined) return '—';
    const s = String(v);
    if(s === '') return '—';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function zmcHumanize(key){
    return String(key).replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  function zmcRenderValue(v){
    if(v === null || v === undefined || v === '') return '<span class="text-muted">—</span>';
    if(typeof v === 'boolean') return v ? 'Yes' : 'No';
    if(Array.isArray(v)){
      if(v.length === 0) return '<span class="text-muted">—</span>';
      const allScalar = v.every(x => x === null || ['string','number','boolean'].includes(typeof x));
      if(allScalar) return v.map(zmcEsc).join(', ');
      return '<pre class="small mb-0" style="white-space:pre-wrap;">' + zmcEsc(JSON.stringify(v, null, 2)) + '</pre>';
    }
    if(typeof v === 'object'){
      let out = '';
      for(const k in v){
        if(!Object.prototype.hasOwnProperty.call(v,k)) continue;
        out += '<div><span class="fw-semibold">' + zmcEsc(zmcHumanize(k)) + ':</span> ' + zmcEsc(v[k] == null ? '' : (typeof v[k] === 'object' ? JSON.stringify(v[k]) : v[k])) + '</div>';
      }
      return out || '<span class="text-muted">—</span>';
    }
    return zmcEsc(v);
  }

  function zmcBlock(titleHtml, bodyHtml){

    return `
      <div class="zmc-mdl-block">
        <div class="zmc-mdl-title">${titleHtml}</div>
        ${bodyHtml}
      </div>
    `;
  }

  function zmcInput(label, value, col = 4){
    return `
      <div class="col-12 col-md-${col}">
        <label class="form-label zmc-lbl">${label}</label>
        <input type="text" class="form-control zmc-input" value="${zmcFmt(value)}" readonly>
      </div>
    `;
  }

  function zmcTextarea(label, value, col = 12){
    return `
      <div class="col-12 col-md-${col}">
        <label class="form-label zmc-lbl">${label}</label>
        <textarea class="form-control zmc-input" rows="3" readonly>${zmcFmt(value)}</textarea>
      </div>
    `;
  }

  async function loadApplicationDetails(appId) {
    const loader = document.getElementById('mdl_loading');
    const area   = document.getElementById('mdl_content_area');
    const meta   = document.getElementById('mdl_meta');
    const errBox = document.getElementById('mdl_error');

    if (errBox){ errBox.classList.add('d-none'); errBox.textContent = ''; }
    if (loader) loader.classList.remove('d-none');
    if (area){ area.classList.add('d-none'); area.innerHTML = ''; }
    if (meta) meta.textContent = '—';

    try {
      const url = ZMC_DETAILS_URL.replace('__ID__', appId);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || data.ok === false) throw new Error(data.message || 'Failed to load details');

      const app = data.application || {};
      const formCode = String(app.form_code || '').toUpperCase();
      const ref = app.reference || ('APP-' + (app.id || appId));
      const status = app.status || '—';

      if (meta) {
        meta.innerHTML = `
          <span class="badge bg-light text-dark border me-2">${zmcFmt(formCode || 'Form')}</span>
          Ref: <span class="fw-bold">${zmcFmt(ref)}</span> •
          Status: <span class="fw-bold" style="color:var(--zmc-accent-dark)">${zmcFmt(status)}</span>
        `;
      }

      let html = '';

      // "All Submitted Fields" — grouped into form sections, in the order the applicant filled them
      const fdAll = (data.form_data && typeof data.form_data === 'object') ? data.form_data : {};
      const flatFd = Object.assign({}, fdAll);
      if (flatFd.ap1 && typeof flatFd.ap1 === 'object' && !Array.isArray(flatFd.ap1)) {
        for (const k in flatFd.ap1) if (!(k in flatFd)) flatFd[k] = flatFd.ap1[k];
      }
      const skipKeys = new Set(['current_step','registration_scope','journalist_scope','ap1',
        'directors','managers','senior_managers','directors_rows','managers_rows','custom_fields']);

      const scope = (fdAll.journalist_scope || fdAll.registration_scope || '').toString().toLowerCase();
      const isLocal = scope === 'local';

      // Section definitions mirror the form's step order
      const SECTIONS = [
        ['Personal Information', ['title','surname','first_name','other_names','dob','date_of_birth','birth_place','marital_status','gender','sex','nationality','origin']],
        ['Identification', ['national_reg_no','national_id','id_number','id_passport_number','passport_no','passport_expiry','passport_issued_at']],
        ['Contact Details', ['address','residential_address','address_in_zimbabwe','zim_address','zim_local_address','drivers_licence_no','phone_country_code','phone','phone_number','cell_number','email','email_address','collection_region']],
        ['Employment & Professional', ['employment_type','medium_type','medium_type_other','designation','designation_other','media_org','media_org_address','media_org_phone','media_org_email','editor_publisher_name','immediate_supervisor','string_for_orgs','string_for_details','employer_name','assignment_brief']],
        ['Foreign Travel', ['journalist_based_country','first_time_in_zim','last_in_zim_when','arrived_on','arrival_date','arrival_mode','departing_on','departure_date','port_of_entry','port_entry','special_assignment']],
        ['Organisation Details', ['org_name','organization_name','media_house_name','reg_no','registration_number','website','head_office','organization_address','postal_address','org_head_office','org_mailing_address','category','media_type','operating_model','mass_media_category','mass_media_activity','entity_name','mass_media_activities']],
        ['Representative Office', ['rep_office_name','rep_office_email','rep_office_address','rep_office_activities','rep_office_wholly_owned']],
        ['CEO Details', ['ceo_name','ceo_surname','ceo_nationality','ceo_qualifications','ceo_experience']],
        ['Contact Person', ['contact_person','contact_name','contact_surname','contact_email','contact_phone','contact_address','contact_phone_country_code']],
        ['Renewal / Replacement', ['previous_reference','changes','changes_details','replacement_reason','return_damaged']],
        ['Declarations & Conduct', ['q_convicted','q_convicted_details','q_judgment_debt','q_judgment_debt_details','q_insolvent_a','q_insolvent_b','q_insolvent_c','q_insolvent_d','q_insolvent_details','declaration_agreed','declaration_confirmed','declaration_date','other_relevant_info']],
      ];

      const isFilled = (v) => {
        if (v === null || v === undefined || v === '') return false;
        if (Array.isArray(v) && v.length === 0) return false;
        if (typeof v === 'object' && !Array.isArray(v) && Object.keys(v).length === 0) return false;
        return true;
      };

      const rowHtml = (k, v) => `<tr><td class="bg-light fw-semibold text-muted ps-3" style="width:35%;">${zmcEsc(zmcHumanize(k))}</td><td class="ps-3">${zmcRenderValue(v)}</td></tr>`;

      const covered = new Set();
      let sectionsHtml = '';

      // Render fields per section in the order the applicant filled them
      SECTIONS.forEach(([title, keys]) => {
        if (isLocal && title === 'Foreign Travel') return;
        const allowed = new Set(keys);
        const rows = [];
        // Iterate in form_data insertion order to preserve fill order
        Object.keys(flatFd).forEach(k => {
          if (!allowed.has(k) || covered.has(k)) return;
          const v = flatFd[k];
          if (!isFilled(v)) return;
          rows.push(rowHtml(k, v));
          covered.add(k);
        });
        if (rows.length) {
          sectionsHtml += `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-folder-open"></i> ${zmcEsc(title)}</div><div class="table-responsive"><table class="table table-sm align-middle mb-0 zmc-table-lite"><tbody class="small">${rows.join('')}</tbody></table></div></div>`;
        }
      });

      // Indexed groups: referees, academic, professional, past work links
      const collectIndexed = (prefixes) => {
        const idxSet = new Set();
        Object.keys(flatFd).forEach(k => {
          prefixes.forEach(p => {
            const m = k.match(new RegExp('^' + p.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '_(\\d+)$'));
            if (m) idxSet.add(parseInt(m[1], 10));
          });
        });
        return [...idxSet].sort((a,b) => a-b);
      };

      const buildIndexedTable = (title, indices, columns) => {
        if (!indices.length) return '';
        const head = '<tr>' + columns.map(c => `<th>${zmcEsc(c.label)}</th>`).join('') + '</tr>';
        const body = indices.map(i => '<tr>' + columns.map(c => {
          const key = c.key.replace('{i}', i);
          covered.add(key);
          const v = flatFd[key];
          return `<td>${isFilled(v) ? zmcRenderValue(v) : '<span class="text-muted">—</span>'}</td>`;
        }).join('') + '</tr>').join('');
        return `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-folder-open"></i> ${zmcEsc(title)}</div><div class="table-responsive"><table class="table table-sm align-middle mb-0 zmc-table-lite"><thead>${head}</thead><tbody class="small">${body}</tbody></table></div></div>`;
      };

      const academicIdx = collectIndexed(['highest_academic_year','highest_academic_institution','highest_academic_qualification']);
      sectionsHtml += buildIndexedTable('Highest Academic Qualifications', academicIdx, [
        {label:'Year', key:'highest_academic_year_{i}'},
        {label:'Institution', key:'highest_academic_institution_{i}'},
        {label:'Qualification', key:'highest_academic_qualification_{i}'},
      ]);

      const professionalIdx = collectIndexed(['professional_year','professional_institution','professional_qualification']);
      sectionsHtml += buildIndexedTable('Professional Qualifications', professionalIdx, [
        {label:'Year', key:'professional_year_{i}'},
        {label:'Institution', key:'professional_institution_{i}'},
        {label:'Qualification', key:'professional_qualification_{i}'},
      ]);

      const pastWorkIdx = collectIndexed(['past_work_link_url','past_work_link_desc']);
      sectionsHtml += buildIndexedTable('Past Work Links', pastWorkIdx, [
        {label:'URL', key:'past_work_link_url_{i}'},
        {label:'Description', key:'past_work_link_desc_{i}'},
      ]);

      const refereeIdx = collectIndexed(['referee_name','referee_address','referee_phone']);
      sectionsHtml += buildIndexedTable('Referees', refereeIdx, [
        {label:'Name', key:'referee_name_{i}'},
        {label:'Address', key:'referee_address_{i}'},
        {label:'Phone', key:'referee_phone_{i}'},
      ]);

      // Anything else not yet rendered → "Other Information" in fill order
      const leftoverRows = [];
      Object.keys(flatFd).forEach(k => {
        if (skipKeys.has(k) || covered.has(k)) return;
        const v = flatFd[k];
        if (!isFilled(v)) return;
        leftoverRows.push(rowHtml(k, v));
      });
      if (leftoverRows.length) {
        sectionsHtml += `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-folder-open"></i> Other Information</div><div class="table-responsive"><table class="table table-sm align-middle mb-0 zmc-table-lite"><tbody class="small">${leftoverRows.join('')}</tbody></table></div></div>`;
      }

      if (sectionsHtml) {
        html += `<div class="zmc-mdl-block"><div class="zmc-mdl-title"><i class="fa-regular fa-rectangle-list"></i> All Submitted Fields</div>${sectionsHtml}</div>`;
      }




      if (formCode === 'AP1') {
        const s = data.ap1 || {};
        html += zmcBlock(
          `<i class="fa-regular fa-building"></i> Organisation details`,
          `
          <div class="row g-3">
            ${zmcInput('Category', s.category)}
            ${zmcInput('Service name', s.service_name)}
            ${zmcInput('Operating model', s.operating_model)}
            ${zmcInput('Organisation', s.org_name)}
            ${zmcInput('Reg no', s.reg_no)}
            ${zmcInput('Website', s.website)}
            ${zmcTextarea('Head office', s.head_office, 6)}
            ${zmcTextarea('Postal address', s.postal_address, 6)}
          </div>
          `
        );

        html += zmcBlock(
          `<i class="fa-regular fa-address-book"></i> Contact`,
          `
          <div class="row g-3">
            ${zmcInput('Contact person', s.contact_person, 4)}
            ${zmcInput('Contact email', s.contact_email, 4)}
            ${zmcInput('Contact phone', s.contact_phone, 4)}
          </div>
          `
        );
      }

      const docs = Array.isArray(data.documents) ? data.documents : [];
      let docRows = docs.length ? '' : `<tr><td colspan="3" class="text-muted text-center">—</td></tr>`;
      docs.forEach(doc => {
        const open = doc.url
          ? `<a href="${doc.url}" target="_blank" class="btn btn-sm zmc-icon-btn btn-outline-primary" title="Open document">
               <i class="fa-solid fa-arrow-up-right-from-square"></i>
             </a>`
          : `<span class="text-muted">—</span>`;
        docRows += `
          <tr>
            <td class="fw-bold">${zmcFmt(doc.document_type)}</td>
            <td>${zmcFmt(doc.original_name || doc.file_name)}</td>
            <td class="text-end">${open}</td>
          </tr>
        `;
      });

      html += zmcBlock(
        `<i class="fa-regular fa-folder-open"></i> Attachments`,
        `
        <div class="table-responsive">
          <table class="table table-sm align-middle zmc-table-lite">
            <thead>
              <tr><th>Type</th><th>File</th><th class="text-end">Open</th></tr>
            </thead>
            <tbody>${docRows}</tbody>
          </table>
        </div>
        `
      );

      if (area) {
        area.innerHTML = html;
        area.classList.remove('d-none');
      }

    } catch (e) {
      if (errBox) {
        errBox.textContent = 'Error loading details: ' + (e.message || 'Unknown error');
        errBox.classList.remove('d-none');
      }
    } finally {
      if (loader) loader.classList.add('d-none');
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    }

    document.addEventListener('click', function(e){
      const btn = e.target.closest('.js-view-more');
      if(!btn) return;
      const appId = btn.getAttribute('data-app-id');
      if(!appId) return;

      zmcOpenModal('#appDetailsModal');
      loadApplicationDetails(appId);
    });
  });
</script>
@endpush
@endsection
