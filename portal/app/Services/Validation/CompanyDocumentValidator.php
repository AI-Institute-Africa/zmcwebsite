<?php

namespace App\Services\Validation;

use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\DB;

class CompanyDocumentValidator
{
    private NameNormalizerService $nameNormalizer;
    private const MIN_OCR_CONFIDENCE = 0.80;

    public function __construct(NameNormalizerService $nameNormalizer)
    {
        $this->nameNormalizer = $nameNormalizer;
    }

    /**
     * Validate company registration document
     *
     * @param string $applicantCompanyName
     * @param ApplicationDocument $document
     * @param string $registrationType (registration, articles, certificate_of_incumbency)
     * @return array
     */
    public function validateCompanyDocument(
        string $applicantCompanyName,
        ApplicationDocument $document,
        string $registrationType = 'registration'
    ): array {
        // Check OCR confidence
        if (($document->ocr_confidence ?? 0) < self::MIN_OCR_CONFIDENCE) {
            return [
                'valid' => false,
                'error' => 'OCR_LOW_CONFIDENCE',
                'hard_stop' => true,
                'message' => 'Document is unclear. Please provide a clear scan.',
                'ocr_confidence' => $document->ocr_confidence ?? 0,
            ];
        }

        $extraction = $this->extractCompanyDetails($document, $registrationType);

        if ($extraction['error']) {
            return [
                'valid' => false,
                'error' => $extraction['error'],
                'hard_stop' => true,
                'message' => 'Company document could not be read.',
                'extraction' => null,
            ];
        }

        $details = $extraction['data'];

        // Hard-stop: Company name must match
        $nameMatch = $this->validateCompanyName(
            $applicantCompanyName,
            $details
        );

        if (!$nameMatch['matches']) {
            return [
                'valid' => false,
                'error' => 'COMPANY_NAME_MISMATCH',
                'hard_stop' => true,
                'message' => 'The company name does not match your application details.',
                'mismatch_reason' => $nameMatch['reason'],
                'extraction' => $details,
            ];
        }

        // Hard-stop: Check for tampering
        $tamperCheck = $this->checkForTampering($document, $details);
        if ($tamperCheck['tampered']) {
            return [
                'valid' => false,
                'error' => 'DOCUMENT_FORGED',
                'hard_stop' => true,
                'message' => 'Document appears to be altered or forged.',
                'tampering_evidence' => $tamperCheck['evidence'],
                'extraction' => $details,
            ];
        }

        // Check for missing fields
        $missingFields = $this->validateRequiredFields($details, $registrationType);
        if (count($missingFields) > 0) {
            return [
                'valid' => false,
                'error' => 'MISSING_FIELDS',
                'hard_stop' => true,
                'message' => 'Document is missing required information: ' . implode(', ', $missingFields),
                'missing_fields' => $missingFields,
                'extraction' => $details,
            ];
        }

        // Validate company registration number
        $regNumberCheck = $this->validateRegistrationNumber($details);
        if (!$regNumberCheck['valid']) {
            return [
                'valid' => false,
                'error' => 'INVALID_REGISTRATION_NUMBER',
                'hard_stop' => false, // Soft warning
                'message' => 'Company registration number format is invalid or not recognized.',
                'extraction' => $details,
            ];
        }

        // Validate company status
        $statusCheck = $this->validateCompanyStatus($details);
        if (!$statusCheck['active']) {
            return [
                'valid' => false,
                'error' => 'COMPANY_NOT_ACTIVE',
                'hard_stop' => false, // Soft warning
                'message' => 'Company registration may be inactive. Officer review recommended.',
                'company_status' => $statusCheck['status'],
                'extraction' => $details,
            ];
        }

        // Validate directors/officers
        $directorsCheck = $this->validateDirectors($details);
        if (!$directorsCheck['valid']) {
            return [
                'valid' => false,
                'error' => 'INVALID_DIRECTORS',
                'hard_stop' => false,
                'message' => $directorsCheck['message'],
                'extraction' => $details,
            ];
        }

        // Check for document reuse
        $reuseCheck = $this->checkDocumentReuse($document, $details);
        if ($reuseCheck['reused']) {
            return [
                'valid' => false,
                'error' => 'DOCUMENT_REUSED',
                'hard_stop' => true,
                'message' => 'This document has already been submitted.',
                'reuse_count' => $reuseCheck['reuse_count'],
                'extraction' => $details,
            ];
        }

        return [
            'valid' => true,
            'hard_stop' => false,
            'message' => 'Company document validated successfully.',
            'extraction' => $details,
            'company_verified' => $statusCheck['verified'],
            'risks' => $statusCheck['verified'] ? [] : ['company_not_verified'],
        ];
    }

    /**
     * Extract company details from OCR
     *
     * @param ApplicationDocument $document
     * @param string $registrationType
     * @return array
     */
    private function extractCompanyDetails(
        ApplicationDocument $document,
        string $registrationType
    ): array {
        if (!$document->extracted_text) {
            return [
                'error' => 'OCR_FAILED',
                'data' => null,
            ];
        }

        $text = $document->extracted_text;

        $patterns = [
            'company_name' => '/(?:registered name|company name|name of company)\s*[:=]?\s*([A-Z][A-Za-z\s\&\-\.\,]+?)(?:[\n,]|registration|trading)/i',
            'trading_name' => '/(?:trading as|trading name|known as)\s*[:=]?\s*([A-Z][A-Za-z\s\&\-\.\,]+?)(?:[\n,]|registration|under)/i',
            'registration_number' => '/(?:registration|company|reg)\s+(?:number|no\.|#)\s*[:=]?\s*([A-Z0-9\/\-]+?)(?:[\n,]|dated|issued)/i',
            'company_status' => '/(?:status|company status)\s*[:=]?\s*(active|inactive|dissolved|liquidated|deregistered)/i',
            'date_registered' => '/(?:date registered|registered on|date of registration)\s*[:=]?\s*([0-9]{1,2}[-\/][A-Za-z0-9-\/]+[0-9]{4}|[A-Za-z]+\s+[0-9]{1,2},?\s+[0-9]{4})/i',
            'registered_office' => '/(?:registered office|principal place|office address)\s*[:=]?\s*([A-Za-z0-9\s\,\-\.]+?)(?:[\n]|phone|tel|email)/i',
            'director_name' => '/(?:director|managing director|secretary|officer)\s*[:=]?\s*(?:name)?\s*([A-Z][A-Za-z\s\-\.]+?)(?:[\n,]|address|id|passport)/i',
            'director_count' => '/(?:number of directors|total directors)\s*[:=]?\s*([0-9]+)/i',
            'shareholding' => '/(?:shareholding|shares|equity)\s+(?:structure|composition)\s*[:=]?\s*([A-Za-z0-9\s\%\,\-]+?)(?:[\n,]|document|certificate)/i',
        ];

        $extracted = [];
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $extracted[$key] = trim($matches[1]);
            }
        }

        // Extract multiple directors
        $directors = [];
        if (preg_match_all('/(?:director|managing director|secretary)\s*[:=]?\s*(?:name)?\s*([A-Z][A-Za-z\s\-\.]+?)(?=[\n,])/i', $text, $matches)) {
            $directors = $matches[1];
        }

        // Check for security features
        $hasStamp = $this->detectStamp($text);
        $hasSignature = $this->detectSignature($text);
        $hasRegistrarDetails = $this->detectRegistrarDetails($text);

        return [
            'error' => null,
            'data' => [
                'company_name' => $extracted['company_name'] ?? null,
                'trading_name' => $extracted['trading_name'] ?? null,
                'registration_number' => $extracted['registration_number'] ?? null,
                'company_status' => $extracted['company_status'] ?? null,
                'date_registered' => $extracted['date_registered'] ?? null,
                'registered_office' => $extracted['registered_office'] ?? null,
                'directors' => $directors,
                'director_count' => (int)($extracted['director_count'] ?? count($directors)),
                'shareholding' => $extracted['shareholding'] ?? null,
                'has_stamp' => $hasStamp,
                'has_signature' => $hasSignature,
                'has_registrar_details' => $hasRegistrarDetails,
                'ocr_confidence' => $document->ocr_confidence ?? 0.8,
                'document_hash' => $document->file_hash,
            ],
        ];
    }

    /**
     * Validate company name matches application
     *
     * @param string $applicantCompanyName
     * @param array $documentDetails
     * @return array
     */
    private function validateCompanyName(
        string $applicantCompanyName,
        array $documentDetails
    ): array {
        $documentName = $documentDetails['company_name'] ?? null;

        if (!$documentName) {
            return [
                'matches' => false,
                'reason' => 'Company name not found in document',
                'confidence' => 0,
            ];
        }

        $equivalent = $this->nameNormalizer->areNamesEquivalent(
            $applicantCompanyName,
            $documentName,
            85
        );

        if ($equivalent) {
            return [
                'matches' => true,
                'reason' => 'Company names match',
                'confidence' => 100,
            ];
        }

        $tradingName = $documentDetails['trading_name'] ?? null;
        if ($tradingName) {
            $tradingEquivalent = $this->nameNormalizer->areNamesEquivalent(
                $applicantCompanyName,
                $tradingName,
                85
            );

            if ($tradingEquivalent) {
                return [
                    'matches' => true,
                    'reason' => 'Trading name matches',
                    'confidence' => 100,
                ];
            }
        }

        return [
            'matches' => false,
            'reason' => 'Company name does not match registration document',
            'confidence' => 0,
        ];
    }

    /**
     * Check for tampering evidence
     *
     * @param ApplicationDocument $document
     * @param array $details
     * @return array
     */
    private function checkForTampering(ApplicationDocument $document, array $details): array
    {
        $evidence = [];

        $flagged = DB::table('document_tampering_flags')
            ->where('application_document_id', $document->id)
            ->where('tampering_detected', true)
            ->first();

        if ($flagged) {
            $evidence[] = $flagged->tampering_type;
            return [
                'tampered' => true,
                'evidence' => $evidence,
            ];
        }

        // Check for missing security features
        if (!$details['has_stamp'] && !$details['has_signature']) {
            $evidence[] = 'Missing official stamp/signature';
        }

        if (!$details['has_registrar_details']) {
            $evidence[] = 'Missing registrar details';
        }

        if (count($evidence) >= 2) {
            return [
                'tampered' => true,
                'evidence' => $evidence,
            ];
        }

        return [
            'tampered' => false,
            'evidence' => [],
        ];
    }

    /**
     * Validate required fields
     *
     * @param array $details
     * @param string $registrationType
     * @return array
     */
    private function validateRequiredFields(array $details, string $registrationType): array
    {
        $required = [
            'company_name' => 'Company name',
            'registration_number' => 'Registration number',
            'company_status' => 'Company status',
            'date_registered' => 'Registration date',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (empty($details[$field])) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Validate registration number format
     *
     * @param array $details
     * @return array
     */
    private function validateRegistrationNumber(array $details): array
    {
        $regNumber = $details['registration_number'] ?? null;

        if (!$regNumber) {
            return [
                'valid' => false,
                'reason' => 'Registration number not found',
            ];
        }

        // Zimbabwe registration numbers typically start with a letter or digit
        if (!preg_match('/^[A-Z0-9]{2,20}$/', strtoupper($regNumber))) {
            return [
                'valid' => false,
                'reason' => 'Registration number format invalid',
            ];
        }

        return [
            'valid' => true,
            'reason' => 'Registration number format valid',
        ];
    }

    /**
     * Validate company status
     *
     * @param array $details
     * @return array
     */
    private function validateCompanyStatus(array $details): array
    {
        $status = strtolower($details['company_status'] ?? 'active');

        $activeStatuses = ['active', 'in good standing', 'registered'];

        $isActive = in_array($status, $activeStatuses);

        return [
            'active' => $isActive,
            'verified' => $isActive,
            'status' => $status,
        ];
    }

    /**
     * Validate directors information
     *
     * @param array $details
     * @return array
     */
    private function validateDirectors(array $details): array
    {
        $directors = $details['directors'] ?? [];

        if (empty($directors)) {
            return [
                'valid' => false,
                'message' => 'No directors found in document',
            ];
        }

        // Check cross-reference against director database
        $invalidDirectors = [];
        foreach ($directors as $director) {
            $exists = DB::table('director_cross_reference')
                ->where(DB::raw('UPPER(director_name)'), strtoupper($director))
                ->where('active', true)
                ->exists();

            if (!$exists) {
                $invalidDirectors[] = $director;
            }
        }

        if (count($invalidDirectors) > 0) {
            return [
                'valid' => false,
                'message' => 'Some directors could not be verified',
                'invalid_count' => count($invalidDirectors),
            ];
        }

        return [
            'valid' => true,
            'message' => 'All directors verified',
            'director_count' => count($directors),
        ];
    }

    /**
     * Check for document reuse
     *
     * @param ApplicationDocument $document
     * @param array $details
     * @return array
     */
    private function checkDocumentReuse(
        ApplicationDocument $document,
        array $details
    ): array {
        $documentHash = $details['document_hash'];

        $duplicates = DB::table('document_reuse_patterns')
            ->where('original_file_hash', $documentHash)
            ->where('application_document_id', '!=', $document->id)
            ->count();

        if ($duplicates > 0) {
            return [
                'reused' => true,
                'reuse_count' => $duplicates,
            ];
        }

        return [
            'reused' => false,
            'reuse_count' => 0,
        ];
    }

    /**
     * Detect stamp in document
     *
     * @param string $text
     * @return bool
     */
    private function detectStamp(string $text): bool
    {
        return strpos(strtolower($text), 'stamp') !== false ||
               strpos(strtolower($text), 'seal') !== false;
    }

    /**
     * Detect signature in document
     *
     * @param string $text
     * @return bool
     */
    private function detectSignature(string $text): bool
    {
        $indicators = ['signed', 'signature', 'authorized', 'approved'];
        $text = strtolower($text);
        foreach ($indicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect registrar details in document
     *
     * @param string $text
     * @return bool
     */
    private function detectRegistrarDetails(string $text): bool
    {
        $indicators = ['registrar', 'companies registry', 'registered office', 'registry'];
        $text = strtolower($text);
        foreach ($indicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return true;
            }
        }
        return false;
    }
}
