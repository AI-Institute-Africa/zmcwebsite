@extends('layouts.portal')

@section('title', 'User Management')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">User &amp; Account Management</h4>
      <div class="text-muted mt-1" style="font-size:13px;">Users grouped into <strong>Public Users</strong> and <strong>Staff Users</strong>.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-dark"><i class="ri-arrow-left-line me-1"></i> Back</a>
      <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary"><i class="ri-user-add-line me-1"></i> Create user</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
      <div class="fw-bold me-2"><i class="ri-search-line me-1"></i> Search</div>
      <input name="q" value="{{ $q }}" class="form-control" style="max-width:360px" placeholder="Search name or email...">
      <button class="btn btn-outline-primary"><i class="ri-filter-3-line me-1"></i> Apply</button>
      @if($q)
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Clear</a>
      @endif
      <div class="ms-auto d-flex gap-2 flex-wrap">
        <span class="zmc-pill"><i class="ri-shield-user-line"></i> Staff: <strong>{{ $counts['staff'] ?? 0 }}</strong></span>
        <span class="zmc-pill"><i class="ri-user-smile-line"></i> Public: <strong>{{ $counts['public'] ?? 0 }}</strong></span>
      </div>
    </div>
  </form>

  <div class="row g-4">
    {{-- Staff Users --}}
    <div class="col-12 col-xl-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <div class="fw-bold"><i class="ri-shield-user-line me-2"></i>Staff Users</div>
          <span class="badge bg-dark">{{ $counts['staff'] ?? 0 }}</span>
        </div>
        <div class="table-responsive">
          <table class="table zmc-table-lite mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Roles</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($staffUsers as $u)
                <tr>
                  <td>{{ $u->id }}</td>
                  <td class="fw-bold">{{ $u->name }}</td>
                  <td class="text-muted">{{ $u->email }}</td>
                  <td>
                    @php($r = $u->roles->pluck('name')->values())
                    @if($r->isEmpty())
                      <span class="text-muted">—</span>
                    @else
                      <div class="d-flex flex-wrap gap-1">
                        @foreach($r as $rn)
                          <span class="badge bg-light text-dark" style="border:1px solid rgba(15,23,42,.12)">{{ str_replace('_',' ', $rn) }}</span>
                        @endforeach
                      </div>
                    @endif
                  </td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.access.edit', $u) }}">
                      <i class="ri-user-settings-line me-1"></i> Access
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No staff users found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-body pt-2">
          {{ $staffUsers->links() }}
        </div>
      </div>
    </div>

    {{-- Public Users --}}
    <div class="col-12 col-xl-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <div class="fw-bold"><i class="ri-user-smile-line me-2"></i>Public Users</div>
          <span class="badge bg-dark">{{ $counts['public'] ?? 0 }}</span>
        </div>
        <div class="table-responsive">
          <table class="table zmc-table-lite mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($publicUsers as $u)
                <tr>
                  <td>{{ $u->id }}</td>
                  <td class="fw-bold">{{ $u->name }}</td>
                  <td class="text-muted">{{ $u->email }}</td>
                  <td>
                    @php($status = $u->account_status ?? 'active')
                    <span class="badge rounded-pill bg-{{ $status === 'active' ? 'success' : 'secondary' }} px-3">
                      {{ strtoupper($status) }}
                    </span>
                  </td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.access.edit', $u) }}">
                      <i class="ri-user-settings-line me-1"></i> Access
                    </a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No public users found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-body pt-2">
          {{ $publicUsers->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
