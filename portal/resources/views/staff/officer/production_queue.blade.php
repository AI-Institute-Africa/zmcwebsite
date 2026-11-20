@extends('layouts.portal')
@section('title', 'Production Queue')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="fw-bold mb-0">Production Queue</h1>
    <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-secondary btn-sm">Back to Dashboard</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="card">
    <div class="card-header fw-bold">Payment Verified — Ready for Production</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 zmc-mini-table">
          @include('staff.partials._application_table_headers')
          <tbody>
            @forelse($applications as $i => $app)
              <tr>
                @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
                <td class="text-end">
                  <a href="{{ route('staff.production.applications.show', $app) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">No applications in the production queue.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  @if(method_exists($applications, 'links'))
    <div class="mt-3">{{ $applications->links() }}</div>
  @endif
@endsection