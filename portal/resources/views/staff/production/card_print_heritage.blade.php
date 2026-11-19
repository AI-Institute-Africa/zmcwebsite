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
    body { display:flex; align-items:center; justify-content:center; background:#fff; font-family: 'Palatino Linotype', 'Book Antiqua', Georgia, serif; }

    .card {
      width: 85.6mm; height: 53.98mm;
      position: relative; overflow: hidden;
      background: linear-gradient(135deg, #fefce8 0%, #fffbeb 50%, #fef9c3 100%);
      border: 1px solid {{ $primaryColor }}33;
    }

    .pattern-overlay {
      position: absolute; top: 0; left: 0; right: 0; bottom: 0;
      background-image:
        repeating-linear-gradient(45deg, {{ $primaryColor }}05 0, {{ $primaryColor }}05 1px, transparent 1px, transparent 5mm),
        repeating-linear-gradient(-45deg, {{ $primaryColor }}05 0, {{ $primaryColor }}05 1px, transparent 1px, transparent 5mm);
    }

    .top-band {
      position: absolute; top: 0; left: 0; right: 0; height: 2mm;
      background: repeating-linear-gradient(90deg, {{ $primaryColor }} 0, {{ $primaryColor }} 3mm, {{ $secondaryColor }} 3mm, {{ $secondaryColor }} 6mm);
    }

    .bottom-band {
      position: absolute; bottom: 0; left: 0; right: 0; height: 2mm;
      background: repeating-linear-gradient(90deg, {{ $secondaryColor }} 0, {{ $secondaryColor }} 3mm, {{ $primaryColor }} 3mm, {{ $primaryColor }} 6mm);
    }

    .watermark {
      position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg);
      width: 40mm; opacity: 0.06;
    }

    .content {
      position: relative; z-index: 2;
      padding: 4mm 4mm 4mm 4mm;
      height: 100%; box-sizing: border-box;
      display: flex; flex-direction: column;
    }

    .header {
      display: flex; align-items: center; justify-content: center; gap: 3mm; margin-bottom: 1.5mm;
    }
    .header img { width: 10mm; height: 10mm; object-fit: contain; }
    .header-text { text-align: center; }
    .header-text .org { font-size: 7pt; font-weight: 700; color: {{ $primaryColor }}; letter-spacing: 1px; }
    .header-text .org .g { color: #228B22; }
    .header-text .type { font-size: 5.5pt; color: #78716c; font-style: italic; }

    .gold-line {
      width: 70%; height: 0.5px; margin: 1mm auto;
      background: linear-gradient(to right, transparent, {{ $secondaryColor }}, {{ $primaryColor }}, {{ $secondaryColor }}, transparent);
    }

    .body-row { display: flex; gap: 3mm; flex: 1; }

    .left-col {
      display: flex; flex-direction: column; align-items: center; gap: 1mm;
    }

    .photo-frame {
      width: 20mm; height: 24mm;
      border: 1.5px solid {{ $secondaryColor }};
      overflow: hidden; background: #fff;
    }
    .photo-frame img { width: 100%; height: 100%; object-fit: cover; }
    .photo-placeholder { font-size: 5pt; color: #94a3b8; text-align: center; padding-top: 8mm; font-weight: 700; }

    .scope-badge {
      background: {{ $primaryColor }}; color: {{ $secondaryColor }};
      font-size: 4.5pt; font-weight: 900; letter-spacing: 1.5px;
      padding: 0.8mm 2mm; text-transform: uppercase;
      font-family: Arial, sans-serif;
    }

    .right-col { flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 1mm; }

    .name-plate {
      font-size: 9pt; font-weight: 700; color: {{ $primaryColor }};
      border-bottom: 1px dotted {{ $secondaryColor }};
      padding-bottom: 0.5mm; margin-bottom: 0.5mm;
    }

    .row { font-size: 6.5pt; display: flex; gap: 1.5mm; }
    .row .k { color: #78716c; font-weight: 700; min-width: 10mm; text-transform: uppercase; font-size: 5.5pt; }
    .row .v { color: #1c1917; font-weight: 800; }

    .qr-wrap { position: absolute; bottom: 4mm; right: 4mm; }
    .flag-watermark { position: absolute; bottom: 3mm; left: 4mm; width: 18mm; opacity: 0.15; }

    @media print { body { background: #fff; } }
  </style>
</head>
<body>
  <div class="card">
    <div class="pattern-overlay"></div>
    <div class="top-band"></div>
    <div class="bottom-band"></div>
    <img src="{{ asset('zmc_logo.png') }}" class="watermark" alt="">
    <img src="{{ asset('zim_flag.png') }}" class="flag-watermark" alt="" onerror="this.style.display='none'">

    <div class="content">
      <div class="header">
        <img src="{{ asset('zmc_logo.png') }}" alt="ZMC">
        <div class="header-text">
          <div class="org">ZIMBABWE <span class="g">MEDIA</span> COMMISSION</div>
          <div class="type">{{ $isForeign ? 'Foreign' : 'Local' }} Accreditation Card</div>
        </div>
      </div>
      <div class="gold-line"></div>

      <div class="body-row">
        <div class="left-col">
          <div class="photo-frame">
            @if(!empty($payload['photo_url']))
              <img src="{{ $payload['photo_url'] }}" alt="Photo">
            @else
              <div class="photo-placeholder">PHOTO</div>
            @endif
          </div>
          <div class="scope-badge">{{ strtoupper($payload['scope'] ?? 'LOCAL') }}</div>
        </div>
        <div class="right-col">
          <div class="name-plate">{{ $payload['name'] ?? '---' }}</div>
          <div class="row"><span class="k">Acc No</span><span class="v">{{ $payload['reg_no'] ?? ($payload['accreditation_id'] ?? '---') }}</span></div>
          <div class="row"><span class="k">ID No</span><span class="v">{{ $application->form_data['national_id'] ?? '---' }}</span></div>
          <div class="row"><span class="k">Org</span><span class="v">{{ $payload['organisation'] ?? '---' }}</span></div>
          <div class="row"><span class="k">Cat</span><span class="v">{{ $application->accreditation_category_code ?? '---' }}</span></div>
          <div class="row"><span class="k">Valid</span><span class="v">{{ $payload['valid_from'] ?? '---' }} - {{ $payload['valid_to'] ?? '---' }}</span></div>
        </div>
      </div>
    </div>

    <div class="qr-wrap"><div id="cardQr"></div></div>
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
