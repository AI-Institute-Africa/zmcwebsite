<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppealCommunication extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'sender_id',
        'subject',
        'body',
        'delivery_status',
        'sent_at',
        'attachments',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
