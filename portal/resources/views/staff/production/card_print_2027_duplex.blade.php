@php
  $fd = is_array($application->form_data) ? $application->form_data : (json_decode($application->form_data ?? '[]', true) ?: []);
  $isForeign = strtoupper((string)($payload['scope'] ?? 'LOCAL')) === 'FOREIGN';
  $bgImage = asset($isForeign ? 'card_foreign_2027.jpg' : 'card_local_2027.jpg');
  $fullName = strtoupper((string)($payload['full_name'] ?? $payload['name'] ?? $application->applicant?->name ?? '—'));
  $fallbackId = $isForeign
    ? data_get($fd, 'passport_no', data_get($fd, 'passport_number', data_get($fd, 'id_number', '—')))
    : data_get($fd, 'national_reg_no', data_get($fd, 'national_id', data_get($fd, 'id_number', '—')));
  $idNumber = strtoupper((string)($payload['id_number'] ?? $fallbackId));
  $organisation = strtoupper((string)($payload['media_org'] ?? $payload['organisation'] ?? data_get($fd, 'employer_name', 'Freelance')));
  if ($organisation === '' || $organisation === '—') $organisation = 'FREELANCE';
  $category = strtoupper((string)($payload['category'] ?? $application->accreditation_category_code ?? data_get($fd, 'accreditation_category_code', '—')));
  $cardNumber = strtoupper((string)($payload['reg_no'] ?? $payload['accreditation_id'] ?? $payload['ref'] ?? $application->reference ?? '—'));
  $validFrom = !empty($payload['valid_from']) ? \Carbon\Carbon::parse($payload['valid_from'])->format('d M Y') : '—';
  $validTo = !empty($payload['valid_to']) ? \Carbon\Carbon::parse($payload['valid_to'])->format('d M Y') : '—';
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Media Card 2027 - {{ $application->reference }}</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    @page{size:85.6mm 53.98mm;margin:0}
    html,body{width:100%;background:#fff;font-family:Arial,Helvetica,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .print-container{width:85.6mm}
    .card-page{position:relative;width:85.6mm;height:53.98mm;min-width:85.6mm;min-height:53.98mm;overflow:hidden;page-break-after:always;break-after:page;page-break-inside:avoid;break-inside:avoid;background:#fff}
    .card-page:last-child{page-break-after:avoid;break-after:auto}
    .card-front{background:url('{{ $bgImage }}') center center/100% 100% no-repeat}
    .front-photo{position:absolute;left:4.5%;top:43%;width:22%;height:38%;background:#fff;border:0.3mm solid rgba(0,0,0,.2);overflow:hidden;z-index:3}
    .front-photo img{display:block;width:100%;height:100%;object-fit:contain;background:#fff}
    .photo-placeholder{height:100%;display:flex;align-items:center;justify-content:center;font-size:2.2mm;color:#777;font-weight:700;text-align:center}
    .front-details{position:absolute;left:29%;bottom:22%;width:66%;color:#101010;z-index:3;font-size:2.45mm;line-height:1.18;font-weight:700}
    .front-row{display:flex;align-items:baseline;white-space:nowrap;margin-bottom:1.15mm}
    .front-label{min-width:14mm;font-weight:900}
    .front-value{font-weight:800;max-width:37mm;overflow:hidden;text-overflow:ellipsis}
    .front-row.name .front-value{font-size:2.6mm}
    .front-valid{margin-top:.5mm;font-size:2.25mm;font-weight:800;white-space:nowrap}
    .front-valid strong{font-weight:900}
    /* Keep the accreditation number clear of the LOCAL/FOREIGN MEDIA badge. */
    .front-serial{position:absolute;left:45%;right:auto;bottom:4.7%;color:#111;font-size:2.15mm;font-weight:900;letter-spacing:.12mm;z-index:4;text-shadow:none;white-space:nowrap}

    .card-back{background:#f7fafc;border:0.35mm solid #12335b;padding:0}
    .back-top{height:15mm;background:#000;color:#fff;display:flex;align-items:center;justify-content:space-between;padding:2.4mm 5mm 2.1mm 5mm;border-bottom:1mm solid #e0b500}
    .back-brand{font-size:4.2mm;line-height:1.08;font-weight:900;letter-spacing:.2mm;white-space:nowrap}.back-brand span{color:#f1c40f;font-size:inherit;line-height:inherit;font-weight:inherit;letter-spacing:inherit}
    .back-tagline{max-width:43mm;text-align:right;color:#f4cd44;font-size:2.35mm;line-height:1.12;font-weight:800}
    .back-body{position:relative;height:38.6mm;padding:3.6mm 5mm;color:#16345b}
    .back-copy{width:53mm;height:auto;overflow:visible}.back-copy p{font-size:2.4mm;line-height:1.2;margin-bottom:.65mm}.back-legal{position:absolute;left:5mm;bottom:5mm;width:53mm;color:#16345b}.back-legal .concern{font-size:2.55mm;font-weight:900;margin-bottom:.8mm}.back-legal p{font-size:1.85mm;line-height:1.22;margin:0}
    .qr-box{position:absolute;right:4.4mm;top:4mm;width:22mm;height:22mm;background:#fff;border:.5mm solid #d6a900;border-radius:2.5mm;padding:1.1mm;display:flex;align-items:center;justify-content:center;overflow:hidden}.qr-box>div{width:100%;height:100%;display:flex;align-items:center;justify-content:center;overflow:hidden}.qr-box canvas,.qr-box img{display:block;width:100% !important;height:100% !important;max-width:100%;max-height:100%;object-fit:contain}.qr-label{position:absolute;right:4.7mm;top:27mm;width:21.5mm;text-align:center;font-size:2mm;line-height:1;color:#16345b;font-weight:900}
    .back-url{position:absolute;left:5mm;right:5mm;bottom:1.7mm;text-align:center;font-size:2.4mm;font-weight:900;color:#16345b}
    @media screen{body{display:flex;justify-content:center;padding:20px;background:#2c3e50}.print-container{box-shadow:0 4px 18px rgba(0,0,0,.3)}}
    @media print{
      html,body{width:85.6mm;height:auto;margin:0;padding:0;background:#fff;overflow:visible}
      .print-container{width:85.6mm;margin:0;padding:0}
      .card-page{box-shadow:none}
    }
  </style>
</head>
<body>
  <div class="print-container">
    <div class="card-page card-front">
      <div class="front-photo">@if(!empty($payload['photo_url']))<img src="{{ $payload['photo_url'] }}" alt="Photo">@else<div class="photo-placeholder">PASSPORT<br>PHOTO</div>@endif</div>
      <div class="front-details">
        <div class="front-row name"><span class="front-label">NAME:</span><span class="front-value">{{ $fullName }}</span></div>
        <div class="front-row"><span class="front-label">I.D:</span><span class="front-value">{{ $idNumber }}</span></div>
        <div class="front-row"><span class="front-label">ORG:</span><span class="front-value">{{ $organisation }}</span></div>
        <div class="front-row"><span class="front-label">Category:</span><span class="front-value">{{ $category }}</span></div>
        <div class="front-valid"><strong>Valid:</strong> {{ $validFrom }} &nbsp; <strong>To:</strong> {{ $validTo }}</div>
      </div>
      <div class="front-serial">{{ $cardNumber }}</div>
    </div>
    <div class="card-page card-back">
      <div class="back-top"><div class="back-brand">ZIMBABWE <span>MEDIA</span><br>COMMISSION</div><div class="back-tagline">ZMC - promoting ethical and professional conduct<br>by all media practitioner</div></div>
      <div class="back-body">
        <div class="back-copy"><p>{{ config('organization.registered_address') }}</p><p>Tel: {{ config('organization.public_phone', '253509/10 or 253572/5/6') }}</p><p>Fax: 253639</p><p>Email: info@zmc.org.zw</p></div>
        <div class="back-legal"><div class="concern">To Whom it May Concern</div><p>The holder of this card is accredited with the Zimbabwe Media Commission for the duration shown. Please accord him/her every assistance in the discharge of his/her duties.</p></div>
        <div class="qr-box"><div id="cardQr"></div></div><div class="qr-label">SCAN TO VERIFY</div><div class="back-url">https://zmc.org.zw</div>
      </div>
    </div>
  </div>
  <script>window.addEventListener('load',function(){new QRCode(document.getElementById('cardQr'),{text:@json($payload['qr_value'] ?? route('public.home')),width:120,height:120,correctLevel:QRCode.CorrectLevel.M});setTimeout(function(){window.print()},450)});</script>
</body>
</html>
