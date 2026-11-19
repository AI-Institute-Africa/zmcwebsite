@extends('layouts.portal')
@section('title', $title ?? 'Follow-up Queue')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">{{ $title ?? 'Officer Follow-up Queue' }}</h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);"><i class="ri-information-line me-1"></i>Auto-generated follow-ups for expiring items and compliance flags.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-dashboard-3-line me-1"></i>Dashboard</a>
      <a href="{{ route('staff.officer.tools.tasks') }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-task-line me-1"></i>Task Queue</a>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small fw-bold mb-1">Search</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Title or notes">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small fw-bold mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(['pending' => 'Pending', 'in_progress' => 'In progress', 'done' => 'Done', 'cancelled' => 'Cancelled'] as $value => $label)
              <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small fw-bold mb-1">Type</label>
          <select name="type" class="form-select form-select-sm">
            <option value="">All types</option>
            @foreach(['expiry' => 'Expiry', 'compliance' => 'Compliance', 'verification' => 'Verification', 'renewal' => 'Renewal'] as $value => $label)
              <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Type</th>
              <th>Title</th>
              <th>Notes</th>
              <th>Due</th>
              <th>Status</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
          @forelse($items as $it)
            <tr>
              <td class="fw-semibold">{{ ucwords(str_replace('_', ' ', $it->type ?? '—')) }}</td>
              <td>{{ $it->title ?: ('#' . $it->id) }}</td>
              <td class="text-muted">{{ \Illuminate\Support\Str::limit($it->notes ?? '', 90) }}</td>
              <td>{{ optional($it->due_date)->format('Y-m-d') ?? '—' }}</td>
              <td><span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $it->status ?? '—')) }}</span></td>
              <td>{{ optional($it->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No follow-ups in the queue.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>

      @if(method_exists($items, 'links'))
        <div class="mt-3">{{ $items->links() }}</div>
      @endif
    </div>
  </div>
</div>
@endsection
