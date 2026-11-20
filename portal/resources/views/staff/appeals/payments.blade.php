@extends('layouts.portal')
@section('title', 'Appeal Payments')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Inter', sans-serif;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="fw-bold m-0" style="color:#0f172a;">Appeal Payments</h1>
      <div class="text-muted small">Manage payments and transactions for appeals</div>
    </div>
  </div>

  <div class="zmc-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label zmc-lbl">Search</label>
        <input type="text" name="search" class="form-control zmc-input" value="{{ request('search') }}" placeholder="Paynow Ref, Appeal Ref, Name...">
      </div>
      <div class="col-md-3">
        <label class="form-label zmc-lbl">Status</label>
        <select name="status" class="form-select zmc-input">
          <option value="">All Payments</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
          <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-dark w-100"><i class="ri-filter-3-line me-1"></i>Filter</button>
        <a href="{{ route('staff.appeals.payments') }}" class="btn btn-light w-100">Reset</a>
      </div>
    </form>
  </div>

  <div class="zmc-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle zmc-table mb-0">
        <thead>
          <tr>
            <th>Date</th>
            <th>Applicant</th>
            <th>Appeal Ref</th>
            <th>Paynow Ref</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Currency</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($payments as $payment)
            @php($latestAttempt = $payment->appealPayments->first())
            <tr>
              <td>{{ $payment->created_at->format('d M Y, H:i') }}</td>
              <td>
                <div class="fw-bold">{{ $payment->full_name }}</div>
                <div class="small text-muted">{{ $payment->email }}</div>
              </td>
              <td class="fw-bold">{{ $payment->reference_number ?? 'N/A' }}</td>
              <td>{{ $latestAttempt?->reference ?? $payment->payment_ref ?? 'N/A' }}</td>
              <td>{{ $payment->payment_currency ?? $latestAttempt?->currency ?? 'USD' }} {{ number_format((float) ($payment->payment_amount ?? $latestAttempt?->amount ?? 0), 2) }}</td>
              <td>
                @if($payment->payment_status === 'paid')
                  <span class="badge bg-success">PAID</span>
                @elseif($payment->payment_status === 'rejected' || $latestAttempt?->status === 'failed')
                  <span class="badge bg-danger">FAILED</span>
                @else
                  <span class="badge bg-warning text-dark">{{ strtoupper($latestAttempt?->status ?? 'PENDING') }}</span>
                @endif
              </td>
              <td>
                <a href="{{ route('staff.appeals.show', $payment) }}" class="btn btn-sm btn-outline-dark">View Appeal</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No payments found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">
      {{ $payments->links() }}
    </div>
  </div>
</div>
@endsection
