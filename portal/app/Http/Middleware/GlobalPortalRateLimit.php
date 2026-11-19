<?php

namespace App\Http\Middleware;

use App\Models\RateLimitEvent;
use App\Models\SystemConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class GlobalPortalRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        if (! $this->isEnabled()) {
            return $next($request);
        }

        $ipAddress = $request->ip();
        if (! $ipAddress) {
            return $next($request);
        }

        $key = 'portal-global-rate-limit:' . hash('sha256', $ipAddress);
        $limit = (int) config('security.global_rate_limit.requests_per_minute', 300);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            RateLimitEvent::record($request);

            $retryAfter = RateLimiter::availableIn($key);
            $response = $request->expectsJson()
                ? response()->json(['message' => 'Too many requests. Please try again shortly.'], 429)
                : response()->view('errors.too-many-requests', ['retryAfter' => $retryAfter], 429);

            $response->headers->set('Retry-After', (string) $retryAfter);
            $response->headers->set('X-RateLimit-Limit', (string) $limit);
            $response->headers->set('X-RateLimit-Remaining', '0');

            return $response;
        }

        RateLimiter::hit($key, 60);
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) RateLimiter::remaining($key, $limit));

        return $response;
    }

    private function isEnabled(): bool
    {
        $default = (bool) config('security.global_rate_limit.enabled', true);

        try {
            return (bool) Cache::store('file')->remember(
                'security.global_rate_limit.enabled',
                now()->addSeconds(15),
                function () use ($default): bool {
                    $setting = SystemConfig::getValue('security.global_rate_limit', ['enabled' => $default]);

                    return is_array($setting) && array_key_exists('enabled', $setting)
                        ? (bool) $setting['enabled']
                        : $default;
                }
            );
        } catch (Throwable) {
            return $default;
        }
    }
}
