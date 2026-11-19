<?php

namespace App\Services\Validation;

class LocalMediaHouseValidator extends BaseValidator
{
    /**
     * Media activity types
     */
    private const MEDIA_ACTIVITIES = [
        'newspaper',
        'magazine',
        'online_news',
        'television',
        'radio',
        'community_media',
        'advertising_agency',
        'production_house',
        'media_service_multiple'
    ];
    
    /**
     * Required fields for mass media service (AP1)
     */
    private const AP1_REQUIRED_FIELDS = [
        'contact_person_name',
        'contact_person_address',
        'contact_person_phone',
        'contact_person_email',
        'applicant_name',
        'type_of_mass_media_activity',
        'titles_published',
        'frequency',
        'circulation_figures',
        'general_news_or_specialised',
        'head_office_address',
        'ceo_details',
        'declaration'
    ];
    
    /**
     * Director-related required fields
     */
    private const DIRECTOR_REQUIRED_FIELDS = [
        'directors' // Array of director details
    ];
    
    /**
     * Required documents for media house registration
     */
    private const REQUIRED_DOCUMENTS = [
        'certificate_of_incorporation',
        'memorandum_articles',
        'cr14_director_return',
        'mission_statement',
        'editorial_charter',
        'code_of_ethics',
        'code_of_conduct'
    ];
    
    /**
     * Financial documents
     */
    private const FINANCIAL_DOCUMENTS = [
        'cash_flow_statement',
        'balance_sheet'
    ];
    
    /**
     * Main validation method for local media houses (AP1)
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
        
        // Validate directors
        if (!empty($formData['directors'])) {
            $this->validateDirectors($formData, $documents);
        }
        
        // Validate documents
        $this->validateRequiredDocuments($documents);
        
        // Validate financial documents
        $this->validateFinancialDocuments($formData, $documents);
        
        // Validate company name against documents
        $this->validateCompanyName($formData, $documents);
        
        // Validate media activity details
        $this->validateMediaActivityDetails($formData);
        
        // Validate CEO and senior management
        $this->validateManagementDetails($formData);
        
        // Validate shareholding structure
        $this->validateShareholdingStructure($formData);
        
        // Validate declarations
        $this->validateDeclarations($formData);
        
        // Check for duplicates
        $this->checkDuplicateApplication($formData, 'media_house');
        
        // Validate against CR14
        $this->validateAgainstCR14($formData, $documents);
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'local_media_house_ap1');
        
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
        $missing = $this->validateRequiredFields($formData, self::AP1_REQUIRED_FIELDS);
        
        if (!empty($missing)) {
            return; // Stop here if required fields are missing
        }
        
        // Validate specific field formats
        if (isset($formData['contact_person_email'])) {
            $this->validateEmail($formData['contact_person_email'], 'contact_person_email');
        }
        
        if (isset($formData['contact_person_phone'])) {
            $this->validatePhone($formData['contact_person_phone'], 'contact_person_phone');
        }
        
        // Validate media activity type
        if (isset($formData['type_of_mass_media_activity'])) {
            if (!in_array($formData['type_of_mass_media_activity'], self::MEDIA_ACTIVITIES)) {
                $this->addHardStop('type_of_mass_media_activity', 'Invalid media activity type selected');
            }
        }
        
        // Validate declaration
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
        
        // Validate frequency is reasonable
        if (isset($formData['frequency'])) {
            $frequency = strtolower($formData['frequency']);
            $validFrequencies = ['daily', 'weekly', 'monthly', 'quarterly', 'annually', 'irregular'];
            
            if (!in_array($frequency, $validFrequencies)) {
                $this->addSoftWarning('frequency', 'Publication frequency should be standard (daily, weekly, monthly, etc.)');
            }
        }
        
        // Validate circulation figures
        if (isset($formData['circulation_figures'])) {
            if (!is_numeric($formData['circulation_figures']) || $formData['circulation_figures'] < 0) {
                $this->addHardStop('circulation_figures', 'Circulation figures must be a positive number');
            }
        }
    }
    
    /**
     * Validate directors
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateDirectors(array $formData, array $documents): void
    {
        if (!isset($formData['directors']) || !is_array($formData['directors']) || empty($formData['directors'])) {
            $this->addHardStop('directors', 'At least one director is required');
            return;
        }
        
        foreach ($formData['directors'] as $index => $director) {
            // Validate required director fields
            if (empty($director['name'])) {
                $this->addHardStop("directors.{$index}.name", "Director name is required");
            }
            
            if (empty($director['national_id']) && empty($director['passport_number'])) {
                $this->addHardStop("directors.{$index}.national_id", "Director ID or passport number is required");
            }
            
            if (empty($director['address'])) {
                $this->addHardStop("directors.{$index}.address", "Director address is required");
            }
            
            if (empty($director['occupation'])) {
                $this->addHardStop("directors.{$index}.occupation", "Director occupation is required");
            }
            
            // Validate director ID if provided
            if (!empty($director['national_id'])) {
                $idValidation = $this->normalizationService->validateNationalId($director['national_id']);
                if (!$idValidation['valid']) {
                    foreach ($idValidation['errors'] as $error) {
                        $this->addHardStop("directors.{$index}.national_id", $error);
                    }
                }
            }
            
            // Check for duplicate director IDs across directors
            if (!empty($director['national_id'])) {
                $this->checkDuplicateDirectorID($formData['directors'], $director['national_id'], $index);
            }
        }
        
        // Validate directors against CR14
        $this->validateDirectorsAgainstCR14($formData, $documents);
        
        // Validate director documents
        $this->validateDirectorDocuments($formData, $documents);
    }
    
    /**
     * Check for duplicate director IDs
     * 
     * @param array $directors
     * @param string $idNumber
     * @param int $currentIndex
     * @return void
     */
    private function checkDuplicateDirectorID(array $directors, string $idNumber, int $currentIndex): void
    {
        $normalizedId = $this->normalizationService->normalizeNationalId($idNumber);
        
        foreach ($directors as $index => $director) {
            if ($index === $currentIndex) {
                continue;
            }
            
            if (!empty($director['national_id'])) {
                $directorNormalizedId = $this->normalizationService->normalizeNationalId($director['national_id']);
                
                if ($directorNormalizedId === $normalizedId) {
                    $this->addHardStop("directors.{$index}.national_id", 'The same National ID cannot be used for multiple directors');
                    $this->addFraudFlag('directors', 'Duplicate director ID detected');
                }
            }
        }
    }
    
    /**
     * Validate directors against CR14
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateDirectorsAgainstCR14(array $formData, array $documents): void
    {
        if (!isset($documents['cr14_director_return'])) {
            return;
        }
        
        $cr14ValidationData = $this->getDocumentValidationData($documents, 'cr14_director_return');
        
        if ($cr14ValidationData) {
            $extractedDirectors = $cr14ValidationData['extracted_data']['directors'] ?? [];
            
            if (empty($extractedDirectors)) {
                $this->addManualReview('cr14_director_return', 'Could not extract director information from CR14');
                return;
            }
            
            $formDirectors = $formData['directors'] ?? [];
            
            // Check if all directors in form are in CR14
            foreach ($formDirectors as $director) {
                $foundInCR14 = false;
                
                foreach ($extractedDirectors as $cr14Director) {
                    $nameComparison = $this->normalizationService->compareNames(
                        $director['name'],
                        $cr14Director['name'] ?? ''
                    );
                    
                    if ($nameComparison['match_status'] !== 'hard_stop') {
                        $foundInCR14 = true;
                        break;
                    }
                }
                
                if (!$foundInCR14) {
                    $this->addHardStop('directors', 'Director listed in form not found in CR14 document');
                }
            }
            
            // Check if all directors in CR14 are in form
            foreach ($extractedDirectors as $cr14Director) {
                $foundInForm = false;
                
                foreach ($formDirectors as $director) {
                    $nameComparison = $this->normalizationService->compareNames(
                        $director['name'],
                        $cr14Director['name'] ?? ''
                    );
                    
                    if ($nameComparison['match_status'] !== 'hard_stop') {
                        $foundInForm = true;
                        break;
                    }
                }
                
                if (!$foundInForm) {
                    $this->addManualReview('directors', 'Director in CR14 not listed in form - may require explanation');
                }
            }
        }
    }
    
    /**
     * Validate director documents
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateDirectorDocuments(array $formData, array $documents): void
    {
        $directors = $formData['directors'] ?? [];
        
        foreach ($directors as $index => $director) {
            $docKey = "director_{$index}_id";
            
            if (isset($documents[$docKey])) {
                $directorDocValidationData = $this->getDocumentValidationData($documents, $docKey);
                
                if ($directorDocValidationData) {
                    $extractedName = $directorDocValidationData['extracted_data']['name'] ?? '';
                    $extractedID = $directorDocValidationData['extracted_data']['id_number'] ?? '';
                    
                    // Validate name matches
                    if (!empty($extractedName)) {
                        $nameComparison = $this->normalizationService->compareNames(
                            $director['name'],
                            $extractedName
                        );
                        
                        if ($nameComparison['match_status'] === 'hard_stop') {
                            $this->addHardStop("directors.{$index}.name", 'Director name does not match uploaded ID');
                        }
                    }
                    
                    // Validate ID matches
                    if (!empty($extractedID) && !empty($director['national_id'])) {
                        $idComparison = $this->normalizationService->compareIds(
                            $director['national_id'],
                            $extractedID,
                            'national_id'
                        );
                        
                        if (!$idComparison['match']) {
                            $this->addHardStop("directors.{$index}.national_id", 'Director ID number does not match uploaded document');
                        }
                    }
                    
                    // Check for tampering
                    if ($directorDocValidationData['tampering_detected']) {
                        $this->addFraudFlag($docKey, 'Potential tampering detected on director ID');
                        $this->addManualReview($docKey, 'Director ID requires manual verification');
                    }
                }
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
     * Validate financial documents
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateFinancialDocuments(array $formData, array $documents): void
    {
        foreach (self::FINANCIAL_DOCUMENTS as $documentType) {
            if (!$this->validateDocumentPresent($documents, $documentType, true)) {
                $this->addHardStop($documentType, "Financial documents are required for media house registration");
            }
        }
        
        // Validate cash flow statement
        if (isset($documents['cash_flow_statement'])) {
            $this->validateFinancialStatement($formData, $documents, 'cash_flow_statement');
        }
        
        // Validate balance sheet
        if (isset($documents['balance_sheet'])) {
            $this->validateFinancialStatement($formData, $documents, 'balance_sheet');
        }
    }
    
    /**
     * Validate financial statement
     * 
     * @param array $formData
     * @param array $documents
     * @param string $documentType
     * @return void
     */
    private function validateFinancialStatement(array $formData, array $documents, string $documentType): void
    {
        $statementValidationData = $this->getDocumentValidationData($documents, $documentType);
        
        if ($statementValidationData) {
            $extractedData = $statementValidationData['extracted_data'];
            
            // Validate company name matches
            if (!empty($extractedData['company_name'])) {
                $orgComparison = $this->normalizationService->compareOrganizationNames(
                    $extractedData['company_name'],
                    $formData['applicant_name'] ?? ''
                );
                
                if ($orgComparison['match_status'] === 'hard_stop') {
                    $this->addHardStop($documentType, 'Company name on financial statement does not match applicant name');
                }
            }
            
            // Check for tampering
            if ($statementValidationData['tampering_detected']) {
                $this->addFraudFlag($documentType, 'Potential tampering detected on financial document');
                $this->addManualReview($documentType, 'Financial document requires manual verification');
            }
            
            // Check for duplicate financial document
            if (!empty($statementValidationData['sha256'])) {
                $this->checkDuplicateDocumentHash($statementValidationData['sha256'], $documentType);
            }
        }
    }
    
    /**
     * Validate company name against documents
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateCompanyName(array $formData, array $documents): void
    {
        if (empty($formData['applicant_name'])) {
            return;
        }
        
        $enteredName = $formData['applicant_name'];
        
        // Validate against certificate of incorporation
        if (isset($documents['certificate_of_incorporation'])) {
            $certValidationData = $this->getDocumentValidationData($documents, 'certificate_of_incorporation');
            
            if ($certValidationData) {
                $extractedCompanyName = $certValidationData['extracted_data']['company_name'] ?? '';
                
                if (!empty($extractedCompanyName)) {
                    $orgComparison = $this->normalizationService->compareOrganizationNames(
                        $enteredName,
                        $extractedCompanyName
                    );
                    
                    if ($orgComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('applicant_name', 'The applicant name entered does not match the company name on the uploaded certificate of incorporation. Please correct the applicant name or upload the correct company document.');
                    } elseif ($orgComparison['match_status'] === 'manual_review') {
                        $this->addManualReview('applicant_name', 'Company name variation detected - may require supporting documentation');
                    }
                }
            }
        }
        
        // Validate against memorandum and articles
        if (isset($documents['memorandum_articles'])) {
            $memoValidationData = $this->getDocumentValidationData($documents, 'memorandum_articles');
            
            if ($memoValidationData) {
                $extractedCompanyName = $memoValidationData['extracted_data']['company_name'] ?? '';
                
                if (!empty($extractedCompanyName)) {
                    $orgComparison = $this->normalizationService->compareOrganizationNames(
                        $enteredName,
                        $extractedCompanyName
                    );
                    
                    if ($orgComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('applicant_name', 'Company name on memorandum/articles does not match applicant name');
                    }
                }
            }
        }
        
        // Validate against CR14
        if (isset($documents['cr14_director_return'])) {
            $cr14ValidationData = $this->getDocumentValidationData($documents, 'cr14_director_return');
            
            if ($cr14ValidationData) {
                $extractedCompanyName = $cr14ValidationData['extracted_data']['company_name'] ?? '';
                
                if (!empty($extractedCompanyName)) {
                    $orgComparison = $this->normalizationService->compareOrganizationNames(
                        $enteredName,
                        $extractedCompanyName
                    );
                    
                    if ($orgComparison['match_status'] === 'hard_stop') {
                        $this->addHardStop('applicant_name', 'Company name on CR14 does not match applicant name');
                    }
                }
            }
        }
        
        // Check for duplicate company name
        $this->checkDuplicateCompanyName($enteredName);
    }
    
    /**
     * Check for duplicate company name
     * 
     * @param string $companyName
     * @return void
     */
    private function checkDuplicateCompanyName(string $companyName): void
    {
        $normCompany = $this->normalizationService->normalizeOrganizationName($companyName);
        
        $duplicate = \DB::table('applications')
            ->where('application_type', 'media_house')
            ->where('form_data->applicant_name', 'like', '%' . $normCompany['normalized'] . '%')
            ->whereIn('status', ['draft', 'submitted', 'under_review', 'officer_review'])
            ->first();
        
        if ($duplicate) {
            $this->addFraudFlag('applicant_name', 'Company name already used in another application');
            $this->addManualReview('applicant_name', 'Company name appears in previous application');
        }
    }
    
    /**
     * Validate media activity details
     * 
     * @param array $formData
     * @return void
     */
    private function validateMediaActivityDetails(array $formData): void
    {
        // Validate titles published
        if (empty($formData['titles_published'])) {
            $this->addHardStop('titles_published', 'Titles published is required');
        }
        
        // Validate general news or specialised
        if (isset($formData['general_news_or_specialised'])) {
            $type = strtolower($formData['general_news_or_specialised']);
            
            if (!in_array($type, ['general news', 'specialised'])) {
                $this->addSoftWarning('general_news_or_specialised', 'Please specify if this is general news or specialised information');
            }
            
            // If specialised, require details
            if ($type === 'specialised' && empty($formData['specialised_details'])) {
                $this->addHardStop('specialised_details', 'Specialised information details are required');
            }
        }
        
        // Validate head office address
        if (empty($formData['head_office_address'])) {
            $this->addHardStop('head_office_address', 'Head office address is required');
        }
    }
    
    /**
     * Validate management details
     * 
     * @param array $formData
     * @return void
     */
    private function validateManagementDetails(array $formData): void
    {
        // Validate CEO details
        if (isset($formData['ceo_details'])) {
            $ceoDetails = $formData['ceo_details'];
            
            if (empty($ceoDetails['name'])) {
                $this->addHardStop('ceo_details.name', 'CEO name is required');
            }
            
            if (empty($ceoDetails['address'])) {
                $this->addHardStop('ceo_details.address', 'CEO address is required');
            }
        }
        
        // Validate senior managers if provided
        if (isset($formData['senior_managers']) && is_array($formData['senior_managers'])) {
            foreach ($formData['senior_managers'] as $index => $manager) {
                if (empty($manager['name']) || empty($manager['position'])) {
                    $this->addSoftWarning("senior_managers.{$index}", 'Senior manager details incomplete');
                }
            }
        }
    }
    
    /**
     * Validate shareholding structure
     * 
     * @param array $formData
     * @return void
     */
    private function validateShareholdingStructure(array $formData): void
    {
        if (!isset($formData['shareholding_structure']) || !is_array($formData['shareholding_structure'])) {
            return;
        }
        
        $totalShareholding = 0;
        
        foreach ($formData['shareholding_structure'] as $index => $shareholder) {
            // Validate shareholder details
            if (empty($shareholder['name'])) {
                $this->addHardStop("shareholding_structure.{$index}.name", 'Shareholder name is required');
            }
            
            // Validate shareholding percentage
            if (!isset($shareholder['percentage']) || !is_numeric($shareholder['percentage'])) {
                $this->addHardStop("shareholding_structure.{$index}.percentage", 'Valid shareholding percentage is required');
            } else {
                $totalShareholding += $shareholder['percentage'];
                
                if ($shareholder['percentage'] < 0 || $shareholder['percentage'] > 100) {
                    $this->addHardStop("shareholding_structure.{$index}.percentage", 'Shareholding percentage must be between 0 and 100');
                }
            }
            
            // Flag significant foreign ownership (10%+)
            if ($shareholder['percentage'] >= 10) {
                $nationality = $shareholder['nationality'] ?? '';
                if (strtolower($nationality) !== 'zimbabwean') {
                    $this->addManualReview('shareholding_structure', 'Significant foreign ownership detected (10%+)');
                }
            }
        }
        
        // Validate total shareholding equals 100%
        if (abs($totalShareholding - 100) > 0.1) { // Allow small rounding errors
            $this->addHardStop('shareholding_structure', 'Total shareholding must equal 100%');
        }
    }
    
    /**
     * Validate declarations
     * 
     * @param array $formData
     * @return void
     */
    private function validateDeclarations(array $formData): void
    {
        // Validate criminal conviction declaration
        if (isset($formData['criminal_conviction'])) {
            if ($formData['criminal_conviction'] === true) {
                if (empty($formData['criminal_conviction_details'])) {
                    $this->addHardStop('criminal_conviction_details', 'Details of criminal convictions are required');
                } else {
                    $this->addManualReview('criminal_conviction', 'Criminal conviction declared - requires review');
                }
            }
        }
        
        // Validate judgment debt declaration
        if (isset($formData['judgment_debt'])) {
            if ($formData['judgment_debt'] === true) {
                if (empty($formData['judgment_debt_details'])) {
                    $this->addHardStop('judgment_debt_details', 'Details of judgment debts are required');
                } else {
                    $this->addManualReview('judgment_debt', 'Judgment debt declared - requires review');
                }
            }
        }
        
        // Validate insolvency declaration
        if (isset($formData['insolvency'])) {
            if ($formData['insolvency'] === true) {
                if (empty($formData['insolvency_details'])) {
                    $this->addHardStop('insolvency_details', 'Details of insolvency are required');
                } else {
                    $this->addManualReview('insolvency', 'Insolvency declared - requires review');
                }
            }
        }
        
        // Validate public office or political party office
        if (isset($formData['public_office'])) {
            if ($formData['public_office'] === true) {
                if (empty($formData['public_office_details'])) {
                    $this->addHardStop('public_office_details', 'Details of public/political office are required');
                } else {
                    $this->addManualReview('public_office', 'Public/political office declared - requires review');
                }
            }
        }
    }
    
    /**
     * Validate against CR14 for other validations
     * 
     * @param array $formData
     * @param array $documents
     * @return void
     */
    private function validateAgainstCR14(array $formData, array $documents): void
    {
        if (!isset($documents['cr14_director_return'])) {
            return;
        }
        
        $cr14ValidationData = $this->getDocumentValidationData($documents, 'cr14_director_return');
        
        if ($cr14ValidationData) {
            $extractedData = $cr14ValidationData['extracted_data'];
            
            // Validate registration number
            if (!empty($extractedData['registration_number']) && !empty($formData['registration_number'])) {
                if ($extractedData['registration_number'] !== $formData['registration_number']) {
                    $this->addHardStop('registration_number', 'Registration number does not match CR14');
                }
            }
            
            // Validate registered address
            if (!empty($extractedData['registered_address']) && !empty($formData['head_office_address'])) {
                $addressSimilarity = similar_text(
                    strtolower($extractedData['registered_address']),
                    strtolower($formData['head_office_address']),
                    $percent
                );
                
                if ($percent < 50) {
                    $this->addManualReview('head_office_address', 'Registered address differs from CR14');
                }
            }
            
            // Check for tampering
            if ($cr14ValidationData['tampering_detected']) {
                $this->addFraudFlag('cr14_director_return', 'Potential tampering detected on CR14');
                $this->addManualReview('cr14_director_return', 'CR14 requires manual verification');
            }
        }
    }
}