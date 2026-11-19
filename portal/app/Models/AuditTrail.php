<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use RuntimeException;

class AuditTrail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'entity_type',
        'entity_id',
        'entity_reference',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function ($model) {
            throw new RuntimeException('Audit trail records cannot be modified.');
        });

        static::deleting(function ($model) {
            throw new RuntimeException('Audit trail records cannot be deleted.');
        });
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new RuntimeException('Audit trail records cannot be modified.');
    }

    public function delete()
    {
        throw new RuntimeException('Audit trail records cannot be deleted.');
    }

    public static function destroy($ids)
    {
        throw new RuntimeException('Audit trail records cannot be deleted.');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $action,
        string $entityType,
        ?int $entityId,
        string $description,
        ?string $entityReference = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        $user = Auth::user();

        return self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_role' => $user ? implode(', ', $user->getRoleNames()->toArray()) : 'System',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_reference' => $entityReference,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public static function logApplicationAction(Application $application, string $action, string $description, ?array $oldValues = null): self
    {
        return self::log(
            $action,
            'Application',
            $application->id,
            $description,
            $application->reference,
            $oldValues,
            ['status' => $application->status]
        );
    }
}
