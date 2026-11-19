<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerFollowUp extends Model
{
    use HasFactory;

    protected $table = 'officer_followups';

    protected $fillable = [
        'type',
        'status',
        'title',
        'notes',
        'entity_type',
        'entity_id',
        'assigned_to',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
