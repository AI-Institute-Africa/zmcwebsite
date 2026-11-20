@extends('layouts.portal')
@section('title', 'Design Studio — Audit Logs')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:var(--font-primary);color:var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:var(--font-size-2xl);color:#1e293b;">
        <i class="ri-file-list-3-line me-2" style="color:var(--zmc-accent)"></i>Design Studio Audit Logs
      </h1>
      <div class="text-muted mt-1">Complete record of design changes, activation, versioning, and archive actions.</div>
    </div>
    <a href="{{ route('staff.design-studio.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
      <i class="ri-arrow-left-line me-1"></i> Dashboard
    </a>
  </div>

  {{-- Filters --}}
  <div class="zmc-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">Action</label>
        <select name="action" class="form-select form-select-sm">
          <option value="">All Actions</option>
          @foreach([
            'template_created','template_edited','template_submitted',
            'template_approved','template_rejected','template_duplicated',
            'template_archived','template_restored',
          ] as $a)
            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>
              {{ ucwords(str_replace('_',' ',$a)) }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">User</label>
        <select name="user_id" class="form-select form-select-sm">
          <option value="">All Users</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">From</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">To</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
      </div>
      <div class="col-md-2 d-flex gap-2 align-items-end">
        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-line me-1"></i>Filter</button>
        <a href="{{ route('staff.design-studio.audit-logs') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0 zmc-mini-table">
        <thead class="bg-light">
          <tr>
            <th class="ps-3">Date &amp; Time</th>
            <th>User</th>
            <th>Action</th>
            <th>Template</th>
            <th>IP Address</th>
            <th class="text-end pe-3">Details</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
          <tr>
            <td class="ps-3 text-muted">{{ $log->created_at->format('d M Y H:i') }}</td>
            <td>{{ $log->user?->name ?? 'System' }}</td>
            <td>
              @php
                $actionColors = [
                  'template_approved' => 'bg-success',
                  'template_rejected' => 'bg-danger',
                  'template_submitted'=> 'bg-warning text-dark',
                  'template_archived' => 'bg-dark',
                  'template_restored' => 'bg-info text-dark',
                  'template_created'  => 'bg-secondary',
                  'template_edited'   => 'bg-secondary',
                  'template_duplicated'=> 'bg-secondary',
                ];
              @endphp
              <span class="badge {{ $actionColors[$log->action] ?? 'bg-secondary' }}">
                {{ ucwords(str_replace('_',' ',$log->action)) }}
              </span>
            </td>
            <td>
              @if($log->entity_id)
                <a href="{{ route('staff.design-studio.show', $log->entity_id) }}" class="text-decoration-none">
                  #{{ $log->entity_id }}
                </a>
              @else
                —
              @endif
            </td>
            <td class="text-muted">{{ $log->ip_address ?? '—' }}</td>
            <td class="text-end pe-3">
              @if($log->new_values)
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal" data-bs-target="#logModal_{{ $log->id }}">
                  <i class="ri-eye-line"></i>
                </button>
                <div class="modal fade" id="logModal_{{ $log->id }}" tabindex="-1">
                  <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h2 class="modal-title fw-bold">Log Detail — {{ ucwords(str_replace('_',' ',$log->action)) }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body small">
                        @if($log->old_values)
                          <div class="fw-bold text-muted mb-1">Before:</div>
                          <pre class="bg-light p-2 rounded" style="font-size:11px;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                        @endif
                        @if($log->new_values)
                          <div class="fw-bold text-muted mb-1 mt-2">After:</div>
                          <pre class="bg-light p-2 rounded" style="font-size:11px;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              @else
                —
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">No audit log entries found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $logs->withQueryString()->links() }}</div>
  </div>

</div>
@endsection
