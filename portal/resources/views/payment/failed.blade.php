@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">✗ Payment Failed</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger" role="alert">
                        {{ $message ?? 'Your payment could not be processed. Please try again.' }}
                    </div>

                    @if(isset($payment) && $payment)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Reference:</strong> {{ $payment->reference }}</p>
                                <p><strong>Amount:</strong> ZWL {{ number_format($payment->amount, 2) }}</p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-danger">{{ ucfirst($payment->status) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Payment Method:</strong> {{ ucfirst($payment->payment_method ?? 'PayNow') }}</p>
                                <p><strong>Attempted:</strong> {{ $payment->created_at?->format('M d, Y H:i') }}</p>
                            </div>
                        </div>

                        @if($payment->application)
                            <div class="alert alert-info" role="alert">
                                <strong>Application ID:</strong> {{ $payment->application->id }}
                                <br>
                                <strong>Applicant:</strong> {{ $payment->application->applicant_name ?? 'N/A' }}
                            </div>
                        @endif
                    @endif

                    <div class="mt-4">
                        <p><strong>What you can do:</strong></p>
                        <ul>
                            <li>Verify your payment information</li>
                            <li>Check your bank account for sufficient funds</li>
                            <li>Try again with a different payment method</li>
                            <li>Contact support if the problem persists</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('home') }}" class="btn btn-secondary">Return to Dashboard</a>
                    @if(isset($payment) && $payment)
                        <a href="{{ route('payment.initiate') }}" class="btn btn-primary">Try Payment Again</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
