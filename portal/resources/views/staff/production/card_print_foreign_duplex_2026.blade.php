<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>Print Foreign Media Card (Duplex) - {{ $application->reference }}</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      background: #fff;
      font-family: 'Arial', 'Helvetica', sans-serif;
    }
    
    /* Print page setup for duplex printing */
    @page {
      size: 85.6mm 53.98mm;
      margin: 0;
    }
    
    /* Container for both sides */
    .print-container {
      width: 100%;
    }
    
    /* Card wrapper - each side is a separate page */
    .card-page {
      width: 85.6mm;
      height: 53.98mm;
      position: relative;
      background: #fff;
      overflow: hidden;
      page-break-after: always;
    }
    
    .card-page:last-child {
      page-break-after: avoid;
    }
    
    /* ============================================
       FRONT CARD STYLES
       ============================================ */
    
    .card-front .card-background {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, 
        rgba(240, 248, 255, 0.9) 0%, 
        rgba(230, 240, 250, 0.95) 50%, 
        rgba(220, 235, 245, 0.9) 100%);
      z-index: 1;
    }
    
    .card-front .wave-pattern {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: 
        repeating-linear-gradient(90deg, 
          rgba(255,255,255,0.03) 0px, 
          transparent 1px, 
          transparent 2px, 
          rgba(255,255,255,0.03) 3px),
        repeating-linear-gradient(0deg, 
          rgba(255,255,255,0.02) 0px, 
          transparent 1px, 
          transparent 2px, 
          rgba(255,255,255,0.02) 3px);
      z-index: 2;
    }
    
    .card-front .flag-section {
      position: absolute;
      top: 0;
      right: 0;
      width: 25mm;
      height: 100%;
      background: linear-gradient(180deg, 
        #006633 0%, 
        #FFD700 25%, 
        #CE1126 50%, 
        #000000 75%, 
        #006633 100%);
      z-index: 3;
    }
    
    .card-front .flag-star {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 15mm;
      height: 15mm;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><polygon points="50,15 60,40 87,40 66,56 75,82 50,65 25,82 34,56 13,40 40,40" fill="%23FFD700"/><circle cx="50" cy="50" r="8" fill="%23CE1126"/></svg>') center/contain no-repeat;
    }
    
    .card-front .green-bar {
      position: absolute;
      top: 0;
      left: 0;
      width: 6mm;
      height: 100%;
      background: linear-gradient(180deg, #006633 0%, #004d26 100%);
      z-index: 4;
    }
    
    .card-front .card-content {
      position: relative;
      z-index: 5;
      padding: 3mm 7mm 3mm 8mm;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .card-front .card-header {
      text-align: center;
      margin-bottom: 2mm;
    }
    
    .card-front .org-name {
      font-size: 11pt;
      font-weight: 900;
      letter-spacing: 0.5px;
      color: #1a1a1a;
      line-height: 1.1;
      margin-bottom: 1mm;
    }
    
    .card-front .org-name .media-word {
      color: #006633;
    }
    
    .card-front .logo-section {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2mm;
      margin-bottom: 1mm;
    }
    
    .card-front .zmc-logo {
      width: 12mm;
      height: 12mm;
      border-radius: 50%;
      border: 2px solid #006633;
      object-fit: contain;
      background: #fff;
      padding: 1mm;
    }
    
    .card-front .card-type {
      font-size: 8pt;
      font-weight: 900;
      color: #1a1a1a;
      letter-spacing: 0.3px;
    }
    
    .card-front .main-content {
      flex: 1;
      display: grid;
      grid-template-columns: 20mm 1fr;
      gap: 3mm;
      align-items: center;
    }
    
    .card-front .photo-container {
      width: 20mm;
      height: 24mm;
      border: 2px solid #006633;
      border-radius: 4px;
      overflow: hidden;
      background: #f0f0f0;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .card-front .photo-container img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      background: #fff;
    }
    
    .card-front .photo-placeholder {
      font-size: 8pt;
      color: #999;
      text-align: center;
      padding: 2mm;
    }
    
    .card-front .details-section {
      display: flex;
      flex-direction: column;
      gap: 1mm;
    }
    
    .card-front .detail-row {
      display: flex;
      align-items: baseline;
      gap: 1mm;
      font-size: 7pt;
      line-height: 1.2;
    }
    
    .card-front .detail-label {
      font-weight: 700;
      color: #555;
      min-width: 18mm;
      text-transform: uppercase;
      font-size: 6pt;
      letter-spacing: 0.2px;
    }
    
    .card-front .detail-value {
      font-weight: 600;
      color: #1a1a1a;
      flex: 1;
      text-transform: uppercase;
      font-size: 7pt;
    }
    
    .card-front .card-footer {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 25mm;
      height: 4mm;
      background: linear-gradient(90deg, #006633 0%, #004d26 100%);
      z-index: 6;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    
    .card-front .footer-text {
      color: #fff;
      font-size: 5pt;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      white-space: nowrap;
    }
    
    .card-front .qr-container {
      position: absolute;
      bottom: 5mm;
      right: 5mm;
      width: 12mm;
      height: 12mm;
      background: #fff;
      border: 1.5px solid #006633;
      border-radius: 3px;
      padding: 1mm;
      z-index: 7;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
    
    /* ============================================
       BACK CARD STYLES
       ============================================ */
    
    .card-back .card-background {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, 
        rgba(240, 248, 255, 0.9) 0%, 
        rgba(230, 240, 250, 0.95) 50%, 
        rgba(220, 235, 245, 0.9) 100%);
      z-index: 1;
    }
    
    .card-back .green-bar {
      position: absolute;
      top: 0;
      right: 0;
      width: 6mm;
      height: 100%;
      background: linear-gradient(180deg, #006633 0%, #004d26 100%);
      z-index: 4;
    }
    
    .card-back .card-content {
      position: relative;
      z-index: 5;
      padding: 5mm;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    .card-back .top-section {
      text-align: center;
    }
    
    .card-back .card-title {
      font-size: 9pt;
      font-weight: 900;
      color: #006633;
      letter-spacing: 0.5px;
      margin-bottom: 2mm;
      text-transform: uppercase;
    }
    
    .card-back .terms-section {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 1.5mm;
    }
    
    .card-back .term-item {
      display: flex;
      align-items: flex-start;
      gap: 2mm;
      font-size: 6pt;
      line-height: 1.3;
      color: #333;
    }
    
    .card-back .term-number {
      font-weight: 900;
      color: #006633;
      min-width: 3mm;
    }
    
    .card-back .term-text {
      flex: 1;
      text-align: justify;
    }
    
    .card-back .verification-section {
      display: grid;
      grid-template-columns: 1fr 15mm;
      gap: 3mm;
      align-items: center;
      margin-top: 2mm;
    }
    
    .card-back .contact-info {
      font-size: 5.5pt;
      line-height: 1.4;
      color: #555;
    }
    
    .card-back .contact-label {
      font-weight: 700;
      color: #006633;
      text-transform: uppercase;
      font-size: 5pt;
      letter-spacing: 0.3px;
    }
    
    .card-back .qr-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    
    .card-back .qr-container {
      width: 15mm;
      height: 15mm;
      background: #fff;
      border: 2px solid #006633;
      border-radius: 3px;
      padding: 1mm;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .card-back .qr-label {
      text-align: center;
      font-size: 5pt;
      font-weight: 700;
      color: #006633;
      margin-top: 1mm;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .card-back .card-footer {
      text-align: center;
      padding-top: 2mm;
      border-top: 1px solid #006633;
    }
    
    .card-back .footer-text {
      font-size: 5pt;
      font-weight: 700;
      color: #006633;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    /* QR Code sizing */
    #cardFrontQr, #cardBackQr {
      width: 100% !important;
      height: 100% !important;
    }
    
    #cardFrontQr img, #cardBackQr img {
      width: 100% !important;
      height: 100% !important;
    }
    
    /* Screen preview mode (not for printing) */
    @media screen {
      body {
        background: #2c3e50;
        padding: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
      }
      
      .print-container {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        justify-content: center;
      }
      
      .card-page {
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border-radius: 8px;
      }
      
      .card-page::after {
        content: attr(data-label);
        position: absolute;
        top: -30px;
        left: 0;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
      }
    }
    
    /* Print-specific styles */
    @media print {
      body {
        background: #fff;
        padding: 0;
      }
      
      .print-container {
        display: block;
      }
      
      .card-page {
        box-shadow: none;
        border-radius: 0;
      }
      
      .card-page::after {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="print-container">
    
    <!-- FRONT SIDE (Page 1) -->
    <div class="card-page card-front" data-label="FRONT SIDE">
      <div class="card-background"></div>
      <div class="wave-pattern"></div>
      <div class="green-bar"></div>
      <div class="flag-section">
        <div class="flag-star"></div>
      </div>
      
      <div class="card-content">
        <div class="card-header">
          <div class="org-name">
            ZIMBABWE <span class="media-word">MEDIA</span> COMMISSION
          </div>
          <div class="logo-section">
            <img src="{{ asset('zmc_logo.png') }}" alt="ZMC Logo" class="zmc-logo" onerror="this.style.display='none'">
          </div>
          <div class="card-type">MEDIA CARD {{ $payload['year'] ?? now()->year }}</div>
        </div>
        
        <div class="main-content">
          <div class="photo-container">
            @if(!empty($payload['photo_url']))
              <img src="{{ $payload['photo_url'] }}" alt="Photo">
            @else
              <div class="photo-placeholder">PHOTO</div>
            @endif
          </div>
          
          <div class="details-section">
            <div class="detail-row">
              <span class="detail-label">Name:</span>
              <span class="detail-value">{{ $payload['full_name'] ?? '—' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Designation:</span>
              <span class="detail-value">{{ $payload['designation'] ?? '—' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Media Org:</span>
              <span class="detail-value">{{ $payload['media_org'] ?? '—' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Nationality:</span>
              <span class="detail-value">{{ $payload['nationality'] ?? '—' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Card No:</span>
              <span class="detail-value">{{ $payload['reg_no'] ?? $payload['ref'] ?? '—' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Valid Until:</span>
              <span class="detail-value">{{ !empty($payload['valid_to']) ? \Illuminate\Support\Carbon::parse($payload['valid_to'])->format('d/m/Y') : '—' }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="qr-container">
        <div id="cardFrontQr"></div>
      </div>
      
      <div class="card-footer">
        <span class="footer-text">FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA</span>
      </div>
    </div>
    
    <!-- BACK SIDE (Page 2) -->
    <div class="card-page card-back" data-label="BACK SIDE">
      <div class="card-background"></div>
      <div class="green-bar"></div>
      
      <div class="card-content">
        <div class="top-section">
          <div class="card-title">Foreign Media Accreditation Card</div>
        </div>
        
        <div class="terms-section">
          <div class="term-item">
            <span class="term-number">1.</span>
            <span class="term-text">This card is the property of the Zimbabwe Media Commission and must be returned upon expiry or on demand.</span>
          </div>
          <div class="term-item">
            <span class="term-number">2.</span>
            <span class="term-text">The bearer must comply with all laws of Zimbabwe and the ZMC Code of Conduct for foreign correspondents.</span>
          </div>
          <div class="term-item">
            <span class="term-number">3.</span>
            <span class="term-text">This card must be carried at all times when undertaking media activities and shown upon request by authorized officials.</span>
          </div>
          <div class="term-item">
            <span class="term-number">4.</span>
            <span class="term-text">Loss, theft, or damage to this card must be reported immediately to the Zimbabwe Media Commission.</span>
          </div>
          <div class="term-item">
            <span class="term-number">5.</span>
            <span class="term-text">This card is valid only for the period stated and must be renewed annually before December 31st.</span>
          </div>
        </div>
        
        <div class="verification-section">
          <div class="contact-info">
            <div class="contact-label">Contact & Verification:</div>
            <div>{{ config('organization.head_office_address') }}</div>
            <div>Tel: {{ config('organization.accounts_phone') }}</div>
            <div>Email: {{ config('organization.public_email') }}</div>
            <div>Web: {{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</div>
          </div>
          
          <div class="qr-wrapper">
            <div class="qr-container">
              <div id="cardBackQr"></div>
            </div>
            <div class="qr-label">Scan to Verify</div>
          </div>
        </div>
        
        <div class="card-footer">
          <div class="footer-text">Authorized by Zimbabwe Media Commission</div>
        </div>
      </div>
    </div>
    
  </div>

  <script>
    window.addEventListener('load', () => {
      // Generate QR codes
      try {
        new QRCode(document.getElementById('cardFrontQr'), {
          text: @json($payload['qr_value'] ?? route('public.home')),
          width: 128,
          height: 128,
          correctLevel: QRCode.CorrectLevel.M
        });
        
        new QRCode(document.getElementById('cardBackQr'), {
          text: @json($payload['qr_value'] ?? route('public.home')),
          width: 128,
          height: 128,
          correctLevel: QRCode.CorrectLevel.M
        });
      } catch(e) {
        console.error('QR Code generation failed:', e);
      }
      
      // Auto-print after QR codes are generated (delay for rendering)
      setTimeout(() => {
        window.print();
      }, 800);
    });
  </script>
</body>
</html>
