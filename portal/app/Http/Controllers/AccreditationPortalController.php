<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ApplicationComment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\MasterSettings;
use App\Support\FileType;
use App\Jobs\ProcessDraftDocuments;

use App\Services\Validation\CertificateValidator;
use App\Services\Validation\NameNormalizerService;
use App\Services\DocumentClassificationService;

class AccreditationPortalController extends Controller
{
    private CertificateValidator $certificateValidator;
    private DocumentClassificationService $documentClassificationService;

    public function __construct(CertificateValidator $certificateValidator, DocumentClassificationService $documentClassificationService)
    {
        $this->certificateValidator = $certificateValidator;
        $this->documentClassificationService = $documentClassificationService;
    }

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
        return redirect()->route('accreditation.home');
    }

    public function dashboard()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $baseQuery = Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'accreditation');

        $actionRequiredStatuses = [
            Application::CORRECTION_REQUESTED,
            Application::RETURNED_TO_APPLICANT,
            Application::OFFICER_REJECTED,
            Application::REGISTRAR_REJECTED,
            Application::PAYMENT_REJECTED,
        ];

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
                    Application::CARD_GENERATED,
                    Application::PRINTED,
                    Application::ISSUED,
                ])
                ->count(),
            'pending' => (clone $baseQuery)->where('is_draft', false)
                ->whereIn('status', [
                    Application::SUBMITTED,
                    Application::OFFICER_REVIEW,
                    Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
                    Application::REGISTRAR_REVIEW,
                    Application::ACCOUNTS_REVIEW,
                    Application::AWAITING_ACCOUNTS_VERIFICATION,
                    Application::REGISTRAR_RAISED_FIX_REQUEST,
                    Application::RETURNED_TO_OFFICER,
                ])
                ->count(),
            'action_required' => (clone $baseQuery)->where('is_draft', false)
                ->whereIn('status', $actionRequiredStatuses)
                ->count(),
            'renewals_due' => 0,
        ];

        // Applications waiting for the applicant to act (returned for correction / rejected for re-submission)
        $actionRequired = (clone $baseQuery)
            ->where('is_draft', false)
            ->whereIn('status', $actionRequiredStatuses)
            ->orderByDesc('updated_at')
            ->get();

        $recentApplications = (clone $baseQuery)
            ->where('is_draft', false)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->with('paymentSubmissions')
            ->get();

        $drafts = (clone $baseQuery)
            ->where('is_draft', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        $notices = \App\Models\Notice::where('is_published', true)
            ->whereIn('target_portal', ['journalist', 'both'])
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        $events = \App\Models\Event::where('is_published', true)
            ->whereIn('target_portal', ['journalist', 'both'])
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $reminders = collect();
        if (Schema::hasTable('reminders')) {
            $reminders = \App\Models\Reminder::active()
                ->forUser($user->id)
                ->whereDoesntHave('reads', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->whereNotNull('acknowledged_at');
                })
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('portal.accreditation.dashboard', compact('stats', 'recentApplications', 'actionRequired', 'drafts', 'notices', 'events', 'reminders'));
    }

    public function new()
    {
        $userId = Auth::id();

        // A practitioner may submit only one new AP3 application. Follow-up
        // changes use that application's amendment or correction workflow.

        $activeApp = Application::where('applicant_user_id', $userId)
            ->where('application_type', 'accreditation')
            ->where('request_type', 'new')
            ->where('is_draft', false)
            ->latest('id')
            ->first();

        if ($activeApp) {
            return redirect()
                ->route('accreditation.home')
                ->with('error', "You already submitted accreditation application ({$activeApp->reference}). Use its amendment or correction request workflow instead of creating another new application.");
        }

        $draftQuery = Application::where('applicant_user_id', $userId)
            ->where('application_type', 'accreditation')
            ->where('request_type', 'new')
            ->where('is_draft', true);
        $draft = request()->filled('draft')
            ? $draftQuery->where('reference', request()->query('draft'))->firstOrFail()
            : $draftQuery->latest('updated_at')->first();

        // IMPORTANT: load docs so blade review can show "saved" documents
        if ($draft) {
            $draft->setRelation('documents', ApplicationDocument::where('application_id', $draft->id)->get());
        }

        return view('portal.accreditation.new', compact('draft'));
    }

    /**
     * If an application was returned for correction, allow the applicant to edit and resubmit.
     */
    private static function applicantEditableStatuses(): array
    {
        return [
            Application::CORRECTION_REQUESTED,
            Application::RETURNED_TO_APPLICANT,
            Application::OFFICER_REJECTED,
            Application::REGISTRAR_REJECTED,
            Application::PAYMENT_REJECTED,
        ];
    }

    public function editCorrection(Application $application)
    {
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless($application->application_type === 'accreditation', 404);
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

        return view('portal.accreditation.new', compact('draft', 'returnReasons'));
    }

    public function requestAmendment(Request $request, Application $application)
    {
        abort_unless($application->applicant_user_id === Auth::id(), 403);
        abort_if($application->is_draft, 422, 'Save or submit the application before requesting an amendment.');

        $data = $request->validate([
            'reason' => [
                'required',
                'string',
                'min:' . config('portal.limits.reason_min_length'),
                'max:' . config('portal.limits.reason_max_length'),
            ],
        ]);

        ApplicationComment::create([
            'application_id' => $application->id,
            'user_id' => Auth::id(),
            'user_role' => 'applicant',
            'visibility' => ApplicationComment::VISIBILITY_APPLICANT,
            'comment_type' => 'amendment_request',
            'body' => $data['reason'],
            'application_status_at_time' => $application->status,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Your amendment request and reason have been recorded for ZMC review. The submitted application remains unchanged until it is reviewed.');
    }

    public function resubmitCorrection(Request $request, Application $application)
    {
        $this->ensureWindowOpen('accreditation');
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless($application->application_type === 'accreditation', 404);
        abort_unless(in_array($application->status, self::applicantEditableStatuses(), true), 403);

        // form_data may arrive as JSON string
        $rawFormData = $request->input('form_data');
        $formData = $rawFormData;
        if (is_string($rawFormData)) {
            $decoded = json_decode($rawFormData, true);
            $formData = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($formData)) {
            $formData = [];
        }

        // Validate National Reg. No from form_data (if local)
        $scope = $request->input('journalist_scope', $formData['journalist_scope'] ?? $application->journalist_scope);
        if ($scope === 'local' && !empty($formData['national_reg_no'])) {
            if (!preg_match('/^\d{2}-\d+[A-Z]\d{2}$/i', $formData['national_reg_no'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid National Reg. No format. Expected format: 08-2048701657V47',
                ], 422);
            }
        }

        $this->saveDraftDocuments($request, $application, [
            'passport_photo',
            'id_scan',
            'employment_letter',
            'reference_letter',
            'educational_certificate',
            'passport_biodata_page',
            'clearance_letter',
        ], 'pending', true);

        $hasEducationalCert = $request->hasFile('educational_certificate') || 
            ApplicationDocument::where('application_id', $application->id)
                ->where('doc_type', 'educational_certificate')
                ->where('is_current', true)
                ->exists();

        $application->update([
            'journalist_scope'  => $request->input('journalist_scope', $formData['journalist_scope'] ?? $application->journalist_scope),
            'collection_region' => $request->input('collection_region', $formData['collection_region'] ?? $application->collection_region),
            'form_data'         => $formData,
            'is_draft'          => false,
            'status'            => Application::AWAITING_ACCOUNTS_VERIFICATION,
            'submitted_at'      => now(),
            'accreditation_category_code' => $this->calculateAccreditationCategoryCode($scope, $formData, $hasEducationalCert),
        ]);

        $this->mergeIntoProfile([
            'title' => $formData['title'] ?? null,
            'first_name' => $formData['first_name'] ?? null,
            'surname' => $formData['surname'] ?? null,
            'other_names' => $formData['other_names'] ?? null,
            'phone' => ($formData['phone_country_code'] ?? '') . ($formData['phone'] ?? ''),
            'email' => $formData['email'] ?? null,
            'province' => $formData['province'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application updated and resubmitted for review.',
            'reference' => $application->reference,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'id_number' => ['nullable', 'string', 'regex:/^\d{2}-\d+[A-Z]\d{2}$/i'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'phone2' => ['nullable', 'string', 'max:30'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'profile' => ['nullable', 'array'],
        ]);

        $updates = [];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'id_number')) {
            $updates['id_number'] = $data['id_number'] ?? $user->id_number;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'passport_number')) {
            $updates['passport_number'] = $data['passport_number'] ?? $user->passport_number;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'phone2')) {
            $updates['phone2'] = $data['phone2'] ?? $user->phone2;
        }
        if (isset($data['phone_number'])) {
            $updates['phone_number'] = $data['phone_number'];
        }

        $profile = $user->profile_data ?? [];
        if (!empty($data['profile'])) {
            $profile = array_merge($profile, $data['profile']);
        }
        if (isset($data['nationality'])) {
            $profile['nationality'] = $data['nationality'];
        }
        if ($profile !== ($user->profile_data ?? [])) {
            $updates['profile_data'] = $profile;
        }

        $user->update($updates);

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Save Draft (FILES + JSON)
     * NOTE: This endpoint MUST be called with multipart/form-data (FormData),
     * not JSON, otherwise files will never arrive here.
     */
    public function saveDraft(Request $request)
    {
        try {
            $user = Auth::user();

            // form_data may arrive as JSON string
            $rawFormData = $request->input('form_data');
            $formData = $rawFormData;

            if (is_string($rawFormData)) {
                $decoded = json_decode($rawFormData, true);
                $formData = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($formData)) {
                $formData = [];
            }

            $draft = Application::where('applicant_user_id', $user->id)
                ->where('application_type', 'accreditation')
                ->where('request_type', 'new')
                ->where('is_draft', true)
                ->first();

            $reference = $draft?->reference ?: ('DRAFT-AP3-' . now()->format('Y') . '-' . Str::random(6));

            $draft = Application::updateOrCreate(
                [
                    'applicant_user_id' => $user->id,
                    'application_type'  => 'accreditation',
                    'request_type'      => 'new',
                    'is_draft'          => true,
                ],
                [
                    'reference'         => $reference,
                    'journalist_scope'  => $request->input('journalist_scope', $formData['journalist_scope'] ?? null),
                    'collection_region' => $request->input('collection_region', $formData['collection_region'] ?? 'harare'),
                    'form_data'         => $formData,
                    'status'            => Application::DRAFT,
                ]
            );

            // Save draft documents (NEW: support all doc fields used in your blade)
            $this->saveDraftDocuments($request, $draft, [
                'passport_photo',
                'id_scan',
                'employment_letter',
                'reference_letter',
                'educational_certificate',
                'passport_biodata_page',
                'clearance_letter',
            ], 'draft', false);

            // Keep profile updated using formData values
            $this->mergeIntoProfile([
                'title' => $formData['title'] ?? null,
                'first_name' => $formData['first_name'] ?? null,
                'surname' => $formData['surname'] ?? null,
                'other_names' => $formData['other_names'] ?? null,
                'phone' => ($formData['phone_country_code'] ?? '') . ($formData['phone'] ?? ''),
                'email' => $formData['email'] ?? null,
                'province' => $formData['province'] ?? null,
            ]);

            return response()->json([
                'success'  => true,
                'message'  => 'Draft saved successfully',
                'draft_id' => $draft->id,
                'reference' => $draft->reference,
            ]);
        } catch (\Throwable $e) {
            $incidentId = (string) Str::uuid();
            Log::error('Failed to save accreditation draft', [
                'user_id' => Auth::id(),
                'incident_id' => $incidentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'We could not save your draft. Please try again. If the problem continues, contact support with reference ' . $incidentId . '.',
            ], 500);
        }
    }

    private function validateEducationalCertificate($file, $applicantName = '', $applicantFirstName = '', $applicantSurname = ''): bool
    {
        $disk = Storage::disk('public');
        $path = $this->storePublicFile($file, 'temp/certificate-validation', \Illuminate\Support\Str::random(40) . "." . $file->getClientOriginalExtension());

        $document = new ApplicationDocument();
        $document->file_path = $path;
        $document->original_name = $file->getClientOriginalName();
        $document->mime = FileType::safeUploadedMime($file);
        $document->size = $file->getSize();

        $extractedText = $this->documentClassificationService->extractTextFromFile($disk, $path);
        $document->extracted_text = $extractedText;

        $isValid = true;

        if (!empty($extractedText) && strlen($extractedText) >= 50) {
            $classification = $this->documentClassificationService->classifyDocument($document, 'graduation_certificate');
            $detectedType = $classification['detected_document_type'] ?? 'unknown';

            if (!in_array($detectedType, ['graduation_certificate', 'certificate', 'unknown'], true)) {
                $isValid = false;
            }


        } elseif (!empty($extractedText) && strlen($extractedText) < 50) {
            $isValid = false;
        }

        $disk->delete($path);

        return $isValid;
    }

    private function validateDocumentType($file, string $expectedType): bool
    {
        $disk = Storage::disk('public');
        $path = $this->storePublicFile($file, 'temp/document-validation', \Illuminate\Support\Str::random(40) . "." . $file->getClientOriginalExtension());

        $document = new ApplicationDocument();
        $document->file_path = $path;
        $document->original_name = $file->getClientOriginalName();
        $document->mime = FileType::safeUploadedMime($file);
        $document->size = $file->getSize();

        $extractedText = $this->documentClassificationService->extractTextFromFile($disk, $path);
        $document->extracted_text = $extractedText;

        $isValid = true;

        if (!empty($extractedText) && strlen($extractedText) >= 20) {
            $classification = $this->documentClassificationService->classifyDocument($document, $expectedType);
            $detectedType = $classification['detected_document_type'] ?? 'unknown';

            // We only reject if we confidently detected it as something else.
            if ($detectedType !== $expectedType && $detectedType !== 'unknown') {
                $isValid = false;
            }
        }

        $disk->delete($path);

        return $isValid;
    }

    private function calculateAccreditationCategoryCode(string $scope, array $formData, bool $hasEducationalCert): string
    {
        if ($scope === 'foreign') {
            return 'JT';
        }

        $employmentType = $formData['employment_type'] ?? null;
        if ($hasEducationalCert) {
            if ($employmentType === 'employed') {
                return 'JE';
            } else {
                $designation = strtolower($formData['designation'] ?? '');
                if (str_contains($designation, 'string')) {
                    return 'JS';
                } elseif (str_contains($designation, 'office')) {
                    return 'JO';
                } elseif (str_contains($designation, 'both')) {
                    return 'JM';
                } else {
                    return 'JF';
                }
            }
        } else {
            $mediumType = strtolower($formData['medium_type'] ?? '');
            $designation = strtolower($formData['designation'] ?? '');
            if (str_contains($mediumType, 'social') || str_contains($designation, 'social')) {
                return 'JD';
            } else {
                return 'JP';
            }
        }
    }

    private function saveDraftDocuments(Request $request, Application $application, array $fields, string $status = 'draft', ?bool $storeBinary = null): void
    {
        if ($storeBinary === null) {
            $storeBinary = $status !== 'draft';
        }

        $isResubmission = in_array($application->status, [
            Application::CORRECTION_REQUESTED,
            Application::RETURNED_TO_APPLICANT,
            Application::OFFICER_REJECTED,
            Application::REGISTRAR_REJECTED,
            Application::PAYMENT_REJECTED,
        ], true);

        $isDraft = $status === 'draft';
        $filesToProcess = [];

        foreach ($fields as $field) {
            if (!$request->hasFile($field)) continue;

            $uploadedFiles = $request->file($field);
            $multiple = is_array($uploadedFiles);
            foreach ($multiple ? $uploadedFiles : [$uploadedFiles] as $fileIndex => $file) {
                if (!$file) continue;
                $docType = $field === 'educational_certificate' && $multiple
                    ? $field . '_' . ((int) $fileIndex + 1)
                    : $field;

                // Certificate validation disabled - accepting all valid file types (PDF/JPG/PNG)
                // Re-enable and tune validation logic if stricter checks are needed in the future
                // if ($field === 'educational_certificate') {
                //     $firstName = trim((string) ($request->input('first_name', '') ?: ($application->form_data['first_name'] ?? '')));
                //     $surname = trim((string) ($request->input('surname', '') ?: ($application->form_data['surname'] ?? '')));
                //     $fullName = trim((string) ($request->input('first_name', '') . ' ' . $request->input('surname', '') ?: ($application->form_data['first_name'] ?? '') . ' ' . ($application->form_data['surname'] ?? '')));
                //
                //     if (!$this->validateEducationalCertificate($file, $fullName, $firstName, $surname)) {
                //         continue;
                //     }
                // }

                $sha256 = null;
                if ($storeBinary || $isResubmission) {
                    try { $sha256 = hash_file('sha256', $file->getRealPath()); } catch (\Throwable $e) {}
                }

                if ($sha256 && Schema::hasColumn('application_documents', 'sha256')) {
                    $exists = ApplicationDocument::where('application_id', $application->id)
                        ->where('doc_type', $docType)
                        ->where('sha256', $sha256)
                        ->where('is_current', true)
                        ->exists();
                    if ($exists) continue;
                }

                if ($isDraft) {
                    // Store in temp location for async processing
                    $tmpPath = $file->store('temp/drafts/' . $application->id, 'public');
                    $filesToProcess[$field][] = [
                        'tmp_path'       => $tmpPath,
                        'original_name'  => $file->getClientOriginalName(),
                        'mime'           => $this->safeUploadedFileMime($file),
                        'size'           => method_exists($file, 'getSize') ? $file->getSize() : null,
                        'doc_type'       => $docType,
                        'sha256'         => $sha256,
                    ];
                } else {
                    // Synchronous processing for submissions
                    $path = $this->storePublicFile($file, 'documents/' . $application->id, \Illuminate\Support\Str::random(40) . "." . $file->getClientOriginalExtension());

                    $docData = [
                        'file_path'      => $path,
                        'original_name'  => $file->getClientOriginalName(),
                        'status'         => $status,
                        'owner_id'       => auth()->id(),
                        'mime'           => $this->safeUploadedFileMime($file),
                        'size'           => method_exists($file, 'getSize')     ? $file->getSize()     : null,
                        'sha256'         => $sha256,
                        'is_current'     => true,
                        'version'        => 1,
                    ];

                    $existingDoc = ApplicationDocument::where('application_id', $application->id)
                        ->where('doc_type', $docType)
                        ->where('is_current', true)
                        ->first();

                    if ($existingDoc && $isResubmission) {
                        $newVersion = ($existingDoc->version ?? 1) + 1;
                        $docData['version'] = $newVersion;
                        $docData['replaces_id'] = $existingDoc->id;

                        $newDoc = ApplicationDocument::create(array_merge($docData, [
                            'application_id' => $application->id,
                            'doc_type'       => $docType,
                        ]));
                        if ($storeBinary) {
                            $newDoc->storeBinaryDeferredFromPath($path);
                        }

                        $existingDoc->update([
                            'is_current'         => false,
                            'replaced_by_id'     => $newDoc->id,
                            'superseded_reason'  => 'Replaced during correction/resubmission',
                            'superseded_at'      => now(),
                        ]);
                    } else {
                        $doc = ApplicationDocument::updateOrCreate(
                            [
                                'application_id' => $application->id,
                                'doc_type'       => $docType,
                                'is_current'     => true,
                            ],
                            $docData
                        );
                        if ($storeBinary) {
                            $doc->storeBinaryDeferredFromPath($path);
                        }
                    }
                }
            }
        }

        if ($request->hasFile('past_work_samples')) {
            $samples = $request->file('past_work_samples');
            if (is_array($samples)) {
                foreach ($samples as $idx => $file) {
                    $docType = 'past_work_sample_' . ($idx + 1);
                    $sha256 = null;
                    if ($storeBinary || $isResubmission) {
                        try { $sha256 = hash_file('sha256', $file->getRealPath()); } catch (\Throwable $e) {}
                    }

                    if ($isDraft) {
                        $tmpPath = $file->store('temp/drafts/' . $application->id, 'public');
                        $filesToProcess['past_work_samples'][] = [
                            'tmp_path'       => $tmpPath,
                            'original_name'  => $file->getClientOriginalName(),
                            'mime'           => $this->safeUploadedFileMime($file),
                            'size'           => method_exists($file, 'getSize') ? $file->getSize() : null,
                            'doc_type'       => $docType,
                            'sha256'         => $sha256,
                        ];
                    } else {
                        $path = $this->storePublicFile($file, 'documents/' . $application->id, \Illuminate\Support\Str::random(40) . "." . $file->getClientOriginalExtension());

                        $docData = [
                            'file_path'      => $path,
                            'original_name'  => $file->getClientOriginalName(),
                            'status'         => $status,
                            'owner_id'       => auth()->id(),
                            'mime'           => $this->safeUploadedFileMime($file),
                            'size'           => method_exists($file, 'getSize')     ? $file->getSize()     : null,
                            'sha256'         => $sha256,
                            'is_current'     => true,
                            'version'        => 1,
                        ];

                        $sampleDoc = ApplicationDocument::updateOrCreate(
                            [
                                'application_id' => $application->id,
                                'doc_type'       => $docType,
                            ],
                            $docData
                        );
                        if ($storeBinary) {
                            $sampleDoc->storeBinaryDeferredFromPath($path);
                        }
                    }
                }
            }
        }

        if ($isDraft && !empty($filesToProcess)) {
            // Dispatch async job for draft document processing
            ProcessDraftDocuments::dispatch($application->id, $filesToProcess, $status);
        }

        if ($status !== 'draft') {
            $this->promoteDraftDocumentsToPending($application);
        }
    }

    /**
     * Flip any current documents still marked as draft to pending once the
     * application is submitted, and backfill file_data bytes when missing.
     * Keeps the officer "pending documents" queue consistent even when the
     * submit request did not re-send every previously saved file.
     */
    private function promoteDraftDocumentsToPending(Application $application): void
    {
        $baseQuery = ApplicationDocument::where('application_id', $application->id)
            ->where('is_current', true)
            ->where('status', 'draft');

        if (Schema::hasColumn('application_documents', 'file_data')) {
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

    private function safeUploadedFileMime($file): ?string
    {
        return FileType::safeUploadedMime($file);
    }

    /**
     * Submit Application (FILES + JSON)
     * Enforces Local vs Foreign requirements and Employed vs Freelancer.
     */
    public function submit(Request $request)
    {
        $this->ensureWindowOpen('accreditation');
        $user = Auth::user();

        // Base request validation for files + top fields
        $validated = $request->validate([
            'journalist_scope'   => 'required|in:local,foreign',
            'collection_region'  => 'nullable|in:harare,bulawayo,mutare,masvingo', // only required for local in our rules
            'form_data'          => 'nullable',

            // Uploads
            'passport_photo'        => 'nullable|file|extensions:jpg,jpeg,png|max:5120',
            'id_scan'               => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'employment_letter'     => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'reference_letter'      => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'educational_certificate' => 'nullable|array|max:5',
            'educational_certificate.*' => 'file|extensions:pdf,jpg,jpeg,png|max:5120',

            'passport_biodata_page' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'clearance_letter'      => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Decode form_data safely
        $rawFormData = $request->input('form_data');
        $formData = $rawFormData;
        if (is_string($rawFormData)) {
            $decoded = json_decode($rawFormData, true);
            $formData = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($formData)) $formData = [];

        // Identify existing draft EARLY to support file presence check
        $existingDraft = Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'accreditation')
            ->where('request_type', 'new')
            ->where('is_draft', true)
            ->first();

        // Helper to check if a file exists in the request OR has already been uploaded for this draft
        $hasDoc = function($field) use ($request, $existingDraft) {
            if ($request->hasFile($field)) return true;
            if ($existingDraft) {
                $query = ApplicationDocument::where('application_id', $existingDraft->id);
                if ($field === 'educational_certificate') {
                    $query->where(function ($documents) use ($field) {
                        $documents->where('doc_type', $field)->orWhere('doc_type', 'like', $field . '_%');
                    });
                } else {
                    $query->where('doc_type', $field);
                }
                return $query->exists();
            }
            return false;
        };

        $scope = $validated['journalist_scope'] ?? $formData['journalist_scope'] ?? ($existingDraft ? $existingDraft->journalist_scope : 'local');
        $employmentType = $formData['employment_type'] ?? null;

        // ---- REQUIRED FIELDS (SPEC) ----
        $requiredCommon = [
            'title', 'surname', 'first_name', 'dob',
            'marital_status', 'gender', 'nationality', 'address',
            'phone_country_code', 'phone', 'email',
            'employment_type', 'medium_type', 'designation',
            'declaration_date',
        ];

        foreach ($requiredCommon as $k) {
            if (empty($formData[$k])) {
                return response()->json([
                    'success' => false,
                    'message' => "Missing required field: {$k}",
                ], 422);
            }
        }

        // Strict email format validation for the applicant's contact email
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address (e.g. name@example.com).',
            ], 422);
        }

        // Enforce the one-new-application rule at submission as well as on the
        // form page; drafts remain resumable.
        $hasActive = Application::where('applicant_user_id', $user->id)
            ->where('application_type', 'accreditation')
            ->where('request_type', 'new')
            ->where('is_draft', false)
            ->when($existingDraft, fn($q) => $q->where('id', '!=', $existingDraft->id))
            ->exists();
        if ($hasActive) {
            return response()->json([
                'success' => false,
                'message' => 'You have already submitted a new application. Use its amendment or correction request workflow from your dashboard.',
            ], 422);
        }

        // Referees: 3 required
        for ($i = 1; $i <= 3; $i++) {
            $rk = "referee_name_{$i}";
            $ra = "referee_address_{$i}";
            $rp = "referee_phone_{$i}";
            if (empty($formData[$rk]) || empty($formData[$ra]) || empty($formData[$rp])) {
                return response()->json([
                    'success' => false,
                    'message' => "All 3 referees are required (Name, Address, Phone). Missing referee {$i}.",
                ], 422);
            }
        }

        // Local vs Foreign
        if ($scope === 'local') {
            $validProvinces = ['harare', 'bulawayo', 'manicaland', 'mashonaland_central', 'mashonaland_east', 'mashonaland_west', 'masvingo', 'matabeleland_north', 'matabeleland_south', 'midlands'];
            if (!in_array($formData['province'] ?? null, $validProvinces, true)) {
                return response()->json(['success' => false, 'message' => 'Please select your province.'], 422);
            }
            if (empty($formData['national_reg_no'])) {
                return response()->json(['success'=>false,'message'=>'National Reg. No is required for local applications.'], 422);
            }
            // Collection office required for local
            $collection = $request->input('collection_region') ?: ($formData['collection_region'] ?? null);
            if (!$collection) {
                return response()->json(['success'=>false,'message'=>'Collection Office is required for local applications.'], 422);
            }

            if (!preg_match('/^\d{2}-\d+[A-Z]\d{2}$/i', $formData['national_reg_no'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid National Reg. No format. Expected format: 08-2048701657V47',
                ], 422);
            }
        }

        if ($scope === 'foreign') {
            $requiredForeign = [
                'passport_no', 'passport_expiry', 'passport_issued_at',
                'first_time_in_zim', 'address_in_zimbabwe',
            ];
            foreach ($requiredForeign as $k) {
                if (empty($formData[$k])) {
                    return response()->json(['success'=>false,'message'=>"Missing required field for foreign application: {$k}"], 422);
                }
            }

            // Foreign travel fields (Required for ALL foreign applicants)
            $requiredForeignTravel = ['arrived_on', 'arrival_mode', 'port_of_entry', 'departing_on', 'special_assignment'];
            foreach ($requiredForeignTravel as $k) {
                if (empty($formData[$k])) {
                    return response()->json(['success'=>false,'message'=>"Missing required foreign travel field: {$k}"], 422);
                }
            }
        }

        // Employed vs Freelancer upload requirements (Local only per spec)
        if ($scope === 'local') {
            if ($employmentType === 'employed' && !$hasDoc('employment_letter')) {
                return response()->json(['success'=>false,'message'=>'Employment Letter is required for Employed applicants.'], 422);
            }
            if ($employmentType === 'freelancer' && !$hasDoc('reference_letter')) {
                return response()->json(['success'=>false,'message'=>'Reference/Testimonial/Affidavit is required for Freelancers.'], 422);
            }
        }

        // Required photo always
        if (!$hasDoc('passport_photo')) {
            return response()->json(['success'=>false,'message'=>'Photo is required. Please upload or take a passport photo.'], 422);
        }

        // Required ID docs
        if ($scope === 'local' && !$hasDoc('id_scan')) {
            return response()->json(['success'=>false,'message'=>'National ID Scan is required for Local applications.'], 422);
        } elseif ($scope === 'local' && $request->hasFile('id_scan')) {
            if (!$this->validateDocumentType($request->file('id_scan'), 'local_national_id')) {
                return response()->json(['success'=>false,'message'=>'The uploaded document does not appear to be a valid National ID.'], 422);
            }
        }

        if ($scope === 'foreign') {
            if (!$hasDoc('passport_biodata_page')) {
                return response()->json(['success'=>false,'message'=>'Passport Bio Data Page is required for Foreign applications.'], 422);
            } elseif ($request->hasFile('passport_biodata_page')) {
                if (!$this->validateDocumentType($request->file('passport_biodata_page'), 'passport')) {
                    return response()->json(['success'=>false,'message'=>'The uploaded document does not appear to be a valid Passport Bio Data Page.'], 422);
                }
            }
            if (!$hasDoc('clearance_letter')) {
                return response()->json(['success'=>false,'message'=>'Clearance Letter is required for Foreign applications.'], 422);
            }
        }

        // Active link validation
        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'past_work_link_url_') && !empty($value)) {
                $url = filter_var($value, FILTER_SANITIZE_URL);
                if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                    $url = 'http://' . $url;
                }
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return response()->json([
                        'success' => false,
                        'message' => "The provided portfolio link ($value) is invalid.",
                    ], 422);
                }
                try {
                    $res = \Illuminate\Support\Facades\Http::timeout(5)->get($url);
                    if (!$res->successful() && !in_array($res->status(), [401, 403, 405])) {
                        return response()->json([
                            'success' => false,
                            'message' => "The provided portfolio link ($value) is unreachable or inactive.",
                        ], 422);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => "The provided portfolio link ($value) is unreachable or inactive.",
                    ], 422);
                }
            }
        }

        // Merge into profile (based on formData)
        $this->mergeIntoProfile([
            'title' => $formData['title'] ?? null,
            'surname' => $formData['surname'] ?? null,
            'first_name' => $formData['first_name'] ?? null,
            'other_names' => $formData['other_names'] ?? null,
            'date_of_birth' => $formData['dob'] ?? null,
            'place_birth' => $formData['birth_place'] ?? null,
            'marital_status' => $formData['marital_status'] ?? null,
            'sex' => $formData['gender'] ?? null,
            'national_reg_no' => $formData['national_reg_no'] ?? null,
            'passport_no' => $formData['passport_no'] ?? null,
            'passport_expiry' => $formData['passport_expiry'] ?? null,
            'passport_issued_at' => $formData['passport_issued_at'] ?? null,
            'nationality' => $formData['nationality'] ?? null,
            'drivers_licence_no' => $formData['drivers_licence_no'] ?? null,
            'residential_address' => $formData['address'] ?? null,
            'phone' => ($formData['phone_country_code'] ?? '') . ($formData['phone'] ?? ''),
            'email' => $formData['email'] ?? null,
            'employment_type' => $formData['employment_type'] ?? null,
        ]);

        // Generate reference (ZMC-AP3-YYYY-0001)
        $year = now()->format('Y');
        $prefix = "ZMC-AP3-{$year}-";

        $lastRef = Application::where('reference', 'like', $prefix . '%')
            ->where('reference', 'not like', 'DRAFT%')
            ->orderByRaw("reference DESC")
            ->value('reference');

        $nextNum = 1;
        if ($lastRef) {
            $lastNum = (int) substr($lastRef, -4);
            $nextNum = $lastNum + 1;
        }

        $reference = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $collectionRegion = $request->input('collection_region') ?: ($formData['collection_region'] ?? 'harare');

        $paymentMethod = $request->input('payment_payment_method') ?: $request->input('payment_method');
        $hasPayment = !empty($paymentMethod) || $request->hasFile('payment_proof_file') || $request->hasFile('payment_waiver_file');
        // Set initial status to SUBMITTED (pending payment) - it will move to Accounts after payment proof is uploaded
        $initialStatus = Application::SUBMITTED;

        $paymentFields = [];
        if ($hasPayment) {
            $paymentFields = [
                'payment_submission_method' => $paymentMethod ?? 'proof_upload',
                'payment_submitted_at' => now(),
                'proof_status' => 'submitted',
                'payment_status' => 'pending',
            ];
            if ($request->input('payment_paynow_reference')) {
                $paymentFields['paynow_ref_submitted'] = $request->input('payment_paynow_reference');
                $paymentFields['payment_submission_method'] = 'paynow_reference';
            }
            if ($request->input('payment_proof_amount_paid')) {
                $paymentFields['proof_amount_paid'] = $request->input('payment_proof_amount_paid');
            }
            if ($request->input('payment_proof_bank_name')) {
                $paymentFields['proof_bank_name'] = $request->input('payment_proof_bank_name');
            }
            if ($request->input('payment_proof_payment_date')) {
                $paymentFields['proof_payment_date'] = $request->input('payment_proof_payment_date');
            }
            if ($request->input('payment_proof_first_name')) {
                $paymentFields['proof_payer_first_name'] = $request->input('payment_proof_first_name');
            }
            if ($request->input('payment_proof_last_name')) {
                $paymentFields['proof_payer_last_name'] = $request->input('payment_proof_last_name');
            }
        }

        $hasEducationalCert = $hasDoc('educational_certificate');
        
        if ($existingDraft) {
            $existingDraft->update(array_merge([
                'reference'         => $reference,
                'journalist_scope'  => $scope,
                'collection_region' => $collectionRegion,
                'form_data'         => $formData,
                'is_draft'          => false,
                'status'            => $initialStatus,
                'submitted_at'      => now(),
                'accreditation_category_code' => $this->calculateAccreditationCategoryCode($scope, $formData, $hasEducationalCert),
            ], $paymentFields));
            $application = $existingDraft;
        } else {
            $application = Application::create(array_merge([
                'reference'         => $reference,
                'applicant_user_id' => $user->id,
                'application_type'  => 'accreditation',
                'request_type'      => 'new',
                'journalist_scope'  => $scope,
                'collection_region' => $collectionRegion,
                'form_data'         => $formData,
                'is_draft'          => false,
                'status'            => $initialStatus,
                'submitted_at'      => now(),
                'accreditation_category_code' => $this->calculateAccreditationCategoryCode($scope, $formData, $hasEducationalCert),
            ], $paymentFields));
        }

        if ($hasPayment && $request->hasFile('payment_proof_file')) {
            $request->validate(['payment_proof_file' => ['file', 'extensions:pdf,jpg,jpeg,png', 'max:10240']]);
            $proofFile = $request->file('payment_proof_file');
            $proofPath = $this->storePublicFile($proofFile, 'payment_proofs', \Illuminate\Support\Str::random(40) . "." . $proofFile->getClientOriginalExtension());
            $proofMime = FileType::safeUploadedMime($proofFile);
            $application->update([
                'payment_proof_path' => $proofPath,
                'payment_proof_uploaded_at' => now(),
                'proof_original_name' => $proofFile->getClientOriginalName(),
                'proof_mime' => $proofMime,
            ]);
            \App\Support\PaymentProofMirror::mirror(
                $application,
                $proofPath,
                'proof_of_payment',
                $proofFile->getClientOriginalName(),
                $proofMime,
                $proofFile->getSize() ?: null
            );
        }

        if ($hasPayment && $request->hasFile('payment_waiver_file')) {
            $request->validate(['payment_waiver_file' => ['file', 'extensions:pdf,jpg,jpeg,png', 'max:10240']]);
            $waiverFile = $request->file('payment_waiver_file');
            $waiverPath = $this->storePublicFile($waiverFile, 'waivers', \Illuminate\Support\Str::random(40) . "." . $waiverFile->getClientOriginalExtension());
            $waiverMime = FileType::safeUploadedMime($waiverFile);
            $application->update([
                'waiver_path' => $waiverPath,
                'waiver_status' => 'submitted',
                'waiver_original_name' => $waiverFile->getClientOriginalName(),
                'waiver_mime' => $waiverMime,
                'payment_submission_method' => 'waiver',
            ]);
            \App\Support\PaymentProofMirror::mirror(
                $application,
                $waiverPath,
                'payment_waiver',
                $waiverFile->getClientOriginalName(),
                $waiverMime,
                $waiverFile->getSize() ?: null
            );
        }

        // Certificate validation disabled - accepting all valid file types (PDF/JPG/PNG)
        // Re-enable and tune validation logic if stricter checks are needed in the future
        // if ($request->hasFile('educational_certificate')) {
        //     $certFile = $request->file('educational_certificate');
        //     $firstName = trim((string) ($formData['first_name'] ?? ''));
        //     $surname = trim((string) ($formData['surname'] ?? ''));
        //     $fullName = trim((string) ($formData['first_name'] ?? '') . ' ' . ($formData['surname'] ?? ''));
        //
        //     if (!$this->validateEducationalCertificate($certFile, $fullName, $firstName, $surname)) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'The uploaded educational certificate could not be verified as a valid certificate. Please upload a clear image or PDF of your academic/professional certificate.',
        //         ], 422);
        //     }
        // }

        // Upload documents to ApplicationDocument
        $this->saveDraftDocuments($request, $application, [
            'passport_photo',
            'id_scan',
            'employment_letter',
            'reference_letter',
            'educational_certificate',
            'passport_biodata_page',
            'clearance_letter',
        ], 'pending', true);

        return response()->json([
            'success'   => true,
            'message'   => 'Application submitted successfully',
            'reference' => $application->reference,
            'application_id' => $application->id,
            'id' => $application->id,
        ]);
    }

    public function renewalForm(Request $request)
    {
        $user = Auth::user();
        $ap5Type = 'renewal';

        $drafts = Application::where('applicant_user_id', $user->id)
            ->where('is_draft', true)
            ->where('application_type', 'accreditation')
            ->where('request_type', 'renewal')
            ->orderByDesc('created_at')
            ->get();

        $draft = null;
        if ($request->filled('draft')) {
            $draft = $drafts->firstWhere('reference', $request->input('draft'));
        }

        return view('portal.accreditation.renewals', compact('drafts', 'draft', 'ap5Type'));
    }

    public function replacementForm(Request $request)
    {
        $user = Auth::user();
        $ap5Type = 'replacement';

        $drafts = Application::where('applicant_user_id', $user->id)
            ->where('is_draft', true)
            ->where('application_type', 'accreditation')
            ->where('request_type', 'replacement')
            ->orderByDesc('created_at')
            ->get();

        $draft = null;
        if ($request->filled('draft')) {
            $draft = $drafts->firstWhere('reference', $request->input('draft'));
        }

        return view('portal.accreditation.renewals', compact('drafts', 'draft', 'ap5Type'));
    }

    public function renewals(Request $request)
    {
        $user = Auth::user();

        $drafts = Application::where('applicant_user_id', $user->id)
            ->where('is_draft', true)
            ->where('application_type', 'accreditation')
            ->whereIn('request_type', ['renewal', 'replacement'])
            ->orderByDesc('created_at')
            ->get();

        $draft = null;
        if ($request->filled('draft')) {
            $draft = $drafts->firstWhere('reference', $request->input('draft'));
        }

        $ap5Type = null;
        return view('portal.accreditation.renewals', compact('drafts', 'draft', 'ap5Type'));
    }

    /**
     * AP5 Draft: allow multiple drafts per user.
     * Every click creates a new draft record (no overwrite).
     */
    public function saveDraftAp5(Request $request)
    {
        $this->ensureWindowOpen('accreditation');
        $user = Auth::user();

        $validated = $request->validate([
            'request_type' => 'required|in:renewal,replacement',
            'practitioner_type' => 'nullable|in:employed,freelancer',
            'draft_reference' => 'nullable|string|max:64',
            'current_step' => 'nullable|integer|min:1|max:4',
            'declaration_confirmed' => 'nullable|in:1',
 
             'renewal_employer_letter'     => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
             'replacement_affidavit'       => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
             'replacement_employer_letter' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
             'replacement_police_report'   => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Store all posted inputs (excluding files + _token) into form_data
        $formData = $request->except([
            '_token',
            'renewal_employer_letter',
            'replacement_affidavit',
            'replacement_employer_letter',
            'replacement_police_report',
        ]);

        $reference = 'DRAFT-AP5-' . now()->format('Y') . '-' . Str::upper(Str::random(6));

        $draft = Application::create([
            'reference'         => $reference,
            'applicant_user_id' => $user->id,
            'application_type'  => 'accreditation',
            'request_type'      => $validated['request_type'],
            'journalist_scope'  => $user->profile_data['journalist_scope'] ?? ($formData['journalist_scope'] ?? 'local'),
            'collection_region' => $formData['collection_region'] ?? 'harare',
            'form_data'         => $formData,
            'is_draft'          => true,
            'status'            => Application::DRAFT,
        ]);

        $this->saveDraftDocuments($request, $draft, [
            'renewal_employer_letter',
            'replacement_affidavit',
            'replacement_employer_letter',
            'replacement_police_report',
        ], 'draft', false);

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully.',
            'reference' => $draft->reference,
        ]);
    }

    public function lookupPractitioner(Request $request)
    {
        $request->validate([
            'search_type' => 'required|in:national_id,accreditation_number',
            'search_value' => 'required|string|max:120',
        ]);

        $type = $request->input('search_type');
        $value = trim($request->input('search_value'));
        $user = Auth::user();

        $query = Application::where('applicant_user_id', $user->id)
            ->where('request_type', 'new')
            ->where('is_draft', false)
            ->whereNotNull('submitted_at');

        if ($type === 'national_id') {
            $query->whereRaw("form_data->>'national_reg_no' LIKE ?", ["%{$value}%"]);
        } else {
            $query->where('reference', 'LIKE', "%{$value}%");
        }

        $app = $query->orderByDesc('submitted_at')->first();

        if (!$app) {
            return response()->json(['found' => false, 'message' => 'No matching accreditation record found.']);
        }

        $fd = $app->form_data ?? [];

        return response()->json([
            'found' => true,
            'data' => [
                'surname' => $fd['surname'] ?? $fd['last_name'] ?? '',
                'first_name' => $fd['first_name'] ?? '',
                'other_names' => $fd['other_names'] ?? '',
                'gender' => $fd['gender'] ?? '',
                'dob' => $fd['dob'] ?? '',
                'nationality' => $fd['nationality'] ?? 'Zimbabwean',
                'national_id' => $fd['national_reg_no'] ?? $fd['id_number'] ?? '',
                'passport_number' => $fd['passport_no'] ?? '',
                'accreditation_number' => $app->reference,
                'phone' => $fd['phone'] ?? '',
                'phone_country_code' => $fd['phone_country_code'] ?? '+263',
                'email' => $fd['email'] ?? $user->email,
                'address' => $fd['address'] ?? '',
                'employment_type' => $fd['employment_type'] ?? '',
                'employer_name' => $fd['employer_name'] ?? '',
            ],
        ]);
    }

    /**
     * Submit AP5 (Renewal/Replacement) for review.
     */
    public function submitAp5(Request $request)
    {
        $this->ensureWindowOpen('accreditation');
        $user = Auth::user();

        $validated = $request->validate([
            'request_type' => 'required|in:renewal,replacement',
            'practitioner_type' => 'required_if:request_type,renewal|in:employed,freelancer',
            'current_step' => 'nullable|integer|min:1|max:4',
            'surname' => 'required|string|max:120',
            'first_name' => 'required|string|max:120',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'nationality' => 'required|string|max:120',
            'national_id' => 'nullable|string|max:120',
            'passport_number' => 'nullable|string|max:120',
            'id_or_passport' => 'nullable|string|max:120',
            'accreditation_number' => 'required|string|max:120',

            // docs required based on type
            'renewal_employer_letter'     => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'replacement_affidavit'       => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'replacement_employer_letter' => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'replacement_police_report'   => 'nullable|file|extensions:pdf,jpg,jpeg,png|max:5120',
            'replacement_reason'          => 'nullable|in:lost,damaged,stolen',
        ]);

        // enforce doc rules
        if ($validated['request_type'] === 'renewal') {
            $isEmployed = ($validated['practitioner_type'] ?? 'employed') === 'employed';
            if ($isEmployed && !$request->hasFile('renewal_employer_letter')) {
                return response()->json(['success' => false, 'message' => 'Employer Letter is required for employed renewal.'], 422);
            }
        }
        if ($validated['request_type'] === 'replacement') {
            if (!$request->hasFile('replacement_affidavit') || !$request->hasFile('replacement_employer_letter')) {
                return response()->json(['success' => false, 'message' => 'Affidavit and Employer Letter are required for replacement.'], 422);
            }
            if (($validated['replacement_reason'] ?? null) === 'stolen' && !$request->hasFile('replacement_police_report')) {
                return response()->json(['success' => false, 'message' => 'Police Report is required for stolen replacement.'], 422);
            }
        }

        $formData = $request->except([
            '_token',
            'renewal_employer_letter',
            'replacement_affidavit',
            'replacement_employer_letter',
            'replacement_police_report',
        ]);

        $draftRef = $request->input('draft_reference');
        $draft = null;

        if ($draftRef) {
            $draft = Application::where('reference', $draftRef)
                ->where('applicant_user_id', $user->id)
                ->where('is_draft', true)
                ->where('application_type', 'accreditation')
                ->first();
        }

        $reference = 'AP5-' . now()->format('Y') . '-' . Str::upper(Str::random(6));

        if ($draft) {
            // Convert draft into a submitted application (removes it from draft lists)
            $draft->reference = $reference;
            $draft->request_type = $validated['request_type'];
            $draft->journalist_scope = $user->profile_data['journalist_scope'] ?? ($draft->journalist_scope ?? 'local');
            $draft->collection_region = $formData['collection_region'] ?? ($draft->collection_region ?? 'harare');
            $draft->form_data = $formData;
            $draft->is_draft = false;
            $draft->status = Application::SUBMITTED;
            $draft->current_stage = Application::ACCOUNTS_REVIEW;
            $draft->submitted_at = now();
            $draft->save();

            $app = $draft;
        } else {
            $app = Application::create([
                'reference'         => $reference,
                'applicant_user_id' => $user->id,
                'application_type'  => 'accreditation',
                'request_type'      => $validated['request_type'],
                'journalist_scope'  => $user->profile_data['journalist_scope'] ?? 'local',
                'collection_region' => $formData['collection_region'] ?? 'harare',
                'form_data'         => $formData,
                'is_draft'          => false,
                'status'            => Application::SUBMITTED,
                'current_stage'     => Application::ACCOUNTS_REVIEW,
                'submitted_at'      => now(),
            ]);
        }

        $this->saveDraftDocuments($request, $app, [
            'renewal_employer_letter',
            'replacement_affidavit',
            'replacement_employer_letter',
            'replacement_police_report',
        ], 'pending', true);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'reference' => $app->reference,
        ]);
    }

    public function payments()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $payments = \App\Models\Payment::where('payer_user_id', $user->id)
            ->with('application')
            ->orderByDesc('created_at')
            ->get();

        return view('portal.accreditation.payments', compact('payments'));
    }

    public function notices()
    {
        $notices = \App\Models\Notice::where('is_published', true)
            ->whereIn('target_portal', ['journalist', 'both'])
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $events = \App\Models\Event::where('is_published', true)
            ->whereIn('target_portal', ['journalist', 'both'])
            ->orderBy('starts_at')
            ->limit(20)
            ->get();

        return view('portal.accreditation.notices', compact('notices', 'events'));
    }

    public function lookupAccreditation(string $accreditationNumber)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $record = null;
        if (class_exists(\App\Models\AccreditationRecord::class) && \Illuminate\Support\Facades\Schema::hasTable('accreditation_records')) {
            $record = \App\Models\AccreditationRecord::where('accreditation_no', $accreditationNumber)
                ->orWhere('certificate_no', $accreditationNumber)
                ->first();
        }

        if (!$record) {
            return response()->json(['found' => false, 'message' => 'No record found for this accreditation number.']);
        }

        return response()->json([
            'found' => true,
            'record' => [
                'accreditation_no' => $record->accreditation_no ?? $record->certificate_no,
                'holder_name' => $record->holder?->name ?? $record->holder_name ?? '',
                'category' => $record->category ?? '',
                'issued_at' => optional($record->issued_at)->format('d M Y'),
                'expires_at' => optional($record->expires_at)->format('d M Y'),
                'status' => $record->status ?? '',
                'media_house' => $record->media_house_name ?? '',
            ],
        ]);
    }

    public function acknowledgeReminder(Request $request, $reminderId)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $reminder = \App\Models\Reminder::where('id', $reminderId)
            ->where(function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('target_type', 'media_practitioner')
                        ->where('target_id', $user->id);
                })->orWhere('target_type', 'bulk');
            })
            ->firstOrFail();

        \App\Models\ReminderRead::updateOrCreate(
            ['reminder_id' => $reminder->id, 'user_id' => $user->id],
            ['read_at' => now(), 'acknowledged_at' => now()]
        );

        \App\Services\ActivityLogger::log('reminder_acknowledged', null, null, null, [
            'reminder_id' => $reminder->id,
            'actor_user_id' => $user->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Reminder acknowledged.');
    }

    public function howto()
    {
        return view('portal.accreditation.howto');
    }

    public function requirements()
    {
        return view('portal.accreditation.requirements');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('portal.accreditation.profile', compact('user'));
    }

    public function communication()
    {
        return view('portal.accreditation.communication');
    }

    public function settings()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        return view('portal.accreditation.settings');
    }

    public function deleteDraft(Application $application)
    {
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless($application->is_draft, 403);

        $application->documents()->each(function ($doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        });

        $application->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Draft deleted successfully.'
        ]);
    }

    public function deleteDocument(ApplicationDocument $document)
    {
        $userId = Auth::id();
        $application = $document->application;
        abort_unless($userId && $application && $application->applicant_user_id === $userId, 403);

        $allowedStatuses = array_merge(self::applicantEditableStatuses(), ['draft']);
        $canDelete = $application->is_draft || in_array($application->status, $allowedStatuses, true);
        abort_unless($canDelete, 403, 'You can only remove documents from editable applications.');

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }
        if ($document->thumbnail_path) {
            Storage::disk('public')->delete($document->thumbnail_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document removed successfully.',
        ]);
    }

    public function validateCertificate(Request $request)
    {
        // CERTIFICATE VALIDATION COMPLETELY DISABLED
        // Accept all uploaded files without any validation
        // Only basic Laravel file validation remains (handled by the form request)
        
        \Log::info('Certificate validation called', [
            'has_file' => $request->hasFile('educational_certificate'),
            'file_name' => $request->hasFile('educational_certificate') ? $request->file('educational_certificate')->getClientOriginalName() : null,
            'file_size' => $request->hasFile('educational_certificate') ? $request->file('educational_certificate')->getSize() : null,
            'mime_type' => $request->hasFile('educational_certificate') ? FileType::safeUploadedMime($request->file('educational_certificate')) : null,
        ]);
        
        try {
            $request->validate([
                'educational_certificate' => 'required|file|extensions:pdf,jpg,jpeg,png|max:10240',
                'first_name' => 'nullable|string|max:120',
                'surname' => 'nullable|string|max:120',
                'formal_qualifications' => 'nullable|string|max:500',
            ]);
            
            \Log::info('Certificate validation: File passed Laravel validation');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation failure but still accept the file
            \Log::warning('Certificate validation: Laravel validation failed but accepting anyway', [
                'errors' => $e->errors(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Certificate accepted.',
            ]);
        }

        // Always return success - no content validation
        \Log::info('Certificate validation: Returning success');
        
        return response()->json([
            'success' => true,
            'message' => 'Certificate accepted.',
        ]);

        /* ORIGINAL VALIDATION CODE - DISABLED FOR NOW
        
        $applicantName = trim(($request->input('first_name', '') . ' ' . $request->input('surname', '')));
        $applicantFirstName = $request->input('first_name', '');
        $applicantSurname = $request->input('surname', '');

        $document = new ApplicationDocument();
        $document->file_path = $file->storeAs('temp/validation', \Illuminate\Support\Str::random(40) . "." . $file->getClientOriginalExtension(), 'public');
        $document->original_name = $file->getClientOriginalName();
        $document->mime = FileType::safeUploadedMime($file);
        $document->size = $file->getSize();
        $document->file_hash = hash_file('sha256', $file->getRealPath()) ?: null;

        try {
            $classificationService = app(\App\Services\DocumentClassificationService::class);
            $disk = Storage::disk('public');
            $extractedText = $classificationService->extractTextFromFile($disk, $document->file_path);
            $document->extracted_text = $extractedText;

            $textLength = strlen($extractedText);
            $document->ocr_confidence = $textLength > 200 ? 0.9 : ($textLength > 100 ? 0.75 : ($textLength > 50 ? 0.6 : 0.4));

            // Only classify if we have enough text to work with; otherwise ACCEPT the file
            if ($textLength >= 50) {
                $classification = $classificationService->classifyDocument($document, 'graduation_certificate');
                $detectedType = $classification['detected_document_type'] ?? 'unknown';

                // ONLY reject if we are VERY confident it is a completely different document type
                // This list should only include documents that are DEFINITELY not certificates
                $definitelyNotCertificates = ['national_id', 'passport', 'drivers_licence'];
                
                // Only reject with high confidence
                $confidence = $classification['confidence'] ?? 0;
                if (in_array($detectedType, $definitelyNotCertificates, true) && $confidence > 0.7) {
                    Storage::disk('public')->delete($document->file_path);

                    return response()->json([
                        'success' => false,
                        'message' => "This document appears to be a '{$detectedType}', not a certificate. Please upload your academic or professional certificate.",
                        'detected_type' => $detectedType,
                    ]);
                }
            }

            // If we reach here, accept the document
            // (either not enough text to classify, or not confidently a non-certificate type)
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }

            return response()->json([
                'success' => true,
                'message' => 'Certificate validated successfully.',
            ]);

        } catch (\Exception $e) {
            // On any error, ACCEPT the document rather than rejecting
            // Log the error but don't block the user
            \Log::warning('Certificate validation error: ' . $e->getMessage());
            
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }

            return response()->json([
                'success' => true,
                'message' => 'Certificate accepted (validation skipped due to processing error).',
            ]);
        }
        
        */
    }

    private function getBlockingCertificateIssues(array $result): array
    {
        $issues = [];

        $ownership = $result['ownership_check'] ?? [];
        if (empty($ownership)) {
            return $issues;
        }

        if (!($ownership['likely_applicant_certificate'] ?? true)) {
            $confidence = $ownership['ownership_confidence'] ?? 0;
            if ($confidence < 55) {
                $issues[] = 'This certificate does not appear to belong to the applicant. Please upload your own certificate.';
            }
        }

        $recipientName = strtolower(trim((string) ($ownership['recipient_name_on_certificate'] ?? '')));
        $extraction = $result['extraction'] ?? [];
        if (empty($recipientName) && empty($extraction['recipient_name'] ?? null)) {
            $issues[] = 'Could not identify the recipient name on this certificate. Please upload a clearer image.';
        }

        if (empty($extraction['institution_name'] ?? null)) {
            $issues[] = 'Could not identify the issuing institution on this certificate.';
        }

        if (empty($extraction['award_date'] ?? null)) {
            $issues[] = 'Could not identify the award date on this certificate.';
        }

        return $issues;
    }

    public function withdraw(Application $application)
    {
        $userId = Auth::id();
        abort_unless($userId && $application->applicant_user_id === $userId, 403);
        abort_unless(!$application->is_draft, 403);
        
        // Only allow withdrawal before approval/rejection logic
        $allowed = [
            Application::SUBMITTED, 
            Application::OFFICER_REVIEW, 
            Application::REGISTRAR_REVIEW, 
            Application::ACCOUNTS_REVIEW
        ];
        abort_unless(in_array($application->status, $allowed), 403);

        $application->update([
            'status' => Application::WITHDRAWN,
            'is_draft' => true, // Move back to draft so they can edit/resubmit or delete
        ]);
        
        return response()->json([
            'success' => true, 
            'message' => 'Application withdrawn and moved back to drafts.'
        ]);
    }

    private function analyzeCertificateContentMatch(
        string $extractedText,
        string $applicantFullName,
        string $applicantFirstName,
        string $applicantSurname,
        string $expectedQualification
    ): array {
        $textLower = strtolower($extractedText);

        $signals = [
            'name_found' => false,
            'name_context' => '',
            'surname_found' => false,
            'first_name_found' => false,
            'qualification_found' => false,
            'qualification_keywords' => [],
            'certificate_indicators' => [],
            'unrelated_certificate_risk' => 0,
        ];

        $fullNameParts = array_values(array_filter(explode(' ', $applicantFullName)));
        $nameContextLength = 80;

        foreach ($fullNameParts as $part) {
            if (strlen($part) < 2) continue;
            $positions = [];
            $offset = 0;
            while (($pos = stripos($textLower, $part, $offset)) !== false) {
                $positions[] = $pos;
                $offset = $pos + 1;
            }
            foreach ($positions as $pos) {
                $start = max(0, $pos - $nameContextLength);
                $context = trim(substr($extractedText, $start, $nameContextLength * 2));
                $signals['name_context'] = $context;
                if (stripos($context, 'name') !== false ||
                    preg_match('/(?:awarded|presented|granted|certified|conferred|issued to|this cert)/i', $context)) {
                    $signals['name_found'] = true;
                    break 2;
                }
            }
        }

        if (!empty($applicantSurname) && stripos($textLower, $applicantSurname) !== false) {
            $signals['surname_found'] = true;
        }
        if (!empty($applicantFirstName) && stripos($textLower, $applicantFirstName) !== false) {
            $signals['first_name_found'] = true;
        }

        $qualKeywords = [
            'degree', 'diploma', 'certificate', 'bachelor', 'master', 'phd', 'doctorate',
            'honours', 'honors', 'bsc', 'ba', 'msc', 'ma', 'mba', 'llb', 'bcom', 'bed',
            'graduated', 'awarded', 'conferred', 'completed', 'qualification',
        ];
        foreach ($qualKeywords as $kw) {
            if (stripos($textLower, $kw) !== false) {
                $signals['qualification_found'] = true;
                $signals['qualification_keywords'][] = $kw;
            }
        }

        $certIndicators = [
            'this is to certify', 'this certifies that', 'this is awarded to',
            'this certificate is awarded', 'hereby certify', 'we hereby certify',
            'has successfully completed', 'has been awarded', 'is conferred',
            'bear witness', 'testimony', 'commencement', 'convocation',
        ];
        foreach ($certIndicators as $ind) {
            if (stripos($textLower, $ind) !== false) {
                $signals['certificate_indicators'][] = $ind;
            }
        }

        $unrelatedRisk = 0;
        $riskReasons = [];

        if (!$signals['name_found'] && !$signals['surname_found']) {
            $unrelatedRisk += 40;
            $riskReasons[] = 'Applicant name not found on document';
        }
        if ($signals['name_found'] && !$signals['surname_found']) {
            $unrelatedRisk += 10;
            $riskReasons[] = 'Only partial name match';
        }
        if (!$signals['qualification_found']) {
            $unrelatedRisk += 15;
            $riskReasons[] = 'No qualification keywords found';
        }
        if (count($signals['certificate_indicators']) === 0) {
            $unrelatedRisk += 20;
            $riskReasons[] = 'No certificate-style phrasing detected';
        }
        if (!empty($expectedQualification) && $signals['qualification_found']) {
            $expectedLower = strtolower($expectedQualification);
            $textSample = substr($extractedText, 0, 2000);
            $overlap = 0;
            foreach (explode(' ', $expectedLower) as $word) {
                if (strlen($word) > 3 && stripos($textSample, $word) !== false) {
                    $overlap++;
                }
            }
            if ($overlap === 0) {
                $unrelatedRisk += 15;
                $riskReasons[] = 'Expected qualification keywords not found in text';
            }
        }

        $unrelatedRisk = min(100, $unrelatedRisk);

        $likelyApplicantCertificate = $unrelatedRisk < 30;

        $signals['unrelated_certificate_risk'] = $unrelatedRisk;
        $signals['likely_applicant_certificate'] = $likelyApplicantCertificate;
        $signals['risk_reasons'] = $riskReasons;

        $matchConfidence = 0;
        if ($signals['name_found']) $matchConfidence += 35;
        if ($signals['surname_found']) $matchConfidence += 15;
        if ($signals['first_name_found']) $matchConfidence += 10;
        if ($signals['qualification_found']) $matchConfidence += 20;
        if (count($signals['certificate_indicators']) > 0) $matchConfidence += 20;
        $signals['match_confidence'] = min(100, $matchConfidence);

        return $signals;
    }

    private function ensureWindowOpen(string $windowKey): void
    {
        $win = (array) MasterSettings::get("portal_specific.public.{$windowKey}_window", ['open' => null, 'close' => null]);
        $open = $win['open'] ?? null;
        $close = $win['close'] ?? null;

        if (!$open && !$close) {
            return; // not configured = always open
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
