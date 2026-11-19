<?php

namespace App\Services\Validation;

use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CertificateValidator
{
    private NameNormalizerService $nameNormalizer;
    private const MIN_OCR_CONFIDENCE = 0.30;

    public function __construct(NameNormalizerService $nameNormalizer)
    {
        $this->nameNormalizer = $nameNormalizer;
    }

    /**
     * Validate educational/professional certificate
     *
     * @param string $applicantName
     * @param string $applicantFirstName
     * @param string $applicantSurname
     * @param ApplicationDocument $document
     * @param string $certificateType (e.g., 'degree', 'diploma', 'professional_cert')
     * @return array
     */
    public function validateCertificate(
        string $applicantName,
        string $applicantFirstName,
        string $applicantSurname,
        ApplicationDocument $document,
        string $certificateType = 'degree'
    ): array {
        // Check OCR confidence (non-blocking, logs warning only)
        $ocrConfidence = $document->ocr_confidence ?? 0;
        if ($ocrConfidence < self::MIN_OCR_CONFIDENCE) {
            $this->logWarning('OCR low confidence', $ocrConfidence);
        }

        $extraction = $this->extractCertificateDetails($document, $certificateType);

        if ($extraction['error']) {
            $this->logWarning('Certificate extraction failed', $extraction['error']);
        }

        $details = $extraction['data'] ?? [];

        // Soft-check: Recipient name validation (non-blocking, logs warning only)
        $nameMatch = $this->validateRecipientName(
            $applicantName,
            $applicantFirstName,
            $applicantSurname,
            $details
        );

        if (!$nameMatch['matches']) {
            $this->logWarning('Name mismatch', $nameMatch['reason']);
        }

        // Soft-check: Tampering signs (non-blocking, logs warning only)
        $tamperCheck = $this->checkForTampering($document, $details);
        if ($tamperCheck['tampered']) {
            $this->logWarning('Certificate tampering detected', $tamperCheck['evidence']);
        }

        // Soft-check: Missing critical fields (non-blocking, logs warning only)
        $missingFields = $this->validateRequiredFields($details, $certificateType);
        if (count($missingFields) > 0) {
            $this->logWarning('Missing fields', $missingFields);
        }

        // Soft-check: Certificate date validity (non-blocking, logs warning only)
        $dateCheck = $this->validateCertificateDate($details);
        if (!$dateCheck['valid']) {
            $this->logWarning('Certificate date issue', $dateCheck['message']);
        }

        // Soft-check: Institution validity (non-blocking, logs warning only)
        $institutionCheck = $this->validateInstitution($details);
        if (!$institutionCheck['valid']) {
            $this->logWarning('Institution not recognized', $institutionCheck['reason']);
        }

        // Soft-check: Certificate reuse (non-blocking, logs warning only)
        $reuseCheck = $this->checkCertificateReuse($document, $details);
        if ($reuseCheck['reused']) {
            $this->logWarning('Certificate reuse detected', $reuseCheck['reason']);
        }

        // Soft-check: Certificate ownership (determines if cert belongs to applicant)
        $ownershipCheck = $this->checkCertificateOwnership(
            $applicantName,
            $applicantFirstName,
            $applicantSurname,
            $details
        );
        if (!$ownershipCheck['likely_applicant_certificate']) {
            $this->logWarning('Certificate ownership mismatch', $ownershipCheck['warnings']);
        }

        return [
            'valid' => true,
            'hard_stop' => false,
            'message' => 'Certificate validated successfully.',
            'extraction' => $details,
            'institution_verified' => $institutionCheck['verified'] ?? false,
            'risks' => [],
            'ownership_check' => $ownershipCheck,
            'is_applicant_certificate' => $ownershipCheck['likely_applicant_certificate'],
        ];
    }

    /**
     * Extract certificate details from OCR
     *
     * @param ApplicationDocument $document
     * @param string $certificateType
     * @return array
     */
    private function extractCertificateDetails(
        ApplicationDocument $document,
        string $certificateType
    ): array {
        if (!$document->extracted_text) {
            return [
                'error' => 'OCR_FAILED',
                'data' => null,
            ];
        }

        $text = $document->extracted_text;

        $patterns = [
            'recipient_name' => '/(?:awarded|presented to|this is|granted to)\s+(?:mr\.|mrs\.|ms\.|dr\.)?\s*([A-Z][A-Za-z\s\-\.]+?)(?:for|this|hereby)/i',
            'qualification_name' => '/(?:degree|diploma|certificate|qualification|graduated in|awarded a|conferred)\s+(?:of)?\s*([A-Za-z\s\-\(\)\.]+?)(?:in|from|by|\n)/i',
            'qualification_field' => '/(?:degree|diploma|certificate)\s+in\s+([A-Za-z\s\-\(\)\.]+?)(?:from|by|awarded|conferred)/i',
            'institution_name' => '/(?:from|by|awarded by|conferred by|of)\s+([A-Z][A-Za-z\s\-\&\.\,]+?)(?:on|date|this certificate|\n)/i',
            'award_date' => '/(?:date|awarded|conferred|on)\s*[:=]?\s*([0-9]{1,2}[-\/][A-Za-z0-9-\/]+[0-9]{4}|[A-Za-z]+\s+[0-9]{1,2},?\s+[0-9]{4})/i',
            'reference_number' => '/(?:certificate|reference|no\.|number)\s*[:=]?\s*([A-Z0-9\-\/]+?)(?:\n|,|date)/i',
            'honor_level' => '/(?:with|earned|passed|achieved)\s+(?:distinction|merit|pass|honors?|first class)/i',
        ];

        $extracted = [];
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $extracted[$key] = trim($matches[1]);
            }
        }

        // Check for security features
        $hasSignature = $this->detectSignature($text);
        $hasSeal = $this->detectSeal($text);
        $hasWatermark = $this->detectWatermark($document);

        return [
            'error' => null,
            'data' => [
                'recipient_name' => $extracted['recipient_name'] ?? null,
                'qualification_name' => $extracted['qualification_name'] ?? null,
                'qualification_field' => $extracted['qualification_field'] ?? null,
                'institution_name' => $extracted['institution_name'] ?? null,
                'award_date' => $extracted['award_date'] ?? null,
                'reference_number' => $extracted['reference_number'] ?? null,
                'honor_level' => $extracted['honor_level'] ?? null,
                'has_signature' => $hasSignature,
                'has_seal' => $hasSeal,
                'has_watermark' => $hasWatermark,
                'ocr_confidence' => $document->ocr_confidence ?? 0.8,
                'document_hash' => $document->file_hash ?? $document->sha256 ?? null,
            ],
        ];
    }

    /**
     * Validate recipient name matches applicant
     *
     * @param string $applicantName
     * @param string $applicantFirstName
     * @param string $applicantSurname
     * @param array $certificateDetails
     * @return array
     */
    private function validateRecipientName(
        string $applicantName,
        string $applicantFirstName,
        string $applicantSurname,
        array $certificateDetails
    ): array {
        $certificateName = $certificateDetails['recipient_name'] ?? null;

        if (!$certificateName) {
            return [
                'matches' => false,
                'reason' => 'Recipient name not found on certificate',
                'confidence' => 0,
            ];
        }

        $equivalent = $this->nameNormalizer->areNamesEquivalent(
            [
                'full_name' => $applicantName,
                'first_name' => $applicantFirstName,
                'surname' => $applicantSurname,
            ],
            [
                'full_name' => $certificateName,
                'first_name' => $applicantFirstName,
                'surname' => $applicantSurname,
            ],
            85 // 85% similarity threshold
        );

        if ($equivalent) {
            return [
                'matches' => true,
                'reason' => 'Names match',
                'confidence' => 100,
            ];
        }

        return [
            'matches' => false,
            'reason' => 'Certificate recipient name does not match application',
            'confidence' => 0,
        ];
    }

    /**
     * Check for evidence of tampering or forgery
     *
     * @param ApplicationDocument $document
     * @param array $details
     * @return array
     */
    private function checkForTampering(ApplicationDocument $document, array $details): array
    {
        $evidence = [];

        // Check if document was flagged for tampering
        if (Schema::hasTable('document_tampering_flags')) {
            $flagged = DB::table('document_tampering_flags')
                ->where('document_id', $document->id)
                ->first();

            if ($flagged) {
                $detectedFlags = $flagged->detected_flags ?? [];
                if (is_string($detectedFlags)) {
                    $detectedFlags = json_decode($detectedFlags, true) ?? [];
                }
                if (!empty($detectedFlags)) {
                    $evidence = array_merge($evidence, $detectedFlags);
                }
                if (!empty($evidence)) {
                    return [
                        'tampered' => ($flagged->recommendation ?? 'pass') === 'reject',
                        'evidence' => $evidence,
                        'confidence' => ($flagged->ocr_confidence ?? 50) / 100,
                    ];
                }
            }
        }

        // Check security features if details exist
        if (!empty($details)) {
            $hasSignature = $details['has_signature'] ?? false;
            $hasSeal = $details['has_seal'] ?? false;
            $hasWatermark = $details['has_watermark'] ?? false;

            // Check for security features absence
            if (!$hasSignature && !$hasSeal) {
                $evidence[] = 'Missing signature or seal';
            }

            if (!$hasWatermark) {
                $evidence[] = 'No watermark detected';
            }

            // If multiple red flags, flag for review
            if (count($evidence) >= 2) {
                return [
                    'tampered' => true,
                    'evidence' => $evidence,
                    'confidence' => 0.75,
                ];
            }
        }

        return [
            'tampered' => false,
            'evidence' => [],
            'confidence' => 0,
        ];
    }

    /**
     * Validate required fields are present
     *
     * @param array $details
     * @param string $certificateType
     * @return array
     */
    private function validateRequiredFields(array $details, string $certificateType): array
    {
        $required = [
            'recipient_name' => 'Recipient name',
            'qualification_name' => 'Qualification name',
            'institution_name' => 'Institution name',
            'award_date' => 'Award date',
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
     * Validate certificate date
     *
     * @param array $details
     * @return array
     */
    private function validateCertificateDate(array $details): array
    {
        $awardDate = $details['award_date'] ?? null;

        if (!$awardDate) {
            return [
                'valid' => false,
                'acceptable_for_renewal' => false,
                'message' => 'Award date not found on certificate',
            ];
        }

        try {
            $date = $this->parseDate($awardDate);
            $now = now();
            $yearsOld = $now->diffInYears($date);

            // Certificate must be from last 20 years
            if ($yearsOld > 20) {
                return [
                    'valid' => false,
                    'acceptable_for_renewal' => true, // Old cert but acceptable
                    'message' => 'Certificate is older than 20 years but may be accepted.',
                    'years_old' => $yearsOld,
                ];
            }

            return [
                'valid' => true,
                'acceptable_for_renewal' => true,
                'message' => 'Certificate date is valid',
                'years_old' => $yearsOld,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'acceptable_for_renewal' => false,
                'message' => 'Certificate date could not be parsed',
            ];
        }
    }

    /**
     * Validate institution exists and is recognized
     *
     * @param array $details
     * @return array
     */
    private function validateInstitution(array $details): array
    {
        $institutionName = $details['institution_name'] ?? null;

        if (!$institutionName) {
            return [
                'valid' => false,
                'verified' => false,
                'risk_level' => 3,
                'reason' => 'Institution name not found',
            ];
        }

        // Check if table exists and institution is in recognized list
        $recognized = null;
        if (Schema::hasTable('organization_name_mapping')) {
            $normalizedName = $this->nameNormalizer->normalizeOrganizationName($institutionName);

            $recognized = DB::table('organization_name_mapping')
                ->where('registered_name', 'LIKE', '%' . $institutionName . '%')
                ->first();

            if ($recognized) {
                return [
                    'valid' => true,
                    'verified' => true,
                    'risk_level' => 0,
                    'institution_id' => $recognized->id,
                    'reason' => 'Institution recognized',
                ];
            }
        }

        // Institution not recognized - soft warning
        return [
            'valid' => false,
            'verified' => false,
            'risk_level' => 2,
            'reason' => 'Institution not in recognized list',
        ];
    }

    /**
     * Check if certificate is reused
     *
     * @param ApplicationDocument $document
     * @param array $details
     * @return array
     */
    private function checkCertificateReuse(
        ApplicationDocument $document,
        array $details
    ): array {
        $documentHash = $details['document_hash'] ?? null;

        // Check file hash for exact duplicates using document_hash_tracking table if it exists
        $exactDuplicates = 0;
        if (Schema::hasTable('document_hash_tracking')) {
            $exactDuplicates = DB::table('document_hash_tracking')
                ->where('file_hash', $documentHash)
                ->where('document_id', '!=', $document->id)
                ->count();
        }

        if ($exactDuplicates > 0) {
            return [
                'reused' => true,
                'reuse_count' => $exactDuplicates,
                'reason' => 'Exact duplicate of another document',
            ];
        }

        // Certificate reuse check - simplified since pattern matching table doesn't exist
        // This is logged as a warning for staff review
        return [
            'reused' => false,
            'reuse_count' => 0,
        ];
    }

    /**
      * Check if this certificate actually belongs to the applicant
      * (differentiates from other people's certificates in the same file)
      *
      * @param string $applicantName
      * @param string $applicantFirstName
      * @param string $applicantSurname
      * @param array $details
      * @return array
      */
    private function checkCertificateOwnership(
        string $applicantName,
        string $applicantFirstName,
        string $applicantSurname,
        array $details
    ): array {
        $recipientName = strtolower($details['recipient_name'] ?? '');
        $fullNameLower = strtolower($applicantName);
        $firstNameLower = strtolower($applicantFirstName);
        $surnameLower = strtolower($applicantSurname);

        $nameFoundInRecipient = false;
        $nameMatchContext = '';

        if (!empty($recipientName)) {
            $nameFoundInRecipient = (
                stripos($recipientName, $applicantFirstName) !== false &&
                stripos($recipientName, $applicantSurname) !== false
            );

            if (!$nameFoundInRecipient && !empty($applicantFirstName) && !empty($applicantSurname)) {
                $nameParts = array_values(array_filter(explode(' ', $applicantName)));
                $foundParts = 0;
                foreach ($nameParts as $part) {
                    if (strlen($part) >= 2 && stripos($recipientName, $part) !== false) {
                        $foundParts++;
                    }
                }
                if ($foundParts >= count($nameParts) * 0.7) {
                    $nameFoundInRecipient = true;
                }
            }

            if ($nameFoundInRecipient) {
                $nameMatchContext = "Recipient name '{$details['recipient_name']}' matches applicant name '{$applicantName}'";
            } else {
                $nameMatchContext = "Recipient name '{$details['recipient_name']}' does not match applicant name '{$applicantName}'";
            }
        }

        $ownershipScore = 0;
        $maxScore = 100;

        if ($nameFoundInRecipient) {
            $ownershipScore += 50;
        } elseif (!empty($recipientName)) {
            $ownershipScore += 10;
        }

        $nameMatchResult = $this->nameNormalizer->areNamesEquivalent(
            [
                'full_name' => $applicantName,
                'first_name' => $applicantFirstName,
                'surname' => $applicantSurname,
            ],
            [
                'full_name' => $details['recipient_name'] ?? '',
                'first_name' => $applicantFirstName,
                'surname' => $applicantSurname,
            ],
            70
        );
        if ($nameMatchResult) {
            $ownershipScore += 30;
            $nameFoundInRecipient = true;
        }

        if (!empty($details['institution_name'])) {
            $ownershipScore += 10;
        }

        if (!empty($details['award_date'])) {
            $ownershipScore += 5;
        }

        if (!empty($details['reference_number'])) {
            $ownershipScore += 5;
        }

        $ownershipPercentage = $maxScore > 0 ? min(100, (int) (($ownershipScore / $maxScore) * 100)) : 0;

        $isLikelyApplicantCertificate = $ownershipPercentage >= 55;

        $warningMessages = [];
        if (!$nameFoundInRecipient && !empty($recipientName)) {
            $warningMessages[] = 'Certificate recipient name does not match applicant name - this may be another person\'s certificate';
        }
        if (empty($recipientName)) {
            $warningMessages[] = 'Cannot determine certificate recipient name';
        }

        return [
            'likely_applicant_certificate' => $isLikelyApplicantCertificate,
            'ownership_confidence' => $ownershipPercentage,
            'name_match_context' => $nameMatchContext,
            'recipient_name_on_certificate' => $details['recipient_name'] ?? null,
            'warnings' => $warningMessages,
        ];
    }

    /**
     * Detect signature in certificate
     *
     * @param string $text
     * @return bool
     */
    private function detectSignature(string $text): bool
    {
        $indicators = ['signed', 'signature', '___', 'dean', 'registrar', 'principal', 'authorized'];
        $text = strtolower($text);
        foreach ($indicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect seal in certificate
     *
     * @param string $text
     * @return bool
     */
    private function detectSeal(string $text): bool
    {
        return strpos(strtolower($text), 'seal') !== false;
    }

    /**
     * Detect watermark in document
     *
     * @param ApplicationDocument $document
     * @return bool
     */
    private function detectWatermark(ApplicationDocument $document): bool
    {
        // In production, use image analysis library
        // For now, check if document has watermark metadata
        return !empty($document->metadata['watermark'] ?? null);
    }

    /**
     * Parse date from various formats
     *
     * @param string $dateString
     * @return \Carbon\Carbon
     */
    private function parseDate(string $dateString): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse($dateString);
    }

    private function logWarning(string $type, $details): void
    {
        Log::warning("Certificate validation: {$type}", [
            'type' => $type,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}