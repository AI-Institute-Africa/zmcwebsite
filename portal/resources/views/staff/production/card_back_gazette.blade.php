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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Courier New', Courier, monospace; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden; background: #fafaf5;
    }

    .header-bar {
      background: {{ $primaryColor }};
      display: flex; align-items: center; justify-content: center;
      padding: 1.5mm 3mm; gap: 2mm;
    }
    .header-bar img { width: 5mm; filter: brightness(10); }
    .header-bar .title { color: #fff; font-size: 5.5pt; font-weight: 700; font-family: 'Georgia', serif; letter-spacing: 0.5px; }
    .header-bar .title .g { color: {{ $secondaryColor }}; }

    .sub-bar {
      background: {{ $secondaryColor }};
      text-align: center; padding: 0.5mm;
      font-size: 4.5pt; font-weight: 700; color: {{ $primaryColor }};
      font-family: 'Georgia', serif;
    }

    .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 25mm; opacity: 0.04; }

    .body-content {
      position: relative; z-index: 2;
      padding: 2mm 3mm;
      display: flex; gap: 3mm;
    }

    .info-col { flex: 1; }
    .info-line { font-size: 6pt; margin-bottom: 1mm; color: #334155; }
    .info-line b { color: {{ $primaryColor }}; font-family: Arial, sans-serif; font-size: 5pt; text-transform: uppercase; }

    .notice {
      font-size: 5pt; color: #78716c;
      border-top: 0.5px dashed {{ $primaryColor }}33;
      padding-top: 1.5mm; margin-top: 1.5mm; line-height: 1.3;
      font-family: Arial, sans-serif;
    }

    .qr-col { display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .qr-hint { font-size: 4.5pt; color: #78716c; font-weight: 700; margin-top: 0.5mm; font-family: Arial, sans-serif; }

    .footer {
      position: absolute; bottom: 0; left: 0; right: 0;
      border-top: 0.5px dashed {{ $primaryColor }}33;
      display: flex; justify-content: space-between;
      padding: 1mm 3mm;
      font-size: 4.5pt; color: {{ $primaryColor }}; font-weight: 700;
      font-family: Arial, sans-serif;
    }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="header-bar">
      <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
      <div class="title">ZIMBABWE <span class="g">MEDIA</span> COMMISSION</div>
    </div>
    <div class="sub-bar">Accreditation Card (Back)</div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">

    <div class="body-content">
      <div class="info-col">
        <div class="info-line"><b>Address:</b> {{ config('organization.registered_address') }}</div>
        <div class="info-line"><b>Tel:</b> {{ config('organization.legacy_phone') }} or 253572/5/6</div>
        <div class="info-line"><b>Email:</b> {{ config('organization.accreditation_email') }}</div>
        <div class="info-line"><b>Designation:</b> {{ $payload['designation'] ?? 'Media Practitioner' }}</div>
        <div class="info-line"><b>Acc No:</b> {{ $payload['accreditation_number'] ?? ($payload['reg_no'] ?? ($payload['ref'] ?? '---')) }}</div>
        <div class="notice">This card remains the property of the Zimbabwe Media Commission. If found, please return to ZMC.</div>
      </div>
      <div class="qr-col">
        <div id="cardBackQr"></div>
        <div class="qr-hint">Scan to verify</div>
      </div>
    </div>

    <div class="footer">
      <span>{{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</span>
      <span>Tel: {{ config('organization.legacy_phone') }}</span>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function(){
      try { new QRCode(document.getElementById('cardBackQr'), { text: @json($payload['qr_value'] ?? ''), width: 55, height: 55, correctLevel: QRCode.CorrectLevel.M }); } catch(e) {}
      window.addEventListener('load', () => setTimeout(() => window.print(), 200));
    })();
  </script>
</body>
</html>
