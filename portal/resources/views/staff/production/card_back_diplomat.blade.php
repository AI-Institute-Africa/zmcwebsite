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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Georgia', serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: linear-gradient(180deg, {{ $primaryColor }}06 0%, #fff 40%, #fff 60%, {{ $primaryColor }}04 100%);
      border: 1.5px solid {{ $primaryColor }}44;
    }

    .inner-border { position: absolute; top: 2mm; left: 2mm; right: 2mm; bottom: 2mm; border: 0.5px solid {{ $secondaryColor }}88; pointer-events: none; }
    .corner { position: absolute; width: 5mm; height: 5mm; border-color: {{ $primaryColor }}; z-index: 2; }
    .corner-tl { top: 1mm; left: 1mm; border-top: 1.5px solid; border-left: 1.5px solid; }
    .corner-tr { top: 1mm; right: 1mm; border-top: 1.5px solid; border-right: 1.5px solid; }
    .corner-bl { bottom: 1mm; left: 1mm; border-bottom: 1.5px solid; border-left: 1.5px solid; }
    .corner-br { bottom: 1mm; right: 1mm; border-bottom: 1.5px solid; border-right: 1.5px solid; }

    .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 30mm; opacity: 0.04; }

    .content {
      position: relative; z-index: 3;
      padding: 4mm 5mm;
      height: 100%; box-sizing: border-box;
      display: flex; flex-direction: column; align-items: center; text-align: center;
    }

    .crest { margin-bottom: 1.5mm; }
    .crest img { width: 8mm; height: 8mm; }
    .crest-text { font-size: 6pt; font-weight: 700; color: {{ $primaryColor }}; letter-spacing: 0.8px; margin-top: 0.5mm; }

    .divider { width: 50mm; height: 0.5px; background: linear-gradient(to right, transparent, {{ $secondaryColor }}, transparent); margin: 1.5mm 0; }

    .info-center { text-align: center; }
    .info-line { font-size: 6.5pt; margin-bottom: 1mm; color: #334155; }
    .info-line b { color: {{ $primaryColor }}; }

    .notice { font-size: 5pt; color: #64748b; margin-top: 1.5mm; font-style: italic; line-height: 1.3; max-width: 60mm; }

    .qr-row { display: flex; align-items: center; gap: 4mm; margin-top: auto; }
    .qr-box { width: 16mm; height: 16mm; border: 1px solid {{ $primaryColor }}44; background: #fff; display: flex; align-items: center; justify-content: center; }
    .qr-hint { font-size: 4.5pt; color: #64748b; font-weight: 700; }

    .footer { font-size: 4.5pt; color: {{ $primaryColor }}; font-weight: 700; margin-top: 1.5mm; letter-spacing: 0.5px; }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="inner-border"></div>
    <div class="corner corner-tl"></div><div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div><div class="corner corner-br"></div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">

    <div class="content">
      <div class="crest">
        <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
        <div class="crest-text">ZIMBABWE MEDIA COMMISSION</div>
      </div>
      <div class="divider"></div>

      <div class="info-center">
        <div class="info-line"><b>Address:</b> {{ config('organization.registered_address') }}</div>
        <div class="info-line"><b>Tel:</b> {{ config('organization.legacy_phone') }} or 253572/5/6</div>
        <div class="info-line"><b>Email:</b> {{ config('organization.accreditation_email') }}</div>
        <div class="info-line"><b>Designation:</b> {{ $payload['designation'] ?? 'Media Practitioner' }}</div>
      </div>

      <div class="notice">This card remains the property of the Zimbabwe Media Commission. If found, please return to ZMC.</div>

      <div class="qr-row">
        <div class="qr-box"><div id="cardBackQr"></div></div>
        <div class="qr-hint">Scan QR to verify<br>accreditation status</div>
      </div>

      <div class="footer">{{ parse_url(config('app.website_url'), PHP_URL_HOST) }} &bull; Tel: {{ config('organization.legacy_phone') }}</div>
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
