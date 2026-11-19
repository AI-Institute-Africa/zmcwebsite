<?php

namespace App\Services\Validation;

class ForeignRepresentativeOfficeValidator extends BaseValidator
{
    /**
     * Required fields for foreign representative office (AP4)
     */
    private const REQUIRED_FIELDS = [
        'contact_person_name',
        'contact_person_address',
        'contact_person_phone',
        'contact_person_email',
        'foreign_mass_media_service',
        'head_office_address',
        'mailing_address',
        'type_of_applicant',
        'media_type',
        'representative_office_scope',
        'zimbabwe_representative_address',
        'declaration'
    ];
    
    /**
     * Required documents for foreign representative office
     */
    private const REQUIRED_DOCUMENTS = [
        'certificate_of_incorporation_foreign',
        'clearance_letter',
        'editorial_charter',
        'code_of_ethics',
        'code_of_conduct',
        'mission_statement',
        'journalists_list'
    ];
    
    public function validate(array $formData, array $documents): array
    {
        $this->resetValidation();
        
        // Validate basic required fields
        $this->validateBasicRequiredFields($formData);
        
        // Validate documents
        $this->validateRequiredDocuments($documents);
        
        // Validate foreign organization name
        $this->validateForeignOrganizationName($formData, $documents);
        
        // Validate clearance letter
        $this->validateClearanceLetter($formData, $documents);
        
        // Validate directors/shareholders
        $this->validateDirectorsShareholders($formData, $documents);
        
        // Validate journalists employed
        $this->validateJournalistsEmployed($formData, $documents);
        
        // Check for duplicates
        $this->checkDuplicateApplication($formData, 'ap4');
        
        // Validate against existing registrations
        $this->validateAgainstExistingRegistrations($formData);
        
        $result = $this->getValidationResult();
        $this->logValidationResult($formData, $result, 'foreign_representative_office_ap4');
        
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
        
        if (isset($formData['contact_person_phone'])) {
            $this->validatePhone($formData['contact_person_phone'], 'contact_person_phone');
        }
        
        if (isset($formData['declaration']) && $formData['declaration'] !== true) {
            $this->addHardStop('declaration', 'You must accept the declaration to proceed');
        }
        
        // Validate media type
        if (isset($formData['media_type'])) {
            $validTypes = ['electronic', 'print', 'both'];
            if (!in_array(strtolower($formData['media_type']), $validTypes)) {
                $this->addHardStop('media_type', 'Invalid media type selected');
            }
        }
    }
    
    private function validateRequiredDocuments(array $documents): void
    {
        foreach (self::REQUIRED_DOCUMENTS as $documentType) {
            $this->validateDocumentPresent($documents, $documentType, true);
        }
    }
    
    private function validateForeignOrganizationName(array $formData, array $documents): void
    {
        if (empty($formData['foreign_mass_media_service'])) {
            return;
        }
        
        if (isset($documents['certificate_of_incorporation_foreign'])) {
            $certValidationData = $this->getDocumentValidationData($documents, 'certificate_of_incorporation_foreign');
            
            if ($certValidationData && !empty($certValidationData['extracted_data']['company_name'])) {
                $orgComparison = $this->normalizationService->compareOrganizationNames(
                    $formData['foreign_mass_media_service'],
                    $certValidationData['extracted_data']['company_name']
                );
                
                if ($orgComparison['match_status'] === 'hard_stop') {
                    $this->addHardStop('foreign_mass_media_service', 'The foreign media service name entered does not match the name on the uploaded certificate of incorporation or clearance letter. Please correct the name or upload the correct document.');
                }
            }
        }
        
        // Check for name conflicts with existing registrations
        $this->checkNameConflict($formData['foreign_mass_media_service']);
    }
    
    private function validateClearanceLetter(array $formData, array $documents): void
    {
        if (!isset($documents['clearance_letter'])) {
            return;
        }
        
        $clearanceValidationData = $this->getDocumentValidationData($documents, 'clearance_letter');
        
        if ($clearanceValidationData) {
            $extractedData = $clearanceValidationData['extracted_data'];
            
            // Validate organisation name matches
            if (!empty($extractedData['organisation_name'])) {
                $orgComparison = $this->normalizationService->compareOrganizationNames(
                    $formData['foreign_mass_media_service'],
                    $extractedData['organisation_name']
                );
                
                if ($orgComparison['match_status'] === 'hard_stop') {
                    $this->addHardStop('clearance_letter', 'Organisation name on clearance letter does not match entered foreign media service name');
                }
            }
            
            // Validate clearance letter is not expired
            if (!empty($extractedData['expiry_date'])) {
                $expiryDate = \DateTime::createFromFormat('Y-m-d', $extractedData['expiry_date']);
                if ($expiryDate < new \DateTime()) {
                    $this->addHardStop('clearance_letter', 'Clearance letter has expired. Please upload a valid clearance letter.');
                }
            }
            
            // Check for tampering
            if ($clearanceValidationData['tampering_detected']) {
                $this->addFraudFlag('clearance_letter', 'Potential tampering detected on clearance letter');
                $this->addManualReview('clearance_letter', 'Clearance letter requires manual verification');
            }
        }
    }
    
    private function validateDirectorsShareholders(array $formData, array $documents): void
    {
        // If representative office is not wholly owned by foreign applicant, validate shareholders
        if (!empty($formData['wholly_owned']) && $formData['wholly_owned'] === false) {
            if (!empty($formData['shareholders']) && is_array($formData['shareholders'])) {
                foreach ($formData['shareholders'] as $index => $shareholder) {
                    // Require details for shareholders with 10%+ holding
                    if (isset($shareholder['percentage']) && $shareholder['percentage'] >= 10) {
                        if (empty($shareholder['name'])) {
                            $this->addHardStop("shareholders.{$index}.name", 'Shareholder name is required for 10%+ holdings');
                        }
                        if (empty($shareholder['nationality'])) {
                            $this->addHardStop("shareholders.{$index}.nationality", 'Shareholder nationality is required for 10%+ holdings');
                        }
                    }
                }
            }
        }
        
        // Validate director IDs if uploaded
        if (isset($documents['directors_ids']) && is_array($documents['directors_ids'])) {
            foreach ($documents['directors_ids'] as $index => $directorDoc) {
                $docValidationData = $directorDoc['validation_data'] ?? null;
                if ($docValidationData && $docValidationData['tampering_detected']) {
                    $this->addFraudFlag("director_{$index}_id", 'Potential tampering detected on director ID');
                }
            }
        }
    }
    
    private function validateJournalistsEmployed(array $formData, array $documents): void
    {
        if (!empty($formData['journalists_employed']) && is_array($formData['journalists_employed'])) {
            foreach ($formData['journalists_employed'] as $index => $journalist) {
                if (empty($journalist['name']) || empty($journalist['address'])) {
                    $this->addSoftWarning("journalists_employed.{$index}", 'Journalist details incomplete');
                }
            }
        }
        
        // Validate journalists list document
        if (isset($documents['journalists_list'])) {
            $listValidationData = $this->getDocumentValidationData($documents, 'journalists_list');
            if ($listValidationData && $listValidationData['tampering_detected']) {
                $this->addFraudFlag('journalists_list', 'Potential tampering detected on journalists list');
            }
        }
    }
    
    private function checkNameConflict(string $organizationName): void
    {
        $normOrg = $this->normalizationService->normalizeOrganizationName($organizationName);
        
        // Check against local media houses
        $localConflict = \DB::table('applications')
            ->where('application_type', 'media_house')
            ->where('form_data->applicant_name', 'like', '%' . $normOrg['normalized'] . '%')
            ->whereIn('status', ['approved', 'issued', 'printed'])
            ->exists();
        
        if ($localConflict) {
            $this->addHardStop('foreign_mass_media_service', 'Organization name conflicts with an existing registered local media house');
        }
        
        // Check against other foreign representative offices
        $foreignConflict = \DB::table('applications')
            ->where('application_type', 'ap4')
            ->where('form_data->foreign_mass_media_service', 'like', '%' . $normOrg['normalized'] . '%')
            ->whereIn('status', ['approved', 'issued', 'printed'])
            ->exists();
        
        if ($foreignConflict) {
            $this->addHardStop('foreign_mass_media_service', 'Foreign representative office with this name already exists');
        }
    }
    
    private function validateAgainstExistingRegistrations(array $formData): void
    {
        // Check if foreign organisation has existing practitioner applications
        if (!empty($formData['foreign_mass_media_service'])) {
            $relatedApplications = \DB::table('applications')
                ->where('application_type', 'foreign_accreditation')
                ->where('form_data->media_organisation', 'like', '%' . $formData['foreign_mass_media_service'] . '%')
                ->count();
            
            if ($relatedApplications > 0) {
                $this->addSoftWarning('foreign_mass_media_service', "This organisation has {$relatedApplications} related foreign practitioner applications");
            }
        }
    }
}