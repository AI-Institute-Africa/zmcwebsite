@extends('layouts.portal')

@section('title', 'Renewals Production')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Renewals Production</h1>
            <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
                <i class="ri-information-line me-1"></i>Generate renewed certificates for renewals whose payment has been
                verified.
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('staff.officer.renewals.requests') }}" class="btn btn-white border shadow-sm btn-sm px-3">
                <i class="ri-file-list-3-line me-1"></i>Renewal Requests
            </a>
            <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Refresh">
                <i class="ri-refresh-line me-1"></i>Refresh
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase" style="font-size: var(--font-size-xs); letter-spacing:.07em;">
                        Ready for production
                    </div>
                    <div class="fw-bold mt-1 text-primary" style="font-size: var(--font-size-2xl);">
                        {{ number_format($kpis['ready'] ?? 0) }}
                    </div>
                    <div class="text-muted" style="font-size: var(--font-size-xs);">Payment verified</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase" style="font-size: var(--font-size-xs); letter-spacing:.07em;">
                        In production
                    </div>
                    <div class="fw-bold mt-1 text-warning" style="font-size: var(--font-size-2xl);">
                        {{ number_format($kpis['in_production'] ?? 0) }}
                    </div>
                    <div class="text-muted" style="font-size: var(--font-size-xs);">Documents being generated</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase" style="font-size: var(--font-size-xs); letter-spacing:.07em;">
                        Ready for collection
                    </div>
                    <div class="fw-bold mt-1 text-success" style="font-size: var(--font-size-2xl);">
                        {{ number_format($kpis['ready_for_collection'] ?? 0) }}
                    </div>
                    <div class="text-muted" style="font-size: var(--font-size-xs);">Awaiting applicant collection</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold mb-1">Renewal type</label>
                    <select name="renewal_type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        @foreach([
                            'renewal' => 'Renewal',
                            'replacement' => 'Replacement',
                            'accreditation' => 'Accreditation (legacy)',
                            'registration' => 'Registration (legacy)',
                            'permission' => 'Permission',
                        ] as $value => $label)
                            <option value="{{ $value }}" {{ request('renewal_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm px-3"><i class="ri-filter-3-line me-1"></i>Filter</button>
                    <a href="{{ url()->current() }}" class="btn btn-white border btn-sm px-3">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="fw-bold mb-3">Queue ({{ number_format($renewals->total()) }})</h2>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Applicant</th>
                            <th>Original number</th>
                            <th>Payment verified</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($renewals as $renewal)
                            <tr>
                                <td class="fw-semibold">{{ ucfirst($renewal->renewal_type ?: '—') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $renewal->applicant->name ?? '—' }}</div>
                                    <div class="text-muted" style="font-size: var(--font-size-xs);">
                                        {{ $renewal->applicant->email ?? '' }}
                                    </div>
                                </td>
                                <td>{{ $renewal->original_number ?: '—' }}</td>
                                <td>{{ optional($renewal->payment_verified_at)->format('M d, Y H:i') ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ ucwords(str_replace('_', ' ', $renewal->status ?: '—')) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('staff.officer.renewals.production.show', $renewal) }}"
                                        class="btn btn-dark btn-sm px-3">
                                        <i class="ri-printer-line me-1"></i>Process
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No renewals are ready for production.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($renewals, 'links'))
                <div class="mt-3">{{ $renewals->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
