<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * If a staff user is authenticated and tries to access applicant portals,
 * force a logout so they must login as an applicant.
 */
class EnsureApplicantPortal
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole([
                'super_admin',
                'accreditation_officer',
                'accounts_payments',
                'registrar',
                'production',
                'it_admin',
                'auditor',
                'director',
                'oversight',
            ])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')
                    ->withErrors(['email' => 'Please login as an applicant to access the applicant portal.'])
                    ->withCookie(\App\Http\Middleware\TokenAuth::forgetAuthCookie());
            }
        }

        return $next($request);
    }
}
