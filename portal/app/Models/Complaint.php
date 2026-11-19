<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'reference_number',
        'category_id',
        'idempotency_key',
        'type',
        'full_name',
        'email',
        'phone',
        'subject',
        'message',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'status',
        'internal_notes',
        'handled_by',
        'handled_at',
        // Appeal payment tracking
        'payment_ref',
        'payment_proof_path',
        'payment_proof_original_name',
        'payment_proof_mime',
        'payment_status',
        'payment_approved_by',
        'payment_approved_at',
        'paynow_poll_url',
        'payment_currency',
        'payment_amount',
        'priority',
        'response_due_at',
        'first_responded_at',
    ];

    protected $casts = [
        'handled_at'          => 'datetime',
        'payment_approved_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'response_due_at' => 'datetime',
        'first_responded_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(AppealCategory::class, 'category_id');
    }

    public function communications()
    {
        return $this->hasMany(AppealCommunication::class, 'complaint_id');
    }

    public function appealPayments()
    {
        return $this->hasMany(AppealPayment::class, 'complaint_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
