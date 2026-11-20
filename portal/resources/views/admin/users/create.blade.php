@extends('layouts.portal')

@section('title', 'Create User')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Create Staff User</h1>
      <div class="text-muted mt-1" style="font-size:13px;">Users created here are marked as <strong>Staff</strong> by default.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('admin.users.staff') }}" class="btn btn-sm btn-outline-dark"><i class="ri-arrow-left-line me-1"></i> Back</a>
    </div>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Please fix the following:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card border-0 shadow-sm" style="max-width:820px;">
    <div class="card-body">
      <form method="POST" action="{{ route('admin.users.store') }}" class="row g-3">
        @csrf

        <div class="col-md-6">
          <label class="form-label fw-bold">Name</label>
          <input name="name" value="{{ old('name') }}" class="form-control" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Designation</label>
          <input name="designation" value="{{ old('designation') }}" class="form-control" placeholder="e.g. Accreditation Officer, Registrar...">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Email</label>
          <input name="email" value="{{ old('email') }}" type="email" class="form-control" required>
          <div class="text-muted small mt-1">An activation link will be emailed so the user can set their own password.</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-bold">Assign roles</label>
          <div class="p-3 rounded" style="border:1px solid var(--zmc-border); background: rgba(248,250,252,.7);">
            <div class="row g-2">
              @foreach($roles as $r)
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="d-flex align-items-center gap-2 p-2 rounded" style="border:1px solid rgba(15,23,42,.08); background:#fff;">
                    <input type="checkbox" name="roles[]" value="{{ $r->name }}" class="form-check-input m-0"
                      @checked(in_array($r->name, old('roles', [])))>
                    <span class="fw-bold" style="font-size:12px;">{{ str_replace('_',' ', $r->name) }}</span>
                  </label>
                </div>
              @endforeach
            </div>
          </div>
          <div class="text-muted small mt-2">Tip: Assign only what the user needs. The <strong>Director</strong> role has admin access, but is blocked from operational workflows.</div>
        </div>

        <div class="col-12">
          <label class="form-label fw-bold">Assign Regional Jurisdiction <span class="text-muted fw-normal">(for Accreditation Officers)</span></label>
          <div class="p-3 rounded" style="border:1px solid var(--zmc-border); background: rgba(248,250,252,.7);">
            @if(count($regions) === 0)
              <div class="text-muted small">No active regions configured. Add regions in IT Admin → Regions.</div>
            @else
              <div class="row g-2">
                @foreach($regions as $region)
                  <div class="col-12 col-sm-6 col-lg-4">
                    <label class="d-flex align-items-center gap-2 p-2 rounded" style="border:1px solid rgba(15,23,42,.08); background:#fff;">
                      <input type="checkbox" name="assigned_regions[]" value="{{ $region->id }}" class="form-check-input m-0"
                        @checked(in_array($region->id, old('assigned_regions', [])))>
                      <div>
                        <div class="fw-bold" style="font-size:12px;">{{ $region->name }}</div>
                        @if($region->expires_at)
                          <div class="text-danger" style="font-size:10px;">Expires: {{ $region->expires_at->format('d M Y') }}</div>
                        @endif
                      </div>
                    </label>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
          <div class="text-muted small mt-2">
            Accreditation Officers see <strong>only applications from their assigned regions</strong> and also handle
            <strong>Production (card / certificate generation)</strong> for the same regions — one person owns the
            application end-to-end for their region. Registrar, Accounts, Director and other roles see all regions.
          </div>
        </div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary"><i class="ri-save-3-line me-1"></i> Create user</button>
          <a href="{{ route('admin.users.staff') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
