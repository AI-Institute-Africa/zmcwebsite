<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Services\IdentityVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyApplicantIdentity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(
        public Application $application,
    ) {}

    public function handle(IdentityVerificationService $identityService): void
    {
        $result = $identityService->verifyIdentity($this->application);

        if ($result['status'] === 'fail') {
            \Log::warning("Identity verification failed for application {$this->application->id}", $result);
            $this->fail(new \Exception("Identity verification failed: " . ($result['error'] ?? 'Unknown error')));
        }

        \Log::info("Identity verification completed for application {$this->application->id}", [
            'status' => $result['status'],
            'confidence' => $result['confidence_score'],
        ]);
    }
}
