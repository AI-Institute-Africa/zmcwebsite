<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppealPayment extends Model
{
    protected $fillable = [
        'complaint_id',
        'reference',
        'provider_reference',
        'amount',
        'currency',
        'status',
        'poll_url',
        'gateway_response',
        'last_checked_at',
        'confirmed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'last_checked_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function appeal(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
}
