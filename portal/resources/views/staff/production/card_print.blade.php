@php
    $isForeign = strtoupper((string)($payload['scope'] ?? 'LOCAL')) === 'FOREIGN';
    $bgImage   = asset($isForeign ? 'card_foreign_2027.jpg' : 'card_local_2027.jpg');
    $stripColor = $isForeign ? '#1f6f43' : '#1a237e';

    $fullName     = strtoupper($payload['name'] ?? '—');
    $idNumber     = strtoupper($payload['id_number'] ?? ($application->form_data['national_id'] ?? ($application->form_data['passport_number'] ?? '—')));
    $organisation = strtoupper($payload['organisation'] ?? ($payload['media_org'] ?? ($application->form_data['employer_name'] ?? 'Freelance')));
    if ($organisation === '' || $organisation === '—') $organisation = 'FREELANCE';
    $category     = strtoupper($payload['category'] ?? ($application->accreditation_category_code ?? '—'));
    $cardNumber   = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string)(
        $payload['reg_no'] ?? $payload['accreditation_id'] ?? $payload['ref'] ?? $application->reference ?? '—'
    )));

    $validFrom = !empty($payload['valid_from'])
        ? \Carbon\Carbon::parse($payload['valid_from'])->format('d/m/Y')
        : '—';

    $validTo = !empty($payload['valid_to'])
        ? \Carbon\Carbon::parse($payload['valid_to'])->format('d/m/Y')
        : '—';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Card - {{ $application->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: 86mm 54mm; margin: 0; }
        html, body { height: 100%; margin: 0; }
        body {
            display: flex; align-items: center; justify-content: center;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
        }

        .card {
            position: relative;
            width: 86mm; height: 54mm;
            background: url("{{ $bgImage }}") center center / 100% 100% no-repeat;
            overflow: hidden;
        }

        /* Vertical card serial overlay (sits on the colored strip in the image) */
        .card-serial {
            position: absolute;
            left: 33%; right: auto; bottom: 2.2%;
            width: auto; height: auto;
            display: flex; align-items: center; justify-content: center;
            color: #111;
            z-index: 5;
        }
        .card-serial span {
            writing-mode: horizontal-tb; transform: none;
            font-size: 3.4mm; font-weight: 900; letter-spacing: 0.3mm;
            white-space: nowrap;
        }

        /* Photo box on the right */
        .photo-box {
            position: absolute;
            left: 4.5%; top: 43%; right: auto;
            width: 22%; height: 38%;
            background: #fff;
            border: 0.4mm solid rgba(0,0,0,0.18);
            border-radius: 1mm;
            overflow: hidden;
            z-index: 5;
            box-shadow: 0 1mm 2mm rgba(0,0,0,0.15);
        }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .photo-ph {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            color: #777; font-size: 1.8mm; font-weight: 700; text-align: center;
        }

        /* Applicant details block (lower-left, over the wave background) */
        .details {
            position: absolute;
            left: 29%; top: auto; bottom: 22%;
            width: 66%;
            z-index: 5;
            color: #0a0a0a;
        }
        .row {
            display: flex; align-items: baseline;
            font-size: 2.45mm; line-height: 1.18; margin-bottom: 1.15mm;
            white-space: nowrap;
        }
        .row .lbl {
            min-width: 14mm; font-weight: 900; letter-spacing: 0.2mm;
        }
        .row .val {
            font-weight: 700; overflow: hidden; text-overflow: ellipsis;
        }
        .name-row .val { font-size: 2.6mm; max-width: 37mm; }
        .validity {
            margin-top: 0.5mm; font-size: 2.25mm; font-weight: 800;
        }
        .validity strong { font-weight: 900; }

        @media print { body { background: #fff; } }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-serial"><span>{{ $cardNumber }}</span></div>

        <div class="photo-box">
            @if(!empty($payload['photo_url']))
                <img src="{{ $payload['photo_url'] }}" alt="Applicant Photo">
            @else
                <div class="photo-ph">PASSPORT<br>PHOTO</div>
            @endif
        </div>

        <div class="details">
            <div class="row name-row"><span class="lbl">NAME:</span><span class="val">{{ $fullName }}</span></div>
            <div class="row"><span class="lbl">I.D:</span><span class="val">{{ $idNumber }}</span></div>
            <div class="row"><span class="lbl">ORG:</span><span class="val">{{ $organisation }}</span></div>
            <div class="row"><span class="lbl">Category:</span><span class="val">{{ $category }}</span></div>
            <div class="validity">
                <strong>Valid:</strong> {{ $validFrom }} &nbsp;<strong>To:</strong> {{ $validTo }}
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>
</html>
