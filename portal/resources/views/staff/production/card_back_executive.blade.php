<!doctype html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>Print Card (Back) - {{ $application->reference }}</title>
  <style>
    @php
        $primaryColor = $template_data['primary_color'] ?? '#1a237e';
        $secondaryColor = $template_data['secondary_color'] ?? '#f5c518';
    @endphp

    @page { size: 85.6mm 53.98mm; margin: 0; }
    html, body { height: 100%; margin: 0; }
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Segoe UI', Arial, sans-serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: linear-gradient(160deg, #ffffff 0%, #f8fafc 50%, {{ $primaryColor }}08 100%);
    }

    .accent-bar {
      position: absolute; top: 0; left: 0; bottom: 0; width: 8mm;
      background: linear-gradient(180deg, {{ $primaryColor }}, {{ $primaryColor }}cc);
    }
    .accent-bar::after {
      content: ''; position: absolute; top: 0; right: -4mm; bottom: 0; width: 4mm;
      background: {{ $secondaryColor }};
    }

    .watermark { position: absolute; top: 50%; left: 55%; transform: translate(-50%, -50%); width: 30mm; opacity: 0.05; }

    .content {
      position: relative; z-index: 2;
      padding: 4mm 5mm 4mm 14mm;
      height: 100%; box-sizing: border-box;
      display: flex; flex-direction: column;
    }

    .header {
      display: flex; align-items: center; gap: 2mm;
      padding-bottom: 2mm; border-bottom: 0.5px solid {{ $primaryColor }}22; margin-bottom: 2mm;
    }
    .header img { width: 8mm; height: 8mm; object-fit: contain; }
    .header-text { font-size: 6pt; font-weight: 800; color: {{ $primaryColor }}; letter-spacing: 0.5px; }
    .header-sub { font-size: 5pt; color: #64748b; margin-top: 0.3mm; }

    .body-row { display: flex; gap: 4mm; flex: 1; }

    .info-col { flex: 1; }
    .info-line { font-size: 7pt; margin-bottom: 1.5mm; color: #334155; }
    .info-line b { color: {{ $primaryColor }}; font-weight: 700; }

    .notice {
      font-size: 5.5pt; color: #64748b; margin-top: auto;
      padding: 2mm; border: 0.5px dashed {{ $secondaryColor }};
      border-radius: 1mm; line-height: 1.4;
    }

    .qr-col { display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .qr-box { width: 18mm; height: 18mm; border: 1.5px solid {{ $primaryColor }}44; background: #fff; display: flex; align-items: center; justify-content: center; }
    .qr-hint { font-size: 5pt; color: #64748b; font-weight: 700; margin-top: 1mm; }

    .footer {
      display: flex; justify-content: space-between; font-size: 5pt; color: {{ $primaryColor }}; font-weight: 700;
      border-top: 0.5px solid {{ $primaryColor }}22; padding-top: 1.5mm; margin-top: 2mm;
    }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="accent-bar"></div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">

    <div class="content">
      <div class="header">
        <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
        <div>
          <div class="header-text">ZIMBABWE MEDIA COMMISSION</div>
          <div class="header-sub">Accreditation Card (Back)</div>
        </div>
      </div>

      <div class="body-row">
        <div class="info-col">
          <div class="info-line"><b>Address:</b> {{ config('organization.registered_address') }}</div>
          <div class="info-line"><b>Tel:</b> {{ config('organization.legacy_phone') }} or 253572/5/6</div>
          <div class="info-line"><b>Email:</b> {{ config('organization.accreditation_email') }}</div>
          <div class="info-line"><b>Designation:</b> {{ $payload['designation'] ?? 'Media Practitioner' }}</div>
          <div class="info-line"><b>Acc No:</b> {{ $payload['accreditation_number'] ?? ($payload['reg_no'] ?? ($payload['ref'] ?? '---')) }}</div>
          <div class="notice">This card remains the property of the Zimbabwe Media Commission. If found, please return to ZMC.</div>
        </div>
        <div class="qr-col">
          <div class="qr-box"><div id="cardBackQr"></div></div>
          <div class="qr-hint">Scan to verify</div>
        </div>
      </div>

      <div class="footer">
        <span>{{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</span>
        <span>Tel: {{ config('organization.legacy_phone') }}</span>
        <span>{{ $payload['ref'] ?? $application->reference }}</span>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function(){
      try { new QRCode(document.getElementById('cardBackQr'), { text: @json($payload['qr_value'] ?? ''), width: 60, height: 60, correctLevel: QRCode.CorrectLevel.M }); } catch(e) {}
      window.addEventListener('load', () => setTimeout(() => window.print(), 200));
    })();
  </script>
</body>
</html>
