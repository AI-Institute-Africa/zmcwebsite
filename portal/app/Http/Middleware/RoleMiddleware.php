<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                abort(401, 'Unauthenticated.');
            }
            return redirect()->guest(route('staff.login'));
        }

        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles = array_merge($allowedRoles, explode('|', $role));
        }

        $userRole = $user->role;
        $sessionRole = session('active_staff_role');

        // Check direct role field
        if (in_array($userRole, $allowedRoles, true) || in_array($sessionRole, $allowedRoles, true)) {
            return $next($request);
        }

        // Check Spatie roles if HasRoles trait is used
        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($allowedRoles)) {
                return $next($request);
            }
        }

        abort(403, 'USER DOES NOT HAVE THE RIGHT ROLES.');
    }
}
