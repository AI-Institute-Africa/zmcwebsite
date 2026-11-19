<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Support\MasterSettings;
use App\Support\FileType;
use App\Services\ApplicationWorkflow;
use App\Services\ActivityLogger;

class MediaHousePortalController extends Controller
{
    private function mergeIntoProfile(array $data): void
    {
        $user = Auth::user();
        if (!$user) return;

        $profile = $user->profile_data ?? [];
        foreach ($data as $k => $v) {
            if ($v === null || $v === '') continue;
            $profile[$k] = $v;
        }
        $user->update(['profile_data' => $profile]);
    }

    public function index()
    {
        return redirect()->route('mediahouse.portal');
    }

    public function dashboard()
    {
        $user = Auth::user();

        $baseQuery = Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'registration');

        $stats = [
            'drafts' => (clone $baseQuery)->where('is_draft', true)->count(),
            'active' => (clone $baseQuery)->where('is_draft', false)
                ->whereNotIn('status', [
                    Application::ISSUED,
                    Application::OFFICER_REJECTED,
                    Application::REGISTRAR_REJECTED,
                ])
                ->count(),
            'approved' => (clone $baseQuery)->where('is_draft', false)
                ->whereIn('status', [
                    Application::REGISTRAR_APPROVED,
                    Application::PAID_CONFIRMED,
                    Application::PRODUCTION_QUEUE,
                    Application::CERT_GENERATED,
                    Application::PRINTED,
                    Application::ISSUED,
                ])
                ->count(),
            'pending' => (clone $baseQuery)->where('is_draft', false)
                ->whereIn('status', [
                    Application::SUBMITTED,
                    Application::OFFICER_REVIEW,
                    Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
                    Application::VERIFIED_BY_OFFICER_PENDING_REGISTRAR,
                    Application::REGISTRAR_REVIEW,
                    Application::REGISTRAR_APPROVED_PENDING_REGISTRATION_FEE_PAYMENT,
                    Application::ACCOUNTS_REVIEW,
                    Application::AWAITING_ACCOUNTS_VERIFICATION,
                    Application::REGISTRATION_FEE_AWAITING_VERIFICATION,
                ])
                ->count(),
            'renewals_due' => 0,
        ];

        $recentApplications = (clone $baseQuery)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->with('paymentSubmissions')
            ->get();

        $notices = \App\Models\Notice::where('is_published', true)
             ->whereIn('target_portal', ['mediahouse', 'both'])
             ->orderByDesc('published_at')
             ->limit(5)
             ->get();
 
         $events = \App\Models\Event::where('is_published', true)
             ->whereIn('target_portal', ['mediahouse', 'both'])
             ->orderBy('starts_at')
             ->limit(5)
             ->get();
 
         $registration = \App\Models\RegistrationRecord::where('contact_user_id', $user->id)
              ->where('status', 'active')
              ->orderByDesc('expires_at')
              ->first();
 
          $yearsRemaining = null;
          if ($registration && $registration->expires_at) {
              $now = now();
              if ($registration->expires_at->isPast()) {
                  $yearsRemaining = 0;
              } else {
                  $yearsRemaining = $now->diffInDays($registration->expires_at) / 365.25;
              }
          }
 
         return view('portal.mediahouse.dashboard', compact('stats', 'recentApplications', 'notices', 'events', 'registration', 'yearsRemaining'));
     }

    public function selectRegistrationType()
    {
        return view('portal.mediahouse.select_registration_type');
    }

    public function newRegistration()
    {
        $draft = Application::where('applicant_user_id', Auth::id())
            ->where('application_type', 'registration')
            ->where('request_type', 'new')
            ->where('is_draft', true)
            ->whereIn('form_type', ['AP1', null])
            ->first();

        return view('portal.mediahouse.newregistration', compact('draft'));
    }

    public function ap2Create()
    {
        $draft = Application::where('applicant_user_id', Auth::id())
            ->where('application_type', 'registration')
            ->where('request_type', 'new')
            ->where('form_type', 'AP2')
            ->where('is_draft', true)
            ->first();

        return view('portal.mediahouse.ap2_form', compact('draft'));
    }

    public function ap4Create()
    {
        $draft = Application::where('applicant_user_id', Auth::id())
            ->where('application_type', 'registration')
            ->where('request_type', 'new')
            ->where('form_type', 'AP4')
            ->where('is_draft', true)
            ->first();

        return view('portal.mediahouse.ap4_form', compact('draft'));
    }


    /**
     * If a registration application was returned for correction, allow the applicant to edit and resubmit.
     */
    private static function applicantEditableStatuses(): array
    {
        return [
            Application::CORRECTION_REQUESTED,
            Application::RETURNED_TO_APPLICANT,
            Application::OFFICER_REJECTED,
            Application::REGISTRAR_REJECTED,
            Application::PAYMENT_REJECTED,
            Application::SUBMITTED,
            Application::SUBMITTED_TO_ACCREDITATION_OFFICER,
            Application::OFFICER_REVIEW,
            Application::REGISTRAR_REVIEW,
            Application::ACCOUNTS_REVIEW,
            Application::AWAITING_ACCOUNTS_VERIFICATION,
        ];
    }

    public function editCorrection(Application $application)
    {
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless($application->application_type === 'registration', 404);
        abort_unless(in_array($application->status, self::applicantEditableStatuses(), true), 403);

        $application->setRelation('documents', ApplicationDocument::where('application_id', $application->id)->where('is_current', true)->get());

        $draft = $application;

        $returnReasons = $application->comments()
            ->where('visibility', \App\Models\ApplicationComment::VISIBILITY_APPLICANT)
            ->whereIn('comment_type', ['rejection', 'return', 'fix_request', 'payment', 'payment_rejected', 'correction_requested'])
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();

        return view('portal.mediahouse.newregistration', compact('draft', 'returnReasons'));
    }

    public function resubmitCorrection(Request $request, Application $application)
    {
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless($application->application_type === 'registration', 404);
        abort_unless(in_array($application->status, self::applicantEditableStatuses(), true), 403);

        $validated = $request->validate([
            'collection_region' => 'required|in:harare,bulawayo',
            'form_data' => 'required',
            'documents' => 'sometimes|array',
            'documents.*' => 'file|max:10240|extensions:pdf,jpg,jpeg,png,zip',
        ]);

        $formDataRaw = $validated['form_data'];
        $formData = is_array($formDataRaw) ? $formDataRaw : (json_decode((string) $formDataRaw, true) ?: null);
        if (!is_array($formData)) {
            return response()->json(['success' => false, 'message' => 'Invalid form data payload.'], 422);
        }

        // Helper to check if a document exists in the request OR has already been uploaded
        $hasDoc = function($docType) use ($request, $application) {
            // Check if file is being uploaded now
            if ($request->hasFile("documents.{$docType}")) {
                return true;
            }
            // Check if document was uploaded previously
            return ApplicationDocument::where('application_id', $application->id)
                ->where('doc_type', $docType)
                ->where('is_current', true)
                ->exists();
        };

        // Get form type and scope
        $formType = $application->form_type ?? 'AP1';
        $scope = $formData['registration_scope'] ?? $application->journalist_scope ?? 'local';

        // Validate required documents for AP1
        if ($formType === 'AP1') {
            $requiredDocs = [
                'directors_ids' => 'Certified copy of national identity card for each director',
                'mission_statement' => 'Mission statement',
                'code_of_ethics' => 'In-house code of ethics',
                'code_of_conduct' => 'In-house code of conduct for employees',
                'style_book' => 'In-house style book',
                'certificate_of_incorporation' => 'Certificate of incorporation',
                'cr7_cr14' => 'CR7/CR14',
                'editorial_charter' => 'Editorial Charter',
                'balance_sheet_3yr' => 'Balance sheet for 3 years',
                'memorandum_of_association' => 'Memorandum of Association',
                'market_analysis' => 'Market Analysis',
                'cashflow_projection_3yr' => 'Cashflow Projection for the next 3 years',
            ];

            foreach ($requiredDocs as $docType => $docLabel) {
                if (!$hasDoc($docType)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Required document missing: {$docLabel}. Please upload all required annexures before resubmitting.",
                    ], 422);
                }
            }

            // Foreign Media Houses must also upload journalist list
            if ($scope === 'foreign' && !$hasDoc('journalist_list')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foreign Media Houses must upload the list of media practitioners employed in the representative office.',
                ], 422);
            }
        }

        // Validate required documents for AP4
        if ($formType === 'AP4') {
            $requiredDocsAP4 = [
                'rep_office_letter' => 'Letter of authorization from parent media house',
                'parent_certificate' => 'Certificate of incorporation/registration of parent organization',
                'rep_office_registration' => 'Business registration certificate (if registered locally)',
                'journalist_list' => 'List of names and addresses of media practitioners',
            ];

            foreach ($requiredDocsAP4 as $docType => $docLabel) {
                if (!$hasDoc($docType)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Required document missing: {$docLabel}. Please upload all required annexures before resubmitting.",
                    ], 422);
                }
            }
        }

        $scope = $formData['registration_scope'] ?? $application->journalist_scope;

        $this->storeUploadedDocuments($application, $request);

        $application->update([
            'collection_region' => $validated['collection_region'],
            'form_data' => $formData,
            'journalist_scope' => $scope,
            'is_draft' => false,
            'status' => Application::SUBMITTED,
            'submitted_at' => now(),
            'media_house_category_code' => $scope === 'foreign' ? 'MF' : ($formData['mass_media_activity'] ?? null),
        ]);

        $this->mergeIntoProfile([
            'organization_name' => $formData['org_name'] ?? $formData['rep_office_name'] ?? null,
            'head_office_address' => $formData['org_head_office'] ?? $formData['rep_office_address'] ?? $formData['contact_address'] ?? null,
            'mass_media_activities' => $formData['mass_media_activity'] ?? $formData['rep_office_activities'] ?? $formData['foreign_media_type'] ?? null,
            'website' => $formData['website'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application updated and resubmitted for review.',
            'reference' => $application->reference,
            'application_id' => $application->id,
            'id' => $application->id,
        ]);
    }

    public function saveDraft(Request $request)
{
    $user = Auth::user();

    $validated = $request->validate([
        'collection_region' => 'nullable|in:harare,bulawayo',
        'form_data'         => 'required',
        'documents'         => 'sometimes|array',
        'documents.*'       => 'file|max:10240|extensions:pdf,jpg,jpeg,png,zip',
    ]);

    $raw = $validated['form_data'];
    $formData = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: null);

    if (!is_array($formData)) {
        return response()->json(['success' => false, 'message' => 'Invalid form data payload.'], 422);
    }

    $formType = $formData['form_type'] ?? 'AP1';
    $formType = in_array($formType, ['AP1','AP2','AP4']) ? $formType : 'AP1';

    $draft = Application::where('applicant_user_id', $user->id)
        ->where('application_type', 'registration')
        ->where('request_type', 'new')
        ->where('is_draft', true)
        ->where(function($q) use ($formType) {
            $q->where('form_type', $formType)->orWhereNull('form_type');
        })
        ->first();

    $reference = $draft?->reference ?: ('DRAFT-' . $formType . '-' . now()->format('Y') . '-' . Str::random(6));

    $draft = Application::updateOrCreate(
        [
            'applicant_user_id' => $user->id,
            'application_type'  => 'registration',
            'request_type'      => 'new',
            'is_draft'          => true,
            'form_type'         => $formType,
        ],
        [
            'reference'         => $reference,
            'collection_region' => $validated['collection_region'] ?? $draft?->collection_region ?? 'harare',
            'form_data'         => $formData,
            'journalist_scope'  => $formData['registration_scope'] ?? null,
            'form_type'         => $formType,
            'status'            => Application::DRAFT,
        ]
    );

    // Save draft documents (update or create per doc_type)
    if ($request->hasFile('documents')) {
        foreach ((array) $request->file('documents') as $docType => $file) {
            if (!$file) continue;

            $sha256 = null;
            // Skip heavy operations for draft saves

            $path = $this->storePublicFile($file, "documents/{$draft->id}", FileType::safeHashName($file));
            $mime = FileType::safeUploadedMime($file);

            $mhDoc = ApplicationDocument::updateOrCreate(
                ['application_id' => $draft->id, 'doc_type' => (string) $docType],
                [
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'owner_id' => $user->id,
                    'mime' => $mime,
                    'size' => method_exists($file, 'getSize') ? $file->getSize() : null,
                    'sha256' => $sha256,
                    'status' => 'draft'
                ]
            );
            // Don't store binary for drafts to speed up saves

            if (\Illuminate\Support\Facades\Schema::hasTable('files')) {
                \App\Models\FileRecord::create([
                    'owner_id' => $user->id,
                    'application_id' => $draft->id,
                    'path' => $path,
                    'mime' => $mime,
                    'size' => method_exists($file, 'getSize') ? $file->getSize() : null,
                    'sha256' => $sha256,
                ]);
            }
        }
    }

    $this->mergeIntoProfile([
        'organization_name' => $formData['org_name'] ?? $formData['rep_office_name'] ?? null,
        'head_office_address' => $formData['org_head_office'] ?? $formData['rep_office_address'] ?? $formData['contact_address'] ?? null,
        'mass_media_activities' => $formData['mass_media_activity'] ?? $formData['rep_office_activities'] ?? $formData['foreign_media_type'] ?? null,
        'website' => $formData['website'] ?? null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Draft saved successfully',
        'draft_id' => $draft->id,
    ]);
}


    public function submit(Request $request)
    {
        $this->ensureWindowOpen('registration');
        $user = Auth::user();

        $validated = $request->validate([
            'collection_region' => 'required|in:harare,bulawayo',
            'form_data' => 'required',
            'documents' => 'sometimes|array',
            'documents.*' => 'file|max:10240|extensions:pdf,jpg,jpeg,png,zip',
        ]);

        $formDataRaw = $validated['form_data'];
        $formData = is_array($formDataRaw) ? $formDataRaw : (json_decode((string) $formDataRaw, true) ?: null);

        if (!is_array($formData)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid form data payload.',
            ], 422);
        }

        // Identify existing draft to check for already uploaded documents
        $existingDraft = Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'registration')
            ->where('request_type', 'new')
            ->where('is_draft', true)
            ->where(function($q) use ($formData) {
                $formType = $formData['form_type'] ?? 'AP1';
                $formType = in_array($formType, ['AP1','AP2','AP4']) ? $formType : 'AP1';
                $q->where('form_type', $formType)->orWhereNull('form_type');
            })
            ->first();

        // Helper to check if a document exists in the request OR has already been uploaded for this draft
        $hasDoc = function($docType) use ($request, $existingDraft) {
            // Check if file is being uploaded now
            if ($request->hasFile("documents.{$docType}")) {
                return true;
            }
            // Check if document was uploaded previously for this draft
            if ($existingDraft) {
                return ApplicationDocument::where('application_id', $existingDraft->id)
                    ->where('doc_type', $docType)
                    ->exists();
            }
            return false;
        };

        // Get form type and scope
        $formType = $formData['form_type'] ?? ($formData['registration_scope'] === 'foreign' ? 'AP4' : 'AP1');
        $scope = $formData['registration_scope'] ?? 'local';

        // Required documents for AP1 (Local Media House)
        if ($formType === 'AP1') {
            $requiredDocs = [
                'directors_ids' => 'Certified copy of national identity card for each director',
                'mission_statement' => 'Mission statement',
                'code_of_ethics' => 'In-house code of ethics',
                'code_of_conduct' => 'In-house code of conduct for employees',
                'style_book' => 'In-house style book',
                'certificate_of_incorporation' => 'Certificate of incorporation',
                'cr7_cr14' => 'CR7/CR14',
                'editorial_charter' => 'Editorial Charter',
                'balance_sheet_3yr' => 'Balance sheet for 3 years',
                'memorandum_of_association' => 'Memorandum of Association',
                'market_analysis' => 'Market Analysis',
                'cashflow_projection_3yr' => 'Cashflow Projection for the next 3 years',
            ];

            foreach ($requiredDocs as $docType => $docLabel) {
                if (!$hasDoc($docType)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Required document missing: {$docLabel}. Please upload all required annexures before submitting.",
                    ], 422);
                }
            }

            // Foreign Media Houses must also upload journalist list
            if ($scope === 'foreign' && !$hasDoc('journalist_list')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Foreign Media Houses must upload the list of media practitioners employed in the representative office.',
                ], 422);
            }
        }

        // Required documents for AP4 (Foreign Representative Office)
        if ($formType === 'AP4') {
            $requiredDocsAP4 = [
                'rep_office_letter' => 'Letter of authorization from parent media house',
                'parent_certificate' => 'Certificate of incorporation/registration of parent organization',
                'rep_office_registration' => 'Business registration certificate (if registered locally)',
                'journalist_list' => 'List of names and addresses of media practitioners',
            ];

            foreach ($requiredDocsAP4 as $docType => $docLabel) {
                if (!$hasDoc($docType)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Required document missing: {$docLabel}. Please upload all required annexures before submitting.",
                    ], 422);
                }
            }
        }

        $formType = $formData['form_type'] ?? ($formData['registration_scope'] === 'foreign' ? 'AP4' : 'AP1');
        $formTypePrefix = in_array($formType, ['AP1','AP2','AP4']) ? $formType : 'AP1';

        $existingDraft = Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'registration')
            ->where('request_type', 'new')
            ->where('is_draft', true)
            ->where(function($q) use ($formTypePrefix) {
                $q->where('form_type', $formTypePrefix)->orWhereNull('form_type');
            })
            ->first();

        $scope = $formData['registration_scope'] ?? 'local';

        // Generate reference only if:
        // 1. Creating new (not updating existing draft), OR
        // 2. Draft has temporary DRAFT- reference that needs conversion to ZMC-
        if ($existingDraft && !str_starts_with($existingDraft->reference ?? '', 'DRAFT-')) {
            // Keep existing real reference if already has one
            $reference = $existingDraft->reference;
        } else {
            $year = now()->format('Y');
            $prefix = "ZMC-{$formTypePrefix}-{$year}-";
            
            // Find the highest existing reference number for this form type and year
            $lastRef = Application::where('reference', 'like', $prefix . '%')
                ->where('reference', 'not like', 'DRAFT%')
                ->orderByDesc('reference')
                ->value('reference');
            
            $nextNum = 1;
            if ($lastRef) {
                $lastNum = (int) substr($lastRef, -4);
                $nextNum = $lastNum + 1;
            }
            
            $reference = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        }

        $appData = [
            'reference' => $reference,
            'collection_region' => $validated['collection_region'],
            'form_data' => $formData,
            'journalist_scope' => $scope,
            'form_type' => $formTypePrefix,
            'is_draft' => false,
            'status' => Application::SUBMITTED,
            'submitted_at' => now(),
            'media_house_category_code' => $scope === 'foreign' ? 'MF' : ($formData['mass_media_activity'] ?? null),
        ];

        if ($existingDraft) {
            $existingDraft->update($appData);
            $application = $existingDraft;
        } else {
            $application = Application::create(array_merge($appData, [
                'applicant_user_id' => $user->id,
                'application_type' => 'registration',
                'request_type' => 'new',
            ]));
        }

        $this->storeUploadedDocuments($application, $request);

        $this->mergeIntoProfile([
            'organization_name' => $formData['org_name'] ?? $formData['rep_office_name'] ?? null,
            'head_office_address' => $formData['org_head_office'] ?? $formData['rep_office_address'] ?? $formData['contact_address'] ?? null,
            'mass_media_activities' => $formData['mass_media_activity'] ?? $formData['rep_office_activities'] ?? $formData['foreign_media_type'] ?? null,
            'website' => $formData['website'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully',
            'reference' => $application->reference,
            'application_id' => $application->id,
            'id' => $application->id,
        ]);
    }

    /**
     * AP5 Draft (Media House): allow multiple drafts per user.
     * Every click creates a new draft record (no overwrite).
     */
    public function saveDraftAp5(Request $request)
    {
        $this->ensureWindowOpen('registration');
        $user = Auth::user();

        $validated = $request->validate([
            'request_type' => 'required|in:renewal,replacement',
            'draft_reference' => 'nullable|string|max:64',
            'current_step' => 'nullable|integer|min:1|max:5',
            'declaration_confirmed' => 'required|in:1',
            // optional for draft
            'previous_reference' => 'nullable|string|max:150',
            'collection_region' => 'nullable|in:harare,bulawayo',

            'proof_of_payment' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'current_certificate' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'official_request_letter' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'supporting_docs' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'affidavit' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'police_report' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
        ]);

        $year = now()->format('Y');
        $reference = 'DRAFT-AP5-' . $year . '-' . Str::upper(Str::random(6));

        // Carry local/foreign scope from previous reference if available
        $prevScope = null;
        if (!empty($validated['previous_reference'])) {
            $prevScope = Application::where('reference', $validated['previous_reference'])
                ->where('application_type', 'registration')
                ->value('journalist_scope');
        }

        $application = Application::create([
            'reference' => $reference,
            'applicant_user_id' => $user->id,
            'application_type' => 'registration',
            'request_type' => $validated['request_type'],
            'journalist_scope' => $prevScope ?? 'local',
            'collection_region' => $validated['collection_region'] ?? 'harare',
            'form_data' => $request->except(['_token']),
            'is_draft' => true,
            'status' => Application::DRAFT,
        ]);

        // Store docs if any were uploaded
        $docMap = ['proof_of_payment' => 'proof_of_payment'];
        if ($validated['request_type'] === 'renewal') {
            if ($request->hasFile('current_certificate')) $docMap['current_certificate'] = 'current_certificate';
            if ($request->hasFile('official_request_letter')) $docMap['official_request_letter'] = 'official_request_letter';
            if ($request->hasFile('supporting_docs')) $docMap['supporting_docs'] = 'supporting_docs';
        } else {
            if ($request->hasFile('affidavit')) $docMap['affidavit'] = 'affidavit';
            if ($request->hasFile('police_report')) $docMap['police_report'] = 'police_report';
        }

        foreach ($docMap as $field => $docType) {
            if (!$request->hasFile($field)) continue;
            $file = $request->file($field);
            $path = $this->storePublicFile($file, 'documents/' . $application->id, FileType::safeHashName($file));

            $apDoc = ApplicationDocument::updateOrCreate(
                ['application_id' => $application->id, 'doc_type' => $docType],
                ['file_path' => $path, 'original_name' => $file->getClientOriginalName(), 'status' => 'draft']
            );
        }

        $this->mergeIntoProfile([
            'organization_name' => $request->input('entity_name') ?? null,
            'head_office_address' => $request->input('head_office') ?? null,
            'website' => $request->input('website') ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully.',
            'reference' => $application->reference,
        ]);
    }

    public function submitAp5(Request $request)
    {
        $this->ensureWindowOpen('registration');
        $user = Auth::user();

        $validated = $request->validate([
            'request_type' => 'required|in:renewal,replacement',
            'current_step' => 'nullable|integer|min:1|max:5',

            'contact_name' => 'required|string|max:150',
            'contact_phone' => 'required|string|max:50',
            'contact_address' => 'required|string|max:500',
            'contact_email' => 'required|email|max:150',

            'entity_name' => 'required|string|max:200',
            'previous_reference' => 'required|string|max:120',
            'head_office' => 'required|string|max:500',
            'postal_address' => 'required|string|max:500',
            'changes' => 'required|in:no,yes',
            'changes_details' => 'nullable|string|max:' . config('portal.limits.long_text_max_length'),
            'collection_region' => 'required|in:harare,bulawayo',

            'replacement_reason' => 'required_if:request_type,replacement|in:lost,damaged,stolen',

            'proof_of_payment' => 'required|file|extensions:pdf,jpg,jpeg,png|max:5120',
 
            'current_certificate' => 'required_if:request_type,renewal|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'official_request_letter' => 'required_if:request_type,renewal|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'supporting_docs' => 'nullable|file|extensions:pdf,jpg,jpeg,png,zip|max:10240',
 
            'affidavit' => 'required_if:request_type,replacement|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'police_report' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
        ]);

        if (($validated['request_type'] ?? '') === 'replacement' && ($validated['replacement_reason'] ?? '') === 'stolen') {
            if (!$request->hasFile('police_report')) {
                return response()->json(['success' => false, 'message' => 'Police report is required for stolen certificates.'], 422);
            }
        }

        $year = now()->format('Y');
        $prefix = "ZMC-AP5-{$year}-";

        $lastRef = Application::where('reference', 'like', $prefix . '%')
            ->where('reference', 'not like', 'DRAFT%')
            ->orderByDesc('reference')
            ->value('reference');

        $nextNum = $lastRef ? ((int) substr($lastRef, -4) + 1) : 1;
        $reference = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        // Carry local/foreign scope from the previous registration reference (for consistent filtering/reporting)
        $prevScope = Application::where('reference', $validated['previous_reference'] ?? '')
            ->where('application_type', 'registration')
            ->value('journalist_scope');

        $application = Application::create([
            'reference' => $reference,
            'applicant_user_id' => $user->id,
            'application_type' => 'registration',
            'request_type' => $validated['request_type'],
            'journalist_scope' => $prevScope ?? 'local',
            'collection_region' => $validated['collection_region'],
            'form_data' => $request->except(['_token']),
            'is_draft' => false,
            'status' => Application::AWAITING_ACCOUNTS_VERIFICATION,
            'submitted_at' => now(),
        ]);

        $docMap = [
            'proof_of_payment' => 'proof_of_payment',
        ];

        if ($validated['request_type'] === 'renewal') {
            $docMap['current_certificate'] = 'current_certificate';
            $docMap['official_request_letter'] = 'official_request_letter';
            if ($request->hasFile('supporting_docs')) $docMap['supporting_docs'] = 'supporting_docs';
        } else {
            $docMap['affidavit'] = 'affidavit';
            if ($request->hasFile('police_report')) $docMap['police_report'] = 'police_report';
        }

        foreach ($docMap as $field => $docType) {
            if (!$request->hasFile($field)) continue;

            $file = $request->file($field);
            $path = $this->storePublicFile($file, 'documents/' . $application->id, FileType::safeHashName($file));

            $ap5Doc = ApplicationDocument::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'doc_type' => $docType,
                ],
                [
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'status' => 'pending',
                ]
            );
            $ap5Doc->storeBinaryDeferredFromPath($path);
        }

        $this->promoteDraftDocumentsToPending($application);

        $this->mergeIntoProfile([
            'organization_name' => $validated['entity_name'] ?? null,
            'head_office_address' => $validated['head_office'] ?? null,
            'website' => $validated['website'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AP5 submitted successfully',
            'reference' => $application->reference,
        ]);
    }

    private function storeUploadedDocuments(Application $application, Request $request): void
    {
        $this->promoteDraftDocumentsToPending($application);

        if (!$request->hasFile('documents')) return;

        $isResubmission = in_array($application->status, self::applicantEditableStatuses(), true);

        foreach ((array) $request->file('documents') as $docType => $file) {
            if (!$file) continue;

            $path = $this->storePublicFile($file, "documents/{$application->id}", FileType::safeHashName($file));

            $existingDoc = ApplicationDocument::where('application_id', $application->id)
                ->where('doc_type', (string) $docType)
                ->where('is_current', true)
                ->first();

            if ($existingDoc && $isResubmission) {
                $newVersion = ($existingDoc->version ?? 1) + 1;

                $newDoc = ApplicationDocument::create([
                    'application_id' => $application->id,
                    'doc_type' => (string) $docType,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'status' => 'pending',
                    'is_current' => true,
                    'version' => $newVersion,
                    'replaces_id' => $existingDoc->id,
                ]);
                $newDoc->storeBinaryDeferredFromPath($path);

                $existingDoc->update([
                    'is_current' => false,
                    'replaced_by_id' => $newDoc->id,
                    'superseded_reason' => 'Replaced during correction/resubmission',
                    'superseded_at' => now(),
                ]);
            } else {
                $upsertDoc = ApplicationDocument::updateOrCreate(
                    [
                        'application_id' => $application->id,
                        'doc_type' => (string) $docType,
                        'is_current' => true,
                    ],
                    [
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'status' => 'pending',
                        'is_current' => true,
                        'version' => 1,
                    ]
                );
                $upsertDoc->storeBinaryDeferredFromPath($path);
            }
        }
    }

    /**
     * Flip any current documents still marked as draft to pending once the
     * application is submitted, and backfill file_data bytes when missing.
     */
    private function promoteDraftDocumentsToPending(Application $application): void
    {
        $baseQuery = ApplicationDocument::where('application_id', $application->id)
            ->where('is_current', true)
            ->where('status', 'draft');

        if (\Illuminate\Support\Facades\Schema::hasColumn('application_documents', 'file_data')) {
            $missingBytes = (clone $baseQuery)
                ->whereNull('file_data')
                ->whereNotNull('file_path')
                ->select('id', 'file_path')
                ->get();

            foreach ($missingBytes as $row) {
                ApplicationDocument::where('id', $row->id)->update(['status' => 'pending']);

                $doc = new ApplicationDocument();
                $doc->id = $row->id;
                $doc->storeBinaryDeferredFromPath($row->file_path);
            }
        }

        (clone $baseQuery)->update(['status' => 'pending']);
    }

    public function renewals(Request $request)
    {
        $user = Auth::user();

        $drafts = Application::where('applicant_user_id', $user->id)
            ->where('is_draft', true)
            ->where('application_type', 'registration')
            ->whereIn('request_type', ['renewal', 'replacement'])
            ->orderByDesc('created_at')
            ->get();

        $draft = null;
        if ($request->filled('draft')) {
            $draft = $drafts->firstWhere('reference', $request->input('draft'));
        }

        return view('portal.mediahouse.renewals', compact('drafts', 'draft'));
    }

    public function payments()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $payments = \App\Models\Payment::where('payer_user_id', $user->id)
            ->with('application')
            ->orderByDesc('created_at')
            ->get();

        return view('portal.mediahouse.payments', compact('payments'));
    }

    public function notices()
    {
        $notices = \App\Models\Notice::where('is_published', true)
            ->whereIn('target_portal', ['mediahouse', 'both'])
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $events = \App\Models\Event::where('is_published', true)
            ->whereIn('target_portal', ['mediahouse', 'both'])
            ->orderBy('starts_at')
            ->limit(20)
            ->get();

        return view('portal.mediahouse.notices', compact('notices', 'events'));
    }

    public function howto()
    {
        return view('portal.mediahouse.howto');
    }

    public function profile()
    {
        return redirect()->to(route('mediahouse.settings') . '#tab-profile');
    }

    public function settings()
    {
        return view('portal.mediahouse.settings');
    }

    public function communication()
    {
        return view('portal.mediahouse.communication');
    }

    public function deleteDraft(Application $application)
    {
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless($application->is_draft, 403);

        $application->documents()->each(function ($doc) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        });

        $application->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Draft deleted successfully.'
        ]);
    }

    public function deleteDocument(\App\Models\ApplicationDocument $document)
    {
        $userId = Auth::id();
        $application = $document->application;
        abort_unless($userId && $application && $application->applicant_user_id === $userId, 403);

        $allowedStatuses = array_merge(self::applicantEditableStatuses(), ['draft']);
        $canDelete = $application->is_draft || in_array($application->status, $allowedStatuses, true);
        abort_unless($canDelete, 403, 'You can only remove documents from editable applications.');

        if ($document->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        }
        if ($document->thumbnail_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($document->thumbnail_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document removed successfully.',
        ]);
    }

    public function withdraw(Application $application)
    {
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless(!$application->is_draft, 403);
        
        $allowed = [
            Application::SUBMITTED, 
            Application::OFFICER_REVIEW, 
            Application::REGISTRAR_REVIEW, 
            Application::ACCOUNTS_REVIEW
        ];
        abort_unless(in_array($application->status, $allowed), 403);

        $application->update([
            'status' => Application::WITHDRAWN,
            'is_draft' => true,
        ]);
        
        return response()->json([
            'success' => true, 
            'message' => 'Application withdrawn and moved back to drafts.'
        ]);
    }

    /**
     * Download official letter for approved media house application
     */
    public function downloadOfficialLetter(Application $application)
    {
        $user = Auth::user();

        // Verify ownership
        if ($application->applicant_user_id !== $user->id) {
            abort(403, 'Unauthorized access to this application.');
        }

        // Verify application type
        if ($application->application_type !== 'registration') {
            abort(404, 'Official letter not available for this application type.');
        }

        // Verify official letter exists
        if (!$application->officialLetter) {
            abort(404, 'Official letter not found for this application.');
        }

        $officialLetter = $application->officialLetter;

        // Verify file exists
        if (!$officialLetter->fileExists()) {
            abort(404, 'Official letter file not found.');
        }

        // Log download
        ActivityLogger::log('download_official_letter', $application, $application->status, $application->status, [
            'official_letter_id' => $officialLetter->id,
            'file_name' => $officialLetter->file_name,
        ]);

        // Return file download
        return \Storage::disk('public')->download(
            $officialLetter->file_path,
            $officialLetter->file_name
        );
    }

    /**
     * Submit application fee payment (PayNow)
     */
    public function submitApplicationFeePaynow(Request $request, Application $application)
    {
        $user = Auth::user();

        // Verify ownership
        if ($application->applicant_user_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify application type
        if ($application->application_type !== 'registration') {
            return response()->json(['ok' => false, 'message' => 'Application fee only applies to media house registrations.'], 422);
        }

        $hasPendingSubmission = $application->paymentSubmissions()
            ->where('payment_stage', 'application_fee')
            ->whereIn('status', ['submitted', 'pending'])
            ->exists();
        if ($hasPendingSubmission || (($application->proof_status ?? null) === 'submitted' && $application->payment_proof_path)) {
            return response()->json(['ok' => false, 'message' => 'Application fee proof has already been submitted and is awaiting Accounts verification.'], 422);
        }

        if (!in_array($application->status, [
            Application::SUBMITTED,
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::ACCOUNTS_REVIEW,
        ], true)) {
            return response()->json(['ok' => false, 'message' => 'This application is not awaiting an application-fee payment.'], 422);
        }

        $data = $request->validate([
            'paynow_reference' => ['required_without:reference', 'string', 'max:255'],
            'reference' => ['required_without:paynow_reference', 'string', 'max:255'],
        ]);

        $ref = $data['paynow_reference'] ?? $data['reference'];

        $from = $application->status;

        \DB::transaction(function() use ($application, $ref, $user, $from) {
            // Create PaymentSubmission record
            $paymentSubmission = \App\Models\PaymentSubmission::create([
                'application_id' => $application->id,
                'payment_stage' => 'application_fee',
                'method' => 'PAYNOW',
                'reference' => $ref,
                'amount' => config('fees.media_house.application_fee'),
                'currency' => 'USD',
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Create Payment record
            \App\Models\Payment::create([
                'application_id' => $application->id,
                'payer_user_id' => $user->id,
                'method' => 'paynow',
                'gateway' => 'paynow',
                'reference' => $application->reference,
                'paynow_reference' => $ref,
                'status' => 'processing',
                'amount' => config('fees.media_house.application_fee'),
                'currency' => 'USD',
            ]);

            // Manual references must be verified by Accounts before they count as paid.
            $application->update([
                'status' => Application::AWAITING_ACCOUNTS_VERIFICATION,
                'payment_status' => 'pending',
                'payment_submission_method' => 'paynow_reference',
                'paynow_reference' => $ref,
                'payment_submitted_at' => now(),
                'last_action_at' => now(),
            ]);

            ActivityLogger::log('submit_application_fee_paynow', $application, $from, Application::AWAITING_ACCOUNTS_VERIFICATION, [
                'payment_submission_id' => $paymentSubmission->id,
                'reference' => $ref,
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => 'PayNow reference submitted. Accounts will verify the payment shortly.',
        ]);
    }

    /**
     * Submit application fee payment (Proof Upload)
     */
    public function submitApplicationFeeProof(Request $request, Application $application)
    {
        $user = Auth::user();

        // Verify ownership
        if ($application->applicant_user_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify application type
        if ($application->application_type !== 'registration') {
            return response()->json(['ok' => false, 'message' => 'Application fee only applies to media house registrations.'], 422);
        }

        $data = $request->validate([
            'payer_first_name' => ['nullable', 'string', 'max:100'],
            'payer_last_name' => ['nullable', 'string', 'max:100'],
            'payer_name' => ['nullable', 'string', 'max:200'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:100'],
            'proof_file' => ['required', 'file', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Combine first and last name if provided
        $payerName = $data['payer_name'] ?? trim(($data['payer_first_name'] ?? '') . ' ' . ($data['payer_last_name'] ?? ''));
        if (empty($payerName)) {
            $payerName = $user->name ?? null;
        }

        $file = $request->file('proof_file');
        $path = $this->storePublicFile($file, 'payment_proofs', FileType::safeHashName($file));
        $mime = FileType::safeUploadedMime($file);
        $hash = hash_file('sha256', \Storage::disk('public')->path($path));

        \DB::transaction(function() use ($application, $data, $file, $path, $mime, $hash, $payerName) {
            // Create PaymentSubmission record
            $paymentSubmission = \App\Models\PaymentSubmission::create([
                'application_id' => $application->id,
                'payment_stage' => 'application_fee',
                'method' => 'PROOF_UPLOAD',
                'reference' => $data['reference'] ?? null,
                'amount' => $data['amount'],
                'currency' => 'USD',
                'status' => 'submitted',
                'submitted_at' => now(),
                'proof_path' => $path,
                'proof_metadata' => [
                    'payer_name' => $payerName,
                    'payment_date' => $data['payment_date'],
                    'amount' => $data['amount'],
                    'method' => $data['method'] ?? null,
                    'file_name' => $file->getClientOriginalName(),
                    'file_hash' => $hash,
                ],
            ]);

            \App\Support\PaymentProofMirror::mirror(
                $application,
                $path,
                'proof_of_payment',
                $file->getClientOriginalName(),
                $mime,
                $file->getSize() ?: null,
                $hash
            );

            // Transition status
            ApplicationWorkflow::transition(
                $application,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                'submit_application_fee_proof',
                ['payment_submission_id' => $paymentSubmission->id]
            );

            ActivityLogger::log('submit_application_fee_proof', $application, $application->status, $application->status, [
                'payment_submission_id' => $paymentSubmission->id,
                'amount' => $data['amount'],
                'file_hash' => $hash,
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => 'Application fee proof uploaded successfully.',
        ]);
    }

    /**
     * Submit registration fee payment (PayNow)
     */
    public function submitRegistrationFeePaynow(Request $request, Application $application)
    {
        $user = Auth::user();

        // Verify ownership
        if ($application->applicant_user_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify status
        if ($application->status !== Application::REGISTRAR_APPROVED_PENDING_REG_FEE && $application->status !== Application::AWAITING_REG_FEE) {
            return response()->json(['ok' => false, 'message' => 'Registration fee can only be submitted after Registrar approval.'], 422);
        }

        $hasPendingSubmission = $application->paymentSubmissions()
            ->where('payment_stage', 'registration_fee')
            ->whereIn('status', ['submitted', 'pending'])
            ->exists();
        if ($hasPendingSubmission || (($application->proof_status ?? null) === 'submitted' && $application->payment_proof_path)) {
            return response()->json(['ok' => false, 'message' => 'Registration fee proof has already been submitted and is awaiting Accounts verification.'], 422);
        }

        $data = $request->validate([
            'paynow_reference' => ['required_without:reference', 'string', 'max:255'],
            'reference' => ['required_without:paynow_reference', 'string', 'max:255'],
        ]);

        $ref = $data['paynow_reference'] ?? $data['reference'];

        $from = $application->status;

        \DB::transaction(function() use ($application, $ref, $from) {
            // Create PaymentSubmission record
            $paymentSubmission = \App\Models\PaymentSubmission::create([
                'application_id' => $application->id,
                'payment_stage' => 'registration_fee',
                'method' => 'PAYNOW',
                'reference' => $ref,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Transition to awaiting verification
            ApplicationWorkflow::transition(
                $application,
                Application::REG_FEE_SUBMITTED_AWAITING_VERIFICATION,
                'submit_registration_fee_paynow',
                ['payment_submission_id' => $paymentSubmission->id]
            );

            ActivityLogger::log('submit_registration_fee_paynow', $application, $from, $application->status, [
                'payment_submission_id' => $paymentSubmission->id,
                'reference' => $ref,
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => 'Registration fee payment reference submitted successfully. Accounts will verify it shortly.',
        ]);
    }

    /**
     * Submit registration fee payment (Proof Upload)
     */
    public function submitRegistrationFeeProof(Request $request, Application $application)
    {
        $user = Auth::user();

        // Verify ownership
        if ($application->applicant_user_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        // Verify status
        if ($application->status !== Application::REGISTRAR_APPROVED_PENDING_REG_FEE && $application->status !== Application::AWAITING_REG_FEE) {
            return response()->json(['ok' => false, 'message' => 'Registration fee can only be submitted after Registrar approval.'], 422);
        }

        $data = $request->validate([
            'payer_first_name' => ['nullable', 'string', 'max:100'],
            'payer_last_name' => ['nullable', 'string', 'max:100'],
            'payer_name' => ['nullable', 'string', 'max:200'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:100'],
            'proof_file' => ['required', 'file', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Combine first and last name if provided
        $payerName = $data['payer_name'] ?? trim(($data['payer_first_name'] ?? '') . ' ' . ($data['payer_last_name'] ?? ''));
        if (empty($payerName)) {
            $payerName = $user->name ?? null;
        }

        $file = $request->file('proof_file');
        $path = $this->storePublicFile($file, 'payment_proofs', FileType::safeHashName($file));
        $mime = FileType::safeUploadedMime($file);
        $hash = hash_file('sha256', \Storage::disk('public')->path($path));

        $from = $application->status;

        \DB::transaction(function() use ($application, $data, $file, $path, $mime, $hash, $from, $payerName) {
            // Create PaymentSubmission record
            $paymentSubmission = \App\Models\PaymentSubmission::create([
                'application_id' => $application->id,
                'payment_stage' => 'registration_fee',
                'method' => 'PROOF_UPLOAD',
                'reference' => $data['reference'] ?? null,
                'amount' => $data['amount'],
                'currency' => 'USD',
                'status' => 'submitted',
                'submitted_at' => now(),
                'proof_path' => $path,
                'proof_metadata' => [
                    'payer_name' => $payerName,
                    'payment_date' => $data['payment_date'],
                    'amount' => $data['amount'],
                    'method' => $data['method'] ?? null,
                    'file_name' => $file->getClientOriginalName(),
                    'file_hash' => $hash,
                ],
            ]);

            // Transition to awaiting verification
            ApplicationWorkflow::transition(
                $application,
                Application::REG_FEE_SUBMITTED_AWAITING_VERIFICATION,
                'submit_registration_fee_proof',
                ['payment_submission_id' => $paymentSubmission->id]
            );

            \App\Support\PaymentProofMirror::mirror(
                $application,
                $path,
                'proof_of_payment',
                $file->getClientOriginalName(),
                $mime,
                $file->getSize() ?: null,
                $hash
            );

            ActivityLogger::log('submit_registration_fee_proof', $application, $from, $application->status, [
                'payment_submission_id' => $paymentSubmission->id,
                'amount' => $data['amount'],
                'file_hash' => $hash,
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => 'Registration fee proof uploaded successfully. Accounts will verify it shortly.',
        ]);
    }

    public function levyIndex()
    {
        $user = Auth::user();
        $levies = \App\Models\LevyRecord::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('portal.mediahouse.levy_index', compact('levies'));
    }

    public function levyCreate()
    {
        return view('portal.mediahouse.levy_form');
    }

    public function levyStore(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'postal_address' => 'nullable|string|max:500',
            'business_address' => 'nullable|string|max:500',
            'telephone' => 'nullable|string|max:50',
            'financial_year' => 'required|string|max:20',
            'media_category' => 'nullable|string|max:255',
            'subsidiary_name' => 'nullable|string|max:255',
            'subsidiary_address' => 'nullable|string|max:500',
            'gross_annual_turnover' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,ZWL,ZiG',
            'documents' => 'sometimes|array',
            'documents.*' => 'file|max:10240|extensions:pdf,jpg,jpeg,png',
        ]);

        $existingLevy = \App\Models\LevyRecord::where('user_id', $user->id)
            ->where('financial_year', $validated['financial_year'])
            ->first();

        if ($existingLevy) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a levy declaration for the ' . $validated['financial_year'] . ' financial year (Ref: ' . $existingLevy->reference . ').',
            ], 422);
        }

        $levyRate = (float) config('fees.media_house.levy_rate');
        $turnover = (float) $validated['gross_annual_turnover'];
        $levyAmount = round($turnover * $levyRate, 2);

        $levy = \App\Models\LevyRecord::create([
            'user_id' => $user->id,
            'reference' => \App\Models\LevyRecord::generateReference(),
            'entity_name' => $validated['entity_name'],
            'postal_address' => $validated['postal_address'] ?? null,
            'business_address' => $validated['business_address'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'financial_year' => $validated['financial_year'],
            'media_category' => $validated['media_category'] ?? null,
            'subsidiary_name' => $validated['subsidiary_name'] ?? null,
            'subsidiary_address' => $validated['subsidiary_address'] ?? null,
            'gross_annual_turnover' => $turnover,
            'levy_rate' => $levyRate,
            'levy_amount' => $levyAmount,
            'currency' => $validated['currency'],
            'payment_status' => 'pending',
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $docType => $file) {
                if (!$file) continue;
                $path = $this->storePublicFile($file, "levy_documents/{$levy->id}", FileType::safeHashName($file));
                \App\Models\LevyDocument::create([
                    'levy_record_id' => $levy->id,
                    'document_type' => (string) $docType,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => FileType::safeUploadedMime($file),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'AP6 Annual Levy declaration submitted. Levy amount: ' . $validated['currency'] . ' ' . number_format($levyAmount, 2),
            'reference' => $levy->reference,
            'levy_amount' => $levyAmount,
        ]);
    }

    private function ensureWindowOpen(string $windowKey): void
    {
        $win = (array) MasterSettings::get("portal_specific.public.{$windowKey}_window", ['open' => null, 'close' => null]);
        $open = $win['open'] ?? null;
        $close = $win['close'] ?? null;

        if (!$open && !$close) {
            return;
        }

        $now = now();
        if ($open && $now->lt(now()->parse($open))) {
            abort(403, ucfirst($windowKey) . ' window is not open yet.');
        }
        if ($close && $now->gt(now()->parse($close))) {
            abort(403, ucfirst($windowKey) . ' window is closed.');
        }
    }
}
