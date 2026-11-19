<?php

namespace App\Services\Validation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

abstract class BaseValidator
{
    protected DataNormalizationService $normalizationService;
    protected DocumentValidationService $documentValidationService;
    
    // Validation results
    protected array $hardStops = [];
    protected array $softWarnings = [];
    protected array $manualReviews = [];
    protected array $fraudFlags = [];
    protected int $riskScore = 0;
    
    // Validation thresholds
    protected const HARD_STOP_THRESHOLD = 90; // Points above which validation fails
    protected const MANUAL_REVIEW_THRESHOLD = 50; // Points above which manual review required
    protected const FRAUD_FLAG_THRESHOLD = 80; // Points above which fraud flags are raised
    
    public function __construct(
        DataNormalizationService $normalizationService,
        DocumentValidationService $documentValidationService
    ) {
        $this->normalizationService = $normalizationService;
        $this->documentValidationService = $documentValidationService;
    }
    
    /**
     * Main validation method - to be implemented by specific validators
     * 
     * @param array $formData
     * @param array $documents
     * @return array
     */
    abstract public function validate(array $formData, array $documents): array;
    
    /**
     * Add a hard-stop error
     * 
     * @param string $field
     * @param string $message
     * @param int $points
     * @return void
     */
    protected function addHardStop(string $field, string $message, int $points = 100): void
    {
        $this->hardStops[] = [
            'field' => $field,
            'message' => $message,
            'points' => $points,
            'timestamp' => now()->toIso8601String()
        ];
        
        $this->riskScore += $points;
    }
    
    /**
     * Add a soft warning
     * 
     * @param string $field
     * @param string $message
     * @param int $points
     * @return void
     */
    protected function addSoftWarning(string $field, string $message, int $points = 10): void
    {
        $this->softWarnings[] = [
            'field' => $field,
            'message' => $message,
            'points' => $points,
            'timestamp' => now()->toIso8601String()
        ];
        
        $this->riskScore += $points;
    }
    
    /**
     * Add a manual review flag
     * 
     * @param string $field
     * @param string $message
     * @param int $points
     * @return void
     */
    protected function addManualReview(string $field, string $message, int $points = 30): void
    {
        $this->manualReviews[] = [
            'field' => $field,
            'message' => $message,
            'points' => $points,
            'timestamp' => now()->toIso8601String()
        ];
        
        $this->riskScore += $points;
    }
    
    /**
     * Add a fraud flag (visible to officers only)
     * 
     * @param string $field
     * @param string $message
     * @param int $points
     * @return void
     */
    protected function addFraudFlag(string $field, string $message, int $points = 50): void
    {
        $this->fraudFlags[] = [
            'field' => $field,
            'message' => $message,
            'points' => $points,
            'timestamp' => now()->toIso8601String()
        ];
        
        $this->riskScore += $points;
    }
    
    /**
     * Get validation result
     * 
     * @return array
     */
    protected function getValidationResult(): array
    {
        $canProceed = $this->canProceedToPayment();
        $requiresManualReview = $this->requiresManualReview();
        $hasFraudIndicators = $this->hasFraudIndicators();
        
        return [
            'can_proceed' => $canProceed,
            'requires_manual_review' => $requiresManualReview,
            'has_fraud_indicators' => $hasFraudIndicators,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->determineRiskLevel(),
            'hard_stops' => $this->hardStops,
            'soft_warnings' => $this->softWarnings,
            'manual_reviews' => $this->manualReviews,
            'fraud_flags' => $this->fraudFlags,
            'applicant_messages' => $this->getApplicantMessages(),
            'officer_messages' => $this->getOfficerMessages()
        ];
    }
    
    /**
     * Check if application can proceed to payment
     * 
     * @return bool
     */
    protected function canProceedToPayment(): bool
    {
        return empty($this->hardStops);
    }
    
    /**
     * Check if manual review is required
     * 
     * @return bool
     */
    protected function requiresManualReview(): bool
    {
        return !empty($this->manualReviews) || $this->riskScore >= self::MANUAL_REVIEW_THRESHOLD;
    }
    
    /**
     * Check if there are fraud indicators
     * 
     * @return bool
     */
    protected function hasFraudIndicators(): bool
    {
        return !empty($this->fraudFlags) || $this->riskScore >= self::FRAUD_FLAG_THRESHOLD;
    }
    
    /**
     * Determine risk level based on score
     * 
     * @return string
     */
    protected function determineRiskLevel(): string
    {
        if ($this->riskScore >= 90) {
            return 'critical';
        } elseif ($this->riskScore >= 70) {
            return 'high';
        } elseif ($this->riskScore >= 40) {
            return 'medium';
        } elseif ($this->riskScore >= 20) {
            return 'low';
        } else {
            return 'minimal';
        }
    }
    
    /**
     * Get messages suitable for applicants
     * 
     * @return array
     */
    protected function getApplicantMessages(): array
    {
        $messages = [];
        
        // Hard stops prevent submission
        foreach ($this->hardStops as $stop) {
            $messages[] = [
                'type' => 'error',
                'field' => $stop['field'],
                'message' => $stop['message']
            ];
        }
        
        // Soft warnings allow submission but notify user
        foreach ($this->softWarnings as $warning) {
            $messages[] = [
                'type' => 'warning',
                'field' => $warning['field'],
                'message' => $warning['message']
            ];
        }
        
        // Manual review flags
        foreach ($this->manualReviews as $review) {
            $messages[] = [
                'type' => 'info',
                'field' => $review['field'],
                'message' => $review['message'] . ' This will require officer review.'
            ];
        }
        
        return $messages;
    }
    
    /**
     * Get messages suitable for officers
     * 
     * @return array
     */
    protected function getOfficerMessages(): array
    {
        $messages = [];
        
        // Include all messages plus fraud flags
        foreach ($this->hardStops as $stop) {
            $messages[] = [
                'type' => 'hard_stop',
                'field' => $stop['field'],
                'message' => $stop['message'],
                'points' => $stop['points']
            ];
        }
        
        foreach ($this->softWarnings as $warning) {
            $messages[] = [
                'type' => 'warning',
                'field' => $warning['field'],
                'message' => $warning['message'],
                'points' => $warning['points']
            ];
        }
        
        foreach ($this->manualReviews as $review) {
            $messages[] = [
                'type' => 'manual_review',
                'field' => $review['field'],
                'message' => $review['message'],
                'points' => $review['points']
            ];
        }
        
        // Fraud flags (officer-only)
        foreach ($this->fraudFlags as $flag) {
            $messages[] = [
                'type' => 'fraud_flag',
                'field' => $flag['field'],
                'message' => $flag['message'],
                'points' => $flag['points']
            ];
        }
        
        return $messages;
    }
    
    /**
     * Reset validation state
     * 
     * @return void
     */
    protected function resetValidation(): void
    {
        $this->hardStops = [];
        $this->softWarnings = [];
        $this->manualReviews = [];
        $this->fraudFlags = [];
        $this->riskScore = 0;
    }
    
    /**
     * Check for duplicate application
     * 
     * @param array $formData
     * @param string $applicationType
     * @return array
     */
    protected function checkDuplicateApplication(array $formData, string $applicationType): array
    {
        $duplicateIndicators = [];
        
        try {
            // Check by National ID for practitioners
            if (!empty($formData['national_id'])) {
                $normalizedId = $this->normalizationService->normalizeNationalId($formData['national_id']);
                
                $duplicate = DB::table('applications')
                    ->where('application_type', $applicationType)
                    ->whereJsonContains('form_data->national_id', $normalizedId)
                    ->whereIn('status', ['draft', 'submitted', 'under_review', 'officer_review'])
                    ->first();
                
                if ($duplicate) {
                    $duplicateIndicators[] = [
                        'type' => 'duplicate_national_id',
                        'message' => 'Application with this National ID already exists',
                        'application_id' => $duplicate->id,
                        'status' => $duplicate->status
                    ];
                }
            }
            
            // Check by passport for foreign practitioners
            if (!empty($formData['passport_number'])) {
                $normalizedPassport = $this->normalizationService->normalizePassportNumber($formData['passport_number']);
                
                $duplicate = DB::table('applications')
                    ->where('application_type', $applicationType)
                    ->whereJsonContains('form_data->passport_number', $normalizedPassport)
                    ->whereIn('status', ['draft', 'submitted', 'under_review', 'officer_review'])
                    ->first();
                
                if ($duplicate) {
                    $duplicateIndicators[] = [
                        'type' => 'duplicate_passport',
                        'message' => 'Application with this passport number already exists',
                        'application_id' => $duplicate->id,
                        'status' => $duplicate->status
                    ];
                }
            }
            
            // Check by company name for media houses
            if (!empty($formData['company_name'])) {
                $normCompany = $this->normalizationService->normalizeOrganizationName($formData['company_name']);
                
                $duplicate = DB::table('applications')
                    ->where('application_type', $applicationType)
                    ->where('form_data->company_name', 'like', '%' . $normCompany['normalized'] . '%')
                    ->whereIn('status', ['draft', 'submitted', 'under_review', 'officer_review'])
                    ->first();
                
                if ($duplicate) {
                    $duplicateIndicators[] = [
                        'type' => 'duplicate_company',
                        'message' => 'Application for this company already exists',
                        'application_id' => $duplicate->id,
                        'status' => $duplicate->status
                    ];
                }
            }
            
            // Check by email
            if (!empty($formData['email'])) {
                $normalizedEmail = $this->normalizationService->normalizeEmail($formData['email']);
                
                $recentApplications = DB::table('applications')
                    ->where('application_type', $applicationType)
                    ->where('form_data->email', $normalizedEmail)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count();
                
                if ($recentApplications > 3) {
                    $duplicateIndicators[] = [
                        'type' => 'multiple_applications_email',
                        'message' => 'Multiple applications from this email address recently',
                        'count' => $recentApplications
                    ];
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Duplicate check failed', [
                'application_type' => $applicationType,
                'error' => $e->getMessage()
            ]);
        }
        
        return $duplicateIndicators;
    }
    
    /**
     * Validate required fields
     * 
     * @param array $formData
     * @param array $requiredFields
     * @return array
     */
    protected function validateRequiredFields(array $formData, array $requiredFields): array
    {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            $value = $formData[$field] ?? null;
            
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                $missing[] = $field;
                $this->addHardStop($field, "The {$field} field is required");
            }
        }
        
        return $missing;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email
     * @param string $fieldName
     * @return bool
     */
    protected function validateEmail(string $email, string $fieldName = 'email'): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addHardStop($fieldName, "Please enter a valid email address");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $phone
     * @param string $fieldName
     * @return bool
     */
    protected function validatePhone(string $phone, string $fieldName = 'phone'): bool
    {
        $normalized = $this->normalizationService->normalizePhone($phone);
        
        // Basic validation - should be at least 10 digits
        $digits = preg_replace('/[^0-9]/', '', $normalized);
        
        if (strlen($digits) < 10) {
            $this->addHardStop($fieldName, "Please enter a valid phone number");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date format
     * 
     * @param string $date
     * @param string $fieldName
     * @return bool
     */
    protected function validateDate(string $date, string $fieldName = 'date'): bool
    {
        try {
            \DateTime::createFromFormat('Y-m-d', $date);
            return true;
        } catch (\Exception $e) {
            $this->addHardStop($fieldName, "Please enter a valid date");
            return false;
        }
    }
    
    /**
     * Check if date is in the past
     * 
     * @param string $date
     * @param string $fieldName
     * @return bool
     */
    protected function validatePastDate(string $date, string $fieldName = 'date'): bool
    {
        if (!$this->validateDate($date, $fieldName)) {
            return false;
        }
        
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj > new \DateTime()) {
            $this->addHardStop($fieldName, "Date cannot be in the future");
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if date is in the future
     * 
     * @param string $date
     * @param string $fieldName
     * @return bool
     */
    protected function validateFutureDate(string $date, string $fieldName = 'date'): bool
    {
        if (!$this->validateDate($date, $fieldName)) {
            return false;
        }
        
        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj <= new \DateTime()) {
            $this->addHardStop($fieldName, "Date must be in the future");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate document is present in documents array
     * 
     * @param array $documents
     * @param string $documentType
     * @param bool $required
     * @return bool
     */
    protected function validateDocumentPresent(array $documents, string $documentType, bool $required = true): bool
    {
        $present = isset($documents[$documentType]) && !empty($documents[$documentType]);
        
        if ($required && !$present) {
            $this->addHardStop($documentType, "The {$documentType} document is required");
            return false;
        }
        
        return $present;
    }
    
    /**
     * Extract document validation data
     * 
     * @param array $documents
     * @param string $documentType
     * @return array|null
     */
    protected function getDocumentValidationData(array $documents, string $documentType): ?array
    {
        if (!isset($documents[$documentType])) {
            return null;
        }
        
        return $documents[$documentType]['validation_data'] ?? null;
    }
    
    /**
     * Log validation result
     * 
     * @param array $formData
     * @param array $result
     * @param string $applicationType
     * @return void
     */
    protected function logValidationResult(array $formData, array $result, string $applicationType): void
    {
        Log::info('Validation completed', [
            'application_type' => $applicationType,
            'can_proceed' => $result['can_proceed'],
            'risk_score' => $result['risk_score'],
            'risk_level' => $result['risk_level'],
            'hard_stops_count' => count($result['hard_stops']),
            'manual_reviews_count' => count($result['manual_reviews']),
            'fraud_flags_count' => count($result['fraud_flags']),
        ]);
    }
}