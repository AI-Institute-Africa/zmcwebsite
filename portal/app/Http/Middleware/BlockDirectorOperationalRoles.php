<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * The Director has access to the Super Admin console for oversight,
 * but must NOT be able to operate workflows belonging to:
 * - Accreditation Officer
 * - Accounts/Payments
 * - Registrar
 * - Production
 *
 * This middleware blocks access if the authenticated user has the Director role.
 */
class BlockDirectorOperationalRoles
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('director')) {
            abort(403, 'Directors have oversight access only and cannot perform operational workflow actions.');
        }

        return $next($request);
    }
}
