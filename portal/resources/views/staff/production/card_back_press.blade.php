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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: Arial, sans-serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden; background: #fff;
    }

    .header-band {
      background: {{ $primaryColor }};
      clip-path: polygon(0 0, 100% 0, 100% 80%, 0 100%);
      padding: 2mm 4mm; display: flex; align-items: center; gap: 2mm;
    }
    .header-band img { width: 7mm; filter: brightness(10); }
    .header-band .text { color: #fff; font-size: 6pt; font-weight: 800; }
    .header-band .text .g { color: {{ $secondaryColor }}; }

    .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 28mm; opacity: 0.04; }

    .body-content {
      position: relative; z-index: 2;
      padding: 2mm 4mm;
      display: flex; gap: 4mm;
    }

    .info-col { flex: 1; }
    .info-line { font-size: 6.5pt; margin-bottom: 1.2mm; color: #334155; }
    .info-line b { color: {{ $primaryColor }}; }

    .notice {
      font-size: 5pt; color: #64748b;
      border-left: 2px solid {{ $secondaryColor }};
      padding-left: 2mm; margin-top: 2mm; line-height: 1.4;
    }

    .qr-col { display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .qr-hint { font-size: 5pt; color: #64748b; font-weight: 700; margin-top: 1mm; }

    .footer {
      position: absolute; bottom: 0; left: 0; right: 0;
      background: {{ $primaryColor }}08;
      display: flex; justify-content: space-between;
      padding: 1.5mm 4mm;
      font-size: 5pt; color: {{ $primaryColor }}; font-weight: 700;
    }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="header-band">
      <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
      <div class="text">ZIMBABWE <span class="g">MEDIA</span> COMMISSION</div>
    </div>
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
      try { new QRCode(document.getElementById('cardBackQr'), { text: @json($payload['qr_value'] ?? ''), width: 60, height: 60, correctLevel: QRCode.CorrectLevel.M }); } catch(e) {}
      window.addEventListener('load', () => setTimeout(() => window.print(), 200));
    })();
  </script>
</body>
</html>
