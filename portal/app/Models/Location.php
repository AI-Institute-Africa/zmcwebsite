<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'region_id', 'location_type', 'address', 'status',
        'start_date', 'end_date', 'notes', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTemporary($query)
    {
        return $query->whereIn('location_type', ['temporary_outreach', 'mobile_service']);
    }
}
