@extends('layouts.portal')
@section('title', 'Comments Audit Trail')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
        <i class="ri-discuss-line me-2"></i>Staff Comments Audit
      </h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        All staff comments on applications, fully audited with timestamps, roles, and IP addresses.
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.auditor.comments.csv', request()->query()) }}" class="btn btn-sm btn-outline-success shadow-sm">
        <i class="ri-download-line me-1"></i> Export CSV
      </a>
      <a href="{{ route('staff.auditor.dashboard') }}" class="btn btn-sm btn-outline-dark">
        <i class="ri-arrow-left-line me-1"></i> Dashboard
      </a>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small">Total Comments</div>
        <div class="fw-bold" style="font-size: 1.8rem;">{{ number_format($stats['total']) }}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small">Today</div>
        <div class="fw-bold" style="font-size: 1.8rem;">{{ number_format($stats['today']) }}</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small">By Role</div>
        <div class="mt-1">
          @foreach($stats['by_role'] as $role => $cnt)
            <span class="badge bg-secondary me-1 mb-1">{{ ucfirst(str_replace('_', ' ', $role)) }}: {{ $cnt }}</span>
          @endforeach
          @if(empty($stats['by_role']))
            <span class="text-muted small">No data</span>
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small">By Type</div>
        <div class="mt-1">
          @foreach($stats['by_type'] as $type => $cnt)
            <span class="badge bg-info me-1 mb-1">{{ ucfirst(str_replace('_', ' ', $type)) }}: {{ $cnt }}</span>
          @endforeach
          @if(empty($stats['by_type']))
            <span class="text-muted small">No data</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
      <form method="GET" action="{{ route('staff.auditor.comments') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small fw-bold mb-1">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Reference, name, or content..." value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-bold mb-1">Role</label>
          <select name="role" class="form-select form-select-sm">
            <option value="">All Roles</option>
            @foreach($roles as $role)
              <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-bold mb-1">Type</label>
          <select name="type" class="form-select form-select-sm">
            <option value="">All Types</option>
            @foreach($types as $val => $label)
              <option value="{{ $val }}" @selected(request('type') === $val)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-bold mb-1">From</label>
          <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-bold mb-1">To</label>
          <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
        </div>
        <div class="col-md-1">
          <button type="submit" class="btn btn-sm btn-primary w-100">
            <i class="ri-filter-line"></i>
          </button>
        </div>
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-3" style="width: 50px;">#</th>
              <th>Application</th>
              <th>Staff Member</th>
              <th>Role</th>
              <th>Type</th>
              <th>Comment</th>
              <th>Visibility</th>
              <th>App Status</th>
              <th>IP</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            @forelse($comments as $comment)
              <tr>
                <td class="ps-3 text-muted small">{{ $comment->id }}</td>
                <td>
                  <a href="#" class="fw-bold text-decoration-none" style="font-size: 0.85rem;">
                    {{ $comment->application?->reference ?? 'N/A' }}
                  </a>
                </td>
                <td class="small">{{ $comment->user?->name ?? '-' }}</td>
                <td>
                  <span class="badge bg-{{ $comment->getRoleBadgeColor() }}" style="font-size: 0.65rem;">
                    {{ $comment->getRoleLabel() }}
                  </span>
                </td>
                <td>
                  <span class="badge bg-{{ $comment->getTypeBadgeColor() }}" style="font-size: 0.65rem;">
                    {{ $comment->getTypeLabel() }}
                  </span>
                </td>
                <td style="max-width: 300px;">
                  <div class="small text-truncate" title="{{ $comment->body }}">
                    {{ Str::limit($comment->body, 80) }}
                  </div>
                </td>
                <td>
                  @if($comment->visibility === 'applicant')
                    <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">
                      <i class="ri-eye-line"></i> Applicant
                    </span>
                  @else
                    <span class="badge bg-light text-muted border" style="font-size: 0.6rem;">Internal</span>
                  @endif
                </td>
                <td>
                  <code class="small text-muted" style="font-size: 0.65rem;">{{ str_replace('_', ' ', $comment->application_status_at_time ?? '-') }}</code>
                </td>
                <td class="small text-muted" style="font-size: 0.7rem;">{{ $comment->ip_address ?? '-' }}</td>
                <td class="small text-muted text-nowrap" style="font-size: 0.7rem;">
                  {{ $comment->created_at->format('d M Y H:i') }}<br>
                  {{ $comment->created_at->format('H:i:s') }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-muted py-4">
                  <i class="ri-chat-3-line" style="font-size: 2rem; opacity: 0.3;"></i>
                  <div class="mt-2">No comments found matching your criteria.</div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($comments->hasPages())
      <div class="card-footer bg-white border-top py-2">
        {{ $comments->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
