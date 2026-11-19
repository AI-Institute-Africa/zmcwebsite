<?php

namespace App\Http\Middleware;

use App\Support\MasterSettings;
use Closure;
use Illuminate\Http\Request;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module)
    {
        $modules = (array) MasterSettings::get('monitoring_maintenance.modules', []);
        if (array_key_exists($module, $modules) && !$modules[$module]) {
            abort(403, strtoupper($module) . ' module is disabled.');
        }
        return $next($request);
    }
}
