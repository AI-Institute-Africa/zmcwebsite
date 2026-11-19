<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class BlockConfiguredIps
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('security.ip_blocking.enabled', false)) {
            return $next($request);
        }

        $ipAddress = $request->ip();
        if (! $ipAddress || in_array($ipAddress, config('security.ip_blocking.excluded_ips', []), true)) {
            return $next($request);
        }

        try {
            $blockedIps = Cache::store('file')->remember(
                'security.ip_blocks.active',
                now()->addSeconds(config('security.ip_blocking.cache_seconds', 30)),
                fn () => DB::table('security_ip_blocks')->pluck('ip_address')->all()
            );
        } catch (Throwable $exception) {
            report($exception);

            try {
                Cache::store('file')->put(
                    'security.ip_blocks.active',
                    [],
                    now()->addSeconds(10)
                );
            } catch (Throwable) {
                // Cache failures must not take down portal requests.
            }

            // Fail open if the security table or database is temporarily unavailable.
            return $next($request);
        }

        if (in_array($ipAddress, $blockedIps, true)) {
            return response()->view('errors.ip-blocked', [], 403);
        }

        return $next($request);
    }
}
