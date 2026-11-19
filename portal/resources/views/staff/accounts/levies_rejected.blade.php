@extends('layouts.portal')
@section('title', 'Rejected Levy Payments — Accounts')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
        <i class="ri-close-circle-line me-2 text-danger"></i>Rejected Levy Payments
      </h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Levy payments that were rejected and require resubmission
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('staff.accounts.levies.pending') }}" class="btn btn-warning btn-sm px-3">
        <i class="ri-time-line me-1"></i> Pending
      </a>
      <a href="{{ route('staff.accounts.levies.verified') }}" class="btn btn-success btn-sm px-3">
        <i class="ri-check-double-line me-1"></i> Verified
      </a>
      <a href="{{ route('staff.accounts.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-arrow-left-line me-1"></i> Dashboard
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size: var(--font-size-lg); line-height: 1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <div class="zmc-card mb-4">
    <h6 class="fw-bold mb-3"><i class="ri-filter-3-line me-2"></i> Filters</h6>
    <form method="GET" action="{{ route('staff.accounts.levies.rejected') }}" id="filterForm">
      <div class="row g-2">
        <div class="col-12 col-md-4 col-lg-3">
          <label class="form-label small fw-bold">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Entity name, Reference..." value="{{ request('search') }}">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Financial Year</label>
          <select name="financial_year" class="form-select form-select-sm">
            <option value="">All Years</option>
            @foreach($availableYears as $yr)
            <option value="{{ $yr }}" {{ request('financial_year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3 col-lg-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i> Filter
          </button>
        </div>
      </div>
      @if(request()->hasAny(['search','financial_year']))
        <div class="mt-2">
          <a href="{{ route('staff.accounts.levies.rejected') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ri-close-line me-1"></i> Clear Filters
          </a>
        </div>
      @endif
    </form>
  </div>

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom">
      <h6 class="fw-bold m-0"><i class="ri-close-circle-line me-2 text-danger"></i>Rejected Levies</h6>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size:13px;">
        <thead class="bg-light">
          <tr>
            <th class="ps-3">Reference</th>
            <th>Entity Name</th>
            <th>Financial Year</th>
            <th>Levy Amount</th>
            <th>Rejected Date</th>
            <th>Rejection Reason</th>
            <th>Contact</th>
          </tr>
        </thead>
        <tbody>
          @forelse($levies as $levy)
            <tr>
              <td class="ps-3 fw-bold font-monospace">{{ $levy->reference }}</td>
              <td>
                <div class="fw-semibold">{{ $levy->entity_name }}</div>
                @if($levy->user)
                  <div class="small text-muted">{{ $levy->user->name }}</div>
                @endif
              </td>
              <td>
                <span class="badge bg-primary-subtle text-primary">{{ $levy->financial_year }}</span>
              </td>
              <td class="fw-bold">{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</td>
              <td class="small">
                @php
                  $rejectedAt = $levy->form_data['rejected_at'] ?? null;
                @endphp
                {{ $rejectedAt ? \Carbon\Carbon::parse($rejectedAt)->format('d M Y H:i') : $levy->updated_at->format('d M Y H:i') }}
              </td>
              <td style="max-width: 300px;">
                @php
                  $reason = $levy->form_data['rejection_reason'] ?? 'No reason provided';
                @endphp
                <div class="text-wrap">{{ Str::limit($reason, 100) }}</div>
                @if(strlen($reason) > 100)
                  <button type="button" class="btn btn-sm btn-link p-0" 
                          data-bs-toggle="modal" 
                          data-bs-target="#reasonModal{{ $levy->id }}">
                    Read more
                  </button>
                @endif
              </td>
              <td class="small">
                @if($levy->user)
                  <div>{{ $levy->user->email }}</div>
                  @if($levy->telephone)
                    <div class="text-muted">{{ $levy->telephone }}</div>
                  @endif
                @endif
              </td>
            </tr>

            {{-- Reason Modal --}}
            <div class="modal fade" id="reasonModal{{ $levy->id }}" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Rejection Reason</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="alert alert-light">
                      <strong>{{ $levy->entity_name }}</strong><br>
                      <small>{{ $levy->reference }} | {{ $levy->financial_year }}</small>
                    </div>
                    <p class="mb-0">{{ $reason }}</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="ri-inbox-line d-block mb-2" style="font-size:48px; opacity:0.3;"></i>
                <div>No rejected levy payments found</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($levies->hasPages())
      <div class="p-3 border-top">
        {{ $levies->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
