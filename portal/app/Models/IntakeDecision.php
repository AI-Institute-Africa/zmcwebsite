<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeDecision extends Model
{
    protected $table = 'intake_decisions';

    protected $fillable = [
        'application_id',
        'overall_score',
        'decision',
        'matched_rules',
        'check_results',
        'category_recommendation',
        'category_confidence_score',
        'reason',
        'created_by',
        'document_ids_checked',
        'documents_checked_count',
    ];

    protected $casts = [
        'overall_score' => 'integer',
        'category_confidence_score' => 'integer',
        'documents_checked_count' => 'integer',
        'matched_rules' => 'array',
        'check_results' => 'array',
        'reason' => 'array',
        'document_ids_checked' => 'array',
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
