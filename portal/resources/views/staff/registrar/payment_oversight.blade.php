@extends('layouts.portal')
@section('title', 'Payment Oversight')

@section('content')
@include('staff.partials.dashboard_theme')
<div class="registrar-theme">
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <style>
      .payment-hero{position:relative;overflow:hidden;padding:26px 30px;border-radius:22px;background:linear-gradient(115deg,#123f35,#1f6f57);color:#fff;box-shadow:0 16px 34px rgba(20,83,65,.15);margin-bottom:22px}.payment-hero:after{content:"";position:absolute;right:-55px;top:-105px;width:230px;height:230px;border-radius:50%;background:rgba(242,201,76,.16)}.payment-hero>*{position:relative;z-index:1}.payment-hero h1{font-size:clamp(1.55rem,2.4vw,2.1rem);font-weight:850;margin:0;letter-spacing:-.03em}.payment-hero p{color:rgba(255,255,255,.76);margin:7px 0 0;font-size:.9rem}.payment-hero .hero-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}.payment-hero .btn{border-radius:10px;font-weight:750}.payment-hero .read-only{display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:8px 12px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);font-size:.76rem;font-weight:750}.payment-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:14px}.payment-kpi{border:1px solid #e5ede9;border-radius:17px;background:#fff;padding:17px;box-shadow:0 7px 22px rgba(20,58,46,.06)}.payment-kpi .icon{width:38px;height:38px;border-radius:12px;display:grid;place-items:center;background:var(--tint);color:var(--tone);font-size:1.15rem}.payment-kpi .label{margin-top:13px;text-transform:uppercase;letter-spacing:.06em;color:#72817b;font-size:.7rem;font-weight:800}.payment-kpi .value{font-size:1.75rem;font-weight:850;line-height:1.05;color:#17352c;margin-top:3px}.payment-kpi .note{font-size:.74rem;color:#85918c;margin-top:6px}.payment-breakdown{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 22px}.payment-breakdown span{border:1px solid #e3ebe7;background:#f8fbf9;border-radius:999px;padding:7px 11px;font-size:.75rem;color:#5e706a;font-weight:700}.payment-breakdown b{color:#176a4f}.payment-filter{border:1px solid #e3ece7!important;border-radius:18px!important;background:linear-gradient(135deg,#f8fbf9,#f2f7f4)!important;padding:18px!important;box-shadow:none!important}.payment-filter .form-control,.payment-filter .form-select{height:40px;border-radius:10px;border-color:#dce7e1}.payment-filter .btn{height:40px;border-radius:10px;font-weight:750}.payment-table{border:1px solid #e5ede9!important;border-radius:19px!important;overflow:hidden;box-shadow:0 9px 26px rgba(20,58,46,.07)!important}.payment-table .card-header{padding:17px 20px;background:#fbfdfc!important;border-bottom:1px solid #e5ede9}.payment-table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.05em;color:#50645f;background:#f4f8f6!important}.payment-table tbody tr:hover{background:#fbfdfc}.payment-table .badge{border-radius:999px;padding:.45em .7em;font-weight:700}@media(max-width:950px){.payment-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.payment-kpis{grid-template-columns:1fr}.payment-hero{padding:22px 20px}}
    </style>
    <div class="payment-hero">
        <div class="text-uppercase small fw-bold" style="letter-spacing:.14em;opacity:.72"><i class="ri-bank-card-line me-1"></i> Finance visibility</div>
        <h1>Payment Oversight</h1>
        <p>Monitor payment submissions and verification progress. This workspace is view-only; Accounts handles all verification decisions.</p>
        <div class="hero-actions"><span class="read-only"><i class="ri-eye-line"></i> Read-only access</span><a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i> Back to dashboard</a></div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(!empty($isLegacySource))
        <div class="alert alert-warning mb-4">
            <i class="ri-database-2-line me-1"></i>
            Showing legacy payment records because no rows were found in the newer payment submissions table.
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="payment-kpis">
        <div class="payment-kpi" style="--tone:#a46d0a;--tint:#fff3d7"><div class="icon"><i class="ri-time-line"></i></div><div class="label">Pending review</div><div class="value">{{ $kpis['pending'] }}</div><div class="note">Awaiting Accounts action</div></div>

        <div class="payment-kpi" style="--tone:#247a54;--tint:#e7f6ed"><div class="icon"><i class="ri-check-line"></i></div><div class="label">Verified</div><div class="value">{{ $kpis['verified'] }}</div><div class="note">Confirmed by Accounts</div></div>

        <div class="payment-kpi" style="--tone:#bd4b42;--tint:#fff0ef"><div class="icon"><i class="ri-close-line"></i></div><div class="label">Returned</div><div class="value">{{ $kpis['rejected'] }}</div><div class="note">Needs correction or resubmission</div></div>

        <div class="payment-kpi" style="--tone:#2d6799;--tint:#e6f1fb"><div class="icon"><i class="ri-pie-chart-line"></i></div><div class="label">Payment methods</div><div class="value">{{ $kpis['paynow'] + $kpis['proof'] + $kpis['waiver'] }}</div><div class="note">PayNow {{ $kpis['paynow'] }} · Proof {{ $kpis['proof'] }} · Waiver {{ $kpis['waiver'] }}</div></div>
    </div>
    <div class="payment-breakdown"><span><i class="ri-file-list-line me-1"></i>Application fees <b>{{ $kpis['app_fee'] }}</b></span><span><i class="ri-file-check-line me-1"></i>Registration fees <b>{{ $kpis['reg_fee'] }}</b></span></div>

    {{-- Filters --}}
    <div class="payment-filter card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Pending</option>
                        <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Returned for Correction</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Method</label>
                    <select name="method" class="form-select form-select-sm">
                        <option value="">All Methods</option>
                        <option value="PAYNOW" {{ request('method') === 'PAYNOW' ? 'selected' : '' }}>PayNow</option>
                        <option value="PROOF_UPLOAD" {{ request('method') === 'PROOF_UPLOAD' ? 'selected' : '' }}>Proof Upload</option>
                        <option value="WAIVER" {{ request('method') === 'WAIVER' ? 'selected' : '' }}>Waiver</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Payment Stage</label>
                    <select name="payment_stage" class="form-select form-select-sm">
                        <option value="">All Stages</option>
                        <option value="application_fee" {{ request('payment_stage') === 'application_fee' ? 'selected' : '' }}>Application Fee</option>
                        <option value="registration_fee" {{ request('payment_stage') === 'registration_fee' ? 'selected' : '' }}>Registration Fee</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Application ref..." value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Date From</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Date To</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-success shadow-sm">
                        <i class="ri-filter-line me-1"></i> Apply Filters
                    </button>
                    <a href="{{ route('staff.registrar.payment-oversight') }}" class="btn btn-sm btn-light border">
                        <i class="ri-refresh-line me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Payment List --}}
    <div class="payment-table card">
        <div class="card-header fw-bold d-flex justify-content-between align-items-center">
            <i class="ri-list-check-2 me-2"></i> Payment Submissions
            <span class="small text-muted fw-normal">{{ $payments->total() }} records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Application</th>
                        <th>Stage</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Amount</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Verified By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        @php
                            $isSubmission = method_exists($payment, 'getStageLabel');
                            $stageLabel = $isSubmission
                                ? $payment->getStageLabel()
                                : (($payment->service_type ?? null) ? ucfirst($payment->service_type) . ' Fee' : 'Legacy Payment');
                            $methodLabel = $isSubmission
                                ? $payment->getMethodLabel()
                                : strtoupper((string)($payment->method ?? '—'));
                            $statusValue = strtolower((string)($payment->status ?? ''));
                            $statusColor = $isSubmission
                                ? $payment->getStatusColor()
                                : (in_array($statusValue, ['paid', 'verified', 'confirmed'], true)
                                    ? 'success'
                                    : (in_array($statusValue, ['rejected', 'failed'], true) ? 'danger' : 'warning'));
                            $submittedAt = $payment->submitted_at ?? $payment->created_at;
                            $verifiedAt = $payment->verified_at ?? $payment->confirmed_at;
                            $verifierName = $payment->verifier->name ?? $payment->payer->name ?? null;
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $payment->application->reference ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border-secondary">
                                    {{ $stageLabel }}
                                </span>
                            </td>
                            <td>{{ $methodLabel }}</td>
                            <td class="small">{{ $payment->reference ?? '—' }}</td>
                            <td>{{ $payment->amount ? '$' . number_format($payment->amount, 2) : '—' }}</td>
                            <td class="small">{{ $submittedAt?->format('d M Y H:i') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} border-{{ $statusColor }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="small">
                                @if($verifierName)
                                    {{ $verifierName }}
                                    <div class="text-muted" style="font-size: 11px;">{{ $verifiedAt?->format('d M H:i') }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($isSubmission)
                                    <a href="{{ route('staff.registrar.payment-detail', $payment) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" title="View payment details" aria-label="View payment details">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                @else
                                    <span class="text-muted small">Legacy</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="ri-inbox-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <div class="mt-2">No payment submissions found.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="card-footer bg-white">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>

</div>
@endsection
