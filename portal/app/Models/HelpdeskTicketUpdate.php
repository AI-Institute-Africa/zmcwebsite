<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpdeskTicketUpdate extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'event_type', 'body', 'changes'];

    protected $casts = ['changes' => 'array'];

    public function ticket()
    {
        return $this->belongsTo(HelpdeskTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
