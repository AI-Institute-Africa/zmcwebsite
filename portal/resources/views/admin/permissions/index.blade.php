@extends('layouts.portal')

@section('title', 'Permissions')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Permissions</h4>
      <div class="text-muted mt-1" style="font-size:13px;">Create permissions and assign them to roles.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-dark"><i class="ri-arrow-left-line me-1"></i> Back</a>
      <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-primary"><i class="ri-shield-keyhole-line me-1"></i> Roles</a>
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
          <div class="fw-bold"><i class="ri-add-circle-line me-2"></i>Create Permission</div>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.permissions.store') }}" class="d-flex gap-2 flex-wrap">
            @csrf
            <input name="name" class="form-control" style="max-width:420px" placeholder="New permission e.g. approve_application" required>
            <button class="btn btn-primary"><i class="ri-save-3-line me-1"></i> Create</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <div class="fw-bold"><i class="ri-key-2-line me-2"></i>All Permissions</div>
          <span class="badge bg-dark">{{ $permissions->count() }}</span>
        </div>
        <div class="table-responsive">
          <table class="table zmc-table-lite mb-0">
            <thead>
              <tr>
                <th>Permission</th>
              </tr>
            </thead>
            <tbody>
              @forelse($permissions as $p)
                <tr>
                  <td class="fw-bold">{{ str_replace('_',' ', $p->name) }}</td>
                </tr>
              @empty
                <tr><td class="text-center py-4 text-muted">No permissions found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
