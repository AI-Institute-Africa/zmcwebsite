<?php

namespace App\Services\Validation;

class RenewalValidator extends BaseValidator
{
    /**
     * Application types that can be renewed
     */
    private const RENEWABLE_TYPES = [
        'accreditation',
        'media_house',
        'news_agency',
        'foreign_representative'
    ];
    
    /**
     * Required fields for renewal (AP5)
     */
    private const REQUIRED_FIELDS = [
        'previous_number',
        'applicant_name',
        'contact_details',
        'changes_declared',
        'declaration',
        'payment_confirmation'
    ];
    
    public function validate(array $formData, array $documents, string $applicationType): array
    {
        $this->resetValidation();
        
        // Validate application type is renewable
        $this->validateApplicationType($applicationType);
        
        // Validate basic required fields
        $this->validateBasicRequiredFields($formData);
        
        // Validate previous number
        $this->validatePreviousNumber($formData, $applicationType);
        
        // Validate identity match with previous record
        $this->validateIdentityMatch($formData, $applicationType);
        
        // Validate changes declared
        if (!empty($formData['changes_declared']) && $formData['changes_declared'] === true) {
            $this->validateChanges($formData, $applicationType);
        }
        
        // Validate previous record status
        $this->validatePreviousRecordStatus($formData, $applicationType);
        
        // Validate payment
        $this->validatePayment($formData);
        
        // Check for duplicate renewal attempts
        $this->checkDuplicateRenewal($formData);
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'renewal_ap5');
        
        return $result;
    }
    
    private function validateApplicationType(string $applicationType): void
    {
        if (!in_array($applicationType, self::RENEWABLE_TYPES)) {
            $this->addHardStop('application_type', 'This application type cannot be renewed');
        }
    }
    
    private function validateBasicRequiredFields(array $formData): void
    {
        $missing = $this->validateRequiredFields($formData, self::REQUIRED_FIELDS);
        
        if (!empty($missing)) {
            return;
        }
        
        if (isset($formData['contact_details']['email'])) {
            $this->validateEmail($formData['contact_details']['email'], 'email');
        }
        
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
    }
    
    private function validatePreviousNumber(array $formData, string $applicationType): void
    {
        if (empty($formData['previous_number'])) {
            $this->addHardStop('previous_number', 'Previous accreditation or registration number is required');
            return;
        }
        
        // Determine which table to check based on application type
        $table = match($applicationType) {
            'accreditation' => 'accreditation_records',
            'media_house', 'news_agency' => 'registration_records',
            'foreign_representative' => 'registration_records',
            default => null
        };
        
        if (!$table) {
            return;
        }
        
        $previousRecord = \DB::table($table)
            ->where(match($applicationType) {
                'accreditation' => 'accreditation_number',
                default => 'registration_number'
            }, $formData['previous_number'])
            ->first();
        
        if (!$previousRecord) {
            $this->addHardStop('previous_number', 'The previous accreditation or registration number entered could not be matched to this applicant. Please check the number or contact ZMC for assistance.');
            return;
        }
        
        // Store previous record for further validation
        $this->previousRecord = $previousRecord;
    }
    
    private function validateIdentityMatch(array $formData, string $applicationType): void
    {
        if (!isset($this->previousRecord)) {
            return;
        }
        
        $previousName = $this->previousRecord->name ?? $this->previousRecord->organisation_name ?? '';
        $currentName = $formData['applicant_name'];
        
        if ($applicationType === 'accreditation') {
            $nameComparison = $this->normalizationService->compareNames($previousName, $currentName);
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $this->addHardStop('applicant_name', 'Applicant name does not match previous record. Renewals cannot be used to transfer accreditation to another person.');
                $this->addFraudFlag('applicant_name', 'Identity mismatch in renewal attempt');
            } elseif ($nameComparison['match_status'] === 'manual_review') {
                $this->addManualReview('applicant_name', 'Name variation detected - may require legal proof of name change');
            }
        } else {
            // For media houses and organizations
            $orgComparison = $this->normalizationService->compareOrganizationNames($previousName, $currentName);
            
            if ($orgComparison['match_status'] === 'hard_stop') {
                $this->addHardStop('applicant_name', 'Organization name does not match previous record. Renewals cannot be used to transfer registration to another entity.');
                $this->addFraudFlag('applicant_name', 'Organization identity mismatch in renewal attempt');
            }
        }
    }
    
    private function validateChanges(array $formData, string $applicationType): void
    {
        if (empty($formData['changes_details'])) {
            $this->addHardStop('changes_details', 'Details of changes are required when changes are declared');
            return;
        }
        
        // For media houses, certain changes require new application rather than renewal
        if (in_array($applicationType, ['media_house', 'news_agency'])) {
            $materialChanges = ['ownership', 'directors', 'category', 'scope'];
            $changesDetails = strtolower($formData['changes_details']);
            
            foreach ($materialChanges as $change) {
                if (str_contains($changesDetails, $change)) {
                    $this->addHardStop('changes_details', "Material changes to {$change} require a new application rather than renewal");
                }
            }
        }
        
        // Require supporting documents for changes
        if (!isset($formData['change_supporting_documents']) || empty($formData['change_supporting_documents'])) {
            $this->addManualReview('changes_details', 'Supporting documents for changes are required');
        }
    }
    
    private function validatePreviousRecordStatus(array $formData, string $applicationType): void
    {
        if (!isset($this->previousRecord)) {
            return;
        }
        
        $status = $this->previousRecord->status ?? '';
        
        // Reject renewal if previous record was rejected, revoked, or suspended
        if (in_array($status, ['rejected', 'revoked', 'suspended'])) {
            $this->addHardStop('previous_number', "Previous record status is '{$status}' and cannot be renewed");
        }
        
        // Check if expired beyond grace period
        if (isset($this->previousRecord->expiry_date)) {
            $expiryDate = \DateTime::createFromFormat('Y-m-d', $this->previousRecord->expiry_date);
            $gracePeriodDays = 30; // Configurable
            $gracePeriodEnd = $expiryDate->modify("+{$gracePeriodDays} days");
            
            if ($gracePeriodEnd < new \DateTime()) {
                $this->addHardStop('previous_number', 'Previous accreditation/registration expired beyond grace period');
            }
        }
        
        // Check for outstanding obligations
        $hasOutstandingObligations = $this->checkOutstandingObligations($formData['previous_number'], $applicationType);
        
        if ($hasOutstandingObligations) {
            $this->addHardStop('previous_number', 'Outstanding obligations must be cleared before renewal');
        }
    }
    
    private function validatePayment(array $formData): void
    {
        if (empty($formData['payment_confirmation'])) {
            $this->addHardStop('payment_confirmation', 'Payment confirmation is required for renewal');
        }
        
        // In production, this would verify payment against payment gateway records
        $this->addManualReview('payment_confirmation', 'Payment verification pending');
    }
    
    private function checkOutstandingObligations(string $previousNumber, string $applicationType): bool
    {
        // Check for unpaid levies (for media houses)
        if (in_array($applicationType, ['media_house', 'news_agency'])) {
            $unpaidLevies = \DB::table('levy_records')
                ->where('registration_number', $previousNumber)
                ->where('status', 'pending')
                ->exists();
            
            if ($unpaidLevies) {
                return true;
            }
        }
        
        // Check for compliance violations
        $hasViolations = \DB::table('compliance_violations')
            ->where('entity_identifier', $previousNumber)
            ->where('resolved', false)
            ->exists();
        
        return $hasViolations;
    }
    
    private function checkDuplicateRenewal(array $formData): void
    {
        $recentRenewal = \DB::table('applications')
            ->where('form_data->previous_number', $formData['previous_number'])
            ->where('request_type', 'renewal')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['draft', 'submitted', 'under_review'])
            ->exists();
        
        if ($recentRenewal) {
            $this->addFraudFlag('previous_number', 'Recent renewal attempt detected for same previous number');
            $this->addManualReview('previous_number', 'Multiple renewal attempts detected');
        }
    }
}