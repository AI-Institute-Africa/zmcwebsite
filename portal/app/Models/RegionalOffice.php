<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RegionalOffice extends Model
{
    protected $fillable = [
        'code', 'name', 'region', 'address', 'phone', 'email', 'schedule', 'is_published', 'display_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'display_order' => 'integer',
    ];

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'regional_office_staff');
    }
}
