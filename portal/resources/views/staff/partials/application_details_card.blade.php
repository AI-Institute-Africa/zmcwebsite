@php
  $fd = $application->form_data;
  if (is_string($fd)) $fd = json_decode($fd, true);
  if (!is_array($fd)) $fd = [];

  $isRegistration = ($application->application_type === 'registration');
  $scope = $application->journalist_scope ?? ($fd['journalist_scope'] ?? 'local');

  // Flatten nested ap1 subkeys into the main form_data
  if (isset($fd['ap1']) && is_array($fd['ap1'])) {
      foreach($fd['ap1'] as $k => $v) {
          if (!isset($fd[$k])) $fd[$k] = $v;
      }
  }

  $labels = [
    'title' => 'Title',
    'surname' => 'Surname',
    'first_name' => 'First Name',
    'other_names' => 'Other Names',
    'dob' => 'Date of Birth',
    'date_of_birth' => 'Date of Birth',
    'birth_place' => 'Place & Country of Birth',
    'marital_status' => 'Marital Status',
    'gender' => 'Sex',
    'sex' => 'Sex',
    'national_reg_no' => 'National ID',
    'national_id' => 'National ID',
    'id_number' => 'ID Number',
    'id_passport_number' => 'ID/Passport Number',
    'passport_no' => 'Passport Number',
    'passport_expiry' => 'Passport Expiry',
    'passport_issued_at' => 'Passport Issued At',
    'nationality' => 'Nationality',
    'origin' => 'Country of Origin',
    'address' => 'Residential Address',
    'residential_address' => 'Residential Address',
    'province' => 'Province',
    'address_in_zimbabwe' => 'Address in Zimbabwe',
    'zim_address' => 'Zimbabwe Address',
    'zim_local_address' => 'Local Address in Zimbabwe',
    'drivers_licence_no' => 'Driver\'s Licence Number',
    'phone_country_code' => 'Phone Country Code',
    'phone' => 'Phone Number',
    'phone_number' => 'Phone Number',
    'cell_number' => 'Cell Number',
    'email' => 'Email Address',
    'email_address' => 'Email Address',
    'employment_type' => 'Employment Type',
    'medium_type' => 'Medium Type',
    'medium_type_other' => 'Medium Type (Other)',
    'designation' => 'Designation',
    'designation_other' => 'Designation (Other)',
    'media_org' => 'Media Organization',
    'media_org_address' => 'Media Org Address',
    'media_org_phone' => 'Media Org Phone',
    'media_org_email' => 'Media Org Email',
    'editor_publisher_name' => 'Editor / Publisher Name',
    'immediate_supervisor' => 'Immediate Supervisor',
    'string_for_orgs' => 'Stringer Organizations',
    'string_for_details' => 'Stringer Details',
    'employer_name' => 'Employer Name',
    'assignment_brief' => 'Brief on Assignment',
    'journalist_based_country' => 'Country Journalist is Based In',
    'first_time_in_zim' => 'First Time in Zimbabwe?',
    'last_in_zim_when' => 'Last in Zimbabwe (When)',
    'arrived_on' => 'Arrival Date',
    'arrival_date' => 'Arrival Date',
    'arrival_mode' => 'Mode of Arrival',
    'departing_on' => 'Departure Date',
    'departure_date' => 'Departure Date',
    'port_of_entry' => 'Port of Entry',
    'port_entry' => 'Port of Entry',
    'special_assignment' => 'Special Assignment',
    'collection_region' => 'Collection Office',
    'past_work_links' => 'Past Work Links',
    'declaration_date' => 'Declaration Date',
    'org_name' => 'Organization Name',
    'organization_name' => 'Organization Name',
    'media_house_name' => 'Media House Name',
    'reg_no' => 'Registration Number',
    'registration_number' => 'Registration Number',
    'website' => 'Website',
    'head_office' => 'Head Office Address',
    'organization_address' => 'Physical Address',
    'postal_address' => 'Postal Address',
    'contact_person' => 'Contact Person',
    'contact_name' => 'Contact Person / Name',
    'contact_surname' => 'Contact Surname',
    'contact_email' => 'Contact Email',
    'contact_phone' => 'Contact Phone',
    'contact_address' => 'Contact Physical Address',
    'contact_phone_country_code' => 'Country Code',
    'category' => 'Category',
    'media_type' => 'Media Type',
    'operating_model' => 'Operating Model',
    'mass_media_category' => 'Mass Media Category',
    'mass_media_activity' => 'Mass Media Activity',
    'entity_name' => 'Entity / Service Name',
    'org_head_office' => 'Head Office Address',
    'org_mailing_address' => 'Mailing Address',
    'rep_office_name' => 'Representative Office Name',
    'rep_office_email' => 'Representative Office Email',
    'rep_office_address' => 'Representative Office Address',
    'rep_office_activities' => 'Representative Office Activities',
    'rep_office_wholly_owned' => 'Wholly Owned by Applicant?',
    'ceo_name' => 'CEO Name',
    'ceo_surname' => 'CEO Surname',
    'ceo_nationality' => 'CEO Nationality',
    'ceo_qualifications' => 'CEO Qualifications',
    'ceo_experience' => 'CEO Experience',
    'previous_reference' => 'Previous Reg. Reference',
    'changes' => 'Any Changes?',
    'changes_details' => 'Details of Changes',
    'declaration_agreed' => 'Declaration Agreed?',
    'declaration_confirmed' => 'Declaration Confirmed?',
    'replacement_reason' => 'Reason for Replacement',
    'return_damaged' => 'Will Return Damaged Certificate?',
    'q_convicted' => 'Has Convictions?',
    'q_convicted_details' => 'Conviction Details',
    'q_judgment_debt' => 'Failed Judgment Debt?',
    'q_judgment_debt_details' => 'Judgment Debt Details',
    'q_insolvent_a' => 'Adjudged Insolvent?',
    'q_insolvent_b' => 'Insolvency Petition (10yr)?',
    'q_insolvent_c' => 'Compromise with Creditors?',
    'q_insolvent_d' => 'Declared Insolvent?',
    'q_insolvent_details' => 'Insolvency Details',
    'other_relevant_info' => 'Other Relevant Info',
    'mass_media_activities' => 'Mass Media Activities',
  ];

  // Logical grouping for cleaner display
  $sections = [
    'Personal Information' => [
      'title','surname','first_name','other_names','dob','date_of_birth','birth_place',
      'marital_status','gender','sex','nationality','origin',
    ],
    'Identification' => [
      'national_reg_no','national_id','id_number','id_passport_number',
      'passport_no','passport_expiry','passport_issued_at',
    ],
    'Contact Details' => [
      'address','residential_address','address_in_zimbabwe','zim_address','zim_local_address',
      'drivers_licence_no',
      'phone_country_code','phone','phone_number','cell_number',
      'email','email_address',
      'collection_region',
    ],
    'Employment & Professional' => [
      'employment_type','medium_type','medium_type_other','designation','designation_other',
      'media_org','media_org_address','media_org_phone','media_org_email',
      'editor_publisher_name','immediate_supervisor',
      'string_for_orgs','string_for_details',
      'employer_name','assignment_brief',
    ],
    'Foreign Travel' => [
      'journalist_based_country',
      'first_time_in_zim','last_in_zim_when',
      'arrived_on','arrival_date','arrival_mode',
      'departing_on','departure_date','port_of_entry','port_entry','special_assignment',
    ],
    'Organization Details (Registration)' => [
      'org_name','organization_name','media_house_name','reg_no','registration_number',
      'website','head_office','organization_address','postal_address',
      'org_head_office','org_mailing_address',
      'category','media_type','operating_model','mass_media_category','mass_media_activity',
      'entity_name','mass_media_activities',
    ],
    'Representative Office' => [
      'rep_office_name','rep_office_email','rep_office_address','rep_office_activities','rep_office_wholly_owned',
    ],
    'CEO Details' => [
      'ceo_name','ceo_surname','ceo_nationality','ceo_qualifications','ceo_experience',
    ],
    'Contact Person' => [
      'contact_person','contact_name','contact_surname','contact_email','contact_phone',
      'contact_address','contact_phone_country_code',
    ],
    'Renewal / Replacement' => [
      'previous_reference','changes','changes_details','replacement_reason','return_damaged',
    ],
    'Declarations & Conduct' => [
      'q_convicted','q_convicted_details','q_judgment_debt','q_judgment_debt_details',
      'q_insolvent_a','q_insolvent_b','q_insolvent_c','q_insolvent_d','q_insolvent_details',
      'declaration_agreed','declaration_confirmed','declaration_date','other_relevant_info',
    ],
  ];

  // Scope-based filtering: Remove foreign travel for local apps
  if ($scope === 'local') {
      unset($sections['Foreign Travel']);
      // Also remove passport fields from Identification for local apps
      $sections['Identification'] = array_diff($sections['Identification'], [
          'passport_no', 'passport_expiry', 'passport_issued_at'
      ]);
  } else {
      // For foreign apps, we can remove national ID fields if they are empty
      // but usually foreign applicants use passport_no.
      // We'll leave it for now unless requested.
  }

  // Build a list of fields already covered by sections, referees, directors, managers, custom fields, structural keys
  $covered = ['current_step','registration_scope','journalist_scope','directors','managers',
              'senior_managers','directors_rows','managers_rows','ap1','custom_fields',
              'past_work_links'];
  foreach ($sections as $keys) { $covered = array_merge($covered, $keys); }
  // Exclude indexed keys rendered in dedicated tables (referees, academic, professional, past work links)
  foreach (array_keys($fd) as $k) {
      if (preg_match('/^referee_(name|address|phone)_\d+$/', $k)) $covered[] = $k;
      if (preg_match('/^highest_academic_(year|institution|qualification)_\d+$/', $k)) $covered[] = $k;
      if (preg_match('/^professional_(year|institution|qualification)_\d+$/', $k)) $covered[] = $k;
      if (preg_match('/^past_work_link_(url|desc)_\d+$/', $k)) $covered[] = $k;
  }

  // Collect academic / professional / past work indexed rows
  $collectIndexed = function ($fd, $prefixes) {
      $indices = [];
      foreach (array_keys($fd) as $k) {
          foreach ($prefixes as $p) {
              if (preg_match('/^' . preg_quote($p, '/') . '_(\d+)$/', $k, $m)) {
                  $indices[(int)$m[1]] = true;
              }
          }
      }
      ksort($indices);
      return array_keys($indices);
  };
  $academicIdx = $collectIndexed($fd, ['highest_academic_year','highest_academic_institution','highest_academic_qualification']);
  $professionalIdx = $collectIndexed($fd, ['professional_year','professional_institution','professional_qualification']);
  $pastWorkIdx = $collectIndexed($fd, ['past_work_link_url','past_work_link_desc']);

  $fmtVal = function ($value) {
      if (is_array($value)) {
          if (array_is_list($value)) {
              $parts = array_map(fn($v) => e(is_array($v) ? json_encode($v) : (string)$v), $value);
              return implode(', ', $parts) ?: '<span class="text-muted">—</span>';
          }
          $out = '';
          foreach ($value as $sk => $sv) {
              $out .= '<div><span class="fw-semibold">' . e(ucwords(str_replace('_',' ',(string)$sk))) . ':</span> '
                   . e(is_array($sv) ? json_encode($sv) : (string)$sv) . '</div>';
          }
          return $out ?: '<span class="text-muted">—</span>';
      }
      if (is_bool($value) || $value === 'true' || $value === 'false') {
          $b = is_bool($value) ? $value : ($value === 'true');
          $cls = $b ? 'success' : 'danger';
          return '<span class="badge bg-'.$cls.'-subtle text-'.$cls.'">' . ($b ? 'Yes' : 'No') . '</span>';
      }
      if ($value === null || $value === '') return '<span class="text-muted">—</span>';
      return e((string)$value);
  };

  $passportPhoto = $application->documents->firstWhere('doc_type', 'passport_photo');

  // Collect referees - dynamically detect any referee_*_N indices
  $refereeIndices = [];
  foreach (array_keys($fd) as $k) {
      if (preg_match('/^referee_(?:name|address|phone)_(\d+)$/', $k, $m)) {
          $refereeIndices[(int)$m[1]] = true;
      }
  }
  ksort($refereeIndices);
  $referees = [];
  foreach (array_keys($refereeIndices) as $i) {
      if (!empty($fd["referee_name_{$i}"]) || !empty($fd["referee_phone_{$i}"]) || !empty($fd["referee_address_{$i}"])) {
          $referees[] = [
              'index'   => $i,
              'name'    => $fd["referee_name_{$i}"]    ?? '—',
              'address' => $fd["referee_address_{$i}"] ?? '—',
              'phone'   => $fd["referee_phone_{$i}"]   ?? '—',
          ];
      }
  }

  // Collect leftover fields not yet rendered
  $leftover = [];
  foreach ($fd as $k => $v) {
      if (in_array($k, $covered, true)) continue;
      // Skip empty/null
      if ($v === null || $v === '' || (is_array($v) && empty($v))) continue;
      $leftover[$k] = $v;
  }
@endphp

<style>
  .application-review-card > .card-header { color: #1e293b !important; background: #f8fafc !important; }
  .application-review-card thead.bg-white th { color: #334155 !important; background: #fff !important; }
</style>
<div class="card border-0 shadow-sm mb-4 application-review-card">
  <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
    <span><i class="ri-file-list-3-line me-1"></i> Full Application Data</span>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-light text-dark border">{{ strtoupper($application->application_type) }}</span>
      <span class="badge bg-primary-subtle text-primary border border-primary">{{ strtoupper(str_replace('_',' ', $application->request_type ?? 'new')) }}</span>
      @if($application->journalist_scope)
        <span class="badge bg-info-subtle text-info border border-info">{{ strtoupper($application->journalist_scope) }}</span>
      @endif
    </div>
  </div>
  <div class="card-body p-0">

    @if($passportPhoto)
      <div class="p-3 border-bottom text-center bg-light">
        <a href="{{ $passportPhoto->url }}" target="_blank">
          <img src="{{ $passportPhoto->url }}" alt="Passport Photo" class="rounded border shadow-sm" style="max-height: 160px; max-width: 140px; object-fit: cover;">
        </a>
        <div class="mt-1 small text-muted">Passport Photo</div>
      </div>
    @endif

    {{-- System metadata --}}
    <div class="p-3 border-bottom bg-light">
      <div class="row g-2 small">
        <div class="col-md-4"><span class="text-muted">Reference:</span> <span class="fw-bold">{{ $application->reference }}</span></div>
        <div class="col-md-4"><span class="text-muted">Submitted:</span> {{ $application->submitted_at?->format('d M Y H:i') ?? $application->created_at?->format('d M Y H:i') ?? '—' }}</div>
        <div class="col-md-4"><span class="text-muted">Status:</span> <span class="badge bg-primary-subtle text-primary border border-primary">{{ strtoupper(str_replace('_',' ', $application->status)) }}</span></div>
        @if($application->accreditation_category_code || $application->media_house_category_code)
          <div class="col-md-4"><span class="text-muted">Category:</span> <span class="fw-bold text-primary">{{ $application->accreditation_category_code ?? $application->media_house_category_code }}</span></div>
        @endif
        @if($application->collection_region)
          <div class="col-md-4"><span class="text-muted">Collection Region:</span> {{ ucfirst($application->collection_region) }}</div>
        @endif
        @if($application->journalist_scope)
          <div class="col-md-4"><span class="text-muted">Scope:</span> {{ ucfirst($application->journalist_scope) }}</div>
        @endif
      </div>
    </div>

    {{-- Sectioned form data --}}
    @foreach($sections as $sectionTitle => $sectionKeys)
      @php
        $rows = [];
        foreach ($sectionKeys as $k) {
          if (array_key_exists($k, $fd)) {
            $val = $fd[$k];
            // Only include if not empty/null
            if ($val !== null && $val !== '' && (!is_array($val) || !empty($val))) {
              $rows[$k] = $val;
            }
          }
        }
      @endphp
      @if(count($rows))
        <div class="border-bottom">
          <div class="px-3 py-2 bg-light">
            <span class="small fw-bold text-muted text-uppercase">{{ $sectionTitle }}</span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
              <tbody class="small">
                @foreach($rows as $key => $value)
                  <tr>
                    <td class="fw-semibold text-muted ps-3" style="width: 35%;">{{ $labels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}</td>
                    <td class="ps-3">{!! $fmtVal($value) !!}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif
    @endforeach

    {{-- Academic Qualifications --}}
    @if(count($academicIdx))
      <div class="border-bottom">
        <div class="px-3 py-2 bg-light">
          <span class="small fw-bold text-muted text-uppercase">Highest Academic Qualifications</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="bg-white">
              <tr><th class="ps-3">Year</th><th>Institution</th><th>Qualification</th></tr>
            </thead>
            <tbody class="small">
              @foreach($academicIdx as $i)
                <tr>
                  <td class="ps-3">{{ $fd["highest_academic_year_{$i}"] ?? '—' }}</td>
                  <td>{{ $fd["highest_academic_institution_{$i}"] ?? '—' }}</td>
                  <td>{{ $fd["highest_academic_qualification_{$i}"] ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    {{-- Professional Qualifications --}}
    @if(count($professionalIdx))
      <div class="border-bottom">
        <div class="px-3 py-2 bg-light">
          <span class="small fw-bold text-muted text-uppercase">Professional Qualifications</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="bg-white">
              <tr><th class="ps-3">Year</th><th>Institution</th><th>Qualification</th></tr>
            </thead>
            <tbody class="small">
              @foreach($professionalIdx as $i)
                <tr>
                  <td class="ps-3">{{ $fd["professional_year_{$i}"] ?? '—' }}</td>
                  <td>{{ $fd["professional_institution_{$i}"] ?? '—' }}</td>
                  <td>{{ $fd["professional_qualification_{$i}"] ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    {{-- Past Work Links --}}
    @if(count($pastWorkIdx))
      <div class="border-bottom">
        <div class="px-3 py-2 bg-light">
          <span class="small fw-bold text-muted text-uppercase">Past Work Links</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="bg-white">
              <tr><th class="ps-3">#</th><th>URL</th><th>Description</th></tr>
            </thead>
            <tbody class="small">
              @foreach($pastWorkIdx as $i)
                @php $url = $fd["past_work_link_url_{$i}"] ?? null; @endphp
                <tr>
                  <td class="ps-3 fw-bold">{{ $i }}</td>
                  <td>
                    @if($url)
                      <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ $url }}</a>
                    @else
                      —
                    @endif
                  </td>
                  <td>{{ $fd["past_work_link_desc_{$i}"] ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    {{-- Referees (accreditation) --}}
    @if(count($referees))
      <div class="border-bottom">
        <div class="px-3 py-2 bg-light">
          <span class="small fw-bold text-muted text-uppercase">Referees ({{ count($referees) }})</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <thead class="bg-white">
              <tr><th class="ps-3">#</th><th>Name</th><th>Address</th><th>Phone</th></tr>
            </thead>
            <tbody class="small">
              @foreach($referees as $r)
                <tr>
                  <td class="ps-3 fw-bold">{{ $r['index'] }}</td>
                  <td>{{ $r['name'] }}</td>
                  <td>{{ $r['address'] }}</td>
                  <td>{{ $r['phone'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    {{-- Directors --}}
    @php
      $directors = $fd['directors'] ?? $fd['directors_rows'] ?? null;
      $managers  = $fd['managers']  ?? $fd['managers_rows']  ?? $fd['senior_managers'] ?? null;
    @endphp
    @if($directors && is_array($directors))
      <div class="border-bottom">
        <div class="px-3 py-2 bg-light">
          <span class="small fw-bold text-muted text-uppercase"><i class="ri-team-line me-1"></i> Directors</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0 x-small">
            <thead class="bg-light">
              <tr><th>Name</th><th>Nationality</th><th>ID Number</th><th>Address</th><th>Occupation</th><th>Shares %</th></tr>
            </thead>
            <tbody>
              @foreach($directors as $d)
                @php $d = is_array($d) ? $d : ['name' => (string)$d]; @endphp
                <tr>
                  <td>{{ trim(($d['name'] ?? '') . ' ' . ($d['surname'] ?? $d['director_name'] ?? '')) ?: '—' }}</td>
                  <td>{{ $d['nationality'] ?? $d['director_nationality'] ?? '—' }}</td>
                  <td>{{ $d['id_number'] ?? $d['national_id'] ?? '—' }}</td>
                  <td>{{ $d['address'] ?? $d['director_address'] ?? '—' }}</td>
                  <td>{{ $d['occupation'] ?? '—' }}</td>
                  <td>{{ ($d['shareholding_percent'] ?? '—') }}{{ isset($d['shareholding_percent']) ? '%' : '' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    @if($managers && is_array($managers))
      <div class="border-bottom">
        <div class="px-3 py-2 bg-light">
          <span class="small fw-bold text-muted text-uppercase"><i class="ri-user-star-line me-1"></i> Managers / Senior Staff</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered mb-0 x-small">
            <thead class="bg-light">
              <tr><th>Name</th><th>Nationality</th><th>Qualifications</th><th>Occupation/Exp</th><th>Address</th></tr>
            </thead>
            <tbody>
              @foreach($managers as $m)
                @php $m = is_array($m) ? $m : ['name' => (string)$m]; @endphp
                <tr>
                  <td>{{ trim(($m['name'] ?? '') . ' ' . ($m['surname'] ?? $m['manager_name'] ?? '')) ?: '—' }}</td>
                  <td>{{ $m['nationality'] ?? $m['manager_nationality'] ?? '—' }}</td>
                  <td>{{ $m['qualifications'] ?? '—' }}</td>
                  <td>{{ $m['occupation'] ?? $m['manager_occupation'] ?? $m['experience'] ?? '—' }}</td>
                  <td>{{ $m['address'] ?? $m['manager_address'] ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

    {{-- Anything else not yet rendered --}}
    @if(count($leftover))
      <div>
        <div class="px-3 py-2 bg-light">
          <span class="small fw-bold text-muted text-uppercase">Additional Information</span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0 align-middle">
            <tbody class="small">
              @foreach($leftover as $key => $value)
                <tr>
                  <td class="fw-semibold text-muted ps-3" style="width: 35%;">{{ $labels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}</td>
                  <td class="ps-3">{!! $fmtVal($value) !!}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif

  </div>
</div>

<style>
  .x-small { font-size: var(--font-size-dense, 0.75rem); }
  .table-sm td, .table-sm th { padding: 0.5rem 0.5rem; }
</style>
