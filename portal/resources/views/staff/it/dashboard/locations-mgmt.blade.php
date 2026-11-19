@extends('layouts.portal')

@section('title', 'Locations Management')

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
            <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Region & Location Management</h4>
            <div class="text-muted mt-1" style="font-size:13px;">Manage service delivery points and operational locations</div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                <i class="ri-map-pin-add-line me-1"></i> Add Location
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
                    <h4 class="mb-0 fw-bold text-dark">{{ $totalCount }}</h4>
                    <small class="text-muted fw-medium">Total Locations</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0 fw-bold text-success">{{ $activeCount }}</h4>
                    <small class="text-muted fw-medium">Active</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0 fw-bold text-danger">{{ $totalCount - $activeCount }}</h4>
                    <small class="text-muted fw-medium">Disabled</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center py-3">
                    <h4 class="mb-0 fw-bold text-primary">{{ $regions->count() }}</h4>
                    <small class="text-muted fw-medium">Regions</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="ri-global-line me-2 text-primary"></i>Regions</h6>
            <button class="btn btn-sm btn-outline-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createRegionModal">
                <i class="ri-add-line me-1"></i> Add Region
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Region Name</th>
                        <th>Locations</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allRegions as $reg)
                    <tr>
                        <td class="ps-4 fw-semibold" style="color:#334155;">{{ $reg->name }}</td>
                        <td class="text-muted">{{ $reg->locations_count ?? $reg->locations()->count() }}</td>
                        <td>
                            <span class="badge {{ $reg->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill border">
                                {{ $reg->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <form method="POST" action="{{ route('staff.it.regions.toggle', $reg) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm {{ $reg->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill px-3">
                                    {{ $reg->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No regions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h6 class="fw-bold mb-0"><i class="ri-map-pin-line me-2 text-primary"></i>All Locations</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Location Name</th>
                        <th>Type</th>
                        <th>Region</th>
                        <th>Address</th>
                        <th>Active Period</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $loc)
                    <tr>
                        <td class="ps-4 fw-semibold" style="color:#334155;">{{ $loc->name }}</td>
                        <td>
                            @php
                                $typeColors = [
                                    'head_office' => 'bg-dark text-white',
                                    'regional_office' => 'bg-primary-subtle text-primary',
                                    'temporary_outreach' => 'bg-warning-subtle text-warning',
                                    'mobile_service' => 'bg-info-subtle text-info',
                                ];
                            @endphp
                            <span class="badge {{ $typeColors[$loc->location_type] ?? 'bg-light text-secondary' }} rounded-pill border">
                                {{ ucwords(str_replace('_', ' ', $loc->location_type)) }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $loc->region->name ?? '—' }}</td>
                        <td class="text-muted small" style="max-width:200px;">{{ Str::limit($loc->address ?? '—', 40) }}</td>
                        <td class="small">
                            @if($loc->start_date)
                                {{ \Carbon\Carbon::parse($loc->start_date)->format('d M Y') }}
                                @if($loc->end_date)
                                    <span class="text-muted">to {{ \Carbon\Carbon::parse($loc->end_date)->format('d M Y') }}</span>
                                @endif
                            @else
                                <span class="text-muted">Permanent</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $loc->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill border">
                                {{ ucfirst($loc->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <form method="POST" action="{{ route('staff.it.locations.toggle', $loc) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm {{ $loc->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill px-3">
                                    {{ $loc->status === 'active' ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No locations found. Create one to get started.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($locations->hasPages())
        <div class="card-footer bg-white border-0 px-4 py-3">
            {{ $locations->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="createLocationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('staff.it.locations.store') }}" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-map-pin-add-line me-2 text-primary"></i>Create Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Location Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Harare Head Office" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Location Type</label>
                        <select name="location_type" class="form-select" required>
                            <option value="head_office">Head Office</option>
                            <option value="regional_office">Regional Office</option>
                            <option value="temporary_outreach">Temporary Outreach</option>
                            <option value="mobile_service">Mobile Service</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Region</label>
                        <select name="region_id" class="form-select">
                            <option value="">None</option>
                            @foreach($regions as $reg)
                                <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Physical address">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Start Date (Optional)</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">End Date (Optional)</label>
                        <input type="date" name="end_date" class="form-control">
                        <div class="form-text small">Leave blank for permanent locations.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4">Create Location</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="createRegionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('staff.it.regions.store') }}" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-global-line me-2 text-primary"></i>Create Region</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Region Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Harare Metropolitan" required>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4">Create Region</button>
            </div>
        </form>
    </div>
</div>

<style>
    nav[role="navigation"] svg, .pagination svg { width: 14px !important; height: 14px !important; }
</style>
@endsection
