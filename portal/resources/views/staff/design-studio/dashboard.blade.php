@extends('layouts.portal')
@section('title', 'Design Studio')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:var(--font-primary);color:var(--zmc-text-dark);">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:var(--font-size-2xl);color:#1e293b;">
        <i class="ri-palette-line me-2" style="color:var(--zmc-accent)"></i>Design Studio
      </h1>
      <div class="text-muted mt-1" style="font-size:var(--font-size-base);">
        Design and manage the card and certificate templates used in production.
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @if($isDesigner)
        <a href="{{ route('staff.design-studio.create') }}" class="btn btn-dark btn-sm fw-bold">
          <i class="ri-add-line me-1"></i> New Template
        </a>
      @endif
      <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-refresh-line me-1"></i> Refresh
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2 mb-3">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  {{-- KPI Tiles --}}
  <style>
    .ds-tile { border-radius:12px; padding:20px; background:#fff; border:1px solid #e2e8f0; transition:box-shadow .2s; }
    .ds-tile:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
    .ds-tile-icon { width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0; }
    .ds-tile-label { font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em; }
    .ds-tile-value { font-size:28px;font-weight:900;line-height:1;margin:4px 0 0; }
  </style>

  @if($isDesigner)
  <div class="mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing:.06em;">My Templates</div>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.index', ['status' => 'draft']) }}" class="text-decoration-none">
        <div class="ds-tile">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">My Drafts</div>
              <div class="ds-tile-value">{{ $myDrafts }}</div>
            </div>
            <div class="ds-tile-icon" style="background:#f1f5f9;color:#64748b;"><i class="ri-draft-line"></i></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.index', ['status' => 'approved']) }}" class="text-decoration-none">
        <div class="ds-tile">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">Active Designs</div>
              <div class="ds-tile-value text-success">{{ $myActive }}</div>
            </div>
            <div class="ds-tile-icon" style="background:#dcfce7;color:#16a34a;"><i class="ri-check-double-line"></i></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.index', ['status' => 'rejected']) }}" class="text-decoration-none">
        <div class="ds-tile">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">Rejected</div>
              <div class="ds-tile-value" style="color:#dc2626;">{{ $myRejected }}</div>
            </div>
            <div class="ds-tile-icon" style="background:#fee2e2;color:#dc2626;"><i class="ri-close-circle-line"></i></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.create') }}" class="text-decoration-none">
        <div class="ds-tile" style="border:2px dashed #e2e8f0;background:#f8fafc;">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">New Template</div>
              <div class="ds-tile-value" style="font-size:20px;color:var(--zmc-accent);">Create</div>
            </div>
            <div class="ds-tile-icon" style="background:rgba(245,158,11,.1);color:var(--zmc-accent);"><i class="ri-add-circle-line"></i></div>
          </div>
        </div>
      </a>
    </div>
  </div>
  @endif

  <div class="mb-2 small fw-bold text-muted text-uppercase" style="letter-spacing:.06em;">System Overview</div>
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.index', ['status' => 'approved', 'type' => 'card']) }}" class="text-decoration-none">
        <div class="ds-tile">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">Active Card Templates</div>
              <div class="ds-tile-value text-success">{{ $cardApproved }}</div>
            </div>
            <div class="ds-tile-icon" style="background:#dcfce7;color:#16a34a;"><i class="ri-id-card-line"></i></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.index', ['status' => 'approved', 'type' => 'certificate']) }}" class="text-decoration-none">
        <div class="ds-tile">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">Active Certificate Templates</div>
              <div class="ds-tile-value text-success">{{ $certApproved }}</div>
            </div>
            <div class="ds-tile-icon" style="background:#dcfce7;color:#16a34a;"><i class="ri-award-line"></i></div>
          </div>
        </div>
      </a>
    </div>
    @if($isSuperAdmin)
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.index', ['status' => 'approved']) }}" class="text-decoration-none">
        <div class="ds-tile">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">All Active Designs</div>
              <div class="ds-tile-value text-success">{{ $totalApproved }}</div>
            </div>
            <div class="ds-tile-icon" style="background:#dcfce7;color:#16a34a;"><i class="ri-check-double-line"></i></div>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="{{ route('staff.design-studio.index', ['status' => 'archived']) }}" class="text-decoration-none">
        <div class="ds-tile">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="ds-tile-label">Archived</div>
              <div class="ds-tile-value text-muted">{{ $totalArchived }}</div>
            </div>
            <div class="ds-tile-icon" style="background:#f1f5f9;color:#64748b;"><i class="ri-archive-line"></i></div>
          </div>
        </div>
      </a>
    </div>
    @endif
  </div>

  {{-- Quick Actions --}}
  <div class="zmc-card mb-4 border-0 bg-light">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="fw-bold small text-muted me-2"><i class="ri-links-line me-1"></i>Quick Access:</span>
      @if($isDesigner)
        <a href="{{ route('staff.design-studio.create') }}" class="btn btn-sm btn-outline-dark">
          <i class="ri-add-line me-1"></i>New Template
        </a>
        <a href="{{ route('staff.design-studio.index') }}" class="btn btn-sm btn-outline-dark">
          <i class="ri-list-check-2 me-1"></i>All My Templates
        </a>
      @endif
      @if($isSuperAdmin)
        <a href="{{ route('staff.design-studio.index', ['status' => 'approved']) }}" class="btn btn-sm btn-outline-success">
          <i class="ri-check-double-line me-1"></i>Active Designs
        </a>
        <a href="{{ route('staff.design-studio.index') }}" class="btn btn-sm btn-outline-primary">
          <i class="ri-eye-line me-1"></i>All Templates
        </a>
      @endif
      <a href="{{ route('staff.design-studio.audit-logs') }}" class="btn btn-sm btn-outline-secondary">
        <i class="ri-file-list-3-line me-1"></i>Audit Logs
      </a>
    </div>
  </div>

  {{-- Workflow Guide --}}
  <div class="zmc-card border-0" style="background:#f8fafc;">
    <h2 class="fw-bold mb-3"><i class="ri-flow-chart me-2" style="color:var(--zmc-accent)"></i>Template Workflow</h2>
    <div class="d-flex flex-wrap gap-2 align-items-center small">
      <span class="badge bg-secondary px-3 py-2">1. Design</span>
      <i class="ri-arrow-right-line text-muted"></i>
      <span class="badge bg-success px-3 py-2">2. Save &amp; Use in Production</span>
      <i class="ri-arrow-right-line text-muted"></i>
      <span class="badge bg-dark px-3 py-2">3. Update anytime</span>
    </div>
    <div class="mt-3 small text-muted">
      <i class="ri-information-line me-1"></i>
      Saving a template makes it the active production design for its card or certificate type. Older designs remain archived for audit and version history.
    </div>
  </div>

</div>
@endsection
