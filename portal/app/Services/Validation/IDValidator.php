<?php

namespace App\Services\Validation;

use App\Models\Application;
use App\Models\IDValidationResult;
use App\Models\DocumentExtractionData;

/**
 * IDValidator
 * Validates Zimbabwe National IDs and Passports
 * Hard-stop validator - application cannot proceed if IDs don't match
 */
class IDValidator
{
    public const ZIMBABWE_ID_FORMAT = '/^\d{2}-\d{6}[A-Z]\d{2}$/'; // Format: 63-123456X70
    public const PASSPORT_FORMAT = '/^[A-Z]{2}\d{6,9}$/'; // Two letters, 6-9 digits

    private NameNormalizerService $nameNormalizer;

    public function __construct(NameNormalizerService $nameNormalizer)
    {
        $this->nameNormalizer = $nameNormalizer;
    }

    /**
     * Validate Zimbabwe National ID
     * - Entered ID must match uploaded ID exactly
     * - ID format must be correct
     * - Name on ID must match entered name
     */
    public function validateZimbabweID(Application $application, string $enteredID, string $enteredFirstName, string $enteredSurname): array
    {
        $result = [
            'is_valid' => false,
            'status' => 'unverified',
            'errors' => [],
            'warnings' => [],
        ];

        // 1. Validate ID format
        $enteredID = $this->normalizeIDNumber($enteredID);
        if (!$this->isValidIDFormat($enteredID)) {
            $result['errors'][] = [
                'type' => 'invalid_format',
                'message' => 'National ID number format is invalid. Expected: 63-123456X70',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 2. Get document extraction data
        $nationalIDDoc = $application->documents()
            ->where('document_type', 'national_id')
            ->first();

        if (!$nationalIDDoc) {
            $result['errors'][] = [
                'type' => 'no_id_document',
                'message' => 'No National ID document uploaded',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 3. Get extracted data from National ID
        $extractedData = DocumentExtractionData::where('document_id', $nationalIDDoc->id)
            ->where('document_type', 'national_id')
            ->first();

        if (!$extractedData) {
            $result['errors'][] = [
                'type' => 'extraction_failed',
                'message' => 'Could not read National ID. Document may be unreadable or altered.',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 4. Check for document tampering
        if ($extractedData->appears_altered || $extractedData->unreadable) {
            $result['errors'][] = [
                'type' => 'document_altered',
                'message' => 'National ID appears to be altered or is unreadable',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 5. Check OCR confidence
        if ($extractedData->ocr_confidence < 70) {
            $result['warnings'][] = [
                'type' => 'low_ocr_confidence',
                'message' => 'OCR confidence is low (' . $extractedData->ocr_confidence . '%). Manual verification recommended.',
                'confidence' => $extractedData->ocr_confidence,
                'severity' => 'manual_review',
            ];
        }

        // 6. Validate ID number match (HARD STOP)
        $uploadedID = $this->normalizeIDNumber($extractedData->extracted_id_number ?? '');
        if ($uploadedID !== $enteredID) {
            $result['errors'][] = [
                'type' => 'id_number_mismatch',
                'message' => 'The National ID number entered does not match the uploaded ID. Please check whether the ID number was typed correctly or whether the correct ID was uploaded.',
                'severity' => 'hard_stop',
                'entered_id' => $enteredID,
                'uploaded_id' => $uploadedID,
            ];
            return $result;
        }

        // 7. Validate name match (HARD STOP)
        $enteredNameNormalized = $this->nameNormalizer->normalizePersonalName($enteredFirstName . ' ' . $enteredSurname);
        $uploadedNameNormalized = $this->nameNormalizer->normalizePersonalName(
            ($extractedData->extracted_first_name ?? '') . ' ' . ($extractedData->extracted_surname ?? '')
        );

        $surnameSimilarity = $this->nameNormalizer->calculateSimilarityScore(
            $enteredNameNormalized['surname'],
            $uploadedNameNormalized['surname']
        );
        $firstNameSimilarity = $this->nameNormalizer->calculateSimilarityScore(
            $enteredNameNormalized['first_name'],
            $uploadedNameNormalized['first_name']
        );

        // Surname must match exactly or be very similar
        if ($surnameSimilarity < 85) {
            $result['errors'][] = [
                'type' => 'surname_mismatch',
                'message' => 'The surname entered does not match the National ID. Please check whether the name was entered correctly or whether the correct ID was uploaded.',
                'severity' => 'hard_stop',
                'entered_surname' => $enteredNameNormalized['surname'],
                'id_surname' => $uploadedNameNormalized['surname'],
                'similarity' => $surnameSimilarity,
            ];
            return $result;
        }

        // First name must match
        if ($firstNameSimilarity < 85) {
            $result['errors'][] = [
                'type' => 'first_name_mismatch',
                'message' => 'The first name entered does not match the National ID. Please check whether the name was entered correctly or whether the correct ID was uploaded.',
                'severity' => 'hard_stop',
                'entered_first_name' => $enteredNameNormalized['first_name'],
                'id_first_name' => $uploadedNameNormalized['first_name'],
                'similarity' => $firstNameSimilarity,
            ];
            return $result;
        }

        // 8. Check for duplicate IDs (same ID already used by another applicant)
        $duplicateApplication = Application::whereHas('idValidationResults', function ($query) use ($uploadedID) {
            $query->where('uploaded_id_number', $uploadedID)
                  ->where('id_number_match', 'match');
        })->where('id', '!=', $application->id)
          ->where('status', '!=', 'rejected')
          ->where('status', '!=', 'withdrawn')
          ->first();

        if ($duplicateApplication) {
            $result['errors'][] = [
                'type' => 'duplicate_id',
                'message' => 'This National ID is already linked to another application',
                'severity' => 'hard_stop',
                'duplicate_application_id' => $duplicateApplication->id,
            ];
            return $result;
        }

        // All validations passed
        $result['is_valid'] = true;
        $result['status'] = 'pass';
        $result['id_number'] = $uploadedID;
        $result['applicant_name'] = $uploadedNameNormalized['full_name'];

        return $result;
    }

    /**
     * Validate Passport for Foreign Practitioners
     */
    public function validatePassport(Application $application, string $enteredPassportNumber): array
    {
        $result = [
            'is_valid' => false,
            'status' => 'unverified',
            'errors' => [],
            'warnings' => [],
        ];

        // 1. Validate format
        $enteredPassportNumber = $this->normalizePassportNumber($enteredPassportNumber);
        if (!$this->isValidPassportFormat($enteredPassportNumber)) {
            $result['errors'][] = [
                'type' => 'invalid_format',
                'message' => 'Passport number format is invalid',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 2. Get passport document
        $passportDoc = $application->documents()
            ->where('document_type', 'passport')
            ->first();

        if (!$passportDoc) {
            $result['errors'][] = [
                'type' => 'no_passport',
                'message' => 'No passport document uploaded',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 3. Get extracted data
        $extractedData = DocumentExtractionData::where('document_id', $passportDoc->id)
            ->where('document_type', 'passport')
            ->first();

        if (!$extractedData) {
            $result['errors'][] = [
                'type' => 'extraction_failed',
                'message' => 'Could not read passport. Document may be unreadable or altered.',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 4. Check for tampering
        if ($extractedData->appears_altered || $extractedData->unreadable) {
            $result['errors'][] = [
                'type' => 'document_altered',
                'message' => 'Passport appears to be altered or is unreadable',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // 5. Validate passport number match (HARD STOP)
        $uploadedPassportNumber = $this->normalizePassportNumber($extractedData->extracted_passport_number ?? '');
        if ($uploadedPassportNumber !== $enteredPassportNumber) {
            $result['errors'][] = [
                'type' => 'passport_mismatch',
                'message' => 'The passport number entered does not match the uploaded passport',
                'severity' => 'hard_stop',
                'entered' => $enteredPassportNumber,
                'uploaded' => $uploadedPassportNumber,
            ];
            return $result;
        }

        // 6. Check expiry
        $expiryDate = $extractedData->extracted_document_expiry;
        if ($expiryDate && now()->isAfter($expiryDate)) {
            $result['errors'][] = [
                'type' => 'passport_expired',
                'message' => 'Passport has expired. Please upload a valid passport.',
                'severity' => 'hard_stop',
                'expiry_date' => $expiryDate,
            ];
            return $result;
        }

        // 7. Check for duplicate passports
        $duplicateApplication = Application::whereHas('idValidationResults', function ($query) use ($uploadedPassportNumber) {
            $query->where('uploaded_passport_number', $uploadedPassportNumber)
                  ->where('passport_number_match', 'match');
        })->where('id', '!=', $application->id)
          ->where('status', '!=', 'rejected')
          ->where('status', '!=', 'withdrawn')
          ->first();

        if ($duplicateApplication) {
            $result['errors'][] = [
                'type' => 'duplicate_passport',
                'message' => 'This passport is already linked to another application',
                'severity' => 'hard_stop',
            ];
            return $result;
        }

        // All validations passed
        $result['is_valid'] = true;
        $result['status'] = 'pass';
        $result['passport_number'] = $uploadedPassportNumber;
        $result['expiry_date'] = $expiryDate;

        return $result;
    }

    /**
     * Helper: Normalize Zimbabwe ID number for comparison
     */
    private function normalizeIDNumber(string $id): string
    {
        // Remove spaces, keep format as-is
        return str_replace(' ', '', $id);
    }

    /**
     * Helper: Normalize passport number
     */
    private function normalizePassportNumber(string $passport): string
    {
        // Remove spaces
        return str_replace(' ', '', mb_strtoupper($passport));
    }

    /**
     * Helper: Validate Zimbabwe ID format
     */
    private function isValidIDFormat(string $id): bool
    {
        return (bool) preg_match(self::ZIMBABWE_ID_FORMAT, $id);
    }

    /**
     * Helper: Validate passport format
     */
    private function isValidPassportFormat(string $passport): bool
    {
        return (bool) preg_match(self::PASSPORT_FORMAT, $passport);
    }
}
