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
      background: #fff;
      font-family: 'Arial', 'Helvetica', sans-serif;
    }
    
    /* Print page setup for duplex printing */
    @page {
      size: 85.6mm 53.98mm;
      margin: 0;
    }
    
    /* Card wrapper */
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
       FRONT CARD WITH BACKGROUND IMAGE OVERLAY
       ============================================ */
    
    .card-front .card-background-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
    }
    
    .card-front .card-background-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    /* Vertical card number on the left - positioned ON the green bar */
    .card-front .vertical-card-number {
      position: absolute;
      left: 3mm;  /* Centered on the green bar */
      top: 50%;
      transform: translateY(-50%) rotate(-90deg);
      transform-origin: center;
      font-size: 8pt;
      font-weight: 900;
      color: #fff;
      letter-spacing: 0.8px;
      z-index: 10;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.4);
      white-space: nowrap;
    }
    
    /* Photo positioning */
    .card-front .photo-container {
      position: absolute;
      top: 17mm;
      right: 8mm;
      width: 20mm;
      height: 24mm;
      border: 1.5px solid #333;
      border-radius: 2px;
      overflow: hidden;
      background: #fff;
      z-index: 6;
    }
    
    .card-front .photo-container img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      background: #fff;
    }
    
    .card-front .photo-placeholder {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 7pt;
      color: #999;
      background: #f0f0f0;
    }
    
    /* Information section */
    .card-front .info-section {
      position: absolute;
      top: 18mm;
      left: 8mm;
      right: 30mm;
      z-index: 6;
    }
    
    .card-front .info-row {
      margin-bottom: 2mm;
      font-size: 8pt;
      line-height: 1.3;
      color: #000;
    }
    
    .card-front .info-label {
      font-weight: 700;
      text-transform: uppercase;
      display: inline-block;
      min-width: 12mm;
      font-size: 7pt;
    }
    
    .card-front .info-value {
      font-weight: 900;
      text-transform: uppercase;
      display: inline-block;
      font-size: 9pt;
    }
    
    .card-front .name-row .info-label {
      font-size: 8pt;
      font-weight: 900;
    }
    
    .card-front .name-row .info-value {
      font-size: 10pt;
      font-weight: 900;
      letter-spacing: 0.3px;
    }
    
    /* QR Code removed from front - only on back */
    
    /* ============================================
       BACK CARD - SIMPLE PROFESSIONAL LAYOUT
       ============================================ */
    
    .card-back .card-background {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #d8d8d8;
      z-index: 1;
    }
    
    .card-back .card-content {
      position: relative;
      z-index: 5;
      padding: 4mm 6mm;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      text-align: center;
    }
    
    .card-back .tagline {
      font-size: 7.5pt;
      font-weight: 700;
      color: #000;
      line-height: 1.3;
      margin-bottom: 3mm;
    }
    
    .card-back .main-title {
      font-size: 10pt;
      font-weight: 900;
      color: #000;
      margin-bottom: 2mm;
    }
    
    .card-back .address-section {
      font-size: 7pt;
      color: #000;
      line-height: 1.4;
      margin-bottom: 2mm;
    }
    
    .card-back .contact-line {
      font-size: 7pt;
      color: #000;
      margin-bottom: 1mm;
    }
    
    .card-back .email-line {
      font-size: 7pt;
      font-weight: 700;
      color: #000;
      margin-bottom: 3mm;
    }
    
    .card-back .concern-title {
      font-size: 9pt;
      font-weight: 900;
      color: #000;
      margin-bottom: 2mm;
    }
    
    .card-back .concern-text {
      font-size: 6.5pt;
      color: #000;
      line-height: 1.4;
      text-align: center;
      margin-bottom: 3mm;
    }
    
    .card-back .website {
      font-size: 7pt;
      font-weight: 700;
      color: #000;
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
    
    /* Screen preview mode */
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
    
    <!-- FRONT SIDE (Page 1) - WITH BACKGROUND IMAGE -->
    <div class="card-page card-front" data-label="FRONT SIDE">
      <div class="card-background-image">
        <img src="{{ asset('card_foreign_2027.jpg') }}" alt="Card Background">
      </div>
      
      <div class="vertical-card-number">
        {{ $payload['reg_no'] ?? $payload['ref'] ?? '—' }}
      </div>
      
      <div class="photo-container">
        @if(!empty($payload['photo_url']))
          <img src="{{ $payload['photo_url'] }}" alt="Photo">
        @else
          <div class="photo-placeholder">PHOTO</div>
        @endif
      </div>
      
      <div class="info-section">
        <div class="info-row name-row">
          <span class="info-label">NAME:</span>
          <span class="info-value">{{ $payload['full_name'] ?? '—' }}</span>
        </div>
        
        <div class="info-row">
          <span class="info-label">I.D:</span>
          <span class="info-value">{{ $payload['id_number'] ?? '—' }}</span>
        </div>
        
        <div class="info-row">
          <span class="info-label">ORG:</span>
          <span class="info-value">{{ $payload['media_org'] ?? '—' }}</span>
        </div>
        
        <div class="info-row">
          <span class="info-label">Category:</span>
          <span class="info-value">{{ $payload['category'] ?? '—' }}</span>
        </div>
        
        <div class="info-row">
          <span class="info-label">Valid till:</span>
          <span class="info-value">{{ !empty($payload['valid_to']) ? \Illuminate\Support\Carbon::parse($payload['valid_to'])->format('d/m/Y') : '—' }}</span>
        </div>
      </div>
    </div>
    
    <!-- BACK SIDE (Page 2) - SIMPLE PROFESSIONAL LAYOUT -->
    <div class="card-page card-back" data-label="BACK SIDE">
      <div class="card-background"></div>
      
      <div class="card-content">
        <div class="tagline">
          ZMC - promoting ethical and professional conduct<br>
          by all media practitioners.
        </div>
        
        <div class="main-title">
          Zimbabwe Media Commission
        </div>
        
        <div class="address-section">
          {{ config('organization.registered_address') }}
        </div>
        
        <div class="contact-line">
          Tel: {{ config('organization.public_phone') }}
        </div>
        
        <div class="contact-line">
          Fax: 253639
        </div>
        
        <div class="email-line">
          Email: {{ config('organization.accreditation_email') }}
        </div>
        
        <div class="concern-title">
          To Whom it May Concern
        </div>
        
        <div class="concern-text">
          The holder of this card is accredited with the<br>
          Zimbabwe Media Commission for the duration shown.<br>
          Please accord him/her every assistance in the<br>
          discharge of his/her duties.
        </div>
        
        <div class="website">
          www.mediacommission.co.zw
        </div>
      </div>
    </div>
    
  </div>

  <script>
    window.addEventListener('load', () => {
      // QR code removed from front - card looks cleaner
      // Auto-print
      setTimeout(() => {
        window.print();
      }, 500);
    });
  </script>
</body>
</html>
