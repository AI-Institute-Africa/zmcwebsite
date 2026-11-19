<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIpBlock extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
