<?php

namespace App\Services;

use App\Models\DesignStudioTemplate;
use App\Models\DesignStudioTemplateField;
use App\Models\DesignStudioTemplateVersion;
use App\Models\DesignStudioAuditLog;
use App\Support\FileType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing design studio templates and canvas operations.
 * Handles template creation, editing, versioning, and auditing.
 */
class DesignStudioService
{
    /**
     * Create a new template.
     */
    public function createTemplate(array $data): DesignStudioTemplate
    {
        return DB::transaction(function () use ($data) {
            $template = DesignStudioTemplate::create(array_merge($data, [
                'created_by' => Auth::id(),
                'status' => DesignStudioTemplate::STATUS_DRAFT,
            ]));

            $this->auditAction($template, 'template_created', [], $data);

            return $template;
        });
    }

    /**
     * Update template layout/canvas.
     */
    public function updateCanvasLayout(DesignStudioTemplate $template, array $layoutConfig): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template, $layoutConfig) {
            // Save version before updating
            $this->createVersion(
                $template,
                'Canvas layout updated',
                $template->layout_config ?? []
            );

            $oldValues = ['layout_config' => $template->layout_config];
            $newValues = ['layout_config' => $layoutConfig];

            $template->update(['layout_config' => $layoutConfig]);

            $this->auditAction($template, 'canvas_updated', $oldValues, $newValues);

            return $template;
        });
    }

    /**
     * Update template background.
     */
    public function updateBackground(DesignStudioTemplate $template, $backgroundFile): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template, $backgroundFile) {
            // Save version
            $this->createVersion($template, 'Background changed');

            $oldPath = $template->background_file;

            // Store file
            if ($backgroundFile) {
                $backgroundFile = $backgroundFile->storeAs('templates/backgrounds', FileType::safeHashName($backgroundFile), 'public');
            }

            // Delete old file if exists
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $template->update(['background_file' => $backgroundFile]);

            $this->auditAction($template, 'background_changed', 
                ['background_file' => $oldPath],
                ['background_file' => $backgroundFile]
            );

            return $template;
        });
    }

    /**
     * Add or update dynamic field in template.
     */
    public function saveField(DesignStudioTemplate $template, array $fieldData): DesignStudioTemplateField
    {
        return DB::transaction(function () use ($template, $fieldData) {
            $field = DesignStudioTemplateField::updateOrCreate(
                ['id' => $fieldData['id'] ?? null, 'template_id' => $template->id],
                array_merge($fieldData, ['template_id' => $template->id])
            );

            $this->createVersion($template, "Field '{$field->field_label}' modified");
            $this->auditAction($template, 'field_updated', [], $fieldData);

            return $field;
        });
    }

    /**
     * Delete a field from template.
     */
    public function deleteField(DesignStudioTemplate $template, DesignStudioTemplateField $field): void
    {
        DB::transaction(function () use ($template, $field) {
            $fieldLabel = $field->field_label;
            $fieldData = $field->toArray();

            $field->delete();

            $this->createVersion($template, "Field '{$fieldLabel}' removed");
            $this->auditAction($template, 'field_deleted', $fieldData, []);
        });
    }

    /**
     * Submit template for Super Admin approval.
     */
    public function submitForApproval(DesignStudioTemplate $template): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template) {
            // Validate required fields
            $requiredFields = $this->getRequiredFieldsForType($template);
            $currentFields = $template->fields()->pluck('field_key')->toArray();

            $missingFields = array_diff($requiredFields, $currentFields);
            if (!empty($missingFields)) {
                throw new \RuntimeException("Missing required fields: " . implode(', ', $missingFields));
            }

            $oldValues = ['status' => $template->status];
            $newValues = ['status' => DesignStudioTemplate::STATUS_PENDING_APPROVAL];

            $template->update([
                'status' => DesignStudioTemplate::STATUS_PENDING_APPROVAL,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            $this->createVersion($template, 'Submitted for Super Admin approval');
            $this->auditAction($template, 'template_submitted', $oldValues, $newValues);

            return $template;
        });
    }

    /**
     * Approve template (Super Admin only).
     */
    public function approveTemplate(DesignStudioTemplate $template): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template) {
            $oldValues = ['status' => $template->status];
            $newValues = ['status' => DesignStudioTemplate::STATUS_APPROVED];

            $template->update([
                'status' => DesignStudioTemplate::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->createVersion($template, 'Approved by Super Admin');
            $this->auditAction($template, 'template_approved', $oldValues, $newValues);

            return $template;
        });
    }

    /**
     * Reject template with reason (Super Admin only).
     */
    public function rejectTemplate(DesignStudioTemplate $template, string $reason): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template, $reason) {
            $oldValues = ['status' => $template->status];
            $newValues = ['status' => DesignStudioTemplate::STATUS_REJECTED, 'rejection_reason' => $reason];

            $template->update([
                'status' => DesignStudioTemplate::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
            ]);

            $this->auditAction($template, 'template_rejected', $oldValues, $newValues);

            return $template;
        });
    }

    /**
     * Archive template.
     */
    public function archiveTemplate(DesignStudioTemplate $template): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template) {
            $oldValues = ['status' => $template->status];
            $newValues = ['status' => DesignStudioTemplate::STATUS_ARCHIVED];

            $template->update([
                'status' => DesignStudioTemplate::STATUS_ARCHIVED,
                'archived_by' => Auth::id(),
                'archived_at' => now(),
            ]);

            $this->auditAction($template, 'template_archived', $oldValues, $newValues);

            return $template;
        });
    }

    /**
     * Restore archived template.
     */
    public function restoreTemplate(DesignStudioTemplate $template): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template) {
            $oldStatus = $template->status;
            $template->update([
                'status' => DesignStudioTemplate::STATUS_APPROVED,
                'archived_at' => null,
                'archived_by' => null,
            ]);

            $this->auditAction($template, 'template_restored', 
                ['status' => $oldStatus],
                ['status' => DesignStudioTemplate::STATUS_APPROVED]
            );

            return $template;
        });
    }

    /**
     * Duplicate template (creates a new draft copy).
     */
    public function duplicateTemplate(DesignStudioTemplate $template): DesignStudioTemplate
    {
        return DB::transaction(function () use ($template) {
            $newTemplate = $template->replicate();
            $newTemplate->name = $template->name . ' (Copy)';
            $newTemplate->status = DesignStudioTemplate::STATUS_DRAFT;
            $newTemplate->created_by = Auth::id();
            $newTemplate->submitted_by = null;
            $newTemplate->approved_by = null;
            $newTemplate->rejected_by = null;
            $newTemplate->archived_by = null;
            $newTemplate->save();

            // Copy fields
            foreach ($template->fields as $field) {
                $newField = $field->replicate();
                $newField->template_id = $newTemplate->id;
                $newField->save();
            }

            $this->auditAction($newTemplate, 'template_duplicated_from', 
                ['source_template_id' => $template->id],
                ['target_template_id' => $newTemplate->id]
            );

            return $newTemplate;
        });
    }

    /**
     * Get template statistics for dashboard.
     */
    public function getTemplateStats(): array
    {
        return [
            'card_templates' => DesignStudioTemplate::cards()->count(),
            'certificate_templates' => DesignStudioTemplate::certificates()->count(),
            'draft_templates' => DesignStudioTemplate::draft()->count(),
            'pending_approval' => DesignStudioTemplate::pendingApproval()->count(),
            'approved_templates' => DesignStudioTemplate::approved()->count(),
            'rejected_templates' => DesignStudioTemplate::where('status', DesignStudioTemplate::STATUS_REJECTED)->count(),
            'archived_templates' => DesignStudioTemplate::where('status', DesignStudioTemplate::STATUS_ARCHIVED)->count(),
        ];
    }

    /**
     * Get required fields based on template type.
     */
    private function getRequiredFieldsForType(DesignStudioTemplate $template): array
    {
        if ($template->type === DesignStudioTemplate::TYPE_CARD) {
            return [
                'applicant_photo',
                'full_name',
                'accreditation_number',
                'category',
                'expiry_date',
                'verification_qr',
            ];
        }

        if ($template->type === DesignStudioTemplate::TYPE_CERTIFICATE) {
            return [
                'media_house_name',
                'registration_number',
                'registration_type',
                'issue_date',
                'expiry_date',
                'verification_qr',
            ];
        }

        return [];
    }

    /**
     * Create a version snapshot.
     */
    private function createVersion(DesignStudioTemplate $template, ?string $reason = null, ?array $previousConfig = null): DesignStudioTemplateVersion
    {
        $lastVersion = $template->versions()->max('version_number') ?? 0;

        return DesignStudioTemplateVersion::create([
            'template_id' => $template->id,
            'version_number' => $lastVersion + 1,
            'snapshot_json' => $previousConfig ?? ($template->layout_config ?? []),
            'changed_by' => Auth::id(),
            'change_reason' => $reason,
        ]);
    }

    /**
     * Log an audit action.
     */
    private function auditAction(DesignStudioTemplate $template, string $action, array $oldValues = [], array $newValues = []): void
    {
        DesignStudioAuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => DesignStudioTemplate::class,
            'entity_id' => $template->id,
            'old_values' => !empty($oldValues) ? $oldValues : null,
            'new_values' => !empty($newValues) ? $newValues : null,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);
    }
}
