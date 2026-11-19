<?php

namespace App\Services\AI;

use App\Support\FileType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * AI-Powered Document Classification Service
 * 
 * Uses Claude Vision API to classify and validate uploaded documents
 * Implements strict guardrails to prevent data leakage and hallucination
 */
class DocumentClassificationService
{
    private ?string $apiKey;
    private ?string $apiUrl;
    private ?string $model;
    
    // Strict classification categories
    private const DOCUMENT_TYPES = [
        'CERTIFICATE' => 'educational_certificate',
        'ID_CARD' => 'national_id_card',
        'PASSPORT' => 'passport_biodata_page',
        'INVALID' => 'invalid_document'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->apiUrl = config('services.anthropic.endpoint');
        $this->model = config('services.anthropic.model');
    }

    /**
     * Classify uploaded document using AI vision
     * 
     * @param string $filePath Path to uploaded file
     * @param string $expectedType Expected document type for validation
     * @return array Classification result with confidence score
     */
    public function classifyDocument(string $filePath, string $expectedType): array
    {
        try {
            if (empty($this->apiKey) || empty($this->apiUrl) || empty($this->model)) {
                return $this->errorResponse('AI document classification is not configured');
            }

            // Validate file exists and is readable
            if (!file_exists($filePath) || !is_readable($filePath)) {
                return $this->errorResponse('File not accessible');
            }

            // Check file size (max 5MB for API)
            if (filesize($filePath) > 5 * 1024 * 1024) {
                return $this->errorResponse('File too large for AI analysis');
            }

            // Get file extension and mime type
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = FileType::mimeFromPath($filePath, $extension) ?: FileType::mimeFromExtension($extension);
            if (!$mimeType) {
                return $this->errorResponse('Unsupported file type');
            }

            // Convert to base64
            $imageData = base64_encode(file_get_contents($filePath));
            
            // Determine media type for API
            $mediaType = $this->getMediaType($mimeType, $extension);
            if (!$mediaType) {
                return $this->errorResponse('Unsupported file type');
            }

            // Build system prompt with strict guardrails
            $systemPrompt = $this->buildSystemPrompt($expectedType);

            // Call Claude Vision API
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => config('services.anthropic.version'),
                'content-type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => 300, // Limit response size to prevent excessive processing
                'temperature' => 0, // Zero temperature for deterministic classification
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mediaType,
                                    'data' => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => 'Classify this document. Respond ONLY with the JSON format specified in your instructions. Do not include any other text.',
                            ],
                        ],
                    ],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('AI classification API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->errorResponse('AI service temporarily unavailable');
            }

            // Parse AI response
            $result = $this->parseAIResponse($response->json(), $expectedType);

            // Log classification (without image data)
            Log::info('Document classified', [
                'expected_type' => $expectedType,
                'detected_type' => $result['document_type'],
                'confidence' => $result['confidence'],
                'is_valid' => $result['is_valid'],
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Document classification exception', [
                'error' => $e->getMessage(),
                'expected_type' => $expectedType,
            ]);
            return $this->errorResponse('Classification failed: ' . $e->getMessage());
        }
    }

    /**
     * Build system prompt with strict guardrails
     */
    private function buildSystemPrompt(string $expectedType): string
    {
        $typeDescription = $this->getTypeDescription($expectedType);

        return <<<PROMPT
You are a document classification system for the Zimbabwe Media Commission portal. Your ONLY task is to classify documents into specific categories.

**STRICT RULES:**
1. DO NOT extract, read, or report ANY personal information (names, IDs, dates, addresses, etc.)
2. DO NOT generate, imagine, or assume document content
3. ONLY classify the document type based on visual characteristics
4. If uncertain, respond with INVALID_DOCUMENT
5. Your response MUST be valid JSON in the exact format specified below

**CLASSIFICATION CATEGORIES:**

1. **CERTIFICATE** (Educational/Academic Certificate):
   - Has institution name/logo at top
   - Contains "Certificate" or "Diploma" heading
   - Has official seals, stamps, or emblems
   - Academic or professional qualification layout
   - Formal certificate border/design

2. **ID_CARD** (Zimbabwe National ID):
   - Government-issued identification card
   - Zimbabwe coat of arms or government logo
   - Photo on left side typically
   - Card format with multiple fields
   - "Republic of Zimbabwe" text
   - ID number format visible

3. **PASSPORT** (Passport Biodata Page):
   - Passport-style photo (top left usually)
   - Machine-readable zone (MRZ) at bottom with <<<< characters
   - "Passport" or travel document heading
   - Multiple data fields in passport format
   - ICAO standard layout
   - Country emblems/seals

4. **INVALID_DOCUMENT**:
   - Doesn't match any category above
   - Too blurry to identify
   - Not a document (photo, screenshot, etc.)
   - Partial/cropped document
   - Wrong document type

**EXPECTED DOCUMENT:** $typeDescription

**RESPONSE FORMAT (JSON ONLY):**
{
  "document_type": "CERTIFICATE|ID_CARD|PASSPORT|INVALID_DOCUMENT",
  "confidence": 0.0-1.0,
  "reasoning": "Brief visual description (NO personal data)",
  "matches_expected": true|false
}

**REMEMBER:** Extract NO personal information. Classify based on document TYPE only.
PROMPT;
    }

    /**
     * Parse AI response and validate
     */
    private function parseAIResponse(array $response, string $expectedType): array
    {
        try {
            // Extract text content
            $content = $response['content'][0]['text'] ?? '';
            
            // Remove markdown code blocks if present
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $content = trim($content);

            // Parse JSON
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->errorResponse('Invalid AI response format');
            }

            // Validate required fields
            if (!isset($data['document_type']) || !isset($data['confidence'])) {
                return $this->errorResponse('Missing required classification fields');
            }

            // Validate document type is in allowed list
            $docType = strtoupper($data['document_type']);
            if (!in_array($docType, array_keys(self::DOCUMENT_TYPES))) {
                $docType = 'INVALID_DOCUMENT';
            }

            // Check if matches expected type
            $matchesExpected = $this->matchesExpectedType($docType, $expectedType);

            // Build result
            return [
                'success' => true,
                'document_type' => $docType,
                'confidence' => (float) $data['confidence'],
                'reasoning' => $this->sanitizeReasoning($data['reasoning'] ?? ''),
                'matches_expected' => $matchesExpected,
                'is_valid' => $matchesExpected && $docType !== 'INVALID_DOCUMENT',
                'message' => $this->getValidationMessage($docType, $matchesExpected, $expectedType),
            ];

        } catch (\Exception $e) {
            Log::error('AI response parsing error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to parse AI response');
        }
    }

    /**
     * Check if detected type matches expected type
     */
    private function matchesExpectedType(string $detectedType, string $expectedType): bool
    {
        $mapping = [
            'CERTIFICATE' => ['certificate', 'educational_certificate'],
            'ID_CARD' => ['id_scan', 'national_id', 'national_id_card'],
            'PASSPORT' => ['passport_biodata_page', 'passport'],
        ];

        foreach ($mapping as $aiType => $expectedTypes) {
            if ($detectedType === $aiType && in_array($expectedType, $expectedTypes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get validation message
     */
    private function getValidationMessage(string $docType, bool $matches, string $expectedType): string
    {
        if ($docType === 'INVALID_DOCUMENT') {
            return 'Document type could not be identified. Please upload a clear, complete document.';
        }

        if (!$matches) {
            $typeName = $this->getTypeDescription($expectedType);
            return "This appears to be a {$this->getTypeName($docType)}, but we expected a {$typeName}. Please upload the correct document type.";
        }

        return 'Document validated successfully. This is a valid ' . $this->getTypeName($docType) . '.';
    }

    /**
     * Get human-readable type name
     */
    private function getTypeName(string $type): string
    {
        return match($type) {
            'CERTIFICATE' => 'educational certificate',
            'ID_CARD' => 'national ID card',
            'PASSPORT' => 'passport biodata page',
            default => 'document',
        };
    }

    /**
     * Get type description for expected document
     */
    private function getTypeDescription(string $expectedType): string
    {
        return match($expectedType) {
            'certificate', 'educational_certificate' => 'Educational Certificate',
            'id_scan', 'national_id' => 'Zimbabwe National ID Card',
            'passport_biodata_page', 'passport' => 'Passport Biodata Page',
            default => 'Document',
        };
    }

    /**
     * Sanitize reasoning to prevent data leakage
     */
    private function sanitizeReasoning(string $reasoning): string
    {
        // Remove any potential personal data patterns
        $reasoning = preg_replace('/\b[A-Z]{2}\d{6,10}\b/', '[ID_REDACTED]', $reasoning); // ID numbers
        $reasoning = preg_replace('/\b[A-Z]{2}\d{6,8}[A-Z]\d\b/', '[PASSPORT_REDACTED]', $reasoning); // Passport numbers
        $reasoning = preg_replace('/\b\d{2}[-\/]\d{2}[-\/]\d{2,4}\b/', '[DATE_REDACTED]', $reasoning); // Dates
        
        // Truncate if too long
        if (strlen($reasoning) > 200) {
            $reasoning = substr($reasoning, 0, 200) . '...';
        }

        return $reasoning;
    }

    /**
     * Get media type for API
     */
    private function getMediaType(string $mimeType, string $extension): ?string
    {
        // Handle PDFs - convert first page to image if needed
        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return 'application/pdf';
        }

        // Handle images
        $imageTypes = [
            'image/jpeg' => 'image/jpeg',
            'image/jpg' => 'image/jpeg',
            'image/png' => 'image/png',
            'image/webp' => 'image/webp',
            'image/gif' => 'image/gif',
        ];

        return $imageTypes[$mimeType] ?? null;
    }

    /**
     * Build error response
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'document_type' => 'INVALID_DOCUMENT',
            'confidence' => 0.0,
            'reasoning' => '',
            'matches_expected' => false,
            'is_valid' => false,
            'message' => $message,
        ];
    }
}
