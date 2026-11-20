@extends('layouts.portal')

@section('title', 'New AP6 Annual Levy Declaration')

@section('content')
<style>
  #ap6-levy-page .section-card {
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 14px;
    padding: 20px;
  }
  #ap6-levy-page .section-title {
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    font-size: 14px;
  }
  #ap6-levy-page .form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 14px;
  }
  #ap6-levy-page .form-row .form-field {
    min-width: 0;
  }
  #ap6-levy-page .form-label {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 6px;
    color: #334155;
  }
  #ap6-levy-page .form-label.required::after {
    content: ' *';
    color: #dc2626;
  }
  @media (max-width: 600px) {
    #ap6-levy-page .form-row {
      grid-template-columns: 1fr;
    }
  }
</style>
<div id="ap6-levy-page" style="font-family:'Roboto', sans-serif; color:#334155; max-width:900px; margin:0 auto; padding:0 16px;">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark m-0" style="font-size:18px;">
      AP6 — Annual Levy Declaration
    </h4>
    <a href="{{ route('mediahouse.levy.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="ri-arrow-left-line me-1"></i> Back to Levy History
    </a>
  </div>

  <div class="form-container">
    <div class="form-header" style="padding:24px 28px;">
      <h1 style="font-size:17px;">AP6 — Declaration of Gross Annual Turnover &amp; Levy Calculation</h1>
      <p class="m-0" style="font-size:13px; line-height:1.6;">
        Every registered media house shall pay an annual levy equal to <strong>0.5%</strong> of its gross annual turnover. Complete this form to declare your turnover and calculate the levy payable.
      </p>
    </div>

    <form id="ap6Form" onsubmit="return false;" enctype="multipart/form-data" style="padding:20px 28px 28px;">

      <div class="section-card mt-3" style="padding:20px;">
        <div class="section-title"><i class="ri-building-2-line me-2"></i>Entity Details</div>
        <div class="form-row">
          <div class="form-field">
            <label class="form-label required">Name of Media Entity</label>
            <input type="text" class="form-control" name="entity_name" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label class="form-label">Postal Address</label>
            <textarea class="form-control" name="postal_address" rows="2"></textarea>
          </div>
          <div class="form-field">
            <label class="form-label">Business Address</label>
            <textarea class="form-control" name="business_address" rows="2"></textarea>
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label class="form-label">Telephone</label>
            <input type="text" class="form-control" name="telephone">
          </div>
          <div class="form-field">
            <label class="form-label">Mass Media Category</label>
            <select class="form-control" name="media_category">
              <option value="">Select</option>
              <option>Community Media</option>
              <option>Advertising Agency as Media Service</option>
              <option>National Media Service Publishing Newspaper</option>
              <option>Internet Based Media Service</option>
              <option>Production House as Media Service</option>
              <option>Media Service Fitting Multiple Categories</option>
              <option>Media Service in Film and Video Production</option>
            </select>
          </div>
        </div>
      </div>

      <div class="section-card mt-3" style="padding:20px;">
        <div class="section-title"><i class="ri-building-4-line me-2"></i>Subsidiary / Branch (if applicable)</div>
        <div class="form-row">
          <div class="form-field">
            <label class="form-label">Name of Subsidiary</label>
            <input type="text" class="form-control" name="subsidiary_name">
          </div>
          <div class="form-field">
            <label class="form-label">Address of Subsidiary</label>
            <textarea class="form-control" name="subsidiary_address" rows="2"></textarea>
          </div>
        </div>
      </div>

      <div class="section-card mt-3" style="padding:20px;">
        <div class="section-title"><i class="ri-money-dollar-circle-line me-2"></i>Financial Declaration</div>
        <div class="form-row">
          <div class="form-field">
            <label class="form-label required">Financial Year</label>
            <select class="form-control" name="financial_year" required>
              <option value="">Select</option>
              @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
              @endfor
            </select>
          </div>
          <div class="form-field">
            <label class="form-label required">Currency</label>
            <select class="form-control" name="currency" id="ap6Currency" required>
              <option value="USD">USD (US Dollar)</option>
              <option value="ZiG">ZiG (Zimbabwe Gold)</option>
              <option value="ZWL">ZWL (Zimbabwe Dollar)</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label class="form-label required">Gross Annual Turnover</label>
            <div class="input-group">
              <span class="input-group-text" id="ap6CurrencySymbol">$</span>
              <input type="number" class="form-control" name="gross_annual_turnover" id="ap6Turnover" step="0.01" min="0" required oninput="ap6CalcLevy()">
            </div>
          </div>
        </div>

        <div class="mt-3 p-3 border rounded-3" style="background:#f0fdf4;">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted" style="font-size:12px;">Levy Rate</div>
              <div class="fw-bold" style="font-size:16px;">0.5% of Gross Annual Turnover</div>
            </div>
            <div class="text-end">
              <div class="text-muted" style="font-size:12px;">Levy Amount Payable</div>
              <div class="fw-bold" style="font-size:28px; color:#166534;" id="ap6LevyDisplay">$0.00</div>
            </div>
          </div>
        </div>
      </div>

      <div class="section-card mt-3" style="padding:20px;">
        <div class="section-title"><i class="ri-attachment-2 me-2"></i>Supporting Documents</div>
        <div class="form-row">
          <div class="form-field">
            <label class="form-label required">Audited Financial Statements</label>
            <input type="file" class="form-control" name="documents[audited_financials]" accept=".pdf,.jpg,.jpeg,.png" required>
          </div>
          <div class="form-field">
            <label class="form-label">Proof of Payment</label>
            <input type="file" class="form-control" name="documents[proof_of_payment]" accept=".pdf,.jpg,.jpeg,.png">
            <small class="text-muted mt-1 d-block" style="font-size:11px;">Upload your bank receipt or proof of deposit</small>
          </div>
        </div>
      </div>

      <div class="section-card mt-3" style="padding:20px;">
        <div class="section-title"><i class="ri-bank-card-line me-2"></i>Payment Options</div>
        <div class="d-flex gap-3 flex-wrap">
          <div class="flex-fill p-3 border rounded-3 text-center" style="background:#f8fafc; min-width:200px;">
            <i class="ri-upload-2-line d-block mb-2" style="font-size:28px; color:#334155;"></i>
            <div class="fw-bold" style="font-size:13px;">Upload Proof of Payment</div>
            <div class="text-muted" style="font-size:11px;">Bank deposit, EcoCash, or other receipt</div>
          </div>
          <div class="flex-fill p-3 border rounded-3 text-center" style="background:#fffbeb; min-width:200px; border-color:#f59e0b !important;">
            <img src="https://www.paynow.co.zw/Content/Images/Logos/PayNow-Logo-2020.png" alt="Paynow" style="height:32px; margin-bottom:8px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <i class="ri-secure-payment-line d-none d-block mb-2" style="font-size:28px; color:#f59e0b;"></i>
            <div class="fw-bold" style="font-size:13px;">Pay Online via Paynow</div>
            <div class="text-muted" style="font-size:11px;">EcoCash, OneMoney, Visa/Mastercard, Bank Transfer</div>
            <button type="button" class="btn btn-warning btn-sm mt-2 px-3" id="ap6PaynowBtn" onclick="ap6InitPaynow()" disabled>
              <i class="ri-secure-payment-line me-1"></i> Pay Now
            </button>
            <div class="text-muted mt-1" style="font-size:10px;" id="ap6PaynowHint">Enter turnover above to enable</div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('mediahouse.levy.index') }}" class="btn btn-outline-secondary">
          <i class="ri-arrow-left-line me-1"></i> Cancel
        </a>
        <button type="button" class="btn btn-warning btn-lg px-4" id="ap6ReviewBtn" onclick="ap6ShowReview()">
          <i class="ri-eye-line me-1"></i> Review Declaration
        </button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="ap6ReviewModal" tabindex="-1" aria-labelledby="ap6ReviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px; overflow:hidden;">
      <div class="modal-header" style="background:#1e293b; color:#fff; padding:20px 28px;">
        <div>
          <h2 class="modal-title fw-bold m-0" id="ap6ReviewModalLabel">
            <i class="ri-file-search-line me-2"></i>Review Your Levy Declaration
          </h2>
          <div class="mt-1" style="font-size:12px; opacity:0.8;">Please review all details below before submitting</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding:24px 28px;">

        <div class="mb-4">
          <div class="fw-bold mb-2 d-flex align-items-center" style="font-size:14px; color:#0f172a;">
            <i class="ri-building-2-line me-2" style="color:#f59e0b;"></i> Entity Details
          </div>
          <table class="table table-sm table-borderless mb-0" style="font-size:13px;">
            <tr><td class="text-muted" style="width:40%;">Name of Media Entity</td><td class="fw-semibold" id="rv_entity_name">—</td></tr>
            <tr><td class="text-muted">Postal Address</td><td id="rv_postal_address">—</td></tr>
            <tr><td class="text-muted">Business Address</td><td id="rv_business_address">—</td></tr>
            <tr><td class="text-muted">Telephone</td><td id="rv_telephone">—</td></tr>
            <tr><td class="text-muted">Mass Media Category</td><td id="rv_media_category">—</td></tr>
          </table>
        </div>

        <hr class="my-3">

        <div class="mb-4">
          <div class="fw-bold mb-2 d-flex align-items-center" style="font-size:14px; color:#0f172a;">
            <i class="ri-building-4-line me-2" style="color:#f59e0b;"></i> Subsidiary / Branch
          </div>
          <table class="table table-sm table-borderless mb-0" style="font-size:13px;">
            <tr><td class="text-muted" style="width:40%;">Name of Subsidiary</td><td id="rv_subsidiary_name">—</td></tr>
            <tr><td class="text-muted">Address of Subsidiary</td><td id="rv_subsidiary_address">—</td></tr>
          </table>
        </div>

        <hr class="my-3">

        <div class="mb-4">
          <div class="fw-bold mb-2 d-flex align-items-center" style="font-size:14px; color:#0f172a;">
            <i class="ri-money-dollar-circle-line me-2" style="color:#f59e0b;"></i> Financial Declaration
          </div>
          <table class="table table-sm table-borderless mb-0" style="font-size:13px;">
            <tr><td class="text-muted" style="width:40%;">Financial Year</td><td class="fw-semibold" id="rv_financial_year">—</td></tr>
            <tr><td class="text-muted">Currency</td><td id="rv_currency">—</td></tr>
            <tr><td class="text-muted">Gross Annual Turnover</td><td class="fw-semibold" id="rv_turnover">—</td></tr>
          </table>

          <div class="mt-3 p-3 rounded-3" style="background:#f0fdf4; border:1px solid #bbf7d0;">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted" style="font-size:11px;">Levy Rate</div>
                <div class="fw-bold" style="font-size:14px;">0.5% of Gross Annual Turnover</div>
              </div>
              <div class="text-end">
                <div class="text-muted" style="font-size:11px;">Levy Amount Payable</div>
                <div class="fw-bold" style="font-size:24px; color:#166534;" id="rv_levy_amount">$0.00</div>
              </div>
            </div>
          </div>
        </div>

        <hr class="my-3">

        <div class="mb-3">
          <div class="fw-bold mb-2 d-flex align-items-center" style="font-size:14px; color:#0f172a;">
            <i class="ri-attachment-2 me-2" style="color:#f59e0b;"></i> Supporting Documents
          </div>
          <table class="table table-sm table-borderless mb-0" style="font-size:13px;">
            <tr><td class="text-muted" style="width:40%;">Audited Financial Statements</td><td id="rv_audited_financials">—</td></tr>
            <tr><td class="text-muted">Proof of Payment</td><td id="rv_proof_of_payment">—</td></tr>
          </table>
        </div>

        <div class="p-3 rounded-3 mt-3" style="background:#fffbeb; border:1px solid #fde68a; font-size:12px;">
          <i class="ri-error-warning-line me-1" style="color:#d97706;"></i>
          <strong>Important:</strong> Once submitted, this declaration cannot be edited. Please ensure all details are correct before proceeding.
        </div>

      </div>
      <div class="modal-footer" style="padding:16px 28px; border-top:1px solid #e2e8f0;">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="ri-arrow-left-line me-1"></i> Go Back & Edit
        </button>
        <button type="button" class="btn btn-success btn-lg px-4" id="ap6SubmitBtn" onclick="ap6Submit()">
          <i class="ri-check-double-line me-1"></i> Confirm & Submit
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
  const csrfToken = '{{ csrf_token() }}';
  const annualLevyRate = @json((float) config('fees.media_house.levy_rate'));

  function ap6CalcLevy() {
    const turnover = parseFloat(document.getElementById('ap6Turnover').value) || 0;
    const levy = turnover * annualLevyRate;
    const curr = document.getElementById('ap6Currency').value;
    const symbol = curr === 'USD' ? '$' : curr;
    document.getElementById('ap6LevyDisplay').textContent = symbol + levy.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('ap6CurrencySymbol').textContent = symbol;

    const paynowBtn = document.getElementById('ap6PaynowBtn');
    const paynowHint = document.getElementById('ap6PaynowHint');
    if (levy > 0) {
      paynowBtn.disabled = false;
      paynowHint.textContent = 'Pay ' + symbol + levy.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' online';
    } else {
      paynowBtn.disabled = true;
      paynowHint.textContent = 'Enter turnover above to enable';
    }
  }

  document.getElementById('ap6Currency').addEventListener('change', ap6CalcLevy);

  function ap6InitPaynow() {
    const turnover = parseFloat(document.getElementById('ap6Turnover').value) || 0;
    const levy = turnover * annualLevyRate;
    const curr = document.getElementById('ap6Currency').value;
    const entityName = document.querySelector('[name="entity_name"]').value.trim();

    if (levy <= 0) { alert('Please enter the gross annual turnover first.'); return; }
    if (!entityName) { alert('Please enter the entity name first.'); return; }

    const btn = document.getElementById('ap6PaynowBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line me-1"></i> Initiating...';

    fetch('{{ route("mediahouse.levy.paynow") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
      body: JSON.stringify({
        amount: levy,
        currency: curr,
        reference: 'AP6-LEVY-' + Date.now(),
        description: 'Annual Levy Payment - ' + entityName,
        entity_name: entityName
      })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success && data.redirect_url) {
        window.open(data.redirect_url, '_blank');
        btn.innerHTML = '<i class="ri-check-line me-1"></i> Payment Initiated';
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-success');
        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = '<i class="ri-secure-payment-line me-1"></i> Pay Now';
          btn.classList.remove('btn-success');
          btn.classList.add('btn-warning');
        }, 5000);
      } else {
        alert(data.message || 'Could not initiate payment. Please try again or upload proof of payment.');
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-secure-payment-line me-1"></i> Pay Now';
      }
    })
    .catch(err => {
      alert('Payment service unavailable. Please upload proof of payment instead.');
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-secure-payment-line me-1"></i> Pay Now';
    });
  }

  function ap6ShowReview() {
    const form = document.getElementById('ap6Form');
    const entityName = form.querySelector('[name="entity_name"]').value.trim();
    const financialYear = form.querySelector('[name="financial_year"]').value;
    const turnover = form.querySelector('[name="gross_annual_turnover"]').value;

    if (!entityName) { alert('Please enter the entity name.'); return; }
    if (!financialYear) { alert('Please select the financial year.'); return; }
    if (!turnover || parseFloat(turnover) <= 0) { alert('Please enter the gross annual turnover.'); return; }

    const audited = form.querySelector('[name="documents[audited_financials]"]');
    if (!audited || !audited.files.length) { alert('Please upload your Audited Financial Statements.'); return; }

    const curr = form.querySelector('[name="currency"]').value;
    const symbol = curr === 'USD' ? '$' : curr + ' ';
    const levy = parseFloat(turnover) * annualLevyRate;

    document.getElementById('rv_entity_name').textContent = entityName;
    document.getElementById('rv_postal_address').textContent = form.querySelector('[name="postal_address"]').value.trim() || '—';
    document.getElementById('rv_business_address').textContent = form.querySelector('[name="business_address"]').value.trim() || '—';
    document.getElementById('rv_telephone').textContent = form.querySelector('[name="telephone"]').value.trim() || '—';
    document.getElementById('rv_media_category').textContent = form.querySelector('[name="media_category"]').value || '—';

    document.getElementById('rv_subsidiary_name').textContent = form.querySelector('[name="subsidiary_name"]').value.trim() || '—';
    document.getElementById('rv_subsidiary_address').textContent = form.querySelector('[name="subsidiary_address"]').value.trim() || '—';

    document.getElementById('rv_financial_year').textContent = financialYear;
    document.getElementById('rv_currency').textContent = curr;
    document.getElementById('rv_turnover').textContent = symbol + parseFloat(turnover).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('rv_levy_amount').textContent = symbol + levy.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    document.getElementById('rv_audited_financials').innerHTML = audited.files[0]
      ? '<i class="ri-check-line text-success me-1"></i>' + audited.files[0].name
      : '<span class="text-danger">Not uploaded</span>';

    const pop = form.querySelector('[name="documents[proof_of_payment]"]');
    document.getElementById('rv_proof_of_payment').innerHTML = (pop && pop.files.length)
      ? '<i class="ri-check-line text-success me-1"></i>' + pop.files[0].name
      : '<span class="text-muted">Not uploaded</span>';

    const modal = new bootstrap.Modal(document.getElementById('ap6ReviewModal'));
    modal.show();
  }

  async function ap6Submit() {
    const form = document.getElementById('ap6Form');
    const entityName = form.querySelector('[name="entity_name"]').value.trim();
    const financialYear = form.querySelector('[name="financial_year"]').value;
    const turnover = form.querySelector('[name="gross_annual_turnover"]').value;

    if (!entityName) { alert('Please enter the entity name.'); return; }
    if (!financialYear) { alert('Please select the financial year.'); return; }
    if (!turnover || parseFloat(turnover) <= 0) { alert('Please enter the gross annual turnover.'); return; }

    const btn = document.getElementById('ap6SubmitBtn');
    try {
      btn.disabled = true;
      btn.innerHTML = '<i class="ri-loader-4-line"></i> Submitting...';

      const fd = new FormData(form);

      const res = await fetch('{{ route("mediahouse.levy.store") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: fd
      });

      const json = await res.json();
      if (json.success) {
        const reviewModal = bootstrap.Modal.getInstance(document.getElementById('ap6ReviewModal'));
        if (reviewModal) reviewModal.hide();
        alert(json.message + '\nReference: ' + json.reference);
        window.location.href = '{{ route("mediahouse.levy.index") }}';
      } else {
        alert(json.message || 'Submission failed.');
      }
    } catch(e) {
      alert('Error: ' + e.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i class="ri-check-double-line me-1"></i> Confirm & Submit';
    }
  }
</script>
@endpush
@endsection
