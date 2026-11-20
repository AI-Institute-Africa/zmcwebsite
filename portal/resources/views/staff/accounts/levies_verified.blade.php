@extends('layouts.portal')
@section('title', 'Verified Levy Payments — Accounts')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color: var(--zmc-text-dark);">
        <i class="ri-check-double-line me-2 text-success"></i>Verified Levy Payments
      </h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Successfully verified annual levy payments
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a href="{{ route('staff.accounts.levies.pending') }}" class="btn btn-warning btn-sm px-3">
        <i class="ri-time-line me-1"></i> Pending
      </a>
      <a href="{{ route('staff.accounts.levies.rejected') }}" class="btn btn-danger btn-sm px-3">
        <i class="ri-close-circle-line me-1"></i> Rejected
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

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
      <div class="zmc-card text-center p-3 h-100 border-success">
        <div class="h3 fw-black mb-0 text-success">{{ $stats['verified_count'] }}</div>
        <div class="small text-muted fw-bold">Total Verified</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="zmc-card text-center p-3 h-100">
        <div class="h3 fw-black mb-0" style="color:#1a1a1a;">${{ number_format($stats['verified_amount'], 2) }}</div>
        <div class="small text-muted fw-bold">Total Amount</div>
      </div>
    </div>
    <div class="col-6 col-md-4">
      <div class="zmc-card text-center p-3 h-100 border-primary">
        <div class="h3 fw-black mb-0 text-primary">{{ $stats['this_month'] }}</div>
        <div class="small text-muted fw-bold">This Month</div>
      </div>
    </div>
  </div>

  <div class="zmc-card mb-4">
    <h2 class="fw-bold mb-3"><i class="ri-filter-3-line me-2"></i> Filters</h2>
    <form method="GET" action="{{ route('staff.accounts.levies.verified') }}" id="filterForm">
      <div class="row g-2">
        <div class="col-12 col-md-4 col-lg-3">
          <label class="form-label small fw-bold">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Entity, Reference, Receipt..." value="{{ request('search') }}">
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
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Currency</label>
          <select name="currency" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="USD" {{ request('currency') === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="ZWG" {{ request('currency') === 'ZWG' ? 'selected' : '' }}>ZWG</option>
            <option value="ZWL" {{ request('currency') === 'ZWL' ? 'selected' : '' }}>ZWL</option>
            <option value="ZiG" {{ request('currency') === 'ZiG' ? 'selected' : '' }}>ZiG</option>
          </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Date From</label>
          <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
          <label class="form-label small fw-bold">Date To</label>
          <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <div class="col-12 col-md-3 col-lg-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-fill">
            <i class="ri-search-line me-1"></i> Filter
          </button>
        </div>
      </div>
      @if(request()->hasAny(['search','financial_year','currency','date_from','date_to']))
        <div class="mt-2">
          <a href="{{ route('staff.accounts.levies.verified') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ri-close-line me-1"></i> Clear Filters
          </a>
        </div>
      @endif
    </form>
  </div>

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h3 class="fw-bold m-0"><i class="ri-check-double-line me-2 text-success"></i>Verified Levies</h3>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size:13px;">
        <thead class="bg-light">
          <tr>
            <th class="ps-3">Reference</th>
            <th>Entity Name</th>
            <th>Financial Year</th>
            <th>Levy Amount</th>
            <th>Payment Method</th>
            <th>Receipt Number</th>
            <th>Payment Date</th>
            <th>Contact</th>
            <th class="pe-3 text-end">Actions</th>
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
              <td class="fw-bold text-success">{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</td>
              <td>
                <span class="badge bg-info-subtle text-info">
                  {{ ucwords(str_replace('_', ' ', $levy->payment_method ?? 'N/A')) }}
                </span>
              </td>
              <td class="font-monospace">{{ $levy->receipt_number ?? '—' }}</td>
              <td class="small">{{ $levy->payment_date ? $levy->payment_date->format('d M Y H:i') : '—' }}</td>
              <td class="small">
                @if($levy->user)
                  <div>{{ $levy->user->email }}</div>
                  @if($levy->telephone)
                    <div class="text-muted">{{ $levy->telephone }}</div>
                  @endif
                @endif
              </td>
              <td class="pe-3 text-end">
                <a href="{{ route('staff.accounts.levies.receipt', $levy->id) }}" 
                   class="btn btn-sm btn-primary" 
                   target="_blank">
                  <i class="ri-file-pdf-line me-1"></i> Receipt
                </a>
                @php
                  $verificationNotes = $levy->form_data['verification_notes'] ?? null;
                @endphp
                @if($verificationNotes)
                  <button type="button" class="btn btn-sm btn-info" 
                          data-bs-toggle="modal" 
                          data-bs-target="#notesModal{{ $levy->id }}">
                    <i class="ri-file-text-line"></i> Notes
                  </button>
                @endif
              </td>
            </tr>

            {{-- Notes Modal --}}
            @if($verificationNotes)
            <div class="modal fade" id="notesModal{{ $levy->id }}" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h4 class="modal-title">Verification Notes</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p class="mb-0">{{ $verificationNotes }}</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
                </div>
              </div>
            </div>
            @endif
          @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">
                <i class="ri-inbox-line d-block mb-2" style="font-size:48px; opacity:0.3;"></i>
                <div>No verified levy payments found</div>
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
