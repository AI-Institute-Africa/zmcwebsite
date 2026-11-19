<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Services\ApplicationCategoryAssignmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignApplicationCategory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(
        public Application $application,
    ) {}

    public function handle(ApplicationCategoryAssignmentService $categoryService): void
    {
        $result = $categoryService->assignCategory($this->application);

        \Log::info("Category assigned to application {$this->application->id}", [
            'category' => $result['assigned_category'],
            'confidence' => $result['confidence_score'],
            'needs_confirmation' => $result['needs_manual_confirmation'],
        ]);
    }
}
