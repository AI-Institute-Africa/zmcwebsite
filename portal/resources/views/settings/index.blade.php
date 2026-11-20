@extends('layouts.portal')

@section('title', 'Settings')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Settings</h1>
      <div class="text-muted mt-1" style="font-size:13px;">Manage your account, security and preferences.</div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-dark"><i class="ri-arrow-left-line me-1"></i> Back</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Please fix the following:</div>
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-2">
          <div class="list-group list-group-flush" id="settingsTabs" role="tablist">
            <a class="list-group-item list-group-item-action active" data-bs-toggle="list" href="#tab-profile" role="tab">
              <i class="ri-user-3-line me-2"></i> Profile
            </a>
            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#tab-security" role="tab">
              <i class="ri-lock-password-line me-2"></i> Security
            </a>
            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#tab-appearance" role="tab">
              <i class="ri-palette-line me-2"></i> Appearance
            </a>
            <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#tab-integrations" role="tab">
              <i class="ri-links-line me-2"></i> Integrations
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="tab-content">

        <div class="tab-pane fade show active" id="tab-profile" role="tabpanel">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
              <div class="fw-bold">Profile Information</div>
              <div class="text-muted" style="font-size:12px;">Full personal details for your ZMC account.</div>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('settings.profile') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-12 text-center mb-3">
                        <div class="position-relative d-inline-block">
                            @php
                                $pic = ($user->profile_data['profile_picture'] ?? null)
                                    ? route('settings.profile-photo')
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=random';
                            @endphp
                            <img id="profile-photo-preview" src="{{ $pic }}" alt="Profile photo" class="rounded-circle border" style="width:100px; height:100px; object-fit:cover;">
                            <label for="profile_pic_input" class="btn btn-sm btn-light rounded-circle border shadow-sm position-absolute bottom-0 end-0" style="padding:4px 6px;">
                                <i class="ri-camera-line"></i>
                            </label>
                            <input type="file" name="profile_picture" id="profile_pic_input" hidden accept="image/*">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Full Name (as per ID)</label>
                        <input name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email Address</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Primary Phone Number</label>
                        <input name="phone_number" class="form-control" value="{{ old('phone_number', $user->phone_number) }}" placeholder="+263...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Secondary Phone Number</label>
                        <input name="profile[secondary_phone]" class="form-control" value="{{ old('profile.secondary_phone', $user->profile_data['secondary_phone'] ?? '') }}" placeholder="+263...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">National ID Number</label>
                        <input name="profile[national_id]" class="form-control zmc-nid-input" value="{{ old('profile.national_id', $user->profile_data['national_id'] ?? '') }}" placeholder="XX-XXXXXXXXXXX">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Passport Number (Foreigners)</label>
                        <input name="profile[passport_number]" class="form-control" value="{{ old('profile.passport_number', $user->profile_data['passport_number'] ?? '') }}" placeholder="Travel Doc No.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Date of Birth</label>
                        <input name="profile[dob]" type="date" class="form-control" value="{{ old('profile.dob', $user->profile_data['dob'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sex</label>
                        <select name="profile[gender]" class="form-select">
                            <option value="">Select...</option>
                            <option value="male" @selected(($user->profile_data['gender'] ?? '') === 'male')>Male</option>
                            <option value="female" @selected(($user->profile_data['gender'] ?? '') === 'female')>Female</option>
                            <option value="other" @selected(($user->profile_data['gender'] ?? '') === 'other')>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Nationality</label>
                        <input name="profile[nationality]" class="form-control" value="{{ old('profile.nationality', $user->profile_data['nationality'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Residential Address</label>
                        <textarea name="profile[residential_address]" class="form-control" rows="2">{{ old('profile.residential_address', $user->profile_data['residential_address'] ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Postal Address</label>
                        <textarea name="profile[postal_address]" class="form-control" rows="2">{{ old('profile.postal_address', $user->profile_data['postal_address'] ?? '') }}</textarea>
                    </div>

                    @if(($user->account_type ?? '') === 'mediahouse')
                    <div class="col-12">
                        <hr>
                        <div class="fw-bold mb-2">Social Media & Online Presence (Media House)</div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="ri-twitter-x-line"></i></span>
                                    <input name="profile[social][twitter]" class="form-control" value="{{ $user->profile_data['social']['twitter'] ?? '' }}" placeholder="Handle">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="ri-facebook-box-line"></i></span>
                                    <input name="profile[social][facebook]" class="form-control" value="{{ $user->profile_data['social']['facebook'] ?? '' }}" placeholder="URL">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="ri-linkedin-box-line"></i></span>
                                    <input name="profile[social][linkedin]" class="form-control" value="{{ $user->profile_data['social']['linkedin'] ?? '' }}" placeholder="URL">
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                <hr>
                <button class="btn btn-primary mt-2"><i class="ri-save-3-line me-1"></i> Save Changes</button>
              </form>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-security" role="tabpanel">
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
              <div class="fw-bold">Password</div>
              <div class="text-muted" style="font-size:12px;">Change your login password.</div>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('settings.password') }}">
                @csrf
                <div class="mb-3">
                  <label class="form-label fw-bold">Current Password</label>
                  <input name="current_password" type="password" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">New Password</label>
                  <input name="password" type="password" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">Confirm New Password</label>
                  <input name="password_confirmation" type="password" class="form-control" required>
                </div>
                <button class="btn btn-primary"><i class="ri-shield-keyhole-line me-1"></i> Update Password</button>
              </form>
            </div>
          </div>

          <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3">
              <div class="fw-bold">Two-Factor Authentication</div>
              <div class="text-muted" style="font-size:12px;">Secure your account with OTP via Email/SMS.</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.security') }}">
                    @csrf
                    <div class="form-check form-switch mb-3">
                        <input name="two_factor_enabled" type="hidden" value="0">
                        <input name="two_factor_enabled" class="form-check-input" type="checkbox" role="switch" id="twoFactorSwitch" value="1" @checked($user->profile_data['two_factor_enabled'] ?? false)>
                        <label class="form-check-label fw-bold" for="twoFactorSwitch">Enable Two-Factor Authentication</label>
                    </div>
                    <button class="btn btn-outline-primary btn-sm">Save Preference</button>
                </form>
            </div>
          </div>

          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
              <div class="fw-bold">Login Activity History</div>
              <div class="text-muted" style="font-size:12px;">Your recent sign-ins.</div>
            </div>
            <div class="card-body p-0">
               <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                        <thead class="bg-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>IP Address</th>
                                <th>Device / Browser</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loginActivity as $act)
                                <tr>
                                    <td>{{ ($act->login_at ?? $act->created_at)?->format('M d, Y H:i') }}</td>
                                    <td>{{ $act->ip_address }}</td>
                                    <td><span class="text-muted">{{ substr($act->user_agent ?? '', 0, 40) }}...</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3">No recent activity detected.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
               </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-appearance" role="tabpanel">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
              <div class="fw-bold">Appearance</div>
              <div class="text-muted" style="font-size:12px;">Choose light or dark theme.</div>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('settings.theme') }}" class="d-flex gap-2 align-items-center flex-wrap">
                @csrf
                <select name="theme" class="form-select" style="max-width:220px;">
                  <option value="light" @selected(($user->theme ?? 'light') === 'light')>Light</option>
                  <option value="dark" @selected(($user->theme ?? 'light') === 'dark')>Dark</option>
                </select>
                <button class="btn btn-outline-primary" type="submit"><i class="ri-palette-line me-1"></i> Apply</button>
                <span class="text-muted small">Applies across your portal pages.</span>
              </form>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-integrations" role="tabpanel">
          <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
              <div class="fw-bold">Integrations</div>
              <div class="text-muted" style="font-size:12px;">(Placeholder) Connect external services.</div>
            </div>
            <div class="card-body">
              <div class="alert alert-info mb-0">Integration settings will be added here (e.g., email provider, SMS gateway, analytics).</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<script>
document.getElementById('profile_pic_input')?.addEventListener('change', function () {
  const file = this.files?.[0];
  const preview = document.getElementById('profile-photo-preview');
  if (file && preview) preview.src = URL.createObjectURL(file);
});
</script>
@endsection
