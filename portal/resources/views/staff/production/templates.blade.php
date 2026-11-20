@extends('layouts.portal')
@section('title', 'Card/Certificate Templates')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
        <i class="ri-layout-masonry-line me-2" style="color:var(--zmc-accent)"></i>Card &amp; Certificate Templates
      </h1>
      <div class="text-muted mt-1" style="font-size:13px;">
        Manage saved templates for card and certificate production.
      </div>
    </div>
    <a href="{{ route('staff.production.designer') }}" class="btn btn-primary btn-sm px-3 fw-bold">
      <i class="ri-add-line me-1"></i> New Template
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger d-flex align-items-start gap-2">
      <i class="ri-error-warning-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('error') }}</div>
    </div>
  @endif

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        <thead>
          <tr>
            <th style="width:50px;">#</th>
            <th>Name</th>
            <th>Type</th>
            <th>Year</th>
            <th>Background</th>
            <th>Fields</th>
            <th>Status</th>
            <th>Created By</th>
            <th>Created</th>
            <th class="text-end" style="min-width:180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        @forelse($templates as $i => $tpl)
          @php
            $fields = is_array($tpl->layout_config) ? count($tpl->layout_config) : 0;
          @endphp
          <tr>
            <td class="text-muted small">{{ $i + 1 }}</td>
            <td class="fw-bold">{{ $tpl->name }}</td>
            <td>
              <span class="badge bg-{{ $tpl->type === 'card' ? 'info' : 'warning' }} text-dark">
                {{ ucfirst($tpl->type) }}
              </span>
            </td>
            <td>{{ $tpl->year }}</td>
            <td>
              @if($tpl->background_path)
                <span class="text-success"><i class="ri-image-line"></i> Yes</span>
              @else
                <span class="text-muted">None</span>
              @endif
            </td>
            <td>{{ $fields }} field(s)</td>
            <td>
              @if($tpl->is_active)
                <span class="badge bg-success">Active</span>
              @else
                <span class="badge bg-secondary">Inactive</span>
              @endif
            </td>
            <td>{{ $tpl->creator->name ?? '—' }}</td>
            <td class="small text-muted">{{ $tpl->created_at->format('d M Y H:i') }}</td>
            <td class="text-end">
              <div class="d-flex justify-content-end gap-1">
                <a href="{{ route('staff.production.designer', ['template' => $tpl->id]) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit template">
                  <i class="ri-pencil-line"></i>
                </a>

                <form method="POST" action="{{ route('staff.production.templates.activate', $tpl) }}" class="d-inline">
                  @csrf
                  @if($tpl->is_active)
                    <button type="submit" class="btn btn-sm zmc-icon-btn btn-outline-secondary" data-bs-toggle="tooltip" title="Deactivate">
                      <i class="ri-toggle-line"></i>
                    </button>
                  @else
                    <button type="submit" class="btn btn-sm zmc-icon-btn btn-outline-success" data-bs-toggle="tooltip" title="Activate">
                      <i class="ri-toggle-fill"></i>
                    </button>
                  @endif
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="text-center py-5 text-muted">
              No templates found. <a href="{{ route('staff.production.designer') }}">Create one now</a>.
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
