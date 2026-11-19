@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
@php
    $isStaffLogin = session()->has('staff_selected_role');
    $publicPortal = session('public_selected_portal');
    $publicPortalLabel = match ($publicPortal) {
        'journalist' => 'Media Practitioner',
        'mass_media' => 'Media House',
        default => 'Portal',
    };
    $publicPortalSubtitle = match ($publicPortal) {
        'journalist' => 'Sign in to continue your media practitioner accreditation journey.',
        'mass_media' => 'Sign in to continue your media house registration journey.',
        default => 'Access your ZMC portal dashboard.',
    };
    $staffRoleKey = session('staff_selected_role');
    $staffRoleLabel = match ($staffRoleKey) {
        'accreditation_officer' => 'Accreditation Officer',
        'registrar' => 'Registrar',
        'accounts_payments' => 'Payments / Accounts',
        'production' => 'Production',
        default => 'Staff',
    };
    $loginTitle = $isStaffLogin ? 'Staff Login' : ($publicPortal === 'journalist' ? 'Media Practitioner Login' : ($publicPortal === 'mass_media' ? 'Media House Login' : 'Sign In'));
@endphp

<header class="auth-page-header">
    <span class="auth-eyebrow"><i class="ri-shield-user-line" aria-hidden="true"></i>{{ $isStaffLogin ? 'Staff Access' : 'Secure Access' }}</span>
    <h1 class="auth-title">{{ $loginTitle }}</h1>
    <p class="auth-subtitle">{{ $isStaffLogin ? 'Sign in to access staff tools.' : $publicPortalSubtitle }}</p>
    @if($isStaffLogin)
        <p class="auth-note">Role selected: <strong>{{ $staffRoleLabel }}</strong> &nbsp;·&nbsp; <a class="auth-link" href="{{ url('/staff/login') }}">Staff Login</a></p>
    @endif
</header>

@if($errors->any())
    <div class="auth-alert error" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
@endif
@if(session('status'))
    <div class="auth-alert" role="status"><i class="ri-checkbox-circle-line" aria-hidden="true"></i><span>{{ session('status') }}</span></div>
@endif
@if(session('error'))
    <div class="auth-alert error" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i><span>{{ session('error') }}</span></div>
@endif

<form method="POST" action="{{ $isStaffLogin ? route('staff.login.store') : route('auth.login.store') }}">
    @csrf
    <div class="auth-field">
        <label class="auth-label" for="email">Email / Username</label>
        <div class="auth-input-wrap">
            <i class="ri-user-line auth-input-icon" aria-hidden="true"></i>
            <input type="text" id="email" name="email" class="auth-input @error('email') is-invalid @enderror" placeholder="e.g. john@example.com" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        @error('email')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
    </div>
    <div class="auth-field">
        <div class="auth-pair-row">
            <label class="auth-label" for="password">Password</label>
            @unless($isStaffLogin)<a class="auth-link" href="{{ route('password.request') }}">Forgot Password?</a>@endunless
        </div>
        <div class="auth-input-wrap">
            <i class="ri-lock-line auth-input-icon" aria-hidden="true"></i>
            <input type="password" id="password" name="password" class="auth-input @error('password') is-invalid @enderror" placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="auth-toggle" id="togglePwd" aria-label="Show password" aria-pressed="false"><i class="ri-eye-off-line" aria-hidden="true"></i></button>
        </div>
        @error('password')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
    </div>
    <label class="auth-check-row"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}><span>Remember me for 30 days</span></label>
    <button type="submit" class="auth-button"><i class="ri-login-box-line" aria-hidden="true"></i>{{ $isStaffLogin ? 'Sign In (Staff)' : 'Sign In to Account' }}</button>

@if(!$isStaffLogin)
        <p class="auth-footer-text">Don't have an account? <a href="{{ $publicPortal === 'journalist' ? route('media_practitioner.register') : ($publicPortal === 'mass_media' ? route('media_house.register') : route('register')) }}">Sign Up {{ $publicPortalLabel }}</a></p>
    @else
        <p class="auth-footer-text">Staff accounts are created by the administrator.</p>
    @endif
</form>
@endsection

@push('scripts')
<script>
(() => {
    const button = document.getElementById('togglePwd');
    const input = document.getElementById('password');
    if (!button || !input) return;
    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show));
        button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} password`);
        button.querySelector('i').className = show ? 'ri-eye-line' : 'ri-eye-off-line';
    });
})();
</script>
@endpush
