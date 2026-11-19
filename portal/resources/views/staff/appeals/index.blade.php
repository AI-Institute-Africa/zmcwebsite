@extends('layouts.portal')
@section('title', 'All Appeals')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Inter', sans-serif;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold m-0" style="color:#0f172a;">All Appeals</h3>
      <div class="text-muted small">Manage and review all public appeals</div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="zmc-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label zmc-lbl">Search</label>
        <input type="text" name="search" class="form-control zmc-input" value="{{ request('search') }}" placeholder="Ref #, Name, Email...">
      </div>
      <div class="col-md-3">
        <label class="form-label zmc-lbl">Status</label>
        <select name="status" class="form-select zmc-input">
          <option value="">All Statuses</option>
          <option value="pending_payment" {{ request('status') === 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
          <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
          <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
          <option value="awaiting_information" {{ request('status') === 'awaiting_information' ? 'selected' : '' }}>Awaiting Info</option>
          <option value="response_sent" {{ request('status') === 'response_sent' ? 'selected' : '' }}>Response Sent</option>
          <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label zmc-lbl">Payment Status</label>
        <select name="payment_status" class="form-select zmc-input">
          <option value="">All Payments</option>
          <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
          <option value="failed" {{ request('payment_status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-dark w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
        <a href="{{ route('staff.appeals.index') }}" class="btn btn-light w-100">Reset</a>
      </div>
    </form>
  </div>

  {{-- Table --}}
  <div class="zmc-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle zmc-table mb-0">
        <thead>
          <tr>
            <th>Ref #</th>
            <th>Applicant</th>
            <th>Subject</th>
            <th>Category</th>
            <th>Payment Status</th>
            <th>Status</th>
            <th>Officer</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($appeals as $appeal)
            <tr>
              <td class="fw-bold text-primary">{{ $appeal->reference_number ?? 'N/A' }}</td>
              <td>
                <div class="fw-bold">{{ $appeal->full_name }}</div>
                <div class="small text-muted">{{ $appeal->email }}</div>
              </td>
              <td>{{ \Illuminate\Support\Str::limit($appeal->subject, 30) }}</td>
              <td>{{ $appeal->category->name ?? 'Uncategorized' }}</td>
              <td>
                @if($appeal->payment_status === 'paid')
                  <span class="badge bg-success">Paid</span>
                @elseif($appeal->payment_status === 'failed')
                  <span class="badge bg-danger">Failed</span>
                @else
                  <span class="badge bg-warning text-dark">Pending</span>
                @endif
              </td>
              <td>
                <span class="badge bg-secondary">{{ strtoupper(str_replace('_', ' ', $appeal->status)) }}</span>
              </td>
              <td>{{ $appeal->handler->name ?? 'Unassigned' }}</td>
              <td>
                <a href="{{ route('staff.appeals.show', $appeal) }}" class="btn btn-sm btn-outline-dark">Manage</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">No appeals found matching criteria.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">
      {{ $appeals->links() }}
    </div>
  </div>
</div>
@endsection
