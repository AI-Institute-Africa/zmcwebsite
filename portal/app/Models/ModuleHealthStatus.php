<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleHealthStatus extends Model
{
    protected $fillable = [
        'module_name', 'display_name', 'category', 'status',
        'response_state', 'affected_users', 'probable_cause',
        'last_checked_at', 'status_changed_at', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'last_checked_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
