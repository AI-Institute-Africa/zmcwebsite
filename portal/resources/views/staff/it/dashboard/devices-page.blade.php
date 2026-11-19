@extends('layouts.portal')
@section('title', 'Device Management - IT Admin')

@section('content')
<style>
    .devices-page { --device-ink:#172b4d; --device-muted:#667085; --device-line:#e7ebf0; color:var(--device-ink); }
    .devices-page .device-panel { border:1px solid var(--device-line); border-radius:18px; background:#fff; box-shadow:0 8px 28px rgba(16,24,40,.05); }
    .devices-page .device-kicker { color:var(--device-muted); font-size:.78rem; }
    .devices-page .device-table th { color:#667085; font-size:.69rem; letter-spacing:.07em; text-transform:uppercase; white-space:nowrap; }
    .devices-page .device-table td { vertical-align:middle; }
    .devices-page .device-stat { min-width:120px; }
</style>

<div class="container-fluid py-3 devices-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="device-kicker fw-semibold text-uppercase">IT Admin · Operations</div>
            <h1 class="h3 fw-bold mb-1">Devices &amp; printers</h1>
            <p class="text-muted mb-0">Track site equipment, connection health, and the last reported successful print.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newDeviceModal">
            <i class="ri-add-line me-1" aria-hidden="true"></i> Register device
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3" role="status">{{ session('success') }}</div>
    @endif
    @if(session('device_agent_token'))
        <div class="alert alert-warning border rounded-3" role="alert">
            <div class="fw-bold mb-1">One-time agent token for {{ session('device_agent_name') }}</div>
            <p class="mb-2 small">Copy this token into the device monitoring agent now. It is shown only once; issuing another token will revoke this one.</p>
            <div class="input-group mb-2">
                <input class="form-control font-monospace" id="deviceAgentToken" value="{{ session('device_agent_token') }}" readonly aria-label="One-time device agent token">
                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('deviceAgentToken').value)">Copy token</button>
            </div>
            <div class="small"><strong>Heartbeat endpoint:</strong> <code>{{ session('device_agent_endpoint') }}</code></div>
        </div>
    @endif

    <div class="device-panel p-3 p-lg-4 mb-4">
        <div class="d-flex align-items-start gap-3">
            <span class="rounded-circle bg-primary-subtle text-primary p-2"><i class="ri-pulse-line" aria-hidden="true"></i></span>
            <div>
                <h2 class="h6 fw-bold mb-1">How live status works</h2>
                <p class="small text-muted mb-2">Register the device, install/configure its site agent with the one-time token, then have it send periodic heartbeats. Without an agent, the device correctly remains “Awaiting agent.”</p>
                <code class="small">POST /api/v1/devices/{device-id}/heartbeat</code>
                <span class="small text-muted ms-2">Bearer token · JSON: queue_size, failed_jobs, last_printed_at</span>
            </div>
        </div>
    </div>

    <div class="device-panel overflow-hidden">
        <div class="d-flex justify-content-between align-items-center px-3 px-lg-4 py-3 border-bottom">
            <div><h2 class="h6 fw-bold mb-0">Registered equipment</h2><span class="small text-muted">{{ $deviceList->total() }} total</span></div>
            <span class="small text-muted">Connected means heartbeat received within {{ max(1, (int) config('monitoring.devices.heartbeat_timeout_minutes', 3)) }} minutes.</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover device-table mb-0">
                <thead class="bg-light"><tr>
                    <th class="ps-4 py-3">Device</th><th>Type</th><th>Connectivity</th><th>Location</th>
                    <th>Last successful print</th><th>Last heartbeat</th><th class="text-end pe-4">Agent</th>
                </tr></thead>
                <tbody>
                    @forelse($deviceList as $dev)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $dev->name }}</div>
                                @if($dev->diagnostics_notes)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($dev->diagnostics_notes, 70) }}</div>@endif
                            </td>
                            <td><span class="badge bg-light text-dark border rounded-pill">{{ ucfirst(str_replace('_', ' ', $dev->type)) }}</span></td>
                            <td><span class="badge rounded-pill bg-{{ $dev->connectivity_variant }}-subtle text-{{ $dev->connectivity_variant }} border">{{ $dev->connectivity_label }}</span></td>
                            <td>
                                @if($dev->location)
                                    <div>{{ $dev->location->name }}@if($dev->location->status !== 'active') <span class="badge bg-warning-subtle text-warning-emphasis">{{ ucfirst($dev->location->status) }}</span>@endif</div>
                                    <div class="small text-muted">{{ $dev->location->region?->name ?? 'Region not set' }}</div>
                                @else <span class="text-muted">No location assigned</span> @endif
                            </td>
                            <td class="device-stat">{{ $dev->last_successful_job?->diffForHumans() ?? 'No successful print reported' }}</td>
                            <td class="device-stat">{{ $dev->last_heartbeat?->diffForHumans() ?? 'Never reported' }}</td>
                            <td class="text-end pe-4">
                                <form method="POST" action="{{ route('staff.it.devices.token', $dev) }}" onsubmit="return confirm('Issue a replacement token? The current device token will stop working.');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill">{{ $dev->api_token_hash ? 'Replace token' : 'Issue token' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No devices registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($deviceList->hasPages())<div class="p-3 border-top">{{ $deviceList->links() }}</div>@endif
    </div>
</div>

<div class="modal fade" id="newDeviceModal" tabindex="-1" aria-labelledby="newDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('staff.it.devices.store') }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2"><div><h2 class="modal-title h5 fw-bold" id="newDeviceModalLabel">Register device</h2><p class="text-muted small mb-0">A secure agent token will be generated after saving.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body p-4 pt-3">
                <div class="mb-3"><label class="form-label small fw-semibold" for="deviceName">Device name</label><input id="deviceName" type="text" name="name" class="form-control rounded-3" required maxlength="255" value="{{ old('name') }}"></div>
                <div class="mb-3"><label class="form-label small fw-semibold" for="deviceType">Type</label><select id="deviceType" name="type" class="form-select rounded-3" required><option value="card_printer">Card printer</option><option value="certificate_printer">Certificate printer</option><option value="scanner">Scanner</option><option value="other">Other</option></select></div>
                <div class="mb-3"><label class="form-label small fw-semibold" for="deviceLocation">Office / location</label><select id="deviceLocation" name="location_id" class="form-select rounded-3"><option value="">No location</option>@foreach($locations as $loc)<option value="{{ $loc->id }}" @selected(old('location_id') == $loc->id)>{{ $loc->name }}{{ $loc->region ? ' — '.$loc->region->name : '' }}{{ $loc->status !== 'active' ? ' ('.ucfirst($loc->status).')' : '' }}</option>@endforeach</select><div class="form-text">All configured locations are listed, including inactive ones.</div></div>
                <div><label class="form-label small fw-semibold" for="deviceNotes">Notes</label><textarea id="deviceNotes" name="diagnostics_notes" class="form-control rounded-3" rows="2">{{ old('diagnostics_notes') }}</textarea></div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0"><button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary rounded-pill px-4">Register &amp; issue token</button></div>
        </form>
    </div>
</div>
@endsection
