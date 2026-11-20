{{-- Payment Form Component --}}
{{-- Usage: <x-payment-form :application="$application" /> --}}

<div class="payment-form-container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h1 class="mb-0">💳 Complete Your Payment</h1>
        </div>
        <div class="card-body">
            <!-- Web Payment Option -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="payment-method-card">
                        <h2>🌐 Pay via PayNow (Web)</h2>
                        <p class="text-muted small">Secure payment gateway with multiple payment options</p>
                        <form action="{{ route('payment.initiate') }}" method="POST" class="mt-3">
                            @csrf
                            <input type="hidden" name="application_id" value="{{ $application->id }}">
                            <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                            <input type="hidden" name="payment_method" value="paynow">
                            
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                Pay ZWL {{ number_format($application->amount ?? 0, 2) }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Mobile Payment Options -->
                <div class="col-md-6">
                    <div class="payment-method-card">
                        <h3>📱 Mobile Money Express</h3>
                        <p class="text-muted small">Quick payment directly from your phone</p>
                        
                        <ul class="nav nav-pills mt-3" role="tablist">
                            <li class="nav-item me-2">
                                <a class="nav-link active" id="ecocash-tab" data-bs-toggle="pill" 
                                   href="#ecocash-form" role="tab">EcoCash</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="onemoney-tab" data-bs-toggle="pill" 
                                   href="#onemoney-form" role="tab">OneMoney</a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3">
                            <!-- EcoCash Form -->
                            <div class="tab-pane fade show active" id="ecocash-form" role="tabpanel">
                                <form action="{{ route('payment.initiate') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                                    <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                    <input type="hidden" name="payment_method" value="ecocash">
                                    
                                    <div class="mb-2">
                                        <label class="form-label small">Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control form-control-sm" 
                                               placeholder="0771234567" pattern="[0-9]{10,13}" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        Pay ZWL {{ number_format($application->amount ?? 0, 2) }} via EcoCash
                                    </button>
                                </form>
                            </div>

                            <!-- OneMoney Form -->
                            <div class="tab-pane fade" id="onemoney-form" role="tabpanel">
                                <form action="{{ route('payment.initiate') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="application_id" value="{{ $application->id }}">
                                    <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                    <input type="hidden" name="payment_method" value="onemoney">
                                    
                                    <div class="mb-2">
                                        <label class="form-label small">Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control form-control-sm" 
                                               placeholder="0771234567" pattern="[0-9]{10,13}" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning btn-sm w-100">
                                        Pay ZWL {{ number_format($application->amount ?? 0, 2) }} via OneMoney
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="alert alert-info">
                <strong>Payment Details:</strong>
                <ul class="mb-0 ms-3 mt-2">
                    <li>Amount: <strong>ZWL {{ number_format($application->amount ?? 0, 2) }}</strong></li>
                    <li>Application: <strong>#{{ $application->id }}</strong></li>
                    <li>Your Email: <strong>{{ auth()->user()->email }}</strong></li>
                </ul>
            </div>

            <!-- Security Notice -->
            <div class="alert alert-success">
                <small>
                    <strong>🔒 Secure:</strong> All payments are processed through PayNow's secure gateway. 
                    Your payment information is protected with industry-standard encryption.
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.payment-method-card {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #f9f9f9;
}

.payment-method-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
