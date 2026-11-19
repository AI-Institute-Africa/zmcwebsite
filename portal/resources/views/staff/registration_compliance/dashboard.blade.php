@extends('layouts.staff')

@section('title', 'Registration Compliance')

@section('content')
<style>
  .rc-kpi{border:1px solid #e2e8f0;border-bottom:4px solid #0f6b43;border-radius:14px;background:#fff;box-shadow:0 7px 18px rgba(15,23,42,.06)}
  .rc-kpi .icon{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#e6f5ed;color:#0f6b43;font-size:20px}.rc-kpi .value{font-size:28px;line-height:1;font-weight:800;color:#172033}.rc-kpi .label{font-size:12px;color:#667085;font-weight:700;margin-top:7px}
  .rc-kpi.blue{border-bottom-color:#367fe0}.rc-kpi.gold{border-bottom-color:#e6a900}.rc-kpi.red{border-bottom-color:#df3b3b}.rc-kpi.purple{border-bottom-color:#7856d9}.rc-kpi.blue .icon{background:#e7f0ff;color:#367fe0}.rc-kpi.gold .icon{background:#fff5d9;color:#bd8500}.rc-kpi.red .icon{background:#ffebeb;color:#d73535}.rc-kpi.purple .icon{background:#f0ebff;color:#7252ce}
</style>
<div class="container-fluid py-3">
  <div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3"><div><h1 class="h3 mb-1">Registration &amp; Accreditation Compliance Dashboard</h1><p class="text-muted mb-0">Monitor registrations, accreditations, compliance status, levies and regulatory actions.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-dark" href="{{ route('staff.registration-compliance.analytics') }}"><i class="ri-bar-chart-2-line me-1"></i>Analytics</a><a class="btn btn-primary" href="{{ route('staff.registration-compliance.analytics', ['export'=>1]) }}"><i class="ri-download-2-line me-1"></i>Export report</a></div></div>
  </div>

  <div class="row g-3 mb-4">
    @foreach([
      ['Registered mass media services', $stats['registered_total'], 'ri-building-4-line', ''],
      ['Accredited media practitioners', $stats['accredited_total'], 'ri-id-card-line', 'blue'],
      ['Compliant services', $stats['compliance_percentage'].'%', 'ri-shield-check-line', 'gold'],
      ['Non-compliant services', $stats['non_compliance_percentage'].'%', 'ri-alert-line', 'red'],
      ['Expiring within 90 days', $stats['expiring_total'], 'ri-calendar-schedule-line', 'purple'],
    ] as [$label, $value, $icon, $tone])
      <div class="col-12 col-sm-6 col-xl"><div class="rc-kpi {{ $tone }} h-100 p-3"><div class="d-flex justify-content-between align-items-start"><div><div class="value">{{ $value }}</div><div class="label">{{ $label }}</div></div><div class="icon"><i class="{{ $icon }}"></i></div></div></div></div>
    @endforeach
  </div>

  <div class="row g-3 mb-4">
    @foreach([
      ['Expired media house registrations', $stats['registrations_expired'], 'ri-building-line', 'danger'],
      ['Registrations expiring in 90 days', $stats['registrations_due'], 'ri-calendar-event-line', 'warning'],
      ['Expired practitioner accreditations', $stats['accreditations_expired'], 'ri-id-card-line', 'danger'],
      ['Accreditations expiring in 90 days', $stats['accreditations_due'], 'ri-calendar-check-line', 'warning'],
      ['Unverified annual levies', $stats['levies_pending'], 'ri-money-dollar-circle-line', 'primary'],
    ] as [$label, $count, $icon, $tone])
      <div class="col-12 col-sm-6 col-xl">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body d-flex align-items-center gap-3">
            <span class="rounded-circle bg-{{ $tone }}-subtle text-{{ $tone }} d-inline-flex align-items-center justify-content-center" style="width:44px;height:44px"><i class="{{ $icon }} fs-5"></i></span>
            <div><div class="h4 mb-0">{{ number_format($count) }}</div><div class="small text-muted">{{ $label }}</div></div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Media house registrations due or expired</div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
      <thead><tr><th>Organization</th><th>Registration number</th><th>Contact</th><th>Status</th><th>Expiry</th></tr></thead>
      <tbody>
        @forelse($registrationRecords as $record)
          <tr><td>{{ $record->display_name }}</td><td>{{ $record->registration_no ?? $record->record_number ?? '—' }}</td><td>{{ $record->contact?->name ?? '—' }}<div class="small text-muted">{{ $record->contact?->email }}</div></td><td>{{ ucfirst(str_replace('_', ' ', $record->registration_status ?? $record->status ?? 'unknown')) }}</td><td>{{ $record->expires_at?->format('d/m/Y') ?? '—' }}</td></tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted py-4">No media house registrations are due within 90 days.</td></tr>
        @endforelse
      </tbody>
    </table></div>
    <div class="card-footer bg-white">{{ $registrationRecords->links() }}</div>
  </div>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Practitioner accreditations due or expired</div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
      <thead><tr><th>Practitioner</th><th>Accreditation number</th><th>Contact</th><th>Status</th><th>Expiry</th></tr></thead>
      <tbody>
        @forelse($accreditationRecords as $record)
          <tr><td>{{ $record->full_name }}</td><td>{{ $record->accreditation_number ?? $record->certificate_no ?? $record->record_number ?? '—' }}</td><td>{{ $record->holder?->name ?? '—' }}<div class="small text-muted">{{ $record->email ?? $record->holder?->email }}</div></td><td>{{ ucfirst(str_replace('_', ' ', $record->status ?? 'unknown')) }}</td><td>{{ $record->expires_at?->format('d/m/Y') ?? '—' }}</td></tr>
        @empty
          <tr><td colspan="5" class="text-center text-muted py-4">No practitioner accreditations are due within 90 days.</td></tr>
        @endforelse
      </tbody>
    </table></div>
    <div class="card-footer bg-white">{{ $accreditationRecords->links() }}</div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Annual levies awaiting payment verification</div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
      <thead><tr><th>Organization</th><th>Financial year</th><th>Reference</th><th>Levy amount</th><th>Payment status</th><th>Submitted</th></tr></thead>
      <tbody>
        @forelse($levies as $levy)
          <tr><td>{{ $levy->entity_name }}</td><td>{{ $levy->financial_year }}</td><td>{{ $levy->reference }}</td><td>{{ $levy->currency }} {{ number_format((float) $levy->levy_amount, 2) }}</td><td>{{ ucfirst(str_replace('_', ' ', $levy->payment_status)) }}</td><td>{{ $levy->created_at?->format('d/m/Y') ?? '—' }}</td></tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted py-4">No annual levy submissions are awaiting payment verification.</td></tr>
        @endforelse
      </tbody>
    </table></div>
    <div class="card-footer bg-white">{{ $levies->links() }}</div>
  </div>
</div>
@endsection
