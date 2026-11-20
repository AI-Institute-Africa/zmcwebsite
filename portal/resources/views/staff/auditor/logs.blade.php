@extends('layouts.portal')
@section('title', 'User Access & Login Monitoring')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">User Access & Login Monitoring</h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Combined view of login history and the immutable user/system access trail. Records cannot be edited or deleted.
      </div>
    </div>

    <div class="d-flex gap-2">
      <a class="btn btn-success btn-sm" href="{{ route('staff.auditor.logs.csv', request()->query()) }}">
        <i class="ri-file-excel-line me-1"></i>Export Excel
      </a>
      <a class="btn btn-outline-dark btn-sm" href="{{ route('staff.auditor.logs.pdf', request()->query()) }}">
        <i class="ri-printer-line me-1"></i>Export PDF
      </a>
      <a class="btn btn-white border btn-sm" href="{{ route('staff.auditor.dashboard') }}">
        <i class="ri-arrow-left-line me-1"></i>Back
      </a>
    </div>
  </div>

  <div class="zmc-card shadow-sm border-0 p-3 mb-3">
    <form class="row g-2 align-items-end" method="GET">
      <div class="col-md-3">
        <label class="form-label small text-muted">Search</label>
        <input class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="action / model / id / ip">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Date Preset</label>
        <select class="form-select form-select-sm" name="date_preset">
          <option value="today" {{ $datePreset === 'today' ? 'selected' : '' }}>Today</option>
          <option value="last_7_days" {{ $datePreset === 'last_7_days' ? 'selected' : '' }}>Last 7 days</option>
          <option value="last_30_days" {{ $datePreset === 'last_30_days' ? 'selected' : '' }}>Last 30 days</option>
          <option value="custom" {{ $datePreset === 'custom' ? 'selected' : '' }}>Custom</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">From</label>
        <input type="date" class="form-control form-control-sm" name="from" value="{{ $fromDate }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">To</label>
        <input type="date" class="form-control form-control-sm" name="to" value="{{ $toDate }}">
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">User ID</label>
        <input class="form-control form-control-sm" name="user_id" value="{{ request('user_id') }}" placeholder="actor user id">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Role</label>
        <input class="form-control form-control-sm" name="role" value="{{ request('role') }}" placeholder="registrar">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Office / Branch</label>
        <input class="form-control form-control-sm" name="office_branch" value="{{ request('office_branch') }}" placeholder="Harare">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Module</label>
        <input class="form-control form-control-sm" name="module" value="{{ request('module') }}" placeholder="applications">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Action Type</label>
        <input class="form-control form-control-sm" name="action_type" value="{{ request('action_type') }}" placeholder="approved">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Application Type</label>
        <input class="form-control form-control-sm" name="application_type" value="{{ request('application_type') }}" placeholder="accreditation">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Record No.</label>
        <input class="form-control form-control-sm" name="record_number" value="{{ request('record_number') }}" placeholder="reference">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Status</label>
        <input class="form-control form-control-sm" name="status" value="{{ request('status') }}" placeholder="approved">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">IP</label>
        <input class="form-control form-control-sm" name="ip" value="{{ request('ip') }}" placeholder="10.0.0.1">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Device / Browser</label>
        <input class="form-control form-control-sm" name="device" value="{{ request('device') }}" placeholder="Chrome">
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Source</label>
        <select class="form-select form-select-sm" name="source">
          <option value="current" {{ $source === 'current' ? 'selected' : '' }}>Current logs</option>
          <option value="archived" {{ $source === 'archived' ? 'selected' : '' }}>Archived logs</option>
          <option value="all" {{ $source === 'all' ? 'selected' : '' }}>All logs</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small text-muted">Per Page</label>
        <select class="form-select form-select-sm" name="per_page">
          <option value="25" {{ (int)$perPage === 25 ? 'selected' : '' }}>25</option>
          <option value="50" {{ (int)$perPage === 50 ? 'selected' : '' }}>50</option>
          <option value="100" {{ (int)$perPage === 100 ? 'selected' : '' }}>100</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-dark btn-sm" type="submit"><i class="ri-search-line me-1"></i>Apply</button>
        <a class="btn btn-white border btn-sm" href="{{ route('staff.auditor.logs') }}">Reset</a>
      </div>
    </form>
  </div>

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h2 class="fw-bold m-0"><i class="ri-file-list-3-line me-2" style="color:var(--zmc-accent)"></i>Audit logs</h2>
      <div class="d-flex align-items-center gap-3"><div class="small text-muted">Showing {{ $logs->count() }} of {{ $logs->total() }}</div><div>{{ $logs->onFirstPage() ? '' : $logs->links() }}</div></div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        <thead>
          <tr>
            <th style="width:80px;">Log ID</th>
            <th style="width:170px;">Time</th>
            <th style="width:180px;">Actor</th>
            <th style="width:110px;">Role</th>
            <th style="width:180px;">Action</th>
            <th style="width:120px;">Module</th>
            <th style="width:130px;">Record</th>
            <th style="width:120px;">Status</th>
            <th style="width:120px;">IP</th>
            <th style="width:130px;">Device</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $l)
            @php
              $meta = is_array($l->meta) ? $l->meta : [];
              $recordNumber = $meta['record_number'] ?? $meta['reference'] ?? ($meta['application_reference'] ?? ($l->model_id ?? '—'));
              $status = $meta['to_status'] ?? $meta['status'] ?? '—';
              $module = $meta['module'] ?? ($l->model_type ? class_basename($l->model_type) : '—');
            @endphp
            <tr>
              <td class="small fw-bold">{{ $l->id }}</td>
              <td class="small text-muted">{{ optional($l->created_at)->format('d M Y H:i') }}</td>
              @php
                $actor = $l->actor;
              @endphp
              <td class="small">
                @if($actor)
                  <div class="fw-bold">{{ $actor->name }}</div>
                  <div class="text-muted" style="font-size: var(--font-size-sm);">{{ $actor->email }}</div>
                @else
                  {{ $l->actor_user_id ?? '—' }}
                @endif
              </td>
              <td class="small">{{ $l->user_role ?? ($meta['actor_role'] ?? '—') }}</td>
              <td class="small fw-bold text-dark">{{ $l->action }}</td>
              <td class="small">{{ $module }}</td>
              <td class="small">{{ $recordNumber }}</td>
              <td class="small">{{ $status }}</td>
              <td class="small">{{ $l->ip ?? '—' }}</td>
              <td class="small text-muted">{{ \Illuminate\Support\Str::limit((string)($l->user_agent ?? '—'), 24) }}</td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center py-5 text-muted">No audit logs yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">{{ $logs->links() }}</div>
</div>
@endsection
