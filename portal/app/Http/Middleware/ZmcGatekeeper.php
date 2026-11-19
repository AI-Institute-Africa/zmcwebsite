<?php

namespace App\Http\Middleware;

use App\Support\MasterSettings;
use Closure;
use Illuminate\Http\Request;

/**
 * Global guard for maintenance mode and portal availability.
 */
class ZmcGatekeeper
{
    public function handle(Request $request, Closure $next)
    {
        // Fast-path: skip on health checks and static asset requests so we
        // don't pay for any settings/DB lookups for infrastructure pings.
        $path = $request->path();
        if (
            $path === '__repl_health' || $path === 'healthz' || $path === 'up'
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'storage/')
        ) {
            return $next($request);
        }

        $general = (array) MasterSettings::get('general', []);

        $isStaff = $request->is('staff/*') || $request->is('admin/*');

        // Maintenance mode: allow super_admin to pass through.
        if (!empty($general['maintenance_mode'])) {
            $u = $request->user();
            if (!$u || !$u->hasAnyRole(['super_admin', 'it_admin'])) {
                return response()->view('shared.maintenance', [], 503);
            }
        }

        // Portal availability toggles
        if (!$isStaff && empty($general['public_portal_enabled'])) {
            return response()->view('shared.portal_disabled', ['portal' => 'Public portal'], 503);
        }
        if ($isStaff && empty($general['staff_portals_enabled'])) {
            $u = $request->user();
            if (!$u || !$u->hasAnyRole(['super_admin', 'it_admin'])) {
                return response()->view('shared.portal_disabled', ['portal' => 'Staff portals'], 503);
            }
        }

        return $next($request);
    }
}
