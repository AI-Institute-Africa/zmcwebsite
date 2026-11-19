<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentClassification extends Model
{
    protected $table = 'document_classifications';

    protected $fillable = [
        'application_id',
        'document_id',
        'expected_document_type',
        'detected_document_type',
        'confidence_score',
        'extracted_text_excerpt',
        'matched_keywords',
        'missing_keywords',
        'warnings',
        'blocking_errors',
        'recommendation',
        'details',
    ];

    protected $casts = [
        'matched_keywords' => 'array',
        'missing_keywords' => 'array',
        'warnings' => 'array',
        'blocking_errors' => 'array',
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
