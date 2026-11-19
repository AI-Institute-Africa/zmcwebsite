@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
@php
    $publicPortal = session('public_selected_portal', 'journalist');
    $isMediaHouse = $publicPortal === 'mass_media';
@endphp

<header class="auth-page-header">
    <span class="auth-eyebrow"><i class="ri-user-add-line" aria-hidden="true"></i> Create your account</span>
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-subtitle">One account for all ZMC digital services.</p>
</header>

<form method="POST" action="{{ route('auth.register.store') }}">
    @csrf

    @if($isMediaHouse)
        <div class="auth-field">
            <label class="auth-label" for="organization_name">Organization’s Name</label>
            <div class="auth-input-wrap">
                <i class="ri-building-line auth-input-icon" aria-hidden="true"></i>
                <input id="organization_name" name="organization_name" type="text" class="auth-input @error('organization_name') is-invalid @enderror" value="{{ old('organization_name') }}" autocomplete="organization" required autofocus>
            </div>
            @error('organization_name')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
        </div>
    @else
        <div class="auth-grid">
            <div class="auth-field">
                <label class="auth-label" for="first_name">Name</label>
                <div class="auth-input-wrap">
                    <i class="ri-user-line auth-input-icon" aria-hidden="true"></i>
                    <input id="first_name" name="first_name" type="text" class="auth-input @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" autocomplete="given-name" required autofocus>
                </div>
                @error('first_name')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
            </div>
            <div class="auth-field">
                <label class="auth-label" for="last_name">Surname</label>
                <div class="auth-input-wrap">
                    <i class="ri-user-line auth-input-icon" aria-hidden="true"></i>
                    <input id="last_name" name="last_name" type="text" class="auth-input @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" autocomplete="family-name" required>
                </div>
                @error('last_name')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
            </div>
        </div>
    @endif

    <div class="auth-field">
        <label class="auth-label" for="email">Email Address</label>
        <div class="auth-input-wrap">
            <i class="ri-mail-line auth-input-icon" aria-hidden="true"></i>
            <input id="email" name="email" type="email" class="auth-input @error('email') is-invalid @enderror" value="{{ old('email') }}" autocomplete="email" required>
        </div>
        @error('email')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
    </div>

    <div class="auth-grid">
        <div class="auth-field">
            <label class="auth-label" id="phone_country_code_label">Country code</label>
            <x-country-code-select />
            @error('phone_country_code')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
        </div>
        <div class="auth-field">
            <label class="auth-label" for="phone_number">Phone Number</label>
            <div class="auth-input-wrap">
                <i class="ri-phone-line auth-input-icon" aria-hidden="true"></i>
                <input id="phone_number" name="phone_number" type="tel" class="auth-input @error('phone_number') is-invalid @enderror" value="{{ old('phone_number') }}" autocomplete="tel-national" required>
            </div>
            @error('phone_number')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="auth-grid">
        <div class="auth-field">
            <label class="auth-label" for="password">Password</label>
            <div class="auth-input-wrap">
                <i class="ri-lock-line auth-input-icon" aria-hidden="true"></i>
                <input id="password" name="password" type="password" class="auth-input @error('password') is-invalid @enderror" autocomplete="new-password" required>
                <button type="button" class="auth-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false"><i class="ri-eye-off-line" aria-hidden="true"></i></button>
            </div>
            @error('password')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
        </div>
        <div class="auth-field">
            <label class="auth-label" for="password_confirmation">Confirm Password</label>
            <div class="auth-input-wrap">
                <i class="ri-lock-line auth-input-icon" aria-hidden="true"></i>
                <input id="password_confirmation" name="password_confirmation" type="password" class="auth-input" autocomplete="new-password" required>
                <button type="button" class="auth-toggle" data-password-toggle="password_confirmation" aria-label="Show password confirmation" aria-pressed="false"><i class="ri-eye-off-line" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>

    <button class="auth-button" type="submit"><i class="ri-user-add-line" aria-hidden="true"></i>Create Account</button>
    <div class="auth-divider"><span>Already have an account?</span></div>
    <div class="auth-footer-text auth-footer-text-compact"><a href="{{ $publicPortal === 'journalist' ? route('media_practitioner.login') : ($publicPortal === 'mass_media' ? route('media_house.login') : route('login')) }}">Sign in to your account</a></div>
</form>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-password-toggle]').forEach(button => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(show));
        button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} ${input.id === 'password' ? 'password' : 'password confirmation'}`);
        button.querySelector('i').className = show ? 'ri-eye-line' : 'ri-eye-off-line';
    });
});
</script>
@endpush
