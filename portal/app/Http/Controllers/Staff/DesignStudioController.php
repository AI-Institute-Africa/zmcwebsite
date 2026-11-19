<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DesignStudioAuditLog;
use App\Models\DesignStudioTemplate;
use App\Models\GeneratedDocument;
use App\Models\User;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DesignStudioController extends Controller
{
    // ── Roles allowed to design ──────────────────────────────────────────────
    private const DESIGNER_ROLES = ['registrar', 'accreditation_officer', 'production', 'it_admin', 'super_admin'];

    // ── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $user = Auth::user();

        $isDesigner   = $user->hasAnyRole(self::DESIGNER_ROLES);
        $isSuperAdmin = $user->hasRole('super_admin');

        // Counts for KPI tiles
        $myDrafts     = DesignStudioTemplate::where('created_by', $user->id)
                            ->where('status', DesignStudioTemplate::STATUS_DRAFT)->count();
        $myPending    = DesignStudioTemplate::where('created_by', $user->id)
                            ->where('status', DesignStudioTemplate::STATUS_PENDING_APPROVAL)->count();
        $myRejected   = DesignStudioTemplate::where('created_by', $user->id)
                            ->where('status', DesignStudioTemplate::STATUS_REJECTED)->count();
        $myActive     = DesignStudioTemplate::where('created_by', $user->id)
                            ->where('status', DesignStudioTemplate::STATUS_APPROVED)->count();
        $totalApproved = DesignStudioTemplate::where('status', DesignStudioTemplate::STATUS_APPROVED)->count();
        $totalPending  = DesignStudioTemplate::where('status', DesignStudioTemplate::STATUS_PENDING_APPROVAL)->count();
        $totalArchived = DesignStudioTemplate::where('status', DesignStudioTemplate::STATUS_ARCHIVED)->count();

        $cardApproved  = DesignStudioTemplate::approved()->cards()->count();
        $certApproved  = DesignStudioTemplate::approved()->certificates()->count();

        return view('staff.design-studio.dashboard', compact(
            'isDesigner', 'isSuperAdmin',
            'myDrafts', 'myPending', 'myRejected', 'myActive',
            'totalApproved', 'totalPending', 'totalArchived',
            'cardApproved', 'certApproved'
        ));
    }

    // ── Template List ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');

        $query = DesignStudioTemplate::with('creator')
            ->orderByDesc('updated_at');

        // Non-super-admins only see their own templates
        if (!$isSuperAdmin) {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $templates = $query->paginate(20)->withQueryString();

        return view('staff.design-studio.index', compact('templates', 'isSuperAdmin'));
    }

    // ── Create / Edit ────────────────────────────────────────────────────────

    public function create()
    {
        $this->authorizeDesigner();
        return view('staff.design-studio.create');
    }

    public function store(Request $request)
    {
        $this->authorizeDesigner();

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:card,certificate'],
            'template_for'     => ['required', 'in:accreditation_local,accreditation_foreign,registration'],
            'year'             => ['required', 'digits:4'],
            'application_type' => ['nullable', 'string', 'max:50'],
            'orientation'      => ['nullable', 'in:portrait,landscape'],
            'width'            => ['nullable', 'numeric'],
            'height'           => ['nullable', 'numeric'],
            'background'       => $this->imageUploadRules(8192, ['jpg', 'jpeg', 'png']),
            'layout_config'    => ['nullable', 'string'],
        ]);

        $bgPath = null;
        if ($request->hasFile('background')) {
            $bgPath = $this->storePublicFile($request->file('background'), 'design-studio/backgrounds', FileType::safeHashName($request->file('background')));
        }

        $template = DesignStudioTemplate::create([
            'name'             => $data['name'],
            'type'             => $data['type'],
            'template_for'     => $data['template_for'],
            'year'             => $data['year'],
            'application_type' => $data['application_type'] ?? null,
            'orientation'      => $data['orientation'] ?? 'portrait',
            'width'            => $data['width'] ?? null,
            'height'           => $data['height'] ?? null,
            'background_file'  => $bgPath,
            'layout_config'    => !empty($data['layout_config']) ? json_decode($data['layout_config'], true) : null,
            // Keep the empty shell as a draft. The first Save in the designer
            // activates the completed design for production.
            'status'           => DesignStudioTemplate::STATUS_DRAFT,
            'created_by'       => Auth::id(),
        ]);

        $template->auditLog('template_created', [], $template->toArray());

        return redirect()->route('staff.design-studio.edit', $template->id)
            ->with('success', 'Template created and activated for production.');
    }

    public function show(DesignStudioTemplate $designStudioTemplate)
    {
        $this->authorizeViewAccess($designStudioTemplate);
        $designStudioTemplate->load(['creator', 'approver', 'rejecter', 'versions.changedBy', 'fields']);
        return view('staff.design-studio.show', ['template' => $designStudioTemplate]);
    }

    public function edit(DesignStudioTemplate $designStudioTemplate)
    {
        $this->authorizeDesigner();
        $this->authorizeOwnerOrAdmin($designStudioTemplate);

        if (!$designStudioTemplate->isEditable()) {
            return redirect()->route('staff.design-studio.show', $designStudioTemplate->id)
                ->with('error', 'This template cannot be edited in its current status ('
                    . $designStudioTemplate->statusLabel() . '). Duplicate it to create a new version.');
        }

        $designStudioTemplate->load(['fields', 'versions']);

        // Available dynamic fields by type
        $cardFields = $this->cardFieldDefinitions();
        $certFields = $this->certFieldDefinitions();

        return view('staff.design-studio.edit', [
            'template'   => $designStudioTemplate,
            'cardFields' => $cardFields,
            'certFields' => $certFields,
        ]);
    }

    public function update(Request $request, DesignStudioTemplate $designStudioTemplate)
    {
        $this->authorizeDesigner();
        $this->authorizeOwnerOrAdmin($designStudioTemplate);

        if (!$designStudioTemplate->isEditable()) {
            return back()->with('error', 'Template cannot be edited in its current status.');
        }

        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'template_for'     => ['required', 'in:accreditation_local,accreditation_foreign,registration'],
            'year'             => ['required', 'digits:4'],
            'application_type' => ['nullable', 'string', 'max:50'],
            'orientation'      => ['nullable', 'in:portrait,landscape'],
            'width'            => ['nullable', 'numeric'],
            'height'           => ['nullable', 'numeric'],
            'background'       => $this->imageUploadRules(8192, ['jpg', 'jpeg', 'png']),
            'layout_config'    => ['nullable', 'string'],
            'save_version'     => ['nullable', 'boolean'],
        ]);

        $old = $designStudioTemplate->toArray();

        // Save version snapshot before overwriting layout
        if ($request->boolean('save_version') && $designStudioTemplate->layout_config) {
            $designStudioTemplate->saveVersion($request->input('change_reason'));
        }

        if ($request->hasFile('background')) {
            if ($designStudioTemplate->background_file) {
                Storage::disk('public')->delete($designStudioTemplate->background_file);
            }
            $data['background_file'] = $this->storePublicFile($request->file('background'), 'design-studio/backgrounds', FileType::safeHashName($request->file('background')));
        }

        $layoutConfig = $designStudioTemplate->layout_config;
        if (array_key_exists('layout_config', $data) && $data['layout_config'] !== null && $data['layout_config'] !== '') {
            try {
                $layoutConfig = json_decode($data['layout_config'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                return back()->with('error', 'The design could not be saved because the canvas data is invalid. Please refresh and try again.');
            }
            if (!is_array($layoutConfig)) {
                return back()->with('error', 'The design canvas data must be a valid layout. Please refresh and try again.');
            }
        }

        $designStudioTemplate->update([
            'name'             => $data['name'],
            'template_for'     => $data['template_for'],
            'year'             => $data['year'],
            'application_type' => $data['application_type'] ?? null,
            'orientation'      => $data['orientation'] ?? $designStudioTemplate->orientation,
            'width'            => $data['width'] ?? $designStudioTemplate->width,
            'height'           => $data['height'] ?? $designStudioTemplate->height,
            'background_file'  => $data['background_file'] ?? $designStudioTemplate->background_file,
            'layout_config'    => $layoutConfig,
            'status'           => DesignStudioTemplate::STATUS_APPROVED,
            'approved_by'      => Auth::id(),
            'approved_at'      => now(),
            'rejected_by'      => null,
            'rejected_at'      => null,
            'rejection_reason' => null,
        ]);

        $this->makeProductionActive($designStudioTemplate);

        $designStudioTemplate->auditLog('template_edited', $old, $designStudioTemplate->fresh()->toArray());

        return back()->with('success', 'Template saved and activated for production.');
    }

    // ── Legacy submit endpoint ───────────────────────────────────────────────
    // Kept for old links/bookmarks. Saving is now the approval/activation step.

    public function submit(DesignStudioTemplate $designStudioTemplate)
    {
        $this->authorizeDesigner();
        $this->authorizeOwnerOrAdmin($designStudioTemplate);

        $designStudioTemplate->update([
            'status'       => DesignStudioTemplate::STATUS_APPROVED,
            'approved_by'  => Auth::id(),
            'approved_at' => now(),
            'rejected_by'  => null,
            'rejected_at'  => null,
            'rejection_reason' => null,
        ]);
        $this->makeProductionActive($designStudioTemplate);
        $designStudioTemplate->auditLog('template_activated', [], ['status' => DesignStudioTemplate::STATUS_APPROVED]);

        return back()->with('success', 'Template activated for production.');
    }

    // ── Duplicate ────────────────────────────────────────────────────────────

    public function duplicate(DesignStudioTemplate $designStudioTemplate)
    {
        $this->authorizeDesigner();

        $new = $designStudioTemplate->replicate();
        $new->name       = $designStudioTemplate->name . ' (Copy)';
        $new->status     = DesignStudioTemplate::STATUS_DRAFT;
        $new->created_by = Auth::id();
        $new->submitted_by = null;
        $new->submitted_at = null;
        $new->approved_by  = null;
        $new->approved_at  = null;
        $new->rejected_by  = null;
        $new->rejected_at  = null;
        $new->rejection_reason = null;
        $new->archived_by  = null;
        $new->archived_at  = null;
        $new->save();

        $new->auditLog('template_duplicated', ['source_id' => $designStudioTemplate->id], $new->toArray());

        return redirect()->route('staff.design-studio.edit', $new->id)
            ->with('success', 'Template duplicated. You can now edit and submit the new copy.');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function destroy(DesignStudioTemplate $designStudioTemplate)
    {
        $this->authorizeDesigner();
        $this->authorizeOwnerOrAdmin($designStudioTemplate);

        if (!$designStudioTemplate->isEditable()) {
            return back()->with('error', 'Only draft or rejected templates can be deleted.');
        }

        if (GeneratedDocument::where('template_id', $designStudioTemplate->id)->exists()) {
            return back()->with('error', 'This template cannot be deleted because it has already been used in production.');
        }

        DB::transaction(function () use ($designStudioTemplate) {
            $old = $designStudioTemplate->toArray();

            $designStudioTemplate->fields()->delete();
            $designStudioTemplate->versions()->delete();

            if ($designStudioTemplate->background_file) {
                Storage::disk('public')->delete($designStudioTemplate->background_file);
            }

            $designStudioTemplate->auditLog('template_deleted', $old, ['deleted' => true]);
            $designStudioTemplate->delete();
        });

        return back()->with('success', 'Template deleted successfully.');
    }

    // ── Audit Log View ───────────────────────────────────────────────────────

    public function auditLogs(Request $request)
    {
        $query = DesignStudioAuditLog::with('user')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs  = $query->paginate(30)->withQueryString();
        $users = User::whereIn('id', DesignStudioAuditLog::distinct()->pluck('user_id'))->get();

        return view('staff.design-studio.audit-logs', compact('logs', 'users'));
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function authorizeDesigner(): void
    {
        if (!Auth::user()->hasAnyRole(self::DESIGNER_ROLES)) {
            abort(403, 'You do not have permission to access the Design Studio.');
        }
    }

    private function authorizeViewAccess(DesignStudioTemplate $template): void
    {
        $user = Auth::user();
        if ($user->hasRole('super_admin')) return;
        if ($user->hasAnyRole(self::DESIGNER_ROLES) && $template->created_by === $user->id) return;
        abort(403);
    }

    private function authorizeOwnerOrAdmin(DesignStudioTemplate $template): void
    {
        $user = Auth::user();
        if ($user->hasRole('super_admin')) return;
        if ($template->created_by !== $user->id) {
            abort(403, 'You can only edit your own templates.');
        }
    }

    /**
     * Keep one active design for a type/target/year while retaining older
     * designs in the archive for audit and version history.
     */
    private function makeProductionActive(DesignStudioTemplate $template): void
    {
        DesignStudioTemplate::query()
            ->where('id', '<>', $template->id)
            ->where('type', $template->type)
            ->where('template_for', $template->template_for)
            ->where('year', $template->year)
            ->where('status', DesignStudioTemplate::STATUS_APPROVED)
            ->update([
                'status' => DesignStudioTemplate::STATUS_ARCHIVED,
                'archived_by' => Auth::id(),
                'archived_at' => now(),
            ]);
    }

    private function cardFieldDefinitions(): array
    {
        return [
            // Core (required for submission)
            ['key' => 'applicant_photo',            'label' => 'Applicant Photo',                 'type' => 'photo'],
            ['key' => 'full_name',                  'label' => 'Full Name',                       'type' => 'text'],
            ['key' => 'accreditation_number',       'label' => 'Accreditation Number',            'type' => 'text'],
            ['key' => 'expiry_date',                'label' => 'Expiry Date',                     'type' => 'date'],
            ['key' => 'verification_qr',            'label' => 'Verification QR Code',            'type' => 'qr'],

            // Extended (optional)
            ['key' => 'first_name',                 'label' => 'First Name',                      'type' => 'text'],
            ['key' => 'surname',                    'label' => 'Surname',                         'type' => 'text'],
            ['key' => 'national_id',                'label' => 'National ID',                     'type' => 'text'],
            ['key' => 'passport_number',            'label' => 'Passport Number',                 'type' => 'text'],
            ['key' => 'accreditation_category_name','label' => 'Category Name',                   'type' => 'text'],
            ['key' => 'application_type',           'label' => 'Application Type',                'type' => 'text'],
            ['key' => 'media_organisation',          'label' => 'Media Organisation',              'type' => 'text'],
            ['key' => 'designation',                'label' => 'Designation',                     'type' => 'text'],
            ['key' => 'issue_date',                 'label' => 'Issue Date',                      'type' => 'date'],
            ['key' => 'collection_office',          'label' => 'Collection Office',               'type' => 'text'],
            ['key' => 'issuing_office',             'label' => 'Issuing Office',                  'type' => 'text'],
            ['key' => 'registrar_name',             'label' => 'Registrar Name',                  'type' => 'text'],
            ['key' => 'registrar_signature',        'label' => 'Registrar Signature',             'type' => 'signature'],
            ['key' => 'zmc_logo',                   'label' => 'ZMC Logo',                        'type' => 'image'],
            ['key' => 'stamp_seal',                 'label' => 'Stamp / Seal',                    'type' => 'stamp'],
            ['key' => 'watermark',                  'label' => 'Watermark / Security Text',       'type' => 'text'],
        ];
    }

    private function certFieldDefinitions(): array
    {
        return [
            // Core (required for submission)
            ['key' => 'media_house_name',     'label' => 'Media House / Company Name', 'type' => 'text'],
            ['key' => 'registration_number',  'label' => 'Registration Number',        'type' => 'text'],
            ['key' => 'licence_number',       'label' => 'Licence Number',             'type' => 'text'],
            ['key' => 'registration_type',    'label' => 'Registration Type',          'type' => 'text'],
            ['key' => 'issue_date',           'label' => 'Issue Date',                 'type' => 'date'],
            ['key' => 'expiry_date',          'label' => 'Expiry Date',                'type' => 'date'],
            ['key' => 'verification_qr',      'label' => 'Verification QR Code',       'type' => 'qr'],

            // Extended (optional)
            ['key' => 'mass_media_category_code', 'label' => 'Mass Media Category Code',    'type' => 'text'],
            ['key' => 'mass_media_category_name', 'label' => 'Mass Media Category Name',    'type' => 'text'],
            ['key' => 'physical_address',         'label' => 'Physical Address',           'type' => 'text'],
            ['key' => 'postal_address',           'label' => 'Postal Address',             'type' => 'text'],
            ['key' => 'registration_year',        'label' => 'Registration Year',          'type' => 'text'],
            ['key' => 'registrar_name',           'label' => 'Registrar Name',             'type' => 'text'],
            ['key' => 'registrar_signature',      'label' => 'Registrar Signature',        'type' => 'signature'],
            ['key' => 'commission_stamp',         'label' => 'Commission Stamp / Seal',    'type' => 'stamp'],
            ['key' => 'certificate_title',        'label' => 'Certificate Title',          'type' => 'text'],
            ['key' => 'official_wording',         'label' => 'Official ZMC Wording',       'type' => 'text'],
            ['key' => 'licence_conditions',       'label' => 'Licence Conditions',         'type' => 'text'],
            ['key' => 'zmc_logo',                 'label' => 'ZMC Logo',                   'type' => 'image'],
        ];
    }

    private function requiredFieldKeys(DesignStudioTemplate $template): array
    {
        if ($template->type === DesignStudioTemplate::TYPE_CARD) {
            return [
                'applicant_photo',
                'full_name',
                'accreditation_number',
                'expiry_date',
                'verification_qr',
            ];
        }

        if ($template->type === DesignStudioTemplate::TYPE_CERTIFICATE) {
            return [
                'media_house_name',
                // special: either registration_number or licence_number is acceptable
                'registration_number_or_licence_number',
                'registration_type',
                'issue_date',
                'expiry_date',
                'verification_qr',
            ];
        }

        return [];
    }

    private function extractFieldKeysFromLayout(array $layout): array
    {
        $out = [];

        $walk = function ($node) use (&$walk, &$out) {
            if (!is_array($node)) return;

            if (!empty($node['fieldKey']) && is_string($node['fieldKey'])) {
                $out[] = $node['fieldKey'];
            }

            // Fabric groups: node may have nested objects
            foreach (['objects', 'children'] as $k) {
                if (!empty($node[$k]) && is_array($node[$k])) {
                    foreach ($node[$k] as $child) {
                        $walk($child);
                    }
                }
            }
        };

        foreach (($layout['objects'] ?? []) as $obj) {
            $walk($obj);
        }

        return array_values(array_unique($out));
    }
}
