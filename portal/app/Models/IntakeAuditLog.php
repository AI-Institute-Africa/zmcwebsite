<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeAuditLog extends Model
{
    protected $table = 'intake_audit_logs';

    protected $fillable = [
        'application_id',
        'action',
        'old_status',
        'new_status',
        'rules_applied',
        'confidence_scores',
        'document_ids_checked',
        'reason',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'rules_applied' => 'array',
        'confidence_scores' => 'array',
        'document_ids_checked' => 'array',
        'metadata' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
