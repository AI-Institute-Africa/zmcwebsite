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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Palatino Linotype', Georgia, serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: linear-gradient(135deg, #fefce8 0%, #fffbeb 50%, #fef9c3 100%);
      border: 1px solid {{ $primaryColor }}33;
    }

    .top-band { position: absolute; top: 0; left: 0; right: 0; height: 2mm; background: repeating-linear-gradient(90deg, {{ $primaryColor }} 0, {{ $primaryColor }} 3mm, {{ $secondaryColor }} 3mm, {{ $secondaryColor }} 6mm); }
    .bottom-band { position: absolute; bottom: 0; left: 0; right: 0; height: 2mm; background: repeating-linear-gradient(90deg, {{ $secondaryColor }} 0, {{ $secondaryColor }} 3mm, {{ $primaryColor }} 3mm, {{ $primaryColor }} 6mm); }

    .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg); width: 35mm; opacity: 0.05; }

    .content {
      position: relative; z-index: 2;
      padding: 4mm;
      height: 100%; box-sizing: border-box;
      display: flex; flex-direction: column; align-items: center; text-align: center;
    }

    .header { display: flex; align-items: center; gap: 2mm; margin-bottom: 1.5mm; }
    .header img { width: 8mm; height: 8mm; }
    .header-text { font-size: 6pt; font-weight: 700; color: {{ $primaryColor }}; letter-spacing: 0.5px; }

    .gold-line { width: 60mm; height: 0.5px; background: linear-gradient(to right, transparent, {{ $secondaryColor }}, {{ $primaryColor }}, {{ $secondaryColor }}, transparent); margin: 1mm 0; }

    .info-block { text-align: center; margin-bottom: 1mm; }
    .info-line { font-size: 6.5pt; margin-bottom: 0.8mm; color: #334155; }
    .info-line b { color: {{ $primaryColor }}; }

    .notice { font-size: 5pt; color: #78716c; font-style: italic; margin-top: 1mm; max-width: 55mm; line-height: 1.3; }

    .qr-row { display: flex; align-items: center; gap: 3mm; margin-top: auto; }
    .qr-box { width: 16mm; height: 16mm; border: 1px solid {{ $secondaryColor }}; background: #fff; display: flex; align-items: center; justify-content: center; }
    .qr-hint { font-size: 4.5pt; color: #78716c; }

    .footer { font-size: 4.5pt; color: {{ $primaryColor }}; font-weight: 700; margin-top: 1mm; letter-spacing: 0.5px; }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="top-band"></div>
    <div class="bottom-band"></div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">

    <div class="content">
      <div class="header">
        <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
        <div class="header-text">ZIMBABWE MEDIA COMMISSION</div>
      </div>
      <div class="gold-line"></div>

      <div class="info-block">
        <div class="info-line"><b>Address:</b> {{ config('organization.registered_address') }}</div>
        <div class="info-line"><b>Tel:</b> {{ config('organization.legacy_phone') }} or 253572/5/6</div>
        <div class="info-line"><b>Email:</b> {{ config('organization.accreditation_email') }}</div>
        <div class="info-line"><b>Designation:</b> {{ $payload['designation'] ?? 'Media Practitioner' }}</div>
      </div>

      <div class="notice">This card remains the property of the Zimbabwe Media Commission. If found, please return to ZMC.</div>

      <div class="qr-row">
        <div class="qr-box"><div id="cardBackQr"></div></div>
        <div class="qr-hint">Scan to verify<br>accreditation</div>
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
