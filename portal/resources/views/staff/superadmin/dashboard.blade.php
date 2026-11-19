@extends('layouts.staff')

@section('title', $title)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $title }}</h2>
            <p class="text-muted mb-0">Unrestricted system overview. You can navigate to any module from here.</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('staff.reports.index') }}" class="btn btn-primary">Operational Reports</a>
            <a href="{{ route('executive.dashboard') }}" class="btn btn-outline-primary">Open Admin Panel</a>
            <a href="{{ route('staff.auditor.dashboard') }}" class="btn btn-outline-secondary">Audit Trail</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['Total Applications',   $kpis['total_applications'] ?? 0, 'primary'],
                ['Pending Officer',      $kpis['pending_officer'] ?? 0, 'info'],
                ['Pending Registrar',    $kpis['pending_registrar'] ?? 0, 'warning'],
                ['Pending Accounts',     $kpis['pending_accounts'] ?? 0, 'warning'],
                ['In Production',        $kpis['in_production'] ?? 0, 'secondary'],
                ['Issued',               $kpis['issued'] ?? 0, 'success'],
                ['Rejected',             $kpis['rejected'] ?? 0, 'danger'],
                ['Total Revenue (USD)',  number_format($kpis['total_revenue'] ?? 0, 2), 'success'],
                ['Total Users',          $kpis['total_users'] ?? 0, 'dark'],
                ['Staff Users',          $kpis['staff_users'] ?? 0, 'dark'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $color])
            <div class="col-md-3 col-sm-4 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase small text-muted">{{ $label }}</div>
                        <div class="h4 mb-0 text-{{ $color }}">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Recent Applications</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Ref</th><th>Applicant</th><th>Type</th><th>Status</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                        @foreach($recentApplications as $a)
                            <tr>
                                <td>{{ $a->reference_code ?? $a->id }}</td>
                                <td>{{ optional($a->applicant)->name ?? '—' }}</td>
                                <td>{{ ucfirst($a->application_type ?? '—') }}</td>
                                <td><span class="badge bg-secondary">{{ str_replace('_',' ', $a->status) }}</span></td>
                                <td>{{ optional($a->created_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                        @if($recentApplications->isEmpty())
                            <tr><td colspan="5" class="text-center text-muted py-4">No applications yet.</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white"><strong>Applications — Last 12 Months</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Month</th><th class="text-end">Count</th></tr></thead>
                        <tbody>
                        @foreach($monthlyTrend as $row)
                            <tr><td>{{ $row->ym }}</td><td class="text-end">{{ $row->c }}</td></tr>
                        @endforeach
                        @if($monthlyTrend->isEmpty())
                            <tr><td colspan="2" class="text-center text-muted">No data.</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white"><strong>Quick Links</strong></div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('staff.reports.index') }}" class="list-group-item list-group-item-action fw-bold text-primary"><i class="ri-bar-chart-line me-1"></i> Operational Reports</a>
                    <a href="{{ route('staff.officer.dashboard') }}" class="list-group-item list-group-item-action">Accreditation Officer</a>
                    <a href="{{ route('staff.registrar.dashboard') }}" class="list-group-item list-group-item-action">Registrar</a>
                    <a href="{{ route('staff.accounts.dashboard') }}" class="list-group-item list-group-item-action">Accounts Officer</a>
                    <a href="{{ route('staff.director.dashboard') }}" class="list-group-item list-group-item-action">Director Media Development</a>
                    <a href="{{ route('staff.public_info_compliance.dashboard') }}" class="list-group-item list-group-item-action">PR / Public Info Compliance</a>
                    @if(\Route::has('staff.research.dashboard'))
                        <a href="{{ route('staff.research.dashboard') }}" class="list-group-item list-group-item-action">Research, Training &amp; Development</a>
                    @endif
                    <a href="{{ route('staff.it.dashboard') }}" class="list-group-item list-group-item-action">IT Administration</a>
                    <a href="{{ route('staff.auditor.dashboard') }}" class="list-group-item list-group-item-action">Auditor</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
