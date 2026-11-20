@extends('layouts.portal')
@section('title', 'Certificate Preview')

@section('content')
@php
  $fd  = is_array($application->form_data)
       ? $application->form_data
       : (json_decode($application->form_data ?? '[]', true) ?: []);
  $ap1 = (isset($fd['ap1']) && is_array($fd['ap1'])) ? $fd['ap1'] : $fd;

  // Resolve organisation name from multiple possible keys, ignoring blank form fields.
  $orgName = '—';
  foreach ([
      $ap1['ap1_org_name'] ?? null,
      $ap1['org_name'] ?? null,
      $ap1['organization_name'] ?? null,
      $ap1['media_house_name'] ?? null,
      $application->applicant?->name,
  ] as $candidate) {
      if (is_scalar($candidate) && trim((string) $candidate) !== '') {
          $orgName = trim((string) $candidate);
          break;
      }
  }

  // Resolve category
  $category = $ap1['ap1_category']
           ?? $ap1['category']
           ?? $ap1['mass_media_category']
           ?? $application->media_house_category_code
           ?? 'Mass Media Service';

  // Certificate number — use existing record if already generated
  $certNo = $application->registrationRecord?->registration_no
         ?? $application->receipt_number
         ?? $application->generateFormattedNumber();

  // QR value
  $qrValue = ($application->registrationRecord?->qr_token)
           ? route('public.verify', $application->registrationRecord->qr_token)
           : route('public.verify', 'invalid');

  // Merge saved edits
  $saved = (array) ($application->form_data['certificate_edits'] ?? []);

  $payload = array_merge([
    'org_name'          => $orgName,
    'category'          => $category,
    'reg_no'            => $certNo,
    'issue_date'        => now()->format('Y-m-d'),
    'valid_until'       => now()->addYears(5)->format('Y-m-d'),
    'right_sign_title'  => 'CHIEF EXECUTIVE OFFICER',
    'certificate_title' => 'CERTIFICATE',
    'certificate_sub'   => 'Of Registration For A Mass Media Service',
    'qr_value'          => $qrValue,
  ], $saved);
  if (trim((string) ($payload['org_name'] ?? '')) === '') {
      $payload['org_name'] = $orgName;
  }
  if (trim((string) ($payload['category'] ?? '')) === '') {
      $payload['category'] = $category;
  }
  $categoryMap = \App\Models\Application::massMediaCategories();
  $categoryCode = trim((string) ($payload['category'] ?? ''));
  if (isset($categoryMap[$categoryCode])) {
      $payload['category'] = $categoryMap[$categoryCode];
  }
  $displayIssueDate = $payload['issue_date'];
  $displayValidUntil = $payload['valid_until'];
  try { $displayIssueDate = \Illuminate\Support\Carbon::parse($payload['issue_date'])->format('d/m/Y'); } catch (\Throwable $e) {}
  try { $displayValidUntil = \Illuminate\Support\Carbon::parse($payload['valid_until'])->format('d/m/Y'); } catch (\Throwable $e) {}
@endphp

<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Certificate Preview</h1>
      <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
        <i class="ri-information-line me-1"></i>
        Review and edit the certificate details before printing.
        <span class="badge bg-light text-dark border ms-2">{{ $application->reference }}</span>
      </div>
    </div>
    <a href="{{ route('staff.production.certificates') }}" class="btn btn-white border shadow-sm btn-sm px-3">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>

  <div class="row g-4">

    {{-- LEFT: Edit Form --}}
    <div class="col-12 col-lg-4">
      <div class="zmc-card">
        <h2 class="fw-bold mb-3"><i class="ri-edit-2-line me-2" style="color:var(--zmc-accent-dark)"></i>Edit Fields</h2>

        <form method="POST"
              action="{{ route('staff.production.applications.certificate.print', $application) }}"
              target="_blank"
              id="certForm">
          @csrf
          {{-- Fixed hidden fields --}}
          <input type="hidden" name="template"                                    value="mass_media_dust_pink">
          <input type="hidden" name="certificate_payload[certificate_title]"      value="CERTIFICATE">
          <input type="hidden" name="certificate_payload[certificate_sub]"        value="Of Registration For A Mass Media Service">
          <input type="hidden" name="certificate_payload[qr_value]"               value="{{ $payload['qr_value'] }}">

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold text-muted">Organisation / Recipient Name</label>
              <input type="text"
                     name="certificate_payload[org_name]"
                     class="form-control zmc-input"
                     data-preview="view_org_name"
                     value="{{ $payload['org_name'] }}">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-muted">Category</label>
              <input type="text"
                     name="certificate_payload[category]"
                     class="form-control zmc-input"
                     data-preview="view_category"
                     value="{{ $payload['category'] }}">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-muted">Certificate / Registration No.</label>
              <input type="text"
                     name="certificate_payload[reg_no]"
                     class="form-control zmc-input"
                     data-preview="view_reg_no"
                     value="{{ $payload['reg_no'] }}">
            </div>

            <div class="col-6">
              <label class="form-label small fw-bold text-muted">Issue Date</label>
              <input type="date"
                     name="certificate_payload[issue_date]"
                     class="form-control zmc-input"
                     data-preview="view_issue_date"
                     value="{{ $payload['issue_date'] }}">
            </div>

            <div class="col-6">
              <label class="form-label small fw-bold text-muted">Valid Until</label>
              <input type="date"
                     name="certificate_payload[valid_until]"
                     class="form-control zmc-input"
                     data-preview="view_valid_until"
                     value="{{ $payload['valid_until'] }}">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-muted">Right Signatory Title</label>
              <input type="text"
                     name="certificate_payload[right_sign_title]"
                     class="form-control zmc-input"
                     data-preview="view_right_sign_title"
                     value="{{ $payload['right_sign_title'] }}">
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-dark fw-bold flex-fill">
              <i class="ri-printer-line me-1"></i> Generate &amp; Print
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
              <i class="ri-refresh-line"></i>
            </button>
          </div>
          <div class="form-text mt-2 text-muted">
            Opens a print-ready page in a new tab. Use browser print (Ctrl+P / Cmd+P).
          </div>
        </form>
      </div>
    </div>

    {{-- RIGHT: Live Preview --}}
    <div class="col-12 col-lg-8">
      <div class="zmc-card p-0 overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <span class="fw-bold small"><i class="ri-eye-line me-2" style="color:var(--zmc-accent-dark)"></i>Live Preview</span>
          <span class="badge bg-light text-dark border">A4 Portrait</span>
        </div>

        {{-- Scale the A4 preview to fit the column --}}
        <div style="padding:16px; background:#d8d4c8;">
          <div id="certPreviewWrap" style="width:100%; max-width:595px; margin:0 auto;">
            <div class="cert-paper" id="certPreview">
              <div class="cert-inner">

                <img src="{{ asset('zmc_logo.png') }}" class="cert-watermark" alt="">
                <div class="cert-header-text"><span>ZIMBABWE MEDIA COMMISSION</span></div>
                <div class="cert-ornament"></div>
                <div class="cert-title">CERTIFICATE</div>
                <div class="cert-ornament"></div>
                <div class="cert-subtitle">Of Registration For A Mass Media Service</div>

                <div class="cert-issued">
                  <div class="cert-label">Issued To</div>
                  <div class="cert-value cert-strong" id="view_org_name">{{ $payload['org_name'] }}</div>
                </div>
                <div class="cert-issued">
                  <div class="cert-label">Category:</div>
                  <div class="cert-value" id="view_category">{{ $payload['category'] }}</div>
                </div>
                <div class="cert-validity">
                  <div><strong>VALID FROM:</strong><span id="view_issue_date">{{ $displayIssueDate }}</span></div>
                  <div><strong>TO:</strong><span id="view_valid_until">{{ $displayValidUntil }}</span></div>
                </div>
                <div class="cert-signatures">
                  <div><div class="cert-sign-line"></div><div class="cert-sign-title">COMMISSION CHAIRPERSON</div><div class="cert-sign-date">DATE: ........../........../20........</div></div>
                  <div><div class="cert-sign-line"></div><div class="cert-sign-title" id="view_right_sign_title">{{ $payload['right_sign_title'] }}</div><div class="cert-sign-date">DATE: ........../........../20........</div></div>
                </div>
                <div class="cert-footer">
                  <svg class="cert-flag" viewBox="0 0 300 180" xmlns="http://www.w3.org/2000/svg" aria-label="Zimbabwe flag">
                    <polygon points="0,0 110,90 0,180" fill="#fff"/><polygon points="0,8 98,90 0,172" fill="#000"/><polygon points="0,16 86,90 0,164" fill="#f4c430"/><polygon points="0,24 74,90 0,156" fill="#cf1020"/><polygon points="0,32 62,90 0,148" fill="#00682d"/>
                    <rect x="62" y="0" width="238" height="30" fill="#00682d"/><rect x="62" y="30" width="238" height="20" fill="#f4c430"/><rect x="62" y="50" width="238" height="20" fill="#cf1020"/><rect x="62" y="70" width="238" height="20" fill="#000"/><rect x="62" y="90" width="238" height="20" fill="#000"/><rect x="62" y="110" width="238" height="20" fill="#cf1020"/><rect x="62" y="130" width="238" height="20" fill="#f4c430"/><rect x="62" y="150" width="238" height="30" fill="#00682d"/>
                    <polygon points="35,80 39,92 52,92 41,100 45,112 35,104 25,112 29,100 18,92 31,92" fill="#cf1020" stroke="#f4c430" stroke-width="2"/>
                  </svg>
                  <img src="{{ asset('zmc_logo.png') }}" alt="ZMC Logo">
                </div>
                <div class="certificate-qr" aria-label="Certificate verification QR code">
                  <div id="certPreviewQr"></div>
                  <span>SCAN TO VERIFY</span>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
/* ── Paper ─────────────────────────────────────────────────────────── */
.cert-paper {
  background: #fff0f5;
  border: 3px solid #9c7c38;
  box-shadow: 0 12px 40px rgba(0,0,0,.18);
  width: 100%;
  aspect-ratio: 210 / 297;
  overflow: hidden;
  position: relative;
}
.cert-inner {
  padding: 5%;
  height: 100%;
  box-sizing: border-box;
  position: relative;
  display: flex;
  flex-direction: column;
}

/* ── Watermark ──────────────────────────────────────────────────────── */
.cert-watermark {
  position: absolute;
  width: 55%;
  opacity: .07;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
  z-index: 0;
}

/* ── Header ─────────────────────────────────────────────────────────── */
.cert-header {
  display: flex;
  align-items: center;
  gap: 12px;
  justify-content: center;
  position: relative;
  z-index: 1;
}
.cert-header-logo { width: 60px; height: auto; }
.cert-header-text {
  font-weight: 900;
  letter-spacing: .6px;
  font-size: 18px;
  text-align: center;
}

/* ── Ornament ───────────────────────────────────────────────────────── */
.cert-ornament {
  height: 2px;
  width: 60%;
  margin: 12px auto;
  background: linear-gradient(to right, transparent, #b08a3c, transparent);
  position: relative;
  z-index: 1;
}

/* ── Titles ─────────────────────────────────────────────────────────── */
.cert-title {
  text-align: center;
  font-size: clamp(28px, 6vw, 52px);
  font-family: Georgia, serif;
  color: #1a3a5c;
  line-height: 1;
  position: relative;
  z-index: 1;
}
.cert-subtitle {
  text-align: center;
  font-size: clamp(10px, 1.8vw, 14px);
  font-style: italic;
  color: #1a3a5c;
  margin-top: 4px;
  position: relative;
  z-index: 1;
}

/* ── Certificate Number ─────────────────────────────────────────────── */
.cert-number {
  text-align: center;
  margin-top: 8px;
  font-size: clamp(9px, 1.5vw, 13px);
  font-weight: 700;
  position: relative;
  z-index: 1;
}
.cert-number span {
  padding: 3px 10px;
  border: 1px solid rgba(0,0,0,.2);
  border-radius: 999px;
  margin-left: 4px;
}

/* ── Sections ───────────────────────────────────────────────────────── */
.cert-section {
  margin-top: 8px;
  text-align: center;
  position: relative;
  z-index: 1;
}
.cert-label {
  font-size: clamp(8px, 1.3vw, 12px);
  font-weight: 700;
  text-transform: uppercase;
  color: #1a3a5c;
}
.cert-value {
  margin-top: 3px;
  font-size: clamp(11px, 2vw, 18px);
  color: #1a3a5c;
}
.cert-strong { font-weight: 900; }

/* ── Statement ──────────────────────────────────────────────────────── */
.cert-statement {
  margin: 8px auto;
  max-width: 85%;
  text-align: center;
  font-size: clamp(7px, 1.2vw, 11px);
  line-height: 1.4;
  color: #333;
  position: relative;
  z-index: 1;
}

/* ── Validity ───────────────────────────────────────────────────────── */
.cert-validity {
  margin-top: 8px;
  display: flex;
  justify-content: center;
  gap: 40px;
  font-size: clamp(8px, 1.3vw, 12px);
  font-weight: 700;
  color: #1a3a5c;
  position: relative;
  z-index: 1;
}

/* ── Footer ─────────────────────────────────────────────────────────── */
.cert-footer {
  margin-top: auto;
  padding-top: 8px;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  position: relative;
  z-index: 1;
}
.cert-flag {
  width: clamp(40px, 12%, 80px);
  height: auto;
  display: block;
}
.cert-sign-block { width: 40%; }
.cert-sign-line {
  height: 1px;
  background: rgba(26,58,92,.5);
  margin-bottom: 4px;
}
.cert-sign-title {
  font-size: clamp(7px, 1.2vw, 11px);
  font-weight: 700;
  color: #1a3a5c;
}
.cert-qr {
  width: clamp(50px, 10%, 80px);
  height: clamp(50px, 10%, 80px);
  display: block;
}
#certPreviewQr { width: 100%; height: 100%; }

/* Keep the browser preview identical to the A4 print template. */
.cert-paper {
  background: #fbf8ee;
  border: 2px solid #c9a86c;
  box-shadow: 0 12px 36px rgba(0,0,0,.16);
  aspect-ratio: 210 / 297;
}
.cert-inner { padding: 5.2% 9%; }
.cert-watermark { width: 62%; top: 67%; opacity: .085; }
.cert-header-text {
  width: 100%;
  margin: 0;
  font-family: Georgia, 'Times New Roman', serif;
  font-size: clamp(13px, 3.3vw, 22px);
  line-height: 1.15;
  letter-spacing: .8px;
  font-weight: 700;
  color: #1b3855;
  text-align: center;
}
.cert-ornament {
  width: 100%;
  height: 16px;
  margin: 7px auto;
  background: linear-gradient(to right, transparent 0%, #c9a86c 16%, #c9a86c 36%, transparent 50%, #c9a86c 64%, #c9a86c 84%, transparent 100%);
  opacity: .9;
}
.cert-title {
  font-family: Georgia, 'Times New Roman', serif;
  font-size: clamp(29px, 7vw, 54px);
  line-height: .95;
  letter-spacing: 2px;
  color: #1b3855;
  font-weight: 700;
}
.cert-subtitle {
  margin-top: 8px;
  font-family: Georgia, 'Times New Roman', serif;
  font-size: clamp(10px, 2.1vw, 16px);
  line-height: 1.2;
  font-style: normal;
  font-weight: 700;
  color: #1b3855;
}
.cert-issued {
  width: 100%;
  margin-top: 20px;
  text-align: center;
}
.cert-label {
  font-size: clamp(10px, 1.8vw, 14px);
  font-weight: 700;
  color: #1b3855;
  text-transform: uppercase;
}
.cert-value {
  width: 82%;
  min-height: 22px;
  margin: 10px auto 0;
  border-bottom: 1px solid rgba(201,168,108,.95);
  font-size: clamp(11px, 2.1vw, 17px);
  line-height: 1.1;
  color: #1b3855;
  text-align: center;
  text-transform: uppercase;
}
.cert-validity {
  width: 82%;
  display: flex;
  justify-content: space-between;
  gap: 14px;
  margin: 22px auto 0;
  color: #1b3855;
  font-size: clamp(9px, 1.6vw, 13px);
  text-align: center;
}
.cert-validity > div { width: 50%; }
.cert-validity span {
  display: block;
  min-height: 18px;
  margin-top: 7px;
  border-bottom: 1px solid rgba(201,168,108,.95);
}
.cert-signatures {
  width: 84%;
  display: flex;
  justify-content: space-between;
  gap: 18px;
  margin: auto auto 0;
  color: #1b3855;
  text-align: center;
}
.cert-signatures > div { width: 50%; }
.cert-sign-line { height: 1px; margin-bottom: 6px; background: rgba(201,168,108,.95); }
.cert-sign-title { min-height: 24px; font-size: clamp(8px, 1.35vw, 11px); line-height: 1.1; font-weight: 700; }
.cert-sign-date { margin-top: 8px; font-size: clamp(8px, 1.25vw, 10px); font-weight: 700; }
.cert-footer {
  width: 84%;
  margin-top: 14px;
  padding-top: 0;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}
.cert-flag { width: 24%; height: auto; }
.cert-footer > img { width: 22%; height: auto; object-fit: contain; }

/* Use the supplied official certificate artwork as the fixed background. */
.cert-paper {
  background-color: #f3f1e8;
  background-image: url('{{ asset('certificate_reference.png') }}');
  background-repeat: no-repeat;
  background-position: center center;
  background-size: 100% 100%;
  border: 0;
  box-shadow: 0 12px 36px rgba(0,0,0,.16);
}
.cert-inner { display: block; position: relative; padding: 0; }
.cert-watermark, .cert-header-text, .cert-ornament, .cert-title,
.cert-subtitle, .cert-label, .cert-signatures, .cert-footer { display: none; }
.cert-issued {
  position: absolute;
  top: 44.5%;
  left: 12%;
  width: 76%;
  margin: 0;
}
.cert-value {
  width: 100%;
  min-height: 0;
  margin: 0;
  border: 0;
  color: #263b4f;
  font-size: clamp(11px, 2.2vw, 17px);
  line-height: 1.1;
  text-transform: uppercase;
}
.cert-issued + .cert-issued { top: 52.2%; left: 25%; width: 50%; }
.cert-issued + .cert-issued .cert-value { font-weight: 800; }
.cert-validity {
  position: absolute;
  top: 59.7%;
  left: 12%;
  width: 76%;
  height: 22px;
  margin: 0;
  padding: 0;
  color: #263b4f;
  font-size: clamp(9px, 1.6vw, 13px);
  line-height: 1.1;
  display: flex;
  align-items: flex-start;
}
.cert-validity > div { width: 50%; text-align: center; }
.cert-validity strong { display: none; }
.cert-validity > div { position: relative; height: 22px; }
.cert-validity span {
  display: block;
  position: absolute;
  top: -2px;
  left: 45%;
  width: 72%;
  margin: 0;
  padding: 1px 0;
  border: 0;
  min-height: 0;
  text-align: center;
  white-space: nowrap;
  z-index: 2;
}
.cert-validity > div:last-child span { left: 25%; width: 100%; }
.certificate-qr {
  position: absolute;
  z-index: 6;
  left: 50%;
  bottom: 3.2%;
  transform: translateX(-50%);
  width: 19%;
  max-width: 82px;
  padding: 4px 4px 2px;
  border: 1px solid #c9a86c;
  border-radius: 3px;
  background: rgba(255,255,255,.98);
  text-align: center;
}
.certificate-qr #certPreviewQr,
.certificate-qr #certPreviewQr img,
.certificate-qr #certPreviewQr canvas { width: 100% !important; height: auto !important; aspect-ratio: 1; display: block; }
.certificate-qr span { display: block; margin-top: 2px; color: #1b3855; font-size: 7px; line-height: 1; font-weight: 800; white-space: nowrap; }
</style>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  // Live sync: form inputs → preview spans
  document.querySelectorAll('[data-preview]').forEach(function (input) {
    const targetId = input.getAttribute('data-preview');
    const target   = document.getElementById(targetId);
    if (!target) return;

    function sync() {
      if ((targetId === 'view_issue_date' || targetId === 'view_valid_until') && input.value) {
        const parts = input.value.split('-');
        target.textContent = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : input.value;
      } else {
        target.textContent = input.value || '—';
      }
    }
    input.addEventListener('input',  sync);
    input.addEventListener('change', sync);
  });

  // Generate QR code
  const qrEl = document.getElementById('certPreviewQr');
  if (qrEl && typeof QRCode !== 'undefined') {
    try {
      new QRCode(qrEl, {
        text: '{{ $payload['qr_value'] }}',
        width: 80,
        height: 80,
        correctLevel: QRCode.CorrectLevel.M
      });
    } catch (e) {}
  }
});
</script>
@endpush

@endsection
