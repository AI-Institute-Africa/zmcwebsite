<!doctype html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>Print Card - {{ $application->reference }}</title>
  <style>
    @php
        $primaryColor = $template_data['primary_color'] ?? '#1a237e';
        $secondaryColor = $template_data['secondary_color'] ?? '#f5c518';
        $isForeign = strtoupper($payload['scope'] ?? 'LOCAL') === 'FOREIGN';
        if ($isForeign) { $primaryColor = '#1a5f2a'; $secondaryColor = '#a8d5ba'; }
    @endphp

    @page { size: 85.6mm 53.98mm; margin: 0; }
    html, body { height: 100%; margin: 0; }
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Arial', 'Helvetica Neue', sans-serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: #ffffff;
    }

    .diagonal-bg {
      position: absolute; top: 0; right: 0;
      width: 0; height: 0;
      border-top: 53.98mm solid {{ $primaryColor }}0d;
      border-left: 42mm solid transparent;
    }

    .header-band {
      position: absolute; top: 0; left: 0; right: 0; height: 14mm;
      background: {{ $primaryColor }};
      clip-path: polygon(0 0, 100% 0, 100% 80%, 0 100%);
      display: flex; align-items: center; padding: 0 4mm;
    }

    .header-band .logo { width: 8mm; height: 8mm; object-fit: contain; margin-right: 2mm; filter: brightness(10); }
    .header-band .text { color: #fff; font-size: 7pt; font-weight: 800; letter-spacing: 0.5px; }
    .header-band .text .g { color: {{ $secondaryColor }}; }
    .header-band .sub { color: {{ $secondaryColor }}; font-size: 5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-top: 0.5mm; }

    .watermark {
      position: absolute; top: 50%; left: 60%; transform: translate(-50%, -50%);
      width: 28mm; opacity: 0.04;
    }

    .body-content {
      position: relative; z-index: 2;
      padding: 15mm 4mm 4mm 4mm;
      display: flex; gap: 3mm; height: calc(100% - 8mm);
      box-sizing: border-box;
    }

    .photo-frame {
      width: 22mm; height: 27mm;
      border: 2px solid {{ $secondaryColor }};
      border-radius: 3px; overflow: hidden; flex-shrink: 0;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      background: #f1f5f9;
    }
    .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
    .photo-placeholder { font-size: 6pt; color: #94a3b8; text-align: center; padding-top: 9mm; font-weight: 700; }

    .info { flex: 1; display: flex; flex-direction: column; gap: 0.8mm; }

    .name-line {
      font-size: 9pt; font-weight: 900; color: {{ $primaryColor }};
      text-transform: uppercase; letter-spacing: 0.3px;
      border-left: 2px solid {{ $secondaryColor }};
      padding-left: 2mm; margin-bottom: 1mm;
    }

    .field { display: flex; font-size: 6.5pt; }
    .field-label { color: #64748b; font-weight: 700; min-width: 11mm; text-transform: uppercase; font-size: 5.5pt; }
    .field-value { color: #1e293b; font-weight: 800; }

    .scope-pill {
      position: absolute; bottom: 2mm; left: 4mm;
      background: {{ $primaryColor }};
      color: {{ $secondaryColor }};
      font-size: 5pt; font-weight: 900; letter-spacing: 2px;
      padding: 1mm 3mm; border-radius: 2mm;
      text-transform: uppercase;
    }

    .year-badge {
      position: absolute; top: 15mm; right: 3mm;
      background: {{ $secondaryColor }};
      color: {{ $primaryColor }};
      font-size: 7pt; font-weight: 900;
      padding: 1mm 2mm; border-radius: 1mm;
    }

    .qr-wrap { position: absolute; bottom: 2mm; right: 3mm; }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="diagonal-bg"></div>
    <div class="header-band">
      <img src="{{ asset('zmc_logo.png') }}" class="logo" alt="ZMC">
      <div>
        <div class="text">ZIMBABWE <span class="g">MEDIA</span> COMMISSION</div>
        <div class="sub">{{ $isForeign ? 'Foreign' : 'Local' }} Accreditation</div>
      </div>
    </div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">
    <div class="year-badge">{{ date('Y') }}</div>

    <div class="body-content">
      <div class="photo-frame">
        @if(!empty($payload['photo_url']))
          <img src="{{ $payload['photo_url'] }}" alt="Photo">
        @else
          <div class="photo-placeholder">PHOTO</div>
        @endif
      </div>
      <div class="info">
        <div class="name-line">{{ $payload['name'] ?? '---' }}</div>
        <div class="field"><span class="field-label">Acc No</span><span class="field-value">{{ $payload['reg_no'] ?? ($payload['accreditation_id'] ?? '---') }}</span></div>
        <div class="field"><span class="field-label">ID No</span><span class="field-value">{{ $application->form_data['national_id'] ?? '---' }}</span></div>
        <div class="field"><span class="field-label">Org</span><span class="field-value">{{ $payload['organisation'] ?? '---' }}</span></div>
        <div class="field"><span class="field-label">Cat</span><span class="field-value">{{ $application->accreditation_category_code ?? '---' }}</span></div>
        <div class="field"><span class="field-label">Valid</span><span class="field-value">{{ $payload['valid_from'] ?? '---' }} - {{ $payload['valid_to'] ?? '---' }}</span></div>
      </div>
    </div>

    <div class="scope-pill">{{ strtoupper($payload['scope'] ?? 'LOCAL') }} MEDIA</div>
    <div class="qr-wrap"><div id="cardQr"></div></div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    window.addEventListener('load', () => {
      try { new QRCode(document.getElementById('cardQr'), { text: @json($payload['qr_value'] ?? ''), width: 38, height: 38, correctLevel: QRCode.CorrectLevel.M }); } catch(e) {}
      setTimeout(() => window.print(), 300);
    });
  </script>
</body>
</html>
