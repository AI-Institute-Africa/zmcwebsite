<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DesignStudioTemplate extends Model
{
    protected $table = 'design_studio_templates';

    // ── Status constants ─────────────────────────────────────────────────────
    public const STATUS_DRAFT            = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED         = 'approved';
    public const STATUS_REJECTED         = 'rejected';
    public const STATUS_ARCHIVED         = 'archived';

    // ── Type constants ───────────────────────────────────────────────────────
    public const TYPE_CARD        = 'card';
    public const TYPE_CERTIFICATE = 'certificate';

    protected $fillable = [
        'name', 'type', 'template_for', 'year', 'category_code',
        'application_type', 'background_file', 'width', 'height',
        'orientation', 'layout_config', 'status', 'rejection_reason',
        'created_by', 'submitted_by', 'submitted_at',
        'approved_by', 'approved_at',
        'rejected_by', 'rejected_at',
        'archived_by', 'archived_at',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'archived_at'   => 'datetime',
        'width'         => 'float',
        'height'        => 'float',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DesignStudioTemplateField::class, 'template_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DesignStudioTemplateVersion::class, 'template_id')
                    ->orderByDesc('version_number');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeForYear($query, string $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCards($query)
    {
        return $query->where('type', self::TYPE_CARD);
    }

    public function scopeCertificates($query)
    {
        return $query->where('type', self::TYPE_CERTIFICATE);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        // Saving in Design Studio publishes the current design immediately.
        // Keep active templates editable so a designer can refine the live
        // production design without a separate approval cycle.
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_REJECTED,
            self::STATUS_APPROVED,
        ], true);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function templateForLabel(): string
    {
        return match ($this->template_for) {
            'accreditation_local' => 'Accreditation (Local)',
            'accreditation_foreign' => 'Accreditation (Foreign)',
            'registration' => 'Registration',
            'accreditation' => 'Accreditation', // fallback
            default => ucfirst(str_replace('_', ' ', $this->template_for)),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT            => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED         => 'Approved / Active',
            self::STATUS_REJECTED         => 'Rejected',
            self::STATUS_ARCHIVED         => 'Archived',
            default                       => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT            => 'bg-secondary',
            self::STATUS_PENDING_APPROVAL => 'bg-warning text-dark',
            self::STATUS_APPROVED         => 'bg-success',
            self::STATUS_REJECTED         => 'bg-danger',
            self::STATUS_ARCHIVED         => 'bg-dark',
            default                       => 'bg-secondary',
        };
    }

    public function backgroundUrl(): ?string
    {
        if (!$this->background_file) return null;
        return Storage::disk('public')->url($this->background_file);
    }

    /**
     * Save a version snapshot before any layout change.
     */
    public function saveVersion(?string $reason = null): void
    {
        $lastVersion = $this->versions()->max('version_number') ?? 0;

        DesignStudioTemplateVersion::create([
            'template_id'    => $this->id,
            'version_number' => $lastVersion + 1,
            'snapshot_json'  => $this->layout_config ?? [],
            'changed_by'     => Auth::id(),
            'change_reason'  => $reason,
        ]);
    }

    /**
     * Log an action to the design studio audit log.
     */
    public function auditLog(string $action, array $oldValues = [], array $newValues = []): void
    {
        DesignStudioAuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_type' => self::class,
            'entity_id'   => $this->id,
            'old_values'  => $oldValues ?: null,
            'new_values'  => $newValues ?: null,
            'ip_address'  => request()->ip(),
            'user_agent'  => substr((string) request()->userAgent(), 0, 500),
        ]);
    }
}
