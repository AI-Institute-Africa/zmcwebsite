<!doctype html>
@php
    $certificateCategoryMap = \App\Models\Application::massMediaCategories();
    $certificateCategory = trim((string) ($payload['category'] ?? ''));
    $certificateCategoryDisplay = $certificateCategoryMap[$certificateCategory] ?? ($certificateCategory ?: '—');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ZMC Certificate – {{ $application->reference }}</title>
    <style>
        /* ── Page ────────────────────────────────────────────────────────── */
        @page { size: A4 portrait; margin: 0; }
        html, body {
            margin: 0; padding: 0;
            background: #fff;
            font-family: 'Georgia', 'Times New Roman', serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Certificate sheet ───────────────────────────────────────────── */
        .cert-page {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
            background: #fbf8ee;
            color: #1a3a5c;
            border: 0.7mm solid #c9a86c;
            box-shadow: inset 0 0 0 1.2mm rgba(255,255,255,.55);
            box-sizing: border-box;
        }

        /* ── Horizontal text watermark ───────────────────────────────────── */
        .text-wm {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            overflow: hidden;
        }
        .wm-row {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            font-size: 9px;
            line-height: 13px;
            color: rgba(36, 67, 84, 0.07);
            text-transform: uppercase;
            letter-spacing: 3px;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: 700;
            padding: 0 5mm;
        }

        /* ── Zimbabwe flag background watermark ─────────────────────────── */
        .flag-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            display: none;
        }
        .flag-bg svg {
            width: 100%;
            height: 100%;
            opacity: 0.06;
        }

        /* ── Central ZMC logo watermark ──────────────────────────────────── */
        .logo-wm {
            position: absolute;
            width: 125mm;
            height: 125mm;
            left: 50%;
            top: 67%;
            transform: translate(-50%, -50%);
            opacity: 0.085;
            z-index: 2;
            pointer-events: none;
            object-fit: contain;
        }

        /* ── Main content ────────────────────────────────────────────────── */
        .cert-body {
            position: relative;
            z-index: 3;
            width: 100%;
            height: 100%;
            padding: 15mm 20mm 13mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── Organisation heading ────────────────────────────────────────── */
        .org-heading {
            font-size: 7.4mm;
            font-weight: bold;
            letter-spacing: .9mm;
            color: #1a3a5c;
            margin: 0 0 1.5mm 0;
            text-align: center;
        }

        /* ── Ornament SVG ────────────────────────────────────────────────── */
        .orn-svg { display: block; width: 100%; }

        /* ── CERTIFICATE heading ─────────────────────────────────────────── */
        .cert-heading {
            font-size: 25mm;
            font-weight: bold;
            letter-spacing: 2mm;
            color: #1a3a5c;
            margin: 0;
            padding: 1.5mm 0;
            line-height: 1;
            text-align: center;
        }

        /* ── Subtitle ────────────────────────────────────────────────────── */
        .cert-subtitle {
            font-size: 5.5mm;
            font-weight: bold;
            font-style: italic;
            color: #1a3a5c;
            margin: 5mm 0 0 0;
            text-align: center;
        }

        /* ── Issued To ───────────────────────────────────────────────────── */
        .issued-section {
            text-align: center;
            margin-top: 10mm;
            width: 100%;
        }
        .issued-label {
            font-size: 5.2mm;
            color: #1a3a5c;
            margin-bottom: 5mm;
        }
        .issued-value {
            font-size: 6.2mm;
            font-weight: bold;
            color: #1a3a5c;
            min-height: 7mm;
            text-align: center;
        }

        /* ── Gold rule ───────────────────────────────────────────────────── */
        .gold-rule {
            width: 100%;
            border: none;
            border-top: 1.5px solid rgba(201, 168, 108, 0.8);
            margin: 4mm 0 0 0;
        }

        /* ── Category ────────────────────────────────────────────────────── */
        .cat-section {
            text-align: center;
            margin-top: 11mm;
            width: 100%;
        }
        .cat-label {
            font-size: 5.2mm;
            font-weight: bold;
            letter-spacing: 1px;
            color: #1a3a5c;
            margin-bottom: 3mm;
        }
        .cat-value {
            display: block;
            width: 74%;
            margin: 0 auto;
            min-height: 8mm;
            border-bottom: 2px dotted rgba(36, 67, 84, 0.65);
            font-size: 5.8mm;
            font-weight: bold;
            text-align: center;
            color: #1a3a5c;
        }

        /* ── Validity dates ──────────────────────────────────────────────── */
        .validity-row {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-top: 12mm;
            font-size: 4.8mm;
            font-weight: bold;
            color: #1a3a5c;
            padding: 0 1mm;
        }
        .date-dots {
            display: inline-block;
            border-bottom: 1.5px dotted rgba(36, 67, 84, 0.6);
            min-width: 38mm;
            padding-bottom: 1mm;
        }

        /* ── Flexible spacer pushes signatures toward bottom ─────────────── */
        .v-spacer { flex: 1; }

        /* ── Signature section ───────────────────────────────────────────── */
        .sig-section {
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 1mm;
            box-sizing: border-box;
        }
        .sig-col-left  { width: 65mm; text-align: center; }
        .sig-col-right { width: 65mm; text-align: center; }
        .sig-line {
            border: none;
            border-top: 1px solid rgba(36, 67, 84, 0.5);
            margin-bottom: 3.5mm;
        }
        .sig-title {
            font-size: 4.4mm;
            font-weight: bold;
            letter-spacing: 0.4px;
            color: #1a3a5c;
            margin-bottom: 3.5mm;
        }
        .sig-date-line {
            font-size: 4.1mm;
            font-weight: bold;
            color: #1a3a5c;
        }

        /* ── Footer: flag (left) · logo (right) ─────────────────────────── */
        .cert-footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 8mm;
        }
        .flag-img {
            width: 48mm;
            height: 28mm;
            display: block;
        }
        .zmc-logo-img {
            width: 39mm;
            height: 39mm;
            object-fit: contain;
            display: block;
        }
    </style>
    <style>
        /* The official supplied certificate artwork is the fixed template. */
        .cert-page {
            background-color: #f3f1e8;
            background-image: url('{{ asset('certificate_reference.png') }}');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 100% 100%;
            border: 0;
            box-shadow: none;
        }
        .inner-border, .text-wm, .logo-wm, .flag-bg { display: none !important; }
        .cert-body {
            display: block;
            position: relative;
            width: 100%;
            height: 100%;
            padding: 0;
        }
        .org-heading, .orn-svg, .cert-heading, .cert-subtitle,
        .issued-label, .cat-label, .sig-section, .cert-footer { display: none; }
        .issued-section {
            position: absolute;
            top: 44.5%;
            left: 12%;
            width: 76%;
            margin: 0;
        }
        .issued-value {
            width: 100%;
            min-height: 0;
            margin: 0;
            border: 0;
            color: #263b4f;
            font-size: 6.1mm;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .cat-section {
            position: absolute;
            top: 52.2%;
            left: 25%;
            width: 50%;
            margin: 0;
        }
        .cat-value {
            width: 100%;
            min-height: 0;
            margin: 0;
            border: 0;
            color: #263b4f;
            font-size: 5.6mm;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .validity-row {
            position: absolute;
            top: 59.7%;
            left: 12%;
            width: 76%;
            height: 8mm;
            margin: 0;
            padding: 0;
            color: #263b4f;
            font-size: 4.4mm;
            line-height: 1.1;
            display: flex;
            align-items: flex-start;
        }
        .validity-row > div { width: 50%; text-align: center; }
        .validity-row strong { display: none; }
        .validity-row > div { position: relative; height: 8mm; }
        .date-dots {
            position: absolute;
            top: -0.5mm;
            left: 45%;
            width: 72%;
            min-width: 0;
            border: 0;
            padding: .5mm 0;
            text-align: center;
            white-space: nowrap;
        }
        .validity-row > div:last-child .date-dots { left: 25%; width: 100%; }
        .cat-value { font-weight: 800; }
        .certificate-qr {
            position: absolute;
            z-index: 6;
            left: 50%;
            bottom: 3.2%;
            transform: translateX(-50%);
            width: 25mm;
            padding: 1.2mm 1.2mm .8mm;
            border: .35mm solid #c9a86c;
            border-radius: 1mm;
            background: rgba(255,255,255,.98);
            text-align: center;
        }
        .certificate-qr #certQr,
        .certificate-qr #certQr img,
        .certificate-qr #certQr canvas { width: 100% !important; height: auto !important; aspect-ratio: 1; display: block; }
        .certificate-qr span { display: block; margin-top: .7mm; color: #1b3855; font: 800 2.2mm/1 Arial, sans-serif; white-space: nowrap; }
        @media screen { .cert-page { box-shadow: 0 10px 30px rgba(0,0,0,.16); } }
    </style>
</head>
<body>
<div class="cert-page">

    {{-- ── Zimbabwe flag full-page background watermark ────────────────── --}}
    <div class="flag-bg">
        <svg viewBox="0 0 300 180" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
            <polygon points="0,0 110,90 0,180" fill="white"/>
            <polygon points="0,8 98,90 0,172" fill="#000"/>
            <polygon points="0,16 86,90 0,164" fill="#f4c430"/>
            <polygon points="0,24 74,90 0,156" fill="#cf1020"/>
            <polygon points="0,32 62,90 0,148" fill="#00682d"/>
            <rect x="62" y="0"   width="238" height="30" fill="#00682d"/>
            <rect x="62" y="30"  width="238" height="20" fill="#f4c430"/>
            <rect x="62" y="50"  width="238" height="20" fill="#cf1020"/>
            <rect x="62" y="70"  width="238" height="20" fill="#000"/>
            <rect x="62" y="90"  width="238" height="20" fill="#000"/>
            <rect x="62" y="110" width="238" height="20" fill="#cf1020"/>
            <rect x="62" y="130" width="238" height="20" fill="#f4c430"/>
            <rect x="62" y="150" width="238" height="30" fill="#00682d"/>
            <polygon points="35,80 39,92 52,92 41,100 45,112 35,104 25,112 29,100 18,92 31,92"
                     fill="#cf1020" stroke="#f4c430" stroke-width="2"/>
        </svg>
    </div>

    {{-- ── Horizontal text watermark (full-width rows) ───────────────── --}}
    <div class="text-wm">
        @for ($i = 0; $i < 27; $i++)
            <div class="wm-row">ZIMBABWE MEDIA COMMISSION &nbsp;&nbsp;&nbsp; ZIMBABWE MEDIA COMMISSION &nbsp;&nbsp;&nbsp; ZIMBABWE MEDIA COMMISSION &nbsp;&nbsp;&nbsp; ZIMBABWE MEDIA COMMISSION &nbsp;&nbsp;&nbsp; ZIMBABWE MEDIA COMMISSION &nbsp;&nbsp;&nbsp; ZIMBABWE MEDIA COMMISSION &nbsp;&nbsp;&nbsp; ZIMBABWE MEDIA COMMISSION</div>
        @endfor
    </div>

    {{-- ── Central ZMC logo watermark ────────────────────────────────── --}}
    <img src="{{ asset('zmc_logo.png') }}" class="logo-wm" alt="">

    {{-- ── Certificate body ───────────────────────────────────────────── --}}
    <div class="cert-body">

        {{-- 1 · Organisation heading --}}
        <div class="org-heading">ZIMBABWE MEDIA COMMISSION</div>

        {{-- 2 · Ornament above CERTIFICATE --}}
        <svg class="orn-svg" viewBox="0 0 580 62" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
            <!-- thin background rules -->
            <line x1="0"   y1="20" x2="580" y2="20" stroke="#c9a86c" stroke-width="0.7" opacity="0.6"/>
            <line x1="0"   y1="42" x2="580" y2="42" stroke="#c9a86c" stroke-width="0.7" opacity="0.6"/>
            <!-- left thick bar -->
            <line x1="0"   y1="31" x2="116" y2="31" stroke="#c9a86c" stroke-width="5.5"/>
            <!-- left scrollwork – 5 diminishing C-scrolls + 2 mini connectors -->
            <path d="M116,31 C120,15 130,10 140,16 C150,22 148,33 141,34 C135,35 133,29 136,26 C139,23 143,26 142,29" fill="none" stroke="#c9a86c" stroke-width="2.4" stroke-linecap="round"/>
            <path d="M140,16 C146,5  158, 8 159,17 C160,26 155,34 148,32" fill="none" stroke="#c9a86c" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M158,16 C164,6  174,9  175,18 C176,27 172,34 166,32" fill="none" stroke="#c9a86c" stroke-width="2"   stroke-linecap="round"/>
            <path d="M174,17 C179,7  189,10 190,18 C191,27 187,33 181,31" fill="none" stroke="#c9a86c" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M189,17 C194,8  203,11 204,19 C205,27 201,33 196,31" fill="none" stroke="#c9a86c" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M203,20 C209,12 216,15 216,22 C216,28 213,32 209,30" fill="none" stroke="#c9a86c" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M215,23 C220,17 225,20 225,26 C225,31 222,33 220,31" fill="none" stroke="#c9a86c" stroke-width="1.2" stroke-linecap="round"/>
            <!-- central medallion -->
            <g transform="translate(290,31)">
                <path d="M-34,0 C-26,-22 26,-22 34,0 C26,22 -26,22 -34,0 Z" fill="none" stroke="#c9a86c" stroke-width="1.9"/>
                <ellipse rx="21" ry="13" fill="none" stroke="#c9a86c" stroke-width="1"/>
                <circle r="7.5" fill="none" stroke="#c9a86c" stroke-width="1.2"/>
                <circle r="3" fill="#c9a86c"/>
                <circle cx="-30" cy="0"   r="3.3" fill="#c9a86c"/>
                <circle cx="30"  cy="0"   r="3.3" fill="#c9a86c"/>
                <circle cx="0"   cy="-18" r="2.2" fill="#c9a86c"/>
                <circle cx="0"   cy="18"  r="2.2" fill="#c9a86c"/>
                <circle cx="-22" cy="-11" r="1.8" fill="#c9a86c"/>
                <circle cx="22"  cy="-11" r="1.8" fill="#c9a86c"/>
                <circle cx="-22" cy="11"  r="1.8" fill="#c9a86c"/>
                <circle cx="22"  cy="11"  r="1.8" fill="#c9a86c"/>
            </g>
            <!-- right thick bar -->
            <line x1="464" y1="31" x2="580" y2="31" stroke="#c9a86c" stroke-width="5.5"/>
            <!-- right scrollwork (x-mirror of left about x=290) -->
            <path d="M464,31 C460,15 450,10 440,16 C430,22 432,33 439,34 C445,35 447,29 444,26 C441,23 437,26 438,29" fill="none" stroke="#c9a86c" stroke-width="2.4" stroke-linecap="round"/>
            <path d="M440,16 C434,5  422, 8 421,17 C420,26 425,34 432,32" fill="none" stroke="#c9a86c" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M422,16 C416,6  406,9  405,18 C404,27 408,34 414,32" fill="none" stroke="#c9a86c" stroke-width="2"   stroke-linecap="round"/>
            <path d="M406,17 C401,7  391,10 390,18 C389,27 393,33 399,31" fill="none" stroke="#c9a86c" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M391,17 C386,8  377,11 376,19 C375,27 379,33 384,31" fill="none" stroke="#c9a86c" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M377,20 C371,12 364,15 364,22 C364,28 367,32 371,30" fill="none" stroke="#c9a86c" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M365,23 C360,17 355,20 355,26 C355,31 358,33 360,31" fill="none" stroke="#c9a86c" stroke-width="1.2" stroke-linecap="round"/>
        </svg>

        {{-- 3 · CERTIFICATE --}}
        <div class="cert-heading">{{ $payload['certificate_title'] ?? 'CERTIFICATE' }}</div>

        {{-- 4 · Ornament below CERTIFICATE (identical design) --}}
        <svg class="orn-svg" viewBox="0 0 580 62" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
            <line x1="0"   y1="20" x2="580" y2="20" stroke="#c9a86c" stroke-width="0.7" opacity="0.6"/>
            <line x1="0"   y1="42" x2="580" y2="42" stroke="#c9a86c" stroke-width="0.7" opacity="0.6"/>
            <line x1="0"   y1="31" x2="116" y2="31" stroke="#c9a86c" stroke-width="5.5"/>
            <path d="M116,31 C120,15 130,10 140,16 C150,22 148,33 141,34 C135,35 133,29 136,26 C139,23 143,26 142,29" fill="none" stroke="#c9a86c" stroke-width="2.4" stroke-linecap="round"/>
            <path d="M140,16 C146,5  158, 8 159,17 C160,26 155,34 148,32" fill="none" stroke="#c9a86c" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M158,16 C164,6  174,9  175,18 C176,27 172,34 166,32" fill="none" stroke="#c9a86c" stroke-width="2"   stroke-linecap="round"/>
            <path d="M174,17 C179,7  189,10 190,18 C191,27 187,33 181,31" fill="none" stroke="#c9a86c" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M189,17 C194,8  203,11 204,19 C205,27 201,33 196,31" fill="none" stroke="#c9a86c" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M203,20 C209,12 216,15 216,22 C216,28 213,32 209,30" fill="none" stroke="#c9a86c" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M215,23 C220,17 225,20 225,26 C225,31 222,33 220,31" fill="none" stroke="#c9a86c" stroke-width="1.2" stroke-linecap="round"/>
            <g transform="translate(290,31)">
                <path d="M-34,0 C-26,-22 26,-22 34,0 C26,22 -26,22 -34,0 Z" fill="none" stroke="#c9a86c" stroke-width="1.9"/>
                <ellipse rx="21" ry="13" fill="none" stroke="#c9a86c" stroke-width="1"/>
                <circle r="7.5" fill="none" stroke="#c9a86c" stroke-width="1.2"/>
                <circle r="3" fill="#c9a86c"/>
                <circle cx="-30" cy="0"   r="3.3" fill="#c9a86c"/>
                <circle cx="30"  cy="0"   r="3.3" fill="#c9a86c"/>
                <circle cx="0"   cy="-18" r="2.2" fill="#c9a86c"/>
                <circle cx="0"   cy="18"  r="2.2" fill="#c9a86c"/>
                <circle cx="-22" cy="-11" r="1.8" fill="#c9a86c"/>
                <circle cx="22"  cy="-11" r="1.8" fill="#c9a86c"/>
                <circle cx="-22" cy="11"  r="1.8" fill="#c9a86c"/>
                <circle cx="22"  cy="11"  r="1.8" fill="#c9a86c"/>
            </g>
            <line x1="464" y1="31" x2="580" y2="31" stroke="#c9a86c" stroke-width="5.5"/>
            <path d="M464,31 C460,15 450,10 440,16 C430,22 432,33 439,34 C445,35 447,29 444,26 C441,23 437,26 438,29" fill="none" stroke="#c9a86c" stroke-width="2.4" stroke-linecap="round"/>
            <path d="M440,16 C434,5  422, 8 421,17 C420,26 425,34 432,32" fill="none" stroke="#c9a86c" stroke-width="2.2" stroke-linecap="round"/>
            <path d="M422,16 C416,6  406,9  405,18 C404,27 408,34 414,32" fill="none" stroke="#c9a86c" stroke-width="2"   stroke-linecap="round"/>
            <path d="M406,17 C401,7  391,10 390,18 C389,27 393,33 399,31" fill="none" stroke="#c9a86c" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M391,17 C386,8  377,11 376,19 C375,27 379,33 384,31" fill="none" stroke="#c9a86c" stroke-width="1.6" stroke-linecap="round"/>
            <path d="M377,20 C371,12 364,15 364,22 C364,28 367,32 371,30" fill="none" stroke="#c9a86c" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M365,23 C360,17 355,20 355,26 C355,31 358,33 360,31" fill="none" stroke="#c9a86c" stroke-width="1.2" stroke-linecap="round"/>
        </svg>

        {{-- 5 · Subtitle --}}
        <div class="cert-subtitle">{{ $payload['certificate_sub'] ?? 'Of Registration For A Mass Media Service' }}</div>

        {{-- 6 · Issued To --}}
        <div class="issued-section">
            <div class="issued-label">Issued To</div>
            <div class="issued-value">{{ $payload['org_name'] ?? '' }}</div>
        </div>

        {{-- 7 · Gold horizontal rule --}}
        <hr class="gold-rule">

        {{-- 8 · Category --}}
        <div class="cat-section">
            <div class="cat-label">CATEGORY:</div>
            <div class="cat-value">{{ $certificateCategoryDisplay }}</div>
        </div>

        {{-- 9 · Validity dates --}}
        <div class="validity-row">
            <div>
                <strong>VALID FROM:</strong>
                <span class="date-dots">{{ $payload['issue_date'] ?? '........../........../ 20........' }}</span>
            </div>
            <div>
                <strong>TO:</strong>
                <span class="date-dots">{{ $payload['valid_until'] ?? '........../........../ 20........' }}</span>
            </div>
        </div>

        {{-- 10 · Flexible spacer (pushes sig section toward bottom ~80%) --}}
        <div class="v-spacer"></div>

        {{-- 11 · Signature section --}}
        <div class="sig-section">
            <div class="sig-col-left">
                <hr class="sig-line">
                <div class="sig-title">COMMISSION CHAIRPERSON</div>
                <div class="sig-date-line">DATE: ............/............/20........</div>
            </div>
            <div class="sig-col-right">
                <hr class="sig-line">
                <div class="sig-title">{{ !empty($payload['right_sign_title']) ? $payload['right_sign_title'] : 'CHIEF EXECUTIVE OFFICER' }}</div>
                <div class="sig-date-line">DATE: ............/............/20........</div>
            </div>
        </div>

        {{-- 12 · Footer: Zimbabwe flag (left) · ZMC circular logo (right) --}}
        <div class="cert-footer">
            {{-- Zimbabwe flag – inline SVG so it renders in both browser and DomPDF --}}
            <svg class="flag-img" viewBox="0 0 300 180" xmlns="http://www.w3.org/2000/svg">
                <!-- White triangle (left chevron) -->
                <polygon points="0,0 110,90 0,180" fill="white"/>
                <!-- Black border of chevron -->
                <polygon points="0,8 98,90 0,172" fill="#000"/>
                <!-- Yellow border -->
                <polygon points="0,16 86,90 0,164" fill="#f4c430"/>
                <!-- Red border -->
                <polygon points="0,24 74,90 0,156" fill="#cf1020"/>
                <!-- Green inner chevron -->
                <polygon points="0,32 62,90 0,148" fill="#00682d"/>
                <!-- Horizontal stripes (right of chevron) -->
                <rect x="62" y="0"   width="238" height="30" fill="#00682d"/>
                <rect x="62" y="30"  width="238" height="20" fill="#f4c430"/>
                <rect x="62" y="50"  width="238" height="20" fill="#cf1020"/>
                <rect x="62" y="70"  width="238" height="20" fill="#000"/>
                <rect x="62" y="90"  width="238" height="20" fill="#000"/>
                <rect x="62" y="110" width="238" height="20" fill="#cf1020"/>
                <rect x="62" y="130" width="238" height="20" fill="#f4c430"/>
                <rect x="62" y="150" width="238" height="30" fill="#00682d"/>
                <!-- Zimbabwe Bird / Star -->
                <polygon points="35,80 39,92 52,92 41,100 45,112 35,104 25,112 29,100 18,92 31,92"
                         fill="#cf1020" stroke="#f4c430" stroke-width="2"/>
            </svg>

            <div style="display: flex; gap: 4mm; align-items: flex-end;">
                {{-- ZMC circular logo --}}
                <img src="{{ asset('zmc_logo.png') }}" class="zmc-logo-img" alt="Zimbabwe Media Commission">
            </div>
        </div>

        <div class="certificate-qr" aria-label="Certificate verification QR code">
            <div id="certQr"></div>
            <span>SCAN TO VERIFY</span>
        </div>

    </div>{{-- /.cert-body --}}
</div>{{-- /.cert-page --}}

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    try {
        new QRCode(document.getElementById('certQr'), {
            text: @json($payload['qr_value'] ?? ''),
            width: 80,
            height: 80,
            correctLevel: QRCode.CorrectLevel.M
        });
    } catch(e) {}
    window.addEventListener('load', () => { setTimeout(() => window.print(), 300); });
</script>
</body>
</html>
