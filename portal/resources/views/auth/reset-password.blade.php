@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<header class="auth-page-header">
    <span class="auth-eyebrow"><i class="ri-lock-password-line" aria-hidden="true"></i> Secure account</span>
    <h1 class="auth-title">Reset password</h1>
    <p class="auth-subtitle">Choose a new password for your account.</p>
</header>

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <div class="auth-input-wrap">
            <i class="ri-mail-line auth-input-icon" aria-hidden="true"></i>
            <input id="email" type="email" name="email" class="auth-input @error('email') is-invalid @enderror" value="{{ old('email', $email ?? '') }}" required autocomplete="email" autofocus>
        </div>
        @error('email')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password">New password</label>
        <div class="auth-input-wrap">
            <i class="ri-lock-line auth-input-icon" aria-hidden="true"></i>
            <input id="password" type="password" name="password" class="auth-input @error('password') is-invalid @enderror" required autocomplete="new-password">
            <button type="button" class="auth-toggle" data-password-toggle="password" aria-label="Show password" aria-pressed="false"><i class="ri-eye-off-line" aria-hidden="true"></i></button>
        </div>
        @error('password')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
    </div>

    <div class="auth-field">
        <label class="auth-label" for="password_confirmation">Confirm password</label>
        <div class="auth-input-wrap">
            <i class="ri-lock-line auth-input-icon" aria-hidden="true"></i>
            <input id="password_confirmation" type="password" name="password_confirmation" class="auth-input" required autocomplete="new-password">
            <button type="button" class="auth-toggle" data-password-toggle="password_confirmation" aria-label="Show password confirmation" aria-pressed="false"><i class="ri-eye-off-line" aria-hidden="true"></i></button>
        </div>
    </div>

    <button class="auth-button" type="submit"><i class="ri-lock-password-line" aria-hidden="true"></i>Reset password</button>
</form>

<p class="auth-footer-text"><a href="{{ route('login') }}"><i class="ri-arrow-left-line" aria-hidden="true"></i> Back to login</a></p>
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
