@extends('layouts.portal')
@section('title', 'Pending Payments')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Pending Payments</h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">Applications awaiting payment verification. Use PayNow Transactions / Payment Proofs / Waivers to clear items.</div>
    </div>
    <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3"><i class="ri-refresh-line me-1"></i> Refresh</a>
  </div>

  <div class="zmc-card p-0">
    <div class="p-3 border-bottom fw-bold"><i class="ri-hourglass-line me-2" style="color:var(--zmc-accent)"></i> Pending</div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
          @forelse($applications as $i => $app)
            <tr>
              @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
              <td class="text-end">
                <a class="btn btn-sm zmc-icon-btn btn-outline-primary" href="{{ route('staff.accounts.applications.show', $app->id) }}" data-bs-toggle="tooltip" title="Open"><i class="ri-eye-line"></i></a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted p-4">No pending payments.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $applications->links() }}</div>
  </div>
</div>
@endsection
