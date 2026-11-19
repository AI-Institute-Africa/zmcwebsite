@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">📱 Complete Your Mobile Payment</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <strong>Payment Instructions:</strong> Follow the steps below to complete your payment
                    </div>

                    @if($payment)
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Reference:</strong> <code>{{ $payment->reference }}</code></p>
                                <p><strong>Amount:</strong> ZWL {{ number_format($payment->amount, 2) }}</p>
                                <p><strong>Phone:</strong> {{ $payment->phone_number }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Method:</strong> {{ strtoupper($payment->payment_method) }}</p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-warning">{{ ucfirst($payment->status) }}</span>
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            @if($instructions)
                                <h6>{{ $instructions }}</h6>
                            @else
                                <p>Please follow the payment prompt sent to your mobile device.</p>
                            @endif
                        </div>
                    </div>

                    <div class="alert alert-warning" role="alert">
                        <strong>⏱️ Important:</strong> Complete the payment within the time limit to avoid transaction timeout.
                    </div>

                    <div id="payment-status-container" class="mt-4">
                        <p>Checking payment status...</p>
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('home') }}" class="btn btn-secondary">Return to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reference = '{{ $payment->reference ?? '' }}';
    let checkInterval = setInterval(checkPaymentStatus, 3000);
    
    function checkPaymentStatus() {
        if (!reference) return;
        
        fetch('{{ route("payment.status") }}?reference=' + reference)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'paid') {
                    clearInterval(checkInterval);
                    document.getElementById('payment-status-container').innerHTML = `
                        <div class="alert alert-success" role="alert">
                            ✓ Payment received successfully!
                            <br>
                            <small>Redirecting...</small>
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = '{{ route("payment.return", ["pollurl" => ($pollUrl ?? "")]) }}';
                    }, 2000);
                }
            })
            .catch(error => console.error('Error checking payment:', error));
    }
    
    // Stop checking after 10 minutes
    setTimeout(() => clearInterval(checkInterval), 600000);
});
</script>
@endsection
