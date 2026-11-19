<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemIncident extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'description', 'severity', 'status', 'module', 'source',
        'assigned_to', 'reported_by', 'root_cause', 'resolution_notes',
        'resolved_at', 'escalated_at', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function helpdeskTicket()
    {
        return $this->hasOne(HelpdeskTicket::class, 'incident_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
