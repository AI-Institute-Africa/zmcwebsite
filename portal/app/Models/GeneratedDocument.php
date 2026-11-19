<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class GeneratedDocument extends Model
{
    protected $table = 'generated_documents';

    protected $fillable = [
        'application_id', 'applicant_id', 'media_house_id',
        'template_id', 'template_version_id',
        'document_type', 'document_number', 'qr_code_value',
        'generated_file', 'generated_preview',
        'generated_by', 'generated_at',
        'status', 'cancellation_reason',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DesignStudioTemplate::class, 'template_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(PrintLog::class, 'generated_document_id');
    }

    public function fileUrl(): ?string
    {
        if (!$this->generated_file) return null;
        return Storage::disk('public')->url($this->generated_file);
    }

    public function previewUrl(): ?string
    {
        if (!$this->generated_preview) return null;
        return Storage::disk('public')->url($this->generated_preview);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'generated' => 'Generated',
            'printed'   => 'Printed',
            'reprinted' => 'Reprinted',
            'cancelled' => 'Cancelled',
            'revoked'   => 'Revoked',
            default     => ucfirst($this->status),
        };
    }
}
