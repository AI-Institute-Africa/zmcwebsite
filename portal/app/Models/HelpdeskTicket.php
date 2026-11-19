<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpdeskTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject', 'description', 'priority', 'status', 'category',
        'submitted_by', 'assigned_to', 'incident_id', 'attachment_path', 'attachment_name', 'resolution_summary',
        'resolved_at', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function incident()
    {
        return $this->belongsTo(SystemIncident::class, 'incident_id');
    }

    public function updates()
    {
        return $this->hasMany(HelpdeskTicketUpdate::class, 'ticket_id')->orderBy('created_at');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }
}
