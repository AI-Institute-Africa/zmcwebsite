@extends('layouts.portal')

@section('title', 'Roles')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Roles</h4>
      <div class="text-muted mt-1" style="font-size:13px;">Create roles and manage role-to-permission mapping.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-dark"><i class="ri-arrow-left-line me-1"></i> Back</a>
      <a href="{{ route('admin.permissions.index') }}" class="btn btn-sm btn-outline-primary"><i class="ri-key-2-line me-1"></i> Permissions</a>
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
      <div class="fw-bold mb-1">Please fix the following:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-xl-5">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-add-circle-line me-2"></i>Create Role</div>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.roles.store') }}" class="d-flex gap-2 flex-wrap">
            @csrf
            <input name="name" class="form-control" placeholder="e.g. qa_officer" required>
            <button class="btn btn-primary"><i class="ri-save-3-line me-1"></i> Create</button>
          </form>
          <div class="text-muted small mt-2">Use lowercase + underscores for consistency.</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <div class="fw-bold"><i class="ri-shield-keyhole-line me-2"></i>Role List</div>
          <span class="badge bg-dark">{{ $roles->count() }}</span>
        </div>
        <div class="table-responsive">
          <table class="table zmc-table-lite mb-0">
            <thead>
              <tr>
                <th>Role</th>
                <th># Permissions</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($roles as $r)
                <tr>
                  <td class="fw-bold">{{ str_replace('_',' ', $r->name) }}</td>
                  <td>{{ $r->permissions_count }}</td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.roles.edit', $r) }}">
                      <i class="ri-settings-3-line me-1"></i> Edit
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center py-4 text-muted">No roles found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
