<div class="modal fade zmc-modal-pop" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header zmc-modal-header">
        <div>
          <div class="zmc-modal-title">
            <i class="ri-bank-card-line me-2" style="color:var(--zmc-yellow)"></i>
            Make Payment
          </div>
          <div class="zmc-modal-sub" id="pay_meta">Application: —</div>
        </div>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <input type="hidden" id="modal_payment_stage" value="application_fee">

      <div class="modal-body">
        <div id="pay_loading" class="d-none text-center py-4">
          <div class="spinner-border" style="color:var(--zmc-yellow)"></div>
          <div class="text-muted mt-2" style="font-size:12px;">Processing...</div>
        </div>

        <div id="pay_error" class="alert alert-danger d-none"></div>
        <div id="pay_success" class="alert alert-success d-none"></div>

        <div id="pay_options">
          <div class="alert alert-light border mb-3">
            <div class="fw-bold mb-1"><i class="ri-information-line me-1"></i> Payment details</div>
            <div class="text-muted small">Choose PayNow for an online payment, or upload one proof of payment for Accounts to verify.</div>
          </div>

          <ul class="nav nav-pills mb-3" id="payTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-paynow" data-bs-toggle="pill" data-bs-target="#pane-paynow" type="button" role="tab">
                <i class="ri-flashlight-line me-1"></i> PayNow
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-proof" data-bs-toggle="pill" data-bs-target="#pane-proof" type="button" role="tab">
                <i class="ri-file-upload-line me-1"></i> Upload Proof
              </button>
            </li>
          </ul>

          <div class="tab-content" id="payTabContent">
            {{-- PAYNOW --}}
            <div class="tab-pane fade show active" id="pane-paynow" role="tabpanel">
              <div id="paynow_initial">
                {{-- Currency Selection --}}
                <div class="mb-4">
                  <label class="form-label fw-bold">
                    <i class="ri-money-dollar-circle-line me-1"></i> Select Currency
                  </label>
                  <div class="row g-3">
                    <div class="col-6">
                      <div class="currency-option" data-currency="USD">
                        <input type="radio" name="payment_currency" id="currency_usd" value="USD" checked>
                        <label for="currency_usd">
                          <i class="ri-money-dollar-circle-line"></i>
                          <div class="fw-bold">USD</div>
                          <div class="small text-muted">US Dollar</div>
                          <div class="currency-amount fw-bold text-success mt-1" id="amount_usd">$0.00</div>
                        </label>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="currency-option" data-currency="ZWG">
                        <input type="radio" name="payment_currency" id="currency_zwg" value="ZWG">
                        <label for="currency_zwg">
                          <i class="ri-money-dollar-circle-line"></i>
                          <div class="fw-bold">ZWG</div>
                          <div class="small text-muted">Zimbabwe Gold</div>
                          <div class="currency-amount fw-bold text-success mt-1" id="amount_zwg">ZWG 0.00</div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                {{-- RBZ Exchange Rate Section (shown for ZWG) --}}
                <div id="rbz_rate_section" class="d-none mb-4">
                  <div class="alert alert-info border">
                    <div class="d-flex align-items-start">
                      <i class="ri-information-line me-2 mt-1"></i>
                      <div class="flex-grow-1">
                        <div class="fw-bold mb-2">Current RBZ Exchange Rate</div>
                        <div class="small mb-2">
                          Accounts maintains this rate from the official RBZ listing. The portal applies it to your ZWG payment:
                        </div>
                        <a href="https://www.rbz.co.zw/index.php" target="_blank" class="btn btn-sm btn-outline-primary">
                          <i class="ri-external-link-line me-1"></i> View RBZ Exchange Rates
                        </a>
                      </div>
                    </div>
                  </div>

                  {{-- Exchange Rate Calculator --}}
                  <div class="card border shadow-sm">
                    <div class="card-body">
                      <h1 class="card-title fw-bold mb-3">
                        <i class="ri-calculator-line me-1"></i> Exchange Rate Calculator
                      </h1>
                      <div class="row g-3">
                        <div class="col-12">
                          <label class="form-label small fw-bold">RBZ Exchange Rate (1 USD = ? ZWG)</label>
                          <input type="number" step="0.0001" min="0" class="form-control" id="rbz_rate_input" placeholder="Loading rate…" readonly>
                          <div class="text-muted small mt-1" id="rbz_rate_effective_date">Rate maintained by Accounts</div>
                        </div>
                        <div class="col-12">
                          <div class="bg-light p-3 rounded">
                            <div class="row">
                              <div class="col-6">
                                <div class="text-muted small">USD Amount</div>
                                <div class="fw-bold" id="calc_usd_amount">$0.00</div>
                              </div>
                              <div class="col-6 text-end">
                                <div class="text-muted small">ZWG Amount</div>
                                <div class="fw-bold text-success" id="calc_zwg_amount">ZWG 0.00</div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="text-center py-4">
                  <button type="button" class="btn btn-dark w-100 fw-bold py-3" id="btnPayCard">
                    <i class="ri-bank-card-2-line me-2"></i> <span id="paynow_btn_text">Pay with PayNow (USD)</span>
                  </button>
                  <div class="text-muted small mt-3">You will be automatically redirected to PayNow. Do not close this tab.</div>
                </div>

                <div id="paynow_redirecting" class="d-none">
                  <div class="alert alert-info border text-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <strong>Redirecting you to PayNow...</strong>
                    <div class="small mt-1">Please wait. You will return here automatically after payment.</div>
                  </div>
                </div>
              </div>
            </div>

            {{-- PROOF UPLOAD --}}
            <div class="tab-pane fade" id="pane-proof" role="tabpanel">
              <form id="proofForm" enctype="multipart/form-data">
                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Applicant first name</label>
                    <input type="text" class="form-control zmc-input" name="payer_first_name" value="{{ explode(' ', Auth::user()?->name ?? '')[0] ?? '' }}" readonly required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Applicant surname</label>
                    <input type="text" class="form-control zmc-input" name="payer_last_name" value="{{ count(explode(' ', Auth::user()?->name ?? '')) > 1 ? implode(' ', array_slice(explode(' ', Auth::user()?->name ?? ''), 1)) : '' }}" readonly required>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Payment date</label>
                    <input type="date" class="form-control zmc-input" name="payment_date" required max="{{ date('Y-m-d') }}">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label small">Amount paid</label>
                    <input type="number" step="0.01" min="0" class="form-control zmc-input" name="amount" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label small">Bank used / Payment method</label>
                    <input type="text" class="form-control zmc-input" name="method" placeholder="e.g. CBZ / Steward / Stanbic / EcoCash" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label small">Reference / Transaction number (optional)</label>
                    <input type="text" class="form-control zmc-input" name="reference" placeholder="e.g. Bank reference or transaction ID">
                  </div>
                  <div class="col-12">
                    <label class="form-label small">Upload proof (PDF/JPG/PNG)</label>
                    <input type="file" class="form-control zmc-input" name="proof_file" accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="text-muted small mt-1">Tip: make sure the amount, date and reference are clearly visible for audit.</div>
                  </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3 fw-bold" id="btnSubmitProof">
                  <i class="ri-upload-cloud-2-line me-1"></i> Submit Payment Proof
                </button>
              </form>
            </div>



          </div>
        </div>

        <div id="pay_polling" class="d-none text-center py-4">
          <div class="spinner-border text-success" role="status"></div>
          <div class="mt-3 fw-bold">Waiting for payment confirmation...</div>
          <div class="text-muted small">Please approve the payment on your phone.</div>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-3" id="btnCheckStatus">
            <i class="ri-refresh-line me-1"></i> Check Status
          </button>
        </div>
      </div>

      <div class="modal-footer zmc-modal-footer">
        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal" id="paymentModalCloseBtn">
          <i class="ri-arrow-left-line me-1"></i> Back to Edit Application
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
@php
  $configuredFees = [
    'accreditationNew' => config('fees.accreditation.new'),
    'accreditationRenewal' => config('fees.accreditation.renewal'),
    'accreditationForeignNew' => config('fees.accreditation.foreign_new'),
    'accreditationForeignRenewal' => config('fees.accreditation.foreign_renewal'),
    'mediaHouseApplication' => config('fees.media_house.application_fee'),
    'mediaHouseRegistration' => config('fees.media_house.registration_fee'),
    'mediaHouseRenewal' => config('fees.media_house.renewal_fee'),
  ];
@endphp
<script>
(function() {
  let currentAppId = null;
  let pollingInterval = null;
  let feeAmountUSD = 0;
  const configuredFees = @json($configuredFees);

  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

  // Debug: Log all pay buttons on page
  console.log('Payment modal script loading...');
  setTimeout(() => {
    const payButtons = document.querySelectorAll('.js-pay-now');
    console.log('Found', payButtons.length, 'pay buttons on page');
    payButtons.forEach((btn, idx) => {
      console.log(`Button ${idx}:`, {
        id: btn.getAttribute('data-app-id'),
        ref: btn.getAttribute('data-app-ref'),
        stage: btn.getAttribute('data-payment-stage'),
        html: btn.outerHTML
      });
    });
  }, 500);

  // Currency selection handler
  document.addEventListener('change', function(e) {
    if (e.target.name === 'payment_currency') {
      const currency = e.target.value;
      const rbzSection = document.getElementById('rbz_rate_section');
      const btnText = document.getElementById('paynow_btn_text');
      
      if (currency === 'ZWG') {
        rbzSection.classList.remove('d-none');
        btnText.textContent = 'Proceed to PayNow (ZWG)';
        loadCurrentRbzRate();
      } else {
        rbzSection.classList.add('d-none');
        btnText.textContent = 'Proceed to PayNow (USD)';
      }
      
      updateCurrencyHighlight();
    }
  });

  async function loadCurrentRbzRate() {
    const rateInput = document.getElementById('rbz_rate_input');
    const effectiveDate = document.getElementById('rbz_rate_effective_date');
    if (!rateInput || rateInput.dataset.loading === 'true') return;
    rateInput.dataset.loading = 'true';
    rateInput.value = '';
    rateInput.placeholder = 'Loading rate…';
    try {
      const response = await fetch(@json(route('paynow.rbz-rate')), { headers: { 'Accept': 'application/json' } });
      const data = await response.json();
      if (!response.ok || !data.available) throw new Error(data.message || 'Rate is unavailable.');
      rateInput.value = data.rate;
      rateInput.dispatchEvent(new Event('input', { bubbles: true }));
      if (effectiveDate) effectiveDate.textContent = data.effective_date ? `Effective ${data.effective_date} · maintained by Accounts` : 'Maintained by Accounts';
    } catch (error) {
      if (effectiveDate) effectiveDate.textContent = error.message || 'The exchange rate is unavailable. Contact Accounts.';
    } finally {
      delete rateInput.dataset.loading;
    }
  }

  // Exchange rate calculator
  document.getElementById('rbz_rate_input')?.addEventListener('input', function() {
    const rate = parseFloat(this.value) || 0;
    const usdAmount = feeAmountUSD;
    const zwgAmount = usdAmount * rate;
    
    document.getElementById('calc_usd_amount').textContent = `$${usdAmount.toFixed(2)}`;
    document.getElementById('calc_zwg_amount').textContent = `ZWG ${zwgAmount.toFixed(2)}`;
    document.getElementById('amount_zwg').textContent = `ZWG ${zwgAmount.toFixed(2)}`;
  });

  function updateCurrencyHighlight() {
    document.querySelectorAll('.currency-option').forEach(opt => {
      const input = opt.querySelector('input[type="radio"]');
      if (input && input.checked) {
        opt.classList.add('selected');
      } else {
        opt.classList.remove('selected');
      }
    });
  }

  // Make currency options clickable
  document.querySelectorAll('.currency-option').forEach(opt => {
    opt.addEventListener('click', function() {
      const input = this.querySelector('input[type="radio"]');
      if (input) {
        input.checked = true;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  });

  function showPayError(msg) {
    const box = document.getElementById('pay_error');
    box.textContent = msg;
    box.classList.remove('d-none');
    document.getElementById('pay_loading').classList.add('d-none');
    document.getElementById('pay_options').classList.remove('d-none');
    document.getElementById('pay_polling').classList.add('d-none');
  }

  function showPaySuccess(msg) {
    const box = document.getElementById('pay_success');
    box.textContent = msg;
    box.classList.remove('d-none');
    document.getElementById('pay_loading').classList.add('d-none');
    document.getElementById('pay_options').classList.add('d-none');
    document.getElementById('pay_polling').classList.add('d-none');
    if (pollingInterval) clearInterval(pollingInterval);
  }

  function resetPayModal() {
    document.getElementById('pay_error').classList.add('d-none');
    document.getElementById('pay_success').classList.add('d-none');
    document.getElementById('pay_loading').classList.add('d-none');
    document.getElementById('pay_options').classList.remove('d-none');
    document.getElementById('pay_polling').classList.add('d-none');
    document.getElementById('paynow_redirecting')?.classList.add('d-none');
    document.getElementById('rbz_rate_input').value = '';
    document.getElementById('currency_usd').checked = true;
    document.getElementById('rbz_rate_section').classList.add('d-none');
    updateCurrencyHighlight();

    if (pollingInterval) clearInterval(pollingInterval);

    document.getElementById('proofForm')?.reset();

    // ensure PayNow tab active
    const tab = document.getElementById('tab-paynow');
    if (tab && window.bootstrap?.Tab) {
      bootstrap.Tab.getOrCreateInstance(tab).show();
    }
  }

  window.initPaymentModal = function(appId, appRef, stage = 'application_fee', applicationType = 'accreditation', mediaCategory = 'default', accreditationScope = 'local') {
    console.log('=== initPaymentModal called ===');
    console.log('Arguments:', { appId, appRef, stage, applicationType });
    
    if (!appId || appId === '' || appId === null || appId === 'undefined') {
      console.error('ERROR: Invalid appId received:', appId);
      alert('Application ID is missing. Please refresh the page and try again.');
      return;
    }
    
    currentAppId = appId;
    console.log('Set currentAppId to:', currentAppId);
    
    // Visual confirmation on page
    console.log('✅ Payment modal initialized with App ID:', appId, 'Ref:', appRef);

    // Set fee amount based on application type
    if (applicationType === 'registration' || applicationType === 'mediahouse') {
      if (stage === 'registration_fee') {
        feeAmountUSD = configuredFees.mediaHouseRegistration[mediaCategory] ?? configuredFees.mediaHouseRegistration.default;
      } else if (stage === 'renewal_fee') {
        feeAmountUSD = configuredFees.mediaHouseRenewal;
      } else {
        feeAmountUSD = configuredFees.mediaHouseApplication;
      }
    } else {
      if (accreditationScope === 'foreign') {
        feeAmountUSD = stage === 'renewal_fee'
          ? configuredFees.accreditationForeignRenewal
          : configuredFees.accreditationForeignNew;
      } else {
        feeAmountUSD = stage === 'renewal_fee'
          ? configuredFees.accreditationRenewal
          : configuredFees.accreditationNew;
      }
    }
    
    console.log('Fee amount set to:', feeAmountUSD, 'USD');
    
    // Update currency amounts
    document.getElementById('amount_usd').textContent = `$${feeAmountUSD.toFixed(2)}`;
    document.getElementById('calc_usd_amount').textContent = `$${feeAmountUSD.toFixed(2)}`;
    
    // Clear previous ZWG calculation
    document.getElementById('rbz_rate_input').value = '';
    document.getElementById('calc_zwg_amount').textContent = `ZWG 0.00`;
    document.getElementById('amount_zwg').textContent = `ZWG 0.00`;
    
    // Also store in a data attribute for extra safety
    const modal = document.getElementById('paymentModal');
    if (modal) {
      modal.setAttribute('data-current-app-id', appId);
      modal.setAttribute('data-current-app-ref', appRef);
      modal.setAttribute('data-application-type', applicationType);
      console.log('Stored in modal data attributes');
    } else {
      console.error('ERROR: Could not find paymentModal element');
    }
    
    // Also store in session storage as ultimate fallback
    try {
      sessionStorage.setItem('zmc_current_payment_app_id', appId);
      sessionStorage.setItem('zmc_current_payment_app_ref', appRef);
      console.log('Stored in sessionStorage as fallback');
    } catch (e) {
      console.warn('Could not store in sessionStorage:', e);
    }
    
    // Update close button text and behavior
    const closeBtn = document.getElementById('paymentModalCloseBtn');
    if (closeBtn) {
      closeBtn.innerHTML = 'Close';
      
      // Store redirect URL based on application type
      const redirectUrl = applicationType === 'mediahouse' || applicationType === 'registration' 
        ? "{{ route('mediahouse.portal') }}"
        : "{{ route('accreditation.home') }}";
      
      closeBtn.setAttribute('data-redirect-url', redirectUrl);
    }
    
    resetPayModal();

    // resetPayModal clears the form between applications; restore the signed-in
    // applicant identity afterwards so required payer fields cannot be lost.
    const proofForm = document.getElementById('proofForm');
    const signedInName = @json(Auth::user()?->name ?? '');
    if (proofForm) {
      const nameParts = signedInName.trim().split(/\s+/).filter(Boolean);
      const firstName = proofForm.querySelector('[name="payer_first_name"]');
      const lastName = proofForm.querySelector('[name="payer_last_name"]');
      if (firstName) firstName.value = nameParts.shift() || '';
      if (lastName) lastName.value = nameParts.join(' ');
    }
    
    document.getElementById('modal_payment_stage').value = stage;
    
    const stageLabel = stage === 'registration_fee' ? 'Registration Fee' : 'Application Fee';
    const metaText = `Application: ${appRef} (${stageLabel}) • App ID: ${appId}`;
    document.getElementById('pay_meta').textContent = metaText;
    document.getElementById('pay_meta').setAttribute('title', `Application ID: ${appId} | Reference: ${appRef}`);
    
    console.log('Modal metadata set to:', metaText);
    
    console.log('Opening payment modal...');
    const modal2 = document.getElementById('paymentModal');
    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
      bootstrap.Modal.getOrCreateInstance(modal2).show();
    }
    
    console.log('currentAppId set to:', currentAppId);
  };

  function setBusy(btn, busy, text) {
    if (!btn) return;
    btn.disabled = !!busy;
    if (text) btn.dataset.originalText = btn.dataset.originalText || btn.innerHTML;
    if (busy) btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${text || 'Submitting...'}`;
    else if (btn.dataset.originalText) btn.innerHTML = btn.dataset.originalText;
  }

  async function postForm(url, formEl, btnEl) {
    try {
      document.getElementById('pay_error').classList.add('d-none');
      document.getElementById('pay_success').classList.add('d-none');

      const fd = new FormData(formEl);
      setBusy(btnEl, true, 'Submitting...');

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf(),
        },
        body: fd,
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.ok === false) {
        if (data.errors) {
          const first = Object.values(data.errors)[0];
          throw new Error(Array.isArray(first) ? first[0] : String(first));
        }
        throw new Error(data.message || 'Submission failed');
      }

      showPaySuccess(data.message || 'Submitted successfully');

      const rowBtn = document.querySelector(`.js-pay-now[data-app-id="${currentAppId}"]`);
      if (rowBtn) {
        rowBtn.classList.remove('btn-success');
        rowBtn.classList.add('btn-outline-secondary');
        rowBtn.innerHTML = `<i class="ri-time-line me-1"></i> Submitted`;
        rowBtn.disabled = true;
      }

      setTimeout(() => {
        const modalEl = document.getElementById('paymentModal');
        const inst = window.bootstrap?.Modal?.getInstance(modalEl) || window.bootstrap?.Modal?.getOrCreateInstance(modalEl);
        inst?.hide();
      }, 1500);

    } catch (e) {
      showPayError(e.message || 'Network error. Please try again.');
    } finally {
      setBusy(btnEl, false);
    }
  }

  function startPolling() {
    if (!currentAppId) return;
    pollingInterval = setInterval(async () => {
      try {
        const res = await fetch(`/payments/${currentAppId}/status`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.paid) {
          showPaySuccess('Payment confirmed! Your application is now being processed.');
          setTimeout(() => window.location.reload(), 1500);
        }
      } catch (e) {
        console.error('Polling error:', e);
      }
    }, 5000);
  }

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.js-pay-now');
    if (!btn) return;

    console.log('Pay button click handler triggered');
    console.log('Button element:', btn);
    console.log('Button HTML:', btn.outerHTML);

    const appId = btn.getAttribute('data-app-id');
    const appRef = btn.getAttribute('data-app-ref');
    const stage = btn.getAttribute('data-payment-stage') || 'application_fee';
    const applicationType = btn.getAttribute('data-application-type') || 'accreditation';
    const mediaCategory = btn.getAttribute('data-media-category') || 'default';
    const accreditationScope = btn.getAttribute('data-accreditation-scope') || 'local';

    console.log('Extracted attributes:', { appId, appRef, stage, applicationType, mediaCategory, accreditationScope });

    // DIAGNOSTIC: Show what we extracted
    if (!appId || appId === '' || appId === null || appId === 'undefined') {
      console.error('Missing or empty data-app-id attribute on pay button', btn);
      console.error('All button attributes:', {
        'data-app-id': btn.getAttribute('data-app-id'),
        'data-app-ref': btn.getAttribute('data-app-ref'),
        'data-payment-stage': btn.getAttribute('data-payment-stage'),
      });
      alert(`DIAGNOSTIC: Application ID is missing from button!\n\nButton has:\n- data-app-id: ${btn.getAttribute('data-app-id')}\n- data-app-ref: ${btn.getAttribute('data-app-ref')}\n\nPlease take a screenshot of this message and the page.`);
      return;
    }

    console.log('Calling initPaymentModal with appId:', appId);
    window.initPaymentModal(appId, appRef, stage, applicationType, mediaCategory, accreditationScope);
  });


  // PayNow - card with currency selection
  document.getElementById('btnPayCard')?.addEventListener('click', async function() {
    console.log('=== PayNow button clicked ===');
    
    // IMMEDIATE CHECK - Show diagnostic on screen
    const modal = document.getElementById('paymentModal');
    const diagnosticInfo = {
      'currentAppId variable': currentAppId,
      'modal data-current-app-id': modal?.getAttribute('data-current-app-id'),
      'sessionStorage': sessionStorage.getItem('zmc_current_payment_app_id'),
      'modal data-current-app-ref': modal?.getAttribute('data-current-app-ref')
    };
    
    console.log('DIAGNOSTIC INFO:', diagnosticInfo);
    
    // Show on-screen diagnostic
    const diagText = Object.entries(diagnosticInfo)
      .map(([k, v]) => `${k}: ${v || 'NULL'}`)
      .join('\n');
    
    console.log('Diagnostic text:', diagText);
    
    const selectedCurrency = document.querySelector('input[name="payment_currency"]:checked')?.value || 'USD';
    
    // Validate ZWG rate if ZWG is selected
    if (selectedCurrency === 'ZWG') {
      const rate = parseFloat(document.getElementById('rbz_rate_input').value) || 0;
      if (rate <= 0) {
        alert('The current RBZ exchange rate is unavailable. Please contact Accounts before proceeding with ZWG payment.');
        document.getElementById('rbz_rate_input').focus();
        return;
      }
    }

    // Try multiple ways to get the application ID
    let appId = currentAppId;
    console.log('Step 1 - currentAppId:', appId);
    
    if (!appId || appId === '' || appId === 'null' || appId === 'undefined') {
      // Try to get from modal data attribute
      if (modal && modal.getAttribute('data-current-app-id')) {
        appId = modal.getAttribute('data-current-app-id');
        currentAppId = appId; // Update the global variable
        console.log('Step 2 - Recovered from modal data attribute:', appId);
      }
    }

    if (!appId || appId === '' || appId === 'null' || appId === 'undefined') {
      // Try sessionStorage
      try {
        appId = sessionStorage.getItem('zmc_current_payment_app_id');
        if (appId) {
          currentAppId = appId; // Update the global variable
          console.log('Step 3 - Recovered from sessionStorage:', appId);
        }
      } catch (e) {
        console.warn('Could not read from sessionStorage:', e);
      }
    }

    if (!appId || appId === '' || appId === 'null' || appId === 'undefined') {
      // Try to get appId from all pay buttons on page
      const allPayButtons = document.querySelectorAll('.js-pay-now');
      console.log('Found', allPayButtons.length, 'pay buttons on page');
      allPayButtons.forEach((btn, idx) => {
        const btnAppId = btn.getAttribute('data-app-id');
        console.log(`Button ${idx} has app ID:`, btnAppId);
        if (btnAppId && btnAppId !== '' && btnAppId !== 'null') {
          appId = btnAppId;
          currentAppId = appId;
          console.log('Step 4 - Recovered from button', idx, ':', appId);
        }
      });
    }

    if (!appId || appId === '' || appId === 'null' || appId === 'undefined') {
      console.error('FATAL ERROR: Failed to retrieve application ID from any source');
      const errorDetails = `DIAGNOSTIC INFORMATION:
      
Current App ID Variable: ${currentAppId || 'NULL'}
Modal Data Attribute: ${modal?.getAttribute('data-current-app-id') || 'NULL'}
SessionStorage: ${sessionStorage.getItem('zmc_current_payment_app_id') || 'NULL'}
Modal Ref Attribute: ${modal?.getAttribute('data-current-app-ref') || 'NULL'}

Pay buttons on page: ${document.querySelectorAll('.js-pay-now').length}

Please take a screenshot of this message and share it.`;
      
      alert(errorDetails);
      showPayError('Application ID is missing. Please close this modal, refresh the page, and try again.');
      return;
    }

    console.log('SUCCESS: Using application ID:', appId);
    currentAppId = appId;

    setBusy(this, true, 'Initiating payment...');

    try {
      const res = await fetch(`/payments/${appId}/initiate`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf(),
        },
        body: JSON.stringify({
          currency: selectedCurrency,
          ...(selectedCurrency === 'ZWG' ? { rbz_rate: document.getElementById('rbz_rate_input').value } : {}),
        }),
      });

      const data = await res.json().catch(() => ({}));

      if (data.success && data.redirect_url) {
        // Show redirecting message then redirect the full page
        document.getElementById('pay_options').classList.add('d-none');
        document.getElementById('paynow_redirecting').classList.remove('d-none');
        window.location.href = data.redirect_url;
      } else {
        showPayError(data.message || 'Failed to initiate payment. Please try again.');
        setBusy(this, false);
      }
    } catch (e) {
      showPayError('Network error. Please try again.');
      setBusy(this, false);
    }
  });



  document.getElementById('btnCheckStatus')?.addEventListener('click', async function() {
    if (!currentAppId) return;

    try {
      const res = await fetch(`/payments/${currentAppId}/status`, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();

      if (data.paid) {
        showPaySuccess('Payment confirmed! Your application is now being processed.');
        setTimeout(() => window.location.reload(), 1500);
      } else {
        alert('Payment not yet confirmed. Status: ' + (data.status || 'pending'));
      }
    } catch (e) {
      alert('Could not check status. Please try again.');
    }
  });

  document.getElementById('proofForm')?.addEventListener('submit', function(ev) {
    ev.preventDefault();
    
    // Get currentAppId with fallback to modal data attribute
    let appId = currentAppId;
    if (!appId || appId === '' || appId === 'null' || appId === 'undefined') {
      const modal = document.getElementById('paymentModal');
      appId = modal?.getAttribute('data-current-app-id');
      console.warn('currentAppId was empty, using fallback from modal:', appId);
    }
    
    // Validate appId exists
    if (!appId || appId === '' || appId === 'null' || appId === 'undefined') {
      alert('Application ID is missing. Please close this modal, refresh the page, and try again.');
      console.error('Proof submission failed: appId is', appId, 'currentAppId is', currentAppId);
      return;
    }
    
    const stage = document.getElementById('modal_payment_stage')?.value || 'application_fee';
    
    // Check if we're in media house portal by checking the current URL
    const isMediaHouse = window.IS_MEDIA_HOUSE_PORTAL
      || window.location.pathname.includes('/media-house/registration')
      || window.location.pathname.includes('/mediahouse');
    
    let url = `/payments/${appId}/upload-proof`;
    if (isMediaHouse) {
      url = stage === 'registration_fee' 
        ? `/media-house/registration/applications/${appId}/payment/registration-fee/proof`
        : `/media-house/registration/applications/${appId}/payment/application-fee/proof`;
    }
    
    console.log('Submitting proof to URL:', url, 'with appId:', appId);
    postForm(url, this, document.getElementById('btnSubmitProof'));
  });

  // Handle payment modal close - redirect to dashboard if closed without completing payment
  const paymentModal = document.getElementById('paymentModal');
  if (paymentModal) {
    paymentModal.addEventListener('hidden.bs.modal', function() {
      const closeBtn = document.getElementById('paymentModalCloseBtn');
      const redirectUrl = closeBtn?.getAttribute('data-redirect-url');
      
      // Only redirect if we have a redirect URL set (means modal was opened from form submission)
      if (redirectUrl) {
        console.log('Payment modal closed, redirecting to:', redirectUrl);
        window.location.href = redirectUrl;
      }
    });
  }

})();
</script>

<style>
.currency-option {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  position: relative;
}

.currency-option input[type="radio"] {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.currency-option label {
  cursor: pointer;
  margin: 0;
  user-select: none;
}

.currency-option:hover {
  border-color: var(--zmc-yellow, #facc15);
  background: #fffbeb;
  transform: translateY(-2px);
}

.currency-option.selected {
  border-color: var(--zmc-yellow, #facc15);
  background: #fef9c3;
  box-shadow: 0 4px 12px rgba(250, 204, 21, 0.2);
}

.currency-option i {
  font-size: 32px;
  color: var(--zmc-yellow, #facc15);
  display: block;
  margin-bottom: 8px;
}

.currency-amount {
  font-size: 18px;
  font-weight: 700;
}
</style>
@endpush
