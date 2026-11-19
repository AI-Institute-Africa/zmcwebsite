<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>Print Foreign Media Card (Back) - {{ $application->reference }}</title>
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
    
    /* Background */
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
    
    /* Green Side Bar (Right on back) */
    .green-bar {
      position: absolute;
      top: 0;
      right: 0;
      width: 6mm;
      height: 100%;
      background: linear-gradient(180deg, #006633 0%, #004d26 100%);
      z-index: 4;
    }
    
    /* Content Container */
    .card-content {
      position: relative;
      z-index: 5;
      padding: 5mm;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    /* Top Section */
    .top-section {
      text-align: center;
    }
    
    .card-title {
      font-size: 9pt;
      font-weight: 900;
      color: #006633;
      letter-spacing: 0.5px;
      margin-bottom: 2mm;
      text-transform: uppercase;
    }
    
    /* Terms and Conditions */
    .terms-section {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 1.5mm;
    }
    
    .term-item {
      display: flex;
      align-items: flex-start;
      gap: 2mm;
      font-size: 6pt;
      line-height: 1.3;
      color: #333;
    }
    
    .term-number {
      font-weight: 900;
      color: #006633;
      min-width: 3mm;
    }
    
    .term-text {
      flex: 1;
      text-align: justify;
    }
    
    /* QR and Verification Section */
    .verification-section {
      display: grid;
      grid-template-columns: 1fr 15mm;
      gap: 3mm;
      align-items: center;
      margin-top: 2mm;
    }
    
    .contact-info {
      font-size: 5.5pt;
      line-height: 1.4;
      color: #555;
    }
    
    .contact-label {
      font-weight: 700;
      color: #006633;
      text-transform: uppercase;
      font-size: 5pt;
      letter-spacing: 0.3px;
    }
    
    .qr-container {
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
    
    #cardBackQr {
      width: 100% !important;
      height: 100% !important;
    }
    
    #cardBackQr img {
      width: 100% !important;
      height: 100% !important;
    }
    
    .qr-label {
      text-align: center;
      font-size: 5pt;
      font-weight: 700;
      color: #006633;
      margin-top: 1mm;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    /* Footer */
    .card-footer {
      text-align: center;
      padding-top: 2mm;
      border-top: 1px solid #006633;
    }
    
    .footer-text {
      font-size: 5pt;
      font-weight: 700;
      color: #006633;
      text-transform: uppercase;
      letter-spacing: 1px;
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
    
    <!-- Green Side Bar -->
    <div class="green-bar"></div>
    
    <!-- Card Content -->
    <div class="card-content">
      <!-- Top Section -->
      <div class="top-section">
        <div class="card-title">Foreign Media Accreditation Card</div>
      </div>
      
      <!-- Terms and Conditions -->
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
      
      <!-- Verification Section -->
      <div class="verification-section">
        <div class="contact-info">
          <div class="contact-label">Contact & Verification:</div>
          <div>{{ config('organization.head_office_address') }}</div>
          <div>Tel: {{ config('organization.accounts_phone') }}</div>
          <div>Email: {{ config('organization.public_email') }}</div>
          <div>Web: {{ parse_url(config('app.website_url'), PHP_URL_HOST) }}</div>
        </div>
        
        <div>
          <div class="qr-container">
            <div id="cardBackQr"></div>
          </div>
          <div class="qr-label">Scan to Verify</div>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="card-footer">
        <div class="footer-text">Authorized by Zimbabwe Media Commission</div>
      </div>
    </div>
  </div>

  <script>
    window.addEventListener('load', () => {
      try {
        new QRCode(document.getElementById('cardBackQr'), {
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
