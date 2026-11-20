@extends('layouts.portal')
@section('title', 'System Help / SOPs')

@section('content')
  <div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div>
        <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">System Help / SOPs</h1>
        <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
          <i class="ri-information-line me-1"></i>Standard operating procedures and quick links for Accreditation
          Officers.
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
          <i class="ri-dashboard-3-line me-1"></i>Dashboard
        </a>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h2 class="fw-bold mb-3"><i class="ri-file-list-3-line me-1"></i>Processing an application</h2>
            <ol class="small mb-0 ps-3">
              <li class="mb-2">Open <a href="{{ route('staff.officer.applications.index') }}">All Applications</a> and filter by year and progress.</li>
              <li class="mb-2">Open the application and verify the applicant details and uploaded documents.</li>
              <li class="mb-2">Use <strong>Request Correction</strong> if anything is missing or unclear. This returns the file to the applicant and notifies them.</li>
              <li class="mb-2">Use <strong>Approve &amp; Forward</strong> with the correct category code to send the file to the Registrar.</li>
              <li class="mb-0">Files returned by the Registrar appear in the application timeline and are cleared automatically once you approve or forward the file.</li>
            </ol>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h3 class="fw-bold mb-3"><i class="ri-folder-shield-2-line me-1"></i>Document verification</h3>
            <ol class="small mb-0 ps-3">
              <li class="mb-2">Work from <a href="{{ route('staff.officer.documents.pending') }}">Pending Verification</a>.</li>
              <li class="mb-2">Check that every required document is legible, current and matches the applicant's details.</li>
              <li class="mb-2">Reject/return documents with a clear reason so the applicant can re-upload.</li>
              <li class="mb-0">Verified documents move to <a href="{{ route('staff.officer.documents.verified') }}">Verified</a> for audit purposes.</li>
            </ol>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h4 class="fw-bold mb-3"><i class="ri-calendar-todo-line me-1"></i>Renewals and expiries</h4>
            <ol class="small mb-0 ps-3">
              <li class="mb-2">Review <a href="{{ route('staff.officer.renewals.expiring') }}">Expiring Soon</a> and <a href="{{ route('staff.officer.renewals.expired') }}">Expired</a> registers weekly.</li>
              <li class="mb-2">Send reminders from the expiry pages (all holders or selected records).</li>
              <li class="mb-2">Renewal submissions arrive under <a href="{{ route('staff.officer.renewals.requests') }}">Renewal Requests</a>.</li>
              <li class="mb-0">Once payment is verified, produce the renewed document from the <a href="{{ route('staff.officer.renewals.production') }}">Production Queue</a>.</li>
            </ol>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <h5 class="fw-bold mb-3"><i class="ri-shield-keyhole-line me-1"></i>Compliance and verification</h5>
            <ol class="small mb-0 ps-3">
              <li class="mb-2">Monitor the register from <a href="{{ route('staff.officer.compliance.monitoring') }}">Compliance Monitoring</a>.</li>
              <li class="mb-2">Log and follow up violations, investigation cases and unaccredited practice reports.</li>
              <li class="mb-2">Use <a href="{{ route('staff.officer.advanced.qr') }}">QR Verification</a> to confirm a certificate or registration on the spot.</li>
              <li class="mb-0">Check <a href="{{ route('staff.officer.advanced.duplicates') }}">Duplicate Detection</a> before issuing a new record.</li>
            </ol>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Quick links</h6>
            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-white border btn-sm" href="{{ route('staff.officer.applications.index') }}"><i class="ri-file-list-3-line me-1"></i>All Applications</a>
              <a class="btn btn-white border btn-sm" href="{{ route('staff.officer.applications.bulk') }}"><i class="ri-play-list-2-line me-1"></i>Bulk Review</a>
              <a class="btn btn-white border btn-sm" href="{{ route('staff.officer.records.history') }}"><i class="ri-history-line me-1"></i>Records History</a>
              <a class="btn btn-white border btn-sm" href="{{ route('staff.officer.reports.stats') }}"><i class="ri-bar-chart-box-line me-1"></i>Statistics</a>
              <a class="btn btn-white border btn-sm" href="{{ route('staff.officer.reports.monthly') }}"><i class="ri-calendar-2-line me-1"></i>Monthly Report</a>
              <a class="btn btn-white border btn-sm" href="{{ route('staff.officer.tools.tasks') }}"><i class="ri-task-line me-1"></i>Task Queue</a>
              <a class="btn btn-white border btn-sm" href="{{ route('staff.officer.comm.messaging') }}"><i class="ri-mail-send-line me-1"></i>Bulk Messaging</a>
              <a class="btn btn-white border btn-sm" href="{{ route('settings') }}"><i class="ri-user-settings-line me-1"></i>My Profile</a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="alert alert-info mb-0">
          <i class="ri-customer-service-2-line me-1"></i>
          For access issues, workflow errors or data corrections that cannot be resolved in the portal, contact the
          Registrar's office or IT Administration and include the application reference number.
        </div>
      </div>
    </div>
  </div>
@endsection
