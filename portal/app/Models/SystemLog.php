<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    protected $fillable = [
        'level',
        'category',
        'message',
        'stack_trace',
        'user_id',
        'ip',
        'request_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public $timestamps = true;
}