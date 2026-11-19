<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false; // immutable

    protected $fillable = [
        'actor_user_id',
        'action',
        'action_type',
        'module',
        'model_type',
        'model_id',
        'record_number',
        'office_branch',
        'old_value',
        'new_value',
        'rejection_reason',
        'is_system_generated',
        'meta',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'old_value' => 'array',
        'new_value' => 'array',
        'is_system_generated' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
