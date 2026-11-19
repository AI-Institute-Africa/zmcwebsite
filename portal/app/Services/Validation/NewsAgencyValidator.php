<?php

namespace App\Services\Validation;

class NewsAgencyValidator extends BaseValidator
{
    /**
     * Media types for news agencies
     */
    private const MEDIA_TYPES = ['electronic', 'print', 'both'];
    
    /**
     * Required fields for news agency (AP2)
     */
    private const REQUIRED_FIELDS = [
        'contact_person',
        'applicant_name',
        'media_type',
        'head_office_address',
        'directors',
        'ceo_details',
        'declaration'
    ];
    
    /**
     * Required documents for news agency registration
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
    
    public function validate(array $formData, array $documents): array
    {
        $this->resetValidation();
        
        // Validate basic required fields
        $this->validateBasicRequiredFields($formData);
        
        // Validate directors (similar to AP1)
        if (!empty($formData['directors'])) {
            $this->validateDirectors($formData, $documents);
        }
        
        // Validate documents
        $this->validateRequiredDocuments($documents);
        
        // Validate company name (similar to AP1)
        $this->validateCompanyName($formData, $documents);
        
        // Validate media type
        $this->validateMediaType($formData);
        
        // Validate declarations
        $this->validateDeclarations($formData);
        
        // Check for duplicates
        $this->checkDuplicateApplication($formData, 'news_agency');
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'news_agency_ap2');
        
        return $result;
    }
    
    private function validateBasicRequiredFields(array $formData): void
    {
        $missing = $this->validateRequiredFields($formData, self::REQUIRED_FIELDS);
        
        if (!empty($missing)) {
            return;
        }
        
        if (isset($formData['contact_person_email'])) {
            $this->validateEmail($formData['contact_person_email'], 'contact_person_email');
        }
        
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
    }
    
    private function validateDirectors(array $formData, array $documents): void
    {
        // Similar to AP1 director validation
        foreach ($formData['directors'] as $index => $director) {
            if (empty($director['name'])) {
                $this->addHardStop("directors.{$index}.name", "Director name is required");
            }
            
            if (empty($director['national_id']) && empty($director['passport_number'])) {
                $this->addHardStop("directors.{$index}.national_id", "Director ID or passport number is required");
            }
            
            if (!empty($director['national_id'])) {
                $idValidation = $this->normalizationService->validateNationalId($director['national_id']);
                if (!$idValidation['valid']) {
                    foreach ($idValidation['errors'] as $error) {
                        $this->addHardStop("directors.{$index}.national_id", $error);
                    }
                }
            }
        }
    }
    
    private function validateRequiredDocuments(array $documents): void
    {
        foreach (self::REQUIRED_DOCUMENTS as $documentType) {
            $this->validateDocumentPresent($documents, $documentType, true);
        }
    }
    
    private function validateCompanyName(array $formData, array $documents): void
    {
        // Similar to AP1 company name validation
        if (empty($formData['applicant_name'])) {
            return;
        }
        
        if (isset($documents['certificate_of_incorporation'])) {
            $certValidationData = $this->getDocumentValidationData($documents, 'certificate_of_incorporation');
            
            if ($certValidationData && !empty($certValidationData['extracted_data']['company_name'])) {
                $orgComparison = $this->normalizationService->compareOrganizationNames(
                    $formData['applicant_name'],
                    $certValidationData['extracted_data']['company_name']
                );
                
                if ($orgComparison['match_status'] === 'hard_stop') {
                    $this->addHardStop('applicant_name', 'Company name does not match certificate of incorporation');
                }
            }
        }
    }
    
    private function validateMediaType(array $formData): void
    {
        if (!empty($formData['media_type'])) {
            if (!in_array(strtolower($formData['media_type']), self::MEDIA_TYPES)) {
                $this->addHardStop('media_type', 'Invalid media type selected');
            }
        }
    }
    
    private function validateDeclarations(array $formData): void
    {
        // Similar to AP1 declarations
        if (isset($formData['criminal_conviction']) && $formData['criminal_conviction'] === true) {
            if (empty($formData['criminal_conviction_details'])) {
                $this->addHardStop('criminal_conviction_details', 'Details of criminal convictions are required');
            }
            $this->addManualReview('criminal_conviction', 'Criminal conviction declared - requires review');
        }
    }
}