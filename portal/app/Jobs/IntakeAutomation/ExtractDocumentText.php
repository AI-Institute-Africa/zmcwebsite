<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Models\DocumentClassification;
use App\Services\DocumentClassificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractDocumentText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(
        public Application $application,
        public $documentId,
    ) {}

    public function handle(DocumentClassificationService $classificationService): void
    {
        $document = $this->application->documents()
            ->where('id', $this->documentId)
            ->firstOrFail();

        // Perform classification which includes text extraction
        $result = $classificationService->classifyDocument(
            $document,
            $document->doc_type
        );

        // Store classification result
        DocumentClassification::updateOrCreate(
            [
                'application_id' => $this->application->id,
                'document_id' => $document->id,
            ],
            [
                'expected_document_type' => $result['expected_document_type'],
                'detected_document_type' => $result['detected_document_type'],
                'confidence_score' => $result['confidence_score'],
                'extracted_text_excerpt' => $result['extracted_text_excerpt'],
                'matched_keywords' => $result['matched_keywords'],
                'missing_keywords' => $result['missing_keywords'],
                'warnings' => $result['warnings'],
                'blocking_errors' => $result['blocking_errors'],
                'recommendation' => $result['recommendation'],
            ]
        );

        \Log::info("Document {$this->documentId} text extracted and classified");
    }
}
