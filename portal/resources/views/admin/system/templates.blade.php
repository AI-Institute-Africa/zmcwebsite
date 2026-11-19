@extends('layouts.portal')

@section('title', 'Templates & Documents')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Templates & Documents</h4>
      <div class="text-muted mt-1" style="font-size:13px;">System-level templates used by Officers, Registrar and Production.</div>
    </div>
    <a href="{{ route('executive.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Back</a>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
      <div>
        <div class="fw-semibold">Template Catalogue</div>
        <div class="text-muted" style="font-size:12px;">This page is intentionally read-only until you decide how templates should be stored (DB vs files).</div>
      </div>
      <span class="badge bg-light text-dark">Super Admin</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Template</th>
              <th>Type</th>
              <th>Owner</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($templates as $t)
              <tr>
                <td class="fw-semibold">{{ $t['name'] }}</td>
                <td><span class="badge bg-secondary">{{ $t['type'] }}</span></td>
                <td>{{ $t['owner'] }}</td>
                <td><span class="badge {{ $t['status']==='Active' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $t['status'] }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <div class="fw-semibold mb-1">Recommended next step</div>
        <div style="font-size:13px;">Add versioning + activation switches, then allow Super Admin to upload/activate card and certificate templates, letterheads, and SMS/email templates.</div>
      </div>
    </div>
  </div>
</div>
@endsection
