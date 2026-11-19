<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DocumentCheck;
use App\Models\DocumentClassification;
use App\Models\IntakeDecision;
use App\Models\IntakeAuditLog;
use Illuminate\Support\Facades\DB;

class PortalIntakeAutomationService
{
    private DocumentSecurityService $securityService;
    private DocumentClassificationService $classificationService;
    private IdentityVerificationService $identityService;
    private PassportPhotoQualityService $photoQualityService;
    private PaymentVerificationService $paymentService;
    private PastWorkVerificationService $pastWorkService;
    private ApplicationCategoryAssignmentService $categoryService;

    public function __construct(
        DocumentSecurityService $securityService,
        DocumentClassificationService $classificationService,
        IdentityVerificationService $identityService,
        PassportPhotoQualityService $photoQualityService,
        PaymentVerificationService $paymentService,
        PastWorkVerificationService $pastWorkService,
        ApplicationCategoryAssignmentService $categoryService,
    ) {
        $this->securityService = $securityService;
        $this->classificationService = $classificationService;
        $this->identityService = $identityService;
        $this->photoQualityService = $photoQualityService;
        $this->paymentService = $paymentService;
        $this->pastWorkService = $pastWorkService;
        $this->categoryService = $categoryService;
    }

    /**
     * Run intake automation on an application.
     *
     * @param Application $application
     * @param array $options (commit: bool, auto_approve: bool, system_user_id: int|null)
     * @return IntakeDecision
     */
    public function processApplication(Application $application, array $options = []): array
    {
        $commit = $options['commit'] ?? false;
        $autoApprove = $options['auto_approve'] ?? true;
        $systemUserId = $options['system_user_id'] ?? null;

        $results = [
            'document_security' => [],
            'document_classifications' => [],
            'identity_verification' => null,
            'certificate_verification' => null,
            'photo_quality' => null,
            'payment_verification' => null,
            'past_work_verification' => null,
            'category_assignment' => null,
        ];

        try {
            DB::beginTransaction();

            // Step 1: Validate required documents exist
            $documentValidation = $this->validateRequiredDocuments($application);
            if (!$documentValidation['valid']) {
                $decision = $this->makeDecision(
                    $application,
                    'correction_needed',
                    0,
                    ['error' => 'Missing required documents'],
                    [],
                    $systemUserId,
                    $commit
                );
                DB::commit();
                return ['decision' => $decision, 'results' => $results];
            }

            // Category assignment only depends on application metadata and
            // document records, so calculate it before byte-level checks that
            // may return early when source files are unavailable.
            $categoryResult = $this->categoryService->assignCategory($application);
            $results['category_assignment'] = $categoryResult;

            // Step 2: Security checks on all documents
            $documents = $application->documents()->get();
            foreach ($documents as $doc) {
                $securityResult = $this->securityService->scanDocument($doc, $application);
                $results['document_security'][$doc->id] = $securityResult;

                if ($commit) {
                    DocumentCheck::create([
                        'application_id' => $application->id,
                        'document_id' => $doc->id,
                        'check_type' => 'security_check',
                        'status' => $securityResult['status'],
                        'details' => $securityResult,
                        'confidence_score' => $securityResult['confidence_score'],
                    ]);
                }

                if ($securityResult['status'] === 'fail') {
                    $decision = $this->makeDecision(
                        $application,
                        'blocked',
                        0,
                        ['error' => 'Document security check failed'],
                        $results,
                        $systemUserId,
                        $commit
                    );
                    DB::commit();
                    return ['decision' => $decision, 'results' => $results, 'failed_at' => 'security_check'];
                }
            }

            // Step 3: Document classification and OCR
            $classificationScores = [];
            foreach ($documents as $doc) {
                $classResult = $this->classificationService->classifyDocument(
                    $doc,
                    $doc->doc_type
                );
                $results['document_classifications'][$doc->id] = $classResult;
                $classificationScores[$doc->id] = $classResult['confidence_score'];

                if ($commit) {
                    DocumentClassification::create([
                        'application_id' => $application->id,
                        'document_id' => $doc->id,
                        'expected_document_type' => $classResult['expected_document_type'],
                        'detected_document_type' => $classResult['detected_document_type'],
                        'confidence_score' => $classResult['confidence_score'],
                        'extracted_text_excerpt' => $classResult['extracted_text_excerpt'],
                        'matched_keywords' => $classResult['matched_keywords'],
                        'missing_keywords' => $classResult['missing_keywords'],
                        'warnings' => $classResult['warnings'],
                        'blocking_errors' => $classResult['blocking_errors'],
                        'recommendation' => $classResult['recommendation'],
                    ]);
                }

                // If classification failed, flag for review
                if ($classResult['recommendation'] === 'fail') {
                    $decision = $this->makeDecision(
                        $application,
                        'flagged_for_review',
                        50,
                        ['reason' => 'Document classification failed'],
                        $results,
                        $systemUserId,
                        $commit
                    );
                    DB::commit();
                    return ['decision' => $decision, 'results' => $results, 'failed_at' => 'document_classification'];
                }
            }

            // Step 4: Identity verification
            $idResult = $this->identityService->verifyIdentity($application);
            $results['identity_verification'] = $idResult;

            // Step 4.5: Certificate verification
            $certResult = $this->identityService->verifyCertificate($application);
            $results['certificate_verification'] = $certResult;

            // Step 5: Passport photo quality check
            $photoDoc = $application->documents()
                ->where('doc_type', 'passport_photo')
                ->first();

            if ($photoDoc) {
                $photoResult = $this->photoQualityService->checkPhotoQuality($photoDoc);
                $results['photo_quality'] = $photoResult;
            }

            // Step 6: Payment verification
            $paymentResult = $this->paymentService->verifyPayment($application);
            $results['payment_verification'] = $paymentResult;

            // Step 7: Past work verification
            $pastWorkResult = $this->pastWorkService->verifyPastWork($application);
            $results['past_work_verification'] = $pastWorkResult;

            // Step 8: Category assignment
            $categoryResult = $results['category_assignment'];

            // Step 9: Calculate overall score
            $scores = $this->calculateOverallScore($results);

            // Step 10: Make final decision
            $matchedRules = $this->extractMatchedRules($results, $scores);
            $decision = $this->makeDecision(
                $application,
                $scores['decision'],
                $scores['overall_score'],
                $matchedRules,
                $scores,
                $systemUserId,
                $commit && $autoApprove
            );

            if ($commit) {
                IntakeDecision::updateOrCreate(
                    ['application_id' => $application->id],
                    [
                        'overall_score' => $scores['overall_score'],
                        'decision' => $scores['decision'],
                        'matched_rules' => $matchedRules,
                        'check_results' => $scores['check_scores'],
                        'category_recommendation' => $categoryResult['assigned_category'],
                        'category_confidence_score' => $categoryResult['confidence_score'],
                        'reason' => ['decision' => $scores['decision'], 'threshold' => $scores['threshold']],
                        'created_by' => $systemUserId,
                        'document_ids_checked' => $documents->pluck('id')->toArray(),
                        'documents_checked_count' => $documents->count(),
                    ]
                );
            }

            DB::commit();

            return ['decision' => $decision, 'results' => $results, 'scores' => $scores];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Intake automation error for application ' . $application->id . ': ' . $e->getMessage());

            return [
                'decision' => ['status' => 'error', 'error' => $e->getMessage()],
                'results' => $results,
                'error' => true,
            ];
        }
    }

    /**
     * Validate that all required documents are present.
     */
    private function validateRequiredDocuments(Application $application): array
    {
        $docTypes = $application->documents()->pluck('doc_type')->toArray();

        // Media-house registrations have their own AP1/AP2/AP4 annexure
        // requirements. Do not apply the practitioner-only passport,
        // graduation-certificate and past-work rules to them.
        if ($application->application_type === 'registration') {
            $required = match ($application->form_type ?? 'AP1') {
                'AP4' => [
                    'rep_office_letter',
                    'parent_certificate',
                    'rep_office_registration',
                    'journalist_list',
                ],
                'AP2' => [
                    'certificate_of_incorporation',
                    'mission_statement',
                    'code_of_ethics',
                    'code_of_conduct',
                ],
                default => [
                    'directors_ids',
                    'mission_statement',
                    'code_of_ethics',
                    'code_of_conduct',
                    'style_book',
                    'certificate_of_incorporation',
                    'cr7_cr14',
                    'editorial_charter',
                    'balance_sheet_3yr',
                    'memorandum_of_association',
                    'market_analysis',
                    'cashflow_projection_3yr',
                ],
            };

            return [
                'valid' => empty(array_diff($required, $docTypes)),
                'required' => $required,
                'provided' => $docTypes,
                'missing' => array_values(array_diff($required, $docTypes)),
            ];
        }

        // Upload handlers use the current AP3 field names (for example,
        // id_scan and educational_certificate_1), while older automation
        // used the legacy names (national_id and graduation_certificate).
        // Match by document family so a correctly uploaded file is not
        // incorrectly reported as missing.
        $hasDoc = function (array $aliases) use ($docTypes): bool {
            foreach ($docTypes as $type) {
                foreach ($aliases as $alias) {
                    if ($type === $alias || str_starts_with($type, $alias . '_')) {
                        return true;
                    }
                }
            }
            return false;
        };

        $required = ['passport_photo', 'graduation_certificate'];
        $missing = [];
        if (!$hasDoc(['passport_photo'])) {
            $missing[] = 'passport_photo';
        }
        if (!$hasDoc(['graduation_certificate', 'educational_certificate'])) {
            $missing[] = 'graduation_certificate';
        }

        $formData = is_array($application->form_data)
            ? $application->form_data
            : (json_decode($application->form_data ?? '{}', true) ?: []);
        $applicantType = $application->applicant_type
            ?: (($formData['journalist_scope'] ?? '') === 'foreign' ? 'international' : 'local');

        if ($applicantType === 'local') {
            $required[] = 'national_id';
            if (!$hasDoc(['national_id', 'id_scan'])) {
                $missing[] = 'national_id';
            }
        } elseif ($applicantType === 'international') {
            $required[] = 'passport';
            $required[] = 'clearance_letter';
            if (!$hasDoc(['passport', 'passport_biodata_page'])) {
                $missing[] = 'passport';
            }
            if (!$hasDoc(['clearance_letter'])) {
                $missing[] = 'clearance_letter';
            }
        }

        $employmentStatus = $application->employment_status ?: ($formData['employment_type'] ?? null);
        if ($employmentStatus === 'employed') {
            $required[] = 'employment_letter';
            if (!$hasDoc(['employment_letter'])) {
                $missing[] = 'employment_letter';
            }
        }

        $hasPastWorkLink = !empty($application->past_work_link)
            || collect($formData)->keys()->contains(fn ($key) => str_starts_with($key, 'past_work_link_url_'));
        $required[] = 'past_work';
        if (!$hasPastWorkLink && !$hasDoc(['past_work', 'past_work_sample'])) {
            $missing[] = 'past_work';
        }

        return [
            'valid' => empty($missing),
            'required' => $required,
            'provided' => $docTypes,
            'missing' => $missing,
        ];
    }

    /**
     * Calculate overall score based on all checks.
     */
    private function calculateOverallScore(array $results): array
    {
        $scores = [];
        $weights = [
            'identity_verification' => 0.25,
            'certificate_verification' => 0.15,
            'document_classifications' => 0.15,
            'photo_quality' => 0.12,
            'payment_verification' => 0.20,
            'past_work_verification' => 0.13,
        ];

        $weightedTotal = 0;
        $weightSum = 0;

        // Identity verification score
        if ($results['identity_verification']) {
            $idScore = $results['identity_verification']['confidence_score'] ?? 0;
            $scores['identity'] = $idScore;
            $weightedTotal += $idScore * $weights['identity_verification'];
            $weightSum += $weights['identity_verification'];
        }

        // Certificate verification score
        if ($results['certificate_verification']) {
            $certScore = $results['certificate_verification']['confidence_score'] ?? 0;
            $scores['certificate'] = $certScore;
            $weightedTotal += $certScore * $weights['certificate_verification'];
            $weightSum += $weights['certificate_verification'];
        }

        // Document classification average
        if (!empty($results['document_classifications'])) {
            $avgClassification = array_reduce(
                $results['document_classifications'],
                fn($carry, $item) => $carry + ($item['confidence_score'] ?? 0),
                0
            ) / count($results['document_classifications']);
            $scores['document_classification'] = (int) $avgClassification;
            $weightedTotal += $avgClassification * $weights['document_classifications'];
            $weightSum += $weights['document_classifications'];
        }

        // Photo quality score
        if ($results['photo_quality']) {
            $photoScore = $results['photo_quality']['status'] === 'pass' ? 100 : 0;
            $scores['photo_quality'] = $photoScore;
            $weightedTotal += $photoScore * $weights['photo_quality'];
            $weightSum += $weights['photo_quality'];
        }

        // Payment verification score
        if ($results['payment_verification']) {
            $paymentScore = 0;
            if ($results['payment_verification']['status'] === 'pass') {
                $paymentScore = 100;
            } elseif ($results['payment_verification']['status'] === 'flag') {
                $paymentScore = 50;
            }
            $scores['payment'] = $paymentScore;
            $weightedTotal += $paymentScore * $weights['payment_verification'];
            $weightSum += $weights['payment_verification'];
        }

        // Past work verification score
        if ($results['past_work_verification']) {
            $pastWorkScore = 0;
            if ($results['past_work_verification']['status'] === 'pass') {
                $pastWorkScore = $results['past_work_verification']['confidence_score'] ?? 85;
            } elseif ($results['past_work_verification']['status'] === 'flag') {
                $pastWorkScore = 40;
            }
            $scores['past_work'] = $pastWorkScore;
            $weightedTotal += $pastWorkScore * $weights['past_work_verification'];
            $weightSum += $weights['past_work_verification'];
        }

        $overallScore = $weightSum > 0 ? (int) ($weightedTotal / $weightSum) : 0;

        // Determine decision
        $autoPassThreshold = config('intake-automation.confidence_thresholds.auto_pass', 90);
        $manualReviewMin = config('intake-automation.confidence_thresholds.manual_review_min', 60);

        if ($overallScore >= $autoPassThreshold) {
            $decision = 'auto_pass';
        } elseif ($overallScore >= $manualReviewMin) {
            $decision = 'flagged_for_review';
        } else {
            $decision = 'correction_needed';
        }

        return [
            'overall_score' => $overallScore,
            'decision' => $decision,
            'threshold' => $autoPassThreshold,
            'check_scores' => $scores,
        ];
    }

    /**
     * Extract matched rules from results.
     */
    private function extractMatchedRules(array $results, array $scores): array
    {
        $rules = [];

        if ($results['identity_verification']) {
            $rules[] = [
                'rule' => 'identity_verification',
                'status' => $results['identity_verification']['status'],
                'score' => $scores['check_scores']['identity'] ?? 0,
            ];
        }

        if ($results['certificate_verification']) {
            $rules[] = [
                'rule' => 'certificate_verification',
                'status' => $results['certificate_verification']['status'],
                'score' => $scores['check_scores']['certificate'] ?? 0,
            ];
        }

        if ($results['photo_quality']) {
            $rules[] = [
                'rule' => 'photo_quality',
                'status' => $results['photo_quality']['status'],
                'score' => $scores['check_scores']['photo_quality'] ?? 0,
            ];
        }

        if ($results['payment_verification']) {
            $rules[] = [
                'rule' => 'payment_verification',
                'status' => $results['payment_verification']['status'],
                'score' => $scores['check_scores']['payment'] ?? 0,
            ];
        }

        if ($results['past_work_verification']) {
            $rules[] = [
                'rule' => 'past_work_verification',
                'status' => $results['past_work_verification']['status'],
                'score' => $scores['check_scores']['past_work'] ?? 0,
            ];
        }

        return $rules;
    }

    /**
     * Make final decision and update application status.
     */
    private function makeDecision(
        Application $application,
        string $decision,
        int $score,
        array $matchedRules,
        array $checkResults,
        ?int $systemUserId,
        bool $commit
    ): array {
        $newStatus = match ($decision) {
            'auto_pass' => 'intake_auto_passed',
            'flagged_for_review' => 'flagged_for_review',
            'correction_needed' => 'correction_requested',
            'blocked' => 'blocked_for_review',
            default => 'officer_review',
        };

        if ($commit) {
            $oldStatus = $application->status;
            $updates = [
                'status' => $newStatus,
                'last_action_at' => now(),
                'last_action_by' => $systemUserId,
            ];

            // Automated intake must never leave an application in a correction
            // state without telling the applicant what failed. Keep the reason
            // on the application as well as in the audit log so every portal
            // (practitioner and media-house) can display it consistently.
            if ($decision === 'correction_needed') {
                $reason = trim((string) ($matchedRules['error'] ?? ''));
                if ($reason === '') {
                    $reason = 'Please review the application and correct the highlighted information or documents.';
                }
                $updates['current_stage'] = 'applicant_correction';
                $updates['correction_notes'] = $reason;
            }

            $application->update($updates);

            IntakeAuditLog::create([
                'application_id' => $application->id,
                'action' => 'completed_intake_check',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'rules_applied' => $matchedRules,
                'confidence_scores' => $checkResults,
                'reason' => $decision,
                'created_by' => $systemUserId,
            ]);
        }

        return [
            'application_id' => $application->id,
            'decision' => $decision,
            'new_status' => $newStatus,
            'overall_score' => $score,
            'matched_rules' => $matchedRules,
        ];
    }
}
