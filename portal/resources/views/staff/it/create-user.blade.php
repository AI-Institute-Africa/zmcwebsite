@extends('layouts.portal')
@section('title', 'Create User (IT Admin)')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-dark">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Create User</h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Create a staff, Media Practitioner, or Media House account. New portal users receive a secure password setup link.
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('staff.it.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3 d-none d-md-inline">
        <i class="ri-dashboard-3-line me-1"></i> Dashboard
      </a>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="zmc-card">
    <form method="POST" action="{{ route('staff.it.users.store') }}">
      @csrf
      <div class="row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label zmc-lbl" for="account_type">Account type</label>
          <select class="form-select zmc-input" name="account_type" id="account_type" required>
            <option value="staff" @selected(old('account_type', 'staff') === 'staff')>Staff account</option>
            <option value="media_practitioner" @selected(old('account_type') === 'media_practitioner')>Media Practitioner portal</option>
            <option value="media_house" @selected(old('account_type') === 'media_house')>Media House portal</option>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label zmc-lbl" id="accountNameLabel" for="account_name">Name</label>
          <input class="form-control zmc-input" name="name" id="account_name" value="{{ old('name') }}" required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label zmc-lbl" for="account_email">Email</label>
          <input class="form-control zmc-input" name="email" id="account_email" type="email" value="{{ old('email') }}" required>
        </div>
        <div class="col-12">
          <div class="text-muted small" id="accountSetupMessage">
            <i class="ri-mail-send-line me-1"></i>
            The staff member will activate their account and set a password from an emailed link.
          </div>
        </div>

        <div class="col-12" id="staffRoleFields">
          <label class="form-label zmc-lbl">Assign Role(s) (requested)</label>
          <div class="row g-2">
            @foreach($roles as $role)
              <div class="col-12 col-md-3">
                <label class="d-flex align-items-center gap-2 p-2 border rounded" style="background:#fff; cursor:pointer;">
                  <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, old('roles', [])))>
                  <span class="small fw-bold">{{ strtoupper(str_replace('_',' ', $role->name)) }}</span>
                </label>
              </div>
            @endforeach
          </div>
        </div>

        <div class="col-12 mt-4" id="staffRegionFields">
          <label class="form-label zmc-lbl">Assign Regional Jurisdiction (for Accreditation Officers)</label>
          <div class="row g-2">
            @foreach($regions as $region)
              <div class="col-12 col-md-3">
                <label class="d-flex align-items-center gap-2 p-2 border rounded" style="background:#fff; cursor:pointer;">
                  <input type="checkbox" name="assigned_regions[]" value="{{ $region->id }}" @checked(in_array($region->id, old('assigned_regions', [])))>
                  <div>
                    <div class="small fw-bold">{{ $region->name }}</div>
                    @if($region->expires_at)
                      <div class="text-danger" style="font-size:10px;">Expires: {{ $region->expires_at->format('d M Y') }}</div>
                    @endif
                  </div>
                </label>
              </div>
            @endforeach
          </div>
          <div class="form-text mt-2">
            Accreditation Officers see <strong>only applications from their assigned regions</strong> and
            also handle <strong>Production (card / certificate generation)</strong> for the same regions —
            one person owns the application end-to-end for their region. Registrar, Accounts, Director and
            other roles continue to see applications from all regions.
          </div>
        </div>
      </div>

      <div class="mt-3 d-flex justify-content-end">
        <button class="btn btn-dark fw-bold px-4" type="submit">
          <i class="ri-user-add-line me-1"></i> Create Account
        </button>
      </div>
    </form>
  </div>
</div>
<script>
(() => {
  const type = document.getElementById('account_type');
  const nameLabel = document.getElementById('accountNameLabel');
  const setupMessage = document.getElementById('accountSetupMessage');
  const roleFields = document.getElementById('staffRoleFields');
  const regionFields = document.getElementById('staffRegionFields');

  function updateAccountType() {
    const staff = type.value === 'staff';
    roleFields.hidden = !staff;
    regionFields.hidden = !staff;
    nameLabel.textContent = type.value === 'media_house' ? 'Media house name' : (type.value === 'media_practitioner' ? 'Practitioner full name' : 'Name');
    setupMessage.innerHTML = staff
      ? '<i class="ri-mail-send-line me-1"></i>The staff member will activate their account and set a password from an emailed link.'
      : '<i class="ri-mail-send-line me-1"></i>A secure password setup link will be emailed. The new portal dashboard will be attached to this account; existing applications are never reassigned or deleted.';
  }

  type.addEventListener('change', updateAccountType);
  updateAccountType();
})();
</script>
@endsection
