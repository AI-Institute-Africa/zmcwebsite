<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Enforce portal separation for media practitioner accounts.
 * 
 * Media practitioner accounts (those who registered for the accreditation portal)
 * should ONLY access the accreditation portal.
 * They should NOT access the media house portal.
 * 
 * This middleware is applied to media house routes to prevent unauthorized access.
 */
class EnforceMediaPractitionerPortal
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }

        // Check if user is a media practitioner (accreditation applicant)
        if ($this->isMediaPractitioner($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your practitioner account cannot access the media house portal. Please login to the accreditation portal or use your media house account.'
                ])
                ->withCookie(\App\Http\Middleware\TokenAuth::forgetAuthCookie());
        }

        return $next($request);
    }

    /**
     * Determine if a user is a media practitioner (accreditation applicant).
     * 
     * A user is considered a practitioner if:
     * 1. They registered for 'journalist' portal (check profile_data['portal']), OR
     * 2. They have an accreditation role, OR
     * 3. They have submitted accreditation applications
     */
    private function isMediaPractitioner($user): bool
    {
        // First check: registered portal type (most reliable)
        if ($user->profile_data && is_array($user->profile_data)) {
            $registeredPortal = $user->profile_data['portal'] ?? null;
            if ($registeredPortal === 'journalist') {
                return true;
            }
        }

        // Second check: journalist-related roles
        if ($user->hasAnyRole(['journalist', 'accredited_journalist', 'media_practitioner'])) {
            return true;
        }

        // Third check: accreditation applications
        $accreditationCount = \App\Models\Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'accreditation')
            ->count();

        return $accreditationCount > 0;
    }
}
