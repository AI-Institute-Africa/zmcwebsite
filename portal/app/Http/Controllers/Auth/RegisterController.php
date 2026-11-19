<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole([
            'super_admin','accreditation_officer','accounts_payments','registrar','production','it_admin','auditor','director','oversight'
        ])) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Staff accounts cannot register or access public applicant portals. Please sign in as an applicant.'])->withCookie(\App\Http\Middleware\TokenAuth::forgetAuthCookie());
        }
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $portal = $request->session()->get('public_selected_portal');

        $rules = [
            // Strict, RFC-compliant email format check + filter_var fallback.
            // This rejects malformed strings like "ghvgyyuvy.com" or "name@.com".
            'email'    => ['required', 'string', 'email:rfc,strict,filter', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'phone_country_code' => ['required','string','max:10'],
            'phone_number'       => ['required','string','max:30'],
        ];

        if ($portal === 'mass_media') {
            $rules['organization_name'] = ['required','string','max:255'];
        } else {
            $rules['first_name'] = ['required', 'string', 'max:255'];
            $rules['last_name']  = ['required', 'string', 'max:255'];
        }

        $request->validate($rules);

        $fullName = $portal === 'mass_media'
            ? trim($request->organization_name)
            : trim($request->first_name . ' ' . $request->last_name);

        $profile = [
            'portal' => $portal ?: 'journalist',
        ];
        if ($portal === 'mass_media') {
            $profile['organization_name'] = $request->organization_name;
        } else {
            $profile['first_name'] = $request->first_name;
            $profile['surname'] = $request->last_name;
        }

        $rawToken = Str::random(64);

        $user = User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_status' => 'pending_verification',
            'account_type' => 'public',
            'phone_country_code' => $request->phone_country_code,
            'phone_number' => $request->phone_number,
            'profile_data' => $profile,
            'email_verification_token' => hash('sha256', $rawToken),
            'email_verification_sent_at' => now(),
        ]);

        $this->sendVerificationEmail($user, $rawToken);

        \App\Support\AuditTrail::log('account_created_applicant', $user);

        if ($portal) {
            $request->session()->put('public_selected_portal', $portal);
        }

        $request->session()->put('verification_email', $user->email);

        return redirect()->route('verification.notice');
    }

    public function showVerificationNotice(Request $request)
    {
        $email = $request->session()->get('verification_email', '');
        return view('auth.verify-email', compact('email'));
    }

    public function verifyEmail(Request $request, string $token)
    {
        $user = User::where('email_verification_token', hash('sha256', $token))->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Invalid or expired verification link. Please try signing up again or request a new verification email.');
        }

        if ($user->email_verification_sent_at && $user->email_verification_sent_at->lt(now()->subHours(24))) {
            return redirect()->route('verification.notice')
                ->with('error', 'This verification link has expired. Please request a new one.')
                ->with('verification_email', $user->email);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'account_status' => 'active',
            'approved_at' => now(),
        ]);

        Auth::login($user);

        $portal = $user->profile_data['portal'] ?? 'journalist';
        $request->session()->put('public_selected_portal', $portal);

        \App\Support\AuditTrail::log('email_verified', $user);

        $authCookie = \App\Http\Middleware\TokenAuth::makeAuthCookie($user, 'applicant');

        return redirect()->route('home')->withCookie($authCookie)->with('success', 'Your email has been verified. Welcome to the ZMC Portal!');
    }

    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email:rfc,strict,filter'],
        ]);

        $user = User::where('email', $request->email)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return back()->with('success', 'If an account with that email exists and is unverified, a new verification link has been sent.');
        }

        if ($user->email_verification_sent_at && $user->email_verification_sent_at->gt(now()->subMinute())) {
            return back()->with('success', 'If an account with that email exists and is unverified, a new verification link has been sent.');
        }

        $rawToken = Str::random(64);
        $user->update([
            'email_verification_token' => hash('sha256', $rawToken),
            'email_verification_sent_at' => now(),
        ]);

        $this->sendVerificationEmail($user, $rawToken);

        return back()->with('success', 'A new verification link has been sent to your email.');
    }

    private function sendVerificationEmail(User $user, string $token): void
    {
        $verificationUrl = route('verification.verify', ['token' => $token]);
        $appName = config('app.name', 'ZMC Portal');

        try {
            Mail::send('emails.verify-email', [
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'appName' => $appName,
            ], function ($message) use ($user, $appName) {
                $message->to($user->email, $user->name)
                    ->subject("Verify Your Email - {$appName}");
            });
        } catch (\Throwable $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }
    }
}
