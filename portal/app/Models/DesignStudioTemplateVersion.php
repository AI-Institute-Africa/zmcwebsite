<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignStudioTemplateVersion extends Model
{
    protected $table = 'design_studio_template_versions';

    protected $fillable = [
        'template_id', 'version_number', 'snapshot_json', 'changed_by', 'change_reason',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DesignStudioTemplate::class, 'template_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
