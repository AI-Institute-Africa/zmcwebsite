@extends('layouts.portal')
@section('title', 'Returned Applications')

@section('content')
@include('staff.partials.dashboard_theme')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold m-0">Returned Applications</h4>
                <p class="text-muted small mb-0">Track applications returned from the Registrar</p>
            </div>
            <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-light border btn-sm">
                <i class="ri-dashboard-line me-1"></i>Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="ri-checkbox-circle-line me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Filters --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="awaiting_officer" {{ request('status') === 'awaiting_officer' ? 'selected' : '' }}>
                                Awaiting Officer Action</option>
                            <option value="officer_fixed" {{ request('status') === 'officer_fixed' ? 'selected' : '' }}>Fixed
                                by Officer</option>
                            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm me-2">
                            <i class="ri-filter-line me-1"></i>Filter
                        </button>
                        <a href="{{ route('staff.registrar.fix-requests') }}" class="btn btn-light border btn-sm">
                            <i class="ri-refresh-line"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Returned Applications Table --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-3 small fw-bold">Application</th>
                                <th class="px-3 py-3 small fw-bold">Request Type</th>
                                <th class="px-3 py-3 small fw-bold">Description</th>
                                <th class="px-3 py-3 small fw-bold">Status</th>
                                <th class="px-3 py-3 small fw-bold">Requested</th>
                                <th class="px-3 py-3 small fw-bold">Resolved</th>
                                <th class="px-3 py-3 small fw-bold text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fixRequests as $fixRequest)
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold">{{ $fixRequest->application?->reference ?? '—' }}</div>
                                        <div class="small text-muted">{{ $fixRequest->application?->applicant?->name ?? '—' }}
                                        </div>
                                    </td>
                                    <td class="px-3">
                                        <span class="badge bg-light text-dark border">
                                            {{ str_replace('_', ' ', ucwords($fixRequest->request_type)) }}
                                        </span>
                                    </td>
                                    <td class="px-3">
                                        <div class="small" style="max-width: 300px;">
                                            {{ Str::limit($fixRequest->description, 100) }}
                                        </div>
                                    </td>
                                    <td class="px-3">
                                        @php
                                            $appStatus = strtolower((string) ($fixRequest->application?->status ?? ''));
                                            if ($fixRequest->status === 'resolved') {
                                                $displayStatus = 'Resolved';
                                                $color = 'success';
                                            } elseif ($appStatus === \App\Models\Application::REGISTRAR_FIX_REQUEST) {
                                                $displayStatus = 'Awaiting Officer Action';
                                                $color = 'warning';
                                            } else {
                                                $displayStatus = 'Fixed by Officer';
                                                $color = 'info';
                                            }
                                        @endphp
                                        <span class="badge bg-{{ $color }}">
                                            {{ $displayStatus }}
                                        </span>
                                    </td>
                                    <td class="px-3 small">
                                        {{ $fixRequest->created_at->format('M d, Y') }}
                                        <div class="text-muted">{{ $fixRequest->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-3 small">
                                        @if($fixRequest->resolved_at)
                                            {{ $fixRequest->resolved_at->format('M d, Y') }}
                                            <div class="text-muted">{{ $fixRequest->resolver->name ?? '—' }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 text-end">
                                        <a href="{{ route('staff.registrar.applications.show', $fixRequest->application) }}"
                                            class="btn btn-sm btn-light border">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ri-inbox-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <div class="mt-2">No returned applications found</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($fixRequests->hasPages())
                <div class="card-footer bg-white border-top">
                    {{ $fixRequests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
