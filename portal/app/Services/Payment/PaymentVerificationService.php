<?php

namespace App\Services\Payment;

use App\Models\Application;
use App\Models\PaymentVerificationRecord;
use App\Models\PaymentAuditTrail;
use Illuminate\Support\Facades\DB;

/**
 * PaymentVerificationService
 * Verifies payments from reliable sources (payment gateway, bank)
 * Does NOT accept payment screenshots as proof of payment
 * Detects duplicate payments, reversals, and payment fraud
 */
class PaymentVerificationService
{
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REVERSED = 'reversed';
    public const PAYMENT_STATUS_UNDER_REVIEW = 'under_review';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';
    public const PAYMENT_STATUS_WAIVED = 'waived';

    /**
     * Process payment from PayNow gateway
     * Called by PayNow webhook/callback
     */
    public function processPaynowPayment(Application $application, array $paymentData): array
    {
        return DB::transaction(function () use ($application, $paymentData) {
            // Verify gateway data authenticity
            if (!$this->verifyPaynowSignature($paymentData)) {
                return [
                    'success' => false,
                    'error' => 'INVALID_SIGNATURE',
                    'message' => 'Payment signature verification failed',
                ];
            }

            // Check for duplicate payments
            $existingPayment = PaymentVerificationRecord::where('application_id', $application->id)
                ->where('transaction_reference', $paymentData['transaction_reference'] ?? null)
                ->where('payment_status', 'paid')
                ->first();

            if ($existingPayment) {
                return [
                    'success' => false,
                    'error' => 'DUPLICATE_PAYMENT',
                    'message' => 'This application has already received payment',
                ];
            }

            // Extract payment data
            $amount = $paymentData['amount'] ?? 0;
            $requiredAmount = $this->getRequiredPaymentAmount($application);

            // Verify amount
            if ((float) $amount < (float) $requiredAmount) {
                return [
                    'success' => false,
                    'error' => 'INSUFFICIENT_AMOUNT',
                    'message' => 'Payment amount is less than required',
                    'required' => $requiredAmount,
                    'received' => $amount,
                ];
            }

            // Create payment record
            $paymentRecord = PaymentVerificationRecord::create([
                'application_id' => $application->id,
                'payment_source' => 'paynow_gateway',
                'transaction_reference' => $paymentData['transaction_reference'],
                'payment_method' => 'paynow',
                'amount_due' => $requiredAmount,
                'amount_paid' => $amount,
                'currency' => 'ZWL',
                'payment_status' => 'paid',
                'gateway_response_code' => $paymentData['response_code'] ?? null,
                'gateway_response_message' => $paymentData['response_message'] ?? null,
                'gateway_data' => $paymentData,
                'proof_of_payment_uploaded' => false,
                'payment_date' => now(),
                'confirmation_date' => now(),
                'is_duplicate_payment' => false,
            ]);

            // Create audit trail entry
            $this->logPaymentAction($application, 'payment_completed', [
                'transaction_reference' => $paymentData['transaction_reference'],
                'amount' => $amount,
                'gateway' => 'paynow',
            ]);

            // Update application status
            $application->update([
                'payment_status' => 'paid_confirmed',
                'payment_verified_at' => now(),
                'payment_verified_by' => 'system:paynow_gateway',
            ]);

            return [
                'success' => true,
                'payment_record_id' => $paymentRecord->id,
                'message' => 'Payment verified successfully',
                'transaction_reference' => $paymentData['transaction_reference'],
                'amount_paid' => $amount,
            ];
        });
    }

    /**
     * Process manual bank transfer verification by officer
     * Officer provides proof of transfer (bank slip, bank statement, etc.)
     */
    public function processManualBankTransferVerification(Application $application, array $verificationData, string $verifiedBy): array
    {
        return DB::transaction(function () use ($application, $verificationData, $verifiedBy) {
            // Check for duplicate verification
            $existingPayment = PaymentVerificationRecord::where('application_id', $application->id)
                ->whereIn('payment_status', ['paid', 'under_review'])
                ->first();

            if ($existingPayment) {
                return [
                    'success' => false,
                    'error' => 'DUPLICATE_VERIFICATION',
                    'message' => 'Payment has already been verified',
                ];
            }

            $amount = $verificationData['amount'] ?? 0;
            $requiredAmount = $this->getRequiredPaymentAmount($application);

            // Create payment record
            $paymentRecord = PaymentVerificationRecord::create([
                'application_id' => $application->id,
                'payment_source' => 'bank_transfer',
                'transaction_reference' => $verificationData['bank_reference'] ?? null,
                'payment_method' => 'bank_transfer',
                'amount_due' => $requiredAmount,
                'amount_paid' => $amount,
                'currency' => 'ZWL',
                'payment_status' => 'under_review', // Requires officer approval
                'verified_by' => $verifiedBy,
                'verified_at' => now(),
                'verification_notes' => $verificationData['notes'] ?? null,
                'is_duplicate_payment' => false,
                'amount_mismatch' => $amount < $requiredAmount,
            ]);

            // Log action
            $this->logPaymentAction($application, 'payment_submitted_for_verification', [
                'amount' => $amount,
                'submitted_by' => $verifiedBy,
            ]);

            return [
                'success' => true,
                'payment_record_id' => $paymentRecord->id,
                'message' => 'Payment submitted for verification',
                'status' => 'under_review',
            ];
        });
    }

    /**
     * Approve verified payment (officer action)
     */
    public function approvePaymentVerification(PaymentVerificationRecord $paymentRecord, string $approvedBy): array
    {
        return DB::transaction(function () use ($paymentRecord, $approvedBy) {
            // Update payment record
            $paymentRecord->update([
                'payment_status' => 'paid',
                'verified_by' => $approvedBy,
                'verified_at' => now(),
            ]);

            // Update application
            $paymentRecord->application->update([
                'payment_status' => 'paid_confirmed',
                'payment_verified_at' => now(),
                'payment_verified_by' => $approvedBy,
            ]);

            // Log action
            $this->logPaymentAction($paymentRecord->application, 'payment_verified', [
                'verified_by' => $approvedBy,
                'transaction_reference' => $paymentRecord->transaction_reference,
            ]);

            return [
                'success' => true,
                'message' => 'Payment verified and approved',
            ];
        });
    }

    /**
     * Reject payment verification
     */
    public function rejectPaymentVerification(PaymentVerificationRecord $paymentRecord, string $rejectedBy, string $reason): array
    {
        return DB::transaction(function () use ($paymentRecord, $rejectedBy, $reason) {
            $paymentRecord->update([
                'payment_status' => 'failed',
                'verified_by' => $rejectedBy,
                'verification_notes' => $reason,
            ]);

            $paymentRecord->application->update([
                'payment_status' => 'payment_failed',
            ]);

            $this->logPaymentAction($paymentRecord->application, 'payment_rejected', [
                'reason' => $reason,
                'rejected_by' => $rejectedBy,
            ]);

            return [
                'success' => true,
                'message' => 'Payment rejected',
            ];
        });
    }

    /**
     * Process payment reversal (e.g., refund, chargeback)
     */
    public function processPaymentReversal(Application $application, array $reversalData): array
    {
        return DB::transaction(function () use ($application, $reversalData) {
            // Find paid payment record
            $paymentRecord = PaymentVerificationRecord::where('application_id', $application->id)
                ->where('payment_status', 'paid')
                ->first();

            if (!$paymentRecord) {
                return [
                    'success' => false,
                    'error' => 'NO_PAID_PAYMENT',
                    'message' => 'No paid payment found to reverse',
                ];
            }

            // Update payment record
            $paymentRecord->update([
                'payment_status' => 'reversed',
            ]);

            // Update application
            $application->update([
                'payment_status' => 'payment_reversed',
            ]);

            // Log action
            $this->logPaymentAction($application, 'payment_reversed', [
                'reversal_reference' => $reversalData['reference'] ?? null,
                'reason' => $reversalData['reason'] ?? 'Unknown',
            ]);

            // Flag application - cannot approve without new payment
            $application->update([
                'flagged_for_payment_issue' => true,
            ]);

            return [
                'success' => true,
                'message' => 'Payment reversal processed',
                'action_required' => 'Applicant must resubmit payment',
            ];
        });
    }

    /**
     * Detect and flag duplicate payments
     */
    public function detectDuplicatePayments(Application $application): array
    {
        $duplicates = PaymentVerificationRecord::where('application_id', $application->id)
            ->where('payment_status', 'paid')
            ->orderBy('created_at')
            ->get();

        if ($duplicates->count() <= 1) {
            return ['has_duplicates' => false];
        }

        // Mark duplicates as duplicate payments
        $firstPayment = $duplicates->first();
        $duplicatePayments = $duplicates->slice(1);

        foreach ($duplicatePayments as $duplicate) {
            $duplicate->update(['is_duplicate_payment' => true]);
        }

        return [
            'has_duplicates' => true,
            'count' => $duplicates->count(),
            'primary_payment_id' => $firstPayment->id,
            'duplicate_payment_ids' => $duplicatePayments->pluck('id')->toArray(),
        ];
    }

    /**
     * Apply fee waiver (requires authorization)
     */
    public function applyFeeWaiver(Application $application, string $waiverType, string $reason, string $approvedBy): array
    {
        return DB::transaction(function () use ($application, $waiverType, $reason, $approvedBy) {
            $requiredAmount = $this->getRequiredPaymentAmount($application);

            // Create waived payment record
            PaymentVerificationRecord::create([
                'application_id' => $application->id,
                'payment_source' => 'fee_waiver',
                'amount_due' => $requiredAmount,
                'amount_paid' => $requiredAmount,
                'payment_status' => 'waived',
                'fee_waiver_approved_by' => $approvedBy,
                'fee_waiver_reason' => $reason,
                'verification_notes' => "Fee waived: $waiverType",
            ]);

            // Update application
            $application->update([
                'payment_status' => 'fee_waived',
                'fee_waiver_approved_by' => $approvedBy,
                'fee_waiver_reason' => $reason,
            ]);

            // Log action
            $this->logPaymentAction($application, 'fee_waived', [
                'waiver_type' => $waiverType,
                'reason' => $reason,
                'approved_by' => $approvedBy,
            ]);

            return [
                'success' => true,
                'message' => 'Fee waiver applied',
                'waived_amount' => $requiredAmount,
            ];
        });
    }

    /**
     * Get required payment amount for application
     */
    private function getRequiredPaymentAmount(Application $application): float
    {
        $fees = config('fees');

        // Determine fee based on application type and category
        $feeKey = $application->application_type;
        
        if ($application->accreditation_category) {
            $feeKey .= '.' . $application->accreditation_category;
        }

        return (float) ($fees[$feeKey] ?? $fees[$application->application_type] ?? 0);
    }

    /**
     * Helper: Verify PayNow gateway signature
     */
    private function verifyPaynowSignature(array $paymentData): bool
    {
        // Implementation depends on PayNow API specifications
        // Verify that signature matches expected HMAC
        
        $signature = $paymentData['signature'] ?? null;
        $secretKey = config('services.paynow.secret_key');

        if (!$signature || !$secretKey) {
            return false;
        }

        // Reconstruct data and verify
        $dataString = implode('', [
            $paymentData['transaction_reference'] ?? '',
            $paymentData['amount'] ?? '',
            $paymentData['response_code'] ?? '',
        ]);

        $expectedSignature = hash_hmac('sha256', $dataString, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Helper: Log payment action for audit trail
     */
    private function logPaymentAction(Application $application, string $eventType, array $details): void
    {
        PaymentAuditTrail::create([
            'application_id' => $application->id,
            'event_type' => $eventType,
            'transaction_reference' => $details['transaction_reference'] ?? null,
            'amount' => $details['amount'] ?? null,
            'performed_by' => auth()->id() ?? 'system',
            'performer_type' => auth()->check() ? 'officer' : 'system',
            'reason_or_note' => json_encode($details),
        ]);
    }

    /**
     * Check if application has paid
     */
    public function hasApplicationPaid(Application $application): bool
    {
        return PaymentVerificationRecord::where('application_id', $application->id)
            ->whereIn('payment_status', ['paid', 'waived'])
            ->exists();
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending(Application $application): bool
    {
        return !$this->hasApplicationPaid($application) &&
               !PaymentVerificationRecord::where('application_id', $application->id)->exists();
    }
}
