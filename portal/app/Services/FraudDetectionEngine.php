<?php

namespace App\Services;

use App\Services\Validation\DataNormalizationService;
use App\Services\Validation\DocumentValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FraudDetectionEngine
{
    private DataNormalizationService $normalizationService;
    private DocumentValidationService $documentValidationService;
    
    // Risk scoring thresholds
    private const RISK_LEVELS = [
        'minimal' => ['min' => 0, 'max' => 20],
        'low' => ['min' => 21, 'max' => 50],
        'medium' => ['min' => 51, 'max' => 75],
        'high' => ['min' => 76, 'max' => 90],
        'critical' => ['min' => 91, 'max' => 100]
    ];
    
    // Fraud detection patterns
    private const FRAUD_PATTERNS = [
        'duplicate_identity' => 30,
        'duplicate_document' => 35,
        'name_mismatch' => 25,
        'id_mismatch' => 40,
        'tampering_detected' => 50,
        'unregistered_employer' => 20,
        'company_name_mismatch' => 35,
        'director_mismatch' => 30,
        'foreign_ownership_hidden' => 25,
        'duplicate_media_house' => 40,
        'payment_reversal_after_approval' => 45,
        'multiple_applications_same_device' => 35,
        'prior_rejection' => 30,
        'prior_revocation' => 35,
        'suspicious_upload_pattern' => 25,
        'assignment_inconsistency' => 30,
        'levy_turnover_under_declared' => 40,
        'fake_audited_financials' => 45,
        'insider_manipulation' => 60
    ];
    
    public function __construct(
        DataNormalizationService $normalizationService,
        DocumentValidationService $documentValidationService
    ) {
        $this->normalizationService = $normalizationService;
        $this->documentValidationService = $documentValidationService;
    }
    
    /**
     * Perform comprehensive fraud detection on an application
     * 
     * @param array $formData
     * @param array $documents
     * @param string $applicationType
     * @param int|null $applicationId
     * @return array
     */
    public function detectFraud(array $formData, array $documents, string $applicationType, ?int $applicationId = null): array
    {
        $result = [
            'risk_score' => 0,
            'risk_level' => 'minimal',
            'fraud_flags' => [],
            'duplicate_checks' => [],
            'pattern_analysis' => [],
            'insider_risk_checks' => [],
            'recommendations' => [],
            'block_application' => false,
            'require_manual_review' => false
        ];
        
        // Perform duplicate identity checks
        $duplicateChecks = $this->performDuplicateIdentityChecks($formData, $applicationType);
        $result['duplicate_checks'] = $duplicateChecks;
        
        // Perform document fraud analysis
        $documentAnalysis = $this->analyzeDocumentFraud($documents, $applicationType);
        $result['document_analysis'] = $documentAnalysis;
        
        // Perform pattern analysis
        $patternAnalysis = $this->analyzeFraudPatterns($formData, $documents, $applicationType);
        $result['pattern_analysis'] = $patternAnalysis;
        
        // Perform insider risk checks (if application ID exists)
        if ($applicationId) {
            $insiderChecks = $this->performInsiderRiskChecks($applicationId);
            $result['insider_risk_checks'] = $insiderChecks;
        }
        
        // Calculate overall risk score
        $result['risk_score'] = $this->calculateRiskScore($result);
        
        // Determine risk level
        $result['risk_level'] = $this->determineRiskLevel($result['risk_score']);
        
        // Aggregate fraud flags
        $result['fraud_flags'] = $this->aggregateFraudFlags($result);
        
        // Determine blocking and review requirements
        $result['block_application'] = $result['risk_score'] >= 90;
        $result['require_manual_review'] = $result['risk_score'] >= 50;
        
        // Generate recommendations
        $result['recommendations'] = $this->generateRecommendations($result);
        
        // Log fraud detection results
        $this->logFraudDetection($formData, $result, $applicationType);
        
        return $result;
    }
    
    /**
     * Perform duplicate identity checks
     * 
     * @param array $formData
     * @param string $applicationType
     * @return array
     */
    private function performDuplicateIdentityChecks(array $formData, string $applicationType): array
    {
        $checks = [];
        
        // Check by National ID
        if (!empty($formData['national_id'])) {
            $normalizedId = $this->normalizationService->normalizeNationalId($formData['national_id']);
            
            $duplicate = DB::table('applications')
                ->whereJsonContains('form_data->national_id', $normalizedId)
                ->where('application_type', $applicationType)
                ->whereIn('status', ['draft', 'submitted', 'under_review', 'officer_review'])
                ->first();
            
            if ($duplicate) {
                // Check if names match
                $duplicateName = $duplicate->form_data['surname'] . ' ' . ($duplicate->form_data['first_name'] ?? '');
                $currentName = ($formData['surname'] ?? '') . ' ' . ($formData['first_name'] ?? '');
                $nameComparison = $this->normalizationService->compareNames($duplicateName, $currentName);
                
                $checks[] = [
                    'type' => 'duplicate_national_id',
                    'severity' => $nameComparison['match_status'] === 'hard_stop' ? 'critical' : 'medium',
                    'description' => 'National ID already used in another application',
                    'application_id' => $duplicate->id,
                    'status' => $duplicate->status,
                    'name_match' => $nameComparison['match_status'],
                    'risk_points' => $nameComparison['match_status'] === 'hard_stop' ? 40 : 20
                ];
            }
        }
        
        // Check by passport
        if (!empty($formData['passport_number'])) {
            $normalizedPassport = $this->normalizationService->normalizePassportNumber($formData['passport_number']);
            
            $duplicate = DB::table('applications')
                ->whereJsonContains('form_data->passport_number', $normalizedPassport)
                ->where('application_type', $applicationType)
                ->whereIn('status', ['draft', 'submitted', 'under_review', 'officer_review'])
                ->first();
            
            if ($duplicate) {
                $checks[] = [
                    'type' => 'duplicate_passport',
                    'severity' => 'critical',
                    'description' => 'Passport number already used in another application',
                    'application_id' => $duplicate->id,
                    'status' => $duplicate->status,
                    'risk_points' => 40
                ];
            }
        }
        
        // Check by email
        if (!empty($formData['email'])) {
            $normalizedEmail = $this->normalizationService->normalizeEmail($formData['email']);
            
            $recentApplications = DB::table('applications')
                ->whereJsonContains('form_data->email', $normalizedEmail)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            
            if ($recentApplications > 5) {
                $checks[] = [
                    'type' => 'multiple_applications_email',
                    'severity' => 'medium',
                    'description' => "Multiple applications ({$recentApplications}) from same email address recently",
                    'count' => $recentApplications,
                    'risk_points' => 25
                ];
            }
        }
        
        // Check by phone
        if (!empty($formData['phone'])) {
            $normalizedPhone = $this->normalizationService->normalizePhone($formData['phone']);
            
            $recentApplications = DB::table('applications')
                ->whereJsonContains('form_data->phone', $normalizedPhone)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            
            if ($recentApplications > 3) {
                $checks[] = [
                    'type' => 'multiple_applications_phone',
                    'severity' => 'medium',
                    'description' => "Multiple applications ({$recentApplications}) from same phone number recently",
                    'count' => $recentApplications,
                    'risk_points' => 20
                ];
            }
        }
        
        return $checks;
    }
    
    /**
     * Analyze document fraud
     * 
     * @param array $documents
     * @param string $applicationType
     * @return array
     */
    private function analyzeDocumentFraud(array $documents, string $applicationType): array
    {
        $analysis = [];
        
        foreach ($documents as $docType => $documentData) {
            $validationData = $documentData['validation_data'] ?? null;
            
            if (!$validationData) {
                continue;
            }
            
            // Check for tampering
            if ($validationData['tampering_detected'] ?? false) {
                $analysis[] = [
                    'document_type' => $docType,
                    'type' => 'document_tampering',
                    'severity' => 'critical',
                    'description' => 'Potential tampering detected on document',
                    'details' => $validationData['tampering_details'] ?? [],
                    'risk_points' => self::FRAUD_PATTERNS['tampering_detected']
                ];
            }
            
            // Check for duplicate document hash
            if (!empty($validationData['sha256'])) {
                $duplicate = DB::table('application_documents')
                    ->where('sha256', $validationData['sha256'])
                    ->where('doc_type', $docType)
                    ->where('application_id', '!=', $documentData['application_id'] ?? 0)
                    ->first();
                
                if ($duplicate) {
                    $analysis[] = [
                        'document_type' => $docType,
                        'type' => 'duplicate_document',
                        'severity' => 'high',
                        'description' => 'Document hash matches previously uploaded document',
                        'duplicate_application_id' => $duplicate->application_id,
                        'risk_points' => self::FRAUD_PATTERNS['duplicate_document']
                    ];
                }
            }
            
            // Check OCR confidence
            if (isset($validationData['ocr_confidence']) && $validationData['ocr_confidence'] < 60) {
                $analysis[] = [
                    'document_type' => $docType,
                    'type' => 'low_ocr_confidence',
                    'severity' => 'low',
                    'description' => 'Document OCR confidence is very low',
                    'confidence' => $validationData['ocr_confidence'],
                    'risk_points' => 15
                ];
            }
        }
        
        return $analysis;
    }
    
    /**
     * Analyze fraud patterns
     * 
     * @param array $formData
     * @param array $documents
     * @param string $applicationType
     * @return array
     */
    private function analyzeFraudPatterns(array $formData, array $documents, string $applicationType): array
    {
        $patterns = [];
        
        // Check for prior rejections
        $priorRejections = $this->checkPriorRejections($formData, $applicationType);
        if ($priorRejections > 0) {
            $patterns[] = [
                'type' => 'prior_rejection',
                'severity' => 'medium',
                'description' => "Applicant has {$priorRejections} prior rejection(s)",
                'count' => $priorRejections,
                'risk_points' => self::FRAUD_PATTERNS['prior_rejection']
            ];
        }
        
        // Check for prior revocations
        $priorRevocations = $this->checkPriorRevocations($formData, $applicationType);
        if ($priorRevocations > 0) {
            $patterns[] = [
                'type' => 'prior_revocation',
                'severity' => 'high',
                'description' => "Applicant has {$priorRevocations} prior revocation(s)",
                'count' => $priorRevocations,
                'risk_points' => self::FRAUD_PATTERNS['prior_revocation']
            ];
        }
        
        // Check for suspicious upload patterns
        $uploadPattern = $this->analyzeUploadPattern($documents);
        if ($uploadPattern['suspicious']) {
            $patterns[] = [
                'type' => 'suspicious_upload_pattern',
                'severity' => 'medium',
                'description' => $uploadPattern['description'],
                'details' => $uploadPattern['details'],
                'risk_points' => self::FRAUD_PATTERNS['suspicious_upload_pattern']
            ];
        }
        
        // Application-specific pattern checks
        match ($applicationType) {
            'accreditation', 'foreign_accreditation' => $patterns = array_merge($patterns, $this->analyzePractitionerPatterns($formData, $documents)),
            'media_house', 'news_agency' => $patterns = array_merge($patterns, $this->analyzeMediaHousePatterns($formData, $documents)),
            'ap4' => $patterns = array_merge($patterns, $this->analyzeForeignOfficePatterns($formData, $documents)),
            default => null
        };
        
        return $patterns;
    }
    
    /**
     * Analyze practitioner-specific fraud patterns
     * 
     * @param array $formData
     * @param array $documents
     * @return array
     */
    private function analyzePractitionerPatterns(array $formData, array $documents): array
    {
        $patterns = [];
        
        // Check for unregistered employer (for full-time practitioners)
        if (!empty($formData['category']) && $formData['category'] === 'full_time' && !empty($formData['organisation_name'])) {
            $employerRegistered = DB::table('applications')
                ->where('application_type', 'media_house')
                ->where('form_data->company_name', 'like', '%' . $formData['organisation_name'] . '%')
                ->whereIn('status', ['approved', 'issued', 'printed'])
                ->exists();
            
            if (!$employerRegistered) {
                $patterns[] = [
                    'type' => 'unregistered_employer',
                    'severity' => 'medium',
                    'description' => 'Employer not found in registered media houses',
                    'employer_name' => $formData['organisation_name'],
                    'risk_points' => self::FRAUD_PATTERNS['unregistered_employer']
                ];
            }
        }
        
        // Check for certificate reuse
        if (isset($documents['educational_certificate'])) {
            $certHash = $documents['educational_certificate']['validation_data']['sha256'] ?? null;
            if ($certHash) {
                $reuse = DB::table('application_documents')
                    ->where('sha256', $certHash)
                    ->where('doc_type', 'educational_certificate')
                    ->count();
                
                if ($reuse > 2) {
                    $patterns[] = [
                        'type' => 'certificate_reuse',
                        'severity' => 'high',
                        'description' => "Certificate reused in {$reuse} applications",
                        'risk_points' => 35
                    ];
                }
            }
        }
        
        return $patterns;
    }
    
    /**
     * Analyze media house-specific fraud patterns
     * 
     * @param array $formData
     * @param array $documents
     * @return array
     */
    private function analyzeMediaHousePatterns(array $formData, array $documents): array
    {
        $patterns = [];
        
        // Check for near-duplicate company names
        if (!empty($formData['applicant_name'])) {
            $similarNames = $this->findSimilarCompanyNames($formData['applicant_name']);
            
            if (!empty($similarNames)) {
                $patterns[] = [
                    'type' => 'similar_company_name',
                    'severity' => 'medium',
                    'description' => 'Company name similar to existing registration',
                    'similar_names' => $similarNames,
                    'risk_points' => 30
                ];
            }
        }
        
        // Check for hidden foreign ownership
        if (!empty($formData['shareholding_structure'])) {
            $hiddenForeign = $this->detectHiddenForeignOwnership($formData['shareholding_structure']);
            
            if ($hiddenForeign) {
                $patterns[] = [
                    'type' => 'foreign_ownership_hidden',
                    'severity' => 'high',
                    'description' => 'Potential hidden foreign ownership detected',
                    'details' => $hiddenForeign,
                    'risk_points' => self::FRAUD_PATTERNS['foreign_ownership_hidden']
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * Analyze foreign office-specific fraud patterns
     * 
     * @param array $formData
     * @param array $documents
     * @return array
     */
    private function analyzeForeignOfficePatterns(array $formData, array $documents): array
    {
        $patterns = [];
        
        // Check if foreign organization is on watch list
        if (!empty($formData['foreign_mass_media_service'])) {
            $onWatchList = DB::table('system_configs')
                ->where('key', 'foreign_media_watchlist')
                ->where('value', 'like', '%' . $formData['foreign_mass_media_service'] . '%')
                ->exists();
            
            if ($onWatchList) {
                $patterns[] = [
                    'type' => 'watchlist_organization',
                    'severity' => 'high',
                    'description' => 'Foreign organization on watch list',
                    'organization' => $formData['foreign_mass_media_service'],
                    'risk_points' => 40
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * Perform insider risk checks
     * 
     * @param int $applicationId
     * @return array
     */
    private function performInsiderRiskChecks(int $applicationId): array
    {
        $checks = [];
        
        // Check for officer override patterns
        $overrideCount = DB::table('audit_logs')
            ->where('application_id', $applicationId)
            ->where('action', 'override')
            ->count();
        
        if ($overrideCount > 3) {
            $checks[] = [
                'type' => 'excessive_overrides',
                'severity' => 'critical',
                'description' => "Application has {$overrideCount} officer overrides",
                'count' => $overrideCount,
                'risk_points' => 50
            ];
        }
        
        // Check for data modifications after approval
        $postApprovalModifications = DB::table('audit_logs')
            ->where('application_id', $applicationId)
            ->where('action', 'update')
            ->where('created_at', '>', function($query) use ($applicationId) {
                $approvalDate = DB::table('audit_logs')
                    ->where('application_id', $applicationId)
                    ->where('action', 'approve')
                    ->value('created_at');
                return $approvalDate;
            })
            ->count();
        
        if ($postApprovalModifications > 0) {
            $checks[] = [
                'type' => 'post_approval_modifications',
                'severity' => 'critical',
                'description' => "Application modified {$postApprovalModifications} times after approval",
                'count' => $postApprovalModifications,
                'risk_points' => 60
            ];
        }
        
        return $checks;
    }
    
    /**
     * Calculate overall risk score
     * 
     * @param array $detectionResults
     * @return int
     */
    private function calculateRiskScore(array $detectionResults): int
    {
        $totalScore = 0;
        
        // Score from duplicate checks
        foreach ($detectionResults['duplicate_checks'] as $check) {
            $totalScore += $check['risk_points'] ?? 0;
        }
        
        // Score from document analysis
        foreach ($detectionResults['document_analysis'] as $analysis) {
            $totalScore += $analysis['risk_points'] ?? 0;
        }
        
        // Score from pattern analysis
        foreach ($detectionResults['pattern_analysis'] as $pattern) {
            $totalScore += $pattern['risk_points'] ?? 0;
        }
        
        // Score from insider checks (weighted higher)
        foreach ($detectionResults['insider_risk_checks'] as $check) {
            $totalScore += ($check['risk_points'] ?? 0) * 1.5;
        }
        
        // Cap at 100
        return min(100, (int) $totalScore);
    }
    
    /**
     * Determine risk level from score
     * 
     * @param int $score
     * @return string
     */
    private function determineRiskLevel(int $score): string
    {
        foreach (self::RISK_LEVELS as $level => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $level;
            }
        }
        
        return 'minimal';
    }
    
    /**
     * Aggregate fraud flags from all checks
     * 
     * @param array $detectionResults
     * @return array
     */
    private function aggregateFraudFlags(array $detectionResults): array
    {
        $flags = [];
        
        // Aggregate from duplicate checks
        foreach ($detectionResults['duplicate_checks'] as $check) {
            if (in_array($check['severity'] ?? 'low', ['high', 'critical'])) {
                $flags[] = [
                    'type' => $check['type'],
                    'severity' => $check['severity'],
                    'description' => $check['description']
                ];
            }
        }
        
        // Aggregate from document analysis
        foreach ($detectionResults['document_analysis'] as $analysis) {
            if (in_array($analysis['severity'] ?? 'low', ['high', 'critical'])) {
                $flags[] = [
                    'type' => $analysis['type'],
                    'severity' => $analysis['severity'],
                    'description' => $analysis['description']
                ];
            }
        }
        
        // Aggregate from pattern analysis
        foreach ($detectionResults['pattern_analysis'] as $pattern) {
            if (in_array($pattern['severity'] ?? 'low', ['high', 'critical'])) {
                $flags[] = [
                    'type' => $pattern['type'],
                    'severity' => $pattern['severity'],
                    'description' => $pattern['description']
                ];
            }
        }
        
        return $flags;
    }
    
    /**
     * Generate recommendations based on detection results
     * 
     * @param array $detectionResults
     * @return array
     */
    private function generateRecommendations(array $detectionResults): array
    {
        $recommendations = [];
        
        $riskLevel = $detectionResults['risk_level'];
        
        match ($riskLevel) {
            'critical' => [
                $recommendations[] = 'Block application and require senior management review',
                $recommendations[] = 'Conduct enhanced identity verification',
                $recommendations[] = 'Require in-person interview'
            ],
            'high' => [
                $recommendations[] = 'Require senior officer review',
                $recommendations[] = 'Request additional supporting documentation',
                $recommendations[] = 'Enhanced document verification required'
            ],
            'medium' => [
                $recommendations[] = 'Require standard officer review',
                $recommendations[] = 'Verify specific flagged items',
                $recommendations[] = 'Consider additional documentation'
            ],
            'low' => [
                $recommendations[] = 'Standard processing acceptable',
                $recommendations[] = 'Monitor for any changes'
            ],
            'minimal' => [
                $recommendations[] = 'Proceed with standard processing'
            ]
        };
        
        // Add specific recommendations based on fraud flags
        foreach ($detectionResults['fraud_flags'] as $flag) {
            match ($flag['type']) {
                'duplicate_national_id' => $recommendations[] = 'Verify identity with applicant directly',
                'document_tampering' => $recommendations[] = 'Request original documents for inspection',
                'unregistered_employer' => $recommendations[] = 'Verify employer registration status',
                default => null
            };
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Helper method: Check prior rejections
     */
    private function checkPriorRejections(array $formData, string $applicationType): int
    {
        $query = DB::table('applications')->where('application_type', $applicationType);
        
        if (!empty($formData['national_id'])) {
            $query->whereJsonContains('form_data->national_id', $formData['national_id']);
        } elseif (!empty($formData['passport_number'])) {
            $query->whereJsonContains('form_data->passport_number', $formData['passport_number']);
        }
        
        return $query->whereIn('status', ['rejected', 'officer_rejected', 'registrar_rejected'])
            ->count();
    }
    
    /**
     * Helper method: Check prior revocations
     */
    private function checkPriorRevocations(array $formData, string $applicationType): int
    {
        // Implementation depends on how revocations are stored
        return 0; // Placeholder
    }
    
    /**
     * Helper method: Analyze upload pattern
     */
    private function analyzeUploadPattern(array $documents): array
    {
        // Analyze timing, file sizes, etc. for suspicious patterns
        return [
            'suspicious' => false,
            'description' => '',
            'details' => []
        ]; // Placeholder
    }
    
    /**
     * Helper method: Find similar company names
     */
    private function findSimilarCompanyNames(string $companyName): array
    {
        // Use fuzzy matching to find similar names
        return []; // Placeholder
    }
    
    /**
     * Helper method: Detect hidden foreign ownership
     */
    private function detectHiddenForeignOwnership(array $shareholding): ?array
    {
        // Analyze shareholding structure for hidden foreign ownership
        return null; // Placeholder
    }
    
    /**
     * Log fraud detection results
     */
    private function logFraudDetection(array $formData, array $result, string $applicationType): void
    {
        Log::info('Fraud detection completed', [
            'application_type' => $applicationType,
            'risk_score' => $result['risk_score'],
            'risk_level' => $result['risk_level'],
            'fraud_flags_count' => count($result['fraud_flags']),
            'block_application' => $result['block_application']
        ]);
    }
}