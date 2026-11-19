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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Georgia', 'Times New Roman', serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: linear-gradient(180deg, {{ $primaryColor }}0a 0%, #fff 30%, #fff 70%, {{ $primaryColor }}08 100%);
      border: 1.5px solid {{ $primaryColor }}44;
    }

    .inner-border {
      position: absolute; top: 2mm; left: 2mm; right: 2mm; bottom: 2mm;
      border: 0.5px solid {{ $secondaryColor }}88;
      pointer-events: none; z-index: 1;
    }

    .corner { position: absolute; width: 5mm; height: 5mm; border-color: {{ $primaryColor }}; z-index: 2; }
    .corner-tl { top: 1mm; left: 1mm; border-top: 1.5px solid; border-left: 1.5px solid; }
    .corner-tr { top: 1mm; right: 1mm; border-top: 1.5px solid; border-right: 1.5px solid; }
    .corner-bl { bottom: 1mm; left: 1mm; border-bottom: 1.5px solid; border-left: 1.5px solid; }
    .corner-br { bottom: 1mm; right: 1mm; border-bottom: 1.5px solid; border-right: 1.5px solid; }

    .watermark {
      position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
      width: 35mm; opacity: 0.05;
    }

    .content { position: relative; z-index: 3; padding: 3mm 4mm; height: 100%; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; }

    .crest {
      display: flex; flex-direction: column; align-items: center; margin-bottom: 1.5mm;
    }
    .crest img { width: 10mm; height: 10mm; object-fit: contain; }
    .crest-text { font-size: 6.5pt; font-weight: 700; letter-spacing: 1px; color: {{ $primaryColor }}; margin-top: 0.5mm; }
    .crest-sub { font-size: 5pt; color: #64748b; letter-spacing: 0.5px; font-style: italic; }

    .divider-line {
      width: 50mm; height: 0.5px; margin: 1.5mm 0;
      background: linear-gradient(to right, transparent, {{ $secondaryColor }}, transparent);
    }

    .main-body {
      display: flex; gap: 3mm; width: 100%; align-items: flex-start;
    }

    .photo-frame {
      width: 18mm; height: 22mm;
      border: 1px solid {{ $primaryColor }}66;
      overflow: hidden; flex-shrink: 0; background: #f8fafc;
    }
    .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
    .photo-placeholder { font-size: 5pt; color: #94a3b8; text-align: center; padding-top: 7mm; font-weight: 700; }

    .info-block { flex: 1; }

    .person-name {
      font-size: 8.5pt; font-weight: 700; color: #0f172a; margin-bottom: 1mm;
      font-family: 'Georgia', serif;
    }

    .info-line { font-size: 6.5pt; margin-bottom: 0.5mm; color: #334155; }
    .info-line strong { color: {{ $primaryColor }}; font-weight: 700; }

    .bottom-bar {
      position: absolute; bottom: 0; left: 0; right: 0;
      background: {{ $primaryColor }};
      display: flex; justify-content: space-between; align-items: center;
      padding: 1mm 4mm;
    }
    .bottom-bar .scope-text { color: {{ $secondaryColor }}; font-size: 5.5pt; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; font-family: Arial, sans-serif; }
    .bottom-bar .year-text { color: #fff; font-size: 5pt; font-weight: 700; font-family: Arial, sans-serif; }

    .qr-wrap { position: absolute; bottom: 8mm; right: 4mm; }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="inner-border"></div>
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">

    <div class="content">
      <div class="crest">
        <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
        <div class="crest-text">ZIMBABWE MEDIA COMMISSION</div>
        <div class="crest-sub">{{ $isForeign ? 'Foreign Accreditation Card' : 'Local Accreditation Card' }}</div>
      </div>
      <div class="divider-line"></div>

      <div class="main-body">
        <div class="photo-frame">
          @if(!empty($payload['photo_url']))
            <img src="{{ $payload['photo_url'] }}" alt="Photo">
          @else
            <div class="photo-placeholder">PHOTO</div>
          @endif
        </div>
        <div class="info-block">
          <div class="person-name">{{ $payload['name'] ?? '---' }}</div>
          <div class="info-line"><strong>ACC:</strong> {{ $payload['reg_no'] ?? ($payload['accreditation_id'] ?? '---') }}</div>
          <div class="info-line"><strong>ID:</strong> {{ $application->form_data['national_id'] ?? '---' }}</div>
          <div class="info-line"><strong>ORG:</strong> {{ $payload['organisation'] ?? '---' }}</div>
          <div class="info-line"><strong>CAT:</strong> {{ $application->accreditation_category_code ?? '---' }}</div>
          <div class="info-line"><strong>VALID:</strong> {{ $payload['valid_from'] ?? '---' }} to {{ $payload['valid_to'] ?? '---' }}</div>
        </div>
      </div>
    </div>

    <div class="qr-wrap"><div id="cardQr"></div></div>

    <div class="bottom-bar">
      <span class="scope-text">{{ strtoupper($payload['scope'] ?? 'LOCAL') }} MEDIA</span>
      <span class="year-text">MEDIA CARD {{ date('Y') }}</span>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    window.addEventListener('load', () => {
      try { new QRCode(document.getElementById('cardQr'), { text: @json($payload['qr_value'] ?? ''), width: 36, height: 36, correctLevel: QRCode.CorrectLevel.M }); } catch(e) {}
      setTimeout(() => window.print(), 300);
    });
  </script>
</body>
</html>
