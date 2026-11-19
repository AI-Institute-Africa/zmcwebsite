<?php

namespace App\Services\Validation;

class ForeignMediaPractitionerValidator extends BaseValidator
{
    /**
     * Application types for foreign practitioners
     */
    private const APPLICATION_TYPES = ['new', 'renewal', 'temporary', 'replacement'];
    
    /**
     * Required fields for foreign practitioners
     */
    private const REQUIRED_FIELDS = [
        'application_type',
        'title',
        'surname',
        'first_name',
        'date_of_birth',
        'place_of_birth',
        'country_of_birth',
        'sex',
        'nationality',
        'passport_number',
        'passport_expiry_date',
        'passport_place_of_issue',
        'phone',
        'email',
        'medium_type',
        'designation',
        'media_organisation',
        'employer_address',
        'country_based',
        'arrival_date',
        'port_of_entry',
        'departure_date',
        'special_assignment',
        'declaration'
    ];
    
    /**
     * Required documents for foreign practitioners
     */
    private const REQUIRED_DOCUMENTS = [
        'passport_biodata_page',
        'passport_photo',
        'assignment_letter'
    ];
    
    /**
     * Additional documents based on application type
     */
    private const TYPE_SPECIFIC_DOCUMENTS = [
        'renewal' => [
            'current_card'
        ],
        'temporary' => [
            'work_permit',
            'immigration_clearance'
        ],
        'replacement' => [
            'lost_card_affidavit'
        ]
    ];
    
    /**
     * Main validation method for foreign media practitioners
     * 
     * @param array $formData
     * @param array $documents
     * @return array
     */
    public function validate(array $formData, array $documents): array
    {
        $this->resetValidation();
        
        // Validate application type
        $this->validateApplicationType($formData);
        
        // Validate basic required fields
        $this->validateBasicRequiredFields($formData);
        
        // Validate type-specific fields
        if (!empty($formData['application_type'])) {
            $this->validateTypeSpecificFields($formData, $formData['application_type']);
        }
        
        // Validate documents
        $this->validateRequiredDocuments($documents, $formData['application_type'] ?? 'new');
        
        // Validate passport
        $this->validatePassport($formData, $documents);
        
        // Validate assignment and employer
        $this->validateAssignmentAndEmployer($formData, $documents);
        
        // Validate dates
        $this->validateDates($formData);
        
        // Validate contact information
        $this->validateContactInformation($formData);
        
        // Check for duplicates
        $this->checkDuplicateApplication($formData, 'foreign_accreditation');
        
        // Validate against foreign representative office records
        $this->validateAgainstRepresentativeOffice($formData);
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'foreign_media_practitioner');
        
        return $result;
    }
    
    /**
     * Validate application type
     * 
     * @param array $formData
     * @return void
     */
    private function validateApplicationType(array $formData): void
    {
        if (empty($formData['application_type'])) {
            $this->addHardStop('application_type', 'Application type is required');
            return;
        }
        
        if (!in_array($formData['application_type'], self::APPLICATION_TYPES)) {
            $this->addHardStop('application_type', 'Invalid application type selected');
        }
    }
    
    /**
     * Validate basic required fields
     * 
     * @param array $formData
     * @return void
     */
    private function validateBasicRequiredFields(array $formData): void
    {
        $missing = $this->validateRequiredFields($formData, self::REQUIRED_FIELDS);
        
        if (!empty($missing)) {
            return; // Stop here if required fields are missing
        }
        
        // Validate specific field formats
        if (isset($formData['email'])) {
            $this->validateEmail($formData['email']);
        }
        
        if (isset($formData['phone'])) {
            $this->validatePhone($formData['phone']);
        }
        
        if (isset($formData['date_of_birth'])) {
            $this->validatePastDate($formData['date_of_birth'], 'date_of_birth');
        }
        
        if (isset($formData['passport_expiry_date'])) {
            if (!$this->validateDate($formData['passport_expiry_date'], 'passport_expiry_date')) {
                return;
            }
            
            // Passport must not be expired
            $expiryDate = \DateTime::createFromFormat('Y-m-d', $formData['passport_expiry_date']);
            if ($expiryDate < new \DateTime()) {
                $this->addHardStop('passport_expiry_date', 'Passport has expired. Please upload a valid passport.');
            }
        }
        
        // Validate nationality
        if (isset($formData['nationality']) && strtolower($formData['nationality']) === 'zimbabwean') {
            $this->addSoftWarning('nationality', 'This form is for foreign nationals. Zimbabwean nationals should use the local practitioner form.');
        }
        
        // Validate declaration
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
    }
    
    /**
     * Validate type-specific fields
     * 
     * @param array $formData
     * @param string $applicationType
     * @return void
     */
    private function validateTypeSpecificFields(array $formData, string $applicationType): void
    {
        switch ($applicationType) {
            case 'renewal':
                if (empty($formData['previous_accreditation_number'])) {
                    $this->addHardStop('previous_accreditation_number', 'Previous accreditation number is required for renewal');
                } else {
                    $this->validatePreviousAccreditation($formData['previous_accreditation_number']);
                }
                break;
                
            case 'replacement':
                if (empty($formData['previous_accreditation_number'])) {
                    $this->addHardStop('previous_accreditation_number', 'Previous accreditation number is required for replacement');
                }
                if (empty($formData['replacement_reason'])) {
                    $this->addHardStop('replacement_reason', 'Reason for replacement is required');
                }
                break;
                
            case 'temporary':
                if (empty($formData['work_permit_number'])) {
                    $this->addHardStop('work_permit_number', 'Work permit number is required for temporary accreditation');
                }
                break;
        }
    }
    
    /**
     * Validate required documents
     * 
     * @param array $documents
     * @param string $applicationType
     * @return void
     */
    private function validateRequiredDocuments(array $documents, string $applicationType): void
    {
        // Always required documents
        foreach (self::REQUIRED_DOCUMENTS as $documentType) {
            $this->validateDocumentPresent($documents, $documentType, true);
        }
        
        // Type-specific documents
        $typeDocuments = self::TYPE_SPECIFIC_DOCUMENTS[$applicationType] ?? [];
        foreach ($typeDocuments as $documentType) {
            $this->validateDocumentPresent($documents, $documentType, true);
        }
    }
    
    /**
     * Validate passport
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validatePassport(array $formData, array $documents): void
    {
        if (empty($formData['passport_number'])) {
            return;
        }
        
        // Validate passport format
        $passportValidation = $this->normalizationService->validatePassportNumber($formData['passport_number']);
        
        if (!$passportValidation['valid']) {
            foreach ($passportValidation['errors'] as $error) {
                $this->addHardStop('passport_number', $error);
            }
            return;
        }
        
        // Validate passport against uploaded document
        if (isset($documents['passport_biodata_page'])) {
            $passportValidationData = $this->getDocumentValidationData($documents, 'passport_biodata_page');
            
            if ($passportValidationData) {
                $extractedData = $passportValidationData['extracted_data'];
                
                // Compare passport numbers
                if (!empty($extractedData['passport_number'])) {
                    $passportComparison = $this->normalizationService->compareIds(
                        $formData['passport_number'],
                        $extractedData['passport_number'],
                        'passport'
                    );
                    
                    if (!$passportComparison['match']) {
                        $this->addHardStop('passport_number', 'The passport number entered does not match the uploaded passport. Please check whether the passport number was typed correctly or whether the correct passport was uploaded.');
                    }
                }
                
                // Compare names
                if (!empty($extractedData['full_name']) && !empty($formData['surname']) && !empty($formData['first_name'])) {
                    $enteredName = $formData['surname'] . ' ' . $formData['first_name'] . ' ' . ($formData['other_names'] ?? '');
                    $nameComparison = $this->normalizationService->compareNames($enteredName, $extractedData['full_name']);
                    
                    if ($nameComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('surname', 'The applicant name entered does not match the uploaded passport. Please check the name entered or upload the correct passport.');
                    } elseif ($nameComparison['match_status'] === 'manual_review') {
                        $this->addManualReview('surname', 'Name on passport requires manual verification due to minor differences');
                    }
                }
                
                // Compare nationality
                if (!empty($extractedData['nationality']) && !empty($formData['nationality'])) {
                    $enteredNationality = strtoupper($formData['nationality']);
                    $extractedNationality = strtoupper($extractedData['nationality']);
                    
                    if ($enteredNationality !== $extractedNationality) {
                        $this->addHardStop('nationality', 'Nationality does not match passport');
                    }
                }
                
                // Compare date of birth
                if (!empty($extractedData['date_of_birth']) && !empty($formData['date_of_birth'])) {
                    if ($formData['date_of_birth'] !== $extractedData['date_of_birth']) {
                        $this->addHardStop('date_of_birth', 'Date of birth does not match passport');
                    }
                }
                
                // Compare sex
                if (!empty($extractedData['sex']) && !empty($formData['sex'])) {
                    if (strtoupper($formData['sex']) !== strtoupper($extractedData['sex'])) {
                        $this->addHardStop('sex', 'Sex does not match passport');
                    }
                }
                
                // Validate passport expiry date matches document
                if (!empty($extractedData['expiry_date']) && !empty($formData['passport_expiry_date'])) {
                    if ($formData['passport_expiry_date'] !== $extractedData['expiry_date']) {
                        $this->addHardStop('passport_expiry_date', 'Passport expiry date does not match passport');
                    }
                }
                
                // Check OCR confidence
                if ($passportValidationData['ocr_confidence'] < 70) {
                    $this->addManualReview('passport_biodata_page', 'Passport OCR confidence is low - manual verification recommended');
                }
                
                // Check for tampering
                if ($passportValidationData['tampering_detected']) {
                    $this->addFraudFlag('passport_biodata_page', 'Potential tampering detected on passport');
                    $this->addHardStop('passport_biodata_page', 'The uploaded passport appears to be altered or invalid. Please upload a clear, unaltered passport.');
                }
            }
        }
        
        // Validate accreditation period does not exceed passport validity
        if (!empty($formData['passport_expiry_date']) && !empty($formData['departure_date'])) {
            $passportExpiry = \DateTime::createFromFormat('Y-m-d', $formData['passport_expiry_date']);
            $departureDate = \DateTime::createFromFormat('Y-m-d', $formData['departure_date']);
            
            if ($departureDate > $passportExpiry) {
                $this->addHardStop('departure_date', 'Departure date cannot exceed passport expiry date');
            }
        }
        
        // Check for duplicate passport usage
        $this->checkDuplicatePassport($formData['passport_number']);
    }
    
    /**
     * Validate assignment and employer
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateAssignmentAndEmployer(array $formData, array $documents): void
    {
        if (empty($formData['media_organisation'])) {
            return;
        }
        
        // Validate assignment letter
        if (isset($documents['assignment_letter'])) {
            $assignmentValidationData = $this->getDocumentValidationData($documents, 'assignment_letter');
            
            if ($assignmentValidationData) {
                $extractedData = $assignmentValidationData['extracted_data'];
                
                // Compare journalist name
                if (!empty($extractedData['journalist_name'])) {
                    $enteredName = $formData['surname'] . ' ' . $formData['first_name'] . ' ' . ($formData['other_names'] ?? '');
                    $nameComparison = $this->normalizationService->compareNames(
                        $extractedData['journalist_name'],
                        $enteredName
                    );
                    
                    if ($nameComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('surname', 'Journalist name on assignment letter does not match applicant name');
                    }
                }
                
                // Compare media organisation
                if (!empty($extractedData['media_organisation'])) {
                    $orgComparison = $this->normalizationService->compareOrganizationNames(
                        $extractedData['media_organisation'],
                        $formData['media_organisation']
                    );
                    
                    if ($orgComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('media_organisation', 'Media organisation on assignment letter does not match entered organisation');
                    }
                }
                
                // Validate assignment dates
                if (!empty($extractedData['assignment_dates'])) {
                    $this->validateAssignmentDates($formData, $extractedData['assignment_dates']);
                }
                
                // Check for letterhead
                if (empty($extractedData['letterhead_detected'])) {
                    $this->addManualReview('assignment_letter', 'Letterhead not clearly detected on assignment letter');
                }
                
                // Check for tampering
                if ($assignmentValidationData['tampering_detected']) {
                    $this->addFraudFlag('assignment_letter', 'Potential tampering detected on assignment letter');
                    $this->addManualReview('assignment_letter', 'Assignment letter requires manual verification');
                }
            }
        }
        
        // Validate assignment description
        if (empty($formData['special_assignment'])) {
            $this->addHardStop('special_assignment', 'Special assignment description is required');
        } else {
            // Check if assignment is media-related
            $mediaKeywords = ['journalist', 'report', 'news', 'media', 'coverage', 'story', 'interview', 'documentary'];
            $assignmentLower = strtolower($formData['special_assignment']);
            $hasMediaKeyword = false;
            
            foreach ($mediaKeywords as $keyword) {
                if (str_contains($assignmentLower, $keyword)) {
                    $hasMediaKeyword = true;
                    break;
                }
            }
            
            if (!$hasMediaKeyword) {
                $this->addManualReview('special_assignment', 'Assignment description does not appear to be media-related');
            }
        }
    }
    
    /**
     * Validate dates
     * 
     * @param array $formData
     * @return void
     */
    private function validateDates(array $formData): void
    {
        // Validate arrival date
        if (isset($formData['arrival_date'])) {
            if (!$this->validateDate($formData['arrival_date'], 'arrival_date')) {
                return;
            }
            
            // Arrival date should be in the future or recent past
            $arrivalDate = \DateTime::createFromFormat('Y-m-d', $formData['arrival_date']);
            $today = new \DateTime();
            $daysDiff = $today->diff($arrivalDate)->days;
            
            if ($arrivalDate < $today && $daysDiff > 30) {
                $this->addSoftWarning('arrival_date', 'Arrival date is more than 30 days in the past');
            }
        }
        
        // Validate departure date
        if (isset($formData['departure_date'])) {
            if (!$this->validateDate($formData['departure_date'], 'departure_date')) {
                return;
            }
            
            // Departure must be after arrival
            if (isset($formData['arrival_date'])) {
                $arrivalDate = \DateTime::createFromFormat('Y-m-d', $formData['arrival_date']);
                $departureDate = \DateTime::createFromFormat('Y-m-d', $formData['departure_date']);
                
                if ($departureDate <= $arrivalDate) {
                    $this->addHardStop('departure_date', 'Departure date must be after arrival date');
                }
            }
            
            // Departure should be in the future
            if (!$this->validateFutureDate($formData['departure_date'], 'departure_date')) {
                $this->addHardStop('departure_date', 'Departure date must be in the future');
            }
        }
        
        // Validate assignment duration
        if (isset($formData['arrival_date']) && isset($formData['departure_date'])) {
            $arrivalDate = \DateTime::createFromFormat('Y-m-d', $formData['arrival_date']);
            $departureDate = \DateTime::createFromFormat('Y-m-d', $formData['departure_date']);
            $duration = $arrivalDate->diff($departureDate)->days;
            
            // Flag unusually long assignments
            if ($duration > 365) {
                $this->addManualReview('departure_date', 'Assignment duration exceeds one year - may require additional approval');
            }
        }
    }
    
    /**
     * Validate assignment dates against document
     * 
     * @param array $formData
     * @param string $documentDates
     * @return void
     */
    private function validateAssignmentDates(array $formData, string $documentDates): void
    {
        // This would parse the dates from the document and compare
        // For now, flag for manual review if dates don't match exactly
        if (!empty($formData['arrival_date']) && !empty($formData['departure_date'])) {
            $enteredDates = $formData['arrival_date'] . ' to ' . $formData['departure_date'];
            
            if (strtolower($documentDates) !== strtolower($enteredDates)) {
                $this->addManualReview('arrival_date', 'Assignment dates on letter may differ from entered dates');
            }
        }
    }
    
    /**
     * Validate contact information
     * 
     * @param array $formData
     * @return void
     */
    private function validateContactInformation(array $formData): void
    {
        // Email and phone already validated in basic fields
        
        // Validate country where journalist is based
        if (!empty($formData['country_based'])) {
            $countryLower = strtolower($formData['country_based']);
            if ($countryLower === 'zimbabwe' || $countryLower === 'zim') {
                $this->addSoftWarning('country_based', 'Journalist based in Zimbabwe may need different application type');
            }
        }
        
        // Check for multiple applications from same email
        if (!empty($formData['email'])) {
            $recentCount = \DB::table('applications')
                ->whereJsonContains('form_data->email', $formData['email'])
                ->where('created_at', '>=', now()->subDays(90))
                ->count();
            
            if ($recentCount > 2) {
                $this->addFraudFlag('email', 'Multiple foreign applications from same email address recently');
                $this->addManualReview('email', 'Multiple recent foreign applications detected');
            }
        }
    }
    
    /**
     * Validate previous accreditation (for renewals/replacements)
     * 
     * @param string $accreditationNumber
     * @return void
     */
    private function validatePreviousAccreditation(string $accreditationNumber): void
    {
        $previous = \DB::table('accreditation_records')
            ->where('accreditation_number', $accreditationNumber)
            ->first();
        
        if (!$previous) {
            $this->addHardStop('previous_accreditation_number', 'Previous accreditation number not found');
            return;
        }
        
        // Check if previous accreditation belongs to same person (basic check)
        // In production, this would verify name match with stored record
        $this->addManualReview('previous_accreditation_number', 'Previous accreditation verification may require manual review');
    }
    
    /**
     * Check for duplicate passport usage
     * 
     * @param string $passportNumber
     * @return void
     */
    private function checkDuplicatePassport(string $passportNumber): void
    {
        $normalizedPassport = $this->normalizationService->normalizePassportNumber($passportNumber);
        
        $duplicate = \DB::table('applications')
            ->whereJsonContains('form_data->passport_number', $normalizedPassport)
            ->whereIn('status', ['draft', 'submitted', 'officer_review', 'registrar_review'])
            ->first();
        
        if ($duplicate) {
            $this->addFraudFlag('passport_number', 'Passport number already used in another application');
            
            // Check if names match
            $duplicateName = $duplicate->form_data['surname'] . ' ' . $duplicate->form_data['first_name'] ?? '';
            $currentName = $formData['surname'] ?? '' . ' ' . $formData['first_name'] ?? '';
            
            $nameComparison = $this->normalizationService->compareNames($duplicateName, $currentName);
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $this->addHardStop('passport_number', 'This passport is already associated with a different applicant');
            } else {
                $this->addManualReview('passport_number', 'Passport appears in previous application');
            }
        }
    }
    
    /**
     * Validate against foreign representative office records
     * 
     * @param array $formData
     * @return void
     */
    private function validateAgainstRepresentativeOffice(array $formData): void
    {
        if (empty($formData['media_organisation'])) {
            return;
        }
        
        // Check if foreign organisation has registered representative office
        $repOffice = \DB::table('applications')
            ->where('application_type', 'ap4')
            ->where('form_data->foreign_mass_media_service', 'like', '%' . $formData['media_organisation'] . '%')
            ->whereIn('status', ['approved', 'issued', 'printed'])
            ->first();
        
        if ($repOffice) {
            $this->addSoftWarning('media_organisation', 'Foreign media organisation has registered representative office in Zimbabwe');
            
            // Link the journalist to the representative office
            $this->addManualReview('media_organisation', 'Should link to registered representative office: ' . $repOffice->reference);
        } else {
            $this->addManualReview('media_organisation', 'Foreign organisation verification required - no representative office found');
        }
    }
}