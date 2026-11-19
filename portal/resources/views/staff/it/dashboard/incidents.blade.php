@extends('layouts.portal')
@section('title', 'Alerts & Incidents - IT Admin')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <h2 class="fw-bold m-0" style="color:#0f172a;">Alerts & Incidents</h2>
            <p class="text-muted m-0 small mt-1">Incident tracking, escalation, and resolution center</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-danger-subtle text-danger border px-3 py-2 rounded-pill fw-bold">{{ $criticalCount }} Critical</span>
            <span class="badge bg-warning-subtle text-warning border px-3 py-2 rounded-pill fw-bold">{{ $openCount }} Open</span>
            <button class="btn btn-dark rounded-pill px-3 fw-bold small" data-bs-toggle="modal" data-bs-target="#newIncidentModal"><i class="ri-add-line"></i> Log Incident</button>
        </div>
    </div>

    <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 rounded-4 border-0 shadow-sm">
        <span><i class="ri-mail-line me-1"></i> Warning and critical incidents email IT when alerts are enabled and the mail transport is ready. Automated health alerts are created by System Monitoring.</span>
        <a href="{{ route('staff.it.monitoring') }}" class="btn btn-sm btn-light border rounded-pill fw-bold flex-shrink-0"><i class="ri-settings-3-line"></i> Email alert settings</a>
    </div>

    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="{{ route('staff.it.incidents') }}" class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-light border' }} rounded-pill px-3 fw-bold">All</a>
        @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'] as $k => $v)
        <a href="{{ route('staff.it.incidents', ['status' => $k]) }}" class="btn btn-sm {{ request('status') === $k ? 'btn-dark' : 'btn-light border' }} rounded-pill px-3 fw-bold">{{ $v }}</a>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Incident</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Severity</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Module</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Status</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Assigned To</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Reported</th>
                        <th class="text-end pe-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incidents as $inc)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold" style="color:#334155;">{{ $inc->title }}</div>
                            @if($inc->description)<div class="text-muted small">{{ Str::limit($inc->description, 60) }}</div>@endif
                        </td>
                        <td>
                            @php $sevColor = match($inc->severity) { 'critical' => 'danger', 'warning' => 'warning', default => 'info' }; @endphp
                            <span class="badge bg-{{ $sevColor }}-subtle text-{{ $sevColor }} border rounded-pill">{{ ucfirst($inc->severity) }}</span>
                        </td>
                        <td class="fw-medium" style="color:#475569;">{{ $inc->module ?? '-' }}</td>
                        <td>
                            @php $statColor = match($inc->status) { 'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', default => 'secondary' }; @endphp
                            <span class="badge bg-{{ $statColor }}-subtle text-{{ $statColor }} border rounded-pill">{{ ucfirst(str_replace('_', ' ', $inc->status)) }}</span>
                        </td>
                        <td class="fw-medium" style="color:#475569;">{{ $inc->assignee?->name ?? 'Unassigned' }}</td>
                        <td class="text-muted">{{ $inc->created_at->diffForHumans() }}</td>
                        <td class="text-end pe-4">
                            @if(auth()->user()?->hasRole('it_admin') && !auth()->user()?->hasRole('super_admin'))
                                @if($inc->helpdeskTicket)
                                    <a href="{{ route('staff.it.helpdesk') }}#ticket-{{ $inc->helpdeskTicket->id }}" class="btn btn-sm btn-light border rounded-pill px-3" title="Open linked Helpdesk ticket">Ticket #{{ $inc->helpdeskTicket->id }}</a>
                                @else
                                    <form action="{{ route('staff.it.incidents.ticket', $inc) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-light border rounded-pill px-3" type="submit" title="Create a Helpdesk ticket from this incident"><i class="ri-ticket-line"></i> Create ticket</button>
                                    </form>
                                @endif
                            @endif
                            <button class="btn btn-sm btn-light border rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#updateIncident{{ $inc->id }}"><i class="ri-edit-line"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted fw-medium">No incidents recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($incidents->hasPages())
        <div class="p-4 border-top">{{ $incidents->links() }}</div>
        @endif
    </div>
</div>

@foreach($incidents as $inc)
<div class="modal fade" id="updateIncident{{ $inc->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('staff.it.incidents.update', $inc) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold">Update: {{ Str::limit($inc->title, 40) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Status</label>
                    <select name="status" class="form-select rounded-3">
                        @foreach(['open','in_progress','resolved','dismissed'] as $s)
                        <option value="{{ $s }}" {{ $inc->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Assigned To</label>
                    <select name="assigned_to" class="form-select rounded-3">
                        <option value="">Unassigned</option>
                        @foreach($staffUsers as $su)
                        <option value="{{ $su->id }}" {{ $inc->assigned_to == $su->id ? 'selected' : '' }}>{{ $su->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Root Cause</label>
                    <textarea name="root_cause" class="form-control rounded-3" rows="2">{{ $inc->root_cause }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold text-muted">Resolution Notes</label>
                    <textarea name="resolution_notes" class="form-control rounded-3" rows="2">{{ $inc->resolution_notes }}</textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4">Update Incident</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="newIncidentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('staff.it.incidents.store') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold">Log New Incident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Title</label>
                    <input type="text" name="title" class="form-control rounded-3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Description</label>
                    <textarea name="description" class="form-control rounded-3" rows="3"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Severity</label>
                        <select name="severity" class="form-select rounded-3" required>
                            <option value="info">Informational</option>
                            <option value="warning" selected>Warning</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Module</label>
                        <input type="text" name="module" class="form-control rounded-3" placeholder="e.g. authentication">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-bold text-muted">Assign To</label>
                    <select name="assigned_to" class="form-select rounded-3">
                        <option value="">Unassigned</option>
                        @foreach($staffUsers as $su)
                        <option value="{{ $su->id }}">{{ $su->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4">Log Incident</button>
            </div>
        </form>
    </div>
</div>
@endsection
