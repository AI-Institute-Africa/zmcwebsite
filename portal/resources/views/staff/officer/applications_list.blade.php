@extends('layouts.portal')
@section('title', $title ?? 'Applications')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">{{ $title ?? 'Applications' }}</h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);"><i class="ri-information-line me-1"></i>Click a row to open the application.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-dashboard-3-line me-1"></i>Dashboard</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size: var(--font-size-lg); line-height: 1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
          @forelse($applications as $i => $app)
            <tr>
              @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
              <td class="text-end">
                <a href="{{ route('staff.officer.applications.show', $app) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="tooltip" title="View Application">
                  <i class="ri-eye-line"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-5">No applications found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if(method_exists($applications, 'links'))
    <div class="mt-3">{{ $applications->links() }}</div>
  @endif
</div>
@endsection
