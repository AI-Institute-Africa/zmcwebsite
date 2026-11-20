@php
  // Initial safe defaults (will be overwritten by AJAX)
  $analyticsTotals = $analyticsTotals ?? [
    'public_users' => 0,
    'staff_users' => 0,
    'accreditation' => 0,
    'registration' => 0,
  ];
@endphp

<div class="analytics-section mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="fw-bold m-0"><i class="ri-bar-chart-box-line me-2"></i>Analytics Overview</h1>
    @php
      $analyticsUser = auth()->user();
      $canSeeFullAnalytics = $analyticsUser
        && $analyticsUser->hasAnyRole(['super_admin', 'director', 'it_admin']);
    @endphp
    @if(Route::has('admin.analytics') && $canSeeFullAnalytics)
    <a href="{{ route('admin.analytics') }}" class="btn btn-sm btn-outline-primary">
      <i class="ri-line-chart-line me-1"></i>Full Analytics
    </a>
    @endif
  </div>

  @php
    $hideKpiCards = $analyticsUser && $analyticsUser->hasAnyRole(['accounts_payments', 'super_admin']);
  @endphp

  @if(!$hideKpiCards)
  {{-- Summary Cards --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
        <div class="card-body text-center py-3">
          <div class="text-muted small mb-1">Public Users</div>
          <div class="fs-3 fw-bold text-primary" id="analytics-total-public">{{ number_format($analyticsTotals['public_users'] ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);">
        <div class="card-body text-center py-3">
          <div class="text-muted small mb-1">Staff Users</div>
          <div class="fs-3 fw-bold" style="color: #92400e;" id="analytics-total-staff">{{ number_format($analyticsTotals['staff_users'] ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);">
        <div class="card-body text-center py-3">
          <div class="text-muted small mb-1">Accreditation Apps</div>
          <div class="fs-3 fw-bold text-info" id="analytics-total-accreditation">{{ number_format($analyticsTotals['accreditation'] ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
        <div class="card-body text-center py-3">
          <div class="text-muted small mb-1">Media House Regs</div>
          <div class="fs-3 fw-bold" style="color: #c2410c;" id="analytics-total-registration">{{ number_format($analyticsTotals['registration'] ?? 0) }}</div>
        </div>
      </div>
    </div>
  </div>
  @endif

  {{-- Charts Row --}}
  <div class="row g-4">
    {{-- Daily Trend Chart --}}
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-line-chart-line me-2"></i>Daily Trend ({{ isset($year) && (int)$year !== (int)now()->year ? $year : '30 days' }})</div>
        </div>
        <div class="card-body">
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="trendChart"></canvas>
          </div>
          <div class="text-muted small mt-2">Includes accreditation, media house registrations, and new public users per day.</div>
        </div>
      </div>
    </div>

    {{-- Status Breakdown Chart --}}
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-pie-chart-line me-2"></i>Applications by Status</div>
        </div>
        <div class="card-body">
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  let trendChart = null, statusChart = null;
  let pendingChartData = null;

  function showChartError(canvasId, message) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    var holder = canvas.parentElement;
    if (!holder) return;
    holder.innerHTML =
      '<div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted small text-center px-3">'
      + '<i class="ri-error-warning-line" style="font-size: 24px; color:#ef4444;"></i>'
      + '<div class="mt-2">' + message + '</div>'
      + '</div>';
  }

  function dataIsAllZero(data) {
    if (!data || !data.datasets) return true;
    var keys = ['accreditation', 'registration', 'public_users'];
    for (var i = 0; i < keys.length; i++) {
      var arr = data.datasets[keys[i]] || [];
      for (var j = 0; j < arr.length; j++) {
        if (Number(arr[j]) > 0) return false;
      }
    }
    return true;
  }

  function createOrUpdateCharts(data) {
    if (!data) return;

    if (typeof window.Chart === 'undefined') {
      // Chart.js not yet loaded; remember the payload and retry shortly.
      pendingChartData = data;
      return;
    }

    try {
      var trendCtx = document.getElementById('trendChart');
      if (trendCtx) {
        if (trendChart) {
          trendChart.data.labels = data.labels;
          trendChart.data.datasets[0].data = data.datasets.accreditation;
          trendChart.data.datasets[1].data = data.datasets.registration;
          trendChart.data.datasets[2].data = data.datasets.public_users;
          trendChart.update('none');
        } else {
          trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
              labels: data.labels,
              datasets: [
                {
                  label: 'Accreditation',
                  data: data.datasets.accreditation,
                  borderColor: '#3b82f6',
                  backgroundColor: 'rgba(59, 130, 246, 0.1)',
                  fill: true,
                  tension: 0.4
                },
                {
                  label: 'Media House',
                  data: data.datasets.registration,
                  borderColor: '#ec4899',
                  backgroundColor: 'rgba(236, 72, 153, 0.1)',
                  fill: true,
                  tension: 0.4
                },
                {
                  label: 'New Public Users',
                  data: data.datasets.public_users,
                  borderColor: '#f59e0b',
                  backgroundColor: 'rgba(245, 158, 11, 0.1)',
                  fill: true,
                  tension: 0.4
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'top',
                  labels: { usePointStyle: true, padding: 15 }
                }
              },
              scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
              }
            }
          });
        }

        // If everything is zero, draw a "no recent activity" hint over the
        // (still-rendered) axes so users know the chart is working.
        var hintHolder = trendCtx.parentElement;
        if (hintHolder) {
          var existingHint = hintHolder.querySelector('.zmc-trend-empty-hint');
          if (dataIsAllZero(data)) {
            if (!existingHint) {
              var hint = document.createElement('div');
              hint.className = 'zmc-trend-empty-hint position-absolute top-50 start-50 translate-middle text-muted small text-center px-3';
              hint.style.pointerEvents = 'none';
              hint.innerHTML = '<i class="ri-information-line me-1"></i>No new applications or sign-ups in this period yet.';
              hintHolder.appendChild(hint);
            }
          } else if (existingHint) {
            existingHint.remove();
          }
        }
      }

      var statusCtx = document.getElementById('statusChart');
      if (statusCtx) {
        var sb = data.status_breakdown || { labels: [], counts: [] };
        var hasStatus = (sb.counts || []).some(function(c){ return Number(c) > 0; });
        var sHolder = statusCtx.parentElement;
        // If data has arrived after an empty state, clear the placeholder so
        // we can render the real chart instead of leaving the canvas hidden.
        if (hasStatus && sHolder) {
          var existingStatusHint = sHolder.querySelector('.zmc-status-empty-hint');
          if (existingStatusHint) existingStatusHint.remove();
          statusCtx.style.display = '';
        }
        if (statusChart) {
          statusChart.data.labels = sb.labels || [];
          statusChart.data.datasets[0].data = sb.counts || [];
          statusChart.update('none');
        } else if (hasStatus) {
          statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
              labels: sb.labels,
              datasets: [{
                data: sb.counts,
                backgroundColor: [
                  '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
                  '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
                ]
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: { usePointStyle: true, padding: 10, font: { size: 11 } }
                }
              }
            }
          });
        } else {
          var sHolder = statusCtx.parentElement;
          if (sHolder && !sHolder.querySelector('.zmc-status-empty-hint')) {
            var sHint = document.createElement('div');
            sHint.className = 'zmc-status-empty-hint d-flex flex-column align-items-center justify-content-center h-100 text-muted small text-center';
            sHint.innerHTML = '<i class="ri-pie-chart-line" style="font-size: 22px; opacity: .5;"></i><div class="mt-2">No applications to break down yet.</div>';
            statusCtx.style.display = 'none';
            sHolder.appendChild(sHint);
          }
        }
      }
    } catch (err) {
      if (window.console && console.error) {
        console.error('[zmc] analytics chart error:', err);
      }
      showChartError('trendChart', 'Could not draw the daily trend chart. Please refresh the page.');
      showChartError('statusChart', 'Could not draw the status chart. Please refresh the page.');
    }
  }

  // Poll for Chart.js to become available (CDN can be slow). Bail after ~6s
  // and show a clear error so the user knows what's wrong.
  function whenChartReady(cb) {
    if (typeof window.Chart !== 'undefined') { cb(); return; }
    var attempts = 0;
    var maxAttempts = 60; // 60 * 100ms = 6s
    var iv = setInterval(function() {
      attempts++;
      if (typeof window.Chart !== 'undefined') {
        clearInterval(iv);
        cb();
      } else if (attempts >= maxAttempts) {
        clearInterval(iv);
        if (window.console && console.error) {
          console.error('[zmc] Chart.js failed to load after 6s — daily trends cannot render.');
        }
        showChartError('trendChart', 'Chart library could not be loaded. Check your internet connection and refresh.');
        showChartError('statusChart', 'Chart library could not be loaded. Check your internet connection and refresh.');
      }
    }, 100);
  }

  window.addEventListener('zmc:dashboard-data-updated', function(e) {
    pendingChartData = e.detail;
    whenChartReady(function() { createOrUpdateCharts(pendingChartData); });
  });

  @if(isset($analyticsChartData))
  pendingChartData = @json($analyticsChartData);
  function zmcRenderInitialCharts() {
    whenChartReady(function() { createOrUpdateCharts(pendingChartData); });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', zmcRenderInitialCharts);
  } else {
    zmcRenderInitialCharts();
  }
  @endif

})();
</script>
