<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>Print Foreign Media Card - {{ $application->reference }}</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: #f5f5f5;
      font-family: 'Arial', 'Helvetica', sans-serif;
      padding: 20px;
    }
    
    .card-wrapper {
      width: 85.6mm;
      height: 53.98mm;
      position: relative;
      background: #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      border-radius: 8px;
      overflow: hidden;
    }
    
    /* Background Image/Pattern */
    .card-background {
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
    
    /* Wave Pattern Overlay */
    .wave-pattern {
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
    
    /* Zimbabwe Flag Section */
    .flag-section {
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
    
    .flag-star {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 15mm;
      height: 15mm;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><polygon points="50,15 60,40 87,40 66,56 75,82 50,65 25,82 34,56 13,40 40,40" fill="%23FFD700"/><circle cx="50" cy="50" r="8" fill="%23CE1126"/></svg>') center/contain no-repeat;
    }
    
    /* Green Side Bar */
    .green-bar {
      position: absolute;
      top: 0;
      left: 0;
      width: 6mm;
      height: 100%;
      background: linear-gradient(180deg, #006633 0%, #004d26 100%);
      z-index: 4;
    }
    
    /* Content Container */
    .card-content {
      position: relative;
      z-index: 5;
      padding: 3mm 7mm 3mm 8mm;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    /* Header Section */
    .card-header {
      text-align: center;
      margin-bottom: 2mm;
    }
    
    .org-name {
      font-size: 11pt;
      font-weight: 900;
      letter-spacing: 0.5px;
      color: #1a1a1a;
      line-height: 1.1;
      margin-bottom: 1mm;
    }
    
    .org-name .media-word {
      color: #006633;
    }
    
    .logo-section {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2mm;
      margin-bottom: 1mm;
    }
    
    .zmc-logo {
      width: 12mm;
      height: 12mm;
      border-radius: 50%;
      border: 2px solid #006633;
      object-fit: contain;
      background: #fff;
      padding: 1mm;
    }
    
    .card-type {
      font-size: 8pt;
      font-weight: 900;
      color: #1a1a1a;
      letter-spacing: 0.3px;
    }
    
    /* Main Content Area */
    .main-content {
      flex: 1;
      display: grid;
      grid-template-columns: 20mm 1fr;
      gap: 3mm;
      align-items: center;
    }
    
    /* Photo Section */
    .photo-container {
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
    
    .photo-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .photo-placeholder {
      font-size: 8pt;
      color: #999;
      text-align: center;
      padding: 2mm;
    }
    
    /* Details Section */
    .details-section {
      display: flex;
      flex-direction: column;
      gap: 1mm;
    }
    
    .detail-row {
      display: flex;
      align-items: baseline;
      gap: 1mm;
      font-size: 7pt;
      line-height: 1.2;
    }
    
    .detail-label {
      font-weight: 700;
      color: #555;
      min-width: 18mm;
      text-transform: uppercase;
      font-size: 6pt;
      letter-spacing: 0.2px;
    }
    
    .detail-value {
      font-weight: 600;
      color: #1a1a1a;
      flex: 1;
      text-transform: uppercase;
      font-size: 7pt;
    }
    
    /* Footer Section */
    .card-footer {
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
      gap: 15mm;
    }
    
    .footer-text {
      color: #fff;
      font-size: 5pt;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      animation: scroll-left 20s linear infinite;
    }
    
    @keyframes scroll-left {
      0% { transform: translateX(0); }
      100% { transform: translateX(-100%); }
    }
    
    /* QR Code Section */
    .qr-container {
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
    
    #cardQr {
      width: 100% !important;
      height: 100% !important;
    }
    
    #cardQr img {
      width: 100% !important;
      height: 100% !important;
    }
    
    /* Print Styles */
    @media print {
      body {
        background: #fff;
        padding: 0;
      }
      
      .card-wrapper {
        box-shadow: none;
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>
  <div class="card-wrapper">
    <!-- Background -->
    <div class="card-background"></div>
    <div class="wave-pattern"></div>
    
    <!-- Green Side Bar -->
    <div class="green-bar"></div>
    
    <!-- Zimbabwe Flag Section -->
    <div class="flag-section">
      <div class="flag-star"></div>
    </div>
    
    <!-- Card Content -->
    <div class="card-content">
      <!-- Header -->
      <div class="card-header">
        <div class="org-name">
          ZIMBABWE <span class="media-word">MEDIA</span> COMMISSION
        </div>
        
        <div class="logo-section">
          <img src="{{ asset('zmc_logo.png') }}" alt="ZMC Logo" class="zmc-logo" onerror="this.style.display='none'">
        </div>
        
        <div class="card-type">MEDIA CARD {{ $payload['year'] ?? now()->year }}</div>
      </div>
      
      <!-- Main Content -->
      <div class="main-content">
        <!-- Photo -->
        <div class="photo-container">
          @if(!empty($payload['photo_url']))
            <img src="{{ $payload['photo_url'] }}" alt="Photo">
          @else
            <div class="photo-placeholder">PHOTO</div>
          @endif
        </div>
        
        <!-- Details -->
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
            <span class="detail-value">{{ $payload['valid_to'] ?? '—' }}</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- QR Code -->
    <div class="qr-container">
      <div id="cardQr"></div>
    </div>
    
    <!-- Footer -->
    <div class="card-footer">
      <span class="footer-text">FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA · FOREIGN MEDIA</span>
    </div>
  </div>

  <script>
    window.addEventListener('load', () => {
      try {
        new QRCode(document.getElementById('cardQr'), {
          text: @json($payload['qr_value'] ?? route('public.home')),
          width: 128,
          height: 128,
          correctLevel: QRCode.CorrectLevel.M
        });
      } catch(e) {
        console.error('QR Code generation failed:', e);
      }
      
      // Auto-print after QR code is generated
      setTimeout(() => {
        window.print();
      }, 500);
    });
  </script>
</body>
</html>
