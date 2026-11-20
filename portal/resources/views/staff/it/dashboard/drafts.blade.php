@extends('layouts.portal')
@section('title', 'Applicant Drafts - IT Admin')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <h1 class="fw-bold m-0" style="color:#0f172a;">Applicant Drafts</h1>
            <p class="text-muted m-0 small mt-1">Monitor draft and incomplete applications</p>
        </div>
        <span class="badge bg-warning-subtle text-warning border px-3 py-2 rounded-pill fw-bold">{{ $totalDrafts }} Total Drafts</span>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white p-4 border-0">
            <form class="d-flex gap-2">
                <input type="text" name="q" class="form-control rounded-3" placeholder="Search applicant..." value="{{ request('q') }}">
                <select name="type" class="form-select rounded-3" style="max-width:200px;">
                    <option value="">All Types</option>
                    <option value="new" {{ request('type') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="renewal" {{ request('type') === 'renewal' ? 'selected' : '' }}>Renewal</option>
                </select>
                <button class="btn btn-dark rounded-pill px-4 fw-bold">Filter</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Applicant</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Type</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Status</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drafts as $d)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold" style="color:#334155;">{{ $d->applicant?->name ?? 'Unknown' }}</div>
                            <div class="text-muted small">{{ $d->applicant?->email }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border rounded-pill">{{ ucfirst($d->application_type ?? 'N/A') }}</span></td>
                        <td><span class="badge bg-warning-subtle text-warning border rounded-pill">Draft</span></td>
                        <td class="text-muted fw-medium">{{ $d->updated_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-5 text-muted fw-medium">No draft applications found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($drafts->hasPages())
        <div class="p-4 border-top">{{ $drafts->links() }}</div>
        @endif
    </div>
</div>
@endsection
