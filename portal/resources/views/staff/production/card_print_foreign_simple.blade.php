<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <title>Print Foreign Media Card - {{ $application->reference }}</title>
  
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
    
    @page {
      size: 85.6mm 53.98mm;
      margin: 0;
    }
    
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
    
    /* Front Card */
    .media-card {
      position: relative;
      width: 85.6mm;
      height: 53.98mm;
      overflow: hidden;
    }
    
    .card-bg {
      width: 100%;
      height: 100%;
      position: absolute;
      left: 0;
      top: 0;
      object-fit: contain;
    }
    
    .card-number {
      position: absolute;
      left: 7mm;
      top: 32mm;
      transform: rotate(-90deg);
      transform-origin: left center;
      color: #fff;
      font-size: 11px;
      font-weight: bold;
      z-index: 2;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }
    
    .photo {
      position: absolute;
      right: 5mm;
      bottom: 8mm;
      width: 18mm;
      height: 18mm;
      overflow: hidden;
      border: 1px solid #333;
      background: #f0f0f0;
    }
    
    .photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .photo-placeholder {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 8pt;
      color: #999;
    }
    
    .details {
      position: absolute;
      left: 13mm;
      top: 28mm;
      z-index: 2;
      font-family: Arial, sans-serif;
      font-size: 10px;
      font-weight: bold;
      color: #000;
    }
    
    .details div {
      margin-bottom: 2px;
    }
    
    .dates {
      display: flex;
      gap: 20px;
    }
    
    /* Back Card */
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
      font-size: 6pt;
      font-weight: 700;
      color: #000;
      line-height: 1.3;
      margin-bottom: 2mm;
    }
    
    .card-back .main-title {
      font-size: 8pt;
      font-weight: 900;
      color: #000;
      margin-bottom: 1.5mm;
    }
    
    .card-back .address-section {
      font-size: 6pt;
      color: #000;
      line-height: 1.4;
      margin-bottom: 1.5mm;
    }
    
    .card-back .contact-line {
      font-size: 6pt;
      color: #000;
      margin-bottom: 0.8mm;
    }
    
    .card-back .email-line {
      font-size: 6pt;
      font-weight: 700;
      color: #000;
      margin-bottom: 2mm;
    }
    
    .card-back .concern-title {
      font-size: 7pt;
      font-weight: 900;
      color: #000;
      margin-bottom: 1.5mm;
    }
    
    .card-back .concern-text {
      font-size: 5.5pt;
      color: #000;
      line-height: 1.4;
      text-align: center;
      margin-bottom: 2mm;
    }
    
    .card-back .website {
      font-size: 6pt;
      font-weight: 700;
      color: #000;
    }
    
    .card-back .qr-container {
      position: absolute;
      bottom: 3mm;
      right: 3mm;
      width: 12mm;
      height: 12mm;
      background: #fff;
      border: 1px solid #000;
      border-radius: 2px;
      padding: 1mm;
      z-index: 10;
    }
    
    #cardBackQr {
      width: 100% !important;
      height: 100% !important;
    }
    
    #cardBackQr img {
      width: 100% !important;
      height: 100% !important;
    }
    
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
    
    <!-- FRONT SIDE -->
    <div class="card-page" data-label="FRONT SIDE">
      <div class="media-card">
        <!-- Actual card background -->
        <img src="{{ asset('card_foreign_2027.jpg') }}" class="card-bg" alt="Card Background">
        
        <!-- Dynamic Card Number -->
        <div class="card-number">{{ $payload['reg_no'] ?? $payload['ref'] ?? '—' }}</div>
        
        <!-- Applicant Photo -->
        <div class="photo">
          @if(!empty($payload['photo_url']))
            <img src="{{ $payload['photo_url'] }}" alt="Photo">
          @else
            <div class="photo-placeholder">PHOTO</div>
          @endif
        </div>
        
        <!-- Details -->
        <div class="details">
          <div><strong>NAME:</strong> {{ strtoupper($payload['full_name'] ?? '—') }}</div>
          <div><strong>I.D:</strong> {{ $payload['id_number'] ?? '—' }}</div>
          <div><strong>ORG:</strong> {{ strtoupper($payload['media_org'] ?? '—') }}</div>
          <div><strong>Category:</strong> {{ $payload['category'] ?? '—' }}</div>
          <div class="dates">
            <span>Valid: {{ $payload['valid_from'] ?? '—' }}</span>
            <span>To: {{ $payload['valid_to'] ?? '—' }}</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- BACK SIDE -->
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
      
      <!-- QR Code for verification -->
      <div class="qr-container">
        <div id="cardBackQr"></div>
      </div>
    </div>
    
  </div>

  <script>
    window.addEventListener('load', () => {
      // Generate QR code on back
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
      
      // Auto-print after QR code loads
      setTimeout(() => {
        window.print();
      }, 800);
    });
  </script>
</body>
</html>
