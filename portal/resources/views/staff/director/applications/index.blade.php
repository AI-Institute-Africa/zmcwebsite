@extends('layouts.portal')
@section('title', 'Applications Awaiting Director Review')

@section('content')
<div class="zmc-dashboard-wrapper">
  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="fw-bold m-0">Director Review</h1>
      <div class="text-muted small">Applications awaiting your approval.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('executive.dashboard') }}" class="btn btn-white border btn-sm px-3">
        <i class="ri-dashboard-line me-1"></i> Dashboard
      </a>
    </div>
  </div>

  {{-- Table --}}
  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h2 class="fw-bold m-0">
        <i class="ri-list-check-2 me-2" style="color:var(--zmc-accent)"></i>
        Pending Applications
      </h2>
      <div class="small text-muted">Showing {{ $applications->total() }} total items</div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
        @forelse($applications as $i => $app)
          @php
            $ref = $app->reference ?: ('APP-' . str_pad($app->id, 5, '0', STR_PAD_LEFT));
          @endphp
          <tr>
            @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
            <td class="text-end">
              <a href="{{ route('staff.director.applications.show', $app) }}" class="btn btn-sm btn-outline-secondary">
                <i class="ri-eye-line me-1"></i> Review
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="15" class="text-center py-5 text-muted">No applications awaiting your review.</td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-4">
    {{ $applications->links() }}
  </div>
</div>
@endsection
