<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Services\PastWorkVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyPastWorkEvidence implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(
        public Application $application,
        private ?PastWorkVerificationService $pastWorkService = null,
    ) {}

    public function handle(PastWorkVerificationService $pastWorkService): void
    {
        $result = $pastWorkService->verifyPastWork($this->application);

        if ($result['status'] === 'fail') {
            \Log::error("Past work verification failed for application {$this->application->id}", $result);
            return;
        }

        if ($result['status'] === 'flag') {
            \Log::warning("Past work verification flagged for application {$this->application->id}", $result);
            return;
        }

        \Log::info("Past work verification completed for application {$this->application->id}", [
            'status' => $result['status'],
            'method' => $result['method'] ?? 'unknown',
            'confidence' => $result['confidence_score'] ?? 0,
        ]);
    }
}
