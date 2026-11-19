<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsitePageSection extends Model
{
    protected $table = 'website_page_sections';

    protected $fillable = [
        'page_key', 'name', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function people(): HasMany
    {
        return $this->hasMany(WebsitePagePerson::class, 'section_id')->orderBy('sort_order')->orderBy('id');
    }
}
