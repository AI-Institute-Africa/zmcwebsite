<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print Certificate - {{ $application->reference }}</title>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background:#fff;
    padding:0;
    font-family:"Times New Roman", serif;
}

/* A4 Certificate Container */
.certificate{
    position:relative;
    width:794px;
    height:1123px;
    margin:auto;
    background:#fff;
    overflow:hidden;
}

/* Background Certificate Template */
.certificate-bg{
    width:100%;
    height:100%;
    object-fit:contain;
    display:block;
}

/* Common Field Styling */
.field{
    position:absolute;
    color:#0d2b4f;
    font-weight:700;
    text-align:center;
    z-index:10;
    line-height:1.2;
}

/* ==========================
   ISSUED TO
   ========================== */

.issued-to{
    top:44.4%;
    left:17%;
    width:66%;
    font-size:22px;
    text-transform:uppercase;
    letter-spacing:.5px;
    word-break:break-word;
}

/* ==========================
   CATEGORY
   ========================== */

.category{
    top:54.0%;
    left:22%;
    width:56%;
    font-size:18px;
    text-transform:uppercase;
}

/* ==========================
   REGISTRATION NUMBER
   ========================== */

.reg-number{
    top:64.2%;
    left:22%;
    width:56%;
    font-size:20px;
    letter-spacing:.5px;
}

/* ==========================
   VALID FROM
   ========================== */

.valid-from{
    top:73.1%;
    left:24%;
    width:14%;
    font-size:17px;
}

/* ==========================
   VALID TO
   ========================== */

.valid-to{
    top:73.1%;
    left:64%;
    width:14%;
    font-size:17px;
}

/* ==========================
   CHAIRPERSON DATE
   ========================== */

.chair-date{
    top:83.5%;
    left:28%;
    width:15%;
    font-size:17px;
    text-align:left;
}

/* ==========================
   CEO DATE
   ========================== */

.ceo-date{
    top:83.5%;
    left:73%;
    width:15%;
    font-size:17px;
    text-align:left;
}

/* ==========================
   QR CODE
   ========================== */

.qr-container{
    position:absolute;
    bottom:65px;
    right:48px;
    width:92px;
    height:92px;
    z-index:20;
}

#certQr{
    width:100%;
    height:100%;
}

#certQr canvas,
#certQr img{
    width:100% !important;
    height:100% !important;
}

/* Screen Preview */
@media screen{
    
    body{
        background:#2c3e50;
        padding:20px;
    }
    
    .certificate{
        box-shadow:0 4px 12px rgba(0,0,0,.3);
    }
}

/* Printing */
@media print{
    
    body{
        background:white;
        padding:0;
    }
    
    .certificate{
        box-shadow:none;
    }
    
    @page{
        size:A4 portrait;
        margin:0;
    }
}

</style>
</head>

<body>

<div class="certificate">
    
    <!-- Certificate Background -->
    <img
        src="{{ asset('CertificateBackground.png') }}"
        class="certificate-bg"
        alt="Certificate Background"
    >
    
    <!-- Dynamic Fields -->
    
    <div class="field issued-to">
        {{ strtoupper($payload['company_name'] ?? '—') }}
    </div>
    
    <div class="field category">
        {{ strtoupper($payload['category'] ?? '—') }}
    </div>
    
    <div class="field reg-number">
        {{ $payload['reg_no'] ?? '—' }}
    </div>
    
    <div class="field valid-from">
        {{ $payload['valid_from'] ?? '—' }}
    </div>
    
    <div class="field valid-to">
        {{ $payload['valid_to'] ?? '—' }}
    </div>
    
    <div class="field chair-date">
        {{ $payload['issue_date'] ?? '—' }}
    </div>
    
    <div class="field ceo-date">
        {{ $payload['issue_date'] ?? '—' }}
    </div>
    
    <!-- QR Code -->
    <div class="qr-container">
        <div id="certQr"></div>
    </div>

</div>

<script>

window.addEventListener("load", () => {
    
    // Generate QR Code
    try {
        new QRCode(
            document.getElementById("certQr"),
            {
                text: @json($payload['qr_value'] ?? route('public.home')),
                width: 92,
                height: 92,
                correctLevel: QRCode.CorrectLevel.H
            }
        );
    } catch(e) {
        console.error('QR Code generation failed:', e);
    }
    
    // Auto-print after QR code loads
    setTimeout(() => {
        window.print();
    }, 1000);

});

</script>

</body>
</html>
