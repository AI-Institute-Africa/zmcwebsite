<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsitePagePerson extends Model
{
    protected $table = 'website_page_people';

    protected $fillable = [
        'page_key', 'section_id', 'title_id', 'tier', 'name', 'position', 'department',
        'biography', 'image_path', 'sort_order', 'is_published', 'created_by',
    ];

    protected $casts = [
        'tier' => 'integer',
        'section_id' => 'integer',
        'title_id' => 'integer',
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(WebsitePageSection::class, 'section_id');
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(WebsitePageTitle::class, 'title_id');
    }

    /**
     * Prefer the reusable title record, then the legacy free-text position.
     */
    public function displayTitle(): ?string
    {
        return $this->title?->name ?: $this->position;
    }
}
