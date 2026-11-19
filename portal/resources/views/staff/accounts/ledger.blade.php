@extends('layouts.portal')

@section('title', 'Accounts Ledger')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Accounts Ledger</h2>
            <p class="text-muted mb-0">All recorded payments across the system.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['Total Paid (USD)', number_format(($summary['paid_total'] ?? 0), 2), 'success'],
                ['Pending Payments', $summary['pending_count'] ?? 0, 'warning'],
                ['Refunded',         number_format(($summary['refunded_total'] ?? 0), 2), 'danger'],
                ['Transactions',     $summary['count'] ?? (method_exists($payments, 'total') ? $payments->total() : count($payments)), 'primary'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $color])
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-uppercase small text-muted">{{ $label }}</div>
                        <div class="h4 mb-0 text-{{ $color }}">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Payment Records</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Application</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ optional($p->created_at)->format('Y-m-d H:i') }}</td>
                        <td>{{ $p->application_id ?? '—' }}</td>
                        <td>
                            <span class="badge bg-info text-uppercase">{{ $p->method ?? '—' }}</span>
                        </td>
                        <td>{{ number_format((float)($p->amount ?? 0), 2) }} {{ $p->currency ?? '' }}</td>
                        <td>
                            @php
                                $badge = ['paid'=>'success','pending'=>'warning','failed'=>'danger','refunded'=>'secondary','reversed'=>'danger'][$p->status ?? ''] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $badge }}">{{ ucfirst($p->status ?? 'unknown') }}</span>
                        </td>
                        <td><code>{{ $p->reference ?? $p->paynow_reference ?? '—' }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No payments recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($payments, 'links'))
            <div class="card-footer bg-white">{{ $payments->links() }}</div>
        @endif
    </div>
</div>
@endsection
