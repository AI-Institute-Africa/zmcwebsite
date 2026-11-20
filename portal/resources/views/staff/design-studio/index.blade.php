@extends('layouts.portal')
@section('title', 'Design Studio — Templates')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:var(--font-primary);color:var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:var(--font-size-2xl);color:#1e293b;">
        <i class="ri-layout-grid-line me-2" style="color:var(--zmc-accent)"></i>Templates
      </h1>
      <div class="text-muted mt-1">All card and certificate templates.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.design-studio.create') }}" class="btn btn-dark btn-sm fw-bold">
        <i class="ri-add-line me-1"></i> New Template
      </a>
      <a href="{{ route('staff.design-studio.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-arrow-left-line me-1"></i> Dashboard
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
  @endif

  {{-- Filters --}}
  <div class="zmc-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">Search</label>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Template name…" value="{{ request('search') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Type</label>
        <select name="type" class="form-select form-select-sm">
          <option value="">All Types</option>
          <option value="card"        {{ request('type') === 'card'        ? 'selected' : '' }}>Card</option>
          <option value="certificate" {{ request('type') === 'certificate' ? 'selected' : '' }}>Certificate</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <option value="draft"            {{ request('status') === 'draft'            ? 'selected' : '' }}>Draft</option>
          <option value="approved"         {{ request('status') === 'approved'         ? 'selected' : '' }}>Approved</option>
          <option value="rejected"         {{ request('status') === 'rejected'         ? 'selected' : '' }}>Rejected</option>
          <option value="archived"         {{ request('status') === 'archived'         ? 'selected' : '' }}>Archived</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold text-muted">Year</label>
        <input type="text" name="year" class="form-control form-control-sm" placeholder="e.g. {{ now()->year }}" value="{{ request('year') }}" maxlength="4">
      </div>
      <div class="col-md-3 d-flex gap-2 align-items-end">
        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-line me-1"></i>Filter</button>
        <a href="{{ route('staff.design-studio.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>

  {{-- Table --}}
  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0 zmc-mini-table">
        <thead class="bg-light">
          <tr>
            <th class="ps-3">Name</th>
            <th>Type</th>
            <th>For</th>
            <th>Year</th>
            <th>Category</th>
            <th>Status</th>
            <th>Designed By</th>
            <th>Last Updated</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($templates as $tpl)
          <tr>
            <td class="ps-3 fw-bold">{{ $tpl->name }}</td>
            <td>
              @if($tpl->type === 'card')
                <span class="badge bg-info-subtle text-info border border-info-subtle">Card</span>
              @else
                <span class="badge bg-purple-subtle" style="background:rgba(99,102,241,.1);color:#6366f1;border:1px solid rgba(99,102,241,.2);">Certificate</span>
              @endif
            </td>
            <td>{{ $tpl->templateForLabel() }}</td>
            <td>{{ $tpl->year }}</td>
            <td>{{ $tpl->category_code ?? '—' }}</td>
            <td>
              <span class="badge {{ $tpl->statusBadgeClass() }}">{{ $tpl->statusLabel() }}</span>
            </td>
            <td>{{ $tpl->creator?->name ?? '—' }}</td>
            <td>{{ $tpl->updated_at->format('d M Y') }}</td>
            <td class="text-end pe-3">
              <div class="d-flex gap-1 justify-content-end">
                <a href="{{ route('staff.design-studio.show', $tpl->id) }}"
                   class="btn btn-sm btn-outline-primary zmc-icon-btn" title="View">
                  <i class="ri-eye-line"></i>
                </a>
                @if($tpl->isEditable() && ($tpl->created_by === auth()->id() || auth()->user()->hasRole('super_admin')))
                  <a href="{{ route('staff.design-studio.edit', $tpl->id) }}"
                     class="btn btn-sm btn-outline-dark zmc-icon-btn" title="Edit / Design">
                    <i class="ri-edit-2-line"></i>
                  </a>
                @endif
                @if($tpl->isEditable() && ($tpl->created_by === auth()->id() || auth()->user()->hasRole('super_admin')))
                  <form method="POST" action="{{ route('staff.design-studio.destroy', $tpl->id) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger zmc-icon-btn" title="Delete"
                            onclick="return confirm('Delete this template permanently? This action cannot be undone.')">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                @endif
                @if(auth()->user()->hasRole('super_admin'))
                  @if($tpl->status === 'approved')
                    <form method="POST" action="{{ route('staff.design-studio.approval.archive', $tpl->id) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-secondary zmc-icon-btn" title="Archive"
                              onclick="return confirm('Archive this template?')">
                        <i class="ri-archive-line"></i>
                      </button>
                    </form>
                  @elseif($tpl->status === 'archived')
                    <form method="POST" action="{{ route('staff.design-studio.approval.restore', $tpl->id) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-success zmc-icon-btn" title="Restore">
                        <i class="ri-restart-line"></i>
                      </button>
                    </form>
                  @endif
                @endif
                <form method="POST" action="{{ route('staff.design-studio.duplicate', $tpl->id) }}" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-secondary zmc-icon-btn" title="Duplicate">
                    <i class="ri-file-copy-line"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="text-center py-5 text-muted">
              No templates found.
              <a href="{{ route('staff.design-studio.create') }}" class="ms-2">Create one</a>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $templates->withQueryString()->links() }}</div>
  </div>

</div>
@endsection
