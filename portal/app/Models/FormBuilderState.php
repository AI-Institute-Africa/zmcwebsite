<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormBuilderState extends Model
{
    protected $fillable = [
        'form_type',
        'draft_schema',
        'published_schema',
        'version',
        'draft_updated_by',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'draft_schema' => 'array',
        'published_schema' => 'array',
        'version' => 'integer',
        'published_at' => 'datetime',
    ];

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
