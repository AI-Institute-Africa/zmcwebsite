@extends('layouts.portal')
@section('title', 'Accreditation Performance Metrics')

@section('content')
@php
    $kpis = $kpis ?? ['total_processed'=>0,'total_verified'=>0,'total_approved'=>0,'total_rejected'=>0,'total_reassign'=>0,'avg_approval_rate'=>0,'active_officers'=>0];
    $period = $period ?? 'month';
    $periodLabel = $periodLabel ?? 'This Month';
@endphp
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-4 gap-3">
        <div>
            <a href="{{ route('staff.director.dashboard') }}" class="btn btn-sm btn-link p-0 text-muted mb-2 text-decoration-none">
                <i class="ri-arrow-left-line"></i> Back to Overview
            </a>
            <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Accreditation Performance & Productivity Metrics</h1>
            <div class="text-muted small mt-1">Period: <strong>{{ $periodLabel }}</strong></div>
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="period" class="form-select form-select-sm" style="min-width:160px;" onchange="this.form.submit()">
                <option value="week"    @selected($period==='week')>This Week</option>
                <option value="month"   @selected($period==='month')>This Month</option>
                <option value="quarter" @selected($period==='quarter')>This Quarter</option>
                <option value="year"    @selected($period==='year')>This Year</option>
                <option value="all"     @selected($period==='all')>All Time</option>
            </select>
            <a href="{{ route('staff.director.reports.staff', ['period'=>$period,'export'=>'csv']) }}" class="btn btn-sm btn-outline-dark">
                <i class="ri-download-2-line"></i> Export CSV
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="ri-printer-line"></i> Print
            </button>
        </form>
    </div>

    {{-- KPI Strip --}}
    <div class="row g-3 mb-4">
        @php
            $kpiCards = [
                ['Applications Processed', $kpis['total_processed'], 'ri-file-list-3-line', '#3b82f6'],
                ['Active Officers',        $kpis['active_officers'], 'ri-user-star-line',   '#0ea5e9'],
                ['Approved',               $kpis['total_approved'],  'ri-checkbox-circle-line', '#10b981'],
                ['Returned',               $kpis['total_rejected'],  'ri-close-circle-line', '#ef4444'],
                ['Avg Approval Rate',      number_format($kpis['avg_approval_rate'],1).'%', 'ri-percent-line', '#8b5cf6'],
                ['Payments Verified',      $kpis['total_verified'],  'ri-bank-card-line',   '#14b8a6'],
                ['Cat. Reassignments',     $kpis['total_reassign'],  'ri-exchange-line',    '#f59e0b'],
            ];
        @endphp
        @foreach($kpiCards as [$label, $value, $icon, $color])
        <div class="col-6 col-md-4 col-lg">
            <div class="zmc-card h-100" style="border-left:3px solid {{ $color }};">
                <div class="d-flex align-items-center gap-2">
                    <i class="{{ $icon }}" style="color:{{ $color }}; font-size:1.5rem;"></i>
                    <div class="text-muted smaller">{{ $label }}</div>
                </div>
                <div class="fw-bold mt-2" style="font-size:1.75rem; color:#1e293b;">{{ is_numeric($value) ? number_format($value) : $value }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4">
        <!-- Applications Processed Per Officer -->
        <div class="col-md-12">
            <div class="zmc-card">
                <h2 class="fw-bold mb-3">Applications Processed by Accreditation Officers & Registrars</h2>
                @if($applicationsProcessed->where('processed_count','>',0)->count() === 0)
                    <div class="text-center text-muted py-4"><i class="ri-inbox-line" style="font-size:2rem;"></i><div class="mt-2">No processing activity in {{ strtolower($periodLabel) }}.</div></div>
                @else
                    <div style="height: 320px;"><canvas id="officerProcessedChart"></canvas></div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr class="smaller text-muted"><th>Staff Member</th><th>Email</th><th class="text-end">Processed</th></tr></thead>
                            <tbody>
                            @foreach($applicationsProcessed as $u)
                                <tr><td class="fw-bold">{{ $u->name }}</td><td class="text-muted small">{{ $u->email }}</td><td class="text-end">{{ number_format($u->processed_count) }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payment Verification Volume -->
        <div class="col-md-6">
            <div class="zmc-card h-100">
                <h3 class="fw-bold mb-3">Payment Verification Volume (Accounts Staff)</h3>
                @if($paymentVerificationVolume->isEmpty())
                    <div class="text-center text-muted py-4"><i class="ri-inbox-line" style="font-size:2rem;"></i><div class="mt-2">No accounts staff on record.</div></div>
                @elseif($paymentVerificationVolume->where('verified_count','>',0)->count() === 0)
                    <div class="text-center text-muted py-4"><i class="ri-bank-card-line" style="font-size:2rem;"></i><div class="mt-2">No payments verified in {{ strtolower($periodLabel) }}.</div></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr class="smaller text-muted"><th>Staff Member</th><th class="text-end">Payments Verified</th></tr></thead>
                            <tbody>
                            @foreach($paymentVerificationVolume as $staff)
                                <tr><td class="fw-bold">{{ $staff->name }}</td><td class="text-end">{{ number_format($staff->verified_count) }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Approval Distribution Per Officer -->
        <div class="col-md-6">
            <div class="zmc-card h-100">
                <h4 class="fw-bold mb-3">Approval Distribution by Officers</h4>
                @if($approvalDistribution->where('total_reviewed','>',0)->count() === 0)
                    <div class="text-center text-muted py-4"><i class="ri-pie-chart-line" style="font-size:2rem;"></i><div class="mt-2">No approval decisions in {{ strtolower($periodLabel) }}.</div></div>
                @else
                    <div style="height: 280px;"><canvas id="approvalDistributionChart"></canvas></div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr class="smaller text-muted"><th>Officer</th><th class="text-end">Approved</th><th class="text-end">Returned</th><th class="text-end">Rate</th></tr></thead>
                            <tbody>
                            @foreach($approvalDistribution as $o)
                                @if($o->total_reviewed > 0)
                                <tr>
                                    <td class="fw-bold">{{ $o->name }}</td>
                                    <td class="text-end text-success">{{ $o->approved_count }}</td>
                                    <td class="text-end text-danger">{{ $o->rejected_count }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $o->approval_rate >= 80 ? 'success' : ($o->approval_rate >= 50 ? 'warning' : 'danger') }}">{{ $o->approval_rate }}%</span>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Category Reassignment Frequency -->
        <div class="col-md-12">
            <div class="zmc-card">
                <h5 class="fw-bold mb-3"><i class="ri-exchange-line text-warning me-2"></i>Category Reassignment Frequency</h5>
                @if($reassignmentFrequency->isEmpty())
                    <div class="text-center text-muted py-4"><i class="ri-checkbox-circle-line text-success" style="font-size:2rem;"></i><div class="mt-2">No category reassignments in {{ strtolower($periodLabel) }} — clean processing.</div></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead><tr class="smaller text-muted"><th>Staff Member</th><th>Role</th><th class="text-end">Reassignments</th><th class="text-end">Risk Level</th></tr></thead>
                            <tbody>
                            @foreach($reassignmentFrequency as $staff)
                            <tr>
                                <td class="fw-bold">{{ $staff->name }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', (string)($staff->role ?? $staff->user_role ?? '—'))) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $staff->reassignment_count < 3 ? 'success' : ($staff->reassignment_count < 5 ? 'warning' : 'danger') }}">{{ $staff->reassignment_count }}</span>
                                </td>
                                <td class="text-end">
                                    @if($staff->reassignment_count < 3)<span class="badge bg-success">Low</span>
                                    @elseif($staff->reassignment_count < 5)<span class="badge bg-warning">Medium</span>
                                    @else<span class="badge bg-danger">High</span>@endif
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function initCharts() {
        if (typeof Chart === 'undefined') { setTimeout(initCharts, 100); return; }

        const processedCanvas = document.getElementById('officerProcessedChart');
        if (processedCanvas) {
            new Chart(processedCanvas, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($applicationsProcessed->pluck('name')->toArray()) !!},
                    datasets: [{
                        label: 'Applications Processed',
                        data: {!! json_encode($applicationsProcessed->pluck('processed_count')->map(fn($v)=>(int)$v)->toArray()) !!},
                        backgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }

        const approvalCanvas = document.getElementById('approvalDistributionChart');
        if (approvalCanvas) {
            const items = {!! json_encode($approvalDistribution->where('total_reviewed','>',0)->values()->map(fn($o)=>['name'=>$o->name,'approved'=>(int)$o->approved_count,'rejected'=>(int)$o->rejected_count])) !!};
            new Chart(approvalCanvas, {
                type: 'bar',
                data: {
                    labels: items.map(i => i.name),
                    datasets: [
                        { label: 'Approved', data: items.map(i => i.approved), backgroundColor: '#10b981' },
                        { label: 'Returned for Correction', data: items.map(i => i.rejected), backgroundColor: '#ef4444' }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCharts);
    else initCharts();
})();
</script>
@endsection
