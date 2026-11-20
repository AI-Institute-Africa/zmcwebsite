@extends('layouts.portal')

@section('title', 'Accreditation Requirements')
@section('page_title', 'ACCREDITATION REQUIREMENTS')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Accreditation Requirements</h1>
      <div class="text-muted mt-1" style="font-size:13px;">
        <i class="ri-information-line me-1"></i>
        Documents and information needed for your accreditation application.
      </div>
    </div>
    <a href="{{ route('accreditation.new') }}" class="btn btn-dark btn-sm px-3">
      <i class="ri-file-add-line me-1"></i> Start Application
    </a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="zmc-card h-100">
        <h2 class="fw-bold mb-3" style="color:#1a1a1a;">
          <i class="ri-user-line me-2" style="color:var(--zmc-accent)"></i>Local Media Practitioner
        </h2>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>National ID</strong><br><span class="text-muted small">Clear, legible certified copy of your national identity document</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Media related qualifications or 5 samples of past work</strong><br><span class="text-muted small">Academic certificates, professional qualifications, or portfolio of published work</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>One Passport sized photo (white background)</strong><br><span class="text-muted small">Recent colour photo with a plain white background</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Relevant attachments</strong><br><span class="text-muted small">Reference letter, testimonial and citations</span></div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Application fee</strong><br><span class="text-muted small">As per {{ config('legislation.fee_schedule') }}</span></div>
          </li>
        </ul>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="zmc-card h-100">
        <h3 class="fw-bold mb-3" style="color:#1a1a1a;">
          <i class="ri-global-line me-2" style="color:var(--zmc-accent)"></i>Foreign Media Practitioner
        </h3>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Valid passport</strong><br><span class="text-muted small">Clear scan of your passport bio-data page</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>One passport size photo</strong><br><span class="text-muted small">Recent colour photo with a plain white background</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Clearance Letter</strong><br><span class="text-muted small">From your country's media regulatory authority or embassy</span></div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Application fee</strong><br><span class="text-muted small">As per {{ config('legislation.fee_schedule') }}</span></div>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="zmc-card h-100">
        <h4 class="fw-bold mb-3" style="color:#1a1a1a;">
          <i class="ri-refresh-line me-2" style="color:var(--zmc-accent)"></i>Renewal (AP5)
        </h4>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Current Accreditation Card</strong><br><span class="text-muted small">Scan of your existing ZMC accreditation card</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Previous Reference Number</strong><br><span class="text-muted small">Your existing accreditation reference</span></div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Proof of Payment</strong><br><span class="text-muted small">Payment receipt or proof of transfer</span></div>
          </li>
        </ul>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="zmc-card h-100">
        <h5 class="fw-bold mb-3" style="color:#1a1a1a;">
          <i class="ri-exchange-line me-2" style="color:var(--zmc-accent)"></i>Replacement (AP5)
        </h5>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Affidavit</strong><br><span class="text-muted small">Sworn statement explaining the loss/damage</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Police Report</strong> <span class="badge bg-danger-subtle text-danger">If stolen</span><br><span class="text-muted small">Official police report if the card was stolen</span></div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Proof of Payment</strong><br><span class="text-muted small">Payment receipt for replacement fee</span></div>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="zmc-card" style="background: linear-gradient(135deg, rgba(46, 125, 50,0.05), rgba(46, 125, 50,0.02));">
    <h6 class="fw-bold mb-3" style="color:#1a1a1a;">
      <i class="ri-money-dollar-circle-line me-2" style="color:var(--zmc-accent)"></i>Payment Information
    </h6>
    <p class="small text-muted mb-2">Fees are as per <strong>{{ config('legislation.fee_schedule') }}</strong>.</p>
    <p class="small mb-0"><i class="ri-error-warning-line me-1 text-warning"></i> Fees are also payable in local currency at the prevailing official bank rate on the day of payment.</p>
  </div>
</div>
@endsection
