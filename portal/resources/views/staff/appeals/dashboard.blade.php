@extends('layouts.portal')
@section('title', 'FOIA Appeals Management')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Inter', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="fw-bold m-0" style="color:#0f172a;">FOIA Appeals Management</h1>
      <div class="text-muted small">Manage public appeals received from the website</div>
    </div>
    <div>
      <a href="{{ route('staff.appeals.index') }}" class="btn btn-dark"><i class="ri-list-check me-1"></i>View All Appeals</a>
    </div>
  </div>

  {{-- KPI Cards --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="zmc-card p-3 d-flex align-items-center">
        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3">
          <i class="ri-scales-3-line fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-bold text-uppercase">Total Appeals</div>
          <h2 class="m-0 fw-bold">{{ number_format($stats['total']) }}</h2>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card p-3 d-flex align-items-center">
        <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 me-3">
          <i class="ri-inbox-line fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-bold text-uppercase">New Appeals</div>
          <h3 class="m-0 fw-bold">{{ number_format($stats['new']) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card p-3 d-flex align-items-center">
        <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-3 me-3">
          <i class="ri-money-dollar-circle-line fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-bold text-uppercase">Pending Payments</div>
          <h3 class="m-0 fw-bold">{{ number_format($stats['pending_payment']) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card p-3 d-flex align-items-center">
        <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 me-3">
          <i class="ri-check-double-line fs-4"></i>
        </div>
        <div>
          <div class="text-muted small fw-bold text-uppercase">Closed Appeals</div>
          <h3 class="m-0 fw-bold">{{ number_format($stats['closed']) }}</h3>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row 1 --}}
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="zmc-card h-100">
        <h4 class="fw-bold mb-3">Appeals Submitted by Month</h4>
        <div style="height:300px;">
          <canvas id="monthlyChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="zmc-card h-100">
        <h5 class="fw-bold mb-3">Appeals by Status</h5>
        <div style="height:300px;">
          <canvas id="statusChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row 2 --}}
  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="zmc-card h-100">
        <h6 class="fw-bold mb-3">Appeals by Category</h6>
        <div style="height:300px;">
          <canvas id="categoryChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="zmc-card h-100">
        <h6 class="fw-bold mb-3">Payment Status Distribution</h6>
        <div style="height:300px;">
          <canvas id="paymentChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  {{-- Recent Appeals Table --}}
  <div class="zmc-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold m-0">Recent Appeals</h6>
      <a href="{{ route('staff.appeals.index') }}" class="btn btn-sm btn-outline-dark">View All</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle zmc-table mb-0">
        <thead>
          <tr>
            <th>Ref #</th>
            <th>Applicant</th>
            <th>Category</th>
            <th>Submission Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentAppeals as $appeal)
            <tr>
              <td class="fw-bold text-primary">{{ $appeal->reference_number ?? 'N/A' }}</td>
              <td>
                <div class="fw-bold">{{ $appeal->full_name }}</div>
                <div class="small text-muted">{{ $appeal->email }}</div>
              </td>
              <td>{{ $appeal->category->name ?? 'Uncategorized' }}</td>
              <td>{{ $appeal->created_at->format('d M Y, H:i') }}</td>
              <td>
                @if($appeal->status === 'open' || $appeal->status === 'pending_payment')
                  <span class="badge bg-warning text-dark">Pending</span>
                @elseif($appeal->status === 'closed')
                  <span class="badge bg-success">Closed</span>
                @else
                  <span class="badge bg-info">In Progress</span>
                @endif
              </td>
              <td>
                <a href="{{ route('staff.appeals.show', $appeal) }}" class="btn btn-sm btn-light">View Details</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No recent appeals found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Setup Monthly Chart
  const monthlyData = @json($monthlyAppeals);
  new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
      labels: Object.keys(monthlyData),
      datasets: [{
        label: 'Appeals',
        data: Object.values(monthlyData),
        backgroundColor: '#3b82f6',
        borderRadius: 4
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  // Setup Status Chart
  const statusData = @json($statusDistribution);
  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: Object.keys(statusData).map(k => k.replace('_', ' ').toUpperCase()),
      datasets: [{
        data: Object.values(statusData),
        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#64748b', '#ef4444']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  // Setup Category Chart
  const catData = @json($categoryDistribution);
  new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
      labels: Object.keys(catData),
      datasets: [{
        data: Object.values(catData),
        backgroundColor: ['#6366f1', '#ec4899', '#8b5cf6', '#14b8a6', '#f59e0b']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });

  // Setup Payment Chart
  const payData = @json($paymentDistribution);
  new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
      labels: Object.keys(payData).map(k => k.toUpperCase()),
      datasets: [{
        data: Object.values(payData),
        backgroundColor: ['#ef4444', '#10b981', '#f59e0b']
      }]
    },
    options: { responsive: true, maintainAspectRatio: false }
  });
</script>
@endpush
