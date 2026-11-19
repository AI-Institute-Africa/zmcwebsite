<?php

namespace App\Services\Validation;

use App\Models\Payment;
use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentValidationService
{
    private FraudDetectionEngine $fraudDetectionEngine;
    
    /**
     * Payment status values
     */
    private const PAYMENT_STATUSES = [
        'pending',
        'paid', 
        'failed',
        'reversed',
        'under_review',
        'refunded',
        'waived'
    ];
    
    public function __construct(FraudDetectionEngine $fraudDetectionEngine)
    {
        $this->fraudDetectionEngine = $fraudDetectionEngine;
    }
    
    /**
     * Validate payment submission
     * 
     * @param array $paymentData
     * @param int $applicationId
     * @return array
     */
    public function validatePaymentSubmission(array $paymentData, int $applicationId): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'warnings' => [],
            'fraud_flags' => [],
            'require_manual_review' => false,
            'block_payment' => false
        ];
        
        $application = Application::find($applicationId);
        if (!$application) {
            $result['errors'][] = 'Application not found';
            return $result;
        }
        
        // Validate payment amount matches expected amount
        $amountValidation = $this->validatePaymentAmount($paymentData, $application);
        if (!$amountValidation['valid']) {
            $result['errors'][] = $amountValidation['error'];
            $result['block_payment'] = true;
        }
        
        // Validate payment method
        $methodValidation = $this->validatePaymentMethod($paymentData);
        if (!$methodValidation['valid']) {
            $result['errors'][] = $methodValidation['error'];
        }
        
        // Check for duplicate payments
        $duplicateCheck = $this->checkDuplicatePayment($paymentData, $applicationId);
        if ($duplicateCheck['is_duplicate']) {
            $result['errors'][] = 'Duplicate payment detected';
            $result['fraud_flags'][] = $duplicateCheck;
            $result['block_payment'] = true;
        }
        
        // Validate against application status
        $statusValidation = $this->validateApplicationStatus($application);
        if (!$statusValidation['valid']) {
            $result['errors'][] = $statusValidation['error'];
            $result['block_payment'] = true;
        }
        
        // Check for suspicious payment patterns
        $patternCheck = $this->checkPaymentPatterns($paymentData, $application);
        if ($patternCheck['suspicious']) {
            $result['warnings'][] = $patternCheck['description'];
            $result['fraud_flags'] = array_merge($result['fraud_flags'], $patternCheck['flags']);
            $result['require_manual_review'] = true;
        }
        
        // Validate proof of payment if provided
        if (!empty($paymentData['proof_document'])) {
            $proofValidation = $this->validatePaymentProof($paymentData['proof_document']);
            if (!$proofValidation['valid']) {
                $result['errors'][] = $proofValidation['error'];
            } else {
                $result['warnings'][] = 'Payment proof requires manual verification - do not rely solely on uploaded screenshot';
            }
        }
        
        // Set final validation result
        $result['valid'] = empty($result['errors']);
        
        return $result;
    }
    
    /**
     * Validate payment amount
     * 
     * @param array $paymentData
     * @param Application $application
     * @return array
     */
    private function validatePaymentAmount(array $paymentData, Application $application): array
    {
        $result = ['valid' => true, 'error' => ''];
        
        if (empty($paymentData['amount'])) {
            $result['valid'] = false;
            $result['error'] = 'Payment amount is required';
            return $result;
        }
        
        $expectedAmount = $this->calculateExpectedAmount($application);
        $paidAmount = (float) $paymentData['amount'];
        
        // Allow small rounding differences (within 0.01)
        $difference = abs($expectedAmount - $paidAmount);
        
        if ($difference > 0.01) {
            $result['valid'] = false;
            $result['error'] = "Payment amount ({$paidAmount}) does not match expected amount ({$expectedAmount})";
        }
        
        return $result;
    }
    
    /**
     * Calculate expected payment amount based on application type
     * 
     * @param Application $application
     * @return float
     */
    private function calculateExpectedAmount(Application $application): float
    {
        $requestType = $application->request_type ?? 'new';

        if ($application->application_type === 'accreditation') {
            $scope = strtolower((string) ($application->journalist_scope ?? 'local'));
            $feeKey = $scope === 'foreign' ? "foreign_{$requestType}" : $requestType;
            $fee = config("fees.accreditation.{$feeKey}", config("fees.accreditation.{$requestType}"));
        } elseif ($requestType === 'renewal') {
            $fee = config('fees.media_house.renewal_fee');
        } elseif ($requestType === 'replacement') {
            $fee = config('fees.media_house.replacement_fee');
        } elseif (in_array($application->status, [
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            Application::PRODUCTION_QUEUE,
        ], true)) {
            $category = $application->media_house_category_code ?? 'default';
            $fee = config(
                "fees.media_house.registration_fee.{$category}",
                config('fees.media_house.registration_fee.default')
            );
        } else {
            $fee = config('fees.media_house.application_fee');
        }

        if ($fee === null) {
            throw new \InvalidArgumentException('No expected fee is configured for this application.');
        }

        return (float) $fee;
    }
    
    /**
     * Validate payment method
     * 
     * @param array $paymentData
     * @return array
     */
    private function validatePaymentMethod(array $paymentData): array
    {
        $result = ['valid' => true, 'error' => ''];
        
        if (empty($paymentData['payment_method'])) {
            $result['valid'] = false;
            $result['error'] = 'Payment method is required';
            return $result;
        }
        
        $validMethods = ['paynow', 'bank_transfer', 'cash', 'card'];
        if (!in_array(strtolower($paymentData['payment_method']), $validMethods)) {
            $result['valid'] = false;
            $result['error'] = 'Invalid payment method';
        }
        
        return $result;
    }
    
    /**
     * Check for duplicate payments
     * 
     * @param array $paymentData
     * @param int $applicationId
     * @return array
     */
    private function checkDuplicatePayment(array $paymentData, int $applicationId): array
    {
        $result = ['is_duplicate' => false, 'flags' => []];
        
        // Check by transaction reference
        if (!empty($paymentData['transaction_reference'])) {
            $existingPayment = Payment::where('transaction_reference', $paymentData['transaction_reference'])
                ->where('application_id', '!=', $applicationId)
                ->first();
            
            if ($existingPayment) {
                $result['is_duplicate'] = true;
                $result['flags'][] = [
                    'type' => 'duplicate_transaction_reference',
                    'severity' => 'critical',
                    'description' => 'Transaction reference already used for another application',
                    'existing_application_id' => $existingPayment->application_id
                ];
            }
        }
        
        // Check for multiple recent payments for same application
        $recentPayments = Payment::where('application_id', $applicationId)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
        
        if ($recentPayments >= 2) {
            $result['is_duplicate'] = true;
            $result['flags'][] = [
                'type' => 'multiple_recent_payments',
                'severity' => 'high',
                'description' => "Multiple payment attempts ({$recentPayments}) in 24 hours",
                'count' => $recentPayments
            ];
        }
        
        return $result;
    }
    
    /**
     * Validate application status allows payment
     * 
     * @param Application $application
     * @return array
     */
    private function validateApplicationStatus(Application $application): array
    {
        $result = ['valid' => true, 'error' => ''];
        
        $payableStatuses = [
            'draft',
            'submitted', 
            'correction_requested',
            'officer_approved',
            'registrar_approved',
            'approved_awaiting_payment'
        ];
        
        if (!in_array($application->status, $payableStatuses)) {
            $result['valid'] = false;
            
            if ($application->status === 'paid_confirmed') {
                $result['error'] = 'Payment has already been confirmed for this application';
            } elseif ($application->status === 'rejected') {
                $result['error'] = 'Application has been rejected';
            } elseif ($application->status === 'withdrawn') {
                $result['error'] = 'Application has been withdrawn';
            } else {
                $result['error'] = "Application status ({$application->status}) does not allow payment";
            }
        }
        
        return $result;
    }
    
    /**
     * Check for suspicious payment patterns
     * 
     * @param array $paymentData
     * @param Application $application
     * @return array
     */
    private function checkPaymentPatterns(array $paymentData, Application $application): array
    {
        $result = ['suspicious' => false, 'description' => '', 'flags' => []];
        
        // Check for payment reversal after approval pattern
        $this->checkPaymentReversalPattern($application, $result);
        
        // Check for unusual payment timing
        $this->checkPaymentTiming($application, $result);
        
        // Check for multiple payment methods from same user
        $this->checkMultiplePaymentMethods($paymentData, $application, $result);
        
        return $result;
    }
    
    /**
     * Check for payment reversal after approval pattern
     * 
     * @param Application $application
     * @param array $result
     * @return void
     */
    private function checkPaymentReversalPattern(Application $application, array &$result): void
    {
        $reversedPayments = Payment::where('applicant_user_id', $application->applicant_user_id)
            ->where('status', 'reversed')
            ->where('created_at', '>=', now()->subDays(90))
            ->count();
        
        if ($reversedPayments > 0) {
            $result['suspicious'] = true;
            $result['description'] = 'Applicant has history of payment reversals';
            $result['flags'][] = [
                'type' => 'payment_reversal_history',
                'severity' => 'high',
                'description' => "Applicant has {$reversedPayments} payment reversal(s) in past 90 days",
                'count' => $reversedPayments
            ];
        }
    }
    
    /**
     * Check for unusual payment timing
     * 
     * @param Application $application
     * @param array $result
     * @return void
     */
    private function checkPaymentTiming(Application $application, array &$result): void
    {
        if ($application->status === 'submitted') {
            // Very fast payment after submission could indicate automation
            $timeSinceSubmission = now()->diffInMinutes($application->submitted_at ?? $application->created_at);
            
            if ($timeSinceSubmission < 2) {
                $result['suspicious'] = true;
                $result['description'] = 'Unusually fast payment after submission';
                $result['flags'][] = [
                    'type' => 'rapid_payment',
                    'severity' => 'medium',
                    'description' => 'Payment submitted less than 2 minutes after application',
                    'timing' => $timeSinceSubmission . ' minutes'
                ];
            }
        }
    }
    
    /**
     * Check for multiple payment methods from same user
     * 
     * @param array $paymentData
     * @param Application $application
     * @param array $result
     * @return void
     */
    private function checkMultiplePaymentMethods(array $paymentData, Application $application, array &$result): void
    {
        $recentMethods = Payment::where('applicant_user_id', $application->applicant_user_id)
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct()
            ->pluck('payment_method')
            ->toArray();
        
        if (count($recentMethods) > 3) {
            $result['suspicious'] = true;
            $result['description'] = 'Applicant using multiple payment methods';
            $result['flags'][] = [
                'type' => 'multiple_payment_methods',
                'severity' => 'medium',
                'description' => 'Applicant has used multiple payment methods recently',
                'methods' => $recentMethods
            ];
        }
    }
    
    /**
     * Validate payment proof document
     * 
     * @param array $proofDocument
     * @return array
     */
    private function validatePaymentProof(array $proofDocument): array
    {
        $result = ['valid' => true, 'error' => ''];
        
        // Check file type
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($proofDocument['extension'] ?? ''), $allowedTypes)) {
            $result['valid'] = false;
            $result['error'] = 'Invalid proof document type';
        }
        
        // Check file size
        $maxSize = 5 * 1024 * 1024; // 5MB
        if (($proofDocument['size'] ?? 0) > $maxSize) {
            $result['valid'] = false;
            $result['error'] = 'Proof document exceeds maximum size';
        }
        
        // Warning: screenshots are not reliable proof
        if (in_array(strtolower($proofDocument['extension'] ?? ''), ['jpg', 'jpeg', 'png'])) {
            $result['warning'] = 'Screenshots are not reliable proof of payment - payment should be verified against gateway or bank records';
        }
        
        return $result;
    }
    
    /**
     * Verify payment against payment gateway records
     * 
     * @param string $transactionReference
     * @return array
     */
    public function verifyPaymentAgainstGateway(string $transactionReference): array
    {
        $result = [
            'verified' => false,
            'gateway_response' => null,
            'amount_matches' => false,
            'status_matches' => false,
            'errors' => []
        ];
        
        try {
            // This would integrate with the actual payment gateway (PayNow, etc.)
            // For now, return a placeholder response
            
            // Simulate gateway verification
            $gatewayResponse = $this->simulateGatewayVerification($transactionReference);
            $result['gateway_response'] = $gatewayResponse;
            
            if ($gatewayResponse['success']) {
                $result['verified'] = true;
                $result['amount_matches'] = $gatewayResponse['amount_matches'] ?? true;
                $result['status_matches'] = $gatewayResponse['status_matches'] ?? true;
            } else {
                $result['errors'] = $gatewayResponse['errors'] ?? ['Gateway verification failed'];
            }
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Gateway verification error: ' . $e->getMessage();
            Log::error('Payment gateway verification failed', [
                'transaction_reference' => $transactionReference,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Simulate gateway verification (placeholder)
     * 
     * @param string $transactionReference
     * @return array
     */
    private function simulateGatewayVerification(string $transactionReference): array
    {
        return [
            'success' => false,
            'amount_matches' => false,
            'status_matches' => false,
            'transaction_status' => 'unverified',
            'transaction_date' => now()->toIso8601String(),
            'message' => 'Gateway verification is not connected.',
        ];
    }
    
    /**
     * Process payment status update
     * 
     * @param Payment $payment
     * @param string $newStatus
     * @param string|null $reason
     * @return array
     */
    public function processPaymentStatusUpdate(Payment $payment, string $newStatus, ?string $reason = null): array
    {
        $result = [
            'success' => false,
            'errors' => [],
            'warnings' => []
        ];
        
        // Validate status transition
        $validTransitions = $this->getValidStatusTransitions($payment->status);
        
        if (!in_array($newStatus, $validTransitions)) {
            $result['errors'][] = "Invalid status transition from {$payment->status} to {$newStatus}";
            return $result;
        }
        
        // Special validation for certain status changes
        match ($newStatus) {
            'reversed' => $this->validateReversal($payment, $result, $reason),
            'refunded' => $this->validateRefund($payment, $result, $reason),
            'waived' => $this->validateWaiver($payment, $result, $reason),
            default => null
        };
        
        if (!empty($result['errors'])) {
            return $result;
        }
        
        // Update payment status
        try {
            $payment->status = $newStatus;
            $payment->status_updated_at = now();
            $payment->status_update_reason = $reason;
            $payment->save();
            
            $result['success'] = true;
            
            // Log the status change
            Log::info('Payment status updated', [
                'payment_id' => $payment->id,
                'old_status' => $payment->getOriginal('status'),
                'new_status' => $newStatus,
                'reason' => $reason
            ]);
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Failed to update payment status: ' . $e->getMessage();
            Log::error('Payment status update failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get valid status transitions
     * 
     * @param string $currentStatus
     * @return array
     */
    private function getValidStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            'pending' => ['paid', 'failed', 'under_review'],
            'paid' => ['reversed', 'refunded'],
            'failed' => ['pending'],
            'under_review' => ['paid', 'failed', 'refunded', 'waived'],
            'reversed' => ['pending'],
            'refunded' => [],
            'waived' => []
        ];
        
        return $transitions[$currentStatus] ?? [];
    }
    
    /**
     * Validate payment reversal
     * 
     * @param Payment $payment
     * @param array $result
     * @param string|null $reason
     * @return void
     */
    private function validateReversal(Payment $payment, array &$result, ?string $reason): void
    {
        if (empty($reason)) {
            $result['errors'][] = 'Reversal reason is required';
        }
        
        // Check if application is already approved
        $application = $payment->application;
        if ($application && in_array($application->status, ['approved', 'paid_confirmed', 'issued'])) {
            $result['warnings'][] = 'Reversing payment for approved application may require additional review';
            
            // Trigger fraud flag for payment reversal after approval
            $fraudFlags = [
                [
                    'type' => 'payment_reversal_after_approval',
                    'severity' => 'high',
                    'description' => 'Payment reversal requested after application approval',
                    'application_id' => $application->id,
                    'payment_id' => $payment->id
                ]
            ];
            
            $result['fraud_flags'] = $fraudFlags;
        }
    }
    
    /**
     * Validate payment refund
     * 
     * @param Payment $payment
     * @param array $result
     * @param string|null $reason
     * @return void
     */
    private function validateRefund(Payment $payment, array &$result, ?string $reason): void
    {
        if (empty($reason)) {
            $result['errors'][] = 'Refund reason is required';
        }
        
        // Check refund timing
        $paymentDate = $payment->created_at;
        $daysSincePayment = $paymentDate->diffInDays(now());
        
        if ($daysSincePayment > 30) {
            $result['errors'][] = 'Refunds cannot be processed after 30 days';
        }
    }
    
    /**
     * Validate payment waiver
     * 
     * @param Payment $payment
     * @param array $result
     * @param string|null $reason
     * @return void
     */
    private function validateWaiver(Payment $payment, array &$result, ?string $reason): void
    {
        if (empty($reason)) {
            $result['errors'][] = 'Waiver reason is required';
        }
        
        // Waivers should require authorized approval
        $result['warnings'][] = 'Payment waiver requires authorised approval and audit trail';
    }
    
    /**
     * Reconcile payments with bank records
     * 
     * @return array
     */
    public function reconcilePaymentsWithBank(): array
    {
        $result = [
            'reconciled' => [],
            'unreconciled' => [],
            'discrepancies' => [],
            'total_processed' => 0
        ];
        
        try {
            // Get pending payments for reconciliation
            $pendingPayments = Payment::where('status', 'under_review')
                ->where('created_at', '>=', now()->subDays(7))
                ->get();
            
            $result['total_processed'] = $pendingPayments->count();
            
            foreach ($pendingPayments as $payment) {
                // Verify against bank records
                $verification = $this->verifyPaymentAgainstGateway($payment->transaction_reference);
                
                if ($verification['verified']) {
                    $payment->status = 'paid';
                    $payment->reconciled_at = now();
                    $payment->save();
                    $result['reconciled'][] = $payment->id;
                } else {
                    $result['unreconciled'][] = [
                        'payment_id' => $payment->id,
                        'transaction_reference' => $payment->transaction_reference,
                        'errors' => $verification['errors']
                    ];
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Payment reconciliation failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
}
