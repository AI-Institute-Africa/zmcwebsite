@extends('layouts.portal')
@section('title', 'Organization Profile')

@section('content')

<div id="org-profile-page">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="fw-bold text-dark m-0" style="font-size:18px;">Organization Profile</h1>
    <button class="btn btn-primary" id="editOrgProfileBtn">
      <i class="ri-edit-line me-2"></i>Edit Profile
    </button>
  </div>

  <div class="form-container">
    <div class="form-header" style="background: transparent !important; border-bottom: 1px solid #e2e8f0;">
      <h2 class="m-0" style="color: #334155;"><i class="ri-building-2-line me-2"></i>Registered Entity Information</h2>
      <p class="mt-2" style="color: #64748b;">Maintain your organization details. (In production this should sync from your database.)</p>
    </div>

    <div class="form-steps-container">
      <div class="text-center mb-4">
        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->profile_data['organization_name'] ?? Auth::user()->name) }}&size=120&background=facc15&color=000&bold=true"
             class="rounded-circle" alt="Profile" width="120" height="120">
        <h3 class="mt-3 mb-1" id="orgNameHeading">{{ Auth::user()->profile_data['organization_name'] ?? Auth::user()->name }}</h3>
        <p class="text-muted mb-1">Applicant Account Account</p>
        <div class="d-flex justify-content-center gap-2">
          <span class="badge bg-light text-dark">Ref: {{ Auth::user()->email }}</span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label class="form-label">Organization Name</label>
          <input type="text" class="form-control" value="{{ Auth::user()->profile_data['organization_name'] ?? Auth::user()->name }}" readonly>
        </div>
        <div class="form-field">
          <label class="form-label">Organization Registration Number</label>
          <input type="text" class="form-control" value="{{ Auth::user()->profile_data['organization_registration_number'] ?? ($registration->registration_no ?? 'Not Registered Yet') }}" readonly>
        </div>
      </div>

      <div class="form-row">
        <div class="form-field">
          <label class="form-label">Primary Contact Email</label>
          <input type="email" class="form-control" value="{{ Auth::user()->email }}" readonly>
        </div>
        <div class="form-field">
          <label class="form-label">Contact Person Name</label>
          <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
        </div>
      </div>

      <div class="form-row">
         <div class="form-field">
           <label class="form-label">Primary Phone</label>
           <input type="text" class="form-control" value="{{ Auth::user()->phone_country_code }} {{ Auth::user()->phone_number }}" readonly>
         </div>
         <div class="form-field">
           <label class="form-label">Secondary Phone</label>
           <input type="text" class="form-control" value="{{ Auth::user()->profile_data['secondary_phone'] ?? 'Not Provided' }}" readonly>
         </div>
       </div>
 
       <div class="form-row">
         <div class="form-field">
           <label class="form-label">Head Office Address</label>
           <input type="text" class="form-control" value="{{ Auth::user()->profile_data['head_office_address'] ?? 'Not Provided' }}" readonly>
         </div>
         <div class="form-field">
           <label class="form-label">Postal Address</label>
           <input type="text" class="form-control" value="{{ Auth::user()->profile_data['postal_address'] ?? 'Not Provided' }}" readonly>
         </div>
       </div>
 
       <div class="form-row">
         <div class="form-field">
           <label class="form-label">Type of Mass Media Activities</label>
           <input type="text" class="form-control" value="{{ Auth::user()->profile_data['mass_media_activities'] ?? 'Not Provided' }}" readonly>
         </div>
         <div class="form-field">
           <label class="form-label">Website</label>
           <input type="text" class="form-control" value="{{ Auth::user()->profile_data['website'] ?? 'Not Provided' }}" readonly>
         </div>
       </div>
    </div>
  </div>
</div>

@endsection
