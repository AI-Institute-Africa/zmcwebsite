@extends('layouts.portal')

@section('title', 'Temporary Role Assignments')

@section('content')
<div class="container-fluid px-4" style="font-family:'Inter',sans-serif;">

    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-checkbox-circle-fill fs-5"></i> {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-error-warning-fill fs-5"></i> {{ $errors->first() }}
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Temporary Role Assignments</h1>
            <div class="text-muted mt-1" style="font-size:13px;">Manage time-bound role delegations for staff coverage</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#createTempRoleModal">
                <i class="ri-user-add-line me-1"></i> Assign Temp Role
            </button>
            <a href="{{ route('staff.it.dashboard') }}" class="btn btn-sm btn-outline-dark">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <h2 class="mb-0 fw-bold text-primary">{{ $activeCount }}</h2>
                    <small class="text-muted fw-medium">Active Assignments</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <h3 class="mb-0 fw-bold text-warning">{{ $expiredCount }}</h3>
                    <small class="text-muted fw-medium">Expired (Pending Cleanup)</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0 fw-bold text-dark">{{ $assignments->total() }}</h4>
                    <small class="text-muted fw-medium">Total Records</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0 fw-bold text-success">{{ $roles->count() }}</h4>
                    <small class="text-muted fw-medium">Available Roles</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0"><i class="ri-swap-line me-2 text-primary"></i>Assignment History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Base Role</th>
                        <th>Temp Role</th>
                        <th>Region</th>
                        <th>Period</th>
                        <th>Assigned By</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $a)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold">{{ $a->user->name ?? '—' }}</div>
                            <div class="text-muted small">{{ $a->user->email ?? '' }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border rounded-pill">{{ ucwords(str_replace('_', ' ', $a->base_role ?? '—')) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border rounded-pill">{{ ucwords(str_replace('_', ' ', $a->temporary_role)) }}</span>
                        </td>
                        <td class="text-muted">{{ $a->region->name ?? 'All' }}</td>
                        <td>
                            <div class="small">{{ $a->starts_at->format('d M Y') }}</div>
                            <div class="small text-muted">to {{ $a->ends_at->format('d M Y') }}</div>
                        </td>
                        <td class="text-muted small">{{ $a->assigner->name ?? '—' }}</td>
                        <td>
                            @if($a->status === 'revoked')
                                <span class="badge bg-danger-subtle text-danger border rounded-pill">Revoked</span>
                            @elseif($a->isExpired())
                                <span class="badge bg-warning-subtle text-warning border rounded-pill">Expired</span>
                            @else
                                <span class="badge bg-success-subtle text-success border rounded-pill">Active</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($a->status === 'active' && !$a->isExpired())
                            <form method="POST" action="{{ route('staff.it.temp-role.revoke', $a) }}" class="d-inline" onsubmit="return confirm('Revoke this temporary role?')">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                    <i class="ri-close-circle-line me-1"></i> Revoke
                                </button>
                            </form>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No temporary role assignments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assignments->hasPages())
        <div class="card-footer bg-white border-0 px-4 py-3">
            {{ $assignments->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="createTempRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('staff.it.temp-role.store') }}" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-user-shared-line me-2 text-primary"></i>Assign Temporary Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Staff Member</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select staff user</option>
                            @foreach($staffUsers as $su)
                                <option value="{{ $su->id }}">{{ $su->name }} ({{ $su->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Temporary Role</label>
                        <select name="temporary_role" class="form-select" required>
                            <option value="">Select role</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->name }}">{{ ucwords(str_replace('_', ' ', $r->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Region (Optional)</label>
                        <select name="region_id" class="form-select">
                            <option value="">All Regions</option>
                            @foreach($regions as $reg)
                                <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Start Date</label>
                        <input type="date" name="starts_at" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">End Date</label>
                        <input type="date" name="ends_at" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Reason</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="e.g. Staff leave cover, training delegation..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4">Assign Role</button>
            </div>
        </form>
    </div>
</div>

<style>
    nav[role="navigation"] svg, .pagination svg { width: 14px !important; height: 14px !important; }
</style>
@endsection
