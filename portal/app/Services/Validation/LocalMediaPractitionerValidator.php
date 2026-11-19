<?php

namespace App\Services\Validation;

class LocalMediaPractitionerValidator extends BaseValidator
{
    /**
     * Accreditation categories for local practitioners
     */
    private const CATEGORIES = [
        'full_time' => 'Full-time Local Media Practitioner',
        'freelancer' => 'Freelancer',
        'foreign_media_local' => 'Local Journalist Working for Foreign Media Service',
        'stringer' => 'Local Stringer for Foreign Media Service',
        'local_abroad' => 'Local Journalist Reporting Locally and Abroad',
        'content_creator' => 'Content Creator/Photographer/PR/Digital Media',
        'digital_social' => 'Digital/Social Media Practitioner',
    ];
    
    /**
     * Required fields for all local practitioners
     */
    private const REQUIRED_FIELDS = [
        'title',
        'surname', 
        'first_name',
        'date_of_birth',
        'sex',
        'national_id',
        'nationality',
        'residential_address',
        'phone',
        'email',
        'medium_type',
        'designation',
        'category',
        'declaration'
    ];
    
    /**
     * Category-specific required fields
     */
    private const CATEGORY_SPECIFIC_FIELDS = [
        'full_time' => [
            'employment_letter',
            'organisation_name',
            'employer_address',
            'editor_name',
            'editor_position'
        ],
        'freelancer' => [
            'samples_of_work',
            'referee_details'
        ],
        'foreign_media_local' => [
            'foreign_media_authorisation',
            'foreign_organisation_name',
            'foreign_organisation_address'
        ],
        'stringer' => [
            'stringer_assignment_letter',
            'foreign_organisation_name',
            'contact_person',
            'editor_contact'
        ],
        'local_abroad' => [
            'assignment_details',
            'evidence_local_work',
            'evidence_international_work'
        ],
        'content_creator' => [
            'portfolio_links',
            'client_letters',
            'publication_evidence'
        ],
        'digital_social' => [
            'platform_links',
            'account_ownership_proof'
        ]
    ];
    
    /**
     * Required documents for all local practitioners
     */
    private const REQUIRED_DOCUMENTS = [
        'id_scan',
        'passport_photo'
    ];
    
    /**
     * Category-specific required documents
     */
    private const CATEGORY_SPECIFIC_DOCUMENTS = [
        'full_time' => [
            'employment_letter',
            'educational_certificate'
        ],
        'freelancer' => [
            'reference_letter'
        ],
        'foreign_media_local' => [
            'foreign_media_authorisation_letter'
        ],
        'stringer' => [
            'stringer_assignment_letter'
        ],
        'local_abroad' => [
            'assignment_letter',
            'work_samples'
        ],
        'content_creator' => [
            'portfolio_samples',
            'client_letter'
        ],
        'digital_social' => [
            'platform_screenshot',
            'account_verification'
        ]
    ];
    
    /**
     * Main validation method for local media practitioners
     * 
     * @param array $formData
     * @param array $documents
     * @return array
     */
    public function validate(array $formData, array $documents): array
    {
        $this->resetValidation();
        
        // Validate basic required fields
        $this->validateBasicRequiredFields($formData);
        
        // Validate category-specific requirements
        if (!empty($formData['category'])) {
            $this->validateCategorySpecificFields($formData, $formData['category']);
            $this->validateCategorySpecificDocuments($formData, $documents, $formData['category']);
        }
        
        // Validate documents
        $this->validateRequiredDocuments($documents);
        
        // Validate National ID
        $this->validateNationalID($formData, $documents);
        
        // Validate contact information
        $this->validateContactInformation($formData);
        
        // Validate employment details (if applicable)
        if (!empty($formData['category']) && $formData['category'] === 'full_time') {
            $this->validateEmploymentDetails($formData, $documents);
        }
        
        // Validate certificate (if uploaded) - DISABLED FOR NOW
        // Certificate validation is too strict and rejecting valid certificates
        // Re-enable after tuning the validation logic
        /*
        if (isset($documents['educational_certificate'])) {
            $this->validateCertificate($formData, $documents);
        }
        */
        
        // Check for duplicates
        $this->checkDuplicateApplication($formData, 'accreditation');
        
        // Category-specific validations
        if (!empty($formData['category'])) {
            $this->validateCategorySpecificRules($formData, $documents, $formData['category']);
        }
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'local_media_practitioner');
        
        return $result;
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
        
        // Validate category
        if (isset($formData['category'])) {
            if (!array_key_exists($formData['category'], self::CATEGORIES)) {
                $this->addHardStop('category', 'Please select a valid accreditation category');
            }
        }
        
        // Validate nationality
        if (isset($formData['nationality']) && strtolower($formData['nationality']) !== 'zimbabwean') {
            $this->addSoftWarning('nationality', 'This form is for Zimbabwe nationals only. Foreign practitioners should use the foreign practitioner form.');
        }
        
        // Validate declaration
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
    }
    
    /**
     * Validate category-specific fields
     * 
     * @param array $formData
     * @param string $category
     * @return void
     */
    private function validateCategorySpecificFields(array $formData, string $category): void
    {
        $categoryFields = self::CATEGORY_SPECIFIC_FIELDS[$category] ?? [];
        
        foreach ($categoryFields as $field) {
            $value = $formData[$field] ?? null;
            
            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                $this->addHardStop($field, "The {$field} field is required for this category");
            }
        }
    }
    
    /**
     * Validate category-specific documents
     * 
     * @param array $formData
     * @param array $documents
     * @param string $category
     * @return void
     */
    private function validateCategorySpecificDocuments(array $formData, array $documents, string $category): void
    {
        $categoryDocuments = self::CATEGORY_SPECIFIC_DOCUMENTS[$category] ?? [];
        
        foreach ($categoryDocuments as $documentType) {
            if (!$this->validateDocumentPresent($documents, $documentType, true)) {
                $this->addHardStop($documentType, "The {$documentType} document is required for this category");
            }
        }
    }
    
    /**
     * Validate required documents
     * 
     * @param array $documents
     * @return void
     */
    private function validateRequiredDocuments(array $documents): void
    {
        foreach (self::REQUIRED_DOCUMENTS as $documentType) {
            $this->validateDocumentPresent($documents, $documentType, true);
        }
    }
    
    /**
     * Validate National ID
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateNationalID(array $formData, array $documents): void
    {
        if (empty($formData['national_id'])) {
            return;
        }
        
        // Validate ID format
        $idValidation = $this->normalizationService->validateNationalId($formData['national_id']);
        
        if (!$idValidation['valid']) {
            foreach ($idValidation['errors'] as $error) {
                $this->addHardStop('national_id', $error);
            }
            return;
        }
        
        // Validate ID against uploaded document
        if (isset($documents['id_scan'])) {
            $idValidationData = $this->getDocumentValidationData($documents, 'id_scan');
            
            if ($idValidationData) {
                $extractedId = $idValidationData['extracted_data']['id_number'] ?? null;
                $extractedName = $idValidationData['extracted_data']['name'] ?? null;
                
                // Compare ID numbers
                if ($extractedId) {
                    $idComparison = $this->normalizationService->compareIds(
                        $formData['national_id'],
                        $extractedId,
                        'national_id'
                    );
                    
                    if (!$idComparison['match']) {
                        $this->addHardStop('national_id', 'The National ID number entered does not match the uploaded National ID. Please check whether the ID number was typed correctly or whether the correct ID was uploaded.');
                    }
                }
                
                // Compare names
                if ($extractedName && !empty($formData['surname']) && !empty($formData['first_name'])) {
                    $enteredName = $formData['surname'] . ' ' . $formData['first_name'] . ' ' . ($formData['other_names'] ?? '');
                    $nameComparison = $this->normalizationService->compareNames($enteredName, $extractedName);
                    
                    if ($nameComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('surname', 'The name and surname entered do not match the National ID uploaded. Please check whether the correct National ID was uploaded or whether the name was entered correctly.');
                    } elseif ($nameComparison['match_status'] === 'manual_review') {
                        $this->addManualReview('surname', 'Name on ID requires manual verification due to minor differences');
                        $this->addSoftWarning('surname', $nameComparison['mismatch_reason']);
                    }
                }
                
                // Check OCR confidence
                if ($idValidationData['ocr_confidence'] < 70) {
                    $this->addManualReview('id_scan', 'ID document OCR confidence is low - manual verification recommended');
                }
                
                // Check for tampering
                if ($idValidationData['tampering_detected']) {
                    $this->addFraudFlag('id_scan', 'Potential tampering detected on National ID document');
                    $this->addHardStop('id_scan', 'The uploaded National ID appears to be altered or invalid. Please upload a clear, unaltered National ID.');
                }
            }
        }
        
        // Check for duplicate ID usage
        $this->checkDuplicateID($formData['national_id']);
    }
    
    /**
     * Validate employment details (for full-time practitioners)
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateEmploymentDetails(array $formData, array $documents): void
    {
        if (empty($formData['organisation_name'])) {
            return;
        }
        
        // Validate employment letter
        if (isset($documents['employment_letter'])) {
            $letterValidationData = $this->getDocumentValidationData($documents, 'employment_letter');
            
            if ($letterValidationData) {
                $extractedData = $letterValidationData['extracted_data'];
                
                // Compare employee name
                if (!empty($extractedData['employee_name'])) {
                    $enteredName = $formData['surname'] . ' ' . $formData['first_name'] . ' ' . ($formData['other_names'] ?? '');
                    $nameComparison = $this->normalizationService->compareNames(
                        $extractedData['employee_name'],
                        $enteredName
                    );
                    
                    if ($nameComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('surname', 'The name on the employment letter does not match the applicant name entered. Please upload the correct employment letter or correct the applicant name.');
                    }
                }
                
                // Compare organization name
                if (!empty($extractedData['organisation_name'])) {
                    $orgComparison = $this->normalizationService->compareOrganizationNames(
                        $extractedData['organisation_name'],
                        $formData['organisation_name']
                    );
                    
                    if ($orgComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('organisation_name', 'The organisation name entered does not match the organisation name on the employment letter. Please check the organisation name or upload the correct employment letter.');
                    }
                }
                
                // Check for letterhead
                if (empty($extractedData['letterhead_detected'])) {
                    $this->addManualReview('employment_letter', 'Letterhead not clearly detected on employment letter');
                }
                
                // Check for tampering
                if ($letterValidationData['tampering_detected']) {
                    $this->addFraudFlag('employment_letter', 'Potential tampering detected on employment letter');
                    $this->addManualReview('employment_letter', 'Employment letter requires manual verification');
                }
            }
        }
        
        // Validate organization exists in system
        $this->validateOrganizationExists($formData['organisation_name']);
    }
    
    /**
     * Validate certificate (if uploaded)
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateCertificate(array $formData, array $documents): void
    {
        if (!isset($documents['educational_certificate'])) {
            return;
        }
        
        $certValidationData = $this->getDocumentValidationData($documents, 'educational_certificate');
        
        if ($certValidationData) {
            $extractedData = $certValidationData['extracted_data'];
            
            // Compare names
            if (!empty($extractedData['name'])) {
                $enteredName = $formData['surname'] . ' ' . $formData['first_name'] . ' ' . ($formData['other_names'] ?? '');
                $nameComparison = $this->normalizationService->compareNames(
                    $extractedData['name'],
                    $enteredName
                );
                
                if ($nameComparison['match_status'] === 'hard_stop') {
                    $this->addHardStop('surname', 'The name on the uploaded certificate does not match the applicant name entered. Please check whether the correct certificate was uploaded or whether the applicant name was entered correctly.');
                } elseif ($nameComparison['match_status'] === 'manual_review') {
                    $this->addManualReview('surname', 'Certificate name may indicate maiden name or variation - manual review required');
                    $this->addSoftWarning('surname', 'Certificate name differs from applicant name - may require supporting documentation');
                }
            }
            
            // Check for tampering
            if ($certValidationData['tampering_detected']) {
                $this->addFraudFlag('educational_certificate', 'Potential tampering detected on certificate');
                $this->addManualReview('educational_certificate', 'Certificate requires manual verification');
            }
            
            // Check for duplicate certificate (certificate hash reused)
            if (!empty($certValidationData['sha256'])) {
                $this->checkDuplicateDocumentHash($certValidationData['sha256'], 'educational_certificate');
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
        // Email already validated in basic fields
        // Phone already validated in basic fields
        
        // Check for multiple applications from same email
        if (!empty($formData['email'])) {
            $recentCount = \DB::table('applications')
                ->whereJsonContains('form_data->email', $formData['email'])
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
            
            if ($recentCount > 3) {
                $this->addFraudFlag('email', 'Multiple applications from same email address recently');
                $this->addManualReview('email', 'Multiple recent applications detected');
            }
        }
    }
    
    /**
     * Validate category-specific rules
     * 
     * @param array $formData
     * @param array $documents
     * @param string $category
     * @return void
     */
    private function validateCategorySpecificRules(array $formData, array $documents, string $category): void
    {
        switch ($category) {
            case 'freelancer':
                $this->validateFreelancerRequirements($formData, $documents);
                break;
                
            case 'foreign_media_local':
                $this->validateForeignMediaLocalRequirements($formData, $documents);
                break;
                
            case 'stringer':
                $this->validateStringerRequirements($formData, $documents);
                break;
                
            case 'local_abroad':
                $this->validateLocalAbroadRequirements($formData, $documents);
                break;
                
            case 'content_creator':
                $this->validateContentCreatorRequirements($formData, $documents);
                break;
                
            case 'digital_social':
                $this->validateDigitalSocialRequirements($formData, $documents);
                break;
        }
    }
    
    /**
     * Validate freelancer requirements
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateFreelancerRequirements(array $formData, array $documents): void
    {
        // Freelancers must provide work samples
        if (empty($formData['samples_of_work']) && !isset($documents['work_samples'])) {
            $this->addHardStop('samples_of_work', 'Freelancers must provide samples of past work');
        }
        
        // Validate referee details
        if (!empty($formData['referee_details'])) {
            if (empty($formData['referee_details']['name']) || empty($formData['referee_details']['contact'])) {
                $this->addHardStop('referee_details', 'Referee name and contact are required');
            }
        }
    }
    
    /**
     * Validate foreign media local requirements
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateForeignMediaLocalRequirements(array $formData, array $documents): void
    {
        // Must provide foreign media authorisation
        if (!isset($documents['foreign_media_authorisation_letter'])) {
            $this->addHardStop('foreign_media_authorisation', 'Foreign media authorisation letter is required');
        }
        
        // Check if foreign organisation is registered
        if (!empty($formData['foreign_organisation_name'])) {
            $this->validateForeignOrganizationExists($formData['foreign_organisation_name']);
        }
        
        // Flag for officer review
        $this->addManualReview('category', 'Local journalists working for foreign media require officer review');
    }
    
    /**
     * Validate stringer requirements
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateStringerRequirements(array $formData, array $documents): void
    {
        // Must provide stringer assignment letter
        if (!isset($documents['stringer_assignment_letter'])) {
            $this->addHardStop('stringer_assignment_letter', 'Stringer assignment letter is required');
        }
        
        // Must have contact person
        if (empty($formData['contact_person']) || empty($formData['editor_contact'])) {
            $this->addHardStop('contact_person', 'Contact person and editor details are required');
        }
        
        // Flag for manual review
        $this->addManualReview('category', 'Stringer applications require manual verification');
    }
    
    /**
     * Validate local abroad requirements
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateLocalAbroadRequirements(array $formData, array $documents): void
    {
        // Must provide evidence of both local and international work
        if (empty($formData['evidence_local_work']) || empty($formData['evidence_international_work'])) {
            $this->addHardStop('evidence_local_work', 'Evidence of both local and international reporting work is required');
        }
        
        // Validate assignment details
        if (empty($formData['assignment_details'])) {
            $this->addHardStop('assignment_details', 'Assignment details are required');
        }
    }
    
    /**
     * Validate content creator requirements
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateContentCreatorRequirements(array $formData, array $documents): void
    {
        // Must provide portfolio or samples
        if (empty($formData['portfolio_links']) && !isset($documents['portfolio_samples'])) {
            $this->addHardStop('portfolio_links', 'Portfolio samples or links are required');
        }
        
        // Validate platform links if provided
        if (!empty($formData['portfolio_links'])) {
            $this->validatePlatformLinks($formData['portfolio_links'], $formData);
        }
    }
    
    /**
     * Validate digital social requirements
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateDigitalSocialRequirements(array $formData, array $documents): void
    {
        // Must provide platform links
        if (empty($formData['platform_links'])) {
            $this->addHardStop('platform_links', 'Platform links are required for digital/social media practitioners');
        }
        
        // Validate platform links
        $this->validatePlatformLinks($formData['platform_links'], $formData);
        
        // Check for account ownership proof
        if (!isset($documents['account_verification'])) {
            $this->addManualReview('platform_links', 'Account ownership verification not provided');
        }
        
        // Check for newly created accounts (suspicious)
        if (!empty($formData['platform_links'])) {
            foreach ($formData['platform_links'] as $link) {
                // This would require API integration with platforms
                // For now, flag for manual review if account appears new
                $this->addSoftWarning('platform_links', 'Platform account verification may require manual review');
            }
        }
    }
    
    /**
     * Validate platform links
     * 
     * @param array $links
     * @param array $formData
     * @return void
     */
    private function validatePlatformLinks(array $links, array $formData): void
    {
        foreach ($links as $link) {
            if (empty($link)) {
                continue;
            }
            
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                $this->addHardStop('platform_links', 'Invalid platform URL provided');
                continue;
            }
            
            // Check if platform name matches applicant or brand
            // This would require API integration to verify
            $this->addSoftWarning('platform_links', 'Platform identity verification may require manual review');
        }
    }
    
    /**
     * Check for duplicate ID usage
     * 
     * @param string $nationalId
     * @return void
     */
    private function checkDuplicateID(string $nationalId): void
    {
        $normalizedId = $this->normalizationService->normalizeNationalId($nationalId);
        
        $duplicate = \DB::table('applications')
            ->whereJsonContains('form_data->national_id', $normalizedId)
            ->whereIn('status', ['draft', 'submitted', 'officer_review', 'registrar_review'])
            ->first();
        
        if ($duplicate) {
            $this->addFraudFlag('national_id', 'National ID already used in another application');
            
            // Check if names match
            $duplicateName = $duplicate->form_data['surname'] . ' ' . $duplicate->form_data['first_name'] ?? '';
            $currentName = $formData['surname'] ?? '' . ' ' . $formData['first_name'] ?? '';
            
            $nameComparison = $this->normalizationService->compareNames($duplicateName, $currentName);
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $this->addHardStop('national_id', 'This National ID is already associated with a different applicant');
            } else {
                $this->addManualReview('national_id', 'National ID appears in previous application');
            }
        }
    }
    
    /**
     * Check for duplicate document hash
     * 
     * @param string $hash
     * @param string $documentType
     * @return void
     */
    private function checkDuplicateDocumentHash(string $hash, string $documentType): void
    {
        $duplicate = \DB::table('application_documents')
            ->where('sha256', $hash)
            ->where('doc_type', $documentType)
            ->first();
        
        if ($duplicate) {
            $this->addFraudFlag($documentType, 'Document hash matches previously uploaded document');
            
            // Check if it's from the same applicant
            if ($duplicate->application_id !== $this->applicationId ?? null) {
                $this->addManualReview($documentType, 'Document appears to be reused from another application');
            }
        }
    }
    
    /**
     * Validate organization exists in system
     * 
     * @param string $organizationName
     * @return void
     */
    private function validateOrganizationExists(string $organizationName): void
    {
        $normOrg = $this->normalizationService->normalizeOrganizationName($organizationName);
        
        $exists = \DB::table('applications')
            ->where('application_type', 'media_house')
            ->where('form_data->company_name', 'like', '%' . $normOrg['normalized'] . '%')
            ->whereIn('status', ['approved', 'issued', 'printed'])
            ->exists();
        
        if (!$exists) {
            $this->addManualReview('organisation_name', 'Organisation not found in registered media houses');
            $this->addSoftWarning('organisation_name', 'Employer verification may require additional documentation');
        }
    }
    
    /**
     * Validate foreign organization exists
     * 
     * @param string $organizationName
     * @return void
     */
    private function validateForeignOrganizationExists(string $organizationName): void
    {
        // Check if foreign organization has representative office
        $hasRepOffice = \DB::table('applications')
            ->where('application_type', 'ap4')
            ->where('form_data->foreign_mass_media_service', 'like', '%' . $organizationName . '%')
            ->whereIn('status', ['approved', 'issued', 'printed'])
            ->exists();
        
        if ($hasRepOffice) {
            $this->addSoftWarning('foreign_organisation_name', 'Foreign organisation has registered representative office in Zimbabwe');
        } else {
            $this->addManualReview('foreign_organisation_name', 'Foreign organisation verification required');
        }
    }
}