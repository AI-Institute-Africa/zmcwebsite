<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemporaryRoleAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'base_role', 'temporary_role', 'region_id',
        'starts_at', 'ends_at', 'reason', 'assigned_by',
        'status', 'auto_expiry',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'auto_expiry' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeExpiringSoon($query, $hours = 24)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '<=', now()->addHours($hours))
            ->where('ends_at', '>=', now());
    }

    public function isExpired(): bool
    {
        return $this->ends_at->isPast();
    }
}
