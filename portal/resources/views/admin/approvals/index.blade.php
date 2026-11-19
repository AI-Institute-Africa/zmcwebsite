@extends('layouts.portal')
@section('title','User Approvals')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">User Approvals</h4>
      <div class="text-muted mt-1" style="font-size:13px;">
        Approve or reject accounts created by IT Admin.
      </div>
    </div>
    <div>
      <a href="{{ route('executive.dashboard') }}" class="btn btn-sm btn-outline-dark">
        <i class="ri-arrow-left-line me-1"></i> Back
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0"><i class="ri-user-follow-line me-2" style="color:var(--zmc-accent)"></i> Pending approvals</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        <thead>
          <tr>
            <th style="width:60px;">#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Date</th>
            <th class="text-end" style="min-width:170px;">Action</th>
          </tr>
        </thead>
        <tbody>
        @forelse($pending as $i => $u)
          <tr>
            <td class="text-muted small">{{ $pending->firstItem() + $i }}</td>
            <td class="fw-bold text-dark">{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>
              @foreach($u->roles as $r)
                <span class="badge bg-light text-dark border me-1">{{ strtoupper(str_replace('_',' ', $r->name)) }}</span>
              @endforeach
            </td>
            <td class="small">{{ optional($u->created_at)->format('d M Y') }}</td>
            <td class="text-end">
              <form method="POST" action="{{ route('admin.approvals.approve', $u) }}" class="d-inline">@csrf
                <button class="btn btn-sm btn-outline-success"><i class="ri-check-line"></i> Approve</button>
              </form>
              <form method="POST" action="{{ route('admin.approvals.reject', $u) }}" class="d-inline" onsubmit="return confirm('Reject / suspend this account?')">@csrf
                <button class="btn btn-sm btn-outline-danger"><i class="ri-close-line"></i> Reject</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center py-5 text-muted">No pending approvals.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="mt-3">{{ $pending->links() }}</div>
</div>
@endsection
