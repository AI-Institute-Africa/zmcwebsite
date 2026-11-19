<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCheck extends Model
{
    protected $table = 'document_checks';

    protected $fillable = [
        'application_id',
        'document_id',
        'check_type',
        'status',
        'details',
        'error_message',
        'confidence_score',
    ];

    protected $casts = [
        'details' => 'array',
        'confidence_score' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'document_id');
    }
}
