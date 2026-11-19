<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Enforce portal separation for media house staff accounts.
 * 
 * Media house staff accounts should ONLY access the media house portal.
 * They should NOT access the accreditation portal for practitioners.
 * 
 * This middleware is applied to accreditation portal routes to prevent
 * media house staff from accessing practitioner features.
 */
class EnforceAccreditationPortal
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $next($request);
        }

        // Check if user is a media house staff member trying to access accreditation portal
        if ($this->isMediaHouseStaff($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your media house account cannot access the accreditation portal. Please login to the media house portal or use your practitioner account.'
                ])
                ->withCookie(\App\Http\Middleware\TokenAuth::forgetAuthCookie());
        }

        return $next($request);
    }

    /**
     * Determine if a user is a media house staff member.
     * 
     * A user is considered media house staff if:
     * 1. They registered for 'mass_media' portal (check profile_data['portal']), OR
     * 2. They are linked as media_house_user_id in media_house_staff table, OR
     * 3. They have submitted registration applications for media houses
     */
    private function isMediaHouseStaff($user): bool
    {
        // First check: registered portal type (most reliable)
        if ($user->profile_data && is_array($user->profile_data)) {
            $registeredPortal = $user->profile_data['portal'] ?? null;
            if ($registeredPortal === 'mass_media') {
                return true;
            }
        }

        // Second check: user is registered as media house staff (media_house_user_id)
        $mediaHouseStaffCount = \App\Models\MediaHouseStaff::where('media_house_user_id', $user->id)
            ->count();

        if ($mediaHouseStaffCount > 0) {
            return true;
        }

        // Third check: has submitted media house (registration) applications
        $registrationCount = \App\Models\Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'registration')
            ->count();

        return $registrationCount > 0;
    }
}
