<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = ['name', 'code', 'is_active', 'expires_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function officers()
    {
        return $this->belongsToMany(User::class, 'officer_regions', 'region_id', 'user_id');
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }
}
