<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Application;
use App\Services\PayNowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Paynow\Core\StatusResponse;

class PaymentController extends Controller
{
    protected PayNowService $paynow;

    public function __construct(PayNowService $paynow)
    {
        $this->paynow = $paynow;
    }

    private function makeReference(Application $application): string
    {
        return 'PAY-' . $application->id . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    private function responseData($response): array
    {
        return is_object($response) && method_exists($response, 'data') ? $response->data() : [];
    }

    private function isSuccessfulPaynowStatus(?StatusResponse $status): bool
    {
        if (!$status) {
            return false;
        }

        return in_array(strtolower((string) $status->status()), ['paid', 'awaiting delivery', 'delivered'], true);
    }

    private function findPaymentForPaynowUpdate(?string $reference, ?string $pollUrl): ?Payment
    {
        if ($reference) {
            $payment = Payment::where('reference', $reference)->first();

            if ($payment) {
                return $payment;
            }
        }

        return $pollUrl ? Payment::where('poll_url', $pollUrl)->first() : null;
    }

    /**
     * Initiate PayNow payment
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function initiate(Request $request)
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|exists:applications,id',
                'email' => 'required|email',
                'phone_number' => 'nullable|regex:/^[0-9]{10,13}$/',
                'payment_method' => 'nullable|in:paynow,ecocash,onemoney',
                'currency' => 'nullable|in:USD,ZWG',
            ]);

            // Get application and calculate amount
            $application = Application::findOrFail($validated['application_id']);
            $amount = $application->calculated_amount ?? $application->amount;
            $method = $validated['payment_method'] ?? 'paynow';
            $currency = $validated['currency'] ?? $this->paynow->getDefaultCurrency();

            if (!$amount || $amount <= 0) {
                return redirect()->back()->with('error', 'Invalid payment amount');
            }

            if (!$this->paynow->isEnabled()) {
                return redirect()->back()->with('error', 'Paynow payments are currently disabled');
            }

            // Create payment reference
            $reference = $this->makeReference($application);
            $returnUrl = route('payment.return', ['reference' => $reference]);

            // Create payment record
            $payment = Payment::create([
                'application_id' => $application->id,
                'payer_user_id' => auth()->id(),
                'method' => $method,
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'gateway' => 'paynow',
                'payment_method' => $method,
                'source' => 'gateway',
            ]);

            // Create PayNow payment
            $paynow = $this->paynow->getPaynow($currency, $returnUrl, route('payment.callback'));
            $paynowPayment = $paynow->createPayment($reference, $validated['email']);
            $paynowPayment->add('Application Fee', $amount);

            // Send payment
            if ($method === 'paynow') {
                // Web redirect payment
                $response = $paynow->send($paynowPayment);
            } else {
                // Mobile payment (ecocash/onemoney)
                if (!$validated['phone_number']) {
                    return redirect()->back()->with('error', 'Phone number required for mobile payments');
                }

                $response = $paynow->sendMobile(
                    $paynowPayment,
                    $validated['phone_number'],
                    $method
                );

                $payment->update([
                    'phone_number' => $validated['phone_number'],
                    'instructions' => $response->instructions(),
                ]);
            }

            // Handle response
            if ($response->success()) {
                $payment->update([
                    'status' => 'processing',
                    'poll_url' => $response->pollUrl(),
                    'gateway_response' => $this->responseData($response),
                    'raw' => $this->responseData($response),
                    'last_checked_at' => now(),
                ]);

                Log::info("Payment initiated for application {$application->id}", [
                    'reference' => $reference,
                    'amount' => $amount,
                    'currency' => $currency,
                ]);

                // If web payment, redirect to PayNow
                if ($method === 'paynow') {
                    return redirect($response->redirectUrl());
                }

                // If mobile payment, show instructions
                return view('payment.mobile-instructions', [
                    'payment' => $payment,
                    'instructions' => $response->instructions(),
                    'pollUrl' => $response->pollUrl(),
                ]);
            } else {
                $payment->update(['status' => 'failed']);
                Log::error("Payment initiation failed for application {$application->id}", [
                    'reference' => $reference,
                    'error' => $response->errors(),
                ]);

                return redirect()->back()->with('error', 'Payment initiation failed: ' . $response->errors());
            }
        } catch (\Exception $e) {
            Log::error('Payment initiation error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'An error occurred while initiating payment');
        }
    }

    /**
     * PayNow callback (result URL)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request)
    {
        try {
            $reference = $request->get('reference');
            $pollUrl = $request->get('pollurl') ?: $request->get('pollUrl');

            if (!$reference && !$pollUrl) {
                Log::warning('PayNow callback received without reference or poll URL');
                return response('OK', 200);
            }

            $payment = $this->findPaymentForPaynowUpdate($reference, $pollUrl);

            if (!$payment) {
                Log::warning('PayNow callback payment not found', [
                    'reference' => $reference,
                    'pollurl' => $pollUrl,
                ]);
                return response('OK', 200);
            }

            $callbackStatus = $this->paynow->processStatusUpdate($payment->currency ?? $this->paynow->getDefaultCurrency(), null, route('payment.callback'));
            $pollUrl = $pollUrl ?: $payment->poll_url;

            if (!$pollUrl) {
                Log::warning('PayNow callback payment missing poll URL', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                ]);
                return response('OK', 200);
            }

            // Check transaction status
            $status = $this->paynow->getPaynow($payment->currency ?? $this->paynow->getDefaultCurrency(), route('payment.return', ['reference' => $payment->reference]), route('payment.callback'))
                ->pollTransaction($pollUrl);

            if ($this->isSuccessfulPaynowStatus($callbackStatus) && $this->isSuccessfulPaynowStatus($status)) {
                $payment->update([
                    'status' => 'paid',
                    'paynow_reference' => $status->paynowReference() ?: $payment->paynow_reference,
                    'confirmed_at' => now(),
                    'gateway_response' => $this->responseData($status),
                    'raw' => $this->responseData($status),
                    'last_checked_at' => now(),
                ]);

                Log::info("Payment confirmed for {$payment->reference}", [
                    'application_id' => $payment->application_id,
                ]);

                // Trigger any post-payment logic here
                $this->handlePaymentConfirmed($payment);
            } else {
                $payment->update([
                    'status' => in_array(strtolower((string) $status->status()), ['cancelled', 'refunded', 'disputed', 'failed'], true) ? 'failed' : 'processing',
                    'gateway_response' => $this->responseData($status),
                    'raw' => $this->responseData($status),
                    'last_checked_at' => now(),
                ]);

                Log::warning("Payment not confirmed for {$payment->reference}", [
                    'status' => $status->status(),
                ]);
            }

            // Return 200 OK to acknowledge receipt
            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('PayNow callback error', [
                'message' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Return page after payment (user redirect)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    public function return(Request $request)
    {
        try {
            $pollUrl = $request->get('pollurl') ?: $request->get('pollUrl');
            $reference = $request->get('reference');

            if (!$pollUrl && !$reference) {
                return view('payment.failed', [
                    'message' => 'Invalid payment request',
                ]);
            }

            $payment = $this->findPaymentForPaynowUpdate($reference, $pollUrl);

            if (!$payment) {
                return view('payment.failed', [
                    'message' => 'Payment record could not be found',
                ]);
            }

            $pollUrl = $pollUrl ?: $payment->poll_url;

            if (!$pollUrl) {
                return view('payment.failed', [
                    'payment' => $payment,
                    'message' => 'Payment status could not be checked',
                ]);
            }

            // Check transaction status
            $status = $this->paynow->pollTransaction($pollUrl, $payment->currency ?? null);

            if ($this->isSuccessfulPaynowStatus($status)) {
                if ($payment->status !== 'paid') {
                    $payment->update([
                        'status' => 'paid',
                        'paynow_reference' => $status->paynowReference() ?: $payment->paynow_reference,
                        'confirmed_at' => now(),
                        'gateway_response' => $this->responseData($status),
                        'raw' => $this->responseData($status),
                        'last_checked_at' => now(),
                    ]);

                    $this->handlePaymentConfirmed($payment);
                }

                return view('payment.success', [
                    'payment' => $payment,
                    'message' => 'Payment received successfully',
                ]);
            } else {
                $payment->update([
                    'status' => in_array(strtolower((string) $status->status()), ['cancelled', 'refunded', 'disputed', 'failed'], true) ? 'failed' : 'processing',
                    'gateway_response' => $this->responseData($status),
                    'raw' => $this->responseData($status),
                    'last_checked_at' => now(),
                ]);

                return view('payment.failed', [
                    'payment' => $payment,
                    'message' => 'Payment was not completed',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Payment return error', [
                'message' => $e->getMessage(),
            ]);

            return view('payment.failed', [
                'message' => 'An error occurred while processing your payment',
            ]);
        }
    }

    /**
     * Check payment status (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request)
    {
        try {
            $request->validate([
                'reference' => 'required|exists:payments,reference',
            ]);

            $payment = Payment::where('reference', $request->reference)->firstOrFail();

            if ($payment->poll_url) {
                $status = $this->paynow->pollTransaction($payment->poll_url, $payment->currency ?? null);

                if ($this->isSuccessfulPaynowStatus($status) && $payment->status !== 'paid') {
                    $payment->update([
                        'status' => 'paid',
                        'paynow_reference' => $status->paynowReference() ?: $payment->paynow_reference,
                        'confirmed_at' => now(),
                        'gateway_response' => $this->responseData($status),
                        'raw' => $this->responseData($status),
                        'last_checked_at' => now(),
                    ]);
                    $this->handlePaymentConfirmed($payment);
                } elseif (!$this->isSuccessfulPaynowStatus($status)) {
                    $payment->update([
                        'status' => in_array(strtolower((string) $status->status()), ['cancelled', 'refunded', 'disputed', 'failed'], true) ? 'failed' : 'processing',
                        'gateway_response' => $this->responseData($status),
                        'raw' => $this->responseData($status),
                        'last_checked_at' => now(),
                    ]);
                }
            }

            return response()->json([
                'status' => $payment->status,
                'amount' => $payment->amount,
                'reference' => $payment->reference,
                'confirmed_at' => $payment->confirmed_at?->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Payment status check error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Unable to check payment status'], 500);
        }
    }

    /**
     * Handle post-payment logic
     *
     * @param Payment $payment
     * @return void
     */
    protected function handlePaymentConfirmed(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            // Update payment status
            if ($payment->application) {
                $application = $payment->application;

                // 1. Update payment status
                $application->update([
                    'payment_status' => 'paid',
                    'payment_currency' => $payment->currency ?? $this->paynow->getDefaultCurrency(),
                    'payment_paid_at' => now(),
                    'paynow_reference' => $payment->reference,
                    'paynow_poll_url' => $payment->poll_url,
                    'paynow_confirmed_at' => now(),
                ]);

                // 2. Generate receipt number if not already set
                if (!$payment->receipt_number) {
                    $receiptNumber = $this->generateReceiptNumber($payment->payment_method ?? 'paynow');
                    $payment->update(['receipt_number' => $receiptNumber]);
                }

                // 3. Move application to PRODUCTION_QUEUE status
                $application->update([
                    'status' => Application::PRODUCTION_QUEUE,
                    'last_action_at' => now(),
                ]);

                Log::info("PayNow Payment confirmed and application moved to production", [
                    'application_id' => $application->id,
                    'payment_id' => $payment->id,
                    'reference' => $payment->reference,
                    'receipt_number' => $payment->receipt_number,
                    'amount' => $payment->amount,
                ]);

                // 4. Optionally: Send confirmation email to applicant
                $this->sendPaymentConfirmationEmail($application, $payment);
            }
        });
    }

    /**
     * Generate receipt number (similar to AccountsPaymentsController)
     *
     * @param string $method
     * @return string
     */
    private function generateReceiptNumber(string $method = 'paynow'): string
    {
        $prefix = match($method) {
            'ecocash' => 'ECO',
            'onemoney' => 'OMN',
            'paynow' => 'PN',
            default => 'REC'
        };

        $year = now()->format('Y');

        $sequence = DB::table('receipt_sequences')
            ->where('prefix', $prefix)
            ->where('year', $year)
            ->value('last_number') ?? 0;

        $sequence = intval($sequence) + 1;

        try {
            DB::table('receipt_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->update(['last_number' => $sequence]);
        } catch (\Exception $e) {
            DB::table('receipt_sequences')->insert([
                'prefix' => $prefix,
                'year' => $year,
                'last_number' => $sequence,
            ]);
        }

        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT) . '-' . $year;
    }

    /**
     * Send payment confirmation email to applicant
     *
     * @param Application $application
     * @param Payment $payment
     * @return void
     */
    private function sendPaymentConfirmationEmail(Application $application, Payment $payment): void
    {
        try {
            $applicant = $application->applicant;
            if (!$applicant || !$applicant->email) {
                Log::warning("Cannot send payment confirmation email: applicant email missing", [
                    'application_id' => $application->id,
                ]);
                return;
            }

            // Send email (implement as needed)
            // Mail::send(...);

            Log::info("Payment confirmation email sent", [
                'application_id' => $application->id,
                'email' => $applicant->email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send payment confirmation email", [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
