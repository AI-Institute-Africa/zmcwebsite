<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RateLimitEvent extends Model
{
    protected $fillable = [
        'event_key',
        'ip_address',
        'route_name',
        'path',
        'user_agent',
        'throttled_at',
    ];

    protected $casts = [
        'throttled_at' => 'datetime',
    ];

    public static function record(Request $request): void
    {
        $ipAddress = $request->ip();
        if (! $ipAddress) {
            return;
        }

        $minute = now()->format('Y-m-d H:i');
        $eventKey = hash('sha256', $ipAddress . '|' . $minute);

        try {
            DB::table('rate_limit_events')->insertOrIgnore([
                'event_key' => $eventKey,
                'ip_address' => $ipAddress,
                'route_name' => $request->route()?->getName(),
                'path' => substr('/' . ltrim($request->path(), '/'), 0, 255),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'throttled_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (Cache::store('file')->add('security.rate_limit_events.prune', true, now()->addDay())) {
                DB::table('rate_limit_events')
                    ->where('throttled_at', '<', now()->subDays(30))
                    ->delete();
            }
        } catch (\Throwable) {
            // A logging failure must never bypass or break the active rate limit.
        }
    }
}
