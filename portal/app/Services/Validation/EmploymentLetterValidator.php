<?php

namespace App\Services\Validation;

use App\Models\ApplicationDocument;
use App\Models\EmployerVerification;
use Illuminate\Support\Facades\DB;

class EmploymentLetterValidator
{
    private NameNormalizerService $nameNormalizer;
    private const MIN_LETTERHEAD_CONFIDENCE = 0.75;
    private const MAX_EMPLOYER_REUSE_ALLOWED = 5;

    public function __construct(NameNormalizerService $nameNormalizer)
    {
        $this->nameNormalizer = $nameNormalizer;
    }

    /**
     * Validate employment letter and extract employer details
     *
     * @param string $applicantName
     * @param string $applicantFirstName
     * @param string $applicantSurname
     * @param ApplicationDocument $document
     * @return array
     */
    public function validateEmploymentLetter(
        string $applicantName,
        string $applicantFirstName,
        string $applicantSurname,
        ApplicationDocument $document
    ): array {
        $extraction = $this->extractEmploymentDetails($document);

        if ($extraction['error']) {
            return [
                'valid' => false,
                'error' => $extraction['error'],
                'hard_stop' => true,
                'message' => 'Employment letter could not be read. Please upload a clear letter.',
                'extraction' => null,
            ];
        }

        $details = $extraction['data'];

        // Hard-stop: Employee name must match
        $nameMatch = $this->validateEmployeeName(
            $applicantName,
            $applicantFirstName,
            $applicantSurname,
            $details
        );

        if (!$nameMatch['matches']) {
            return [
                'valid' => false,
                'error' => 'NAME_MISMATCH',
                'hard_stop' => true,
                'message' => 'The name in the employment letter does not match your application details.',
                'mismatch_reason' => $nameMatch['reason'],
                'extraction' => $details,
            ];
        }

        // Validate letterhead
        $letterheadCheck = $this->validateLetterhead($details);
        if (!$letterheadCheck['valid']) {
            return [
                'valid' => false,
                'error' => 'LETTERHEAD_INVALID',
                'hard_stop' => true,
                'message' => 'The employment letter appears to be unsigned or not on official letterhead.',
                'extraction' => $details,
            ];
        }

        // Check for missing critical fields
        $missingFields = $this->validateRequiredFields($details);
        if (count($missingFields) > 0) {
            return [
                'valid' => false,
                'error' => 'MISSING_FIELDS',
                'hard_stop' => true,
                'message' => 'Employment letter is missing required information: ' . implode(', ', $missingFields),
                'missing_fields' => $missingFields,
                'extraction' => $details,
            ];
        }

        // Check for reuse patterns
        $reuseCheck = $this->checkEmploymentLetterReuse($document, $details);
        if ($reuseCheck['reused']) {
            return [
                'valid' => false,
                'error' => 'DOCUMENT_REUSED',
                'hard_stop' => $reuseCheck['hard_stop'],
                'message' => 'This employment letter appears to have been used by another applicant.',
                'reuse_count' => $reuseCheck['reuse_count'],
                'extraction' => $details,
            ];
        }

        // Validate employer exists and is active
        $employerCheck = $this->validateEmployer($details);
        if (!$employerCheck['exists']) {
            return [
                'valid' => false,
                'error' => 'EMPLOYER_NOT_REGISTERED',
                'hard_stop' => false, // Soft warning - officer can override
                'message' => 'The employer does not appear to be registered. Officer review recommended.',
                'employer_risk' => 'unregistered',
                'extraction' => $details,
            ];
        }

        // Validate signature
        $signatureCheck = $this->validateSignature($details);
        if (!$signatureCheck['valid']) {
            return [
                'valid' => false,
                'error' => 'SIGNATURE_INVALID',
                'hard_stop' => false,
                'message' => 'Employment letter signature could not be verified. Officer review recommended.',
                'signature_confidence' => $signatureCheck['confidence'],
                'extraction' => $details,
            ];
        }

        // All checks passed
        return [
            'valid' => true,
            'hard_stop' => false,
            'message' => 'Employment letter validated successfully.',
            'extraction' => $details,
            'employer_verified' => $employerCheck['verified'],
            'risks' => [],
        ];
    }

    /**
     * Extract employment details from OCR text
     *
     * @param ApplicationDocument $document
     * @return array
     */
    private function extractEmploymentDetails(ApplicationDocument $document): array
    {
        if (!$document->extracted_text) {
            return [
                'error' => 'OCR_FAILED',
                'data' => null,
            ];
        }

        $text = $document->extracted_text;
        $patterns = [
            'employee_name' => '/(?:employee|staff|hereby|certif[iy]|person)\s+(?:name|named|is)\s*[:=]?\s*([A-Z][A-Za-z\s\-\.]+)/i',
            'position' => '/(?:position|designation|title|role)\s*[:=]?\s*([A-Za-z\s\-\.]+?)(?:[\.\n,]|department|since)/i',
            'department' => '/(?:department|division|section)\s*[:=]?\s*([A-Za-z\s\-\.]+?)(?:[\.\n,]|since|period)/i',
            'employment_date' => '/(?:employ|period|since|from)\s+([A-Za-z0-9\-\s\.]+)(?:\s+to\s+)?([A-Za-z0-9\-\s\.]*)/i',
            'salary' => '/(?:salary|remuneration|monthly|annual)\s+(?:of)?\s*(?:ZWL|USD|USD|EUR)?\s*([0-9,\.]+)/i',
            'employer_name' => '/(?:employer|company|organization|on behalf of)\s*[:=]?\s*([A-Z][A-Za-z\s\&\-\.]+?)(?:\.|\n|,)/i',
            'signatory_name' => '/(?:signed by|signature of|authorized by)\s*[:=]?\s*([A-Z][A-Za-z\s\-\.]+?)(?:\.|\n)/i',
            'signatory_title' => '/(?:signatory|authorized by|approved by).*?(?:title|position)\s*[:=]?\s*([A-Za-z\s\-\.]+?)(?:[\.\n,])/i',
            'letter_date' => '/(?:date|issued|written)\s*[:=]?\s*([0-9]{1,2}[-\/][0-9]{1,2}[-\/][0-9]{2,4})/i',
        ];

        $extracted = [];
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $extracted[$key] = trim($matches[1]);
            }
        }

        return [
            'error' => null,
            'data' => [
                'employee_name' => $extracted['employee_name'] ?? null,
                'position' => $extracted['position'] ?? null,
                'department' => $extracted['department'] ?? null,
                'employment_date' => $extracted['employment_date'] ?? null,
                'salary' => $extracted['salary'] ?? null,
                'employer_name' => $extracted['employer_name'] ?? null,
                'signatory_name' => $extracted['signatory_name'] ?? null,
                'signatory_title' => $extracted['signatory_title'] ?? null,
                'letter_date' => $extracted['letter_date'] ?? null,
                'ocr_confidence' => $document->ocr_confidence ?? 0.8,
                'document_hash' => $document->file_hash,
                'letterhead_detected' => $this->detectLetterhead($text),
                'signature_detected' => $this->detectSignature($text),
            ],
        ];
    }

    /**
     * Validate employee name matches applicant
     *
     * @param string $applicantName
     * @param string $applicantFirstName
     * @param string $applicantSurname
     * @param array $letterDetails
     * @return array
     */
    private function validateEmployeeName(
        string $applicantName,
        string $applicantFirstName,
        string $applicantSurname,
        array $letterDetails
    ): array {
        $letterEmployeeName = $letterDetails['employee_name'] ?? null;

        if (!$letterEmployeeName) {
            return [
                'matches' => false,
                'reason' => 'Employee name not found in letter',
                'confidence' => 0,
            ];
        }

        $equivalent = $this->nameNormalizer->areNamesEquivalent(
            $applicantName,
            $letterEmployeeName,
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
            'reason' => 'Employee name in letter does not match application name',
            'confidence' => 0,
        ];
    }

    /**
     * Validate letterhead presence and authenticity
     *
     * @param array $details
     * @return array
     */
    private function validateLetterhead(array $details): array
    {
        $hasLetterhead = $details['letterhead_detected'] ?? false;
        $hasSignature = $details['signature_detected'] ?? false;
        $hasSignatoryName = !empty($details['signatory_name']);
        $hasLetterDate = !empty($details['letter_date']);

        if (!$hasLetterhead) {
            return [
                'valid' => false,
                'reason' => 'No letterhead detected',
            ];
        }

        if (!$hasSignature) {
            return [
                'valid' => false,
                'reason' => 'No signature detected on letter',
            ];
        }

        if (!$hasSignatoryName) {
            return [
                'valid' => false,
                'reason' => 'Signatory name not found',
            ];
        }

        return [
            'valid' => true,
            'reason' => 'Letterhead validated',
        ];
    }

    /**
     * Validate required fields are present
     *
     * @param array $details
     * @return array
     */
    private function validateRequiredFields(array $details): array
    {
        $required = [
            'employee_name' => 'Employee name',
            'position' => 'Job position',
            'employer_name' => 'Employer name',
            'signatory_name' => 'Signatory name',
            'letter_date' => 'Letter date',
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
     * Check for document reuse across applicants
     *
     * @param ApplicationDocument $document
     * @param array $details
     * @return array
     */
    private function checkEmploymentLetterReuse(
        ApplicationDocument $document,
        array $details
    ): array {
        $documentHash = $details['document_hash'];

        // Check file hash for exact duplicates
        $exactDuplicates = DB::table('document_reuse_patterns')
            ->where('original_file_hash', $documentHash)
            ->where('application_document_id', '!=', $document->id)
            ->count();

        if ($exactDuplicates > 0) {
            return [
                'reused' => true,
                'hard_stop' => true,
                'reuse_count' => $exactDuplicates,
                'reason' => 'Exact duplicate of another application',
            ];
        }

        // Check employer + signatory combination
        $employerName = $details['employer_name'];
        $signatoryName = $details['signatory_name'];

        $patternDuplicates = DB::table('document_reuse_patterns')
            ->where('employer_name', strtoupper($employerName))
            ->where('signatory_name', strtoupper($signatoryName))
            ->where('application_document_id', '!=', $document->id)
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();

        if ($patternDuplicates > self::MAX_EMPLOYER_REUSE_ALLOWED) {
            return [
                'reused' => true,
                'hard_stop' => false, // Soft warning
                'reuse_count' => $patternDuplicates,
                'reason' => 'Employment letter pattern appears frequently',
            ];
        }

        return [
            'reused' => false,
            'hard_stop' => false,
            'reuse_count' => $patternDuplicates,
        ];
    }

    /**
     * Validate employer exists and is active
     *
     * @param array $details
     * @return array
     */
    private function validateEmployer(array $details): array
    {
        $employerName = $details['employer_name'] ?? null;

        if (!$employerName) {
            return [
                'exists' => false,
                'verified' => false,
                'reason' => 'Employer name not found',
            ];
        }

        $normalizedName = $this->nameNormalizer->normalizeOrganizationName($employerName);

        $employer = EmployerVerification::where(
            DB::raw('UPPER(employer_name)'),
            strtoupper($normalizedName)
        )
            ->where('is_active', true)
            ->first();

        if ($employer) {
            return [
                'exists' => true,
                'verified' => true,
                'employer_id' => $employer->id,
                'reason' => 'Employer verified in registry',
            ];
        }

        // Check organization mapping table
        $mapped = DB::table('organization_name_mapping')
            ->where(DB::raw('UPPER(canonical_name)'), strtoupper($normalizedName))
            ->first();

        if ($mapped) {
            return [
                'exists' => true,
                'verified' => true,
                'mapped_name' => $mapped->canonical_name,
                'reason' => 'Employer found in registry',
            ];
        }

        return [
            'exists' => false,
            'verified' => false,
            'reason' => 'Employer not found in registry',
        ];
    }

    /**
     * Validate signature presence and characteristics
     *
     * @param array $details
     * @return array
     */
    private function validateSignature(array $details): array
    {
        $signatureDetected = $details['signature_detected'] ?? false;
        $signatoryName = $details['signatory_name'] ?? null;
        $signatoryTitle = $details['signatory_title'] ?? null;

        if (!$signatureDetected) {
            return [
                'valid' => false,
                'confidence' => 0,
                'reason' => 'No signature detected',
            ];
        }

        if (!$signatoryName || !$signatoryTitle) {
            return [
                'valid' => false,
                'confidence' => 0.5,
                'reason' => 'Signatory information incomplete',
            ];
        }

        // Signature is present and signatories are identified
        return [
            'valid' => true,
            'confidence' => 0.85,
            'reason' => 'Signature validated',
        ];
    }

    /**
     * Detect letterhead in document text
     *
     * @param string $text
     * @return bool
     */
    private function detectLetterhead(string $text): bool
    {
        $letterheadIndicators = [
            'letterhead',
            'company address',
            'tel:',
            'email:',
            'fax:',
            'pvt ltd',
            'private limited',
            'limited',
            'plc',
        ];

        $text = strtolower($text);
        foreach ($letterheadIndicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect signature in document
     *
     * @param string $text
     * @return bool
     */
    private function detectSignature(string $text): bool
    {
        $signatureIndicators = [
            'signed',
            'signature',
            '__________',
            'authorised',
            'approved by',
            'dated',
            'stamp',
        ];

        $text = strtolower($text);
        foreach ($signatureIndicators as $indicator) {
            if (strpos($text, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }
}
