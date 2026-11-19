<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteMediaItem extends Model
{
    protected $fillable = [
        'type', 'title', 'description', 'category', 'file_path', 'thumbnail_path', 'external_url',
        'is_published', 'published_at', 'display_order', 'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'display_order' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
