@extends('layouts.portal')
@section('title', 'Card Preview')

@section('content')
@php
  $fd = is_array($application->form_data) ? $application->form_data : (json_decode($application->form_data ?? '[]', true) ?: []);
  $fullName = trim(($fd['first_name'] ?? '') . ' ' . ($fd['surname'] ?? ''));
  if ($fullName === '') $fullName = $application->applicant?->name ?? '—';

  $passportPhoto = $application->documents->where('doc_type', 'passport_photo')->first();
  $photoUrl = $passportPhoto ? $passportPhoto->url : null;

  /**
   * PaymentTypeLetter:
   *  - waiver => C
   *  - paynow/proof/anything else => P
   */
  $paymentLetter = 'P';
  try {
    // If you have a payments relation, use it. Otherwise default P.
    $lastPayment = method_exists($application, 'payments') ? $application->payments()->latest()->first() : null;
    if ($lastPayment && strtolower((string)$lastPayment->method) === 'waiver') $paymentLetter = 'C';
  } catch (\Throwable $e) {}

  /**
   * Media Practitioner Type Initials:
   * Example given: Videographer => JV
   * Rule implemented:
   *  - Always starts with J
   *  - Second letter = first meaningful word initial of designation/type
   */
  $designation = (string)($fd['designation'] ?? 'Media Practitioner');
  $designationClean = trim(preg_replace('/\s+/', ' ', $designation));
  $words = array_values(array_filter(explode(' ', strtoupper($designationClean))));

  $second = 'J';
  if (count($words) >= 1) {
    // If the designation includes PRACTITIONER, use next word (PHOTO PRACTITIONER => JP etc)
    $idxPractitioner = array_search('PRACTITIONER', $words);
    if ($idxPractitioner !== false && isset($words[$idxPractitioner + 1])) {
      $second = substr($words[$idxPractitioner + 1], 0, 1);
    } else {
      // Otherwise use first word (VIDEOGRAPHER => V)
      $second = substr($words[0], 0, 1);
      // if first word is MEDIA and no next word, fallback to J
      if ($second === 'M' && !isset($words[1])) $second = 'J';
    }
  }
  $typeInitials = 'J' . $second; // e.g. JV

  // Number format: DDMM + Initials + YYYY + PaymentLetter
  // Update: Using model-driven category-based numbering if available, else fallback
  $ddmm = now()->format('dm');
  $yyyy = now()->format('Y');
  $generatedAccreditationId = $application->accreditationRecord->certificate_no ?? $application->generateFormattedNumber();

  $defaults = [
    'name'        => $fullName,
    // reference still exists for internal use, but we DO NOT SHOW it in preview anymore
    'ref'         => $application->reference ?? ('APP-' . $application->id),
    'designation' => $designationClean ?: 'Media Practitioner',
    'organisation'=> $fd['employer_name'] ?? '—',
    'scope'       => strtoupper((string)($application->journalist_scope ?? 'local')),
    'region'      => strtoupper((string)($application->collection_region ?? '—')),
    'valid_from'  => now()->format('Y-m-d'),
    'valid_to'    => now()->addYear()->format('Y-m-d'),

    // Accreditation ID fields/components (store separately too)
    'accreditation_id' => $generatedAccreditationId,
    'acc_ddmm'         => $ddmm,
    'acc_initials'     => $typeInitials,
    'acc_year'         => $yyyy,
    'acc_pay_letter'   => $paymentLetter,
  ];

  $payload = array_merge($defaults, $edits ?? []);
  // Accreditation cards run from the day the card is generated through
  // 31 December of that same year. Do not let older saved edits override it.
  $cardIssueDate = now();
  $payload['valid_from'] = $cardIssueDate->format('Y-m-d');
  $payload['valid_to'] = $cardIssueDate->copy()->endOfYear()->format('Y-m-d');

  // Keep only the legacy Standard layout and the current 2027 artwork in the
  // production selector. An approved Design Studio card is the current option.
  $template = $designTemplate ? 'zmc_2027' : ($application->form_data['card_template'] ?? 'default');

  $isForeign = strtoupper((string)($payload['scope'] ?? 'LOCAL')) === 'FOREIGN';
  // Foreign cards identify the holder with the passport number; local cards
  // use the national ID captured in the application.
  $displayId = $isForeign
    ? ($fd['passport_no'] ?? $fd['passport_number'] ?? $payload['id_number'] ?? '—')
    : ($fd['national_reg_no'] ?? $fd['national_id'] ?? $fd['id_number'] ?? $payload['id_number'] ?? '—');

  // Use dynamic verification link for QR
  $verificationNumber = $payload['accreditation_id']
    ?? $application->accreditationRecord?->accreditation_number
    ?? $application->accreditationRecord?->certificate_no
    ?? $application->accreditationRecord?->record_number;
  // Cards scan directly to the public website verification page.
  $qrValue = $verificationNumber
    ? 'https://zmc.org.zw/verify-accreditation?number=' . rawurlencode((string) $verificationNumber)
    : route('public.verify', 'invalid');

  $designStudioLayout = $designTemplate?->layout_config;
  $designStudioBackground = $designTemplate?->backgroundUrl();
  $designStudioData = [
    'full_name' => $payload['name'] ?? '—',
    'first_name' => $fd['first_name'] ?? '—',
    'surname' => $fd['surname'] ?? '—',
    'national_id' => $displayId,
    'passport_number' => $fd['passport_number'] ?? '—',
    'media_organisation' => (($fd['employer_name'] ?? '') !== '' ? $fd['employer_name'] : 'Freelance'),
    'designation' => $payload['designation'] ?? 'Media Practitioner',
    'accreditation_number' => $payload['accreditation_id'] ?? '—',
    'accreditation_category_name' => $application->accreditation_category_code ?? '—',
    'application_type' => 'Media Practitioner Accreditation',
    'issue_date' => $payload['valid_from'] ?? '—',
    'expiry_date' => $payload['valid_to'] ?? '—',
    'collection_office' => $payload['region'] ?? '—',
    'verification_qr' => $qrValue,
  ];
@endphp

<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <div class="card-preview-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Card Preview</h4>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Preview & edit the accreditation card before printing.
        @if($designTemplate)
          <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">Design Studio: {{ $designTemplate->name }}</span>
        @endif
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      {{-- Removed reference pill as requested --}}
      <a href="{{ route('staff.production.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Back">
        <i class="ri-arrow-left-line me-1"></i> Back
      </a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-5">
      <div class="zmc-card">
        <h6 class="fw-bold mb-3"><i class="ri-edit-2-line me-2" style="color:var(--zmc-accent-dark)"></i> Edit fields</h6>

        <form method="POST" action="{{ route('staff.production.applications.card.print', $application) }}" target="_blank" id="cardForm">
          @csrf

          <input type="hidden" name="card_payload[ref]" id="p_ref" value="{{ e($payload['ref']) }}">
          <input type="hidden" name="card_payload[acc_ddmm]" id="p_acc_ddmm" value="{{ e($payload['acc_ddmm']) }}">
          <input type="hidden" name="card_payload[acc_initials]" id="p_acc_initials" value="{{ e($payload['acc_initials']) }}">
          <input type="hidden" name="card_payload[acc_year]" id="p_acc_year" value="{{ e($payload['acc_year']) }}">
          <input type="hidden" name="card_payload[acc_pay_letter]" id="p_acc_pay_letter" value="{{ e($payload['acc_pay_letter']) }}">
          <input type="hidden" name="card_payload[qr_value]" id="p_qr_value" value="{{ e($qrValue) }}">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label zmc-lbl">Name</label>
              <input class="form-control zmc-input" value="{{ $payload['name'] }}" data-bind="name" name="card_payload[name]">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label zmc-lbl">Scope</label>
              <select class="form-control zmc-input" data-bind="scope" name="card_payload[scope]">
                <option value="LOCAL" {{ $payload['scope'] == 'LOCAL' ? 'selected' : '' }}>LOCAL</option>
                <option value="FOREIGN" {{ $payload['scope'] == 'FOREIGN' ? 'selected' : '' }}>FOREIGN</option>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label zmc-lbl">Accreditation ID (ID No.)</label>
              <input class="form-control zmc-input" value="{{ $payload['accreditation_id'] }}" data-bind="accreditation_id" name="card_payload[accreditation_id]">
              <div class="form-text">Auto-generated: DDMM + Initials + YYYY + C/P</div>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label zmc-lbl">Designation</label>
              <input class="form-control zmc-input" value="{{ $payload['designation'] }}" data-bind="designation" name="card_payload[designation]">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label zmc-lbl">Region</label>
              <input class="form-control zmc-input" value="{{ $payload['region'] }}" data-bind="region" name="card_payload[region]" readonly>
              <div class="form-text">Region is taken from the submitted application and cannot be changed during card production.</div>
            </div>

            <div class="col-12">
              <label class="form-label zmc-lbl">Organisation</label>
              <input class="form-control zmc-input" value="{{ $payload['organisation'] }}" data-bind="organisation" name="card_payload[organisation]">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label zmc-lbl">Valid from</label>
              <input type="date" class="form-control zmc-input" value="{{ $payload['valid_from'] }}" data-bind="valid_from" name="card_payload[valid_from]" readonly title="Automatically set to the card generation date">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label zmc-lbl">Valid to</label>
              <input type="date" class="form-control zmc-input" value="{{ $payload['valid_to'] }}" data-bind="valid_to" name="card_payload[valid_to]" readonly title="Automatically set to 31 December of the generation year">
            </div>

            <div class="col-12">
              <label class="form-label zmc-lbl">Card Template</label>
              <select name="template" class="form-select zmc-input" onchange="updateCardTemplate(this.value)">
                <option value="default" {{ $template == 'default' ? 'selected' : '' }}>Standard ZMC (Previous)</option>
                <option value="zmc_2027" {{ $template == 'zmc_2027' ? 'selected' : '' }}>ZMC Media Card 2027 (Current)</option>
              </select>
            </div>
          </div>

          <div class="print-actions mt-3">
            <button type="submit" class="btn btn-warning fw-bold px-4 py-2" formaction="{{ route('staff.production.applications.card.print_duplex', $application) }}" formtarget="_blank" title="Open one print job containing page 1 (front) and page 2 (back)">
              <i class="ri-printer-cloud-line me-1"></i> Print both sides (duplex)
            </button>
            <button type="button" class="btn btn-light fw-bold" id="btnReset">Reset</button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-12 col-lg-7">
      <div class="zmc-card">
        <h6 class="fw-bold mb-3"><i class="ri-eye-line me-2" style="color:var(--zmc-accent-dark)"></i> Live preview (3D View)</h6>

        <div class="d-flex flex-column align-items-center mb-4">
          <div class="zmc-3d-card-wrap">
            <div class="zmc-3d-card" id="mainCard3D">
              {{-- FRONT (background template image + editable overlays) --}}
              <div id="cardPreview"
                   class="zmc-3d-front zmc-card-preview zmc-tpl-front {{ $isForeign ? 'is-foreign' : 'is-local' }} {{ $designTemplate ? 'design-studio-active' : '' }}"
                   style="background: url('{{ asset($isForeign ? 'card_foreign_2027.jpg' : 'card_local_2027.jpg') }}') center center / 100% 100% no-repeat; border:1px solid rgba(0,0,0,0.18);">

                @if($designTemplate && is_array($designStudioLayout))
                  <div id="designStudioOverlay"
                       data-template-id="{{ $designTemplate->id }}"
                       data-template-name="{{ e($designTemplate->name) }}"
                       style="background-image:url('{{ $designStudioBackground }}');">
                    <canvas id="designStudioCanvas" aria-label="{{ $designTemplate->name }} production preview"></canvas>
                  </div>
                @endif

                {{-- Vertical card number on the colored side strip --}}
                <div class="tpl-serial">
                  <span contenteditable="true" data-edit="accreditation_id">{{ strtoupper(preg_replace('/[^A-Z0-9]/i','', (string)$payload['accreditation_id'])) ?: '0101JE45623E' }}</span>
                </div>

                {{-- Photo (right side) --}}
                <div class="tpl-photo">
                  @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="Photo">
                  @else
                    <div class="tpl-photo-ph">PASSPORT<br>PHOTO</div>
                  @endif
                </div>

                {{-- Applicant details (lower-left) --}}
                <div class="tpl-details">
                  <div class="tpl-row tpl-name-row">
                    <span class="tpl-lbl">NAME:</span>
                    <span class="tpl-val" contenteditable="true" data-edit="name">{{ strtoupper($payload['name'] ?? 'FULL NAME') }}</span>
                  </div>
                  <div class="tpl-row">
                    <span class="tpl-lbl">I.D:</span>
                    <span class="tpl-val" contenteditable="true" data-edit="national_id">{{ strtoupper($displayId) }}</span>
                  </div>
                  <div class="tpl-row">
                    <span class="tpl-lbl">ORG:</span>
                    <span class="tpl-val" contenteditable="true" data-edit="organisation">{{ strtoupper($payload['organisation'] ?? 'ZMC') }}</span>
                  </div>
                  <div class="tpl-row">
                    <span class="tpl-lbl">Category:</span>
                    <span class="tpl-val" contenteditable="true" data-edit="category_code">{{ strtoupper($application->accreditation_category_code ?? 'JE') }}</span>
                  </div>
                  <div class="tpl-validity">
                    <strong>Valid:</strong>
                    <span contenteditable="true" data-edit="valid_from">{{ $payload['valid_from'] }}</span>
                    &nbsp;<strong>To:</strong>
                    <span contenteditable="true" data-edit="valid_to">{{ $payload['valid_to'] }}</span>
                  </div>
                </div>

                <span style="display:none" data-edit="scope">{{ $payload['scope'] }}</span>
              </div>

              {{-- BACK (the approved black-header verification layout) --}}
              <div id="cardBackPreview" class="zmc-3d-back zmc-card-preview zmc-card-back-preview zmc-new-back {{ $isForeign ? 'is-foreign' : 'is-local' }}">
                <div class="nb-top">
                  <div class="nb-brand">ZIMBABWE <span>MEDIA</span><br>COMMISSION</div>
                  <div class="nb-tagline">ZMC - promoting ethical and<br>professional conduct by all media practitioner</div>
                </div>
                <div class="nb-body-wrap">
                  <div class="nb-copy">
                    <div class="nb-contact">
                      <div>{{ config('organization.registered_address') }}</div>
                      <div>Tel: {{ config('organization.public_phone', '253509/10 or 253572/5/6') }}</div>
                      <div>Fax: 253639</div>
                      <div>Email: info@zmc.org.zw</div>
                    </div>
                  </div>
                  <div class="nb-legal"><div class="nb-salutation">To Whom it May Concern</div><div class="nb-body">The holder of this card is accredited with the Zimbabwe Media Commission for the duration shown. Please accord him/her every assistance in the discharge of his/her duties.</div></div>
                  <div class="nb-qr-box"><div id="cardBackQr"></div><span>SCAN TO VERIFY</span></div>
                  <div class="nb-footer">https://zmc.org.zw</div>
                </div>
                {{-- Hidden editable field preserved so save() still posts designation --}}
                <span style="display:none" contenteditable="true" data-edit="designation">{{ $payload['designation'] }}</span>
              </div>
            </div>
          </div>

          <button type="button" class="btn btn-outline-dark btn-sm mt-3 px-4" onclick="flipCard()">
            <i class="ri-refresh-line me-1"></i> Flip Card View
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
  :root{
    --zmc-local:#1a237e;
    --zmc-foreign:#1a5f2a;
    --zmc-local-lite: rgba(26,35,126,.14);
    --zmc-foreign-lite: rgba(26,95,42,.14);
  }

  /* 3D Card Wrap */
  .zmc-3d-card-wrap {
    perspective: 1200px;
    width: 440px; /* Increased from 340px */
    height: 280px; /* Increased from 215px */
    margin: 0 auto;
    position: relative;
    display: block;
  }
  .zmc-3d-card {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    /* Keep one fixed frame; switching faces must never move the card. */
    transition: none;
    transform: none !important;
    transform-style: flat;
    cursor: pointer;
    transform-origin: center center;
  }
  .zmc-3d-card.is-flipped {
    transform: none !important;
  }
  .zmc-3d-front, .zmc-3d-back {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    margin: 0 !important;
    border-radius: 12px;
    overflow: hidden;
    box-sizing: border-box;
    transform-origin: center center;
    transform: none !important;
    transition: opacity .2s ease, visibility .2s ease;
  }
  .zmc-3d-front { z-index: 2; opacity: 1; visibility: visible; pointer-events: auto; }
  .zmc-3d-back {
    z-index: 1; opacity: 0; visibility: hidden; pointer-events: none;
  }
  .zmc-3d-card.is-flipped .zmc-3d-front {
    z-index: 1; opacity: 0; visibility: hidden; pointer-events: none;
  }
  .zmc-3d-card.is-flipped .zmc-3d-back {
    z-index: 2; opacity: 1; visibility: visible; pointer-events: auto;
  }

  .zmc-card-preview {
    width: 100% !important;
    height: 100% !important;
    border: 2px solid rgba(15,23,42,.18);
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 40%, #ffffff 100%);
    position: relative;
    box-shadow: 0 18px 45px rgba(0,0,0,.15);
    overflow: hidden; /* ensure content stays inside */
  }

  .zmc-card-preview.is-local { --accent: var(--zmc-local); --accentLite: var(--zmc-local-lite); }
  .zmc-card-preview.is-foreign { --accent: var(--zmc-foreign); --accentLite: var(--zmc-foreign-lite); }

  /* Template Variations */
  .template-modern_dark { --accent: #212121; --accentLite: rgba(33,33,33,0.15); }
  .template-eco_green { --accent: #1a1a1a; --accentLite: rgba(245,197,24,0.12); }
  .template-royal_gold { --accent: #4a148c; --accentLite: rgba(74,20,140,0.15); }
  .template-ocean_blue { --accent: #01579b; --accentLite: rgba(1,87,155,0.15); }
  .template-crimson_pro { --accent: #b71c1c; --accentLite: rgba(183,28,28,0.15); }

  .card-bg-wave {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60%;
    background: radial-gradient(circle at 20% 80%, var(--accentLite), transparent 55%),
                radial-gradient(circle at 80% 90%, var(--accentLite), transparent 55%);
    opacity: 1;
  }

  .card-bg-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 65%;
    opacity: 0.08;
    z-index: 1;
    pointer-events: none;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  .card-bg-watermark img {
    width: 100%;
    height: auto;
    filter: grayscale(100%);
  }

  .card-header-row {
    display: flex;
    align-items: center;
    justify-content: center; /* Center-align the header content */
    gap: 14px; /* Increased gap for better spacing with centered logo */
    padding: 8px 12px;
    position: relative;
    z-index: 2;
    min-height: 44px;
  }

  .logo-small { width: 32px; height: 32px; object-fit: contain; display: block; }

  .header-title {
    flex: none; /* Don't grow, keep it centered */
  }

  .header-title .main {
    font-size: 11px; /* Increased from 8.5px */
    font-weight: 900;
    color: #000;
    letter-spacing: 0.1px;
    line-height: 1.0;
    text-align: center;
  }

  .header-title .highlight { color: #228B22; }

  .header-title .sub{
    font-size: 10px; /* Increased from 7.5px */
    font-weight: 800;
    color: rgba(15,23,42,.65);
    margin-top: 1px;
    letter-spacing: .4px;
    text-align: center;
  }

  .card-body-content {
    display: flex;
    gap: 10px;
    padding: 10px 12px;
    position: relative;
    z-index: 2;
  }

  .photo-frame {
    width: 85px; /* Increased from 65px */
    height: 105px; /* Increased from 80px */
    border: 2px solid var(--accent);
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
  }

  .photo-placeholder {
    font-size: 8px;
    color: #64748b;
    font-weight: 700;
    text-align: center;
  }

  .photo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .card-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
  }

  .detail-line {
    display: flex;
    gap: 6px;
    align-items: baseline;
    font-size: 10.5px; /* Increased from 8px */
  }

  .detail-line .k {
    min-width: 64px;
    font-weight: 900;
    color: #64748b;
  }

  .detail-line .v {
    flex: 1;
    font-weight: 900;
    color: #0f172a;
    outline: none;
    padding: 1px 3px;
    border-radius: 4px;
  }

  .detail-line .v:focus {
    box-shadow: 0 0 0 2px rgba(245,158,11,.25);
    background: #fff7ed;
  }

  .scope-banner {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--accent);
    color: #fff;
    font-size: 7px;
    font-weight: 900;
    letter-spacing: 2px;
    text-align: center;
    padding: 3px 0;
    z-index: 3;
  }

  /* Card Back Preview */
  .zmc-card-back-preview {
    padding: 10px;
    box-sizing: border-box;
  }

  .back-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 12px 6px;
    border-bottom: 1px solid rgba(15,23,42,0.12);
    position: relative;
    z-index: 2;
  }

  .back-header .org-name {
    font-size: 11px; /* Increased from 8.5px */
    font-weight: 900;
    color: #000;
    text-align: center;
    line-height: 1.0;
  }

  .back-header .org-sub {
    font-size: 10px; /* Increased from 8px */
    color: #64748b;
    text-align: center;
  }

  .back-content {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 0;
    position: relative;
    z-index: 2;
  }

  .back-info {
    flex: 1;
  }

  .back-info .info-line {
    font-size: 10px; /* Increased from 8px */
    margin-bottom: 4px;
    color: #334155;
  }

  .back-info .info-line b { color: var(--accent); }

  .notice-box {
    font-size: 7px;
    color: #64748b;
    margin-top: 8px;
    padding: 5px;
    background: rgba(255,255,255,0.85);
    border: 1px dashed rgba(15,23,42,.25);
    border-radius: 4px;
    line-height: 1.3;
  }

  .qr-section {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .qr-section #cardBackQr {
    width: 85px; /* Increased from 65px */
    height: 85px; /* Increased from 65px */
    border: 2px solid var(--accent);
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .qr-hint {
    font-size: 7px;
    color: #64748b;
    font-weight: 700;
    margin-top: 3px;
  }

  .back-footer {
    display: flex;
    justify-content: space-between;
    font-size: 10px; /* Increased from 8px */
    color: var(--accent);
    font-weight: 700;
    border-top: 1px solid rgba(15,23,42,0.12);
    padding-top: 4px;
    position: relative;
    z-index: 2;
  }

  /* ============ TEMPLATE-IMAGE LOCAL/FOREIGN CARD (preview, editable) ============ */
  .zmc-tpl-front {
    position: relative;
    overflow: hidden;
    font-family: Arial, Helvetica, sans-serif;
  }
  .zmc-tpl-front.design-studio-active {
    background: #fff !important;
  }
  .zmc-tpl-front.design-studio-active > :not(#designStudioOverlay) {
    display: none !important;
  }
  #designStudioOverlay {
    position: absolute;
    inset: 0;
    z-index: 20;
    overflow: hidden;
    background-position: center;
    background-repeat: no-repeat;
    background-size: 100% 100%;
  }
  #designStudioCanvas {
    display: block;
    width: 100%;
    height: 100%;
  }
  /* 2027 supplied artwork with live, editable fields over the clear lower panel. */
  .zmc-tpl-front {
    background: url('{{ asset($isForeign ? 'card_foreign_2027.jpg' : 'card_local_2027.jpg') }}') center center / 100% 100% no-repeat !important;
  }
  .zmc-tpl-front .tpl-serial { display: none; }
  .zmc-tpl-front .tpl-photo { left: 4.5%; right: auto; top: 53%; width: 22%; height: 38%; border: 1px solid rgba(0,0,0,.22); border-radius: 2px; box-shadow: none; }
  .zmc-tpl-front .tpl-details { left: 29%; right: 4%; bottom: 22%; width: 66%; color: #111; text-shadow: none; }
  .zmc-tpl-front .tpl-row { font-size: 12px; line-height: 1.2; margin-bottom: 3px; }
  .zmc-tpl-front .tpl-lbl { min-width: 58px; color: #111; }
  .zmc-tpl-front .tpl-val { color: #111; }
  .zmc-tpl-front .tpl-name-row .tpl-val { font-size: 12px; }
  .zmc-tpl-front .tpl-validity { margin-top: 3px; font-size: 11px; color: #111; }
  .zmc-tpl-front [contenteditable="true"]:focus {
    outline: 2px solid rgba(255,193,7,0.85);
    background: rgba(255,255,255,0.85);
    border-radius: 2px;
  }

  /* Accreditation number sits on the bottom strip immediately after the
     Local Media / Foreign Media label in the supplied artwork. */
  .zmc-tpl-front .tpl-serial {
    position: absolute;
    left: 45%; right: auto; top: auto; bottom: 4.7%;
    width: 51%; height: 7%;
    display: flex; align-items: center; justify-content: center;
    color: #111; z-index: 5;
  }
  .zmc-tpl-front .tpl-serial span {
    writing-mode: horizontal-tb; transform: none;
    font-size: 10px; font-weight: 900; letter-spacing: .45px;
    white-space: nowrap; outline: none;
    text-shadow: none;
  }

  /* Photo on the right */
  .zmc-tpl-front .tpl-photo {
    position: absolute;
    left: 4.5%; right: auto; top: 43%;
    width: 22%; height: 38%;
    background: #fff;
    border: 1px solid rgba(0,0,0,0.18);
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    z-index: 5;
  }
  .zmc-tpl-front .tpl-photo img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
  .zmc-tpl-front .tpl-photo-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: #777; font-size: 10px; font-weight: 700; text-align: center;
  }

  /* Applicant details (lower-left) */
  .zmc-tpl-front .tpl-details {
    position: absolute;
    left: 29%; bottom: 22%;
    width: 66%;
    z-index: 5;
    color: #0a0a0a;
    text-shadow: 0 1px 1px rgba(255,255,255,0.6);
  }
  .zmc-tpl-front .tpl-row {
    display: flex; align-items: baseline;
    font-size: 11px; line-height: 1.15; margin-bottom: 2px;
    white-space: nowrap;
  }
  .zmc-tpl-front .tpl-lbl {
    min-width: 60px; font-weight: 900; letter-spacing: 0.4px;
  }
  .zmc-tpl-front .tpl-val {
    font-weight: 700; overflow: hidden; text-overflow: ellipsis; flex: 1;
  }
  .zmc-tpl-front .tpl-name-row .tpl-val { font-size: 10.5px; }
  .zmc-tpl-front .tpl-validity {
    margin-top: 2px; font-size: 10px; font-weight: 800;
  }

  /* ============ MEDIA-CARD TEMPLATE (preview, editable) ============ */
  .zmc-mc-front {
    position: relative;
    background:
      linear-gradient(180deg, rgba(255,255,255,0.98), rgba(245,238,250,0.98)),
      #ffffff !important;
    border: 2px solid #1a1a1a !important;
    border-radius: 12px !important;
    overflow: hidden;
    font-family: Arial, Helvetica, sans-serif;
  }
  .zmc-mc-front .mc-deco-bg {
    position: absolute; inset: 0; z-index: 1;
    background:
      radial-gradient(circle at 22% 72%, rgba(164, 111, 220, 0.20) 0, rgba(164, 111, 220, 0.20) 17%, transparent 18%),
      radial-gradient(circle at 35% 70%, rgba(185, 140, 230, 0.12) 0, rgba(185, 140, 230, 0.12) 20%, transparent 21%),
      linear-gradient(135deg, transparent 0 70%, #081d8f 70% 100%);
    pointer-events: none;
  }
  .zmc-mc-front .mc-deco-flag {
    position: absolute; right: 36px; top: 80px;
    width: 70px; height: 28px; z-index: 1;
    background:
      linear-gradient(180deg, rgba(255,255,255,0.15), rgba(255,255,255,0.15)),
      linear-gradient(90deg,
        #d8b55b 0 18%,
        #b15d5d 18% 36%,
        #c2c2c2 36% 54%,
        #9bc2d8 54% 72%,
        #b9a3d6 72% 100%);
    opacity: 0.75;
    clip-path: polygon(12% 0, 100% 0, 88% 100%, 0 100%);
  }

  .zmc-mc-front [contenteditable="true"]:focus {
    outline: 2px solid rgba(255,193,7,0.85);
    background: rgba(255,255,255,0.85);
    border-radius: 2px;
  }

  .zmc-mc-front .mc-left-strip {
    position: absolute; left: 8px; top: 28px;
    width: 22px; height: 150px;
    background: #081d8f; border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    z-index: 3; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.18);
  }
  .zmc-mc-front .mc-left-strip span {
    color: #fff; font-weight: 700; font-size: 11px; letter-spacing: 1px;
    writing-mode: vertical-rl; transform: rotate(180deg);
    outline: none;
  }

  .zmc-mc-front .mc-header {
    position: relative; z-index: 3; text-align: center; padding-top: 6px;
  }
  .zmc-mc-front .mc-header h1 {
    margin: 0; font-size: 14px; letter-spacing: 0.6px;
    font-weight: 800; color: #111827;
  }
  .zmc-mc-front .mc-header .mc-green { color: #84b547; }
  .zmc-mc-front .mc-logo-box {
    margin-top: 2px; display: flex; flex-direction: column; align-items: center; gap: 0;
  }
  .zmc-mc-front .mc-logo-box img { width: 54px; height: 54px; object-fit: contain; }
  .zmc-mc-front .mc-subtext {
    margin-top: -2px; font-size: 11px; font-weight: 800;
    color: #111827; letter-spacing: 0.4px;
  }

  .zmc-mc-front .mc-content {
    position: relative; z-index: 3;
    display: grid; grid-template-columns: 1fr 90px; gap: 10px;
    padding: 6px 14px 10px 38px;
    align-items: end;
  }
  .zmc-mc-front .mc-details { padding-top: 2px; }
  .zmc-mc-front .mc-row {
    display: flex; align-items: baseline; gap: 6px; margin-bottom: 3px; flex-wrap: nowrap;
  }
  .zmc-mc-front .mc-label {
    min-width: 56px; font-size: 11px; font-weight: 900; color: #111; letter-spacing: 0.4px;
  }
  .zmc-mc-front .mc-value {
    font-size: 12px; font-weight: 700; color: #111; line-height: 1.15;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    flex: 1;
  }
  .zmc-mc-front .mc-name-row .mc-value { font-size: 10.5px; }
  .zmc-mc-front .mc-validity {
    margin-top: 2px; font-size: 10.5px; font-weight: 800; color: #111;
  }

  .zmc-mc-front .mc-photo-section {
    display: flex; flex-direction: column; align-items: center; justify-content: end; gap: 4px;
  }
  .zmc-mc-front .mc-photo-box {
    width: 80px; height: 96px; background: #fff;
    border: 2px solid rgba(0,0,0,0.12); border-radius: 4px;
    overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.12);
  }
  .zmc-mc-front .mc-photo-box img { width: 100%; height: 100%; object-fit: cover; }

  /* ============ IMAGE-BASED LOCAL CARD (preview) ============ */
  .zmc-img-front {
    background: url('{{ asset('local_card_front.jpg') }}') center center / 100% 100% no-repeat !important;
    border: 1px solid rgba(0,0,0,0.2) !important;
    position: relative;
    font-family: 'Times New Roman', Times, serif;
    overflow: hidden;
  }
  /* Generic editable focus ring */
  .zmc-img-front [contenteditable="true"]:focus {
    outline: 2px solid rgba(255,193,7,0.8);
    background: rgba(255,255,255,0.85) !important;
    color: #0d3d0d;
    border-radius: 2px;
  }

  /* Vertical serial strip — positioned over the left edge of the image */
  .zmc-img-front .ovl-serial {
    position: absolute;
    top: 17%; left: 0.6%;
    width: 5.2%; height: 78%;
    background: linear-gradient(180deg, rgba(76,175,80,0.95) 0%, rgba(33,150,243,0.95) 100%);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    z-index: 5;
  }
  .zmc-img-front .ovl-serial span {
    writing-mode: vertical-rl; transform: rotate(180deg);
    font-size: 12px; font-weight: 900; letter-spacing: 3px;
    white-space: nowrap; outline: none;
  }

  /* Photo overlay on the right */
  .zmc-img-front .ovl-photo {
    position: absolute;
    right: 5%; top: 38%;
    width: 23%; height: 53%;
    background: #f4f4f4;
    border: 1px solid rgba(0,0,0,0.25);
    overflow: hidden;
    z-index: 5;
  }
  .zmc-img-front .ovl-photo img { width: 100%; height: 100%; object-fit: cover; }
  .zmc-img-front .ovl-photo-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-family: Arial, sans-serif; font-size: 11px;
    color: #777; font-weight: 700; text-align: center;
  }

  /* Detail value overlays — placed over the example values in the image */
  .zmc-img-front .ovl-name,
  .zmc-img-front .ovl-id,
  .zmc-img-front .ovl-org,
  .zmc-img-front .ovl-cat,
  .zmc-img-front .ovl-valid {
    position: absolute;
    z-index: 5;
    color: #0d3d0d;
    font-style: italic;
    font-weight: 700;
    font-size: 14px;
    background: rgba(248,243,222,0.96); /* opaque cream — covers underlying example text */
    padding: 1px 6px;
    border-radius: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.1;
  }
  .zmc-img-front .ovl-name  { left: 23%;   top: 55%;   width: 47%; }
  .zmc-img-front .ovl-id    { left: 19.5%; top: 64%;   width: 50%; }
  .zmc-img-front .ovl-org   { left: 23%;   top: 73%;   width: 47%; }
  .zmc-img-front .ovl-cat   { left: 28%;   top: 82%;   width: 42%; }
  .zmc-img-front .ovl-valid { left: 21%;   top: 91%;   width: 50%; font-size: 12px; }

  /* ============ NEW LOCAL CARD TEMPLATE (preview) ============ */
  .zmc-new-front {
    border: 1px solid rgba(0,0,0,0.18) !important;
    background:
      linear-gradient(135deg,
        rgba(255,236,179,0.55) 0%,
        rgba(232,245,233,0.55) 35%,
        rgba(255,255,255,0.85) 60%,
        rgba(255,205,210,0.45) 100%) !important;
    color: #0b3d0b;
    font-family: 'Times New Roman', Times, serif;
    overflow: hidden;
  }
  .zmc-new-front .nf-top-band {
    position: absolute; top: 0; left: 0; right: 0; height: 38px;
    background: linear-gradient(90deg,
      rgba(255,213,79,0.6) 0%,
      rgba(76,175,80,0.55) 50%,
      rgba(244,67,54,0.5) 100%);
    display: flex; align-items: center; justify-content: center;
    border-bottom: 1px solid rgba(0,0,0,0.15);
    z-index: 4;
  }
  .zmc-new-front .nf-title {
    font-weight: 900; font-size: 16px; letter-spacing: 0.6px;
    color: #0a1f3d; text-transform: uppercase;
    text-shadow: 0 1px 0 rgba(255,255,255,0.4);
  }
  .zmc-new-front .nf-title span { color: #1b5e20; }

  .zmc-new-front .nf-serial-strip {
    position: absolute; top: 38px; bottom: 0; left: 0; width: 26px;
    background: linear-gradient(180deg,
      rgba(76,175,80,0.85) 0%,
      rgba(33,150,243,0.85) 100%);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    z-index: 4;
  }
  .zmc-new-front .nf-serial-strip span {
    writing-mode: vertical-rl; transform: rotate(180deg);
    font-size: 12px; font-weight: 900; letter-spacing: 3px;
    white-space: nowrap;
  }

  .zmc-new-front .nf-watermark {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 70%; opacity: 0.07; z-index: 1; pointer-events: none;
  }
  .zmc-new-front .nf-watermark img { width: 100%; }

  .zmc-new-front .nf-center {
    position: absolute; top: 46px; left: 50%;
    transform: translateX(-50%);
    text-align: center; z-index: 2;
  }
  .zmc-new-front .nf-center img { width: 70px; height: 70px; object-fit: contain; }
  .zmc-new-front .nf-year {
    font-size: 13px; font-weight: 900; letter-spacing: 2px;
    color: #1b5e20; margin-top: 2px;
  }

  .zmc-new-front .nf-flag {
    position: absolute; right: -40px; top: 40px;
    width: 130px; transform: rotate(12deg);
    opacity: 0.18; z-index: 1; pointer-events: none;
  }
  .zmc-new-front .nf-flag img { width: 100%; }

  .zmc-new-front .nf-photo {
    position: absolute; right: 18px; top: 120px;
    width: 110px; height: 130px;
    border: 1px solid rgba(0,0,0,0.25);
    background: #f4f4f4;
    overflow: hidden; z-index: 5;
  }
  .zmc-new-front .nf-photo img { width: 100%; height: 100%; object-fit: cover; }
  .zmc-new-front .nf-photo-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-family: Arial, sans-serif; font-size: 11px;
    color: #777; font-weight: 700; text-align: center;
  }

  .zmc-new-front .nf-details {
    position: absolute; left: 40px; right: 145px; bottom: 18px;
    z-index: 5; color: #1b5e20;
    font-style: italic; font-weight: 700;
  }
  .zmc-new-front .nf-row {
    font-size: 14px; line-height: 1.3; margin-bottom: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .zmc-new-front .nf-lbl { display: inline-block; min-width: 70px; color: #1b5e20; }
  .zmc-new-front .nf-val { color: #0d3d0d; outline: none; }
  .zmc-new-front .nf-val[contenteditable="true"]:focus,
  .zmc-new-front .nf-val [contenteditable="true"]:focus {
    background: rgba(255,235,59,0.25);
    box-shadow: 0 0 0 2px rgba(255,193,7,0.45);
    border-radius: 2px;
  }

  /* ============ NEW LOCAL CARD BACK (preview) ============ */
  .zmc-new-back {
    background: #f7fafc !important;
    border: 1px solid #12335b !important;
    color: #16345b;
    font-family: Arial, Helvetica, sans-serif;
    padding: 0;
    box-sizing: border-box;
    position: relative;
  }
  .zmc-new-back .nb-top {
    height: 28%; min-height: 70px; background: #000; color: #fff;
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 22px 9px; border-bottom: 4px solid #e0b500;
  }
  .zmc-new-back .nb-brand {
    flex: 0 0 auto; white-space: nowrap;
    font-size: 19px !important; line-height: 1.08 !important;
    font-weight: 900 !important; letter-spacing: .4px;
  }
  .zmc-new-back .nb-brand span {
    color: #f1c40f; font-size: inherit !important; line-height: inherit !important;
    font-weight: inherit !important; letter-spacing: inherit;
  }
  .zmc-new-back .nb-tagline {
    max-width: 205px; text-align: right; color: #f4cd44;
    font-size: 10px; line-height: 1.14; font-weight: 800;
  }
  .zmc-new-back .nb-body-wrap {
    position: relative; height: 72%; padding: 10px 22px 8px;
  }
  .zmc-new-back .nb-copy { width: 58%; }
  .zmc-new-back .nb-contact {
    font-size: 9px; line-height: 1.18; margin-bottom: 3px;
  }
  .zmc-new-back .nb-legal {
    position: absolute; left: 22px; bottom: 24px; width: 58%; color: #16345b;
  }
  .zmc-new-back .nb-salutation {
    font-weight: 900; font-size: 10px; margin-bottom: 2px;
  }
  .zmc-new-back .nb-body {
    font-size: 7.5px; line-height: 1.2; max-height: 31px; overflow: hidden;
  }
  .zmc-new-back .nb-qr-box {
    position: absolute; right: 20px; top: 12px; width: 104px; height: 126px;
    background: #fff; border: 2px solid #d6a900; border-radius: 8px;
    padding: 7px; display: flex; flex-direction: column; align-items: center;
    justify-content: center; box-shadow: 0 5px 12px rgba(15,23,42,.12);
  }
  .zmc-new-back .nb-qr-box > div { width: 86px; height: 86px; }
  .zmc-new-back .nb-qr-box img,
  .zmc-new-back .nb-qr-box canvas { width: 86px !important; height: 86px !important; }
  .zmc-new-back .nb-qr-box span {
    display: block; margin-top: 3px; font-size: 8px; line-height: 1;
    color: #16345b; font-weight: 900; white-space: nowrap;
  }
  .zmc-new-back .nb-footer {
    position: absolute; left: 22px; bottom: 5px;
    font-size: 10px; font-weight: 900; color: #16345b;
  }

  /* Keep both faces in one exact frame. This must come after the generic
     card styles, which otherwise set the back face back to position:relative. */
  #mainCard3D > .zmc-3d-front,
  #mainCard3D > .zmc-3d-back {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
  }

  .print-actions{display:flex;flex-wrap:wrap;align-items:center;gap:.55rem}
  .print-actions .btn-warning{background:#f4c327;border-color:#d9a900;color:#151515;box-shadow:0 5px 14px rgba(217,169,0,.2)}
  .print-actions .btn-warning:hover,.print-actions .btn-warning:focus{background:#e5b20d;border-color:#c79600;color:#111}
  .card-preview-header{position:relative;isolation:isolate;overflow:hidden;padding:28px 32px;border-radius:30px;background:linear-gradient(118deg,#111111 0%,#1b5e20 54%,#2e7d32 100%);box-shadow:0 14px 30px rgba(0,0,0,.16);color:#fff}
  .card-preview-header::after{content:"";position:absolute;z-index:-1;right:-70px;top:-120px;width:390px;height:310px;border-radius:50%;background:rgba(76,175,80,.52);box-shadow:0 0 0 28px rgba(245,197,24,.16),0 0 0 58px rgba(211,47,47,.08)}
  .card-preview-header h4{color:#fff !important;font-size:clamp(28px,3vw,48px) !important;line-height:1.05}
  .card-preview-header .text-muted{color:rgba(255,255,255,.76) !important}
  .card-preview-header .badge{background:rgba(245,197,24,.14) !important;border-color:#f5c518 !important;color:#f5c518 !important}
  .card-preview-header .btn{background:#fff;color:#172033;border-color:#d7e1e7}
  @media (max-width: 767.98px){.print-actions{align-items:stretch}.print-actions .btn{flex:1 1 45%}}
</style>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
@if($designTemplate)
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
@endif
<script>
  (function(){
    const designStudioLayout = @json($designStudioLayout);
    const designStudioData = @json($designStudioData);
    const designStudioPhotoUrl = @json($photoUrl);
    let designStudioCanvas = null;
    const designStudioKeyMap = {
      name: 'full_name',
      organisation: 'media_organisation',
      accreditation_id: 'accreditation_number',
      valid_from: 'issue_date',
      valid_to: 'expiry_date',
    };
    window.designStudioData = designStudioData;

    function designStudioTextValue(key) {
      return String(designStudioData[key] ?? '—');
    }

    function refreshDesignStudioValues() {
      if (!designStudioCanvas) return;
      designStudioCanvas.getObjects().forEach((obj) => {
        if (!obj.fieldKey) return;
        if (obj.type === 'i-text' || obj.type === 'text' || obj.type === 'textbox') {
          obj.set('text', designStudioTextValue(obj.fieldKey));
        }
      });
      designStudioCanvas.requestRenderAll();
    }

    function renderDesignStudioPreview() {
      const canvasEl = document.getElementById('designStudioCanvas');
      if (!canvasEl || !designStudioLayout || !window.fabric) return;

      const layoutWidth = 1012;
      const layoutHeight = 638;
      const overlay = document.getElementById('designStudioOverlay');
      const canvasWidth = overlay?.clientWidth || 440;
      const canvasHeight = overlay?.clientHeight || 280;
      const scaleX = canvasWidth / layoutWidth;
      const scaleY = canvasHeight / layoutHeight;
      canvasEl.width = canvasWidth;
      canvasEl.height = canvasHeight;
      designStudioCanvas = new fabric.StaticCanvas(canvasEl, {
        width: canvasWidth,
        height: canvasHeight,
        // The saved artwork is the DOM background; keep Fabric transparent so
        // it does not paint an opaque white layer over the card design.
        backgroundColor: 'transparent',
      });
      designStudioCanvas.setViewportTransform([scaleX, 0, 0, scaleY, 0, 0]);

      designStudioCanvas.loadFromJSON(designStudioLayout, function () {
        // Fabric restores the editor's white background from the JSON. The
        // production artwork is supplied by the overlay's background image,
        // so the canvas must remain transparent.
        designStudioCanvas.setBackgroundColor('transparent', function () {});
        // loadFromJSON can restore the editor viewport; re-apply the display
        // scale so all saved objects stay aligned with the visible card.
        designStudioCanvas.setViewportTransform([scaleX, 0, 0, scaleY, 0, 0]);
        const objects = designStudioCanvas.getObjects().slice();
        objects.forEach((obj) => {
          if (obj.fieldKey && (obj.type === 'i-text' || obj.type === 'text' || obj.type === 'textbox')) {
            obj.set('text', designStudioTextValue(obj.fieldKey));
          }

          if (obj.fieldKey === 'applicant_photo' && designStudioPhotoUrl) {
            const left = obj.left || 0;
            const top = obj.top || 0;
            const width = obj.getScaledWidth ? obj.getScaledWidth() : (obj.width || 180);
            const height = obj.getScaledHeight ? obj.getScaledHeight() : (obj.height || 220);
            const angle = obj.angle || 0;
            designStudioCanvas.remove(obj);
            fabric.Image.fromURL(designStudioPhotoUrl, function (photo) {
              // Keep the applicant's original aspect ratio. Fit the complete
              // photo inside the saved frame and center any remaining space;
              // do not stretch or crop faces when the source proportions differ.
              const sourceWidth = photo.width || width;
              const sourceHeight = photo.height || height;
              // Give the photo a little more visual presence inside the
              // saved frame while still keeping the complete image visible.
              const fitScale = Math.min(width / sourceWidth, height / sourceHeight) * 1.18;
              const renderedWidth = sourceWidth * fitScale;
              const renderedHeight = sourceHeight * fitScale;
              photo.set({
                left: left + ((width - renderedWidth) / 2),
                top: top + ((height - renderedHeight) / 2),
                scaleX: fitScale,
                scaleY: fitScale,
                angle,
                selectable: false,
                evented: false,
                originX: 'left',
                originY: 'top',
              });
              designStudioCanvas.add(photo);
              designStudioCanvas.requestRenderAll();
            }, { crossOrigin: 'anonymous' });
          }

          obj.set({ selectable: false, evented: false });
        });
        designStudioCanvas.requestRenderAll();
      });

      window.refreshDesignStudioValues = refreshDesignStudioValues;
    }

    if (designStudioLayout) {
      if (document.readyState === 'complete') renderDesignStudioPreview();
      else window.addEventListener('load', renderDesignStudioPreview, { once: true });
    }

    const bindToHidden = {
      qr_value: 'p_qr_value',
    };

    function setHidden(key, val){
      const id = bindToHidden[key];
      if(!id) return;
      const el = document.getElementById(id);
      if(el) el.value = String(val ?? '');
    }

    function updateScopeBanner(scope) {
      const banner = document.getElementById('scopeBanner');
      if (banner) banner.textContent = scope + ' MEDIA';

      const front = document.getElementById('cardPreview');
      const back  = document.getElementById('cardBackPreview');
      const isForeign = String(scope || '').toUpperCase() === 'FOREIGN';

      if(front){
        front.classList.toggle('is-foreign', isForeign);
        front.classList.toggle('is-local', !isForeign);
      }
      if(back){
        back.classList.toggle('is-foreign', isForeign);
        back.classList.toggle('is-local', !isForeign);
      }
    }

    function updateCardTemplate(template) {
      const front = document.getElementById('cardPreview');
      const back = document.getElementById('cardBackPreview');

      // Remove all template classes
      const templates = ['template-default', 'template-zmc_2027', 'template-modern_dark', 'template-eco_green', 'template-royal_gold', 'template-ocean_blue', 'template-crimson_pro'];

      [front, back].forEach(el => {
        if (!el) return;
        templates.forEach(t => el.classList.remove(t));
        el.classList.add('template-' + template);
      });
    }

    document.querySelectorAll('[data-bind]').forEach(inp => {
      const key = inp.getAttribute('data-bind');
      const handler = () => {
        const v = inp.value;
        setHidden(key, v);
        document.querySelectorAll('[data-edit="'+key+'"]').forEach(tgt => tgt.textContent = v);
        const designKey = designStudioKeyMap[key] || key;
        if (window.designStudioData && Object.prototype.hasOwnProperty.call(window.designStudioData, designKey)) {
          window.designStudioData[designKey] = v;
          window.refreshDesignStudioValues?.();
        }
        if (key === 'accreditation_id') {
          const verifyUrl = 'https://zmc.org.zw/verify-accreditation?number=' + encodeURIComponent(v);
          setHidden('qr_value', verifyUrl);
        }
        if (key === 'scope') updateScopeBanner(v);
        renderBackQr();
      };
      inp.addEventListener('input', handler);
      inp.addEventListener('change', handler);
    });

    document.querySelectorAll('[data-edit]').forEach(sp => {
      const key = sp.getAttribute('data-edit');
      sp.addEventListener('input', () => {
        setHidden(key, sp.textContent);
        const inp = document.querySelector('[data-bind="'+key+'"]');
        if(inp && inp.value !== sp.textContent) inp.value = sp.textContent;
        renderBackQr();
      });
    });

    document.getElementById('btnReset')?.addEventListener('click', () => {
      window.location.reload();
    });

    function renderBackQr(){
      const host = document.getElementById('cardBackQr');
      if(!host || typeof QRCode === 'undefined') return;
      host.innerHTML='';
      const v = document.getElementById('p_qr_value')?.value || '';
      try{
        new QRCode(host, {text:String(v), width:86, height:86, correctLevel: QRCode.CorrectLevel.M});
      }catch(e){}
    }

    function flipCard() {
      document.getElementById('mainCard3D').classList.toggle('is-flipped');
    }

    window.addEventListener('load', () => {
      // initialize QR on back
      refreshQr();
    });

    function refreshQr() {
      const qrEl = document.getElementById('cardBackQr');
      if(!qrEl) return;
      qrEl.innerHTML = '';
      new QRCode(qrEl, {
        text: document.getElementById('p_qr_value').value,
        width: 86,
        height: 86
      });
    }

    // Attach flipCard to window so it's globally callable if needed via onclick
    window.flipCard = flipCard;
    window.refreshQr = refreshQr;

    updateScopeBanner(document.querySelector('[data-bind="scope"]')?.value || 'LOCAL');
    updateCardTemplate('{{ $template }}');
    renderBackQr();
  })();
</script>
@endpush

@endsection
