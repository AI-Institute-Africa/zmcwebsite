<?php

namespace App\Services\Validation;

class ReplacementValidator extends BaseValidator
{
    /**
     * Required fields for replacement
     */
    private const REQUIRED_FIELDS = [
        'previous_number',
        'replacement_reason',
        'applicant_identity_confirmation',
        'payment_confirmation',
        'declaration'
    ];
    
    public function validate(array $formData, array $documents, string $applicationType): array
    {
        $this->resetValidation();
        
        // Validate basic required fields
        $this->validateBasicRequiredFields($formData);
        
        // Validate previous number
        $this->validatePreviousNumber($formData, $applicationType);
        
        // Validate identity match
        $this->validateIdentityMatch($formData);
        
        // Validate replacement reason
        $this->validateReplacementReason($formData, $documents);
        
        // Validate payment
        $this->validatePayment($formData);
        
        // Check for suspicious replacement patterns
        $this->checkReplacementPatterns($formData);
        
        // Validate supporting documents
        $this->validateSupportingDocuments($formData, $documents);
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'replacement');
        
        return $result;
    }
    
    private function validateBasicRequiredFields(array $formData): void
    {
        $missing = $this->validateRequiredFields($formData, self::REQUIRED_FIELDS);
        
        if (!empty($missing)) {
            return;
        }
        
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
        
        // Validate replacement reason is legitimate
        $legitimateReasons = ['lost', 'damaged', 'stolen', 'name_change', 'other'];
        if (isset($formData['replacement_reason'])) {
            if (!in_array(strtolower($formData['replacement_reason']), $legitimateReasons)) {
                $this->addSoftWarning('replacement_reason', 'Please provide a clear replacement reason');
            }
            
            if (strtolower($formData['replacement_reason']) === 'other' && empty($formData['replacement_reason_details'])) {
                $this->addHardStop('replacement_reason_details', 'Details are required when reason is "other"');
            }
        }
    }
    
    private function validatePreviousNumber(array $formData, string $applicationType): void
    {
        if (empty($formData['previous_number'])) {
            $this->addHardStop('previous_number', 'Previous card or certificate number is required');
            return;
        }
        
        // Determine which table to check
        $table = ($applicationType === 'accreditation') ? 'accreditation_records' : 'registration_records';
        $numberField = ($applicationType === 'accreditation') ? 'accreditation_number' : 'registration_number';
        
        $previousRecord = \DB::table($table)
            ->where($numberField, $formData['previous_number'])
            ->first();
        
        if (!$previousRecord) {
            $this->addHardStop('previous_number', 'Previous card or certificate number not found');
            return;
        }
        
        // Store for further validation
        $this->previousRecord = $previousRecord;
    }
    
    private function validateIdentityMatch(array $formData): void
    {
        if (!isset($this->previousRecord)) {
            return;
        }
        
        $previousName = $this->previousRecord->name ?? $this->previousRecord->organisation_name ?? '';
        $currentName = $formData['applicant_name'] ?? '';
        
        // Basic identity check
        if ($previousName && $currentName) {
            $nameComparison = $this->normalizationService->compareNames($previousName, $currentName);
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $this->addHardStop('applicant_name', 'Identity does not match previous record. Replacement cannot be issued to a different person/entity.');
                $this->addFraudFlag('applicant_name', 'Identity mismatch in replacement request');
            }
        }
    }
    
    private function validateReplacementReason(array $formData, array $documents): void
    {
        $reason = strtolower($formData['replacement_reason'] ?? '');
        
        // Require supporting documents based on reason
        match ($reason) {
            'lost', 'stolen' => $this->requireDocument($documents, 'lost_card_affidavit', 'Affidavit is required for lost/stolen cards'),
            'damaged' => $this->requireDocument($documents, 'damage_evidence', 'Evidence of damage is required for damaged cards'),
            'name_change' => $this->requireDocument($documents, 'name_change_proof', 'Legal proof of name change is required'),
            default => null
        };
    }
    
    private function requireDocument(array $documents, string $documentType, string $errorMessage): void
    {
        if (!$this->validateDocumentPresent($documents, $documentType, true)) {
            $this->addHardStop($documentType, $errorMessage);
        }
    }
    
    private function validatePayment(array $formData): void
    {
        if (empty($formData['payment_confirmation'])) {
            $this->addHardStop('payment_confirmation', 'Payment confirmation is required for replacement');
        }
        
        $this->addManualReview('payment_confirmation', 'Payment verification pending');
    }
    
    private function checkReplacementPatterns(array $formData): void
    {
        if (!isset($this->previousRecord) || !isset($formData['previous_number'])) {
            return;
        }
        
        // Check for multiple replacements within short period
        $recentReplacements = \DB::table('applications')
            ->where('form_data->previous_number', $formData['previous_number'])
            ->where('request_type', 'replacement')
            ->where('created_at', '>=', now()->subDays(90))
            ->count();
        
        if ($recentReplacements >= 2) {
            $this->addFraudFlag('previous_number', 'Multiple replacement requests within short period');
            $this->addManualReview('previous_number', 'Suspicious replacement pattern detected');
        }
        
        // Check if previous card was recently issued
        if (isset($this->previousRecord->issued_at)) {
            $issueDate = \DateTime::createFromFormat('Y-m-d H:i:s', $this->previousRecord->issued_at);
            $daysSinceIssue = $issueDate->diff(new \DateTime())->days;
            
            if ($daysSinceIssue < 30) {
                $this->addFraudFlag('previous_number', 'Replacement requested for recently issued card');
                $this->addManualReview('previous_number', 'Card issued recently - replacement requires review');
            }
        }
    }
    
    private function validateSupportingDocuments(array $formData, array $documents): void
    {
        // Validate affidavit if provided
        if (isset($documents['lost_card_affidavit'])) {
            $affidavitValidationData = $this->getDocumentValidationData($documents, 'lost_card_affidavit');
            
            if ($affidavitValidationData) {
                if ($affidavitValidationData['tampering_detected']) {
                    $this->addFraudFlag('lost_card_affidavit', 'Potential tampering detected on affidavit');
                    $this->addManualReview('lost_card_affidavit', 'Affidavit requires manual verification');
                }
                
                // Check affidavit date is recent
                $extractedDate = $affidavitValidationData['extracted_data']['date'] ?? null;
                if ($extractedDate) {
                    $affidavitDate = \DateTime::createFromFormat('Y-m-d', $extractedDate);
                    $daysSinceAffidavit = $affidavitDate->diff(new \DateTime())->days;
                    
                    if ($daysSinceAffidavit > 90) {
                        $this->addSoftWarning('lost_card_affidavit', 'Affidavit is more than 90 days old');
                    }
                }
            }
        }
        
        // Validate damage evidence if provided
        if (isset($documents['damage_evidence'])) {
            $damageValidationData = $this->getDocumentValidationData($documents, 'damage_evidence');
            
            if ($damageValidationData && $damageValidationData['tampering_detected']) {
                $this->addFraudFlag('damage_evidence', 'Potential tampering detected on damage evidence');
            }
        }
    }
}