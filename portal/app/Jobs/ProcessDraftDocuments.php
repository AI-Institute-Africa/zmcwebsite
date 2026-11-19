<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\UploadedFile;

class ProcessDraftDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    protected int $applicationId;
    protected array $filesData; // [field => [tmp_path, original_name, mime, size, doc_type]]
    protected string $status;

    public function __construct(int $applicationId, array $filesData, string $status = 'draft')
    {
        $this->applicationId = $applicationId;
        $this->filesData = $filesData;
        $this->status = $status;
    }

    public function handle(): void
    {
        $application = Application::find($this->applicationId);
        if (!$application) {
            Log::warning('ProcessDraftDocuments: Application not found', ['id' => $this->applicationId]);
            return;
        }

        foreach ($this->filesData as $field => $files) {
            foreach ($files as $fileData) {
                try {
                    $this->processFile($application, $field, $fileData);
                } catch (\Throwable $e) {
                    Log::error('ProcessDraftDocuments: File processing failed', [
                        'application_id' => $this->applicationId,
                        'field' => $field,
                        'file' => $fileData['original_name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    protected function processFile(Application $application, string $field, array $fileData): void
    {
        $tmpPath = $fileData['tmp_path'];
        $originalName = $fileData['original_name'];
        $mime = $fileData['mime'];
        $size = $fileData['size'];
        $docType = $fileData['doc_type'];

        if (!Storage::disk('public')->exists($tmpPath)) {
            Log::warning('ProcessDraftDocuments: Temp file missing', ['path' => $tmpPath]);
            return;
        }

        // Move from temp to permanent location
        $permanentPath = 'documents/' . $application->id . '/' . basename($tmpPath);
        Storage::disk('public')->move($tmpPath, $permanentPath);

        $sha256 = null;
        try { $sha256 = hash_file('sha256', Storage::disk('public')->path($permanentPath)); } catch (\Throwable $e) {}

        $docData = [
            'file_path'      => $permanentPath,
            'original_name'  => $originalName,
            'status'         => $this->status,
            'owner_id'       => $application->applicant_user_id,
            'mime'           => $mime,
            'size'           => $size,
            'sha256'         => $sha256,
            'is_current'     => true,
            'version'        => 1,
        ];

        ApplicationDocument::updateOrCreate(
            [
                'application_id' => $application->id,
                'doc_type'       => $docType,
                'is_current'     => true,
            ],
            $docData
        );
    }
}
