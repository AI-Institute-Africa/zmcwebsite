@extends('layouts.portal')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">System Settings</h4>
      <div class="text-muted" style="font-size:13px;">Branding, security, backups, and integrations.</div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.system.settings') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold">Branding</div>
          <div class="card-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small text-muted">Logo</label>
                <input class="form-control form-control-sm" type="file" name="branding[logo]" />
                <div class="small text-muted mt-1">Current: {{ $cfg['branding']['logo_path'] ?? '—' }}</div>
              </div>
              <div class="col-md-6">
                <label class="form-label small text-muted">Official Seal</label>
                <input class="form-control form-control-sm" type="file" name="branding[seal]" />
                <div class="small text-muted mt-1">Current: {{ $cfg['branding']['seal_path'] ?? '—' }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
          <div class="card-header bg-white fw-semibold">Security</div>
          <div class="card-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small text-muted">Password minimum length</label>
                <input class="form-control form-control-sm" type="number" min="6" max="30" name="security[password_min]" value="{{ $cfg['security']['password_min'] ?? 8 }}" />
              </div>
              <div class="col-md-6">
                <label class="form-label small text-muted">MFA</label>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="security[mfa_enabled]" value="1" {{ !empty($cfg['security']['mfa_enabled']) ? 'checked' : '' }} />
                  <label class="form-check-label">Enable MFA (toggle)</label>
                </div>
                <div class="small text-muted">This toggle prepares the system for MFA; implementation can be added later.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold">Backups</div>
          <div class="card-body">
            <label class="form-label small text-muted">Schedule</label>
            <input class="form-control form-control-sm" name="backups[schedule]" value="{{ $cfg['backups']['schedule'] ?? 'daily' }}" placeholder="daily / weekly / cron expression" />
            <div class="small text-muted mt-2">This stores your preferred schedule; actual backup jobs are configured on the server.</div>
          </div>
        </div>

        <div class="card border-0 shadow-sm mt-3">
          <div class="card-header bg-white fw-semibold">Integrations</div>
          <div class="card-body">
            <div class="mb-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="integrations[paynow][enabled]" value="1" {{ !empty($cfg['integrations']['paynow']['enabled']) ? 'checked' : '' }} />
                <label class="form-check-label">Enable PayNow</label>
              </div>
            </div>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small text-muted">Email driver</label>
                <input class="form-control form-control-sm" name="integrations[email][driver]" value="{{ $cfg['integrations']['email']['driver'] ?? 'smtp' }}" placeholder="smtp / ses / mailgun" />
              </div>
              <div class="col-md-6">
                <label class="form-label small text-muted">SMS provider</label>
                <input class="form-control form-control-sm" name="integrations[sms][provider]" value="{{ $cfg['integrations']['sms']['provider'] ?? '' }}" placeholder="provider name" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button class="btn btn-primary">Save Settings</button>
    </div>
  </form>
</div>
@endsection
