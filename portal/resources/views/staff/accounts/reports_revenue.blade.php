@extends('layouts.portal')
@section('title', 'Revenue Reports')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Revenue Reports</h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        Confirmed payment revenue from the <strong>payments</strong> ledger.
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <form action="{{ route('staff.accounts.reports.revenue') }}" method="GET" id="revenueYearForm">
        @foreach(request()->except('year') as $k => $v)
          <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <select name="year" class="form-select fw-bold"
          style="height:38px;min-width:130px;background:#fff;color:#1a1a2e;border:2px solid #f59e0b;border-radius:8px;font-size:14px;padding:0 12px;cursor:pointer;box-shadow:0 2px 6px rgba(245,158,11,.25);"
          onchange="document.getElementById('revenueYearForm').submit()">
          @foreach($availableYears ?? [] as $y)
            <option value="{{ $y }}" {{ (isset($year) && $year == $y) ? 'selected' : '' }}>Year: {{ $y }}</option>
          @endforeach
        </select>
      </form>
      <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-refresh-line me-1"></i> Refresh
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="zmc-card mb-4">
    <form method="GET" action="{{ route('staff.accounts.reports.revenue') }}" class="row g-2 align-items-end">
      <input type="hidden" name="year" value="{{ $year }}">
      <div class="col-12 col-md-2">
        <label class="form-label small fw-bold text-muted">From Date</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label small fw-bold text-muted">To Date</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
      </div>
      <div class="col-12 col-md-2">
        <label class="form-label small fw-bold text-muted">Payment Method</label>
        <select name="method" class="form-select form-select-sm">
          <option value="">All Methods</option>
          @foreach($availableMethods ?? [] as $m)
            <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>
              {{ $methodLabels[$m] ?? ucfirst($m) }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label small fw-bold text-muted">Service Type</label>
        <select name="service_type" class="form-select form-select-sm">
          <option value="">All Types</option>
          <option value="accreditation" {{ request('service_type') === 'accreditation' ? 'selected' : '' }}>Accreditation</option>
          <option value="registration" {{ request('service_type') === 'registration' ? 'selected' : '' }}>Registration</option>
        </select>
      </div>
      <div class="col-12 col-md-3 d-flex gap-2 align-items-end">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="ri-filter-line me-1"></i> Filter
        </button>
        <a href="{{ route('staff.accounts.reports.revenue', ['year' => $year]) }}" class="btn btn-outline-secondary btn-sm">
          Clear
        </a>
        <a href="{{ route('staff.accounts.reports.export-ledger', array_merge(request()->query(), ['bucket' => 'all'])) }}"
           class="btn btn-outline-success btn-sm ms-auto">
          <i class="ri-download-2-line me-1"></i> Export CSV
        </a>
      </div>
    </form>
  </div>

  {{-- KPI Cards --}}
  <style>
    .rev-kpi-label { font-size: 11px; font-weight: 700; color: #64748b; white-space: nowrap; }
    .rev-kpi-value { font-size: 26px; font-weight: 900; margin: 0; }
    .rev-kpi-icon  { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; border-radius: 8px; }
  </style>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rev-kpi-label">Total Revenue (USD)</div>
            <div class="rev-kpi-value text-success">${{ number_format($totalRevenue, 2) }}</div>
            <div class="small text-muted mt-1">{{ $paidCount }} confirmed payments</div>
          </div>
          <div class="rev-kpi-icon" style="background:rgba(16,185,129,.1);">
            <i class="ri-money-dollar-circle-line text-success"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rev-kpi-label">Avg. per Payment</div>
            <div class="rev-kpi-value text-primary">
              ${{ $paidCount > 0 ? number_format($totalRevenue / $paidCount, 2) : '0.00' }}
            </div>
            <div class="small text-muted mt-1">Overall average</div>
          </div>
          <div class="rev-kpi-icon" style="background:rgba(59,130,246,.1);">
            <i class="ri-bar-chart-grouped-line text-primary"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rev-kpi-label">Waivers Granted ({{ $year }})</div>
            <div class="rev-kpi-value" style="color:#b45309;">{{ $waivedCount }}</div>
            <div class="small text-muted mt-1">Applications waived</div>
          </div>
          <div class="rev-kpi-icon" style="background:rgba(245,158,11,.1);">
            <i class="ri-hand-coin-line text-warning"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="zmc-card h-100">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div>
            <div class="rev-kpi-label">Top Method</div>
            <div class="rev-kpi-value" style="font-size:18px; color:#1e293b;">
              {{ isset($byMethod[0]) ? ($methodLabels[$byMethod[0]->method] ?? ucfirst($byMethod[0]->method)) : '—' }}
            </div>
            <div class="small text-muted mt-1">
              {{ isset($byMethod[0]) ? '$'.number_format($byMethod[0]->total, 2) : 'No data' }}
            </div>
          </div>
          <div class="rev-kpi-icon" style="background:rgba(99,102,241,.1);">
            <i class="ri-bank-card-line" style="color:#6366f1;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts Row --}}
  <div class="row g-4 mb-4">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
          <div class="fw-bold"><i class="ri-bar-chart-box-line me-2" style="color:var(--zmc-accent)"></i>Monthly Revenue — {{ $year }}</div>
          <span class="badge bg-success-subtle text-success border border-success-subtle">USD</span>
        </div>
        <div class="card-body">
          <div style="position:relative; height:280px; width:100%;">
            <canvas id="revenueBarChart"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-pie-chart-line me-2" style="color:var(--zmc-accent)"></i>Revenue by Method</div>
        </div>
        <div class="card-body d-flex flex-column align-items-center justify-content-center">
          <div style="position:relative; height:220px; width:100%;">
            <canvas id="revenuePieChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Revenue by Service Type --}}
  @if($byServiceType->count())
  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 fw-bold py-3">
          <i class="ri-stack-line me-2" style="color:var(--zmc-accent)"></i>Revenue by Service Type
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
              <thead class="bg-light">
                <tr>
                  <th class="ps-3" style="width:180px;">Application Type</th>
                  <th style="width:160px;">Category</th>
                  <th class="text-center" style="width:120px;">Payments</th>
                  <th class="text-end pe-3" style="width:150px;">Total Revenue</th>
                </tr>
              </thead>
              <tbody>
                @foreach($byServiceType as $row)
                @php
                  $isAccred = strtolower($row->application_type ?? '') === 'accreditation';
                  $isNew    = strtolower($row->request_type ?? '') === 'new';
                  $typeColor = $isAccred ? '#6366f1' : '#059669';
                  $typeBg    = $isAccred ? 'rgba(99,102,241,.1)' : 'rgba(16,185,129,.1)';
                  $reqColor  = $isNew ? '#0ea5e9' : '#f59e0b';
                  $reqBg     = $isNew ? 'rgba(14,165,233,.1)' : 'rgba(245,158,11,.1)';
                @endphp
                <tr>
                  <td class="ps-3">
                    <span class="badge rounded-pill px-3 py-1"
                          style="background:{{ $typeBg }}; color:{{ $typeColor }};">
                      <i class="ri-{{ $isAccred ? 'id-card' : 'building-2' }}-line me-1"></i>
                      {{ ucfirst($row->application_type ?? '—') }}
                    </span>
                  </td>
                  <td>
                    <span class="badge rounded-pill px-3 py-1"
                          style="background:{{ $reqBg }}; color:{{ $reqColor }};">
                      <i class="ri-{{ $isNew ? 'add-circle' : 'refresh' }}-line me-1"></i>
                      {{ ucfirst($row->request_type ?? '—') }}
                    </span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border px-3">{{ $row->cnt }}</span>
                  </td>
                  <td class="text-end pe-3 fw-bold text-success">${{ number_format($row->total, 2) }}</td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr style="background:#1e293b; color:#fff;">
                  <td class="ps-3 fw-bold" colspan="2">Grand Total</td>
                  <td class="text-center fw-bold">{{ $byServiceType->sum('cnt') }}</td>
                  <td class="text-end pe-3 fw-bold">${{ number_format($byServiceType->sum('total'), 2) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Recent Transactions --}}
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 fw-bold py-3 d-flex justify-content-between align-items-center">
      <span><i class="ri-receipt-line me-2" style="color:var(--zmc-accent)"></i>Recent Confirmed Payments ({{ $year }})</span>
      <span class="badge bg-light text-dark border">{{ $recentPayments->count() }} shown</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle small mb-0">
          <thead class="bg-light">
            <tr>
              <th class="ps-3">Receipt #</th>
              <th>Applicant</th>
              <th>Reference</th>
              <th>Method</th>
              <th>Service</th>
              <th class="text-end">Amount</th>
              <th>Confirmed At</th>
              <th>Receipted By</th>
              <th class="text-end pe-3">Receipt</th>
            </tr>
          </thead>
          <tbody>
            @forelse($recentPayments as $pay)
            <tr>
              <td class="ps-3">
                <span class="badge bg-light text-dark border">{{ $pay->receipt_number ?? '—' }}</span>
              </td>
              <td>{{ $pay->application?->applicant?->name ?? '—' }}</td>
              <td class="fw-bold">{{ $pay->application?->reference ?? '—' }}</td>
              <td>
                <span class="badge bg-light text-dark border">
                  {{ $methodLabels[$pay->method] ?? ucfirst($pay->method ?? '—') }}
                </span>
              </td>
              <td class="text-muted">{{ ucwords(str_replace('_', ' ', $pay->service_type ?? '—')) }}</td>
              <td class="text-end fw-bold text-success">${{ number_format($pay->amount, 2) }}</td>
              <td>{{ $pay->confirmed_at?->format('d M Y H:i') ?? '—' }}</td>
              <td class="text-muted">{{ $pay->recorder?->name ?? '—' }}</td>
              <td class="text-end pe-3">
                @if($pay->application_id)
                  <a href="{{ route('staff.accounts.applications.receipt', $pay->application_id) }}"
                     class="btn btn-sm btn-outline-info" target="_blank" title="Download Receipt">
                    <i class="ri-file-download-line"></i>
                  </a>
                @else
                  <span class="text-muted">—</span>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">No confirmed payments found for the selected filters.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var chartData = @json($revenueChartData ?? null);
  var pieData   = @json($pieData ?? null);

  // Bar chart — monthly revenue
  var barCtx = document.getElementById('revenueBarChart');
  if (barCtx && chartData) {
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: chartData.labels,
        datasets: [
          {
            label: 'Revenue (USD)',
            data: chartData.revenue,
            backgroundColor: 'rgba(16,185,129,0.75)',
            borderColor: '#10b981',
            borderWidth: 1,
            borderRadius: 5,
            yAxisID: 'yRevenue',
          },
          {
            label: 'Payments',
            data: chartData.count,
            type: 'line',
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.1)',
            borderWidth: 2,
            pointRadius: 4,
            fill: false,
            tension: 0.3,
            yAxisID: 'yCount',
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'top', labels: { usePointStyle: true, padding: 15 } },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                if (ctx.dataset.yAxisID === 'yRevenue') {
                  return ' $' + ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2});
                }
                return ' ' + ctx.parsed.y + ' payments';
              }
            }
          }
        },
        scales: {
          yRevenue: {
            type: 'linear',
            position: 'left',
            beginAtZero: true,
            ticks: {
              callback: function(v) { return '$' + v.toLocaleString(); }
            },
            grid: { color: 'rgba(0,0,0,0.05)' }
          },
          yCount: {
            type: 'linear',
            position: 'right',
            beginAtZero: true,
            grid: { drawOnChartArea: false },
            ticks: { stepSize: 1 }
          }
        }
      }
    });
  }

  // Doughnut — revenue by method
  var pieCtx = document.getElementById('revenuePieChart');
  if (pieCtx && pieData && pieData.labels.length) {
    var palette = ['#10b981','#6366f1','#f59e0b','#3b82f6','#ef4444','#8b5cf6','#14b8a6'];
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: pieData.labels,
        datasets: [{
          data: pieData.totals,
          backgroundColor: palette.slice(0, pieData.labels.length),
          borderWidth: 2,
          borderColor: '#fff',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { usePointStyle: true, padding: 10, font: { size: 11 } } },
          tooltip: {
            callbacks: {
              label: function(ctx) {
                return ' $' + ctx.parsed.toLocaleString(undefined, {minimumFractionDigits: 2});
              }
            }
          }
        }
      }
    });
  }
});
</script>
@endsection
