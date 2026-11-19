<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePageTitle extends Model
{
    protected $table = 'website_page_titles';

    protected $fillable = [
        'page_key', 'name', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
