<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;

class IdentityVerificationService
{
    private DocumentClassificationService $classificationService;

    public function __construct(DocumentClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
    }

    /**
     * Verify applicant identity based on ID/passport documents.
     */
    public function verifyIdentity(Application $application): array
    {
        if ($application->applicant_type === 'local') {
            return $this->verifyLocalIdentity($application);
        } elseif ($application->applicant_type === 'international') {
            return $this->verifyInternationalIdentity($application);
        }

        return ['status' => 'fail', 'confidence_score' => 0, 'error' => 'Unknown applicant type'];
    }

    /**
     * Verify local applicant identity using national ID.
     * Validates Zimbabwe National Registration ID format.
     */
    private function verifyLocalIdentity(Application $application): array
    {
        $natIdDoc = $application->documents()
            ->where('doc_type', 'national_id')
            ->first();

        if (!$natIdDoc) {
            return [
                'status' => 'fail',
                'confidence_score' => 0,
                'error' => 'National ID document not found',
                'details' => [],
            ];
        }

        // Classify the document
        $classification = $this->classificationService->classifyDocument($natIdDoc, 'local_national_id');

        // Extract name from OCR text
        $extractedText = $classification['extracted_text_excerpt'] ?? '';
        
        // Validate Zimbabwe National Registration ID format
        $zimbabweValidation = $this->validateZimbabweNationalId($extractedText, $application);
        
        $nameMatch = $this->matchApplicantName($extractedText, $application);

        $score = 0;
        if ($classification['recommendation'] === 'pass') {
            $score += 60;
        } elseif ($classification['recommendation'] === 'manual_review') {
            $score += 35;
        }

        // Zimbabwe ID format validation
        if ($zimbabweValidation['is_valid_format']) {
            $score += 25;
        } else {
            $score -= 20; // Deduct if format is invalid
        }

        if ($nameMatch) {
            $score += 20;
        }

        // ID number matching (if both ID numbers are available)
        if ($zimbabweValidation['id_number_match']) {
            $score += 15;
        }

        $confidence = max(0, min($score, 100));

        return [
            'status' => $confidence >= 80 ? 'pass' : ($confidence >= 60 ? 'flag' : 'fail'),
            'confidence_score' => (int) $confidence,
            'document_type' => 'local_national_id',
            'classification' => $classification,
            'name_match' => $nameMatch,
            'zimbabwe_validation' => $zimbabweValidation,
            'details' => [
                'applicant_name' => $application->first_name . ' ' . $application->last_name,
                'extracted_excerpt' => substr($extractedText, 0, 200),
                'required_fields' => $zimbabweValidation['found_fields'],
                'missing_fields' => $zimbabweValidation['missing_fields'],
            ],
        ];
    }

    /**
     * Verify international applicant identity using passport and clearance letter.
     */
    private function verifyInternationalIdentity(Application $application): array
    {
        $passportDoc = $application->documents()
            ->where('doc_type', 'passport')
            ->first();

        $clearanceDoc = $application->documents()
            ->where('doc_type', 'clearance_letter')
            ->first();

        if (!$passportDoc) {
            return [
                'status' => 'fail',
                'confidence_score' => 0,
                'error' => 'Passport document not found',
                'details' => [],
            ];
        }

        if (!$clearanceDoc) {
            return [
                'status' => 'fail',
                'confidence_score' => 0,
                'error' => 'Clearance letter not found',
                'details' => [],
            ];
        }

        // Classify passport
        $passportClassification = $this->classificationService->classifyDocument($passportDoc, 'passport');
        $passportText = $passportClassification['extracted_text_excerpt'] ?? '';

        // Classify clearance letter
        $clearanceClassification = $this->classificationService->classifyDocument($clearanceDoc, 'clearance_letter');
        $clearanceText = $clearanceClassification['extracted_text_excerpt'] ?? '';

        // Validate passport format and extract fields
        $passportValidation = $this->validatePassportFormat($passportText, $application);

        // Check passport expiry
        $passportExpired = $passportValidation['is_expired'];

        // Check clearance letter age
        $clearanceTooOld = $this->checkClearanceAge($clearanceText);

        // Check name match
        $nameMatch = $this->matchApplicantName($passportText, $application);

        $score = 0;

        // Passport validation
        if ($passportClassification['recommendation'] === 'pass') {
            $score += 45;
        } elseif ($passportClassification['recommendation'] === 'manual_review') {
            $score += 20;
        }

        // Passport format validation
        if ($passportValidation['is_valid_format']) {
            $score += 20;
        } else {
            $score -= 15;
        }

        if (!$passportExpired) {
            $score += 15;
        } else {
            // Expired passport is automatic fail
            return [
                'status' => 'fail',
                'confidence_score' => 0,
                'error' => 'Passport expired',
                'passport_validation' => $passportValidation,
                'details' => [
                    'applicant_name' => $application->first_name . ' ' . $application->last_name,
                    'expiry_date' => $passportValidation['expiry_date'] ?? 'Unknown',
                ],
            ];
        }

        if ($nameMatch) {
            $score += 10;
        }

        // Clearance validation
        if ($clearanceClassification['recommendation'] === 'pass') {
            $score += 25;
        } elseif ($clearanceClassification['recommendation'] === 'manual_review') {
            $score += 12;
        }

        if (!$clearanceTooOld) {
            $score += 8;
        }

        $confidence = max(0, min($score, 100));
        $status = 'pass';

        if ($clearanceTooOld) {
            $status = 'flag';
            $confidence = max(0, $confidence - 25);
        } elseif (!$passportValidation['is_valid_format']) {
            $status = 'flag';
            $confidence = max(0, $confidence - 20);
        } elseif ($confidence < 60) {
            $status = 'flag';
        }

        return [
            'status' => $status,
            'confidence_score' => (int) $confidence,
            'document_type' => 'passport_and_clearance',
            'passport_classification' => $passportClassification,
            'clearance_classification' => $clearanceClassification,
            'passport_validation' => $passportValidation,
            'passport_expired' => $passportExpired,
            'clearance_too_old' => $clearanceTooOld,
            'name_match' => $nameMatch,
            'details' => [
                'applicant_name' => $application->first_name . ' ' . $application->last_name,
                'passport_excerpt' => substr($passportText, 0, 200),
                'clearance_excerpt' => substr($clearanceText, 0, 200),
                'required_fields' => $passportValidation['found_fields'],
                'missing_fields' => $passportValidation['missing_fields'],
            ],
        ];
    }

    /**
     * Check if applicant name appears in extracted text.
     */
    private function matchApplicantName(string $extractedText, Application $application): bool
    {
        $textLower = strtolower($extractedText);
        $firstNameLower = strtolower($application->first_name);
        $lastNameLower = strtolower($application->last_name);
        $fullNameLower = $firstNameLower . ' ' . $lastNameLower;

        return (
            strpos($textLower, $fullNameLower) !== false ||
            strpos($textLower, $lastNameLower) !== false
        );
    }

    /**
     * Check if passport is expired.
     */
    private function checkPassportExpiry(string $extractedText): bool
    {
        // Look for date patterns like "expiry", "valid until", "expires"
        $pattern = '/(?:expiry|valid until|expires|expired|expiration).*?(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i';

        if (preg_match($pattern, $extractedText, $matches)) {
            $dateStr = $matches[1];
            try {
                $expiryDate = \DateTime::createFromFormat('d/m/Y', str_replace('-', '/', $dateStr));
                if (!$expiryDate) {
                    $expiryDate = \DateTime::createFromFormat('m/d/Y', str_replace('-', '/', $dateStr));
                }
                if ($expiryDate && $expiryDate < new \DateTime()) {
                    return true;
                }
            } catch (\Exception $e) {
                // Could not parse date
            }
        }

        return false;
    }

    /**
     * Check if clearance letter is too old (> 6 months by default).
     */
    private function checkClearanceAge(string $extractedText): bool
    {
        $maxAgeMonths = config('intake-automation.clearance_letter_max_age_months', 6);
        $pattern = '/(?:issued|date|issuance).*?(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i';

        if (preg_match($pattern, $extractedText, $matches)) {
            $dateStr = $matches[1];
            try {
                $issueDate = \DateTime::createFromFormat('d/m/Y', str_replace('-', '/', $dateStr));
                if (!$issueDate) {
                    $issueDate = \DateTime::createFromFormat('m/d/Y', str_replace('-', '/', $dateStr));
                }

                if ($issueDate) {
                    $cutoffDate = (new \DateTime())->modify("-{$maxAgeMonths} months");
                    if ($issueDate < $cutoffDate) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Could not parse date
            }
        }

        return false;
    }

    /**
     * Validate Zimbabwe National Registration ID format.
     * 
     * Expected fields from Zimbabwe National ID:
     * - ID Number (format: XX-XXXXXX X XX CITF or similar)
     * - Surname
     * - First Name
     * - Date of Birth (DD/MM/YYYY)
     * - Village of Origin
     * - Place of Birth
     * - Date of Issue (DD/MM/YYYY)
     * - Fingerprint
     * - Signature of Holder
     */
    private function validateZimbabweNationalId(string $extractedText, Application $application): array
    {
        $textLower = strtolower($extractedText);
        $foundFields = [];
        $missingFields = [];
        
        // Required fields to check
        $requiredFields = [
            'id_number' => '/(?:id\s*number|identification\s*number).*?(\d+[-\/]?\d+\s*[a-z]+\s*\d+(?:\s*citf)?)/i',
            'surname' => '/(?:surname|last\s*name).*?([a-z\s]{2,}?)(?:\n|first|date|village)/i',
            'first_name' => '/(?:first\s*name|given\s*name).*?([a-z\s]{2,}?)(?:\n|date|village)/i',
            'date_of_birth' => '/(?:date\s*of\s*birth|dob).*?(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'village_of_origin' => '/(?:village\s*of\s*origin|village).*?([a-z\s]{2,}?)(?:\n|place|date)/i',
            'place_of_birth' => '/(?:place\s*of\s*birth|birthplace).*?([a-z\s]{2,}?)(?:\n|date|issue)/i',
            'date_of_issue' => '/(?:date\s*of\s*issue|issued\s*on).*?(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/i',
            'fingerprint' => '/fingerprint/i',
            'signature' => '/(?:signature|sign)/i',
        ];

        // Check for Republic of Zimbabwe and National Registration indicators
        $isZimbabweId = (
            strpos($textLower, 'republic of zimbabwe') !== false ||
            strpos($textLower, 'zimbabwe') !== false
        ) && (
            strpos($textLower, 'national registration') !== false ||
            strpos($textLower, 'registration') !== false
        );

        // Check each field
        foreach ($requiredFields as $fieldName => $pattern) {
            if (preg_match($pattern, $extractedText, $matches)) {
                $foundFields[$fieldName] = trim($matches[1] ?? $matches[0]);
            } else {
                $missingFields[] = $fieldName;
            }
        }

        // Validate date formats
        $dateValidation = true;
        if (isset($foundFields['date_of_birth'])) {
            $dateValidation = $this->validateDateFormat($foundFields['date_of_birth']) && $dateValidation;
        }
        if (isset($foundFields['date_of_issue'])) {
            $dateValidation = $this->validateDateFormat($foundFields['date_of_issue']) && $dateValidation;
        }

        // Check if ID number matches expected format (XX-XXXXXX X XX CITF pattern)
        $idNumberValid = false;
        if (isset($foundFields['id_number'])) {
            // Zimbabwe ID format: typically XX-XXXXXX X XX CITF or similar variations
            $idNumberValid = preg_match('/^\d{2,3}[-\/]?\d{5,6}\s+[a-z]?\s*\d{2,3}\s*(citf|other)/i', $foundFields['id_number']) > 0;
        }

        $isValidFormat = $isZimbabweId && count($missingFields) <= 2; // Allow up to 2 missing fields (very lenient)

        return [
            'is_valid_format' => $isValidFormat,
            'is_zimbabwe_id' => $isZimbabweId,
            'found_fields' => $foundFields,
            'missing_fields' => $missingFields,
            'date_format_valid' => $dateValidation,
            'id_number_format_valid' => $idNumberValid,
            'missing_count' => count($missingFields),
            'recommendation' => $this->getZimbabweIdRecommendation($isValidFormat, $missingFields, $idNumberValid),
        ];
    }

    /**
     * Validate date format (DD/MM/YYYY or MM/DD/YYYY).
     */
    private function validateDateFormat(string $dateStr): bool
    {
        $patterns = [
            'd/m/Y',
            'm/d/Y',
            'dd/mm/yyyy',
            'mm/dd/yyyy',
        ];

        $dateStr = str_replace('-', '/', trim($dateStr));

        foreach ($patterns as $pattern) {
            try {
                $date = \DateTime::createFromFormat($pattern, $dateStr);
                if ($date && $date->format($pattern) === $dateStr) {
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return false;
    }

    /**
     * Determine recommendation based on Zimbabwe ID validation.
     */
    private function getZimbabweIdRecommendation(bool $isValidFormat, array $missingFields, bool $idNumberValid): string
    {
        if (!$isValidFormat) {
            return 'manual_review'; // Format doesn't match expected Zimbabwe ID
        }

        if (count($missingFields) > 2) {
            return 'manual_review'; // Too many fields missing
        }

        if (!$idNumberValid) {
            return 'manual_review'; // ID number doesn't match expected format
        }

        return 'pass';
    }

    /**
     * Validate international passport format.
     *
     * Expected fields from a typical passport:
     * - Type: P (for Passport)
     * - Code: Country code (e.g., GBR, USA, ZWE)
     * - Passport Number: Unique identifier
     * - Surname: Last name
     * - Given names: First and middle names
     * - Nationality: Country of citizenship
     * - Date of Birth: DD MMM YY format
     * - Sex: M or F
     * - Place of Birth: City/location
     * - Date of Issue: DD MMM YY format
     * - Authority: Issuing organization
     * - Date of Expiry: DD MMM YY format (CRITICAL - if expired, fail)
     * - MRZ Line: Machine Readable Zone (optional but helps verify)
     */
    private function validatePassportFormat(string $extractedText, Application $application): array
    {
        $foundFields = [];
        $missingFields = [];

        // Expected fields with regex patterns
        $fieldsToExtract = [
            'type' => '/type.*?([a-z])/i',
            'country_code' => '/code.*?([a-z]{3})/i',
            'passport_number' => '/passport\s*no.*?(\d{6,9})/i',
            'surname' => '/surname.*?([a-z\s]{2,}?)(?:\n|given|first)/i',
            'given_names' => '/(?:given\s*names|first\s*name).*?([a-z\s]{2,}?)(?:\n|nationality|date)/i',
            'nationality' => '/nationality.*?([a-z\s]{5,}?)(?:\n|date|sex)/i',
            'date_of_birth' => '/(?:date\s*of\s*birth|birth).*?(\d{1,2}\s*(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|[a-z]{3})\s*\d{2,4})/i',
            'sex' => '/sex.*?([mf])/i',
            'place_of_birth' => '/(?:place\s*of\s*birth|birth\s*place).*?([a-z\s]{2,}?)(?:\n|date|issue)/i',
            'date_of_issue' => '/(?:date\s*of\s*issue|issued).*?(\d{1,2}\s*(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|[a-z]{3})\s*\d{2,4})/i',
            'authority' => '/(?:authority|issuing\s*authority|authority\s*issuer).*?([a-z\s]{2,}?)(?:\n|expir|date)/i',
            'date_of_expiry' => '/(?:date\s*of\s*expiry|expir).*?(\d{1,2}\s*(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|[a-z]{3})\s*\d{2,4})/i',
        ];

        // Extract fields
        foreach ($fieldsToExtract as $fieldName => $pattern) {
            if (preg_match($pattern, $extractedText, $matches)) {
                $foundFields[$fieldName] = trim($matches[1] ?? $matches[0]);
            } else {
                $missingFields[] = $fieldName;
            }
        }

        // Check for MRZ (Machine Readable Zone) - optional but indicates quality document
        $hasMrz = preg_match('/^P<[A-Z]{3}/', $extractedText) > 0;

        // Check expiry date
        $isExpired = false;
        $expiryDate = null;
        if (isset($foundFields['date_of_expiry'])) {
            $isExpired = $this->isPassportExpired($foundFields['date_of_expiry']);
            $expiryDate = $foundFields['date_of_expiry'];
        }

        // Passport is valid if:
        // 1. Has type "P"
        // 2. Has valid country code (3 letters)
        // 3. Has passport number (6-9 digits)
        // 4. Not expired
        // 5. Contains most required fields
        $isValidFormat = (
            (isset($foundFields['type']) && strtoupper($foundFields['type']) === 'P') &&
            (isset($foundFields['country_code']) && strlen($foundFields['country_code']) === 3) &&
            (isset($foundFields['passport_number']) && preg_match('/^\d{6,9}$/', $foundFields['passport_number'])) &&
            count($missingFields) <= 3 && // Allow up to 3 missing fields
            !$isExpired
        );

        return [
            'is_valid_format' => $isValidFormat,
            'is_expired' => $isExpired,
            'expiry_date' => $expiryDate,
            'found_fields' => $foundFields,
            'missing_fields' => $missingFields,
            'has_mrz' => $hasMrz,
            'missing_count' => count($missingFields),
            'country_code' => $foundFields['country_code'] ?? null,
            'passport_number' => $foundFields['passport_number'] ?? null,
            'recommendation' => $this->getPassportRecommendation($isValidFormat, $missingFields, $isExpired, $hasMrz),
        ];
    }

    /**
     * Check if passport is expired.
     * Handles multiple date formats: "DD MMM YY", "18 SEP 99", "18 SEPT 99", etc.
     */
    private function isPassportExpired(string $expiryDateStr): bool
    {
        $expiryDateStr = strtoupper(trim($expiryDateStr));

        // Map month abbreviations
        $months = [
            'JAN' => '01', 'JANUARY' => '01',
            'FEB' => '02', 'FEBRUARY' => '02',
            'MAR' => '03', 'MARCH' => '03', 'MARS' => '03',
            'APR' => '04', 'APRIL' => '04',
            'MAY' => '05',
            'JUN' => '06', 'JUNE' => '06',
            'JUL' => '07', 'JULY' => '07',
            'AUG' => '08', 'AUGUST' => '08',
            'SEP' => '09', 'SEPT' => '09', 'SEPTEMBER' => '09',
            'OCT' => '10', 'OCTOBER' => '10',
            'NOV' => '11', 'NOVEMBER' => '11',
            'DEC' => '12', 'DECEMBER' => '12',
        ];

        // Try to parse various formats
        $patterns = [
            '/(\d{1,2})\s+([a-z]{3,9})\s+(\d{2,4})/i',  // 18 SEP 99 or 18 SEPT 1999
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $expiryDateStr, $matches)) {
                $day = $matches[1];
                $monthStr = strtoupper($matches[2]);
                $year = $matches[3];

                // Find month
                $month = null;
                foreach ($months as $abbr => $num) {
                    if (strpos($monthStr, $abbr) === 0) {
                        $month = $num;
                        break;
                    }
                }

                if (!$month) {
                    continue;
                }

                // Handle 2-digit years (assume 1900s if > 50, 2000s if <= 50)
                if (strlen($year) === 2) {
                    $year = (int) $year;
                    $year = $year > 50 ? 1900 + $year : 2000 + $year;
                } else {
                    $year = (int) $year;
                }

                try {
                    $expiryDate = \DateTime::createFromFormat('d/m/Y', "$day/$month/$year");
                    if ($expiryDate && $expiryDate < new \DateTime()) {
                        return true; // Expired
                    }
                    return false; // Not expired
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return false; // Could not determine expiry
    }

    /**
     * Determine recommendation based on passport validation.
     */
    private function getPassportRecommendation(bool $isValidFormat, array $missingFields, bool $isExpired, bool $hasMrz): string
    {
        if ($isExpired) {
            return 'fail'; // Expired passport - automatic fail
        }

        if (!$isValidFormat) {
            return 'manual_review'; // Format doesn't match expected passport
        }

        if (count($missingFields) > 3) {
            return 'manual_review'; // Too many fields missing
        }

        // Bonus for MRZ presence (indicates quality document)
        if ($hasMrz && count($missingFields) <= 1) {
            return 'pass';
        }

        return 'pass';
    }

    /**
     * Verify graduation/qualification certificate.
     * 
     * Expected fields from a graduation certificate:
     * - Certificate/Diploma type indicator
     * - Institution/University/College name
     * - Award/Qualification level (Diploma, Degree, etc.)
     * - Recipient/Applicant name
     * - Field of Study/Discipline
     * - Date of Issue/Graduation/Completion
     * - Authorized Signature/Signatory
     * - Optional: Course duration, GPA/Result, Certificate number, Institution seal/stamp
     */
    public function verifyCertificate(Application $application): array
    {
        $certDoc = $application->documents()
            ->where('doc_type', 'graduation_certificate')
            ->first();

        if (!$certDoc) {
            return [
                'status' => 'fail',
                'confidence_score' => 0,
                'error' => 'Graduation certificate document not found',
                'details' => [],
            ];
        }

        // Classify the document
        $classification = $this->classificationService->classifyDocument($certDoc, 'graduation_certificate');

        // Extract text from OCR
        $extractedText = $classification['extracted_text_excerpt'] ?? '';

        // Validate certificate format
        $certificateValidation = $this->validateCertificateFormat($extractedText, $application);

        // Check for applicant name match
        $nameMatch = $this->matchApplicantName($extractedText, $application);

        $score = 0;

        // Classification score
        if ($classification['recommendation'] === 'pass') {
            $score += 50;
        } elseif ($classification['recommendation'] === 'manual_review') {
            $score += 30;
        }

        // Certificate format validation
        if ($certificateValidation['is_valid_format']) {
            $score += 25;
        } else {
            $score -= 10;
        }

        // Name match
        if ($nameMatch) {
            $score += 15;
        }

        // Detected certificate type confidence
        if ($certificateValidation['detected_type'] !== 'unknown') {
            $score += 10;
        }

        // Qualification signal strength
        if ($certificateValidation['qualification_confidence'] >= 80) {
            $score += 10;
        } elseif ($certificateValidation['qualification_confidence'] >= 60) {
            $score += 5;
        }

        // Institution confidence
        if ($certificateValidation['institution_confidence'] >= 80) {
            $score += 5;
        }

        $confidence = max(0, min($score, 100));

        return [
            'status' => $confidence >= 80 ? 'pass' : ($confidence >= 60 ? 'flag' : 'fail'),
            'confidence_score' => (int) $confidence,
            'document_type' => 'graduation_certificate',
            'classification' => $classification,
            'name_match' => $nameMatch,
            'certificate_validation' => $certificateValidation,
            'details' => [
                'applicant_name' => $application->first_name . ' ' . $application->last_name,
                'extracted_excerpt' => substr($extractedText, 0, 250),
                'detected_certificate_type' => $certificateValidation['detected_type'],
                'found_fields' => $certificateValidation['found_fields'],
                'missing_fields' => $certificateValidation['missing_fields'],
                'qualification_signals' => $certificateValidation['qualification_signals'],
                'institution_signals' => $certificateValidation['institution_signals'],
            ],
        ];
    }

    /**
     * Validate graduation certificate format.
     * 
     * Checks for:
     * - Certificate/diploma/degree indicators
     * - Institution name (university, college, institute)
     * - Qualification level
     * - Recipient name
     * - Completion/graduation/issue date
     * - Signature/authorized official
     */
    private function validateCertificateFormat(string $extractedText, Application $application): array
    {
        $textLower = strtolower($extractedText);
        $foundFields = [];
        $missingFields = [];
        $qualificationSignals = [];
        $institutionSignals = [];

        // Primary certificate/qualification indicators
        $certificateKeywords = [
            'certificate' => '/certificate\s+(?:of|in)?/i',
            'diploma' => '/diploma/i',
            'degree' => '/(?:bachelor|master|phd|degree)\s+(?:of|in)?/i',
            'award' => '/(?:award|awarded|awarded to)/i',
            'completion' => '/(?:completion|completed|successfully\s+completed)/i',
        ];

        // Check certificate type
        $detectedType = 'unknown';
        foreach ($certificateKeywords as $type => $pattern) {
            if (preg_match($pattern, $extractedText)) {
                $qualificationSignals[] = $type;
                if ($type === 'degree' && strpos($textLower, 'bachelor') !== false) {
                    $detectedType = 'bachelor_degree';
                } elseif ($type === 'degree' && strpos($textLower, 'master') !== false) {
                    $detectedType = 'master_degree';
                } elseif ($type === 'diploma') {
                    $detectedType = 'diploma';
                } elseif ($type === 'certificate') {
                    $detectedType = 'certificate_of_completion';
                }
            }
        }

        // Institution indicators
        $institutionKeywords = [
            'university' => '/university/i',
            'college' => '/college/i',
            'institute' => '/institute/i',
            'academy' => '/academy/i',
            'school' => '/school/i',
        ];

        foreach ($institutionKeywords as $inst => $pattern) {
            if (preg_match($pattern, $extractedText)) {
                $institutionSignals[] = $inst;
            }
        }

        // Field extraction patterns
        $fieldsToExtract = [
            'institution_name' => '/(?:university|college|institute|academy|school).*?([a-z\s&,]{5,}?)(?:\n|certificate|diploma|degree|award)/i',
            'qualification_level' => '/(?:bachelor|master|diploma|phd|associate|honours).*?(?:of|in|degree)?.*?([a-z\s]{3,}?)(?:\n|in|programme|field)/i',
            'field_of_study' => '/(?:in|field\s*of|discipline|programme|program|specialization).*?([a-z\s]{3,}?)(?:\n|date|awarded|graduation)/i',
            'recipient_name' => '/(?:awarded\s*to|recipient|name|this\s*is\s*to\s*certify\s*that).*?([a-z\s]{3,}?)(?:\n|on|date|in\s*recognition)/i',
            'graduation_date' => '/(?:graduation|completed|conferred|issued|awarded\s*on).*?(\d{1,2}\s*(?:january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|[\/\-]|\/)\s*\d{1,2}\s*[\/\-]?\s*\d{2,4})/i',
            'signature' => '/(?:signature|signed|authorized|director|principal|registrar).*?([a-z\s]{2,}?)(?:\n|date|stamp|seal)/i',
            'seal_stamp' => '/(?:seal|stamp|official\s*seal|institution\s*seal)/i',
            'certificate_number' => '/(?:certificate\s*no|reference|ref|no\.?).*?([a-z0-9\-\/]{5,}?)(?:\n|date|issued)/i',
        ];

        // Extract fields
        foreach ($fieldsToExtract as $fieldName => $pattern) {
            if (preg_match($pattern, $extractedText, $matches)) {
                $foundFields[$fieldName] = trim($matches[1] ?? $matches[0]);
            } else {
                $missingFields[] = $fieldName;
            }
        }

        // Validation logic
        // Core fields required: institution name, qualification level, recipient name, date
        $hasInstitution = isset($foundFields['institution_name']) && !empty($foundFields['institution_name']);
        $hasQualificationLevel = isset($foundFields['qualification_level']) && !empty($foundFields['qualification_level']);
        $hasRecipientName = isset($foundFields['recipient_name']) && !empty($foundFields['recipient_name']);
        $hasDate = isset($foundFields['graduation_date']) && !empty($foundFields['graduation_date']);

        // Allow up to 2 missing fields from the core set
        $missingCoreCount = 0;
        if (!$hasInstitution) $missingCoreCount++;
        if (!$hasQualificationLevel) $missingCoreCount++;
        if (!$hasRecipientName) $missingCoreCount++;
        if (!$hasDate) $missingCoreCount++;

        // Certificate is valid if:
        // 1. Contains certificate/diploma/degree/award indicators
        // 2. Contains institution indicators
        // 3. Has most core fields (no more than 2 missing)
        // 4. Total missing fields <= 3 (very lenient due to OCR variance)
        $isValidFormat = (
            count($qualificationSignals) > 0 &&
            count($institutionSignals) > 0 &&
            $missingCoreCount <= 2 &&
            count($missingFields) <= 3
        );

        // Calculate confidence scores
        $qualificationConfidence = (count($qualificationSignals) / count($certificateKeywords)) * 100;
        $institutionConfidence = (count($institutionSignals) / count($institutionKeywords)) * 100;

        return [
            'is_valid_format' => $isValidFormat,
            'detected_type' => $detectedType,
            'found_fields' => $foundFields,
            'missing_fields' => $missingFields,
            'missing_core_count' => $missingCoreCount,
            'qualification_signals' => $qualificationSignals,
            'institution_signals' => $institutionSignals,
            'qualification_confidence' => (int) $qualificationConfidence,
            'institution_confidence' => (int) $institutionConfidence,
            'has_core_fields' => [
                'institution' => $hasInstitution,
                'qualification_level' => $hasQualificationLevel,
                'recipient_name' => $hasRecipientName,
                'date' => $hasDate,
            ],
            'recommendation' => $this->getCertificateRecommendation($isValidFormat, $missingFields, $detectedType, $qualificationConfidence),
        ];
    }

    /**
     * Determine recommendation based on certificate validation.
     */
    private function getCertificateRecommendation(bool $isValidFormat, array $missingFields, string $detectedType, int $qualificationConfidence): string
    {
        if (!$isValidFormat) {
            return 'manual_review'; // Format doesn't match expected certificate
        }

        if (count($missingFields) > 3) {
            return 'manual_review'; // Too many fields missing
        }

        if ($detectedType === 'unknown') {
            return 'manual_review'; // Could not determine certificate type
        }

        if ($qualificationConfidence < 40) {
            return 'manual_review'; // Low confidence in qualification signals
        }

        return 'pass';
    }
}
