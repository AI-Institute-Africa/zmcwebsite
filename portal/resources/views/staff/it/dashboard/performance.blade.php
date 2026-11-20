@extends('layouts.portal')
@section('title', 'Performance - IT Admin')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <h1 class="fw-bold m-0" style="color:#0f172a;">Performance Dashboard</h1>
            <p class="text-muted m-0 small mt-1">Server metrics, response times, and login trends</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.it.dashboard') }}" class="btn btn-light rounded-pill px-3 fw-bold small border"><i class="ri-arrow-left-line"></i> Dashboard</a>
            <button onclick="location.reload()" class="btn btn-dark rounded-pill px-3 fw-bold small"><i class="ri-refresh-line"></i> Refresh</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="display-5 fw-bold" style="color:#0f172a;">{{ $memUsage }} MB</div>
                <p class="text-muted small fw-bold m-0 mt-1">Current Memory Usage</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="display-5 fw-bold" style="color:#0f172a;">{{ $peakMem }} MB</div>
                <p class="text-muted small fw-bold m-0 mt-1">Peak Memory Usage</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="display-5 fw-bold {{ $avgResponseTime > 500 ? 'text-warning' : 'text-success' }}">{{ round($avgResponseTime) }}ms</div>
                <p class="text-muted small fw-bold m-0 mt-1">Avg Response Time (1h)</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="display-5 fw-bold {{ $failedJobsToday > 0 ? 'text-danger' : 'text-success' }}">{{ $failedJobsToday }}</div>
                <p class="text-muted small fw-bold m-0 mt-1">Failed Jobs Today</p>
            </div>
        </div>
    </div>

    @if(count($recentLogins) > 0)
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h2 class="fw-bold mb-3">Login Activity (Last 30 Days)</h2>
        <div style="height:200px; position:relative;">
            <canvas id="loginChart"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const ctx = document.getElementById('loginChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode(collect($recentLogins)->pluck('date')) !!},
                datasets: [{
                    label: 'Logins',
                    data: {!! json_encode(collect($recentLogins)->pluck('count')) !!},
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
    </script>
    @endif
</div>
@endsection
