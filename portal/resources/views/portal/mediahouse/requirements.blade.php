@extends('layouts.portal')

@section('title', 'Registration Requirements')
@section('page_title', 'REGISTRATION REQUIREMENTS')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Registration Requirements</h4>
      <div class="text-muted mt-1" style="font-size:13px;">
        <i class="ri-information-line me-1"></i>
        Documents and information needed for media house registration.
      </div>
    </div>
    <a href="{{ route('mediahouse.new') }}" class="btn btn-dark btn-sm px-3">
      <i class="ri-file-add-line me-1"></i> Start Registration
    </a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="zmc-card h-100" style="border-left:4px solid #166534;">
        <h6 class="fw-bold mb-1" style="color:#166534;">
          <i class="ri-home-4-line me-2"></i>Local Media House Requirements
        </h6>
        <div class="text-muted small mb-3">AP1 (Mass Media Service) &amp; AP2 (News Agency)</div>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>1. Projected Cash Flow Statement</strong><br><span class="text-muted small">For a minimum of three (3) years</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>2. Projected Balance Sheet</strong><br><span class="text-muted small">For a minimum of three (3) years</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>3. Editorial Charter</strong><br><span class="text-muted small">Outlines editorial policies and principles</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>4. Code of Ethics</strong><br><span class="text-muted small">Ethical guidelines for media operations</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>5. Code of Conduct for Employees</strong><br><span class="text-muted small">Employee conduct and behaviour standards</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>6. Market Analysis</strong><br><span class="text-muted small">Analysis of the target market and media landscape</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>7. Certificate of Incorporation</strong><br><span class="text-muted small">Company registration certificate from CIPC</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>8. Certified IDs for Directors</strong><br><span class="text-muted small">Certified copies of national IDs for all company directors</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>9. Memorandum of Association</strong><br><span class="text-muted small">Articles and memorandum of the company</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>10. In-house Style Book</strong><br><span class="text-muted small">Internal editorial style guide</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>11. Dummy / Sample Publication</strong><br><span class="text-muted small">Copy or mock-up of the publication</span></div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>12. Mission Statement</strong><br><span class="text-muted small">Organisation's mission and objectives</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mt-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Annual Media House Levy (AP6)</strong><br><span class="text-muted small">Submit the annual turnover declaration and pay the levy at {{ rtrim(rtrim(number_format((float) config('fees.media_house.levy_rate', 0) * 100, 2), '0'), '.') }}% of gross annual turnover.</span></div>
          </li>
        </ul>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="zmc-card h-100" style="border-left:4px solid #1e40af;">
        <h6 class="fw-bold mb-1" style="color:#1e40af;">
          <i class="ri-global-line me-2"></i>Foreign Media House Requirements
        </h6>
        <div class="text-muted small mb-3">AP4 (Foreign Representative Office)</div>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>1. Certificate of Incorporation (Country of Domicile)</strong><br><span class="text-muted small">Company registration certificate from the country of origin</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>2. Certified Copies of Directors' National IDs</strong><br><span class="text-muted small">Certified copies of identification for all directors or passport copies</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>3. Clearance Letter</strong><br><span class="text-muted small">Permission to operate a Representative Office in Zimbabwe</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>4. Editorial Charter</strong><br><span class="text-muted small">Outlines editorial policies and principles</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>5. Code of Ethics</strong><br><span class="text-muted small">Ethical guidelines for media operations</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>6. Code of Conduct for Employees</strong><br><span class="text-muted small">Employee conduct and behaviour standards</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>7. In-house Style Book</strong><br><span class="text-muted small">Internal editorial style guide</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>8. Mission Statement</strong><br><span class="text-muted small">Organisation's mission and objectives</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>9. Letter from Parent Organisation</strong><br><span class="text-muted small">Authorisation letter from the parent media company</span></div>
          </li>
          <li class="d-flex align-items-start gap-2">
            <i class="ri-checkbox-circle-fill text-primary mt-1"></i>
            <div><strong>10. Passport Copies &amp; Work Permits</strong><br><span class="text-muted small">For all foreign staff members operating in Zimbabwe</span></div>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="zmc-card h-100">
        <h6 class="fw-bold mb-3" style="color:#1a1a1a;">
          <i class="ri-refresh-line me-2" style="color:var(--zmc-accent)"></i>Renewal (AP5)
        </h6>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Current Registration Certificate</strong><br><span class="text-muted small">Scan of your existing ZMC registration certificate</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Previous Reference Number</strong><br><span class="text-muted small">Your existing registration reference</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Supporting Documents</strong><br><span class="text-muted small">Any changes in directors, operations, or details</span></div>
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
        <h6 class="fw-bold mb-3" style="color:#1a1a1a;">
          <i class="ri-exchange-line me-2" style="color:var(--zmc-accent)"></i>Replacement (AP5)
        </h6>
        <ul class="list-unstyled mb-0">
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Affidavit</strong><br><span class="text-muted small">Sworn statement explaining the loss or damage</span></div>
          </li>
          <li class="d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-fill text-success mt-1"></i>
            <div><strong>Police Report</strong> <span class="badge bg-danger-subtle text-danger">If stolen</span><br><span class="text-muted small">Official police report if the certificate was stolen</span></div>
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
    <p class="small text-muted mb-2">Application and registration fees are as per <strong>{{ config('legislation.fee_schedule') }}</strong>.</p>
    <p class="small text-muted mb-2">Media house registration involves a <strong>two-stage payment</strong>: an application fee at submission and a registration fee upon approval.</p>
    <p class="small mb-0"><i class="ri-error-warning-line me-1 text-warning"></i> Fees are also payable in local currency at the prevailing official bank rate on the day of payment.</p>
  </div>
</div>
@endsection
