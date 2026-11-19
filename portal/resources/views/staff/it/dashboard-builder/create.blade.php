@extends('layouts.portal')

@section('title', 'Create Dashboard')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
                <i class="ri-dashboard-3-line me-2"></i>Create New Dashboard
            </h4>
            <div class="text-muted mt-1" style="font-size:13px;">Set up a new custom dashboard</div>
        </div>
        <a href="{{ route('staff.it.dashboard-builder') }}" class="btn btn-sm btn-outline-dark">
            <i class="ri-arrow-left-line me-1"></i> Back
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('staff.it.dashboard-builder.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Dashboard Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Accounts Overview Dashboard" value="{{ old('name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Assign to Role</label>
                        <select name="role_slug" class="form-select">
                            <option value="">-- All Roles --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this dashboard">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" checked>
                            <label for="isActive" class="form-check-label">Active</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-dark">
                            <i class="ri-save-line me-1"></i> Create Dashboard
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
