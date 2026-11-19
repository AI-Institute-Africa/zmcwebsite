<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Services\PortalIntakeAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FinalizeIntakeDecision implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(
        public Application $application,
        public array $options = [],
    ) {}

    public function handle(PortalIntakeAutomationService $automationService): void
    {
        $result = $automationService->processApplication(
            $this->application,
            $this->options
        );

        if (isset($result['error']) && $result['error']) {
            \Log::error("Intake automation failed for application {$this->application->id}", $result);
            $this->fail(new \Exception($result['decision']['error'] ?? 'Unknown error'));
            return;
        }

        \Log::info("Intake decision finalized for application {$this->application->id}", [
            'decision' => $result['decision']['decision'] ?? 'unknown',
            'score' => $result['scores']['overall_score'] ?? 0,
        ]);
    }
}
