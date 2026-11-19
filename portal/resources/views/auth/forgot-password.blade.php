@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<header class="auth-page-header">
    <span class="auth-eyebrow"><i class="ri-key-2-line" aria-hidden="true"></i> Account recovery</span>
    <h1 class="auth-title">Forgot Password</h1>
    <p class="auth-subtitle">Enter your email address and we’ll send you a reset link.</p>
</header>

@if(session('status'))
    <div class="auth-alert" role="status"><i class="ri-checkbox-circle-line" aria-hidden="true"></i><span>{{ session('status') }}</span></div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="auth-field">
        <label class="auth-label" for="email">Email Address</label>
        <div class="auth-input-wrap">
            <i class="ri-mail-line auth-input-icon" aria-hidden="true"></i>
            <input id="email" name="email" type="email" class="auth-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="name@example.com">
        </div>
        @error('email')<div class="auth-error"><i class="ri-error-warning-line" aria-hidden="true"></i>{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="auth-button"><i class="ri-mail-send-line" aria-hidden="true"></i>Send Reset Link</button>
</form>

<p class="auth-footer-text"><a href="{{ route('login') }}"><i class="ri-arrow-left-line" aria-hidden="true"></i> Back to sign in</a></p>
@endsection
