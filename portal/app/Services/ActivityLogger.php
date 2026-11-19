<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $action, Model $entity, ?string $fromStatus, ?string $toStatus, array $meta = []): void
    {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'user_role'   => $meta['actor_role'] ?? session('active_staff_role') ?? null,
            'action'      => $action,

            // IMPORTANT: must match morphMany() default = full class name
            'entity_type' => get_class($entity),
            'entity_id'   => $entity->getKey(),

            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'ip'          => request()->ip(),
            'user_agent'  => substr((string) request()->userAgent(), 0, 1000),
            'meta'        => $meta,
        ]);
    }
}
