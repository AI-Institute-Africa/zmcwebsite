@extends('layouts.portal')

@section('title', 'Payment Pending')

@section('content')
<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="ri-time-line text-warning" style="font-size:64px;"></i>
                    </div>

                    <h2 class="text-warning fw-bold mb-3">Payment Pending</h2>

                    <p class="lead text-muted mb-4">
                        Paynow has not confirmed this payment yet.
                    </p>

                    <div class="alert alert-light border mb-4">
                        <div class="row text-start">
                            <div class="col-6">
                                <small class="text-muted d-block">Application Reference</small>
                                <strong class="d-block">{{ $application->reference }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Paynow Status</small>
                                <strong class="d-block">{{ ucwords(str_replace('_', ' ', $status ?? 'pending')) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ $redirectUrl }}" class="btn btn-primary btn-lg">
                            <i class="ri-dashboard-line me-2"></i> Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
