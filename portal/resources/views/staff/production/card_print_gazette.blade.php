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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Courier New', Courier, monospace; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: #fafaf5;
    }

    .header-bar {
      background: {{ $primaryColor }};
      display: flex; align-items: center; justify-content: center;
      padding: 1.5mm 3mm; gap: 2mm;
    }
    .header-bar img { width: 6mm; height: 6mm; filter: brightness(10); }
    .header-bar .title {
      color: #fff; font-size: 6pt; font-weight: 700;
      font-family: 'Georgia', serif; letter-spacing: 1px;
    }
    .header-bar .title .g { color: {{ $secondaryColor }}; }

    .sub-bar {
      background: {{ $secondaryColor }};
      display: flex; justify-content: space-between; align-items: center;
      padding: 0.8mm 3mm;
      font-family: 'Georgia', serif;
    }
    .sub-bar .type { font-size: 5pt; font-weight: 700; color: {{ $primaryColor }}; text-transform: uppercase; letter-spacing: 1px; }
    .sub-bar .year { font-size: 5pt; font-weight: 900; color: {{ $primaryColor }}; }

    .watermark {
      position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
      width: 30mm; opacity: 0.04;
    }

    .body-content {
      position: relative; z-index: 2;
      padding: 2mm 3mm;
      display: flex; gap: 2.5mm;
    }

    .photo-col {
      display: flex; flex-direction: column; align-items: center; gap: 1mm;
    }

    .photo-frame {
      width: 20mm; height: 24mm;
      border: 1px solid {{ $primaryColor }}44;
      overflow: hidden; background: #fff;
    }
    .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
    .photo-placeholder { font-size: 5pt; color: #94a3b8; text-align: center; padding-top: 8mm; font-weight: 700; }

    .data-col {
      flex: 1;
      border-left: 1px solid {{ $primaryColor }}22;
      padding-left: 2.5mm;
    }

    .headline {
      font-size: 9pt; font-weight: 900; color: #0f172a;
      font-family: 'Georgia', serif;
      border-bottom: 1px solid {{ $primaryColor }}33;
      padding-bottom: 0.5mm; margin-bottom: 1mm;
      line-height: 1.1;
    }

    .col-row { display: flex; gap: 3mm; }
    .col-left, .col-right { flex: 1; }

    .entry { margin-bottom: 0.8mm; }
    .entry-label { font-size: 4.5pt; color: #78716c; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; font-family: Arial, sans-serif; }
    .entry-value { font-size: 6.5pt; color: #1e293b; font-weight: 700; }

    .footer-strip {
      position: absolute; bottom: 0; left: 0; right: 0;
      display: flex; justify-content: space-between; align-items: flex-end;
      padding: 1.5mm 3mm;
      border-top: 0.5px dashed {{ $primaryColor }}33;
      background: #fafaf5;
    }

    .scope-label { font-size: 4pt; color: {{ $primaryColor }}; font-weight: 900; letter-spacing: 2px; text-transform: uppercase; font-family: Arial, sans-serif; }
    .verify-text { font-size: 3.5pt; color: #94a3b8; }

    .qr-wrap { display: flex; flex-direction: column; align-items: center; }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="header-bar">
      <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
      <div class="title">ZIMBABWE <span class="g">MEDIA</span> COMMISSION</div>
    </div>
    <div class="sub-bar">
      <span class="type">{{ $isForeign ? 'Foreign' : 'Local' }} Accreditation Card</span>
      <span class="year">{{ date('Y') }}</span>
    </div>

    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">

    <div class="body-content">
      <div class="photo-col">
        <div class="photo-frame">
          @if(!empty($payload['photo_url']))
            <img src="{{ $payload['photo_url'] }}" alt="Photo">
          @else
            <div class="photo-placeholder">PHOTO</div>
          @endif
        </div>
      </div>

      <div class="data-col">
        <div class="headline">{{ $payload['name'] ?? '---' }}</div>
        <div class="col-row">
          <div class="col-left">
            <div class="entry">
              <div class="entry-label">Acc. Number</div>
              <div class="entry-value">{{ $payload['reg_no'] ?? ($payload['accreditation_id'] ?? '---') }}</div>
            </div>
            <div class="entry">
              <div class="entry-label">National ID</div>
              <div class="entry-value">{{ $application->form_data['national_id'] ?? '---' }}</div>
            </div>
            <div class="entry">
              <div class="entry-label">Category</div>
              <div class="entry-value">{{ $application->accreditation_category_code ?? '---' }}</div>
            </div>
          </div>
          <div class="col-right">
            <div class="entry">
              <div class="entry-label">Organisation</div>
              <div class="entry-value">{{ $payload['organisation'] ?? '---' }}</div>
            </div>
            <div class="entry">
              <div class="entry-label">Valid From</div>
              <div class="entry-value">{{ $payload['valid_from'] ?? '---' }}</div>
            </div>
            <div class="entry">
              <div class="entry-label">Valid To</div>
              <div class="entry-value">{{ $payload['valid_to'] ?? '---' }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="footer-strip">
      <div class="scope-label">{{ strtoupper($payload['scope'] ?? 'LOCAL') }} MEDIA</div>
      <div class="qr-wrap">
        <div id="cardQr"></div>
        <div class="verify-text">Scan to verify</div>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    window.addEventListener('load', () => {
      try { new QRCode(document.getElementById('cardQr'), { text: @json($payload['qr_value'] ?? ''), width: 32, height: 32, correctLevel: QRCode.CorrectLevel.M }); } catch(e) {}
      setTimeout(() => window.print(), 300);
    });
  </script>
</body>
</html>
