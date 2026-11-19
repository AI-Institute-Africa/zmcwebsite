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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Helvetica Neue', Arial, sans-serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden; background: #fff;
    }

    .top-accent { position: absolute; top: 0; left: 0; right: 0; height: 0.8mm; background: {{ $secondaryColor }}; }

    .content {
      position: relative; z-index: 2;
      padding: 3mm 5mm;
      height: 100%; box-sizing: border-box;
      display: flex; flex-direction: column;
    }

    .header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 2mm; padding-bottom: 1.5mm; border-bottom: 0.3px solid #e2e8f0;
    }

    .brand { display: flex; align-items: center; gap: 1.5mm; }
    .brand img { width: 6mm; height: 6mm; }
    .brand-text { font-size: 5pt; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

    .body-content { flex: 1; display: flex; gap: 5mm; }

    .info-col { flex: 1; }

    .info-line { font-size: 6.5pt; margin-bottom: 1.5mm; color: #64748b; }
    .info-line strong { color: #334155; font-weight: 700; }

    .notice {
      font-size: 5pt; color: #cbd5e1;
      margin-top: auto; line-height: 1.4;
    }

    .qr-col { display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .qr-hint { font-size: 4pt; color: #cbd5e1; margin-top: 1mm; letter-spacing: 0.5px; text-transform: uppercase; }

    .footer {
      display: flex; justify-content: space-between;
      font-size: 4.5pt; color: #cbd5e1;
      border-top: 0.3px solid #e2e8f0; padding-top: 1.5mm; margin-top: 1.5mm;
    }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="top-accent"></div>

    <div class="content">
      <div class="header">
        <div class="brand">
          <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
          <div class="brand-text">ZMC</div>
        </div>
      </div>

      <div class="body-content">
        <div class="info-col">
          <div class="info-line"><strong>Address:</strong> {{ config('organization.registered_address') }}</div>
          <div class="info-line"><strong>Tel:</strong> {{ config('organization.legacy_phone') }} or 253572/5/6</div>
          <div class="info-line"><strong>Email:</strong> {{ config('organization.accreditation_email') }}</div>
          <div class="info-line"><strong>Designation:</strong> {{ $payload['designation'] ?? 'Media Practitioner' }}</div>
          <div class="info-line"><strong>Valid:</strong> {{ $payload['valid_from'] ?? '---' }} to {{ $payload['valid_to'] ?? '---' }}</div>
          <div class="notice">This card remains the property of the Zimbabwe Media Commission.</div>
        </div>
        <div class="qr-col">
          <div id="cardBackQr"></div>
          <div class="qr-hint">Verify</div>
        </div>
      </div>

      <div class="footer">
        <span>{{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</span>
        <span>{{ $payload['ref'] ?? $application->reference }}</span>
      </div>
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
