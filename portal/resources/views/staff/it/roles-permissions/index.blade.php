@extends('layouts.portal')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid px-4">

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-line" style="font-size:18px;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
            <i class="ri-error-warning-line" style="font-size:18px;"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
                <i class="ri-shield-user-line me-2"></i>Roles & Permissions
            </h1>
            <div class="text-muted mt-1" style="font-size:13px;">Manage system roles, permissions, and access control</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="ri-add-line me-1"></i> New Role
            </button>
            <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                <i class="ri-key-line me-1"></i> New Permission
            </button>
            <a href="{{ route('staff.it.dashboard') }}" class="btn btn-sm btn-outline-dark">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="fw-bold m-0"><i class="ri-user-settings-line me-1"></i> Roles ({{ $roles->count() }})</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:13px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Role</th>
                                    <th>Permissions</th>
                                    <th>Users</th>
                                    <th>Guard</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                                        @if(in_array($role->name, ['super_admin', 'it_admin']))
                                            <span class="badge bg-danger ms-1" style="font-size:9px;">Protected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $role->permissions_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark">{{ $role->users_count }}</span>
                                    </td>
                                    <td><code style="font-size:11px;">{{ $role->guard_name }}</code></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('staff.it.roles-permissions.edit-role', $role) }}" class="btn btn-outline-dark" title="Edit permissions">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                            @unless(in_array($role->name, ['super_admin', 'it_admin']))
                                            <form action="{{ route('staff.it.roles-permissions.delete-role', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete role {{ $role->name }}?')">
                                                @csrf
                                                <button class="btn btn-outline-danger" title="Delete">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold m-0"><i class="ri-key-2-line me-1"></i> Permissions ({{ $permissions->count() }})</h3>
                </div>
                <div class="card-body p-0" style="max-height:500px; overflow-y:auto;">
                    @foreach($permissionGroups as $group => $perms)
                        <div class="px-3 py-2 bg-light border-bottom">
                            <strong style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">{{ $group }}</strong>
                            <span class="badge bg-secondary ms-1" style="font-size:10px;">{{ $perms->count() }}</span>
                        </div>
                        @foreach($perms as $perm)
                        <div class="d-flex justify-content-between align-items-center px-3 py-1 border-bottom" style="font-size:12px;">
                            <span>{{ $perm->name }}</span>
                            <form action="{{ route('staff.it.roles-permissions.delete-permission', $perm) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete permission {{ $perm->name }}?')">
                                @csrf
                                <button class="btn btn-link btn-sm text-danger p-0" title="Delete">
                                    <i class="ri-close-line"></i>
                                </button>
                            </form>
                        </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h4 class="fw-bold m-0"><i class="ri-user-add-line me-1"></i> Quick Role Assignment</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.it.roles-permissions.assign-role') }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size:13px;">User ID</label>
                            <input type="number" name="user_id" class="form-control form-control-sm" required placeholder="Enter user ID">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold" style="font-size:13px;">Role</label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="">Select role...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ ucwords(str_replace('_', ' ', $role->name)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm">
                            <i class="ri-user-add-line me-1"></i> Assign
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.it.roles-permissions.create-role') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. finance_officer">
                        <div class="form-text">Use snake_case (lowercase, underscores). Spaces will be converted automatically.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.it.roles-permissions.create-permission') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Permission Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. manage_finances">
                        <div class="form-text">Use snake_case. Spaces will be converted automatically.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">Create Permission</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
