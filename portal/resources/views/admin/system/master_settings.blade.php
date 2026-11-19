@extends('layouts.portal')

@section('title', 'Master System Settings')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">Master System Settings</h4>
      <div class="text-muted" style="font-size:13px;">All portals & dashboards: branding, security, workflow, content, reports, integrations, and UI controls.</div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-1">Please fix the highlighted issues.</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.system.master_settings') }}" enctype="multipart/form-data">
    @csrf

    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-security" type="button" role="tab">Security & Auth</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-portal" type="button" role="tab">Portals</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-workflow" type="button" role="tab">Workflow</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-finance" type="button" role="tab">Payments & Finance</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-content" type="button" role="tab">Content</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reports" type="button" role="tab">Reports & Audit</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-integrations" type="button" role="tab">Integrations</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ui" type="button" role="tab">Menu & UI</button></li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white shadow-sm">

      {{-- GENERAL --}}
      <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">System identity</div>
              <div class="card-body">
                <div class="mb-2">
                  <label class="form-label small text-muted">System name</label>
                  <input class="form-control form-control-sm" name="general[system_name]" value="{{ $cfg['general']['system_name'] ?? '' }}" />
                </div>
                <div class="mb-2">
                  <label class="form-label small text-muted">Commission short name</label>
                  <input class="form-control form-control-sm" name="general[commission_short_name]" value="{{ $cfg['general']['commission_short_name'] ?? '' }}" />
                </div>
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Default language</label>
                    <input class="form-control form-control-sm" name="general[default_language]" value="{{ $cfg['general']['default_language'] ?? 'en' }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Timezone</label>
                    <input class="form-control form-control-sm" name="general[time_zone]" value="{{ $cfg['general']['time_zone'] ?? 'Africa/Harare' }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Currency</label>
                    <div class="input-group input-group-sm">
                      <input class="form-control" style="max-width:80px" name="general[currency_symbol]" value="{{ $cfg['general']['currency_symbol'] ?? '$' }}" />
                      <input class="form-control" name="general[currency_iso]" value="{{ $cfg['general']['currency_iso'] ?? 'USD' }}" />
                    </div>
                  </div>
                </div>
                <div class="row g-2 mt-1">
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Date format</label>
                    <input class="form-control form-control-sm" name="general[date_format]" value="{{ $cfg['general']['date_format'] ?? 'Y-m-d' }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Time format</label>
                    <input class="form-control form-control-sm" name="general[time_format]" value="{{ $cfg['general']['time_format'] ?? 'H:i' }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Footer text</label>
                    <input class="form-control form-control-sm" name="general[footer_text]" value="{{ $cfg['general']['footer_text'] ?? '' }}" />
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
              <div class="card-header bg-white fw-semibold">Availability</div>
              <div class="card-body">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="general[maintenance_mode]" value="1" {{ !empty($cfg['general']['maintenance_mode']) ? 'checked' : '' }}>
                  <label class="form-check-label">Maintenance mode (blocks portals for non-super admins)</label>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="general[public_portal_enabled]" value="1" {{ !empty($cfg['general']['public_portal_enabled']) ? 'checked' : '' }}>
                  <label class="form-check-label">Public portal available</label>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="general[staff_portals_enabled]" value="1" {{ !empty($cfg['general']['staff_portals_enabled']) ? 'checked' : '' }}>
                  <label class="form-check-label">Staff portals available</label>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Branding assets</div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Logo (Light)</label>
                    <input class="form-control form-control-sm" type="file" name="general[logo_light]" />
                    <div class="small text-muted mt-1">Current: {{ $cfg['general']['logo_light_path'] ?? '—' }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Logo (Dark)</label>
                    <input class="form-control form-control-sm" type="file" name="general[logo_dark]" />
                    <div class="small text-muted mt-1">Current: {{ $cfg['general']['logo_dark_path'] ?? '—' }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Favicon</label>
                    <input class="form-control form-control-sm" type="file" name="general[favicon]" />
                    <div class="small text-muted mt-1">Current: {{ $cfg['general']['favicon_path'] ?? '—' }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Official Seal</label>
                    <input class="form-control form-control-sm" type="file" name="system_settings[branding][seal]" />
                    <div class="small text-muted mt-1">Current: {{ data_get($cfg,'system_settings.branding.seal_path','—') }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
              <div class="card-header bg-white fw-semibold">Financial year</div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Start (MM-DD)</label>
                    <input class="form-control form-control-sm" name="general[financial_year_start]" value="{{ $cfg['general']['financial_year_start'] ?? '01-01' }}" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">End (MM-DD)</label>
                    <input class="form-control form-control-sm" name="general[financial_year_end]" value="{{ $cfg['general']['financial_year_end'] ?? '12-31' }}" />
                  </div>
                </div>
                <div class="small text-muted mt-2">Used for reports and financial summaries.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- SECURITY & AUTH --}}
      <div class="tab-pane fade" id="tab-security" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Password policy</div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Min length</label>
                    <input class="form-control form-control-sm" type="number" min="6" max="30" name="accounts_roles[password_policy][min_length]" value="{{ data_get($cfg,'accounts_roles.password_policy.min_length',8) }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Expiry (days)</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="365" name="accounts_roles[password_policy][expiry_days]" value="{{ data_get($cfg,'accounts_roles.password_policy.expiry_days',0) }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">History</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="10" name="accounts_roles[password_policy][history]" value="{{ data_get($cfg,'accounts_roles.password_policy.history',3) }}" />
                  </div>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="accounts_roles[password_policy][complexity]" value="1" {{ data_get($cfg,'accounts_roles.password_policy.complexity') ? 'checked' : '' }}>
                  <label class="form-check-label">Require complexity (upper/lower/number/symbol)</label>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="accounts_roles[force_password_reset_first_login]" value="1" {{ data_get($cfg,'accounts_roles.force_password_reset_first_login') ? 'checked' : '' }}>
                  <label class="form-check-label">Force password reset on first login</label>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
              <div class="card-header bg-white fw-semibold">Sessions & lockouts</div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Session timeout (min)</label>
                    <input class="form-control form-control-sm" type="number" min="5" max="480" name="accounts_roles[session_timeout_minutes]" value="{{ data_get($cfg,'accounts_roles.session_timeout_minutes',60) }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Max login attempts</label>
                    <input class="form-control form-control-sm" type="number" min="1" max="20" name="accounts_roles[max_login_attempts]" value="{{ data_get($cfg,'accounts_roles.max_login_attempts',5) }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Lockout (min)</label>
                    <input class="form-control form-control-sm" type="number" min="1" max="240" name="accounts_roles[account_lockout_minutes]" value="{{ data_get($cfg,'accounts_roles.account_lockout_minutes',15) }}" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Email verification, OTP, 2FA</div>
              <div class="card-body">
                <div class="mb-2">
                  <label class="form-label small text-muted">Email verification on signup</label>
                  <select class="form-select form-select-sm" name="auth_security[email_verification_on_signup]">
                    @php($v = data_get($cfg,'auth_security.email_verification_on_signup','mandatory'))
                    <option value="mandatory" {{ $v==='mandatory'?'selected':'' }}>Mandatory</option>
                    <option value="optional" {{ $v==='optional'?'selected':'' }}>Optional</option>
                    <option value="disabled" {{ $v==='disabled'?'selected':'' }}>Disabled</option>
                  </select>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="auth_security[otp_on_login]" value="1" {{ data_get($cfg,'auth_security.otp_on_login') ? 'checked' : '' }}>
                  <label class="form-check-label">Enable OTP during login (global)</label>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-md-4">
                    <label class="form-label small text-muted">OTP length</label>
                    <input class="form-control form-control-sm" type="number" min="4" max="10" name="auth_security[otp_length]" value="{{ data_get($cfg,'auth_security.otp_length',6) }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Expiry (min)</label>
                    <input class="form-control form-control-sm" type="number" min="1" max="60" name="auth_security[otp_expiry_minutes]" value="{{ data_get($cfg,'auth_security.otp_expiry_minutes',10) }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Resend limit</label>
                    <input class="form-control form-control-sm" type="number" min="1" max="10" name="auth_security[otp_resend_limit]" value="{{ data_get($cfg,'auth_security.otp_resend_limit',3) }}" />
                  </div>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="auth_security[ip_logging]" value="1" {{ data_get($cfg,'auth_security.ip_logging') ? 'checked' : '' }}>
                  <label class="form-check-label">IP logging (audit)</label>
                </div>
                <div class="small text-muted mt-2">OTP/2FA delivery integrations are controlled under Integrations tab.</div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
              <div class="card-header bg-white fw-semibold">Profile self-service</div>
              <div class="card-body">
                <div class="mb-2">
                  <label class="form-label small text-muted">Change username</label>
                  @php($cu = data_get($cfg,'profile_self_service.change_username','allowed'))
                  <select class="form-select form-select-sm" name="profile_self_service[change_username]">
                    <option value="allowed" {{ $cu==='allowed'?'selected':'' }}>Allowed</option>
                    <option value="approval_required" {{ $cu==='approval_required'?'selected':'' }}>Approval required</option>
                    <option value="disabled" {{ $cu==='disabled'?'selected':'' }}>Disabled</option>
                  </select>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="profile_self_service[change_email_requires_verification]" value="1" {{ data_get($cfg,'profile_self_service.change_email_requires_verification') ? 'checked' : '' }}>
                  <label class="form-check-label">Change email requires verification</label>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="profile_self_service[logout_all_devices]" value="1" {{ data_get($cfg,'profile_self_service.logout_all_devices') ? 'checked' : '' }}>
                  <label class="form-check-label">Allow logout from all devices</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- PORTALS --}}
      <div class="tab-pane fade" id="tab-portal" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Public portal windows</div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Registration open</label>
                    <input class="form-control form-control-sm" type="date" name="portal_specific[public][registration_window][open]" value="{{ data_get($cfg,'portal_specific.public.registration_window.open') }}" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Registration close</label>
                    <input class="form-control form-control-sm" type="date" name="portal_specific[public][registration_window][close]" value="{{ data_get($cfg,'portal_specific.public.registration_window.close') }}" />
                  </div>
                </div>
                <div class="row g-2 mt-1">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Accreditation open</label>
                    <input class="form-control form-control-sm" type="date" name="portal_specific[public][accreditation_window][open]" value="{{ data_get($cfg,'portal_specific.public.accreditation_window.open') }}" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Accreditation close</label>
                    <input class="form-control form-control-sm" type="date" name="portal_specific[public][accreditation_window][close]" value="{{ data_get($cfg,'portal_specific.public.accreditation_window.close') }}" />
                  </div>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="portal_specific[public][public_notices_visible]" value="1" {{ data_get($cfg,'portal_specific.public.public_notices_visible') ? 'checked' : '' }}>
                  <label class="form-check-label">Public notices visible</label>
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="portal_specific[public][captcha_enabled]" value="1" {{ data_get($cfg,'portal_specific.public.captcha_enabled') ? 'checked' : '' }}>
                  <label class="form-check-label">Enable CAPTCHA</label>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Uploads</div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-8">
                    <label class="form-label small text-muted">Allowed upload formats (comma)</label>
                    <input class="form-control form-control-sm" name="portal_specific[public][allowed_upload_formats]" value="{{ is_array(data_get($cfg,'portal_specific.public.allowed_upload_formats')) ? implode(',', data_get($cfg,'portal_specific.public.allowed_upload_formats')) : (string)data_get($cfg,'portal_specific.public.allowed_upload_formats') }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Max upload size (MB)</label>
                    <input class="form-control form-control-sm" type="number" min="1" max="50" name="portal_specific[public][max_upload_size_mb]" value="{{ data_get($cfg,'portal_specific.public.max_upload_size_mb',10) }}" />
                  </div>
                </div>
                <div class="small text-muted mt-2">Upload rules are enforced for application documents and payment proofs.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- WORKFLOW --}}
      <div class="tab-pane fade" id="tab-workflow" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Routing rules</div>
              <div class="card-body">
                <div class="small text-muted mb-2">Current routing is enforced by status transitions: <span class="fw-semibold">Officer → Accounts → Registrar → Production</span>.</div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Auto status transitions</label>
                    <select class="form-select form-select-sm" name="workflow_approvals[auto_status_transitions]">
                      @php($ast = data_get($cfg,'workflow_approvals.auto_status_transitions',true))
                      <option value="1" {{ $ast ? 'selected' : '' }}>Enabled</option>
                      <option value="0" {{ !$ast ? 'selected' : '' }}>Disabled</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Multi-step approvals</label>
                    <select class="form-select form-select-sm" name="workflow_approvals[multi_step]">
                      @php($ms = data_get($cfg,'workflow_approvals.multi_step',false))
                      <option value="0" {{ !$ms ? 'selected' : '' }}>Single-step</option>
                      <option value="1" {{ $ms ? 'selected' : '' }}>Multi-step</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="card border-0 shadow-sm mt-3">
              <div class="card-header bg-white fw-semibold">SLA settings (hours)</div>
              <div class="card-body">
                <div class="row g-2">
                  @php($sla = data_get($cfg,'workflow.sla_hours', data_get($defaults,'workflow.sla_hours',[])))
                  @foreach(['submitted','officer_review','accounts_review','registrar_review','production_queue'] as $k)
                    <div class="col-md-4">
                      <label class="form-label small text-muted">{{ str_replace('_',' ',ucfirst($k)) }}</label>
                      <input class="form-control form-control-sm" type="number" min="1" max="720" name="workflow[sla_hours][{{ $k }}]" value="{{ $sla[$k] ?? '' }}" />
                    </div>
                  @endforeach
                </div>
                <div class="small text-muted mt-2">SLA breaches appear in Super Admin alerts.</div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Application & status management (advanced)</div>
              <div class="card-body">
                <div class="small text-muted">For complex rules, paste JSON below (stored under <code>applications_status</code> and <code>workflow_approvals</code>).</div>
                <label class="form-label small text-muted mt-2">Status definitions JSON</label>
                <textarea class="form-control form-control-sm" rows="6" name="applications_status[definitions]">{{ json_encode(data_get($cfg,'applications_status.definitions',[]), JSON_PRETTY_PRINT) }}</textarea>
                <label class="form-label small text-muted mt-2">Transition rules JSON</label>
                <textarea class="form-control form-control-sm" rows="6" name="applications_status[transition_rules]">{{ json_encode(data_get($cfg,'applications_status.transition_rules',[]), JSON_PRETTY_PRINT) }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- FINANCE --}}
      <div class="tab-pane fade" id="tab-finance" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Payment channels & PayNow</div>
              <div class="card-body">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="payments_finance[paynow_enabled]" value="1" {{ data_get($cfg,'payments_finance.paynow_enabled') ? 'checked' : '' }}>
                  <label class="form-check-label">Enable PayNow gateway</label>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-md-8">
                    <label class="form-label small text-muted">PayNow callback URL</label>
                    <input class="form-control form-control-sm" name="payments_finance[paynow_callback_url]" value="{{ data_get($cfg,'payments_finance.paynow_callback_url') }}" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label small text-muted">Timeout (min)</label>
                    <input class="form-control form-control-sm" type="number" min="1" max="240" name="payments_finance[paynow_timeout_minutes]" value="{{ data_get($cfg,'payments_finance.paynow_timeout_minutes',30) }}" />
                  </div>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Rounding</label>
                    @php($rnd = data_get($cfg,'payments_finance.rounding','none'))
                    <select class="form-select form-select-sm" name="payments_finance[rounding]">
                      <option value="none" {{ $rnd==='none'?'selected':'' }}>None</option>
                      <option value="nearest_cent" {{ $rnd==='nearest_cent'?'selected':'' }}>Nearest cent</option>
                      <option value="nearest_dollar" {{ $rnd==='nearest_dollar'?'selected':'' }}>Nearest dollar</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">VAT (%)</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="100" name="fees[tax][vat_percent]" value="{{ data_get($cfg,'fees.tax.vat_percent',0) }}" />
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Waivers</div>
              <div class="card-body">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="payments_finance[waivers][enabled]" value="1" {{ data_get($cfg,'payments_finance.waivers.enabled') ? 'checked' : '' }}>
                  <label class="form-check-label">Enable waivers</label>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Partial max (%)</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="100" name="payments_finance[waivers][partial_max_percent]" value="{{ data_get($cfg,'payments_finance.waivers.partial_max_percent',100) }}" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Auto-approve threshold</label>
                    <input class="form-control form-control-sm" type="number" min="0" name="payments_finance[waivers][auto_approval_threshold]" value="{{ data_get($cfg,'payments_finance.waivers.auto_approval_threshold',0) }}" />
                  </div>
                </div>
                <label class="form-label small text-muted mt-2">Eligibility criteria</label>
                <textarea class="form-control form-control-sm" rows="4" name="waivers[eligibility_criteria]">{{ data_get($cfg,'waivers.eligibility_criteria','') }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- CONTENT --}}
      <div class="tab-pane fade" id="tab-content" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Module access (enable/disable)</div>
              <div class="card-body">
                @foreach(['notices'=>'Notices','news'=>'News','events'=>'Events','complaints'=>'Complaints','payments'=>'Payments'] as $k=>$label)
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="monitoring_maintenance[modules][{{ $k }}]" value="1" {{ data_get($cfg,"monitoring_maintenance.modules.$k") ? 'checked' : '' }}>
                    <label class="form-check-label">Enable {{ $label }} module</label>
                  </div>
                @endforeach
                <div class="small text-muted mt-2">Disabled modules are blocked at route/controller level.</div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Categories</div>
              <div class="card-body">
                <label class="form-label small text-muted">News categories (one per line)</label>
                <textarea class="form-control form-control-sm" rows="4" name="content_management[news_categories_text]">{{ implode("\n", data_get($cfg,'content_management.news_categories',[])) }}</textarea>
                <label class="form-label small text-muted mt-2">Notice categories</label>
                <textarea class="form-control form-control-sm" rows="4" name="content_management[notice_categories_text]">{{ implode("\n", data_get($cfg,'content_management.notice_categories',[])) }}</textarea>
                <label class="form-label small text-muted mt-2">Event categories</label>
                <textarea class="form-control form-control-sm" rows="4" name="content_management[event_categories_text]">{{ implode("\n", data_get($cfg,'content_management.event_categories',[])) }}</textarea>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Media Training Hub</div>
              <div class="card-body">
                <label class="form-label small text-muted" for="mediaHubUrl">Hub URL</label>
                <input class="form-control form-control-sm" id="mediaHubUrl" type="url" name="website_content[media_hub_url]" value="{{ data_get($cfg, 'website_content.media_hub_url', config('services.media_hub.url')) }}" placeholder="https://traininghub.zmc.org.zw" required>
                <div class="small text-muted mt-2">The public portal uses this configured destination for its Media Hub links.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- REPORTS & AUDIT --}}
      <div class="tab-pane fade" id="tab-reports" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Reports defaults</div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Default period (days)</label>
                    <input class="form-control form-control-sm" type="number" min="1" max="365" name="reports_analytics[default_period_days]" value="{{ data_get($cfg,'reports_analytics.default_period_days',30) }}" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Retention (days, 0=forever)</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="5000" name="reports_analytics[data_retention_days]" value="{{ data_get($cfg,'reports_analytics.data_retention_days',0) }}" />
                  </div>
                </div>
                <div class="small text-muted mt-2">Exports are available from Reports pages (CSV + Print).</div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Audit & compliance</div>
              <div class="card-body">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="audit_compliance[audit_logging_enabled]" value="1" {{ data_get($cfg,'audit_compliance.audit_logging_enabled') ? 'checked' : '' }}>
                  <label class="form-check-label">Enable audit logging</label>
                </div>
                <div class="row g-2 mt-2">
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Retention (days)</label>
                    <input class="form-control form-control-sm" type="number" min="0" max="5000" name="audit_compliance[retention_days]" value="{{ data_get($cfg,'audit_compliance.retention_days',365) }}" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label small text-muted">Scope</label>
                    <input class="form-control form-control-sm" name="audit_compliance[scope]" value="{{ data_get($cfg,'audit_compliance.scope','all') }}" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- INTEGRATIONS --}}
      <div class="tab-pane fade" id="tab-integrations" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Email & SMS</div>
              <div class="card-body">
                <div class="mb-2">
                  <label class="form-label small text-muted">Email driver</label>
                  <input class="form-control form-control-sm" name="integrations[email][driver]" value="{{ data_get($cfg,'integrations.email.driver','smtp') }}" />
                </div>
                <div class="mb-2">
                  <label class="form-label small text-muted">SMS provider</label>
                  <input class="form-control form-control-sm" name="integrations[sms][provider]" value="{{ data_get($cfg,'integrations.sms.provider','') }}" />
                </div>
                <div class="small text-muted">Credentials are stored as environment variables in production.</div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">PayNow</div>
              <div class="card-body">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="integrations[paynow][enabled]" value="1" {{ data_get($cfg,'integrations.paynow.enabled') ? 'checked' : '' }}>
                  <label class="form-check-label">Integration enabled</label>
                </div>
                <div class="small text-muted mt-2">Credentials should be set in <code>.env</code> (secured). The UI toggles enable/disable usage.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- MENU & UI --}}
      <div class="tab-pane fade" id="tab-ui" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Theme & accessibility</div>
              <div class="card-body">
                @php($theme = data_get($cfg,'menu_ui.theme','light'))
                <label class="form-label small text-muted">Theme</label>
                <select class="form-select form-select-sm" name="menu_ui[theme]">
                  <option value="light" {{ $theme==='light'?'selected':'' }}>Light</option>
                  <option value="dark" {{ $theme==='dark'?'selected':'' }}>Dark</option>
                </select>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="menu_ui[accessibility][high_contrast]" value="1" {{ data_get($cfg,'menu_ui.accessibility.high_contrast') ? 'checked' : '' }}>
                  <label class="form-check-label">High contrast</label>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white fw-semibold">Role menus (advanced JSON)</div>
              <div class="card-body">
                <div class="small text-muted mb-2">Optional: define sidebar menu configuration per role.</div>
                <textarea class="form-control form-control-sm" rows="10" name="menu_ui[sidebar_by_role]">{{ json_encode(data_get($cfg,'menu_ui.sidebar_by_role',[]), JSON_PRETTY_PRINT) }}</textarea>
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
