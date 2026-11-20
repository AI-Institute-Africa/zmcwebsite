@extends('layouts.portal')
@section('title', 'Payment Audit')
@section('content')
@php($modeLabel = $mode === 'proofs' ? 'Proof of Payment Audit' : ($mode === 'waivers' ? 'Fee Waiver Audit' : 'PayNow & Fees Audit'))
<div class="zmc-dashboard-wrapper"><div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4"><div><h1 class="fw-bold m-0" style="font-size:var(--font-size-2xl);">{{ $modeLabel }}</h1><div class="text-muted mt-1">Read-only payment metadata. Applicant identity and personal information are restricted.</div></div><a class="btn btn-white border btn-sm" href="{{ route('staff.auditor.reconciliation') }}"><i class="ri-arrow-left-line me-1"></i>Payments & Reconciliations</a></div>
<div class="zmc-card shadow-sm border-0 p-3 mb-3"><form method="GET" class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label small text-muted">Status</label><input class="form-control form-control-sm" name="status" value="{{ request('status') }}" placeholder="approved / pending"></div><div class="col-md-3"><label class="form-label small text-muted">From</label><input type="date" class="form-control form-control-sm" name="from" value="{{ request('from',request('date_from')) }}"></div><div class="col-md-3"><label class="form-label small text-muted">To</label><input type="date" class="form-control form-control-sm" name="to" value="{{ request('to',request('date_to')) }}"></div><div class="col-md-3 d-flex gap-2"><button class="btn btn-dark btn-sm">Filter</button><a class="btn btn-white border btn-sm" href="{{ url()->current() }}">Reset</a></div></form></div>
<div class="d-flex justify-content-between align-items-center mb-2"><div class="small text-muted">Showing {{ $applications->count() }} of {{ $applications->total() }}</div><div>{{ $applications->onFirstPage() ? '' : $applications->links() }}</div></div>
<div class="zmc-card shadow-sm border-0 p-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Reference</th><th>Type</th><th>Status</th><th>PayNow ref</th><th>Payment status</th><th>Proof</th><th>Waiver</th><th>Updated</th></tr></thead>
      <tbody>
      @forelse($applications as $a)
        @php($dup = !empty($a->paynow_reference) && in_array($a->paynow_reference, $duplicateRefs, true))
        <tr @if($dup) style="background:#fff7ed" @endif>
          <td class="fw-bold">{{ $a->reference ?: 'APP-'.$a->id }}</td>
          <td>{{ ucfirst((string)$a->application_type) }}</td>
          <td>{{ \App\Models\Application::statusLabel((string)$a->status) }}</td>
          <td>{{ $a->paynow_reference ?: '—' }}</td>
          <td>{{ $a->payment_status ?: 'Not recorded' }}</td>
          <td>{{ $a->proof_status ?: ($a->payment_proof_path ? 'Uploaded' : '—') }}</td>
          <td>{{ $a->waiver_status ?: ($a->waiver_path ? 'Uploaded' : '—') }}</td>
          <td class="small text-muted">{{ optional($a->updated_at)->format('d M Y H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-center py-5 text-muted">No payment records found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3">{{ $applications->links() }}</div>
</div>
@endsection
