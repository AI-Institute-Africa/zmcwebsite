<?php

namespace App\Http\Controllers\Portal\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\DocumentClassificationService;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * AI-Powered Document Validation Controller
 * 
 * Handles real-time document validation during application upload
 */
class DocumentValidationController extends Controller
{
    private DocumentClassificationService $classificationService;

    public function __construct(DocumentClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
    }

    /**
     * Validate uploaded document using AI
     * 
     * POST /portal/validate-document
     */
    public function validateDocument(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|extensions:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'document_type' => 'required|string|in:certificate,id_scan,passport_biodata_page',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request: ' . $validator->errors()->first(),
                'is_valid' => false,
            ], 422);
        }

        try {
            $file = $request->file('document');
            $documentType = $request->input('document_type');

            // Store file temporarily
            $tempPath = $file->storeAs('temp/validation', FileType::safeHashName($file), 'local');
            $fullPath = storage_path('app/' . $tempPath);

            // Classify document with AI
            $result = $this->classificationService->classifyDocument($fullPath, $documentType);

            // Clean up temporary file
            Storage::disk('local')->delete($tempPath);

            // Log validation attempt (without file data)
            Log::info('Document validation request', [
                'user_id' => auth()->id(),
                'expected_type' => $documentType,
                'result' => $result['document_type'],
                'is_valid' => $result['is_valid'],
                'ip' => $request->ip(),
            ]);

            // Return validation result
            return response()->json([
                'success' => $result['success'],
                'is_valid' => $result['is_valid'],
                'document_type' => $result['document_type'],
                'confidence' => $result['confidence'],
                'message' => $result['message'],
                'reasoning' => $result['reasoning'], // Sanitized reasoning
            ]);

        } catch (\Exception $e) {
            Log::error('Document validation error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'document_type' => $request->input('document_type'),
            ]);

            return response()->json([
                'success' => false,
                'is_valid' => false,
                'message' => 'Validation failed. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Get sample reference images for document types
     * 
     * GET /portal/document-references
     */
    public function getDocumentReferences(Request $request)
    {
        $documentType = $request->query('type');

        $references = [
            'certificate' => [
                'title' => 'Educational Certificate',
                'description' => 'Academic certificates from recognized institutions',
                'characteristics' => [
                    'Institution name and logo at the top',
                    'Certificate or Diploma heading',
                    'Official seals and stamps',
                    'Formal certificate design and border',
                ],
                'example_path' => '/images/references/certificate-example.jpg',
            ],
            'id_scan' => [
                'title' => 'Zimbabwe National ID Card',
                'description' => 'Government-issued National Identity Card',
                'characteristics' => [
                    'Zimbabwe coat of arms',
                    'Photo on the left side',
                    'ID number in standard format',
                    '"Republic of Zimbabwe" text',
                ],
                'example_path' => '/images/references/national-id-example.jpg',
            ],
            'passport_biodata_page' => [
                'title' => 'Passport Biodata Page',
                'description' => 'Main page of passport with personal information',
                'characteristics' => [
                    'Passport photo (usually top left)',
                    'Machine-readable zone (MRZ) at bottom',
                    '"Passport" heading',
                    'Country emblems and seals',
                ],
                'example_path' => '/images/references/passport-example.jpg',
            ],
        ];

        if ($documentType && isset($references[$documentType])) {
            return response()->json([
                'success' => true,
                'reference' => $references[$documentType],
            ]);
        }

        return response()->json([
            'success' => true,
            'references' => $references,
        ]);
    }
}
