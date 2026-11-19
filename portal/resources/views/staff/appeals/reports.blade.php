@extends('layouts.portal')
@section('title', 'Appeal Reports')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Inter', sans-serif;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold m-0" style="color:#0f172a;">Reports & Analytics</h3>
      <div class="text-muted small">Analyze appeals data and generate reports</div>
    </div>
    <div>
      <button class="btn btn-outline-dark me-2" onclick="window.print()"><i class="ri-printer-line me-1"></i> Print PDF</button>
      <button class="btn btn-success"><i class="ri-file-excel-line me-1"></i> Export Excel</button>
    </div>
  </div>

  <div class="zmc-card mb-4 d-print-none">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label zmc-lbl">Filter by Month</label>
        <input type="month" name="month" class="form-control zmc-input" value="{{ request('month') }}">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-dark w-100">Generate</button>
      </div>
      <div class="col-md-2">
        <a href="{{ route('staff.appeals.reports') }}" class="btn btn-light w-100">Reset</a>
      </div>
    </form>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="zmc-card bg-primary text-white">
        <div class="small text-uppercase fw-bold opacity-75">Total Appeals</div>
        <h2 class="m-0 fw-bold">{{ number_format($reportsData['total']) }}</h2>
      </div>
    </div>
    <div class="col-md-4">
      <div class="zmc-card bg-success text-white">
        <div class="small text-uppercase fw-bold opacity-75">Paid Appeals</div>
        <h2 class="m-0 fw-bold">{{ number_format($reportsData['paid']) }}</h2>
      </div>
    </div>
    <div class="col-md-4">
      <div class="zmc-card bg-dark text-white">
        <div class="small text-uppercase fw-bold opacity-75">Closed Appeals</div>
        <h2 class="m-0 fw-bold">{{ number_format($reportsData['closed']) }}</h2>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="zmc-card h-100">
        <h6 class="fw-bold border-bottom pb-2 mb-3">Appeals by Category</h6>
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Category</th>
              <th class="text-end">Count</th>
            </tr>
          </thead>
          <tbody>
            @foreach($reportsData['by_category'] as $cat)
              <tr>
                <td>{{ $cat->category }}</td>
                <td class="text-end fw-bold">{{ number_format($cat->count) }}</td>
              </tr>
            @endforeach
            @if($reportsData['by_category']->isEmpty())
              <tr><td colspan="2" class="text-muted text-center py-3">No data available.</td></tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
    <div class="col-md-6">
      <div class="zmc-card h-100 d-flex align-items-center justify-content-center bg-light">
        <div class="text-center text-muted">
          <i class="ri-bar-chart-2-line fs-1 mb-2"></i>
          <p>More detailed reports (Revenue, Resolution Time) will appear here as data accumulates.</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
