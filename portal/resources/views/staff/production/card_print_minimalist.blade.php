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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Helvetica Neue', Arial, sans-serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: #ffffff;
    }

    .top-accent {
      position: absolute; top: 0; left: 0; right: 0; height: 0.8mm;
      background: {{ $secondaryColor }};
    }

    .content {
      position: relative; z-index: 2;
      padding: 3mm 5mm;
      height: 100%; box-sizing: border-box;
      display: flex; flex-direction: column;
    }

    .header {
      display: flex; justify-content: space-between; align-items: flex-start;
      margin-bottom: 2mm;
    }

    .brand {
      display: flex; align-items: center; gap: 2mm;
    }
    .brand img { width: 7mm; height: 7mm; object-fit: contain; }
    .brand-text { font-size: 5pt; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

    .scope-chip {
      font-size: 5pt; font-weight: 800;
      color: {{ $primaryColor }};
      border: 1px solid {{ $primaryColor }}44;
      padding: 0.5mm 2mm;
      letter-spacing: 0.5px; text-transform: uppercase;
    }

    .name-section {
      font-size: 12pt; font-weight: 300; color: #0f172a;
      letter-spacing: -0.3px; margin-bottom: 2mm;
      line-height: 1.1;
    }

    .name-section strong { font-weight: 800; }

    .thin-line { height: 0.3px; background: #e2e8f0; margin-bottom: 2mm; }

    .body-row { display: flex; gap: 3mm; flex: 1; }

    .photo-frame {
      width: 18mm; height: 22mm;
      overflow: hidden; flex-shrink: 0;
      background: #f8fafc;
    }
    .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
    .photo-placeholder { font-size: 5pt; color: #cbd5e1; text-align: center; padding-top: 7mm; font-weight: 600; }

    .grid {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5mm 4mm;
      align-content: start;
    }

    .cell {}
    .cell-label { font-size: 4.5pt; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600; }
    .cell-value { font-size: 7pt; color: #1e293b; font-weight: 700; margin-top: 0.2mm; }
    .cell.span2 { grid-column: span 2; }

    .bottom-row {
      display: flex; justify-content: space-between; align-items: flex-end;
      margin-top: auto;
    }

    .card-type { font-size: 4.5pt; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; }

    .qr-wrap {}

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
        <div class="scope-chip">{{ strtoupper($payload['scope'] ?? 'LOCAL') }}</div>
      </div>

      <div class="name-section">
        <strong>{{ $payload['name'] ?? '---' }}</strong>
      </div>

      <div class="thin-line"></div>

      <div class="body-row">
        <div class="photo-frame">
          @if(!empty($payload['photo_url']))
            <img src="{{ $payload['photo_url'] }}" alt="Photo">
          @else
            <div class="photo-placeholder">PHOTO</div>
          @endif
        </div>
        <div class="grid">
          <div class="cell">
            <div class="cell-label">Accreditation No</div>
            <div class="cell-value">{{ $payload['reg_no'] ?? ($payload['accreditation_id'] ?? '---') }}</div>
          </div>
          <div class="cell">
            <div class="cell-label">National ID</div>
            <div class="cell-value">{{ $application->form_data['national_id'] ?? '---' }}</div>
          </div>
          <div class="cell">
            <div class="cell-label">Organisation</div>
            <div class="cell-value">{{ $payload['organisation'] ?? '---' }}</div>
          </div>
          <div class="cell">
            <div class="cell-label">Category</div>
            <div class="cell-value">{{ $application->accreditation_category_code ?? '---' }}</div>
          </div>
          <div class="cell span2">
            <div class="cell-label">Validity</div>
            <div class="cell-value">{{ $payload['valid_from'] ?? '---' }} &mdash; {{ $payload['valid_to'] ?? '---' }}</div>
          </div>
        </div>
      </div>

      <div class="bottom-row">
        <div class="card-type">{{ $isForeign ? 'Foreign' : 'Local' }} Accreditation &bull; {{ date('Y') }}</div>
        <div class="qr-wrap"><div id="cardQr"></div></div>
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
