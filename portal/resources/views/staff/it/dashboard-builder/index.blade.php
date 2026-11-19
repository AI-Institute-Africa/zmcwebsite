@extends('layouts.portal')

@section('title', 'Dashboard Builder')

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
            <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
                <i class="ri-dashboard-3-line me-2"></i>Dashboard Builder
            </h4>
            <div class="text-muted mt-1" style="font-size:13px;">Create and manage custom dashboards for different roles</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.it.dashboard-builder.create') }}" class="btn btn-sm btn-dark">
                <i class="ri-add-line me-1"></i> New Dashboard
            </a>
            <a href="{{ route('staff.it.dashboard') }}" class="btn btn-sm btn-outline-dark">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
    </div>

    @if($dashboards->isEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="ri-dashboard-3-line" style="font-size:48px; opacity:0.3;"></i>
                <p class="mt-2 text-muted">No custom dashboards created yet.</p>
                <a href="{{ route('staff.it.dashboard-builder.create') }}" class="btn btn-sm btn-dark">
                    <i class="ri-add-line me-1"></i> Create First Dashboard
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($dashboards as $dashboard)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0">{{ $dashboard->name }}</h6>
                            @if($dashboard->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-warning text-dark">Inactive</span>
                            @endif
                        </div>
                        @if($dashboard->description)
                            <p class="text-muted mb-2" style="font-size:12px;">{{ Str::limit($dashboard->description, 100) }}</p>
                        @endif
                        <div class="d-flex gap-2 mb-3" style="font-size:12px;">
                            @if($dashboard->role_slug)
                                <span class="badge bg-dark">{{ ucwords(str_replace('_', ' ', $dashboard->role_slug)) }}</span>
                            @else
                                <span class="badge bg-secondary">No role assigned</span>
                            @endif
                            <span class="badge bg-light text-dark border">{{ $dashboard->widgets_count }} widget(s)</span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('staff.it.dashboard-builder.edit', $dashboard) }}" class="btn btn-sm btn-outline-dark">
                                <i class="ri-pencil-line me-1"></i>Edit
                            </a>
                            <a href="{{ route('staff.it.dashboard-builder.preview', $dashboard) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ri-eye-line me-1"></i>Preview
                            </a>
                            <form action="{{ route('staff.it.dashboard-builder.delete', $dashboard) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this dashboard?')">
                                @csrf
                                <button class="btn btn-sm zmc-icon-btn btn-outline-danger">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
