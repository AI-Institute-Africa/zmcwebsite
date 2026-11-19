<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Support\FileType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class DocumentSecurityService
{
    /**
     * Scan a document for security issues before processing.
     * Checks: MIME type, file health, duplicates, malware (optional).
     *
     * @param ApplicationDocument $document
     * @param Application $application
     * @return array Result with status, confidence_score, and details
     */
    public function scanDocument(ApplicationDocument $document, Application $application): array
    {
        $results = [];
        $file = $document->file_path;
        $disk = Storage::disk(config('filesystems.default'));

        // Check 1: File exists and is readable
        if (!$disk->exists($file)) {
            return [
                'status' => 'fail',
                'check_type' => 'file_health',
                'confidence_score' => 100,
                'error_message' => 'File not found on disk.',
                'details' => ['file_path' => $file],
            ];
        }

        // Check 2: Verify MIME type using magic bytes, not extension
        $expectedType = $document->expected_document_type ?? $document->doc_type ?? 'unknown';
        $mimeCheckResult = $this->verifyMimeType($disk, $file, $expectedType);
        if ($mimeCheckResult['status'] === 'fail') {
            return array_merge($mimeCheckResult, ['check_type' => 'mime_verify']);
        }

        // Check 3: File health (size, corruption indicators)
        $fileHealthResult = $this->checkFileHealth($disk, $file);
        if ($fileHealthResult['status'] === 'fail') {
            return array_merge($fileHealthResult, ['check_type' => 'file_health']);
        }

        // Check 4: Duplicate upload detection
        $duplicateResult = $this->checkForDuplicates($application, $disk, $file);
        if ($duplicateResult['status'] === 'warning') {
            // Don't fail, but flag warning
            $results['duplicate_warning'] = $duplicateResult;
        }

        // Check 5: Malware scan (optional, if enabled)
        if (config('intake-automation.enable_malware_scanning')) {
            $malwareResult = $this->scanForMalware($disk, $file);
            if ($malwareResult['status'] === 'fail') {
                return array_merge($malwareResult, ['check_type' => 'virus_scan']);
            }
        }

        // All checks passed
        return [
            'status' => 'pass',
            'check_type' => 'all_security_checks',
            'confidence_score' => 95,
            'details' => array_merge(
                $mimeCheckResult['details'] ?? [],
                $fileHealthResult['details'] ?? [],
                ['duplicate_check' => $duplicateResult ?? null]
            ),
        ];
    }

    /**
     * Verify MIME type using magic bytes, not just file extension.
     */
    private function verifyMimeType(object $disk, string $filePath, string $expectedDocType): array
    {
        try {
            // Read file header for magic byte detection
            $stream = $disk->readStream($filePath);
            $header = fread($stream, 512); // Read first 512 bytes
            fclose($stream);

            if (empty($header)) {
                return [
                    'status' => 'fail',
                    'confidence_score' => 100,
                    'error_message' => 'File is empty or unreadable.',
                    'details' => ['file_path' => $filePath],
                ];
            }

            // Get filename to check extension
            $filename = basename($filePath);
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $actualMimeType = FileType::mimeFromHeader($header, $extension);

            // Allowed MIME types by document type
            $allowedMimes = $this->getAllowedMimeTypes($expectedDocType);

            // Check if actual MIME matches expected
            if (!$actualMimeType || !in_array($actualMimeType, $allowedMimes, true)) {
                return [
                    'status' => 'fail',
                    'confidence_score' => 100,
                    'error_message' => "File type mismatch. Expected: " . implode(', ', $allowedMimes) . ", Got: " . ($actualMimeType ?: 'unknown'),
                    'details' => [
                        'file_path' => $filePath,
                        'actual_mime' => $actualMimeType,
                        'expected_type' => $expectedDocType,
                        'extension' => $extension,
                    ],
                ];
            }

            return [
                'status' => 'pass',
                'confidence_score' => 100,
                'details' => [
                    'file_path' => $filePath,
                    'actual_mime' => $actualMimeType,
                    'expected_type' => $expectedDocType,
                    'extension' => $extension,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'fail',
                'confidence_score' => 100,
                'error_message' => 'Error during MIME verification: ' . $e->getMessage(),
                'details' => ['file_path' => $filePath],
            ];
        }
    }

    /**
     * Check file health: size, corruption indicators.
     */
    private function checkFileHealth(object $disk, string $filePath): array
    {
        try {
            $fileSize = $disk->size($filePath);

            // Check minimum file size (must not be empty)
            if ($fileSize < 100) {
                return [
                    'status' => 'fail',
                    'confidence_score' => 100,
                    'error_message' => 'File is too small (likely empty or corrupted). Size: ' . $fileSize . ' bytes.',
                    'details' => ['file_path' => $filePath, 'file_size' => $fileSize],
                ];
            }

            // Check maximum file size (safety limit)
            $maxSize = 52428800; // 50MB default
            if ($fileSize > $maxSize) {
                return [
                    'status' => 'fail',
                    'confidence_score' => 100,
                    'error_message' => 'File is too large. Max allowed: 50MB, Got: ' . round($fileSize / 1024 / 1024, 2) . 'MB',
                    'details' => ['file_path' => $filePath, 'file_size' => $fileSize],
                ];
            }

            return [
                'status' => 'pass',
                'confidence_score' => 100,
                'details' => [
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_size_mb' => round($fileSize / 1024 / 1024, 2),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'fail',
                'confidence_score' => 100,
                'error_message' => 'Error checking file health: ' . $e->getMessage(),
                'details' => ['file_path' => $filePath],
            ];
        }
    }

    /**
     * Detect duplicate uploads within an application.
     */
    private function checkForDuplicates(Application $application, object $disk, string $filePath): array
    {
        try {
            // Read file content and compute hash
            $fileContent = $disk->get($filePath);
            $fileHash = hash('sha256', $fileContent);

            // Check if this hash already exists in other documents of the same application
            $existingDocument = ApplicationDocument::where('application_id', $application->id)
                ->where('file_path', '!=', $filePath)
                ->get()
                ->first(function ($doc) use ($disk, $fileHash) {
                    try {
                        $docHash = hash('sha256', $disk->get($doc->file_path));
                        return $docHash === $fileHash;
                    } catch (\Exception $e) {
                        return false;
                    }
                });

            if ($existingDocument) {
                return [
                    'status' => 'warning',
                    'confidence_score' => 100,
                    'error_message' => 'Duplicate file detected. This document was already uploaded.',
                    'details' => [
                        'file_path' => $filePath,
                        'file_hash' => $fileHash,
                        'duplicate_of' => $existingDocument->id,
                    ],
                ];
            }

            return [
                'status' => 'pass',
                'confidence_score' => 100,
                'details' => [
                    'file_path' => $filePath,
                    'file_hash' => $fileHash,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'confidence_score' => 50,
                'error_message' => 'Error checking duplicates: ' . $e->getMessage(),
                'details' => ['file_path' => $filePath],
            ];
        }
    }

    /**
     * Malware scan using ClamAV (optional, requires system setup).
     * This is a stub; actual implementation depends on ClamAV installation.
     */
    private function scanForMalware(object $disk, string $filePath): array
    {
        // Stub: ClamAV integration would go here
        // For now, return pass (no scanning performed)
        return [
            'status' => 'pass',
            'confidence_score' => 0, // Confidence is 0 because we're not scanning
            'details' => [
                'file_path' => $filePath,
                'malware_scan' => 'not_performed',
                'note' => 'ClamAV scanning not configured. Enable in config/intake-automation.php',
            ],
        ];
    }

    /**
     * Get allowed MIME types for a document type.
     */
    private function getAllowedMimeTypes(string $documentType): array
    {
        // Normalize the versioned upload names used by the AP3 form to the
        // canonical validation families.
        if (str_starts_with($documentType, 'past_work_sample')) {
            $documentType = 'past_work';
        } elseif (str_starts_with($documentType, 'educational_certificate')) {
            $documentType = 'graduation_certificate';
        } elseif (in_array($documentType, ['id_scan', 'national_id'], true)) {
            $documentType = 'local_national_id';
        }

        $mimeMap = [
            'passport' => ['image/jpeg', 'image/png', 'application/pdf'],
            'local_national_id' => ['image/jpeg', 'image/png', 'application/pdf'],
            'passport_photo' => ['image/jpeg', 'image/png'],
            'graduation_certificate' => ['image/jpeg', 'image/png', 'application/pdf'],
            'employment_letter' => ['image/jpeg', 'image/png', 'application/pdf'],
            'clearance_letter' => ['image/jpeg', 'image/png', 'application/pdf'],
            'payment_proof' => ['image/jpeg', 'image/png', 'application/pdf'],
            'past_work' => [
                'image/jpeg',
                'image/png',
                'application/pdf',
                'video/mp4',
                'audio/mpeg',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];

        return $mimeMap[$documentType] ?? ['image/jpeg', 'image/png', 'application/pdf'];
    }
}
