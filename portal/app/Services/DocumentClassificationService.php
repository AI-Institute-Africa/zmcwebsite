<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class DocumentClassificationService
{
    private array $documentTypeKeywords = [
        'local_national_id' => [
            'required' => ['national', 'id', 'identification', 'number', 'issued', 'expiry', 'date', 'country'],
            'optional' => ['card', 'document', 'valid', 'citizenship', 'resident', 'birth'],
            'patterns' => ['/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', '/[A-Z]{1,3}\d{6,10}/'],
        ],
        'passport' => [
            'required' => ['passport', 'number', 'issued', 'expiry', 'date', 'nationality', 'surname', 'given'],
            'optional' => ['valid', 'government', 'country', 'born', 'sex', 'mrz'],
            'patterns' => ['/[A-Z]{1,3}\d{6,9}/', '/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/'],
        ],
        'passport_photo' => [
            'required' => ['photo', 'picture', 'portrait', 'image'],
            'optional' => ['passport', 'size', 'size', 'size', 'colored'],
            'patterns' => [],
        ],
        'graduation_certificate' => [
            'required' => ['certificate', 'diploma', 'degree', 'university', 'college', 'graduated', 'awarded', 'qualification'],
            'optional' => ['institute', 'school', 'academy', 'has', 'successfully', 'completed', 'conferred'],
            'patterns' => ['/\d{4}/', '/(?:january|february|march|april|may|june|july|august|september|october|november|december)/i'],
        ],
        'employment_letter' => [
            'required' => ['employment', 'employed', 'confirms', 'position', 'designation', 'company', 'organization', 'date'],
            'optional' => ['currently', 'works', 'employed', 'letter', 'confirm', 'signature', 'authorized', 'hr', 'office'],
            'patterns' => ['/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', '/(?:[a-z0-9._%-]+@[a-z0-9.-]+\.[a-z]{2,})/i'],
        ],
        'clearance_letter' => [
            'required' => ['clearance', 'police', 'criminal', 'record', 'certificate', 'authority', 'issued', 'date'],
            'optional' => ['no', 'government', 'ministry', 'office', 'valid', 'authenticated', 'seal'],
            'patterns' => ['/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', '/(?:police|authority|ministry|government)/i'],
        ],
        'payment_proof' => [
            'required' => ['payment', 'receipt', 'transaction', 'amount', 'date', 'reference', 'invoice'],
            'optional' => ['paid', 'confirmed', 'bank', 'gateway', 'channel', 'method', 'successful', 'status'],
            'patterns' => ['/\d+[.,]\d{2}/', '/(?:zwd|usd|gbp|eur)/', '/[A-Z0-9]{8,}/'],
        ],
        'past_work' => [
            'required' => ['article', 'journalist', 'publication', 'media', 'report', 'news', 'broadcast', 'byline', 'writer'],
            'optional' => ['author', 'published', 'date', 'interview', 'story', 'video', 'photography', 'producer', 'editor'],
            'patterns' => ['/\d{4}/', '/(?:january|february|march|april|may|june|july|august|september|october|november|december)/i'],
        ],
    ];

    /**
     * Classify a document and extract key information.
     *
     * @param  string  $expectedDocumentType  The type we expect (e.g., 'passport')
     * @return array Classification result with detected_type, confidence_score, keywords, recommendation
     */
    public function classifyDocument(ApplicationDocument $document, string $expectedDocumentType): array
    {
        // Try to find the file on available disks
        $disk = null;
        $filePath = $document->file_path;
        foreach (['public', 'local'] as $diskName) {
            $candidateDisk = Storage::disk($diskName);
            if ($candidateDisk->exists($filePath)) {
                $disk = $candidateDisk;
                break;
            }
        }
        if ($disk === null) {
            return $this->failResult(
                $expectedDocumentType,
                'File not found',
                'File does not exist on any configured disk'
            );
        }

        // Extract text based on file type
        $extractedText = $this->extractTextFromFile($disk, $filePath);

        if (empty($extractedText) || strlen($extractedText) < 50) {
            return [
                'expected_document_type' => $expectedDocumentType,
                'detected_document_type' => 'unknown',
                'confidence_score' => 0,
                'extracted_text_excerpt' => null,
                'matched_keywords' => [],
                'missing_keywords' => $this->documentTypeKeywords[$expectedDocumentType]['required'] ?? [],
                'warnings' => ['OCR extraction failed or returned insufficient text'],
                'blocking_errors' => ['Unable to extract text for classification'],
                'recommendation' => 'manual_review',
                'details' => ['file_path' => $filePath, 'extraction_method' => 'failed'],
            ];
        }

        // Score the document against all known types
        $scores = $this->scoreDocumentAgainstAllTypes($extractedText);

        // Determine detected type (highest score)
        $detectedType = array_key_first($scores);
        $detectionScore = $scores[$detectedType] ?? 0;

        // Get keyword analysis for expected type
        $keywordAnalysis = $this->analyzeKeywords($extractedText, $expectedDocumentType);

        // Determine confidence and recommendation
        $confidence = $detectionScore;
        $recommendation = $this->makeRecommendation(
            $expectedDocumentType,
            $detectedType,
            $confidence,
            $keywordAnalysis,
        );

        return [
            'expected_document_type' => $expectedDocumentType,
            'detected_document_type' => $detectedType,
            'confidence_score' => (int) $confidence,
            'extracted_text_excerpt' => substr($extractedText, 0, 500),
            'matched_keywords' => $keywordAnalysis['matched'] ?? [],
            'missing_keywords' => $keywordAnalysis['missing'] ?? [],
            'warnings' => $keywordAnalysis['warnings'] ?? [],
            'blocking_errors' => [],
            'recommendation' => $recommendation,
            'details' => [
                'file_path' => $filePath,
                'all_scores' => $scores,
                'extraction_method' => 'ocr',
                'text_length' => strlen($extractedText),
            ],
        ];
    }

    /**
     * Extract text from a file (PDF or image).
     */
    public function extractTextFromFile(object $disk, string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        try {
            if ($extension === 'pdf') {
                return $this->extractTextFromPdf($disk, $filePath);
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'])) {
                return $this->extractTextFromImage($disk, $filePath);
            }

            return '';
        } catch (\Exception $e) {
            \Log::warning("Error extracting text from {$filePath}: ".$e->getMessage());

            return '';
        }
    }

    /**
     * Extract text from PDF using smalot/pdfparser.
     */
    private function extractTextFromPdf(object $disk, string $filePath): string
    {
        try {
            $content = $disk->get($filePath);
            $parser = new Parser;
            $document = $parser->parseContent($content);
            $pages = $document->getPages();

            $text = '';
            foreach ($pages as $page) {
                $text .= $page->getText();
            }

            return trim($text);
        } catch (\Exception $e) {
            \Log::warning("PDF parsing error for {$filePath}: ".$e->getMessage());

            return '';
        }
    }

    /**
     * Extract text from image using Tesseract OCR.
     * Falls back to empty string if Tesseract is not available.
     */
    private function extractTextFromImage(object $disk, string $filePath): string
    {
        try {
            $tempPath = tempnam(sys_get_temp_dir(), 'ocr');
            file_put_contents($tempPath, $disk->get($filePath));

            if (! is_file($tempPath) || ! is_readable($tempPath)) {
                \Log::warning('OCR temp file not readable', ['temp_path' => $tempPath]);

                return '';
            }

            $command = 'tesseract '.escapeshellarg($tempPath).' stdout 2>&1';
            $text = function_exists('shell_exec') ? @shell_exec($command) : '';

            $errorOutput = $text;
            unlink($tempPath);

            $text = trim($text ?? '');

            if ($text === '' && is_string($errorOutput)) {
                \Log::warning('OCR returned empty text', [
                    'file_path' => $filePath,
                    'tesseract_output' => $errorOutput,
                ]);
            }

            return $text;
        } catch (\Exception $e) {
            \Log::warning("OCR extraction error for {$filePath}: ".$e->getMessage());

            return '';
        }
    }

    /**
     * Score extracted text against all known document types.
     * Returns array of [type => score] sorted by score descending.
     */
    private function scoreDocumentAgainstAllTypes(string $text): array
    {
        $textLower = strtolower($text);
        $scores = [];

        foreach ($this->documentTypeKeywords as $docType => $keywords) {
            $score = $this->scoreDocumentType($textLower, $keywords);
            $scores[$docType] = $score;
        }

        arsort($scores); // Sort by score descending

        return $scores;
    }

    /**
     * Score a document against a single type based on keyword presence.
     */
    private function scoreDocumentType(string $textLower, array $keywords): int
    {
        $score = 0;
        $maxScore = 0;

        // Required keywords: each is worth up to 20 points
        foreach ($keywords['required'] ?? [] as $keyword) {
            $maxScore += 20;
            if (strpos($textLower, strtolower($keyword)) !== false) {
                $score += 20;
            }
        }

        // Optional keywords: each is worth up to 5 points (up to 5 keywords count)
        $optionalCount = 0;
        foreach ($keywords['optional'] ?? [] as $keyword) {
            if ($optionalCount < 5) {
                $maxScore += 5;
                if (strpos($textLower, strtolower($keyword)) !== false) {
                    $score += 5;
                }
                $optionalCount++;
            }
        }

        // Patterns: each match is worth 10 points
        foreach ($keywords['patterns'] ?? [] as $pattern) {
            $maxScore += 10;
            if (preg_match($pattern, $textLower)) {
                $score += 10;
            }
        }

        // Return confidence as percentage (0-100)
        return $maxScore > 0 ? (int) (($score / $maxScore) * 100) : 0;
    }

    /**
     * Analyze keywords for a specific document type.
     */
    private function analyzeKeywords(string $text, string $docType): array
    {
        $textLower = strtolower($text);
        $keywords = $this->documentTypeKeywords[$docType] ?? [];

        $matched = [];
        $missing = [];
        $warnings = [];

        // Check required keywords
        foreach ($keywords['required'] ?? [] as $keyword) {
            if (strpos($textLower, strtolower($keyword)) !== false) {
                $matched[] = $keyword;
            } else {
                $missing[] = $keyword;
            }
        }

        // Check for warnings (optional keywords mostly missing)
        $optionalMatched = 0;
        foreach ($keywords['optional'] ?? [] as $keyword) {
            if (strpos($textLower, strtolower($keyword)) !== false) {
                $optionalMatched++;
            }
        }

        if ($optionalMatched < (count($keywords['optional'] ?? []) / 2)) {
            $warnings[] = 'Document may not contain expected content; few optional keywords found';
        }

        return [
            'matched' => $matched,
            'missing' => $missing,
            'warnings' => $warnings,
        ];
    }

    /**
     * Make a classification recommendation based on matching and scores.
     */
    private function makeRecommendation(
        string $expectedType,
        string $detectedType,
        int $confidence,
        array $keywordAnalysis,
    ): string {
        // If detected type matches expected type with high confidence
        if ($detectedType === $expectedType && $confidence >= 80) {
            return 'pass';
        }

        // If detected type matches but confidence is medium
        if ($detectedType === $expectedType && $confidence >= 60) {
            return 'manual_review';
        }

        // If detected type doesn't match expected type
        if ($detectedType !== $expectedType) {
            return 'manual_review';
        }

        // Low confidence on expected type
        if ($confidence < 60) {
            return 'manual_review';
        }

        // Missing critical keywords
        if (count($keywordAnalysis['missing'] ?? []) >= 3) {
            return 'manual_review';
        }

        return 'manual_review';
    }

    /**
     * Helper to return a failed classification result.
     */
    private function failResult(
        string $expectedType,
        string $blockerMessage,
        string $details = '',
    ): array {
        return [
            'expected_document_type' => $expectedType,
            'detected_document_type' => 'unknown',
            'confidence_score' => 0,
            'extracted_text_excerpt' => null,
            'matched_keywords' => [],
            'missing_keywords' => $this->documentTypeKeywords[$expectedType]['required'] ?? [],
            'warnings' => [],
            'blocking_errors' => [$blockerMessage.($details ? ": {$details}" : '')],
            'recommendation' => 'manual_review',
            'details' => [],
        ];
    }
}
