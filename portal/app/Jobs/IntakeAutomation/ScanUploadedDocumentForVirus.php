<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Services\DocumentSecurityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanUploadedDocumentForVirus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(
        public Application $application,
        public $documentId,
    ) {}

    public function handle(DocumentSecurityService $securityService): void
    {
        $document = $this->application->documents()
            ->where('id', $this->documentId)
            ->firstOrFail();

        $result = $securityService->scanDocument($document, $this->application);

        // Log result or dispatch next job
        if ($result['status'] === 'fail') {
            \Log::warning("Virus scan failed for document {$this->documentId}", $result);
            $this->fail(new \Exception("Document failed virus scan: " . ($result['error_message'] ?? 'Unknown error')));
        }

        \Log::info("Document {$this->documentId} passed virus scan");
    }
}
