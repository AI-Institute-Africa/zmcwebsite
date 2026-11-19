@extends('layouts.portal')

@section('title', 'Annual Levy (AP6)')

@section('content')
<div style="font-family:'Roboto', sans-serif; color:#334155;">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Annual Levy Declarations (AP6)</h4>
      <div class="text-muted mt-1" style="font-size:13px;">
        <i class="ri-information-line me-1"></i>
        Submit your annual levy declaration. The levy is calculated at 0.5% of gross annual turnover.
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('mediahouse.portal') }}" class="btn btn-outline-secondary btn-sm px-3">
        <i class="ri-arrow-left-line me-1"></i> Dashboard
      </a>
      <a href="{{ route('mediahouse.levy.create') }}" class="btn btn-dark btn-sm px-3">
        <i class="ri-file-add-line me-1"></i> New AP6 Declaration
      </a>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="border rounded-3 p-3 text-center" style="background:#fff;">
        <div class="text-muted" style="font-size:11px;">Total Declarations</div>
        <div class="fw-bold" style="font-size:22px; color:#1e293b;">{{ $levies->count() }}</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="border rounded-3 p-3 text-center" style="background:#f0fdf4;">
        <div class="text-muted" style="font-size:11px;">Paid</div>
        <div class="fw-bold" style="font-size:22px; color:#166534;">{{ $levies->where('payment_status', 'paid')->count() }}</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="border rounded-3 p-3 text-center" style="background:#fef3c7;">
        <div class="text-muted" style="font-size:11px;">Pending</div>
        <div class="fw-bold" style="font-size:22px; color:#92400e;">{{ $levies->where('payment_status', 'pending')->count() }}</div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="border rounded-3 p-3 text-center" style="background:#fff;">
        <div class="text-muted" style="font-size:11px;">Total Levy Amount</div>
        <div class="fw-bold" style="font-size:22px; color:#1e293b;">
          ${{ number_format($levies->sum('levy_amount'), 2) }}
        </div>
      </div>
    </div>
  </div>

  <div class="border-0 shadow-sm rounded-3 overflow-hidden" style="background:#fff;">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0"><i class="ri-list-check-2 me-2" style="color:#92400e;"></i>Levy History</h6>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th><i class="ri-hashtag me-1"></i> Reference</th>
            <th>Entity</th>
            <th>Financial Year</th>
            <th>Turnover</th>
            <th>Levy Amount</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse($levies as $levy)
            @php
              $badge = match($levy->payment_status) {
                'paid' => 'success',
                'pending' => 'warning',
                'rejected' => 'danger',
                default => 'secondary'
              };
            @endphp
            <tr>
              <td class="fw-bold">{{ $levy->reference }}</td>
              <td>{{ $levy->entity_name }}</td>
              <td>{{ $levy->financial_year }}</td>
              <td>{{ $levy->currency }} {{ number_format($levy->gross_annual_turnover, 2) }}</td>
              <td class="fw-bold">{{ $levy->currency }} {{ number_format($levy->levy_amount, 2) }}</td>
              <td>
                <span class="badge rounded-pill bg-{{ $badge }} px-3">
                  {{ ucfirst($levy->payment_status) }}
                </span>
              </td>
              <td class="small text-muted">{{ $levy->created_at->format('d M Y H:i') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-5 text-muted">
                <i class="ri-file-list-3-line d-block mb-2" style="font-size:32px;"></i>
                No levy declarations yet. Click "New AP6 Declaration" to get started.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>
@endsection
