@extends('layouts.portal')
@section('title', $title ?? 'Compliance')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">{{ $title ?? 'Compliance' }}</h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);"><i class="ri-information-line me-1"></i>Reported violations, their category and current status.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="{{ route('staff.officer.compliance.monitoring') }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-shield-keyhole-line me-1"></i>Monitoring</a>
      <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-dashboard-3-line me-1"></i>Dashboard</a>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small fw-bold mb-1">Search</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
            placeholder="Reference, category or summary">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small fw-bold mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(['reported' => 'Reported', 'under_investigation' => 'Under investigation', 'resolved' => 'Resolved', 'closed' => 'Closed', 'dismissed' => 'Dismissed'] as $value => $label)
              <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-auto d-flex gap-2">
          <button type="submit" class="btn btn-dark btn-sm px-3"><i class="ri-filter-3-line me-1"></i>Apply</button>
          <a href="{{ url()->current() }}" class="btn btn-white border btn-sm px-3">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Ref</th>
              <th>Category</th>
              <th>Status</th>
              <th>Summary</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
          @forelse($rows as $r)
            <tr>
              <td class="fw-semibold">{{ $r->reference ?: ('#' . $r->id) }}</td>
              <td>{{ ucwords(str_replace('_', ' ', $r->category ?? '—')) }}</td>
              <td><span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $r->status ?? '—')) }}</span></td>
              <td class="text-muted">{{ \Illuminate\Support\Str::limit($r->summary ?? '', 90) ?: '—' }}</td>
              <td>{{ optional($r->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No compliance items found.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      @if(method_exists($rows, 'links'))
        <div class="mt-3">{{ $rows->links() }}</div>
      @endif
    </div>
  </div>
</div>
@endsection
