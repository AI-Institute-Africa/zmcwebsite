<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PastWorkVerificationService
{
    private DocumentClassificationService $classificationService;

    public function __construct(DocumentClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
    }

    /**
     * Verify past work evidence (either link or file).
     */
    public function verifyPastWork(Application $application): array
    {
        // Check for past work link first (preferred)
        if ($application->past_work_link) {
            return $this->verifyPastWorkLink($application->past_work_link);
        }

        // Check for past work file
        $pastWorkDoc = $application->documents()
            ->where('doc_type', 'past_work')
            ->first();

        if ($pastWorkDoc) {
            return $this->verifyPastWorkFile($pastWorkDoc);
        }

        return [
            'status' => 'fail',
            'confidence_score' => 0,
            'error' => 'No past work evidence provided (neither link nor file)',
            'details' => [],
        ];
    }

    /**
     * Verify past work via URL link.
     */
    private function verifyPastWorkLink(string $url): array
    {
        try {
            // Check URL format
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return [
                    'status' => 'fail',
                    'confidence_score' => 0,
                    'error' => 'Invalid URL format',
                    'details' => ['url' => $url],
                ];
            }

            // Try to reach the URL
            try {
                $response = Http::timeout(10)
                    ->head($url);

                $statusCode = $response->status();

                // Accept 2xx status codes
                if ($statusCode >= 200 && $statusCode < 300) {
                    return [
                        'status' => 'pass',
                        'confidence_score' => 85,
                        'method' => 'url_link',
                        'details' => [
                            'url' => $url,
                            'status_code' => $statusCode,
                            'reachable' => true,
                        ],
                    ];
                }

                // 3xx redirects are acceptable
                if ($statusCode >= 300 && $statusCode < 400) {
                    return [
                        'status' => 'pass',
                        'confidence_score' => 80,
                        'method' => 'url_link',
                        'details' => [
                            'url' => $url,
                            'status_code' => $statusCode,
                            'reachable' => true,
                            'note' => 'URL redirects; may require manual verification',
                        ],
                    ];
                }

                // 4xx/5xx errors
                return [
                    'status' => 'flag',
                    'confidence_score' => 0,
                    'error' => "URL returned HTTP {$statusCode}",
                    'method' => 'url_link',
                    'details' => ['url' => $url, 'status_code' => $statusCode],
                ];
            } catch (\Exception $e) {
                // URL is unreachable (timeout, connection error, etc.)
                return [
                    'status' => 'flag',
                    'confidence_score' => 40,
                    'error' => 'URL is not reachable or timed out',
                    'method' => 'url_link',
                    'details' => [
                        'url' => $url,
                        'error_message' => $e->getMessage(),
                        'note' => 'May be temporarily unavailable; flagged for manual check',
                    ],
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'confidence_score' => 0,
                'error' => 'Error verifying URL: ' . $e->getMessage(),
                'details' => ['url' => $url],
            ];
        }
    }

    /**
     * Verify past work via uploaded file.
     */
    private function verifyPastWorkFile(ApplicationDocument $document): array
    {
        $disk = Storage::disk(config('filesystems.default'));
        $filePath = $document->file_path;

        if (!$disk->exists($filePath)) {
            return [
                'status' => 'fail',
                'confidence_score' => 0,
                'error' => 'Past work file not found on disk',
                'details' => ['file_path' => $filePath],
            ];
        }

        try {
            $fileSize = $disk->size($filePath);
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Check file is readable and not corrupted
            if ($fileSize < 100) {
                return [
                    'status' => 'fail',
                    'confidence_score' => 0,
                    'error' => 'File is too small or empty',
                    'details' => ['file_path' => $filePath, 'file_size' => $fileSize],
                ];
            }

            // Try to classify as past work
            $score = 70; // Base score for successfully uploaded file

            // For documents/images, try to extract text and check for media-related keywords
            if (in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'])) {
                $classification = $this->classificationService->classifyDocument($document, 'past_work');

                if ($classification['recommendation'] === 'pass') {
                    $score += 20;
                } elseif ($classification['recommendation'] === 'manual_review') {
                    $score += 10;
                }

                $details = [
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'extension' => $extension,
                    'classification' => $classification,
                    'extracted_text' => substr($classification['extracted_text_excerpt'] ?? '', 0, 300),
                ];
            } else {
                // For media files (mp4, mp3, etc.), just verify format
                $details = [
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'extension' => $extension,
                    'format' => $this->getMediaFormat($extension),
                ];
            }

            return [
                'status' => 'pass',
                'confidence_score' => min($score, 100),
                'method' => 'uploaded_file',
                'details' => $details,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'confidence_score' => 0,
                'error' => 'Error verifying past work file: ' . $e->getMessage(),
                'details' => ['file_path' => $filePath],
            ];
        }
    }

    /**
     * Get human-readable format name.
     */
    private function getMediaFormat(string $extension): string
    {
        $formats = [
            'pdf' => 'PDF Document',
            'jpg' => 'JPEG Image',
            'jpeg' => 'JPEG Image',
            'png' => 'PNG Image',
            'mp4' => 'MP4 Video',
            'mp3' => 'MP3 Audio',
            'doc' => 'Word Document',
            'docx' => 'Word Document',
            'xls' => 'Excel Spreadsheet',
            'xlsx' => 'Excel Spreadsheet',
        ];

        return $formats[strtolower($extension)] ?? ucfirst($extension) . ' File';
    }
}
