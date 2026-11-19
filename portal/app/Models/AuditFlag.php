<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditFlag extends Model
{
    protected $fillable = [
        'auditor_user_id',
        'entity_type',
        'entity_id',
        'severity',
        'reason',
        'assigned_unit',
        'status',
        'response_notes',
        'corrective_action',
        'due_at',
        'attachment_path',
        'attachment_original_name',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_user_id');
    }
}
