@extends('layouts.portal')

@section('title', 'Fees & Payments Configuration')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">Fees & Payments (Configuration)</h4>
      <div class="text-muted" style="font-size:13px;">Super Admin configures fee rules; Accounts verifies payments.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.fees.index') }}">Overview</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Reconciliation Snapshot</div>
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-warning text-dark">Pending: {{ $recon['pending'] ?? 0 }}</span>
            <span class="badge bg-success">Paid: {{ $recon['paid'] ?? 0 }}</span>
            <span class="badge bg-danger">Failed: {{ $recon['failed'] ?? 0 }}</span>
          </div>
          <div class="small text-muted mt-2">Derived from application payment_status fields.</div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white fw-semibold">Waiver rules</div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.fees.config') }}">
            @csrf
            <input type="hidden" name="fees" value="{{ json_encode($cfg['fees'] ?? []) }}" />
            <input type="hidden" name="payment_channels" value="{{ json_encode($cfg['payment_channels'] ?? []) }}" />
            <textarea class="form-control" name="waiver_rules" rows="6" placeholder="Describe waiver/discount rules...">{{ $cfg['waiver_rules'] ?? '' }}</textarea>
            <div class="small text-muted mt-2">This is a policy note for staff. You can later wire it into automated waiver approvals.</div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <form method="POST" action="{{ route('admin.fees.config') }}">
        @csrf
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
            <span>Fee Catalogue</span>
            <span class="small text-muted">Edit rows and save</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0" id="feeTable">
                <thead class="table-light">
                  <tr>
                    <th>Fee name</th>
                    <th style="width:140px;">Amount</th>
                    <th style="width:110px;">Currency</th>
                    <th style="width:90px;">Active</th>
                    <th style="width:70px;"></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach(($cfg['fees'] ?? $defaults['fees'] ?? []) as $i => $fee)
                    <tr>
                      <td><input class="form-control form-control-sm" name="fees[{{ $i }}][name]" value="{{ $fee['name'] ?? '' }}" /></td>
                      <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="fees[{{ $i }}][amount]" value="{{ $fee['amount'] ?? 0 }}" /></td>
                      <td><input class="form-control form-control-sm" name="fees[{{ $i }}][currency]" value="{{ $fee['currency'] ?? 'USD' }}" /></td>
                      <td class="text-center"><input type="checkbox" name="fees[{{ $i }}][active]" value="1" {{ !empty($fee['active']) ? 'checked' : '' }} /></td>
                      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">Remove</button></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addFeeRow()">Add row</button>
            <div class="d-flex gap-2">
              <div class="input-group input-group-sm" style="width:220px;">
                <span class="input-group-text">VAT %</span>
                <input class="form-control" type="number" step="0.01" min="0" max="100" name="tax[vat_percent]" value="{{ $cfg['tax']['vat_percent'] ?? 0 }}" />
              </div>
              <button class="btn btn-primary">Save All</button>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
          <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
            <span>Payment Channels</span>
            <span class="small text-muted">Enable/disable channels used by Accounts</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0" id="channelTable">
                <thead class="table-light"><tr><th>Channel</th><th style="width:90px;">Active</th><th style="width:90px;"></th></tr></thead>
                <tbody>
                  @foreach(($cfg['payment_channels'] ?? $defaults['payment_channels'] ?? []) as $i => $ch)
                    <tr>
                      <td><input class="form-control form-control-sm" name="payment_channels[{{ $i }}][name]" value="{{ $ch['name'] ?? '' }}" /></td>
                      <td class="text-center"><input type="checkbox" name="payment_channels[{{ $i }}][active]" value="1" {{ !empty($ch['active']) ? 'checked' : '' }} /></td>
                      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">Remove</button></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer bg-white">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addChannelRow()">Add channel</button>
          </div>
        </div>

        <textarea class="d-none" name="waiver_rules">{{ $cfg['waiver_rules'] ?? '' }}</textarea>
      </form>
    </div>
  </div>
</div>

<script>
  function addFeeRow(){
    const tbody = document.querySelector('#feeTable tbody');
    const i = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input class="form-control form-control-sm" name="fees[${i}][name]" /></td>
      <td><input class="form-control form-control-sm" type="number" step="0.01" min="0" name="fees[${i}][amount]" value="0" /></td>
      <td><input class="form-control form-control-sm" name="fees[${i}][currency]" value="USD" /></td>
      <td class="text-center"><input type="checkbox" name="fees[${i}][active]" value="1" checked /></td>
      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">Remove</button></td>
    `;
    tbody.appendChild(tr);
  }
  function addChannelRow(){
    const tbody = document.querySelector('#channelTable tbody');
    const i = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input class="form-control form-control-sm" name="payment_channels[${i}][name]" /></td>
      <td class="text-center"><input type="checkbox" name="payment_channels[${i}][active]" value="1" checked /></td>
      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">Remove</button></td>
    `;
    tbody.appendChild(tr);
  }
</script>
@endsection
