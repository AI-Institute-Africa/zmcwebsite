@extends('layouts.portal')
@section('title', 'Record Offline Payment')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Record Offline Payment</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.accounts.payments.offline.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Link to Application</label>
                            <select name="application_id" class="form-select select2" required>
                                <option value="">Select Application...</option>
                                @foreach($applications as $app)
                                <option value="{{ $app->id }}">
                                    {{ $app->reference }} - {{ $app->applicant->name ?? 'N/A' }} ({{ $app->application_type }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="method" class="form-select" required>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cash">Cash</option>
                                    <option value="pos">POS</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" name="reference" class="form-control" placeholder="Receipt or Bank Ref" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bank Name (if applicable)</label>
                                <input type="text" name="bank_name" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Deposit Slip Ref</label>
                                <input type="text" name="deposit_slip_ref" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attach Proof (Scanned Slip/Receipt)</label>
                            <input type="file" name="proof_file" class="form-control" accept=".pdf,.jpg,.png">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('staff.accounts.payments.index') }}" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-primary">Record Payment & Confirm Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
