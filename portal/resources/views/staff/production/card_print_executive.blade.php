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

    .top-stripe {
      position: absolute; top: 0; left: 12mm; right: 0; height: 3mm;
      background: {{ $secondaryColor }};
    }

    .watermark {
      position: absolute; bottom: 5mm; right: 5mm;
      width: 30mm; opacity: 0.06;
    }

    .content { position: relative; z-index: 2; padding: 4mm 5mm 4mm 14mm; height: 100%; box-sizing: border-box; display: flex; flex-direction: column; }

    .header-row {
      display: flex; align-items: center; gap: 3mm; margin-bottom: 2mm;
    }

    .logo { width: 9mm; height: 9mm; object-fit: contain; }

    .org-text {
      font-size: 7pt; font-weight: 800; line-height: 1.1; letter-spacing: 0.3px;
    }
    .org-text .g { color: #228B22; }

    .card-type {
      margin-top: 0.5mm;
      font-size: 6pt; font-weight: 700;
      color: {{ $primaryColor }}; letter-spacing: 1px; text-transform: uppercase;
    }

    .body-row {
      display: flex; gap: 3mm; flex: 1; align-items: flex-start; margin-top: 1mm;
    }

    .photo-frame {
      width: 20mm; height: 25mm;
      border: 1.5px solid {{ $primaryColor }};
      border-radius: 2px; overflow: hidden; flex-shrink: 0;
      background: #f1f5f9;
    }

    .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
    .photo-placeholder { font-size: 6pt; color: #94a3b8; text-align: center; padding-top: 8mm; font-weight: 700; }

    .details { flex: 1; display: flex; flex-direction: column; gap: 1mm; }

    .d-line { display: flex; gap: 2mm; font-size: 7pt; }
    .d-line .lbl { color: {{ $primaryColor }}; font-weight: 800; min-width: 12mm; text-transform: uppercase; font-size: 5.5pt; letter-spacing: 0.5px; }
    .d-line .val { font-weight: 700; color: #1e293b; }

    .name-highlight {
      font-size: 9pt; font-weight: 900; color: #0f172a;
      border-bottom: 1px solid {{ $secondaryColor }};
      padding-bottom: 0.5mm; margin-bottom: 1mm;
    }

    .scope-tag {
      position: absolute; bottom: 0; right: 0;
      background: {{ $primaryColor }}; color: #fff;
      font-size: 5pt; font-weight: 900; letter-spacing: 2px;
      padding: 1.5mm 5mm; text-transform: uppercase;
    }

    .qr-wrap {
      position: absolute; top: 5mm; right: 5mm;
    }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="accent-bar"></div>
    <div class="top-stripe"></div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">

    <div class="content">
      <div class="header-row">
        <img src="{{ asset('zmc_logo.png') }}" class="logo" alt="ZMC">
        <div>
          <div class="org-text">ZIMBABWE <span class="g">MEDIA</span> COMMISSION</div>
          <div class="card-type">{{ $isForeign ? 'Foreign' : 'Local' }} Accreditation Card</div>
        </div>
      </div>

      <div class="body-row">
        <div class="photo-frame">
          @if(!empty($payload['photo_url']))
            <img src="{{ $payload['photo_url'] }}" alt="Photo">
          @else
            <div class="photo-placeholder">PHOTO</div>
          @endif
        </div>
        <div class="details">
          <div class="name-highlight">{{ $payload['name'] ?? '---' }}</div>
          <div class="d-line"><span class="lbl">Acc No</span><span class="val">{{ $payload['reg_no'] ?? ($payload['accreditation_id'] ?? '---') }}</span></div>
          <div class="d-line"><span class="lbl">ID No</span><span class="val">{{ $application->form_data['national_id'] ?? '---' }}</span></div>
          <div class="d-line"><span class="lbl">Org</span><span class="val">{{ $payload['organisation'] ?? '---' }}</span></div>
          <div class="d-line"><span class="lbl">Cat</span><span class="val">{{ $application->accreditation_category_code ?? '---' }}</span></div>
          <div class="d-line"><span class="lbl">Valid</span><span class="val">{{ $payload['valid_from'] ?? '---' }} to {{ $payload['valid_to'] ?? '---' }}</span></div>
        </div>
      </div>
    </div>

    <div class="qr-wrap">
      <div id="cardQr"></div>
    </div>
    <div class="scope-tag">{{ strtoupper($payload['scope'] ?? 'LOCAL') }} MEDIA</div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    window.addEventListener('load', () => {
      try { new QRCode(document.getElementById('cardQr'), { text: @json($payload['qr_value'] ?? ''), width: 40, height: 40, correctLevel: QRCode.CorrectLevel.M }); } catch(e) {}
      setTimeout(() => window.print(), 300);
    });
  </script>
</body>
</html>
