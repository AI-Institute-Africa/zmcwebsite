<?php

namespace App\Services\Validation;

use App\Models\ApplicationDocument;
use App\Support\FileType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class DocumentValidationService
{
    private DataNormalizationService $normalizationService;
    private ImageManager $imageManager;
    
    // Allowed file types per document type
    private array $allowedFileTypes = [
        'id_scan' => ['pdf', 'jpg', 'jpeg', 'png'],
        'passport_photo' => ['jpg', 'jpeg', 'png'],
        'employment_letter' => ['pdf', 'jpg', 'jpeg', 'png'],
        'reference_letter' => ['pdf', 'jpg', 'jpeg', 'png'],
        'educational_certificate' => ['pdf', 'jpg', 'jpeg', 'png'],
        'passport_biodata_page' => ['pdf', 'jpg', 'jpeg', 'png'],
        'clearance_letter' => ['pdf', 'jpg', 'jpeg', 'png'],
        'current_card' => ['pdf', 'jpg', 'jpeg', 'png'],
        'employer_letter' => ['pdf', 'jpg', 'jpeg', 'png'],
        'affidavit' => ['pdf', 'jpg', 'jpeg', 'png'],
        'police_report' => ['pdf', 'jpg', 'jpeg', 'png'],
        'certificate_of_incorporation' => ['pdf', 'jpg', 'jpeg', 'png'],
        'cr14' => ['pdf', 'jpg', 'jpeg', 'png'],
        'memorandum_articles' => ['pdf'],
        'audited_financials' => ['pdf'],
        'mission_statement' => ['pdf', 'doc', 'docx'],
        'editorial_charter' => ['pdf', 'doc', 'docx'],
        'code_of_ethics' => ['pdf', 'doc', 'docx'],
        'code_of_conduct' => ['pdf', 'doc', 'docx'],
        'style_book' => ['pdf', 'doc', 'docx'],
        'dummy_copy' => ['pdf'],
        'market_analysis' => ['pdf', 'doc', 'docx'],
        'cash_flow_statement' => ['pdf', 'xlsx', 'xls'],
        'balance_sheet' => ['pdf', 'xlsx', 'xls'],
        'director_id' => ['pdf', 'jpg', 'jpeg', 'png'],
        'shareholder_info' => ['pdf', 'doc', 'docx'],
    ];
    
    // Maximum file sizes in MB
    private array $maxFileSizes = [
        'photo' => 5, // passport photos
        'document' => 15, // standard documents
        'financial' => 25, // financial statements
    ];
    
    public function __construct(DataNormalizationService $normalizationService)
    {
        $this->normalizationService = $normalizationService;
        $this->imageManager = new ImageManager(new Driver());
    }
    
    /**
     * Validate document upload before processing
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @return array
     */
    public function validateUpload(UploadedFile $file, string $documentType): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedTypes = $this->allowedFileTypes[$documentType] ?? ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($extension, $allowedTypes)) {
            $result['valid'] = false;
            $result['errors'][] = "File type not allowed. Allowed types: " . implode(', ', $allowedTypes);
        }
        
        // Check file size
        $sizeMb = $file->getSize() / (1024 * 1024);
        $maxSize = $this->getMaxSizeForDocument($documentType);
        
        if ($sizeMb > $maxSize) {
            $result['valid'] = false;
            $result['errors'][] = "File size exceeds maximum allowed size of {$maxSize}MB";
        }
        
        // Check for malware (basic scan)
        if ($this->detectMalware($file)) {
            $result['valid'] = false;
            $result['errors'][] = "File appears to contain malicious content";
        }
        
        return $result;
    }
    
    /**
     * Process document and generate validation data
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @param int|null $applicationId
     * @return array
     */
    public function processDocument(UploadedFile $file, string $documentType, ?int $applicationId = null): array
    {
        $result = [
            'success' => false,
            'validation_data' => [],
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            // Generate file hash
            $sha256 = hash_file('sha256', $file->getRealPath());
            $md5 = hash_file('md5', $file->getRealPath());
            
            // Check for duplicate hash
            if ($applicationId && $this->isDuplicateHash($sha256, $documentType, $applicationId)) {
                $result['warnings'][] = "Document hash matches an existing document";
                $result['validation_data']['duplicate_hash'] = true;
            }
            
            // Extract text via OCR (simplified - real implementation would use Tesseract or similar)
            $ocrResult = $this->extractText($file, $documentType);
            
            // Detect tampering
            $tamperingResult = $this->detectTampering($file, $documentType);
            
            // Generate perceptual hash for image comparison
            $perceptualHash = null;
            if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                $perceptualHash = $this->generatePerceptualHash($file);
            }
            
            // Extract metadata
            $metadata = $this->extractMetadata($file);
            
            $result['validation_data'] = [
                'sha256' => $sha256,
                'md5' => $md5,
                'perceptual_hash' => $perceptualHash,
                'ocr_text' => $ocrResult['text'],
                'ocr_confidence' => $ocrResult['confidence'],
                'extracted_data' => $ocrResult['extracted_data'],
                'tampering_detected' => $tamperingResult['detected'],
                'tampering_details' => $tamperingResult['details'],
                'metadata' => $metadata,
                'file_size' => $file->getSize(),
                'mime_type' => FileType::safeUploadedMime($file),
                'original_extension' => strtolower($file->getClientOriginalExtension()),
            ];
            
            $result['success'] = true;
            
            // Add warnings if any
            if ($tamperingResult['detected']) {
                $result['warnings'][] = "Potential tampering detected: " . implode(', ', $tamperingResult['details']);
            }
            
            if ($ocrResult['confidence'] < 70) {
                $result['warnings'][] = "OCR confidence is low ({$ocrResult['confidence']}%)";
            }
            
        } catch (\Exception $e) {
            $result['errors'][] = "Document processing failed: " . $e->getMessage();
            Log::error('Document processing failed', [
                'document_type' => $documentType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Check if document hash is duplicate
     * 
     * @param string $hash
     * @param string $documentType
     * @param int $applicationId
     * @return bool
     */
    private function isDuplicateHash(string $hash, string $documentType, int $applicationId): bool
    {
        return ApplicationDocument::where('sha256', $hash)
            ->where('doc_type', $documentType)
            ->where('application_id', '!=', $applicationId)
            ->exists();
    }
    
    /**
     * Extract text from document via OCR
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @return array
     */
    private function extractText(UploadedFile $file, string $documentType): array
    {
        $result = [
            'text' => '',
            'confidence' => 0,
            'extracted_data' => []
        ];
        
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            
            // For images, attempt OCR (simplified - real implementation would use Tesseract)
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                // This is a placeholder - real implementation would use Tesseract OCR
                // For now, we'll simulate extraction based on document type
                $result = $this->simulateOCR($file, $documentType);
            }
            // For PDFs, attempt text extraction (simplified)
            elseif ($extension === 'pdf') {
                $result = $this->extractPDFText($file, $documentType);
            }
            
        } catch (\Exception $e) {
            Log::error('OCR extraction failed', [
                'document_type' => $documentType,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Simulate OCR extraction (placeholder for real Tesseract implementation)
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @return array
     */
    private function simulateOCR(UploadedFile $file, string $documentType): array
    {
        // In production, this would use Tesseract OCR:
        // $text = (new TesseractOCR($file->getRealPath()))->run();
        
        $result = [
            'text' => '',
            'confidence' => 0,
            'extracted_data' => []
        ];
        
        // Placeholder - different extraction based on document type
        switch ($documentType) {
            case 'id_scan':
                $result['extracted_data'] = [
                    'name' => '',
                    'id_number' => '',
                    'date_of_birth' => '',
                    'sex' => '',
                    'nationality' => ''
                ];
                $result['confidence'] = 75; // Simulated confidence
                break;
                
            case 'passport_biodata_page':
                $result['extracted_data'] = [
                    'full_name' => '',
                    'passport_number' => '',
                    'nationality' => '',
                    'date_of_birth' => '',
                    'sex' => '',
                    'place_of_birth' => '',
                    'issue_date' => '',
                    'expiry_date' => ''
                ];
                $result['confidence'] = 80;
                break;
                
            case 'employment_letter':
                $result['extracted_data'] = [
                    'employee_name' => '',
                    'organisation_name' => '',
                    'position' => '',
                    'date' => '',
                    'signatory_name' => '',
                    'letterhead_detected' => false
                ];
                $result['confidence'] = 70;
                break;
                
            case 'educational_certificate':
                $result['extracted_data'] = [
                    'name' => '',
                    'institution' => '',
                    'qualification' => '',
                    'date' => ''
                ];
                $result['confidence'] = 75;
                break;
                
            case 'certificate_of_incorporation':
                $result['extracted_data'] = [
                    'company_name' => '',
                    'registration_number' => '',
                    'incorporation_date' => '',
                    'registered_address' => ''
                ];
                $result['confidence'] = 85;
                break;
                
            default:
                $result['confidence'] = 60;
                break;
        }
        
        return $result;
    }
    
    /**
     * Extract text from PDF (placeholder)
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @return array
     */
    private function extractPDFText(UploadedFile $file, string $documentType): array
    {
        // In production, this would use a PDF text extraction library
        // like spatie/pdf-to-text or similar
        
        $result = [
            'text' => '',
            'confidence' => 65,
            'extracted_data' => []
        ];
        
        // Placeholder - in production would actually extract PDF text
        return $result;
    }
    
    /**
     * Detect document tampering
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @return array
     */
    private function detectTampering(UploadedFile $file, string $documentType): array
    {
        $result = [
            'detected' => false,
            'details' => []
        ];
        
        try {
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $result = $this->detectImageTampering($file, $documentType);
            } elseif ($extension === 'pdf') {
                $result = $this->detectPDFTampering($file, $documentType);
            }
            
        } catch (\Exception $e) {
            Log::error('Tampering detection failed', [
                'document_type' => $documentType,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Detect image tampering
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @return array
     */
    private function detectImageTampering(UploadedFile $file, string $documentType): array
    {
        $result = [
            'detected' => false,
            'details' => []
        ];
        
        try {
            $image = $this->imageManager->read($file->getRealPath());
            
            // Check for unusual compression artifacts
            $exif = @exif_read_data($file->getRealPath());
            if ($exif === false) {
                $result['details'][] = 'No EXIF data found';
            } else {
                // Check for editing software signatures
                if (isset($exif['Software'])) {
                    $software = strtolower($exif['Software']);
                    $editors = ['photoshop', 'gimp', 'paint', 'editor'];
                    foreach ($editors as $editor) {
                        if (str_contains($software, $editor)) {
                            $result['detected'] = true;
                            $result['details'][] = 'Image appears edited with software';
                            break;
                        }
                    }
                }
            }
            
            // Check for inconsistent quality
            $width = $image->width();
            $height = $image->height();
            
            // Very low resolution might indicate manipulation
            if ($width < 300 || $height < 200) {
                $result['detected'] = true;
                $result['details'][] = 'Image resolution unusually low';
            }
            
            // ID-specific checks
            if (in_array($documentType, ['id_scan', 'passport_biodata_page'])) {
                // Check for cropped areas
                $aspectRatio = $width / $height;
                
                // ID cards typically have specific aspect ratios
                if ($documentType === 'id_scan') {
                    if ($aspectRatio < 1.3 || $aspectRatio > 1.7) {
                        $result['details'][] = 'ID card aspect ratio unusual';
                    }
                }
                
                // Check for blurred regions (simplified check)
                // In production, this would use more sophisticated analysis
            }
            
            // Check for multiple distinct regions with different compression
            // This would indicate splicing
            // In production, this would use Error Level Analysis
            
        } catch (\Exception $e) {
            Log::error('Image tampering detection failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Detect PDF tampering
     * 
     * @param UploadedFile $file
     * @param string $documentType
     * @return array
     */
    private function detectPDFTampering(UploadedFile $file, string $documentType): array
    {
        $result = [
            'detected' => false,
            'details' => []
        ];
        
        // In production, this would analyze PDF structure for:
        // - Multiple layers
        // - Inconsistent fonts
        // - Missing security features
        // - Modified metadata
        
        return $result;
    }
    
    /**
     * Generate perceptual hash for image comparison
     * 
     * @param UploadedFile $file
     * @return string|null
     */
    private function generatePerceptualHash(UploadedFile $file): ?string
    {
        try {
            $image = $this->imageManager->read($file->getRealPath());
            
            // Resize to small size for perceptual hashing
            $image->resize(8, 8);
            
            // Convert to grayscale
            $image->grayscale();
            
            // Get average pixel value
            $pixels = [];
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 8; $x++) {
                    $color = $image->pickColor($x, $y);
                    $pixels[] = $color['r']; // Grayscale, so r=g=b
                }
            }
            
            $average = array_sum($pixels) / count($pixels);
            
            // Generate hash based on comparison to average
            $hash = '';
            foreach ($pixels as $pixel) {
                $hash .= ($pixel >= $average) ? '1' : '0';
            }
            
            // Convert to hexadecimal
            return base_convert($hash, 2, 16);
            
        } catch (\Exception $e) {
            Log::error('Perceptual hash generation failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Extract metadata from file
     * 
     * @param UploadedFile $file
     * @return array
     */
    private function extractMetadata(UploadedFile $file): array
    {
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => FileType::safeUploadedMime($file),
            'extension' => strtolower($file->getClientOriginalExtension()),
            'last_modified' => $file->getCTime(),
        ];
        
        // Extract EXIF data from images
        if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'tiff'])) {
            $exif = @exif_read_data($file->getRealPath());
            if ($exif !== false) {
                $metadata['exif'] = [
                    'camera' => $exif['Make'] . ' ' . ($exif['Model'] ?? ''),
                    'datetime' => $exif['DateTime'] ?? null,
                    'software' => $exif['Software'] ?? null,
                ];
            }
        }
        
        return $metadata;
    }
    
    /**
     * Get maximum file size for document type
     * 
     * @param string $documentType
     * @return float
     */
    private function getMaxSizeForDocument(string $documentType): float
    {
        if (in_array($documentType, ['passport_photo'])) {
            return $this->maxFileSizes['photo'];
        }
        
        if (in_array($documentType, ['audited_financials', 'cash_flow_statement', 'balance_sheet'])) {
            return $this->maxFileSizes['financial'];
        }
        
        return $this->maxFileSizes['document'];
    }
    
    /**
     * Basic malware detection (placeholder)
     * In production, this would integrate with antivirus scanning
     * 
     * @param UploadedFile $file
     * @return bool
     */
    private function detectMalware(UploadedFile $file): bool
    {
        // Basic checks for suspicious patterns
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'sh', 'php', 'js', 'vbs'];
        $fileName = strtolower($file->getClientOriginalName());
        
        foreach ($dangerousExtensions as $ext) {
            if (str_ends_with($fileName, '.' . $ext)) {
                return true;
            }
        }
        
        // Check for double extensions
        $parts = explode('.', $fileName);
        if (count($parts) > 2) {
            $extensions = array_slice($parts, -2);
            if (in_array($extensions[0], ['php', 'exe', 'bat'])) {
                return true;
            }
        }
        
        // In production, this would integrate with ClamAV or similar
        return false;
    }
    
    /**
     * Compare extracted data with entered data
     * 
     * @param array $extractedData
     * @param array $enteredData
     * @param string $documentType
     * @return array
     */
    public function compareExtractedData(array $extractedData, array $enteredData, string $documentType): array
    {
        $result = [
            'match_status' => 'unknown',
            'mismatches' => [],
            'warnings' => [],
            'overall_score' => 0
        ];
        
        switch ($documentType) {
            case 'id_scan':
                $result = $this->compareIdData($extractedData, $enteredData);
                break;
                
            case 'passport_biodata_page':
                $result = $this->comparePassportData($extractedData, $enteredData);
                break;
                
            case 'employment_letter':
                $result = $this->compareEmploymentLetterData($extractedData, $enteredData);
                break;
                
            case 'educational_certificate':
                $result = $this->compareCertificateData($extractedData, $enteredData);
                break;
                
            case 'certificate_of_incorporation':
                $result = $this->compareCompanyData($extractedData, $enteredData);
                break;
        }
        
        return $result;
    }
    
    /**
     * Compare ID document data
     */
    private function compareIdData(array $extracted, array $entered): array
    {
        $result = [
            'match_status' => 'unknown',
            'mismatches' => [],
            'warnings' => [],
            'overall_score' => 0
        ];
        
        // Compare names
        if (!empty($entered['surname']) && !empty($extracted['name'])) {
            $nameComparison = $this->normalizationService->compareNames(
                $entered['surname'] . ' ' . ($entered['first_name'] ?? '') . ' ' . ($entered['other_names'] ?? ''),
                $extracted['name']
            );
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $result['mismatches'][] = 'Name on ID does not match entered name';
            } elseif ($nameComparison['match_status'] === 'manual_review') {
                $result['warnings'][] = 'Name match requires manual review';
            }
            
            $result['overall_score'] += $nameComparison['match_score'];
        }
        
        // Compare ID numbers
        if (!empty($entered['national_id']) && !empty($extracted['id_number'])) {
            $idComparison = $this->normalizationService->compareIds(
                $entered['national_id'],
                $extracted['id_number'],
                'national_id'
            );
            
            if (!$idComparison['match']) {
                $result['mismatches'][] = 'ID number on document does not match entered ID';
                $result['match_status'] = 'hard_stop';
            } else {
                $result['overall_score'] += 40;
            }
        }
        
        // Determine overall status
        if (!empty($result['mismatches'])) {
            $result['match_status'] = 'hard_stop';
        } elseif (!empty($result['warnings'])) {
            $result['match_status'] = 'manual_review';
        } else {
            $result['match_status'] = 'match';
        }
        
        return $result;
    }
    
    /**
     * Compare passport data
     */
    private function comparePassportData(array $extracted, array $entered): array
    {
        $result = [
            'match_status' => 'unknown',
            'mismatches' => [],
            'warnings' => [],
            'overall_score' => 0
        ];
        
        // Compare names
        if (!empty($entered['surname']) && !empty($extracted['full_name'])) {
            $nameComparison = $this->normalizationService->compareNames(
                $entered['surname'] . ' ' . ($entered['first_name'] ?? '') . ' ' . ($entered['other_names'] ?? ''),
                $extracted['full_name']
            );
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $result['mismatches'][] = 'Name on passport does not match entered name';
            }
            
            $result['overall_score'] += $nameComparison['match_score'];
        }
        
        // Compare passport numbers
        if (!empty($entered['passport_number']) && !empty($extracted['passport_number'])) {
            $passportComparison = $this->normalizationService->compareIds(
                $entered['passport_number'],
                $extracted['passport_number'],
                'passport'
            );
            
            if (!$passportComparison['match']) {
                $result['mismatches'][] = 'Passport number does not match';
                $result['match_status'] = 'hard_stop';
            } else {
                $result['overall_score'] += 40;
            }
        }
        
        // Compare dates of birth
        if (!empty($entered['date_of_birth']) && !empty($extracted['date_of_birth'])) {
            if ($entered['date_of_birth'] !== $extracted['date_of_birth']) {
                $result['mismatches'][] = 'Date of birth does not match';
            } else {
                $result['overall_score'] += 20;
            }
        }
        
        // Determine overall status
        if (!empty($result['mismatches'])) {
            $result['match_status'] = 'hard_stop';
        } elseif (!empty($result['warnings'])) {
            $result['match_status'] = 'manual_review';
        } else {
            $result['match_status'] = 'match';
        }
        
        return $result;
    }
    
    /**
     * Compare employment letter data
     */
    private function compareEmploymentLetterData(array $extracted, array $entered): array
    {
        $result = [
            'match_status' => 'unknown',
            'mismatches' => [],
            'warnings' => [],
            'overall_score' => 0
        ];
        
        // Compare employee names
        if (!empty($extracted['employee_name']) && !empty($entered['surname'])) {
            $enteredName = $entered['surname'] . ' ' . ($entered['first_name'] ?? '');
            $nameComparison = $this->normalizationService->compareNames(
                $extracted['employee_name'],
                $enteredName
            );
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $result['mismatches'][] = 'Name on employment letter does not match applicant name';
            }
            
            $result['overall_score'] += $nameComparison['match_score'];
        }
        
        // Compare organization names
        if (!empty($extracted['organisation_name']) && !empty($entered['organisation_name'])) {
            $orgComparison = $this->normalizationService->compareOrganizationNames(
                $extracted['organisation_name'],
                $entered['organisation_name']
            );
            
            if ($orgComparison['match_status'] === 'hard_stop') {
                $result['mismatches'][] = 'Organization name on letter does not match entered organization';
            }
            
            $result['overall_score'] += $orgComparison['match_score'];
        }
        
        // Check for letterhead
        if (!$extracted['letterhead_detected'] ?? false) {
            $result['warnings'][] = 'Letterhead not clearly detected';
        }
        
        // Determine overall status
        if (!empty($result['mismatches'])) {
            $result['match_status'] = 'hard_stop';
        } elseif (!empty($result['warnings'])) {
            $result['match_status'] = 'manual_review';
        } else {
            $result['match_status'] = 'match';
        }
        
        return $result;
    }
    
    /**
     * Compare certificate data
     */
    private function compareCertificateData(array $extracted, array $entered): array
    {
        $result = [
            'match_status' => 'unknown',
            'mismatches' => [],
            'warnings' => [],
            'overall_score' => 0
        ];
        
        // Compare names
        if (!empty($extracted['name']) && !empty($entered['surname'])) {
            $enteredName = $entered['surname'] . ' ' . ($entered['first_name'] ?? '') . ' ' . ($entered['other_names'] ?? '');
            $nameComparison = $this->normalizationService->compareNames(
                $extracted['name'],
                $enteredName
            );
            
            if ($nameComparison['match_status'] === 'hard_stop') {
                $result['mismatches'][] = 'Name on certificate does not match applicant name';
            } elseif ($nameComparison['match_status'] === 'manual_review') {
                $result['warnings'][] = 'Name on certificate may indicate maiden name or variation';
            }
            
            $result['overall_score'] += $nameComparison['match_score'];
        }
        
        // Determine overall status
        if (!empty($result['mismatches'])) {
            $result['match_status'] = 'hard_stop';
        } elseif (!empty($result['warnings'])) {
            $result['match_status'] = 'manual_review';
        } else {
            $result['match_status'] = 'match';
        }
        
        return $result;
    }
    
    /**
     * Compare company data
     */
    private function compareCompanyData(array $extracted, array $entered): array
    {
        $result = [
            'match_status' => 'unknown',
            'mismatches' => [],
            'warnings' => [],
            'overall_score' => 0
        ];
        
        // Compare company names
        if (!empty($extracted['company_name']) && !empty($entered['company_name'])) {
            $orgComparison = $this->normalizationService->compareOrganizationNames(
                $extracted['company_name'],
                $entered['company_name']
            );
            
            if ($orgComparison['match_status'] === 'hard_stop') {
                $result['mismatches'][] = 'Company name on document does not match entered company name';
            }
            
            $result['overall_score'] += $orgComparison['match_score'];
        }
        
        // Determine overall status
        if (!empty($result['mismatches'])) {
            $result['match_status'] = 'hard_stop';
        } elseif (!empty($result['warnings'])) {
            $result['match_status'] = 'manual_review';
        } else {
            $result['match_status'] = 'match';
        }
        
        return $result;
    }
}
