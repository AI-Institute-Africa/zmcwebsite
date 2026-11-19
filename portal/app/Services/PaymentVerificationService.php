<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Http;

class PaymentVerificationService
{
    /**
     * Verify payment for an application.
     * Prefers gateway webhook records; falls back to uploaded proof.
     */
    public function verifyPayment(Application $application): array
    {
        // Check 1: Payment via gateway webhook (preferred)
        $gatewayPayment = $this->checkGatewayPayment($application);
        if ($gatewayPayment['status'] === 'pass') {
            return $gatewayPayment;
        }

        // Check 2: Payment proof document (fallback)
        if ($application->payment_proof_document) {
            $proofVerification = $this->verifyPaymentProofDocument($application);
            return $proofVerification;
        }

        // Check 3: Payment gateway reference provided
        if ($application->payment_gateway_reference) {
            $referenceCheck = $this->verifyGatewayReference($application->payment_gateway_reference);
            return $referenceCheck;
        }

        return [
            'status' => 'pending',
            'confidence_score' => 0,
            'error' => 'No payment verification found. Awaiting payment proof or gateway confirmation.',
            'details' => [],
        ];
    }

    /**
     * Check payment gateway webhook records.
     */
    private function checkGatewayPayment(Application $application): array
    {
        try {
            // Query for payment records from the application
            // Assuming there's a payments table with application_id, amount, status, gateway_ref
            $payment = $application->payments()
                ->where('status', 'completed')
                ->where('amount', '>=', $application->application_fee ?? 0)
                ->latest()
                ->first();

            if ($payment) {
                return [
                    'status' => 'pass',
                    'confidence_score' => 100,
                    'method' => 'gateway_webhook',
                    'details' => [
                        'payment_id' => $payment->id,
                        'gateway_ref' => $payment->gateway_reference,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'paid_at' => $payment->paid_at,
                    ],
                ];
            }

            return [
                'status' => 'pending',
                'confidence_score' => 0,
                'method' => 'gateway_webhook',
                'error' => 'No completed payment found in gateway records',
                'details' => [],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'confidence_score' => 0,
                'error' => 'Error checking gateway payment: ' . $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * Verify payment proof document (OCR and cross-check).
     */
    private function verifyPaymentProofDocument(Application $application): array
    {
        $paymentDoc = $application->documents()
            ->where('doc_type', 'payment_proof')
            ->first();

        if (!$paymentDoc) {
            return [
                'status' => 'pending',
                'confidence_score' => 0,
                'error' => 'Payment proof document not found',
                'details' => [],
            ];
        }

        // Extract text from payment proof
        $classificationService = new DocumentClassificationService();
        $classification = $classificationService->classifyDocument($paymentDoc, 'payment_proof');

        $extractedText = $classification['extracted_text_excerpt'] ?? '';

        // Try to extract payment details
        $paymentDetails = $this->extractPaymentDetails($extractedText);

        $score = 0;
        $warnings = [];

        if ($classification['recommendation'] === 'pass') {
            $score += 60;
        } elseif ($classification['recommendation'] === 'manual_review') {
            $score += 30;
            $warnings[] = 'Payment proof document classification uncertain';
        } else {
            return [
                'status' => 'fail',
                'confidence_score' => 0,
                'error' => 'Payment proof document failed classification',
                'details' => $classification,
            ];
        }

        // Check extracted details against expected amount
        if ($paymentDetails['amount']) {
            $expectedAmount = $application->application_fee ?? 0;
            if (abs($paymentDetails['amount'] - $expectedAmount) < 1) {
                $score += 40;
            } else {
                $score -= 20;
                $warnings[] = "Amount mismatch: Found {$paymentDetails['amount']}, Expected {$expectedAmount}";
            }
        }

        return [
            'status' => $score >= 70 ? 'pass' : 'flag',
            'confidence_score' => min($score, 100),
            'method' => 'uploaded_proof',
            'extracted_details' => $paymentDetails,
            'warnings' => $warnings,
            'details' => [
                'classification' => $classification,
                'extracted_text' => substr($extractedText, 0, 300),
            ],
        ];
    }

    /**
     * Verify payment using gateway reference.
     */
    private function verifyGatewayReference(string $reference): array
    {
        // This would typically call the payment gateway API to verify the reference
        // For now, return pending status

        return [
            'status' => 'flag',
            'confidence_score' => 50,
            'method' => 'gateway_reference',
            'error' => 'Payment reference provided; requires manual verification',
            'details' => ['gateway_reference' => $reference],
        ];
    }

    /**
     * Extract payment details from OCR text.
     */
    private function extractPaymentDetails(string $text): array
    {
        $details = [];

        // Extract amount (look for number patterns with 2 decimal places)
        if (preg_match('/(\d+[.,]\d{2})/', $text, $matches)) {
            $amount = str_replace(',', '.', $matches[1]);
            $details['amount'] = (float) $amount;
        }

        // Extract date
        if (preg_match('/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/', $text, $matches)) {
            $details['date'] = $matches[1];
        }

        // Extract transaction reference
        if (preg_match('/(?:ref|reference|transaction|trans)[\s:]*([A-Z0-9]{8,})/i', $text, $matches)) {
            $details['reference'] = $matches[1];
        }

        // Extract payment method
        if (preg_match('/(?:bank|card|paypal|stripe|gateway)[\s:]*([a-z0-9\s]+)/i', $text, $matches)) {
            $details['method'] = trim($matches[1]);
        }

        return $details;
    }
}
