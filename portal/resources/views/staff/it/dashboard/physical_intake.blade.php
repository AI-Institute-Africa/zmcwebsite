@extends('layouts.portal')
@section('title', 'New Physical Intake - IT Admin')

@section('content')
<form method="POST" action="{{ route('staff.it.intake.process') }}" id="intakeForm" enctype="multipart/form-data">
  @csrf
  <div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155; padding: 20px;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div>
        <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">New Physical Intake</h4>
        <div class="text-muted mt-1" style="font-size:13px;">
          <i class="ri-information-line me-1"></i>
          Record walk-in applications, renewals, and replacements for card generation.
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('staff.it.intake') }}" class="btn btn-white border shadow-sm btn-sm px-3">
          <i class="ri-arrow-left-line me-1"></i>Back to Intake List
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success d-flex align-items-start gap-2">
        <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
        <div>{{ session('success') }}</div>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

  <div class="zmc-card shadow-sm mb-4">
    <div class="card-header bg-light">
      <h6 class="fw-bold m-0"><i class="ri-user-add-line me-2"></i>Intake Type</h6>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label small fw-bold">Application Type</label>
          <select name="application_type" id="application_type" class="form-select border-light-subtle py-2 shadow-none" style="border-radius: 10px; background-color: #f8fafc;">
              <option value="accreditation" selected>Accreditation (Media Practitioner)</option>
              <option value="registration">Registration (Media House)</option>
            </select>
        </div>
        <div class="col-md-12">
          <label class="form-label small fw-bold">Request Type</label>
          <select name="request_type" id="request_type" class="form-select">
            <option value="">-- Select Request Type --</option>
            <option value="new">New Application</option>
            <option value="renewal">Renewal</option>
            <option value="replacement">Replacement</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div id="newApplicationForm" class="card shadow-sm mb-4" style="display: none;">
    <div class="card-header bg-primary text-white">
      <h6 class="fw-bold m-0"><i class="ri-user-add-line me-2"></i>New Application Details</h6>
    </div>
    <div class="card-body">
      <div id="newAppInfoAlert" class="alert alert-info">
        <i class="ri-information-line me-2"></i>
        <strong>New Application:</strong> <span id="newAppInfoText">Complete applicant details for walk-in applications.</span>
      </div>

      <div id="practitionerFields">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">First Name <span class="text-danger">*</span></label>
            <input type="text" name="first_name" class="form-control" placeholder="First name">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Surname <span class="text-danger">*</span></label>
            <input type="text" name="surname" class="form-control" placeholder="Surname">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">ID Number <span class="text-danger">*</span></label>
            <input type="text" name="id_number" class="form-control zmc-nid-input" placeholder="XX-XXXXXXXXXXX">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Category <span class="text-danger">*</span></label>
            <select name="category" class="form-select">
              <option value="">-- Select Category --</option>
              <option value="JE">JE — Local media practitioner employed on full-time basis</option>
              <option value="JF">JF — Local journalist free-lancing locally</option>
              <option value="JO">JO — Local journalist running an office for foreign media service</option>
              <option value="JS">JS — Local journalist stringing for foreign media service</option>
              <option value="JM">JM — Local journalist reporting both locally and abroad</option>
              <option value="JP">JP — Local media practitioner in content creation, photography, public relations and all forms of digital media</option>
              <option value="JD">JD — Local media practitioner in digital social media</option>
              <option value="JT">JT — Foreign journalist on temporary permit</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Organisation/Media House <span class="text-danger">*</span></label>
            <input type="text" name="employer_name" class="form-control" placeholder="Organisation or media house name">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Designation/Position</label>
            <input type="text" name="designation" class="form-control" placeholder="e.g., Reporter, Editor, Cameraman">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Receipt Number <span class="text-danger">*</span></label>
            <input type="text" name="receipt_number" class="form-control" placeholder="Receipt number">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="Email address">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control" placeholder="Phone number">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Physical Address <span class="text-danger">*</span></label>
            <input type="text" name="physical_address" class="form-control" placeholder="Full physical address">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Province <span class="text-danger">*</span></label>
            <select name="province" class="form-select">
              <option value="">-- Select Province --</option>
              <option value="harare">Harare</option>
              <option value="bulawayo">Bulawayo</option>
              <option value="manicaland">Manicaland</option>
              <option value="mashonaland_central">Mashonaland Central</option>
              <option value="mashonaland_east">Mashonaland East</option>
              <option value="mashonaland_west">Mashonaland West</option>
              <option value="masvingo">Masvingo</option>
              <option value="matabeleland_north">Matabeleland North</option>
              <option value="matabeleland_south">Matabeleland South</option>
              <option value="midlands">Midlands</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">City <span class="text-danger">*</span></label>
            <input type="text" name="city" class="form-control" placeholder="City or town">
          </div>
          <div class="col-12">
            <label class="form-label small fw-bold">Applicant Photo</label>
            <div class="row g-3">
              <div class="col-md-8">
                <input type="file" name="applicant_photo" id="applicant_photo" class="form-control" accept="image/*" onchange="previewPhoto(this)">
                <div class="form-text">Upload applicant photo (JPG, PNG - Max 5MB)</div>
              </div>
              <div class="col-md-4 text-center">
                <img id="photo_preview" src="{{ asset('images/default-avatar.png') }}" alt="Photo Preview" class="rounded" style="width: 100px; height: 100px; object-fit: cover; border: 2px solid #dee2e6;">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="mediaHouseFields" style="display: none;">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-bold">Entity Name <span class="text-danger">*</span></label>
            <input type="text" name="entity_name" class="form-control" placeholder="Official registered entity name">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Trading Name <span class="text-danger">*</span></label>
            <input type="text" name="trading_name" class="form-control" placeholder="Trading as name">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Business Registration Number <span class="text-danger">*</span></label>
            <input type="text" name="business_registration" class="form-control" placeholder="Company registration number">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Tax Number <span class="text-danger">*</span></label>
            <input type="text" name="tax_number" class="form-control" placeholder="Tax/BVR number">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Business Type <span class="text-danger">*</span></label>
            <select name="business_type" class="form-select">
              <option value="">-- Select --</option>
              <option value="private_limited">Private Limited</option>
              <option value="public_limited">Public Limited</option>
              <option value="partnership">Partnership</option>
              <option value="sole_proprietor">Sole Proprietor</option>
              <option value="ngo">NGO</option>
              <option value="government">Government</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Ownership Type <span class="text-danger">*</span></label>
            <select name="ownership_type" class="form-select">
              <option value="">-- Select --</option>
              <option value="local">Local Ownership</option>
              <option value="foreign">Foreign Ownership</option>
              <option value="mixed">Mixed Ownership</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Local Shareholding (%) <span class="text-danger">*</span></label>
            <input type="number" name="local_ownership" class="form-control" min="0" max="100">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Website</label>
            <input type="url" name="website" class="form-control" placeholder="https://www.example.com">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">ZMC Media Category <span class="text-danger">*</span></label>
            <select name="media_category" class="form-select">
              <option value="">-- Select --</option>
              <option value="MC">MC — Community Media</option>
              <option value="MA">MA — Advertising agency as media service</option>
              <option value="MF">MF — Local office for foreign media service</option>
              <option value="MN">MN — National media service publishing newspaper</option>
              <option value="DG">DG — Internet base media service</option>
              <option value="MP">MP — Production house as media service</option>
              <option value="MS">MS — National media service publishing magazine</option>
              <option value="MT">MT — Broadcasting media service free to air</option>
              <option value="MB">MB — Satellite broadcast</option>
              <option value="MV">MV — Video on demand</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Publication/Service Name <span class="text-danger">*</span></label>
            <input type="text" name="publication_name" class="form-control" placeholder="e.g., The Daily News">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Frequency</label>
            <select name="publication_frequency" class="form-select">
              <option value="">-- Frequency --</option>
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
              <option value="quarterly">Quarterly</option>
              <option value="irregular">Irregular</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Editor/Manager Name <span class="text-danger">*</span></label>
            <input type="text" name="editor_name" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Editor Contact <span class="text-danger">*</span></label>
            <input type="text" name="editor_contact" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="mh_email" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
            <input type="tel" name="mh_phone" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Physical Address <span class="text-danger">*</span></label>
            <input type="text" name="mh_physical_address" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Postal Address <span class="text-danger">*</span></label>
            <input type="text" name="postal_address" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Province <span class="text-danger">*</span></label>
            <select name="mh_province" class="form-select">
              <option value="">-- Select --</option>
              <option value="harare">Harare</option>
              <option value="bulawayo">Bulawayo</option>
              <option value="manicaland">Manicaland</option>
              <option value="mashonaland_central">Mashonaland Central</option>
              <option value="mashonaland_east">Mashonaland East</option>
              <option value="mashonaland_west">Mashonaland West</option>
              <option value="masvingo">Masvingo</option>
              <option value="matabeleland_north">Matabeleland North</option>
              <option value="matabeleland_south">Matabeleland South</option>
              <option value="midlands">Midlands</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">City/Town <span class="text-danger">*</span></label>
            <input type="text" name="mh_city" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-bold">Receipt Number <span class="text-danger">*</span></label>
            <input type="text" name="mh_receipt_number" class="form-control">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="existingApplicationForm" class="card shadow-sm mb-4" style="display: none;">
    <div class="card-header bg-success text-white">
      <h6 class="fw-bold m-0"><i class="ri-refresh-line me-2"></i>Renewal / Replacement Details</h6>
    </div>
    <div class="card-body">
      <div class="alert alert-info">
        <i class="ri-information-line me-2"></i>
        <strong>Renewal/Replacement:</strong> Enter applicant details to link to existing application record.
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label small fw-bold">First Name <span class="text-danger">*</span></label>
          <input type="text" name="renewal_first_name" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Surname <span class="text-danger">*</span></label>
          <input type="text" name="renewal_surname" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">ID Number <span class="text-danger">*</span></label>
          <input type="text" name="renewal_id_number" class="form-control zmc-nid-input" placeholder="XX-XXXXXXXXXXX">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Accreditation / Registration Number <span class="text-danger">*</span></label>
          <input type="text" name="lookup_number" class="form-control" placeholder="e.g. J12345678E or MC00001234">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Receipt Number <span class="text-danger">*</span></label>
          <input type="text" name="renewal_receipt_number" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
          <input type="email" name="renewal_email" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
          <input type="tel" name="renewal_phone" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Physical Address <span class="text-danger">*</span></label>
          <input type="text" name="renewal_physical_address" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">City/Town <span class="text-danger">*</span></label>
          <input type="text" name="renewal_city" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-bold">Province <span class="text-danger">*</span></label>
          <select name="renewal_province" class="form-select">
            <option value="">-- Select --</option>
            <option value="harare">Harare</option>
            <option value="bulawayo">Bulawayo</option>
            <option value="manicaland">Manicaland</option>
            <option value="mashonaland_central">Mashonaland Central</option>
            <option value="mashonaland_east">Mashonaland East</option>
            <option value="mashonaland_west">Mashonaland West</option>
            <option value="masvingo">Masvingo</option>
            <option value="matabeleland_north">Matabeleland North</option>
            <option value="matabeleland_south">Matabeleland South</option>
            <option value="midlands">Midlands</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="zmc-card shadow-sm">
    <div class="card-body">
      <div class="alert alert-info">
        <i class="ri-information-line me-1"></i>
        All required fields must be completed before submission.
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('staff.it.intake') }}" class="btn btn-secondary">
          <i class="ri-arrow-left-line me-1"></i>Cancel
        </a>
        <button type="submit" form="intakeForm" class="btn btn-primary btn-lg">
          <i class="ri-check-line me-1"></i>Record Intake & Add to Production Queue
        </button>
      </div>
    </div>
  </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const requestType = document.getElementById('request_type');
    const applicationType = document.getElementById('application_type');
    const newAppForm = document.getElementById('newApplicationForm');
    const existingAppForm = document.getElementById('existingApplicationForm');

    function updateFormVisibility() {
        const rv = requestType.value;
        const av = applicationType.value;
        const practitionerFields = document.getElementById('practitionerFields');
        const mediaHouseFields = document.getElementById('mediaHouseFields');

        newAppForm.style.display = 'none';
        existingAppForm.style.display = 'none';
        practitionerFields.style.display = 'none';
        mediaHouseFields.style.display = 'none';

        if (rv === 'new') {
            newAppForm.style.display = 'block';
            if (av === 'registration') {
                mediaHouseFields.style.display = 'block';
            } else {
                practitionerFields.style.display = 'block';
            }
        } else if (rv === 'renewal' || rv === 'replacement') {
            existingAppForm.style.display = 'block';
        }
    }

    requestType.addEventListener('change', updateFormVisibility);
    applicationType.addEventListener('change', updateFormVisibility);
});

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photo_preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
