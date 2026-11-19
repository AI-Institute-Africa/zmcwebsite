<?php

namespace App\Jobs\IntakeAutomation;

use App\Models\Application;
use App\Services\PaymentVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyPaymentProof implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(
        public Application $application,
    ) {}

    public function handle(PaymentVerificationService $paymentService): void
    {
        $result = $paymentService->verifyPayment($this->application);

        if ($result['status'] === 'fail') {
            \Log::error("Payment verification failed for application {$this->application->id}", $result);
            return;
        }

        if ($result['status'] === 'pending') {
            \Log::warning("Payment verification pending for application {$this->application->id}", $result);
            return;
        }

        \Log::info("Payment verification completed for application {$this->application->id}", [
            'status' => $result['status'],
            'method' => $result['method'] ?? 'unknown',
        ]);
    }
}
