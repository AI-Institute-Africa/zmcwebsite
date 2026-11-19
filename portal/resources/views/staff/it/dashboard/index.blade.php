@extends('layouts.portal')

@section('title', 'IT Dashboard')

@section('content')
<div class="it-dashboard" style="font-family: 'Inter', sans-serif; color: #1e293b; background: #f8fafc; min-height: 100vh; padding: 20px;">
    
    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-checkbox-circle-fill fs-5"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-error-warning-fill fs-5"></i> {{ session('error') }}
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border border-slate-100">
        <div>
            <h2 class="fw-bold m-0" style="letter-spacing: -0.02em; color: #0f172a;">IT Dashboard</h2>
            <p class="text-muted m-0 small mt-1">Unified System Administration & Monitoring</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if($health['database'] && $health['storage'])
            <div class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill d-flex align-items-center">
                <span class="pulse-dot me-2"></span> All Systems Online
            </div>
            @else
            <div class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill d-flex align-items-center">
                <span class="pulse-dot-red me-2"></span> Issues Detected
            </div>
            @endif
            <div class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                v2.4.0-Onyx
            </div>
        </div>
    </div>

    <div class="dashboard-nav-container mb-4 overflow-x-auto">
        <div class="nav nav-pills gap-2 flex-nowrap pb-2" id="itDashboardTabs" role="tablist">
            @php
                $tabs = [
                    'overview' => ['icon' => 'ri-dashboard-3-line', 'label' => 'Overview'],
                    'audit' => ['icon' => 'ri-history-line', 'label' => 'Audit'],
                    'system' => ['icon' => 'ri-settings-5-line', 'label' => 'System'],
                ];
            @endphp
            @foreach($tabs as $key => $tab)
            <button class="nav-link @if($key === 'overview') active @endif rounded-3 border-0 px-3 py-2 d-flex align-items-center gap-2 whitespace-nowrap" 
                    id="tab-{{ $key }}" data-bs-toggle="pill" data-bs-target="#panel-{{ $key }}" type="button" role="tab"
                    style="font-size: 13px; font-weight: 500; transition: all 0.2s ease;">
                <i class="{{ $tab['icon'] }} fs-5"></i>
                <span>{{ $tab['label'] }}</span>
            </button>
            @endforeach
        </div>
    </div>

    <div class="tab-content border-0" id="itDashboardContent">
        
        <div class="tab-pane fade show active" id="panel-overview" role="tabpanel">

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="stats-card p-4 rounded-4 bg-white shadow-sm border">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box p-2 rounded-3" style="background:#eff6ff;"><i class="ri-group-line fs-4" style="color:#3b82f6;"></i></div>
                            <div class="text-end">
                                <span class="badge bg-success-subtle text-success border rounded-pill" style="font-size:10px;">{{ $stats['active_count'] }} active</span>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1" style="color: #0f172a;">{{ number_format($stats['total_users']) }}</h3>
                        <p class="text-muted small m-0 fw-medium">Total Users</p>
                        <div class="mt-2 d-flex gap-2" style="font-size:11px;">
                            <span class="text-primary fw-bold">{{ $stats['staff_count'] }} staff</span>
                            <span class="text-muted">{{ $stats['public_count'] }} public</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stats-card p-4 rounded-4 bg-white shadow-sm border">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box p-2 rounded-3" style="background:#faf5ff;"><i class="ri-file-list-3-line fs-4" style="color:#a855f7;"></i></div>
                            <div class="text-end">
                                <span class="badge bg-warning-subtle text-warning border rounded-pill" style="font-size:10px;">{{ $stats['pending_applications'] }} pending</span>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-1" style="color: #0f172a;">{{ number_format($stats['total_applications']) }}</h3>
                        <p class="text-muted small m-0 fw-medium">Applications</p>
                        <div class="mt-2 d-flex gap-2" style="font-size:11px;">
                            <span class="text-success fw-bold">{{ $stats['approved_applications'] }} approved</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stats-card p-4 rounded-4 bg-white shadow-sm border">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box p-2 rounded-3" style="background:#fef2f2;"><i class="ri-alarm-warning-line fs-4" style="color:#ef4444;"></i></div>
                        </div>
                        <h3 class="fw-bold mb-1" style="color: #0f172a;">{{ $stats['open_incidents'] }}</h3>
                        <p class="text-muted small m-0 fw-medium">Open Incidents</p>
                        <div class="mt-2" style="font-size:11px;">
                            <a href="{{ route('staff.it.incidents') }}" class="text-danger fw-bold text-decoration-none">View All <i class="ri-arrow-right-s-line"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stats-card p-4 rounded-4 bg-white shadow-sm border">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="icon-box p-2 rounded-3" style="background:#f0fdf4;"><i class="ri-hard-drive-2-line fs-4" style="color:#22c55e;"></i></div>
                        </div>
                        <h3 class="fw-bold mb-1" style="color: #0f172a;">{{ $driveSpace }}</h3>
                        <p class="text-muted small m-0 fw-medium">Storage Available</p>
                        <div class="mt-2" style="font-size:11px;">
                            <span class="text-muted">{{ $storageUsageBytes ? number_format($storageUsageBytes/1024/1024, 1) . ' MB docs' : 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="ri-heart-pulse-line text-success"></i> System Health
                            </h6>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border" style="background:#f8fafc;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ri-database-2-line fs-5 {{ $health['database'] ? 'text-success' : 'text-danger' }}"></i>
                                        <span class="fw-semibold small">Database</span>
                                    </div>
                                    <span class="badge {{ $health['database'] ? 'bg-success' : 'bg-danger' }} rounded-pill">{{ $health['database'] ? 'Online' : 'Down' }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border" style="background:#f8fafc;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ri-folder-line fs-5 {{ $health['storage'] ? 'text-success' : 'text-danger' }}"></i>
                                        <span class="fw-semibold small">Storage</span>
                                    </div>
                                    <span class="badge {{ $health['storage'] ? 'bg-success' : 'bg-danger' }} rounded-pill">{{ $health['storage'] ? 'Online' : 'Down' }}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border" style="background:#f8fafc;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="ri-stack-line fs-5 {{ $health['queue'] ? 'text-success' : 'text-warning' }}"></i>
                                        <span class="fw-semibold small">Queue Worker</span>
                                    </div>
                                    <span class="badge {{ $health['queue'] ? 'bg-success' : 'bg-warning' }} rounded-pill">{{ $health['queue'] ? 'Online' : 'Check' }}</span>
                                </div>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <a href="{{ route('staff.it.monitoring') }}" class="btn btn-sm btn-outline-dark w-100 rounded-pill fw-semibold">
                                    <i class="ri-pulse-line me-1"></i> Full Monitoring
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="ri-pie-chart-line text-primary"></i> Users by Role
                            </h6>
                            <div id="rolesPieChart" style="min-height:200px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="ri-bar-chart-box-line text-purple"></i> Applications by Type
                            </h6>
                            <div id="appTypeChart" style="min-height:200px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="ri-line-chart-line text-primary"></i> Application Submissions (Last 6 Months)
                            </h6>
                            <div id="appsMonthlyChart" style="min-height:260px;"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    @if(auth()->user()?->hasRole('it_admin') && !auth()->user()?->hasRole('super_admin'))
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="ri-customer-service-2-line text-warning"></i> Helpdesk
                            </h6>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="flex-grow-1">
                                    <h2 class="fw-bold m-0" style="color:#0f172a;">{{ $stats['open_tickets'] }}</h2>
                                    <span class="text-muted small">Open Tickets</span>
                                </div>
                                <div class="p-3 rounded-3" style="background:#fffbeb;">
                                    <i class="ri-ticket-line fs-3" style="color:#f59e0b;"></i>
                                </div>
                            </div>
                            <a href="{{ route('staff.it.helpdesk') }}" class="btn btn-sm btn-outline-warning w-100 rounded-pill fw-semibold">
                                <i class="ri-external-link-line me-1"></i> Open Helpdesk
                            </a>
                        </div>
                    </div>
                    @endif

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="ri-hard-drive-2-line text-primary"></i> Upload Storage
                            </h6>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-2">
                                    <span class="text-muted">Documents Volume</span>
                                    <span class="fw-bold" style="color:#0f172a;">{{ $storageUsageBytes ? number_format($storageUsageBytes/1024/1024, 1) . ' MB' : 'N/A' }}</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 8px; background: #f1f5f9;">
                                    @php
                                        $storagePercent = ($storageUsage['total'] > 0) ? ($storageUsage['used'] / $storageUsage['total']) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar rounded-pill bg-primary" style="width: {{ min($storagePercent, 100) }}%"></div>
                                </div>
                            </div>
                            @foreach($storageByModule->take(4) as $mod)
                            <div class="d-flex align-items-center justify-content-between py-1" style="font-size:12px;">
                                <span class="text-muted">{{ ucfirst(str_replace('_', ' ', $mod->doc_type)) }}</span>
                                <span class="fw-bold">{{ number_format($mod->total/1024/1024, 1) }} MB</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0"><i class="ri-map-pin-line me-2 text-primary"></i>Regions Management</h6>
                            <button type="button" class="btn btn-sm btn-dark rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addRegionModal">
                                <i class="ri-add-line"></i> New Region
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead style="background: #f8fafc;">
                                    <tr>
                                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Region</th>
                                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Code</th>
                                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Status</th>
                                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Expiry</th>
                                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Officers</th>
                                        <th class="text-end pe-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($regions as $r)
                                    <tr>
                                        <td class="ps-4 fw-bold" style="color:#334155;">{{ $r->name }}</td>
                                        <td><code class="text-primary">{{ $r->code }}</code></td>
                                        <td>
                                            <span class="badge {{ $r->is_active ? 'bg-success-subtle text-success' : 'bg-light text-secondary' }} rounded-pill border">
                                                {{ $r->is_active ? 'Active' : 'Disabled' }}
                                            </span>
                                        </td>
                                        <td class="fw-bold" style="color:#475569;">{{ $r->expires_at ? $r->expires_at->format('d M Y') : 'Permanent' }}</td>
                                        <td><span class="badge rounded-pill bg-dark">{{ $r->officers_count }}</span></td>
                                        <td class="text-end pe-4">
                                            <form action="{{ route('staff.it.regions.toggle', $r) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm {{ $r->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill px-3">
                                                    {{ $r->is_active ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="ri-shield-check-line text-success"></i> Security Status
                        </h6>
                        <ul class="list-unstyled m-0">
                            <li class="d-flex align-items-center gap-3 mb-3">
                                <i class="ri-checkbox-circle-fill text-success fs-5"></i>
                                <div class="small">
                                    <div class="fw-bold" style="color:#0f172a;">SSL Certificate</div>
                                    <div class="text-muted fw-medium">Active (Verified)</div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center gap-3 mb-3">
                                <i class="ri-checkbox-circle-fill text-success fs-5"></i>
                                <div class="small">
                                    <div class="fw-bold" style="color:#0f172a;">WAF Policy</div>
                                    <div class="text-muted fw-medium">Strict Mode Enabled</div>
                                </div>
                            </li>
                            <li class="d-flex align-items-center gap-3">
                                <div class="spinner-grow spinner-grow-sm text-warning me-1"></div>
                                <div class="small">
                                    <div class="fw-bold" style="color:#0f172a;">Session Monitor</div>
                                    <div class="text-muted fw-medium">{{ count($activeSessions) }} active sessions</div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h6 class="fw-bold mb-3">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="{{ route('staff.it.users-mgmt') }}" class="btn bg-light text-dark btn-sm fw-bold border text-start px-3 py-2 d-flex align-items-center gap-2 rounded-3">
                                <i class="ri-user-settings-line text-primary"></i> Manage Users
                            </a>
                            <a href="{{ route('staff.it.performance') }}" class="btn bg-light text-dark btn-sm fw-bold border text-start px-3 py-2 d-flex align-items-center gap-2 rounded-3">
                                <i class="ri-speed-line text-warning"></i> Performance
                            </a>
                            @if(auth()->user()?->hasRole('it_admin') && !auth()->user()?->hasRole('super_admin'))
                                <a href="{{ route('staff.it.devices') }}" class="btn bg-light text-dark btn-sm fw-bold border text-start px-3 py-2 d-flex align-items-center gap-2 rounded-3">
                                    <i class="ri-printer-line text-danger"></i> Devices
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white p-4 border-0">
                    <h6 class="fw-bold m-0"><i class="ri-time-line me-2 text-warning"></i>Pending Staff Approvals</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Requesting User</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Roles</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Created</th>
                                <th class="text-end pe-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pending as $p)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold" style="color:#334155;">{{ $p->name }}</div>
                                    <div class="text-muted small fw-medium">{{ $p->email }}</div>
                                </td>
                                <td>
                                    @foreach($p->roles as $role)
                                    <span class="badge bg-light text-secondary border rounded-pill">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td class="fw-bold" style="color:#475569;">{{ $p->created_at->diffForHumans() }}</td>
                                <td class="text-end pe-4">
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Awaiting Approval</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted fw-medium small">No pending staff approvals at this time.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($pending->hasPages())
                <div class="card-footer bg-white border-0 px-4 py-3">
                    {{ $pending->links() }}
                </div>
                @endif
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-2 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold m-0"><i class="ri-server-line me-2 text-primary"></i>System Information</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush border-0">
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 rounded-circle" style="background:#f8fafc;"><i class="ri-server-line" style="color:#64748b;"></i></div>
                                <span class="fw-bold small" style="color:#475569;">System Core Version</span>
                            </div>
                            <span class="fw-bold" style="color:#0f172a;">{{ $envData['laravel_version'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 rounded-circle" style="background:#f8fafc;"><i class="ri-code-s-slash-line" style="color:#64748b;"></i></div>
                                <span class="fw-bold small" style="color:#475569;">PHP Version</span>
                            </div>
                            <span class="fw-bold" style="color:#0f172a;">{{ $envData['php_version'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 rounded-circle" style="background:#f8fafc;"><i class="ri-bug-line" style="color:#64748b;"></i></div>
                                <span class="fw-bold small" style="color:#475569;">Debug Mode</span>
                            </div>
                            <span class="badge {{ $envData['app_debug'] ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }} rounded-pill border">
                                {{ $envData['app_debug'] ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 rounded-circle" style="background:#f8fafc;"><i class="ri-database-line" style="color:#64748b;"></i></div>
                                <span class="fw-bold small" style="color:#475569;">Disk Usage</span>
                            </div>
                            <div class="d-flex align-items-center gap-3" style="min-width: 160px;">
                                <div class="progress flex-grow-1 rounded-pill" style="height: 6px;">
                                    <div class="progress-bar bg-primary rounded-pill" style="width: {{ min($storagePercent, 100) }}%"></div>
                                </div>
                                <span class="small fw-bold" style="color:#475569;">{{ round($storagePercent, 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="tab-pane fade" id="panel-audit" role="tabpanel">
             @include('staff.it.dashboard.partials.audit', ['logs' => $auditLogs])
        </div>

        <div class="tab-pane fade" id="panel-system" role="tabpanel">
             @include('staff.it.dashboard.partials.system', ['env' => $systemEnv])
        </div>

    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    
    .it-dashboard .nav-pills .nav-link {
        color: #64748b;
        background: #fff;
        border: 1px solid #e2e8f0 !important;
        white-space: nowrap;
    }
    
    .it-dashboard .nav-pills .nav-link.active {
        background: #0f172a !important;
        color: #fff !important;
        border-color: #0f172a !important;
    }
    
    .it-dashboard .stats-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .it-dashboard .stats-card:hover {
        transform: translateY(-4px);
        border-color: #cbd5e1 !important;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background: #22c55e;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
    }

    .pulse-dot-red {
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        animation: pulseRed 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    @keyframes pulseRed {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .list-group-item:last-child {
        border-bottom: 0 !important;
    }

    .bg-purple-subtle { background: #f3e8ff; }
    .text-purple { color: #a855f7; }

    .dashboard-nav-container::-webkit-scrollbar {
        height: 4px;
    }
    .dashboard-nav-container::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }

    nav[role="navigation"] svg,
    .pagination svg {
        width: 14px !important;
        height: 14px !important;
    }
</style>

<div class="modal fade" id="addRegionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <form action="{{ route('staff.it.regions.store') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
        @csrf
        <div class="modal-header border-0 p-4 pb-2">
          <h5 class="modal-title fw-bold">Create New Administrative Region</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Region Name</label>
            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Harare North" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Internal Code</label>
            <input type="text" name="code" class="form-control rounded-3" placeholder="e.g. harare_n" required>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-bold text-muted">Expiry Date (Optional)</label>
            <input type="date" name="expires_at" class="form-control rounded-3">
            <div class="form-text small">Leave blank for permanent regions.</div>
          </div>
        </div>
        <div class="modal-footer border-0 p-4 pt-2">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark rounded-pill px-4">Create Region</button>
        </div>
      </form>
    </div>
</div>

@php
  $rolesChartData = $usersByRole->map(function($r) {
      return ['name' => ucfirst(str_replace('_', ' ', $r->name)), 'count' => $r->users_count];
  })->values()->toArray();

  $appTypeChartData = $appsByType->map(function($a) {
      return ['type' => ucfirst(str_replace('_', ' ', $a->application_type ?? 'Unknown')), 'count' => $a->count];
  })->values()->toArray();

  $monthlyChartData = $appsByMonth->map(function($a) {
      return ['month' => $a->month, 'count' => $a->count];
  })->values()->toArray();
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var rolesData = {!! json_encode($rolesChartData) !!};
    if (rolesData.length > 0) {
        new ApexCharts(document.querySelector('#rolesPieChart'), {
            chart: { type: 'donut', height: 200, fontFamily: 'Inter' },
            series: rolesData.map(r => r.count),
            labels: rolesData.map(r => r.name),
            colors: ['#3b82f6','#8b5cf6','#f59e0b','#22c55e','#ef4444','#06b6d4','#ec4899','#f97316'],
            legend: { position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: false },
            plotOptions: { pie: { donut: { size: '55%' } } }
        }).render();
    }

    var appTypeData = {!! json_encode($appTypeChartData) !!};
    if (appTypeData.length > 0) {
        new ApexCharts(document.querySelector('#appTypeChart'), {
            chart: { type: 'bar', height: 200, fontFamily: 'Inter', toolbar: { show: false } },
            series: [{ name: 'Count', data: appTypeData.map(a => a.count) }],
            xaxis: { categories: appTypeData.map(a => a.type) },
            colors: ['#8b5cf6'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f1f5f9' }
        }).render();
    }

    var monthlyData = {!! json_encode($monthlyChartData) !!};
    new ApexCharts(document.querySelector('#appsMonthlyChart'), {
        chart: { type: 'area', height: 260, fontFamily: 'Inter', toolbar: { show: false }, sparkline: { enabled: false } },
        series: [{ name: 'Applications', data: monthlyData.map(m => m.count) }],
        xaxis: { categories: monthlyData.map(m => m.month), labels: { style: { fontSize: '11px' } } },
        yaxis: { labels: { style: { fontSize: '11px' } } },
        colors: ['#3b82f6'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    }).render();
});
</script>
@endpush

@endsection
