<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'body',
        'image_path',
        'image_paths',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'is_published',
        'published_at',
        'created_by',
        'category',
        'tags',
        'excerpt',
        'author_name',
        'status',
        'featured',
        'language',
        'source_reference',
        'image_alt_text',
        'allow_comments',
        'notify_subscribers',
        'meta_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'featured' => 'boolean',
        'allow_comments' => 'boolean',
        'notify_subscribers' => 'boolean',
        'image_paths' => 'array',
    ];

    public static function makeSlug(string $title): string
    {
        $slug = Str::slug($title);
        if ($slug === '') $slug = 'news';
        $base = $slug;
        $i = 2;
        while (self::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
