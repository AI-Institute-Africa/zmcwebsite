@extends('layouts.portal')
@section('title', 'Physical Intake Applications - IT Admin')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <h2 class="fw-bold m-0" style="color:#0f172a;">Physical Intake Applications</h2>
            <p class="text-muted m-0 small mt-1">View, create, and edit walk-in applications entered by accreditation officers</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.it.intake.create') }}" class="btn btn-primary rounded-pill px-3 fw-bold small">
                <i class="ri-add-line me-1"></i> New Intake
            </a>
            <a href="{{ route('staff.it.dashboard') }}" class="btn btn-light rounded-pill px-3 fw-bold small border">
                <i class="ri-arrow-left-line me-1"></i> Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2 rounded-3 shadow-sm">
            <i class="ri-checkbox-circle-line" style="font-size:18px;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2 rounded-3 shadow-sm">
            <i class="ri-error-warning-line" style="font-size:18px;"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Reference</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Applicant</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Type</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Request</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Status</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Intake Officer</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Date</th>
                        <th class="fw-semibold text-uppercase text-end pe-4" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $getFormVal = function($app, $key) {
                            $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
                            return $fd[$key] ?? null;
                        };
                    @endphp
                    @forelse($intakeApps as $app)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold" style="color:#334155;">{{ $app->reference }}</span>
                        </td>
                        <td>
                            <div class="fw-bold" style="color:#334155;">{{ $app->applicant?->name ?? $getFormVal($app, 'first_name') . ' ' . ($getFormVal($app, 'surname') ?? '') }}</div>
                            <div class="text-muted small">{{ $app->applicant?->email ?? $getFormVal($app, 'email') }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border rounded-pill">{{ ucfirst($app->application_type ?? 'N/A') }}</span></td>
                        <td><span class="badge bg-light text-dark border rounded-pill">{{ ucfirst($app->request_type ?? 'N/A') }}</span></td>
                        <td>
                            @php
                                $statusColors = [
                                    'production_queue' => 'bg-info-subtle text-info',
                                    'draft' => 'bg-warning-subtle text-warning',
                                    'incomplete' => 'bg-secondary-subtle text-secondary',
                                    'approved' => 'bg-success-subtle text-success',
                                    'printed' => 'bg-primary-subtle text-primary',
                                ];
                                $sClass = $statusColors[$app->status] ?? 'bg-secondary-subtle text-secondary';
                            @endphp
                            <span class="badge {{ $sClass }} border rounded-pill">{{ ucwords(str_replace('_', ' ', $app->status)) }}</span>
                        </td>
                        <td class="text-muted">{{ $getFormVal($app, 'intake_officer') ?? '—' }}</td>
                        <td class="text-muted fw-medium">{{ $app->updated_at->format('d M Y H:i') }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('staff.it.intake.edit', $app) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="ri-edit-line me-1"></i>Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted fw-medium">No physical intake applications found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($intakeApps->hasPages())
        <div class="p-4 border-top">{{ $intakeApps->links() }}</div>
        @endif
    </div>
</div>
@endsection
