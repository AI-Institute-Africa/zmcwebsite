@extends('layouts.portal')

@section('title', 'Audit & Logs')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Audit & Logs</h4>
      <div class="text-muted mt-1" style="font-size:13px;">Traceability for approvals, user actions, and system events.</div>
    </div>
    <a href="{{ route('executive.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Back</a>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('admin.audit.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="flex-grow-1" style="min-width:240px;">
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search audits by action/description..." />
        </div>
        <button class="btn btn-primary" type="submit"><i class="ri-search-line me-1"></i>Search</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.audit.index') }}">Reset</a>
      </form>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0">
          <div class="fw-semibold">Audit Trail</div>
          <div class="text-muted" style="font-size:12px;">High-level trail used across the system.</div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>When</th>
                  <th>Action</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                @forelse($auditTrail as $row)
                  <tr>
                    <td class="text-muted" style="white-space:nowrap;">{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="fw-semibold">{{ $row->action }}</td>
                    <td class="text-muted">{{ $row->description }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-muted">No audit trail entries.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-2">{{ $auditTrail->withQueryString()->links() }}</div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0">
          <div class="fw-semibold">Audit Log</div>
          <div class="text-muted" style="font-size:12px;">System log events (incl. user creation & access changes).</div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>When</th>
                  <th>Action</th>
                  <th class="text-muted">Meta</th>
                </tr>
              </thead>
              <tbody>
                @forelse($auditLog as $row)
                  <tr>
                    <td class="text-muted" style="white-space:nowrap;">{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="fw-semibold">{{ $row->action }}</td>
                    <td class="text-muted" style="max-width:320px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ is_string($row->meta) ? $row->meta : json_encode($row->meta) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-muted">No audit log entries.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-2">{{ $auditLog->withQueryString()->links() }}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
