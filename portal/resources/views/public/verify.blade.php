<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZMC - Document Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .verify-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .status-badge {
            font-size: 1.2rem;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .info-grid {
            text-align: left;
            margin-top: 1.5rem;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #edf2f7;
        }
        .info-label {
            color: #718096;
            font-weight: 500;
        }
        .info-value {
            color: #2d3748;
            font-weight: 700;
        }
        .logo-section img {
            height: 60px;
            margin-bottom: 1rem;
        }
    </style>
  @include('layouts.favicon')
</head>
<body>

<div class="verify-card">
    <div class="logo-section">
        <img src="{{ asset('zmc_logo.png') }}" alt="ZMC Logo">
        <h4 class="fw-bold">Zimbabwe Media Council</h4>
        <p class="text-muted small">Official Document Verification</p>
    </div>

    @if(isset($error))
        <div class="alert alert-danger">
            <i class="ri-error-warning-line me-2"></i> {{ $error }}
        </div>
        <p class="mt-3">The QR code you scanned could not be validated.</p>
    @else
        @php
            $status = strtolower($record->status ?? 'unknown');
            $isActive = in_array($status, ['active', 'issued'], true);
            $isAccreditation = ($type === 'accreditation');
            $applicationData = $isAccreditation && $record->application
                ? (array) ($record->application->form_data ?? [])
                : [];
            $holderName = $isAccreditation
                ? ($record->full_name ?: ($record->holder->name ?? 'Media Practitioner'))
                : ($record->entity_name ?? 'Media House');
            $organisation = $isAccreditation
                ? (($record->organisation ?: ($applicationData['employer_name'] ?? '')) ?: 'Freelance')
                : null;
            $category = $isAccreditation
                ? (($record->category ?: ($record->application->accreditation_category_code ?? '')) ?: '—')
                : null;
            $designation = $isAccreditation
                ? (($record->designation ?: ($applicationData['designation'] ?? '')) ?: 'Media Practitioner')
                : null;
        @endphp

        <div class="status-badge {{ $isActive ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
            <i class="{{ $isActive ? 'ri-checkbox-circle-line' : 'ri-close-circle-line' }} me-2"></i>
            {{ strtoupper($status) }}
        </div>

        <h5 class="fw-bold mb-0">
            {{ $holderName }}
        </h5>
        <p class="text-muted small">{{ $isAccreditation ? 'Accredited Media Practitioner' : 'Registered Media House' }}</p>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">{{ $isAccreditation ? 'Certificate No' : 'Registration No' }}</span>
                <span class="info-value">{{ $isAccreditation ? $record->certificate_no : $record->registration_no }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Issued At</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($record->issued_at)->format('d M Y') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Expires At</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($record->expires_at)->format('d M Y') }}</span>
            </div>
            @if($isAccreditation)
                <div class="info-item">
                    <span class="info-label">Organisation</span>
                    <span class="info-value">{{ $organisation }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Designation</span>
                    <span class="info-value">{{ $designation }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Category</span>
                    <span class="info-value">{{ $category }}</span>
                </div>
            @endif
        </div>

        <div class="mt-4 text-muted small">
            <i class="ri-shield-check-line me-1"></i> This is an authentic ZMC document.
        </div>
    @endif
</div>

</body>
</html>
