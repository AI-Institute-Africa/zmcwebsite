@extends('layouts.portal')
@section('title', 'Payments Listing')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold">Payments Listing</h4>
        <a href="{{ route('staff.accounts.payments.offline.create') }}" class="btn btn-primary">
            <i class="ri-add-line"></i> Record Offline Payment
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('staff.accounts.payments.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ref, Applicant Name...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status[]" class="form-select select2" multiple>
                        <option value="paid" {{ in_array('paid', (array)request('status')) ? 'selected' : '' }}>Paid</option>
                        <option value="pending" {{ in_array('pending', (array)request('status')) ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ in_array('failed', (array)request('status')) ? 'selected' : '' }}>Failed</option>
                        <option value="reversed" {{ in_array('reversed', (array)request('status')) ? 'selected' : '' }}>Reversed</option>
                        <option value="refunded" {{ in_array('refunded', (array)request('status')) ? 'selected' : '' }}>Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Method</label>
                    <select name="method[]" class="form-select select2" multiple>
                        <option value="paynow" {{ in_array('paynow', (array)request('method')) ? 'selected' : '' }}>Paynow</option>
                        <option value="bank_transfer" {{ in_array('bank_transfer', (array)request('method')) ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cash" {{ in_array('cash', (array)request('method')) ? 'selected' : '' }}>Cash</option>
                        <option value="pos" {{ in_array('pos', (array)request('method')) ? 'selected' : '' }}>POS</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Reference</th>
                        <th>Applicant</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>
                            <span class="fw-bold">{{ $payment->reference }}</span>
                            @if($payment->source == 'offline')
                                <span class="badge bg-info">Offline</span>
                            @endif
                        </td>
                        <td>
                            {{ $payment->application->applicant->name ?? 'N/A' }}
                            <div class="small text-muted">{{ $payment->applicant_category }} | {{ $payment->residency }}</div>
                        </td>
                        <td>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                        <td>
                            <span class="badge bg-{{ $payment->status == 'paid' ? 'success' : ($payment->status == 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td>{{ $payment->created_at->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Options
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('staff.applications.details', $payment->application_id) }}">View Application</a></li>
                                    @if($payment->method == 'paynow' && $payment->status != 'paid')
                                    <li><a class="dropdown-item" href="{{ route('staff.accounts.payments.retry', $payment->id) }}">Retry Status</a></li>
                                    @endif
                                    @if($payment->status == 'paid')
                                    <li><button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reverseModal{{ $payment->id }}">Reverse</button></li>
                                    <li><button class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#refundModal{{ $payment->id }}">Refund</button></li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
