@extends('layouts.portal')

@section('title', $title)

@section('content')
@php
  $status = request('status');
  $statusList = [
    'draft','submitted','officer_review','officer_approved','correction_requested','officer_rejected',
    'registrar_review','registrar_approved','registrar_rejected','accounts_review','paid_confirmed',
    'production_queue','card_generated','certificate_generated','printed','issued'
  ];
@endphp

<div class="zmc-dashboard-wrapper" style="font-family:var(--font-primary); color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">{{ $title }}</h4>
      <div class="text-muted mt-1" style="font-size:13px;">Executive read-only view. Detailed processing remains with operational officers.</div>
    </div>
    <a href="{{ route('executive.dashboard') }}" class="btn btn-sm btn-outline-dark">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
      <form class="d-flex gap-2 flex-wrap align-items-center" method="GET">
        <div class="fw-bold me-2">Filter</div>
        <select name="status" class="form-select form-select-sm" style="width:220px;">
          <option value="">All statuses</option>
          @foreach($statusList as $s)
            <option value="{{ $s }}" @selected($status === $s)>{{ ucwords(str_replace('_',' ', $s)) }}</option>
          @endforeach
        </select>
        <button class="btn btn-sm btn-primary" type="submit"><i class="ri-filter-3-line me-1"></i>Apply</button>
        <a class="btn btn-sm btn-outline-secondary" href="{{ request()->url() }}">Reset</a>
      </form>
      <div class="text-muted small">Showing {{ $applications->firstItem() ?? 0 }}-{{ $applications->lastItem() ?? 0 }} of {{ $applications->total() }}</div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 zmc-mini-table">
          @include('staff.partials._application_table_headers')
          <tbody>
            @forelse($applications as $i => $app)
              <tr>
                @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
                <td class="text-end">
                  <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">No applications found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-3">
    {{ $applications->links() }}
  </div>
</div>
@endsection
