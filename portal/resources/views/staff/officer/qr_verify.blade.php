@extends('layouts.portal')
@section('title', 'Accreditation QR Verification')

@section('content')
  <div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div>
        <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Accreditation QR Verification</h4>
        <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
          <i class="ri-information-line me-1"></i>Verify a certificate, registration number or QR token against the
          official register.
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
          <i class="ri-dashboard-3-line me-1"></i>Dashboard
        </a>
      </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body">
        <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
          <div class="col-12 col-md-8">
            <label class="form-label small fw-bold mb-1">Certificate / registration number or QR token</label>
            <input type="text" name="q" value="{{ $term }}" class="form-control"
              placeholder="e.g. ZMC/ACC/2026/0001 or a QR token" autofocus>
          </div>
          <div class="col-12 col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-dark px-4"><i class="ri-search-line me-1"></i>Verify</button>
            <a href="{{ url()->current() }}" class="btn btn-white border px-3">Clear</a>
          </div>
        </form>
      </div>
    </div>

    @if($term !== '')
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <h6 class="fw-bold mb-3">Results for "{{ $term }}"</h6>

          @if($acc->isEmpty() && $reg->isEmpty())
            <div class="alert alert-warning mb-0">
              <i class="ri-error-warning-line me-1"></i>
              No accreditation or registration record matches that reference. It may be invalid, mis-typed, or not yet issued.
            </div>
          @endif

          @foreach($acc as $record)
            @php
              $valid = $record->expires_at ? $record->expires_at->isFuture() : in_array($record->status, ['active', 'issued']);
              $holder = $record->holder->name ?? trim(($record->first_name ?? '') . ' ' . ($record->surname ?? ''));
            @endphp
            <div class="border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <div class="fw-bold">{{ $holder ?: '—' }}</div>
                  <div class="text-muted small">Media practitioner accreditation</div>
                </div>
                <span class="badge {{ $valid ? 'bg-success' : 'bg-danger' }}">
                  {{ $valid ? 'VALID' : 'NOT VALID' }}
                </span>
              </div>
              <div class="row g-3 mt-1 small">
                <div class="col-6 col-md-3">
                  <div class="text-muted">Certificate no.</div>
                  <div class="fw-semibold">{{ $record->certificate_no ?: $record->record_number ?: '—' }}</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="text-muted">Status</div>
                  <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $record->status ?: '—')) }}</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="text-muted">Issued</div>
                  <div class="fw-semibold">{{ optional($record->issued_at)->format('Y-m-d') ?: '—' }}</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="text-muted">Expires</div>
                  <div class="fw-semibold">{{ optional($record->expires_at)->format('Y-m-d') ?: '—' }}</div>
                </div>
              </div>
            </div>
          @endforeach

          @foreach($reg as $record)
            @php
              $valid = $record->expires_at ? $record->expires_at->isFuture() : in_array($record->status, ['active', 'issued']);
            @endphp
            <div class="border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <div class="fw-bold">{{ $record->entity_name ?: $record->organisation_name ?: '—' }}</div>
                  <div class="text-muted small">Media house registration</div>
                </div>
                <span class="badge {{ $valid ? 'bg-success' : 'bg-danger' }}">
                  {{ $valid ? 'VALID' : 'NOT VALID' }}
                </span>
              </div>
              <div class="row g-3 mt-1 small">
                <div class="col-6 col-md-3">
                  <div class="text-muted">Registration no.</div>
                  <div class="fw-semibold">{{ $record->registration_no ?: $record->record_number ?: '—' }}</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="text-muted">Status</div>
                  <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $record->status ?: '—')) }}</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="text-muted">Issued</div>
                  <div class="fw-semibold">{{ optional($record->issued_at)->format('Y-m-d') ?: '—' }}</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="text-muted">Expires</div>
                  <div class="fw-semibold">{{ optional($record->expires_at)->format('Y-m-d') ?: '—' }}</div>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

    <div class="row g-4">
      <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Recently issued practitioner certificates</h6>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Number</th><th>Holder</th><th>Expires</th><th>QR token</th></tr></thead>
                <tbody>
                  @forelse($recentAcc as $record)
                    <tr>
                      <td class="fw-semibold">{{ $record->certificate_no ?: $record->record_number ?: ('#' . $record->id) }}</td>
                      <td>{{ $record->holder->name ?? '—' }}</td>
                      <td>{{ optional($record->expires_at)->format('Y-m-d') ?: '—' }}</td>
                      <td class="text-muted small">{{ $record->qr_token ?: '—' }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No records yet.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-xl-6">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Recently issued media house registrations</h6>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Number</th><th>Entity</th><th>Expires</th><th>QR token</th></tr></thead>
                <tbody>
                  @forelse($recentReg as $record)
                    <tr>
                      <td class="fw-semibold">{{ $record->registration_no ?: $record->record_number ?: ('#' . $record->id) }}</td>
                      <td>{{ $record->entity_name ?: $record->organisation_name ?: '—' }}</td>
                      <td>{{ optional($record->expires_at)->format('Y-m-d') ?: '—' }}</td>
                      <td class="text-muted small">{{ $record->qr_token ?: '—' }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No records yet.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
