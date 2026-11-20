@extends('layouts.portal')

@section('title', 'Payment Successful')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center py-5">
                    {{-- Success Icon --}}
                    <div class="mb-4">
                        <div class="success-checkmark">
                            <div class="check-icon">
                                <span class="icon-line line-tip"></span>
                                <span class="icon-line line-long"></span>
                                <div class="icon-circle"></div>
                                <div class="icon-fix"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Success Message --}}
                    <h1 class="text-success fw-bold mb-3">
                        <i class="ri-checkbox-circle-line"></i> Payment Successful!
                    </h1>
                    
                    <p class="lead text-muted mb-4">
                        {{ $message }}
                    </p>

                    {{-- Application Details --}}
                    <div class="alert alert-light border mb-4">
                        <div class="row text-start">
                            <div class="col-6">
                                <small class="text-muted d-block">Application Reference</small>
                                <strong class="d-block">{{ $application->reference }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Payment Status</small>
                                <strong class="d-block text-success">
                                    <i class="ri-check-line"></i> Paid
                                </strong>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="row text-start">
                            <div class="col-6">
                                <small class="text-muted d-block">Application Status</small>
                                <strong class="d-block">{{ \App\Models\Application::statusLabel($application->status) }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Payment Date</small>
                                <strong class="d-block">{{ $application->payment_paid_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Next Steps --}}
                    <div class="alert alert-info border-0 text-start mb-4">
                        <h2 class="fw-bold mb-2">
                            <i class="ri-information-line"></i> What Happens Next?
                        </h2>
                        <ul class="mb-0 small">
                            <li>Your payment has been verified and confirmed</li>
                            <li>Your application is now being processed</li>
                            <li>You can track the status from your dashboard</li>
                            <li>You will receive updates as your application progresses</li>
                        </ul>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-grid gap-2">
                        <a href="{{ $redirectUrl }}" class="btn btn-primary btn-lg">
                            <i class="ri-dashboard-line me-2"></i> Go to Dashboard
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="ri-printer-line me-2"></i> Print Confirmation
                        </button>
                    </div>

                    {{-- Auto-redirect notice --}}
                    <p class="text-muted small mt-4 mb-0">
                        <i class="ri-time-line"></i> Redirecting to dashboard in <span id="countdown">10</span> seconds...
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .success-checkmark {
        width: 80px;
        height: 80px;
        margin: 0 auto;
    }

    .success-checkmark .check-icon {
        width: 80px;
        height: 80px;
        position: relative;
        border-radius: 50%;
        box-sizing: content-box;
        border: 4px solid #198754;
    }

    .success-checkmark .check-icon::before {
        top: 3px;
        left: -2px;
        width: 30px;
        transform-origin: 100% 50%;
        border-radius: 100px 0 0 100px;
    }

    .success-checkmark .check-icon::after {
        top: 0;
        left: 30px;
        width: 60px;
        transform-origin: 0 50%;
        border-radius: 0 100px 100px 0;
        animation: rotate-circle 4.25s ease-in;
    }

    .success-checkmark .check-icon::before,
    .success-checkmark .check-icon::after {
        content: '';
        height: 100px;
        position: absolute;
        background: #fff;
        transform: rotate(-45deg);
    }

    .success-checkmark .check-icon .icon-line {
        height: 5px;
        background-color: #198754;
        display: block;
        border-radius: 2px;
        position: absolute;
        z-index: 10;
    }

    .success-checkmark .check-icon .icon-line.line-tip {
        top: 46px;
        left: 14px;
        width: 25px;
        transform: rotate(45deg);
        animation: icon-line-tip 0.75s;
    }

    .success-checkmark .check-icon .icon-line.line-long {
        top: 38px;
        right: 8px;
        width: 47px;
        transform: rotate(-45deg);
        animation: icon-line-long 0.75s;
    }

    .success-checkmark .check-icon .icon-circle {
        top: -4px;
        left: -4px;
        z-index: 10;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        position: absolute;
        box-sizing: content-box;
        border: 4px solid rgba(25, 135, 84, 0.5);
    }

    .success-checkmark .check-icon .icon-fix {
        top: 8px;
        width: 5px;
        left: 26px;
        z-index: 1;
        height: 85px;
        position: absolute;
        transform: rotate(-45deg);
        background-color: #fff;
    }

    @keyframes rotate-circle {
        0% {
            transform: rotate(-45deg);
        }
        5% {
            transform: rotate(-45deg);
        }
        12% {
            transform: rotate(-405deg);
        }
        100% {
            transform: rotate(-405deg);
        }
    }

    @keyframes icon-line-tip {
        0% {
            width: 0;
            left: 1px;
            top: 19px;
        }
        54% {
            width: 0;
            left: 1px;
            top: 19px;
        }
        70% {
            width: 50px;
            left: -8px;
            top: 37px;
        }
        84% {
            width: 17px;
            left: 21px;
            top: 48px;
        }
        100% {
            width: 25px;
            left: 14px;
            top: 45px;
        }
    }

    @keyframes icon-line-long {
        0% {
            width: 0;
            right: 46px;
            top: 54px;
        }
        65% {
            width: 0;
            right: 46px;
            top: 54px;
        }
        84% {
            width: 55px;
            right: 0px;
            top: 35px;
        }
        100% {
            width: 47px;
            right: 8px;
            top: 38px;
        }
    }

    @media print {
        .btn, #countdown, .alert-info {
            display: none !important;
        }
    }
</style>

@push('scripts')
<script>
    // Auto-redirect countdown
    let seconds = 10;
    const countdownEl = document.getElementById('countdown');
    const redirectUrl = '{{ $redirectUrl }}';

    const countdown = setInterval(() => {
        seconds--;
        if (countdownEl) {
            countdownEl.textContent = seconds;
        }

        if (seconds <= 0) {
            clearInterval(countdown);
            window.location.href = redirectUrl;
        }
    }, 1000);

    // Clear countdown if user clicks dashboard button
    document.querySelector('.btn-primary').addEventListener('click', () => {
        clearInterval(countdown);
    });
</script>
@endpush
@endsection
