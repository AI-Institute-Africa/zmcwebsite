<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Services\PassportPhotoQualityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyPassportPhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(
        public Application $application,
    ) {}

    public function handle(PassportPhotoQualityService $photoService): void
    {
        $photoDoc = $this->application->documents()
            ->where('doc_type', 'passport_photo')
            ->first();

        if (!$photoDoc) {
            \Log::warning("Passport photo not found for application {$this->application->id}");
            return;
        }

        $result = $photoService->checkPhotoQuality($photoDoc);

        if ($result['status'] === 'fail') {
            \Log::warning("Passport photo quality check failed for application {$this->application->id}", $result);
            // Don't fail job; flag for review instead
            return;
        }

        \Log::info("Passport photo quality check passed for application {$this->application->id}");
    }
}
