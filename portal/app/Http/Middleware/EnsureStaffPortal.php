<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * If an applicant is authenticated and tries to access staff routes,
 * force a logout so they must login via /staff.
 */
class EnsureStaffPortal
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'hasAnyRole')) {
            $staffRoles = [
                'super_admin','accreditation_officer','registration_compliance_officer','accounts_payments','registrar',
                'production','it_admin','auditor','director','oversight',
                'pr_officer','complaints_officer','public_info_compliance',
            ];
            $isStaff = $user->hasAnyRole($staffRoles);
            if (!$isStaff) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('staff.entry')
                    ->withErrors(['email' => 'Please login via the staff portal to access staff dashboards.']);
            }
            
            if (!$request->session()->has('active_staff_role')) {
                foreach ($staffRoles as $role) {
                    if ($user->hasRole($role)) {
                        $request->session()->put('active_staff_role', $role);
                        break;
                    }
                }
            }
        }

        return $next($request);
    }
}
