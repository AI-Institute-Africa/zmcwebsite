<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'location_id', 'status', 'last_heartbeat',
        'queue_size', 'failed_jobs', 'last_successful_job',
        'connection_status', 'api_token_hash', 'diagnostics_notes', 'meta',
    ];

    protected $hidden = ['api_token_hash'];

    protected $casts = [
        'meta' => 'array',
        'last_heartbeat' => 'datetime',
        'last_successful_job' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }
}
