@extends('layouts.portal')

@section('title', 'Payment History')

@section('content')
<div id="payment-history-page">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="fw-bold text-dark m-0" style="font-size:18px;">Payment History</h1>
  </div>

  <div class="form-container">
    <div class="form-header" style="background: transparent !important; border-bottom: 2px solid #e5e7eb !important;">
      <h2 class="m-0" style="color: #1f2937 !important;"><i class="ri-bank-card-line me-2"></i>Transaction History</h2>
    </div>

    <div class="form-steps-container">
      @if(isset($payments) && $payments->count() > 0)
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Invoice / Reference</th>
              <th>Date</th>
              <th>Description</th>
              <th>Payment Method</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Receipt</th>
            </tr>
          </thead>
          <tbody>
            @foreach($payments as $payment)
            <tr>
              <td>
                <span class="fw-bold" style="font-size:12px;">{{ $payment->receipt_number ?? $payment->reference ?? '—' }}</span>
                @if($payment->paynow_reference)
                  <div class="text-muted small" title="PayNow Transaction ID">TXN: {{ $payment->paynow_reference }}</div>
                @endif
              </td>
              <td>{{ $payment->confirmed_at ? $payment->confirmed_at->format('d M Y H:i') : $payment->created_at->format('d M Y H:i') }}</td>
              <td>
                {{ ucfirst($payment->service_type ?? 'Registration') }} Fee
                @if($payment->application)
                  <div class="text-muted small">{{ $payment->application->reference ?? '' }}</div>
                @endif
              </td>
              <td>
                @php
                  // Determine the payment method label
                  $methodLabel = $payment->payment_method ?? $payment->method ?? 'N/A';
                  
                  // Map common methods
                  $methodLabels = [
                    'paynow' => 'PayNow',
                    'ecocash' => 'EcoCash (PayNow)',
                    'onemoney' => 'OneMoney (PayNow)',
                    'paynow_reference' => 'PayNow',
                    'proof' => 'Proof of Payment',
                    'proof_upload' => 'Proof of Payment',
                    'cash' => 'Cash',
                    'transfer' => 'Bank Transfer',
                    'general' => 'Other',
                  ];
                  
                  $displayLabel = $methodLabels[$methodLabel] ?? ucfirst($methodLabel);
                  
                  // Color code for PayNow
                  $isBadgeColor = (stripos($displayLabel, 'paynow') !== false || stripos($displayLabel, 'ecocash') !== false || stripos($displayLabel, 'onemoney') !== false) 
                    ? 'bg-primary text-white' 
                    : 'bg-light text-dark border';
                @endphp
                <span class="badge {{ $isBadgeColor }}">{{ $displayLabel }}</span>
              </td>
              <td class="fw-bold">{{ $payment->currency ?? 'ZWL' }} {{ number_format($payment->amount, 2) }}</td>
              <td>
                @php
                  $statusMap = [
                    'paid' => 'success',
                    'confirmed' => 'success',
                    'processing' => 'warning',
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    'failed' => 'danger',
                    'cancelled' => 'secondary',
                  ];
                  $badgeClass = $statusMap[strtolower($payment->status)] ?? 'secondary';
                  $statusText = match($payment->status) {
                    'paid', 'confirmed' => 'Confirmed',
                    'processing' => 'Processing',
                    'pending' => 'Pending',
                    'rejected' => 'Returned for Correction',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                    default => ucfirst($payment->status)
                  };
                @endphp
                <span class="badge bg-{{ $badgeClass }}">{{ $statusText }}</span>
              </td>
              <td>
                @if(in_array($payment->status, ['paid', 'confirmed']) && ($payment->receipt_number || $payment->paynow_reference))
                  <a href="{{ route('portal.receipt.download', $payment->id) }}"
                     class="btn btn-sm btn-primary portal-link" target="_blank">
                    <i class="ri-download-line me-1"></i>Download
                  </a>
                @else
                  <button class="btn btn-sm btn-primary" disabled title="Receipt available after payment confirmation">Download</button>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="alert alert-info mt-3">
        <i class="ri-information-line me-2"></i>
        Your payment history is displayed above. PayNow transactions are automatically tracked and updated in real-time.
      </div>
      @else
      <div class="text-center py-5">
        <i class="ri-bank-card-line" style="font-size:48px; color:#d1d5db;"></i>
        <div class="text-muted mt-3">No payment records found.</div>
        <div class="text-muted small">Payment records will appear here once you make a payment for your application or renewal.</div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
