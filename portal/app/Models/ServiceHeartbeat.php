<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceHeartbeat extends Model
{
    protected $fillable = [
        'service_name', 'status', 'response_time_ms',
        'error_message', 'meta', 'checked_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'checked_at' => 'datetime',
    ];
}
