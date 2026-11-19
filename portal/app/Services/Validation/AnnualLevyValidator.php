<?php

namespace App\Services\Validation;

class AnnualLevyValidator extends BaseValidator
{
    /**
     * Required fields for annual levy (AP6)
     */
    private const REQUIRED_FIELDS = [
        'registration_number',
        'company_name',
        'financial_year',
        'gross_annual_turnover',
        'audited_financials_uploaded',
        'declaration'
    ];
    
    /**
     * Required documents for annual levy
     */
    private const REQUIRED_DOCUMENTS = [
        'audited_financials'
    ];
    
    public function validate(array $formData, array $documents): array
    {
        $this->resetValidation();
        
        // Validate basic required fields
        $this->validateBasicRequiredFields($formData);
        
        // Validate documents
        $this->validateRequiredDocuments($documents);
        
        // Validate registration number
        $this->validateRegistrationNumber($formData);
        
        // Validate company name
        $this->validateCompanyName($formData);
        
        // Validate financial year
        $this->validateFinancialYear($formData);
        
        // Validate turnover against audited financials
        $this->validateTurnoverAgainstFinancials($formData, $documents);
        
        // Calculate and validate levy
        $this->validateLevyCalculation($formData);
        
        // Check for duplicate levy submissions
        $this->checkDuplicateLevy($formData);
        
        // Validate against previous levy payments
        $this->validateLevyHistory($formData);
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'annual_levy_ap6');
        
        return $result;
    }
    
    private function validateBasicRequiredFields(array $formData): void
    {
        $missing = $this->validateRequiredFields($formData, self::REQUIRED_FIELDS);
        
        if (!empty($missing)) {
            return;
        }
        
        // Validate turnover is positive number
        if (isset($formData['gross_annual_turnover'])) {
            if (!is_numeric($formData['gross_annual_turnover']) || $formData['gross_annual_turnover'] < 0) {
                $this->addHardStop('gross_annual_turnover', 'Gross annual turnover must be a positive number');
            }
        }
        
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
    }
    
    private function validateRequiredDocuments(array $documents): void
    {
        foreach (self::REQUIRED_DOCUMENTS as $documentType) {
            $this->validateDocumentPresent($documents, $documentType, true);
        }
    }
    
    private function validateRegistrationNumber(array $formData): void
    {
        if (empty($formData['registration_number'])) {
            $this->addHardStop('registration_number', 'Registration number is required');
            return;
        }
        
        // Validate registration number exists and belongs to active media house
        $registration = \DB::table('registration_records')
            ->where('registration_number', $formData['registration_number'])
            ->whereIn('status', ['approved', 'issued', 'printed'])
            ->first();
        
        if (!$registration) {
            $this->addHardStop('registration_number', 'Registration number not found or not active');
            return;
        }
        
        $this->registrationRecord = $registration;
    }
    
    private function validateCompanyName(array $formData): void
    {
        if (empty($formData['company_name'])) {
            return;
        }
        
        // Validate company name matches registration record
        if (isset($this->registrationRecord)) {
            $registeredName = $this->registrationRecord->organisation_name ?? '';
            
            $orgComparison = $this->normalizationService->compareOrganizationNames(
                $formData['company_name'],
                $registeredName
            );
            
            if ($orgComparison['match_status'] === 'hard_stop') {
                $this->addHardStop('company_name', 'Company name does not match registration record');
            }
        }
        
        // Validate company name against audited financials
        if (isset($this->auditedFinancialsData)) {
            $financialsName = $this->auditedFinancialsData['extracted_data']['company_name'] ?? '';
            
            if (!empty($financialsName)) {
                $orgComparison = $this->normalizationService->compareOrganizationNames(
                    $formData['company_name'],
                    $financialsName
                );
                
                if ($orgComparison['match_status'] === 'hard_stop') {
                    $this->addHardStop('company_name', 'Company name does not match uploaded audited financial statements');
                }
            }
        }
    }
    
    private function validateFinancialYear(array $formData): void
    {
        if (empty($formData['financial_year'])) {
            return;
        }
        
        // Validate financial year format (YYYY)
        if (!preg_match('/^\d{4}$/', $formData['financial_year'])) {
            $this->addHardStop('financial_year', 'Financial year must be in YYYY format');
            return;
        }
        
        $year = (int) $formData['financial_year'];
        $currentYear = (int) date('Y');
        
        // Financial year should not be in the future
        if ($year > $currentYear) {
            $this->addHardStop('financial_year', 'Financial year cannot be in the future');
        }
        
        // Financial year should not be too old (more than 3 years)
        if ($year < $currentYear - 3) {
            $this->addSoftWarning('financial_year', 'Financial year is more than 3 years old');
        }
        
        // Validate financial year matches audited financials
        if (isset($this->auditedFinancialsData)) {
            $financialsYear = $this->auditedFinancialsData['extracted_data']['financial_year'] ?? '';
            
            if (!empty($financialsYear) && $financialsYear !== $formData['financial_year']) {
                $this->addHardStop('financial_year', 'Financial year entered does not match uploaded audited financial statements. Please correct the financial year or upload the correct financial document.');
            }
        }
    }
    
    private function validateTurnoverAgainstFinancials(array $formData, array $documents): void
    {
        if (!isset($documents['audited_financials'])) {
            return;
        }
        
        $auditedFinancialsValidationData = $this->getDocumentValidationData($documents, 'audited_financials');
        
        if (!$auditedFinancialsValidationData) {
            return;
        }
        
        $this->auditedFinancialsData = $auditedFinancialsValidationData;
        $extractedData = $auditedFinancialsValidationData['extracted_data'];
        
        // Validate turnover matches audited financials
        if (!empty($extractedData['gross_annual_turnover']) && !empty($formData['gross_annual_turnover'])) {
            $auditedTurnover = (float) $extractedData['gross_annual_turnover'];
            $enteredTurnover = (float) $formData['gross_annual_turnover'];
            
            // Allow small rounding differences (within 1%)
            $difference = abs($auditedTurnover - $enteredTurnover);
            $percentageDifference = ($difference / $auditedTurnover) * 100;
            
            if ($percentageDifference > 1) {
                // If entered turnover is significantly lower than audited, flag for review
                if ($enteredTurnover < $auditedTurnover * 0.95) {
                    $this->addHardStop('gross_annual_turnover', 'The turnover entered does not match the uploaded audited financial statements. Please correct the turnover or upload the correct audited financial statements.');
                    $this->addFraudFlag('gross_annual_turnover', 'Turnover significantly lower than audited financials');
                } else {
                    $this->addManualReview('gross_annual_turnover', 'Turnover differs from audited financials - please verify');
                    $this->addSoftWarning('gross_annual_turnover', 'Turnover entered differs from audited financials');
                }
            }
        }
        
        // Check for tampering on audited financials
        if ($auditedFinancialsValidationData['tampering_detected']) {
            $this->addFraudFlag('audited_financials', 'Potential tampering detected on audited financial statements');
            $this->addManualReview('audited_financials', 'Audited financials require manual verification');
        }
        
        // Validate auditor information
        $this->validateAuditorInformation($extractedData);
        
        // Check for duplicate financial documents
        if (!empty($auditedFinancialsValidationData['sha256'])) {
            $this->checkDuplicateFinancialDocument($auditedFinancialsValidationData['sha256']);
        }
    }
    
    private function validateAuditorInformation(array $extractedData): void
    {
        if (empty($extractedData['auditor_name'])) {
            $this->addSoftWarning('audited_financials', 'Auditor name not clearly detected in financial statements');
        }
        
        if (empty($extractedData['auditor_signature']) && empty($extractedData['auditor_stamp'])) {
            $this->addManualReview('audited_financials', 'Auditor signature or stamp not clearly visible');
        }
        
        // Validate statement date is recent (within 18 months)
        if (!empty($extractedData['statement_date'])) {
            $statementDate = \DateTime::createFromFormat('Y-m-d', $extractedData['statement_date']);
            $monthsSinceStatement = $statementDate->diff(new \DateTime())->m + ($statementDate->diff(new \DateTime())->y * 12);
            
            if ($monthsSinceStatement > 18) {
                $this->addSoftWarning('audited_financials', 'Financial statements are more than 18 months old');
            }
        }
    }
    
    private function validateLevyCalculation(array $formData): void
    {
        if (empty($formData['gross_annual_turnover'])) {
            return;
        }
        
        $turnover = (float) $formData['gross_annual_turnover'];
        $calculatedLevy = $turnover * (float) config('fees.media_house.levy_rate');
        
        // Validate that the levy amount matches calculation
        if (isset($formData['levy_amount'])) {
            $declaredLevy = (float) $formData['levy_amount'];
            $difference = abs($calculatedLevy - $declaredLevy);
            
            if ($difference > 0.01) { // Allow small rounding differences
                $this->addHardStop('levy_amount', 'Levy amount calculation error. Expected: ' . number_format($calculatedLevy, 2));
            }
        } else {
            // Auto-calculate levy if not provided
            $formData['levy_amount'] = $calculatedLevy;
        }
        
        // Store calculated levy for payment processing
        $this->calculatedLevy = $calculatedLevy;
    }
    
    private function checkDuplicateLevy(array $formData): void
    {
        if (empty($formData['registration_number']) || empty($formData['financial_year'])) {
            return;
        }
        
        $existingLevy = \DB::table('levy_records')
            ->where('registration_number', $formData['registration_number'])
            ->where('financial_year', $formData['financial_year'])
            ->whereIn('status', ['pending', 'paid', 'processing'])
            ->first();
        
        if ($existingLevy) {
            if ($existingLevy->status === 'paid') {
                $this->addHardStop('financial_year', 'Levy for this financial year has already been paid');
            } else {
                $this->addManualReview('financial_year', 'Levy for this financial year is already being processed');
            }
        }
    }
    
    private function checkDuplicateFinancialDocument(string $hash): void
    {
        $duplicate = \DB::table('levy_documents')
            ->where('sha256', $hash)
            ->first();
        
        if ($duplicate) {
            // Check if it's for the same media house
            if (isset($this->registrationRecord) && $duplicate->registration_number === $this->registrationRecord->registration_number) {
                $this->addSoftWarning('audited_financials', 'These financial statements have been uploaded before');
            } else {
                // Different media house - potential fraud
                $this->addFraudFlag('audited_financials', 'Financial statements appear to be reused from another media house');
                $this->addHardStop('audited_financials', 'Financial statements appear to be from another entity');
            }
        }
    }
    
    private function validateLevyHistory(array $formData): void
    {
        if (empty($formData['registration_number'])) {
            return;
        }
        
        // Check for outstanding unpaid levies
        $outstandingLevies = \DB::table('levy_records')
            ->where('registration_number', $formData['registration_number'])
            ->where('status', 'pending')
            ->where('financial_year', '<', $formData['financial_year'])
            ->count();
        
        if ($outstandingLevies > 0) {
            $this->addManualReview('registration_number', "There are {$outstandingLevies} outstanding levy payments from previous financial years");
            $this->addSoftWarning('registration_number', 'Please settle outstanding levies from previous years');
        }
        
        // Check payment history patterns
        $recentPayments = \DB::table('levy_records')
            ->where('registration_number', $formData['registration_number'])
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();
        
        if ($recentPayments > 3) {
            $this->addManualReview('registration_number', 'Multiple levy payments in recent months - unusual pattern');
        }
    }
}
