@extends('layouts.portal')

@section('title', 'Regions & Offices')

@section('content')
@include('staff.partials.dashboard_theme')
<div class="staff-dashboard-theme">
<div class="regions-page zmc-dashboard-wrapper">
  @php
    $officeCount = collect($offices ?? [])->count();
    $activeOfficeCount = collect($offices ?? [])->where('status', 'Active')->count();
    $inactiveOfficeCount = $officeCount - $activeOfficeCount;
  @endphp
  <style>
    .regions-page{font-family:var(--font-primary);color:var(--zmc-text-dark)}
    .regions-heading{position:relative;overflow:hidden;padding:24px 28px;border-radius:24px;background:linear-gradient(120deg,#0e1b2b 0%,#173b3d 55%,#1f6f57 100%);color:#fff;box-shadow:0 18px 40px rgba(15,35,48,.18);margin-bottom:24px}
    .regions-heading:after{content:"";position:absolute;right:-55px;top:-95px;width:220px;height:220px;border-radius:50%;background:rgba(242,201,76,.18)}
    .regions-heading>*{position:relative;z-index:1}.regions-heading h4{font-size:clamp(1.65rem,2.7vw,2.25rem)!important;letter-spacing:-.03em;color:#fff!important}.regions-heading .text-muted{color:rgba(255,255,255,.76)!important}.regions-heading .btn{border-radius:10px;font-weight:700;background:rgba(255,255,255,.95);border-color:transparent;color:#176a4f}
    .region-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:24px}.region-kpi{display:flex;align-items:center;gap:13px;min-height:108px;padding:17px 18px;border:1px solid #e8edf1;border-radius:18px;background:linear-gradient(145deg,#fff,#fbfdfc);box-shadow:0 10px 24px rgba(15,35,48,.07)}.region-kpi-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#fff8d9;color:#927300;font-size:1.2rem;flex:0 0 auto}.region-kpi-icon.green{background:#e8f5ed;color:#247a54}.region-kpi-icon.red{background:#fff0ef;color:#bd4b42}.region-kpi-label{text-transform:uppercase;letter-spacing:.06em;color:#64756f;font-size:.68rem;font-weight:800}.region-kpi-value{font-size:1.85rem;line-height:1.05;font-weight:850;color:#172b2b;margin-top:4px}.region-kpi-note{font-size:.73rem;color:#83918c;margin-top:5px}
    .regions-shell{border:1px solid #e8edf1!important;border-radius:22px!important;box-shadow:0 10px 24px rgba(15,35,48,.07)!important;overflow:hidden}.regions-shell .card-header{padding:20px 22px;background:#f4f8f6!important;border-bottom:1px solid #e8edf1!important}.regions-shell .card-header .fw-semibold{font-size:1.05rem;color:#173b3d}.regions-table{margin:0}.regions-table thead th{background:#f8faf9;color:#64756f;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e8edf1;padding:14px 18px}.regions-table tbody td{padding:15px 18px;border-color:#edf1ef}.regions-table tbody tr:hover{background:#fbfdfc}.regions-table .code-badge{background:#eef4f1;color:#176a4f;border:1px solid #dce9e2;font-weight:800;letter-spacing:.05em}.regions-table .status-active{background:#e8f5ed;color:#247a54;border:1px solid #cfe8d8}.regions-table .status-inactive{background:#fff3d7;color:#936a08;border:1px solid #f1dfaa}.regions-note{margin:18px 22px 22px;border:1px solid #dce9e2;background:#f8fbf9;color:#64756f;border-radius:14px;padding:13px 15px;font-size:.82rem}.regions-note i{color:#247a54}@media(max-width:700px){.region-kpis{grid-template-columns:1fr}.regions-heading{padding:20px}.regions-table{font-size:.85rem}}
  </style>
  <div class="regions-heading d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h4 class="fw-bold m-0">Regions & Offices</h4>
      <div class="text-muted mt-1"><i class="ri-map-pin-line me-1"></i>Manage the regional offices used for assignments, records, and reporting.</div>
    </div>
    <a href="{{ route('executive.dashboard') }}" class="btn btn-sm"><i class="ri-arrow-left-line me-1"></i>Back to dashboard</a>
  </div>

  <div class="region-kpis">
    <div class="region-kpi"><div class="region-kpi-icon"><i class="ri-building-4-line"></i></div><div><div class="region-kpi-label">Total offices</div><div class="region-kpi-value">{{ number_format($officeCount) }}</div><div class="region-kpi-note">Configured regional locations</div></div></div>
    <div class="region-kpi"><div class="region-kpi-icon green"><i class="ri-checkbox-circle-line"></i></div><div><div class="region-kpi-label">Active offices</div><div class="region-kpi-value">{{ number_format($activeOfficeCount) }}</div><div class="region-kpi-note">Available for assignments</div></div></div>
    <div class="region-kpi"><div class="region-kpi-icon red"><i class="ri-pause-circle-line"></i></div><div><div class="region-kpi-label">Inactive offices</div><div class="region-kpi-value">{{ number_format($inactiveOfficeCount) }}</div><div class="region-kpi-note">Review or reactivation required</div></div></div>
  </div>

  <div class="card border-0 regions-shell">
    <div class="card-header bg-white border-0">
      <div class="fw-semibold">Regional Offices</div>
      <div class="text-muted" style="font-size:12px;">Current office coverage and availability across the Commission.</div>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle regions-table">
          <thead>
            <tr>
              <th>Office</th>
              <th>Code</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($offices as $o)
              <tr>
                <td class="fw-semibold">{{ $o['name'] }}</td>
                <td><span class="badge rounded-pill code-badge">{{ $o['code'] }}</span></td>
                <td><span class="badge rounded-pill {{ $o['status']==='Active' ? 'status-active' : 'status-inactive' }}"><i class="{{ $o['status']==='Active' ? 'ri-checkbox-circle-line' : 'ri-pause-circle-line' }} me-1"></i>{{ $o['status'] }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="regions-note">
        <i class="ri-information-line me-1"></i> Active offices are available for assignments and regional reporting. Status changes should be reviewed before reallocating work.
      </div>
    </div>
  </div>
</div>
</div>
@endsection
