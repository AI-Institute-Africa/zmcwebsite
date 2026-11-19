<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditTrail
{
    public static function log(string $action, $model = null, array $meta = []): void
    {
        try {
            $actorId = Auth::id();
            $modelType = $model ? get_class($model) : null;
            $modelId = $model?->id ?? null;

            AuditLog::create([
                'actor_user_id' => $actorId,
                'action'        => $action,
                'action_type'   => $meta['action_type'] ?? $action,
                'module'        => $meta['module'] ?? ($modelType ? class_basename($modelType) : 'system'),
                'model_type'    => $modelType,
                'model_id'      => $modelId,
                'record_number' => $meta['record_number'] ?? ($meta['reference'] ?? null),
                'office_branch' => $meta['office_branch'] ?? null,
                'old_value'     => $meta['old_value'] ?? null,
                'new_value'     => $meta['new_value'] ?? null,
                'rejection_reason' => $meta['reason'] ?? null,
                'is_system_generated' => (bool)($meta['is_system_generated'] ?? false),
                'meta'          => $meta,
                'ip'            => request()->ip(),
                'user_agent'    => substr((string)request()->userAgent(), 0, 500),
                'created_at'    => now(),
            ]);

            if ($model instanceof \App\Models\Application) {
                \App\Models\AuditTrail::logApplicationAction(
                    $model,
                    $action,
                    $meta['description'] ?? ($meta['notes'] ?? ($meta['reason'] ?? "Action $action performed")),
                    isset($meta['from_status']) ? ['status' => $meta['from_status']] : null
                );
            } else {
                $entityType = $modelType ? class_basename($modelType) : 'System';
                $entityRef = null;
                if ($model) {
                    foreach (['reference', 'name', 'email', 'title'] as $attr) {
                        if (isset($model->{$attr}) && $model->{$attr} !== null && $model->{$attr} !== '') {
                            $entityRef = (string) $model->{$attr};
                            break;
                        }
                    }
                }
                $description = $meta['description']
                    ?? $meta['notes']
                    ?? $meta['reason']
                    ?? (ucfirst(str_replace('_', ' ', $action))
                        . ($entityRef ? " ({$entityRef})" : ''));

                \App\Models\AuditTrail::log(
                    $action,
                    $entityType,
                    $modelId,
                    $description,
                    $entityRef,
                    null,
                    !empty($meta) ? $meta : null
                );
            }
        } catch (\Throwable $e) {
            // Never break core flows because of audit logging.
        }
    }
}
