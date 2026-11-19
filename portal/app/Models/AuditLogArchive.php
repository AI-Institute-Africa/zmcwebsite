<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogArchive extends Model
{
    protected $table = 'audit_log_archives';
    public $timestamps = false;

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
        'application_id',
        'user_id',
        'user_role',
        'meta',
        'ip',
        'user_agent',
        'created_at',
        'archived_at',
        'archive_year',
    ];

    protected $casts = [
        'meta' => 'array',
        'old_value' => 'array',
        'new_value' => 'array',
        'is_system_generated' => 'boolean',
        'created_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
