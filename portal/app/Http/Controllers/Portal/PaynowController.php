<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\AppealPayment;
use App\Models\Payment;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\ActivityLogger;
use App\Services\PayNowService as PaynowService;
use Paynow\Core\StatusResponse;

class PaynowController extends Controller
{
    private PaynowService $paynowService;

    public function __construct(PaynowService $paynowService)
    {
        $this->paynowService = $paynowService;
    }

    private function currentRbzExchangeRate(): ?float
    {
        if (!Schema::hasTable('system_configs')) return null;
        $settings = SystemConfig::getValue('rbz_exchange_rate_zwg', []);
        $rate = is_array($settings) ? ($settings['rate'] ?? null) : null;
        return is_numeric($rate) && (float) $rate > 0 ? (float) $rate : null;
    }

    public function rbzRate()
    {
        if (!Schema::hasTable('system_configs')) {
            return response()->json(['available' => false, 'message' => 'The exchange-rate settings are unavailable. Contact Accounts.'], 503);
        }
        $settings = SystemConfig::getValue('rbz_exchange_rate_zwg', []);
        $rate = $this->currentRbzExchangeRate();
        if (!$rate) {
            return response()->json(['available' => false, 'message' => 'Accounts has not configured the current ZWG exchange rate.'], 503);
        }

        return response()->json([
            'available' => true,
            'rate' => $rate,
            'effective_date' => !empty($settings['effective_date']) ? \Illuminate\Support\Carbon::parse($settings['effective_date'])->format('d/m/Y') : null,
        ]);
    }

    private function getPaynow(string $currency = 'USD')
    {
        return $this->paynowService->getPaynow($currency);
    }

    private function resolvePaynowAuthEmail(?string $preferredEmail = null): string
    {
        // PayNow rejects the request when the integration is in test mode and the
        // "authemail" does not match the merchant's registered email address.
        // Prefer the configured merchant email so payments succeed in both test
        // and live modes.
        $email = $this->paynowService->getMerchantEmail()
            ?: $preferredEmail
            ?: Auth::user()?->email
            ?: config('mail.from.address');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid payer email address is required for PayNow payments.');
        }

        return $email;
    }

    private function makePaynowReference(string $prefix, int|string $id): string
    {
        return strtoupper($prefix) . '-' . $id . '-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    private function getApplicationPollUrl(Application $application): ?string
    {
        if (!empty($application->paynow_poll_url)) {
            return $application->paynow_poll_url;
        }

        // Backward compatibility for older records that stored pollurl in paynow_reference.
        if (!empty($application->paynow_reference) && filter_var($application->paynow_reference, FILTER_VALIDATE_URL)) {
            return $application->paynow_reference;
        }

        return null;
    }

    private function findApplicationForPaynowUpdate(?string $reference, ?string $pollUrl): ?Application
    {
        if ($reference) {
            $application = Application::where('paynow_reference', $reference)
                ->orWhere('reference', $reference)
                ->first();

            if ($application) {
                return $application;
            }
        }

        if ($pollUrl) {
            return Application::where('paynow_poll_url', $pollUrl)
                ->orWhere('paynow_reference', $pollUrl)
                ->first();
        }

        return null;
    }

    private function findAppealForPaynowUpdate(?string $reference, ?string $pollUrl): ?Complaint
    {
        $query = Complaint::where('type', 'appeal');

        if ($reference) {
            $appeal = (clone $query)->where('payment_ref', $reference)->first();

            if ($appeal) {
                return $appeal;
            }
        }

        if ($pollUrl) {
            return (clone $query)->where('paynow_poll_url', $pollUrl)->first();
        }

        return null;
    }

    private function findAppealPayment(Complaint $appeal): ?AppealPayment
    {
        $payment = $appeal->appealPayments()->latest('id')->first();

        // Preserve Paynow records created before appeal_payments was introduced.
        if (!$payment && $appeal->payment_ref && $appeal->paynow_poll_url) {
            $payment = AppealPayment::firstOrCreate(
                ['reference' => $appeal->payment_ref],
                [
                    'complaint_id' => $appeal->id,
                    'amount' => $appeal->payment_amount ?: config('fees.foia.appeal_fee'),
                    'currency' => $appeal->payment_currency ?: 'USD',
                    'status' => 'processing',
                    'poll_url' => $appeal->paynow_poll_url,
                ]
            );
        }

        return $payment;
    }

    private function appealPaymentStatusMatches(AppealPayment $payment, ?StatusResponse $status): bool
    {
        if (!$this->isSuccessfulPaynowStatus($status)) {
            return false;
        }

        $receivedAmount = (float) $status->amount();
        $receivedReference = trim((string) $status->reference());

        return $receivedReference !== ''
            && hash_equals($payment->reference, $receivedReference)
            && $receivedAmount > 0
            && abs($receivedAmount - (float) $payment->amount) <= 0.01;
    }

    private function describeApplicationPayment(Application $application): string
    {
        if ($application->application_type === 'accreditation') {
            $requestType = $application->request_type === 'renewal' ? 'Renewal' : 'New';
            return "Media Practitioner Accreditation Fee ({$requestType})";
        }

        return match ($application->request_type ?? 'new') {
            'new' => 'Media House Application Fee',
            'renewal' => 'Media House Renewal Fee',
            'replacement' => 'Media House Replacement Fee',
            default => 'Media House Registration Fee',
        };
    }

    private function isSuccessfulPaynowStatus(?StatusResponse $status): bool
    {
        if (!$status) {
            return false;
        }

        return in_array(strtolower((string) $status->status()), ['paid', 'awaiting delivery', 'delivered'], true);
    }

    private function paynowResponseData($response): array
    {
        return is_object($response) && method_exists($response, 'data') ? $response->data() : [];
    }

    private function recordApplicationPaymentInitiated(
        Application $application,
        string $merchantReference,
        string $pollUrl,
        float $amount,
        string $currency,
        string $method,
        array $gatewayResponse = []
    ): void {
        $application->update([
            'paynow_reference' => $merchantReference,
            'paynow_poll_url' => $pollUrl,
            'payment_status' => $method === 'paynow' ? 'pending' : 'awaiting_confirmation',
            'payment_submission_method' => 'paynow_reference',
            'payment_submitted_at' => now(),
            'payment_currency' => $currency,
        ]);

        Payment::updateOrCreate(
            ['reference' => $merchantReference],
            [
                'application_id' => $application->id,
                'payer_user_id' => $application->applicant_user_id,
                'method' => $method,
                'payment_method' => $method,
                'gateway' => 'paynow',
                'source' => 'gateway',
                'status' => 'processing',
                'poll_url' => $pollUrl,
                'amount' => $amount,
                'currency' => $currency,
                'raw' => $gatewayResponse,
                'gateway_response' => $gatewayResponse,
                'last_checked_at' => now(),
                'service_type' => $application->application_type === 'accreditation' ? 'accreditation' : 'registration',
            ]
        );
    }

    private function syncApplicationPaymentFromStatus(Application $application, ?StatusResponse $status, ?string $pollUrl = null): void
    {
        $pollUrl = $pollUrl ?: $this->getApplicationPollUrl($application);
        $data = $status ? $this->paynowResponseData($status) : [];
        $paynowReference = $status?->paynowReference() ?: ($data['paynowreference'] ?? null);
        $amount = $status && (float) $status->amount() > 0 ? (float) $status->amount() : $this->calculateFee($application);
        $currency = $application->payment_currency ?? 'USD';
        $merchantReference = $application->paynow_reference ?: ($status?->reference() ?: $application->reference);

        $payment = Payment::where('reference', $merchantReference)
            ->when($pollUrl, fn ($query) => $query->orWhere('poll_url', $pollUrl))
            ->first();

        $values = [
            'application_id' => $application->id,
            'payer_user_id' => $application->applicant_user_id,
            'method' => $payment?->method ?? 'paynow',
            'payment_method' => $payment?->payment_method ?? 'paynow',
            'gateway' => 'paynow',
            'source' => 'gateway',
            'reference' => $merchantReference,
            'paynow_reference' => $paynowReference ?: ($payment?->paynow_reference ?? null),
            'poll_url' => $pollUrl,
            'status' => 'paid',
            'amount' => $amount,
            'currency' => $currency,
            'raw' => $data,
            'gateway_response' => $data,
            'last_checked_at' => now(),
            'confirmed_at' => now(),
            'reconciled' => false,
            'service_type' => $application->application_type === 'accreditation' ? 'accreditation' : 'registration',
        ];

        if ($payment) {
            if (!$payment->receipt_number) {
                $values['receipt_number'] = $this->generateReceiptNumber('paynow', $application->id);
            }

            $payment->update($values);
        } else {
            $values['receipt_number'] = $this->generateReceiptNumber('paynow', $application->id);
            Payment::create($values);
        }

        $application->update(array_merge([
            'payment_status' => 'paid',
            'payment_paid_at' => now(),
            'paynow_confirmed_at' => now(),
            'last_action_at' => now(),
        ], $this->resolvePaymentConfirmedStatus($application)));
    }

    private function syncPendingApplicationPaymentStatus(Application $application, StatusResponse $status, ?string $pollUrl = null): void
    {
        $pollUrl = $pollUrl ?: $this->getApplicationPollUrl($application);
        $gatewayStatus = strtolower((string) $status->status());
        $paymentStatus = in_array($gatewayStatus, ['cancelled', 'refunded', 'disputed', 'failed'], true)
            ? 'failed'
            : 'processing';

        $query = Payment::query();

        if ($application->paynow_reference) {
            $query->where('reference', $application->paynow_reference);

            if ($pollUrl) {
                $query->orWhere('poll_url', $pollUrl);
            }
        } elseif ($pollUrl) {
            $query->where('poll_url', $pollUrl);
        } else {
            return;
        }

        $query->update([
            'status' => $paymentStatus,
            'gateway_response' => $this->paynowResponseData($status),
            'raw' => $this->paynowResponseData($status),
            'last_checked_at' => now(),
        ]);
    }

    private function confirmAppealPayment(Complaint $appeal, AppealPayment $payment, StatusResponse $status): bool
    {
        if (!$this->appealPaymentStatusMatches($payment, $status)) {
            Log::warning('Paynow appeal amount or merchant reference did not match', [
                'appeal_id' => $appeal->id,
                'payment_reference' => $payment->reference,
                'received_reference' => $status->reference(),
                'expected_amount' => (float) $payment->amount,
                'received_amount' => (float) $status->amount(),
            ]);
            return false;
        }

        DB::transaction(function () use ($appeal, $payment, $status) {
            $lockedAppeal = Complaint::whereKey($appeal->id)->lockForUpdate()->firstOrFail();
            $lockedPayment = AppealPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status === 'paid' || $lockedAppeal->payment_status === 'paid') {
                return;
            }

            $gatewayData = $this->paynowResponseData($status);
            $lockedPayment->update([
                'status' => 'paid',
                'provider_reference' => $status->paynowReference() ?: null,
                'gateway_response' => $gatewayData,
                'last_checked_at' => now(),
                'confirmed_at' => now(),
            ]);

            $lockedAppeal->update([
                'payment_status' => 'paid',
                'payment_amount' => $lockedPayment->amount,
                'payment_ref' => $lockedPayment->reference,
                'payment_currency' => $lockedPayment->currency,
                'payment_approved_at' => now(),
            ]);

            \App\Support\AuditTrail::log('appeal_payment_verified', $lockedAppeal, [
                'reference' => $lockedPayment->reference,
                'amount' => (float) $lockedPayment->amount,
                'currency' => $lockedPayment->currency,
                'is_system_generated' => true,
            ]);
        });

        return true;
    }

    private function resolvePaymentConfirmedStatus(Application $application): array
    {
        $registrarAlreadyApproved = !empty($application->registrar_reviewed_at) || !empty($application->registrar_reviewed_by);

        if ($application->status === Application::REGISTRAR_APPROVED
            || ($registrarAlreadyApproved && in_array($application->status, [
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
            ]))
        ) {
            ActivityLogger::log('paynow_auto_production', $application, $application->status, Application::PRODUCTION_QUEUE, [
                'auto_reason' => 'PayNow confirmed after Registrar approval — auto-forwarded to Production',
            ]);
            return [
                'status' => Application::PRODUCTION_QUEUE,
                'current_stage' => 'production',
            ];
        }

        if (in_array($application->status, [Application::APPROVED_AWAITING_PAYMENT, Application::AWAITING_ACCOUNTS_VERIFICATION])) {
            return ['status' => Application::PAID_CONFIRMED];
        }

        return [];
    }

    public function initiate(Request $request, $applicationId = null)
    {
        $user = Auth::user();

        // Handle missing or invalid application ID
        if (!$applicationId) {
            return response()->json(['success' => false, 'message' => 'Application ID is required.'], 400);
        }

        // Resolve the default first so ZWG also requires the displayed RBZ rate
        // when ZWG is configured as the default currency.
        $request->merge([
            'currency' => $request->input('currency', $this->paynowService->getDefaultCurrency()),
        ]);

        // Validate currency selection
        $validated = $request->validate([
            'currency' => 'required|string|in:USD,ZWG',
        ]);

        $currency = $validated['currency'];
        $rbzRate = $currency === 'ZWG' ? $this->currentRbzExchangeRate() : null;
        if ($currency === 'ZWG' && !$rbzRate) {
            return response()->json(['success' => false, 'message' => 'ZWG payments are temporarily unavailable because Accounts has not configured the current RBZ exchange rate.'], 503);
        }

        // Fetch application with error handling
        try {
            $application = Application::findOrFail($applicationId);
        } catch (\Exception $e) {
            Log::warning('Application not found for payment initiation', [
                'application_id' => $applicationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        if ($application->applicant_user_id !== $user->id) {
            Log::warning('Unauthorized payment attempt', [
                'application_id' => $application->id,
                'user_id' => $user->id,
                'applicant_id' => $application->applicant_user_id,
            ]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!in_array($application->status, [
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::SUBMITTED,
            Application::ACCOUNTS_REVIEW, 
            Application::PAYMENT_REJECTED,
            Application::APPROVED_AWAITING_PAYMENT,
            Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
            Application::REGISTRAR_APPROVED_PENDING_REGISTRATION_FEE_PAYMENT
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This application is not awaiting payment. Status: ' . $application->status
            ], 400);
        }

        if (!$this->paynowService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Paynow payments are currently disabled.',
            ], 503);
        }

        // Registration fees are a separate payment stage and are configured
        // per media-house category. Never fall back to the application fee.
        $registrationFeeStage = $application->application_type === 'registration'
            && $application->status === Application::REGISTRAR_APPROVED_PENDING_REGISTRATION_FEE_PAYMENT;
        $fee = $registrationFeeStage
            ? $this->calculateRegistrationFee($application)
            : $this->calculateFee($application);
        if ($currency === 'ZWG') {
            $fee *= $rbzRate;
        }
        $paymentReference = $this->makePaynowReference('APP', $application->id);
        $returnUrl = route('paynow.return', ['reference' => $paymentReference]);

        try {
            $paynow = $this->paynowService->getPaynow($currency, $returnUrl, route('paynow.callback'));
            
            $payment = $paynow->createPayment($paymentReference, $this->resolvePaynowAuthEmail($user->email));
            $payment->add($this->describeApplicationPayment($application), $fee);

            // Send payment to PayNow
            $response = $paynow->send($payment);

            if ($response->success()) {
                $this->recordApplicationPaymentInitiated(
                    $application,
                    $paymentReference,
                    $response->pollUrl(),
                    $fee,
                    $currency,
                    'paynow',
                    array_merge($this->paynowResponseData($response), $rbzRate ? ['rbz_rate' => $rbzRate] : [])
                );

                Log::info('Paynow payment initiated', [
                    'application_id' => $application->id,
                    'currency' => $currency,
                    'amount' => $fee,
                ]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $response->redirectUrl(),
                    'poll_url' => $response->pollUrl(),
                    'reference' => $paymentReference,
                    'currency' => $currency,
                    'amount' => $fee,
                    'fee_source' => $registrationFeeStage
                        ? 'fees.media_house.registration_fee.' . ($application->media_house_category_code ?? 'default')
                        : 'configured_application_fee',
                    'rbz_rate' => $rbzRate,
                ]);
            } else {
                Log::error('Paynow initiation failed', [
                    'application_id' => $application->id,
                    'currency' => $currency,
                    'errors' => $response->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment initiation failed. Please try again.',
                    'errors' => $response->errors(),
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error('PayNow initiation exception', [
                'application_id' => $application->id,
                'currency' => $currency,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment service temporarily unavailable. Please try again or contact support.',
            ], 500);
        }
    }

    public function initiateMobile(Request $request, $applicationId = null)
    {
        $user = Auth::user();

        // Handle missing or invalid application ID
        if (!$applicationId) {
            return response()->json(['success' => false, 'message' => 'Application ID is required.'], 400);
        }

        // Fetch application with error handling
        try {
            $application = Application::findOrFail($applicationId);
        } catch (\Exception $e) {
            Log::warning('Application not found for mobile payment initiation', [
                'application_id' => $applicationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        if ($application->applicant_user_id !== $user->id) {
            Log::warning('Unauthorized mobile payment attempt', [
                'application_id' => $application->id,
                'user_id' => $user->id,
                'applicant_id' => $application->applicant_user_id,
            ]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!in_array($application->status, [
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::SUBMITTED,
            Application::ACCOUNTS_REVIEW, 
            Application::APPROVED_AWAITING_PAYMENT,
            Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
            Application::REGISTRAR_APPROVED_PENDING_REGISTRATION_FEE_PAYMENT
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This application is not awaiting payment.'
            ], 400);
        }

        $request->merge([
            'currency' => $request->input('currency', $this->paynowService->getDefaultCurrency()),
        ]);

        $validated = $request->validate([
            'phone' => 'required|string|regex:/^0[7][0-9]{8}$/',
            'method' => 'required|in:ecocash,onemoney',
            'currency' => 'required|string|in:USD,ZWG',
        ]);

        $currency = $validated['currency'];
        $rbzRate = $currency === 'ZWG' ? $this->currentRbzExchangeRate() : null;
        if ($currency === 'ZWG' && !$rbzRate) {
            return response()->json(['success' => false, 'message' => 'ZWG payments are temporarily unavailable because Accounts has not configured the current RBZ exchange rate.'], 503);
        }

        if (!$this->paynowService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Paynow payments are currently disabled.',
            ], 503);
        }

        $fee = $this->calculateFee($application);
        if ($currency === 'ZWG') {
            $fee *= $rbzRate;
        }
        $paymentReference = $this->makePaynowReference('MOB', $application->id);
        $returnUrl = route('paynow.return', ['reference' => $paymentReference]);

        try {
            $paynow = $this->paynowService->getPaynow($currency, $returnUrl, route('paynow.callback'));
            
            $payment = $paynow->createPayment($paymentReference, $this->resolvePaynowAuthEmail($user->email));
            $payment->add($this->describeApplicationPayment($application), $fee);

            // Send mobile payment to PayNow
            $response = $paynow->sendMobile($payment, $validated['phone'], $validated['method']);

            if ($response->success()) {
                $this->recordApplicationPaymentInitiated(
                    $application,
                    $paymentReference,
                    $response->pollUrl(),
                    $fee,
                    $currency,
                    $validated['method'],
                    array_merge($this->paynowResponseData($response), $rbzRate ? ['rbz_rate' => $rbzRate] : [])
                );

                Payment::where('reference', $paymentReference)->update([
                    'phone_number' => $validated['phone'],
                    'instructions' => $response->instructions(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment request sent. Please approve on your phone.',
                    'poll_url' => $response->pollUrl(),
                    'reference' => $paymentReference,
                    'instructions' => $response->instructions(),
                    'currency' => $currency,
                    'amount' => $fee,
                    'rbz_rate' => $rbzRate,
                ]);
            } else {
                Log::error('Paynow mobile initiation failed', [
                    'application_id' => $application->id,
                    'errors' => $response->errors(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Mobile payment failed. Please try again.',
                    'errors' => $response->errors(),
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error('PayNow mobile initiation exception', [
                'application_id' => $application->id,
                'currency' => $currency,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment service temporarily unavailable. Please try again or contact support.',
            ], 500);
        }
    }

    public function checkStatus($applicationId = null)
    {
        $user = Auth::user();

        // Handle missing or invalid application ID
        if (!$applicationId) {
            return response()->json(['success' => false, 'message' => 'Application ID is required.'], 400);
        }

        // Fetch application with error handling
        try {
            $application = Application::findOrFail($applicationId);
        } catch (\Exception $e) {
            Log::warning('Application not found for status check', [
                'application_id' => $applicationId,
                'user_id' => $user->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        if ($application->applicant_user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $pollUrl = $this->getApplicationPollUrl($application);

        if (!$pollUrl) {
            return response()->json([
                'success' => false,
                'message' => 'No payment initiated for this application.',
            ], 400);
        }

        if ($application->payment_status === 'paid') {
            return response()->json([
                'success' => true,
                'paid' => true,
                'message' => 'Payment already confirmed.',
            ]);
        }

        try {
            $currency = $application->payment_currency ?? 'USD';
            $paynow = $this->getPaynow($currency);
            $status = $paynow->pollTransaction($pollUrl);

            if ($this->isSuccessfulPaynowStatus($status)) {
                $this->syncApplicationPaymentFromStatus($application, $status, $pollUrl);

                Log::info('Payment confirmed via polling', [
                    'application_id' => $application->id,
                    'reference' => $application->reference,
                    'new_status' => $application->status,
                ]);

                return response()->json([
                    'success' => true,
                    'paid' => true,
                    'message' => 'Payment confirmed!',
                ]);
            }

            if ($status) {
                $this->syncPendingApplicationPaymentStatus($application, $status, $pollUrl);
            }

            $statusText = $status ? $status->status() : 'unknown';

            return response()->json([
                'success' => true,
                'paid' => false,
                'status' => $statusText,
                'message' => 'Payment not yet confirmed. Status: ' . $statusText,
            ]);

        } catch (\Exception $e) {
            Log::error('Paynow polling failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'paid' => false,
                'message' => 'Could not check payment status. Please try again.',
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        Log::info('Paynow callback received', [
            'reference' => $request->input('reference'),
            'status' => $request->input('status'),
            'pollurl' => $request->input('pollurl'),
        ]);

        $reference = $request->input('reference');
        $pollUrl = $request->input('pollurl') ?: $request->input('pollUrl');

        if (!$reference) {
            Log::warning('Paynow callback: Missing reference');
            return response('OK', 200);
        }

        $application = $this->findApplicationForPaynowUpdate($reference, $pollUrl);
        $appeal = $this->findAppealForPaynowUpdate($reference, $pollUrl);

        if (!$application && !$appeal) {
            Log::warning('Paynow callback: Transaction not found', [
                'reference' => $reference,
                'pollurl' => $pollUrl,
            ]);
            return response('OK', 200);
        }

        $currency = $application?->payment_currency ?? $appeal?->payment_currency ?? 'USD';

        try {
            $status = $this->paynowService->processStatusUpdate($currency, null, route('paynow.callback'));
        } catch (\Throwable $e) {
            Log::warning('Paynow callback: Hash validation failed', [
                'reference' => $reference,
                'currency' => $currency,
                'error' => $e->getMessage(),
            ]);

            return response('Invalid callback', 400);
        }

        try {
            if ($application) {
                $storedPollUrl = $this->getApplicationPollUrl($application);

                if ($pollUrl && $storedPollUrl && $storedPollUrl !== $pollUrl) {
                    Log::warning('Paynow callback: Application poll URL mismatch', [
                        'reference' => $reference,
                        'expected_poll_url' => $storedPollUrl,
                        'received_poll_url' => $pollUrl,
                    ]);
                    return response('OK', 200);
                }

                if ($application->payment_status === 'paid') {
                    return response('OK', 200);
                }

                $pollForVerification = $pollUrl ?: $storedPollUrl;

                if (!$pollForVerification) {
                    Log::warning('Paynow callback: Application missing poll URL for verification', [
                        'reference' => $reference,
                        'application_id' => $application->id,
                    ]);
                    return response('OK', 200);
                }

                $pollResult = $this->getPaynow($currency)->pollTransaction($pollForVerification);

                if ($this->isSuccessfulPaynowStatus($status) && $this->isSuccessfulPaynowStatus($pollResult)) {
                    $this->syncApplicationPaymentFromStatus($application, $pollResult, $pollForVerification);

                    Log::info('Paynow application payment confirmed via callback', [
                        'application_id' => $application->id,
                        'reference' => $reference,
                    ]);
                } else {
                    $this->syncPendingApplicationPaymentStatus($application, $pollResult, $pollForVerification);
                }

                return response('OK', 200);
            }

            if ($appeal) {
                $paymentAttempt = $this->findAppealPayment($appeal);
                if (!$paymentAttempt || !hash_equals($paymentAttempt->reference, (string) $reference)) {
                    Log::warning('Paynow callback: Appeal payment reference mismatch', [
                        'appeal_id' => $appeal->id,
                        'received_reference' => $reference,
                    ]);
                    return response('OK', 200);
                }

                if ($pollUrl && $paymentAttempt->poll_url && !hash_equals($paymentAttempt->poll_url, $pollUrl)) {
                    Log::warning('Paynow callback: Appeal poll URL mismatch', [
                        'reference' => $reference,
                        'expected_poll_url' => $paymentAttempt->poll_url,
                        'received_poll_url' => $pollUrl,
                    ]);
                    return response('OK', 200);
                }

                if ($appeal->payment_status === 'paid') {
                    return response('OK', 200);
                }

                $pollForVerification = $paymentAttempt->poll_url;

                if (!$pollForVerification) {
                    Log::warning('Paynow callback: Appeal missing poll URL for verification', [
                        'reference' => $reference,
                        'appeal_id' => $appeal->id,
                    ]);
                    return response('OK', 200);
                }

                $pollResult = $this->getPaynow($currency)->pollTransaction($pollForVerification);

                if ($this->appealPaymentStatusMatches($paymentAttempt, $status)
                    && $this->appealPaymentStatusMatches($paymentAttempt, $pollResult)) {
                    $this->confirmAppealPayment($appeal, $paymentAttempt, $pollResult);

                    Log::info('Paynow appeal payment confirmed via callback', [
                        'appeal_id' => $appeal->id,
                        'reference' => $reference,
                    ]);
                } else {
                    $paymentAttempt->update([
                        'status' => 'processing',
                        'gateway_response' => $this->paynowResponseData($pollResult),
                        'last_checked_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Paynow callback: Verification failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }

    public function return(Request $request)
    {
        $pollUrl = $request->query('pollurl') ?: $request->query('pollUrl');
        $reference = $request->query('reference');

        if (!$pollUrl && !$reference) {
            return redirect()->route('mediahouse.portal')
                ->with('error', 'Invalid payment return. Please contact support if you completed payment.');
        }

        $application = $this->findApplicationForPaynowUpdate($reference, $pollUrl);

        if (!$application) {
            return redirect()->route('mediahouse.portal')
                ->with('error', 'Application not found. Please contact support if you completed payment.');
        }

        // Determine portal route based on application type
        $portalRoute = $application->application_type === 'accreditation'
            ? 'accreditation.home'
            : 'mediahouse.portal';

        // If already paid, show success directly
        if ($application->payment_status === 'paid') {
            return view('portal.payment-success', [
                'application' => $application,
                'redirectUrl' => route($portalRoute),
                'message' => 'Payment verified! Your application is being processed.',
            ]);
        }

        // Check payment status with PayNow
        try {
            $currency = $application->payment_currency ?? 'USD';
            $pollUrl = $pollUrl ?: $this->getApplicationPollUrl($application);

            if (!$pollUrl) {
                throw new \RuntimeException('Missing Paynow poll URL for returned transaction.');
            }

            $paynow = $this->getPaynow($currency);
            $status = $paynow->pollTransaction($pollUrl);

            if ($this->isSuccessfulPaynowStatus($status)) {
                $this->syncApplicationPaymentFromStatus($application, $status, $pollUrl);

                Log::info('Payment confirmed on return', [
                    'application_id' => $application->id,
                    'reference' => $application->reference,
                ]);

                // Show success page with auto-redirect
                return view('portal.payment-success', [
                    'application' => $application->fresh(),
                    'redirectUrl' => route($portalRoute),
                    'message' => 'Payment successful! Your application is now being processed.',
                ]);
            }

            // Payment pending or failed
            if ($status) {
                $this->syncPendingApplicationPaymentStatus($application, $status, $pollUrl);
            }

            $statusMessage = $status ? $status->status() : 'unknown';
            
            return view('portal.payment-pending', [
                'application' => $application,
                'redirectUrl' => route($portalRoute),
                'status' => $statusMessage,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment verification failed on return', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route($portalRoute)
                ->with('warning', 'Payment status could not be verified. Please check your dashboard or contact support.');
        }
    }

    private function calculateFee(Application $application): float
    {
        if ($application->application_type === 'accreditation') {
            $requestType = $application->request_type ?? 'new';
            $scope = strtolower((string) ($application->journalist_scope ?? 'local'));
            $feeKey = $scope === 'foreign' ? "foreign_{$requestType}" : $requestType;
            $fee = config("fees.accreditation.{$feeKey}");
            $fee ??= config("fees.accreditation.{$requestType}");
            if ($fee === null) {
                throw new \InvalidArgumentException('No fee is configured for this accreditation request type.');
            }
            return (float) $fee;
        }

        $requestType = $application->request_type ?? 'new';
        $feeKey = match ($requestType) {
            'new' => 'fees.media_house.application_fee',
            'renewal' => 'fees.media_house.renewal_fee',
            'replacement' => 'fees.media_house.replacement_fee',
            default => null,
        };

        if ($feeKey === null || config($feeKey) === null) {
            throw new \InvalidArgumentException('No fee is configured for this media house request type.');
        }

        return (float) config($feeKey);
    }
    
    /**
     * Calculate registration fee based on media house category
     */
    private function calculateRegistrationFee(Application $application): float
    {
        $category = $application->media_house_category_code ?? 'default';
        return (float) config("fees.media_house.registration_fee.{$category}", config('fees.media_house.registration_fee.default'));
    }
    
    /**
     * Calculate annual levy based on media house category
     */
    private function calculateAnnualLevy(Application $application): float
    {
        $category = $application->media_house_category_code ?? 'default';
        return (float) config("fees.media_house.annual_levy.{$category}", config('fees.media_house.annual_levy.default'));
    }

    /**
     * Submit manual PayNow reference number
     */
    public function submitReference(Request $request, $applicationId = null)
    {
        $user = Auth::user();

        // Handle missing or invalid application ID
        if (!$applicationId) {
            return response()->json(['success' => false, 'message' => 'Application ID is required.'], 400);
        }

        // Fetch application with error handling
        try {
            $application = Application::findOrFail($applicationId);
        } catch (\Exception $e) {
            Log::warning('Application not found for reference submission', [
                'application_id' => $applicationId,
                'user_id' => $user->id,
            ]);
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        if ($application->applicant_user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'reference' => 'required|string|max:100',
        ]);

        $registrationFeeStage = $application->application_type === 'registration'
            && $application->status === Application::REGISTRAR_APPROVED_PENDING_REGISTRATION_FEE_PAYMENT;
        $allowedStatuses = [
            Application::SUBMITTED,
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::ACCOUNTS_REVIEW,
            Application::APPROVED_AWAITING_PAYMENT,
            Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
            Application::REGISTRAR_APPROVED_PENDING_REGISTRATION_FEE_PAYMENT,
        ];

        if (!in_array($application->status, $allowedStatuses, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This application is not awaiting a payment reference.',
            ], 422);
        }

        $reference = $validated['reference'];
        $amount = $registrationFeeStage
            ? $this->calculateRegistrationFee($application)
            : $this->calculateFee($application);
        $from = $application->status;

        DB::transaction(function () use ($application, $user, $reference, $amount, $registrationFeeStage, $from) {
            $submission = \App\Models\PaymentSubmission::create([
                'application_id' => $application->id,
                'payment_stage' => $registrationFeeStage ? 'registration_fee' : 'application_fee',
                'method' => 'PAYNOW',
                'reference' => $reference,
                'amount' => $amount,
                'currency' => $application->payment_currency ?: 'USD',
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            \App\Models\Payment::updateOrCreate(
                ['reference' => $reference],
                [
                    'application_id' => $application->id,
                    'payer_user_id' => $user->id,
                    'method' => 'paynow',
                    'payment_method' => 'paynow',
                    'gateway' => 'paynow',
                    'source' => 'gateway',
                    'paynow_reference' => $reference,
                    'status' => 'processing',
                    'amount' => $amount,
                    'currency' => $application->payment_currency ?: 'USD',
                    'service_type' => $application->application_type,
                    'last_checked_at' => now(),
                ]
            );

            $application->update([
                'status' => $registrationFeeStage
                    ? Application::REGISTRATION_FEE_AWAITING_VERIFICATION
                    : Application::AWAITING_ACCOUNTS_VERIFICATION,
                'current_stage' => $registrationFeeStage ? 'accounts_verification_reg_fee' : 'accounts_verification',
                'paynow_reference' => $reference,
                'paynow_ref_submitted' => $reference,
                'payment_status' => 'pending',
                'payment_submission_method' => 'paynow_reference',
                'payment_submitted_at' => now(),
                'last_action_at' => now(),
                'last_action_by' => $user->id,
            ]);

            ActivityLogger::log('paynow_reference_submitted', $application, $from, $application->status, [
                'payment_submission_id' => $submission->id,
                'reference' => $reference,
            ]);
        });

        Log::info('Manual PayNow reference submitted for accounts verification', [
            'application_id' => $application->id,
            'reference' => $reference,
            'new_status' => $application->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PayNow reference submitted. Accounts will verify the payment shortly.',
        ]);
    }

    public function initiateLevy(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:USD,ZWG',
            'reference' => 'required|string',
            'description' => 'required|string',
            'entity_name' => 'required|string',
        ]);

        $user = Auth::user();
        $currency = $request->currency;

        try {
            $paynow = $this->getPaynow($currency);
            $payment = $paynow->createPayment(
                $request->reference,
                $this->resolvePaynowAuthEmail($user->email)
            );

            $payment->add($request->description, $request->amount);

            $response = $paynow->send($payment);

            if ($response->success()) {
                Log::info('Paynow levy payment initiated', [
                    'user_id' => $user->id,
                    'entity' => $request->entity_name,
                    'amount' => $request->amount,
                    'currency' => $currency,
                    'poll_url' => $response->pollUrl(),
                ]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $response->redirectUrl(),
                    'poll_url' => $response->pollUrl(),
                    'message' => 'Payment initiated successfully.',
                    'currency' => $currency,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment gateway could not process the request. Please try again.',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Paynow levy initiation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'currency' => $currency,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment service temporarily unavailable. Please upload proof of payment instead.',
            ], 500);
        }
    }

    /**
     * Generate unique receipt number for payment
     */
    private function generateReceiptNumber(string $method = 'paynow', $applicationId = null): string
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
     * Initiate PayNow payment for FOIA Appeal
     * This is a public endpoint (no authentication required since appeals are from website)
     */
    public function initiateAppealPayment(Request $request)
    {
        $validated = $request->validate([
            'appeal_id' => 'required|integer|exists:complaints,id',
            'currency' => 'sometimes|string|in:USD,ZWG',
        ]);

        $currency = $validated['currency'] ?? $this->paynowService->getDefaultCurrency();
        $appealFee = (float) config('fees.foia.appeal_fee');
        $appeal = Complaint::whereKey($validated['appeal_id'])->where('type', 'appeal')->firstOrFail();

        if ($appeal->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Payment already completed for this appeal.',
            ], 409);
        }

        if (!$this->paynowService->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Paynow payments are currently disabled.',
            ], 503);
        }

        try {
            [$appeal, $paymentAttempt] = DB::transaction(function () use ($validated, $currency, $appealFee) {
                $lockedAppeal = Complaint::whereKey($validated['appeal_id'])->where('type', 'appeal')->lockForUpdate()->firstOrFail();

                if ($lockedAppeal->payment_status === 'paid') {
                    throw new \DomainException('Payment already completed for this appeal.');
                }

                $activeAttempt = $lockedAppeal->appealPayments()
                    ->whereIn('status', ['initiating', 'processing'])
                    ->latest('id')
                    ->first();

                if ($activeAttempt) {
                    throw new \DomainException('A payment is already in progress for this appeal.');
                }

                $reference = $this->makePaynowReference('APPEAL', $lockedAppeal->id);
                $attempt = AppealPayment::create([
                    'complaint_id' => $lockedAppeal->id,
                    'reference' => $reference,
                    'amount' => $appealFee,
                    'currency' => $currency,
                    'status' => 'initiating',
                ]);

                $lockedAppeal->update([
                    'payment_ref' => $reference,
                    'payment_amount' => $appealFee,
                    'payment_currency' => $currency,
                    'payment_status' => 'pending',
                ]);

                return [$lockedAppeal, $attempt];
            });
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 409);
        }

        try {
            $paymentReference = $paymentAttempt->reference;
            $returnUrl = route('appeals.payment.return', [
                'appeal_id' => $appeal->id,
                'reference' => $paymentReference,
            ]);
            $paynow = $this->paynowService->getPaynow($currency, $returnUrl, route('paynow.callback'));
            
            $payment = $paynow->createPayment($paymentReference, $this->resolvePaynowAuthEmail($appeal->email));
            $payment->add('FOIA Appeal Processing Fee', $appealFee);

            $response = $paynow->send($payment);

            if ($response->success()) {
                $paymentAttempt->update([
                    'status' => 'processing',
                    'poll_url' => $response->pollUrl(),
                    'gateway_response' => ['redirect_url' => $response->redirectUrl()],
                    'last_checked_at' => now(),
                ]);
                $appeal->update([
                    'payment_ref' => $paymentReference,
                    'paynow_poll_url' => $response->pollUrl(),
                    'payment_currency' => $currency,
                ]);

                Log::info('FOIA Appeal payment initiated', [
                    'appeal_id' => $appeal->id,
                    'reference' => $paymentReference,
                    'amount' => $appealFee,
                    'currency' => $currency,
                ]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $response->redirectUrl(),
                    'reference' => $paymentReference,
                    'appeal_reference' => $appeal->reference_number,
                    'currency' => $currency,
                ]);
            }

            $paymentAttempt->update([
                'status' => 'failed',
                'gateway_response' => ['errors' => $response->errors()],
                'last_checked_at' => now(),
            ]);
            $appeal->update(['payment_status' => 'rejected']);

            Log::error('FOIA Appeal payment initiation failed', [
                'appeal_id' => $appeal->id,
                'errors' => $response->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initiation failed. Please try again.',
                'errors' => $response->errors(),
            ], 500);

        } catch (\Throwable $e) {
            $paymentAttempt->update([
                'status' => 'failed',
                'gateway_response' => ['error' => get_class($e)],
                'last_checked_at' => now(),
            ]);
            $appeal->update(['payment_status' => 'rejected']);

            Log::error('FOIA Appeal payment exception', [
                'appeal_id' => $appeal->id,
                'currency' => $currency,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment service temporarily unavailable. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Check payment status for FOIA Appeal
     * Public endpoint (no authentication required)
     */
    public function checkAppealPaymentStatus(Request $request)
    {
        $validated = $request->validate([
            'appeal_id' => 'required|integer|exists:complaints,id',
            'currency' => 'sometimes|string|in:USD,ZWG',
        ]);

        $appeal = Complaint::whereKey($validated['appeal_id'])->where('type', 'appeal')->firstOrFail();
        $currency = $validated['currency'] ?? $appeal->payment_currency ?? 'USD';
        $paymentAttempt = $this->findAppealPayment($appeal);

        if (!$paymentAttempt?->poll_url) {
            return response()->json([
                'success' => false,
                'message' => 'No payment initiated for this appeal.',
            ], 400);
        }

        if ($appeal->payment_status === 'paid') {
            return response()->json([
                'success' => true,
                'paid' => true,
                'message' => 'Payment already confirmed.',
                'reference' => $appeal->reference_number,
            ]);
        }

        try {
            $paynow = $this->getPaynow($paymentAttempt->currency ?: $currency);
            $status = $paynow->pollTransaction($paymentAttempt->poll_url);

            if ($this->appealPaymentStatusMatches($paymentAttempt, $status)) {
                $this->confirmAppealPayment($appeal, $paymentAttempt, $status);

                Log::info('FOIA Appeal payment confirmed', [
                    'appeal_id' => $appeal->id,
                    'reference' => $paymentAttempt->reference,
                ]);

                return response()->json([
                    'success' => true,
                    'paid' => true,
                    'message' => 'Payment confirmed!',
                    'reference' => $appeal->reference_number,
                ]);
            }

            $statusText = $status ? $status->status() : 'pending';
            $attemptStatus = in_array(strtolower((string) $statusText), ['failed', 'cancelled', 'refunded', 'disputed'], true)
                ? 'failed'
                : 'processing';
            $paymentAttempt->update([
                'status' => $attemptStatus,
                'provider_reference' => $status?->paynowReference() ?: $paymentAttempt->provider_reference,
                'gateway_response' => $this->paynowResponseData($status),
                'last_checked_at' => now(),
            ]);
            if ($attemptStatus === 'failed') {
                $appeal->update(['payment_status' => 'rejected']);
            }

            return response()->json([
                'success' => true,
                'paid' => false,
                'status' => $statusText,
                'reference' => $appeal->reference_number,
                'message' => 'Payment not yet confirmed.',
            ]);

        } catch (\Exception $e) {
            Log::error('Appeal payment status check failed', [
                'appeal_id' => $appeal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not check payment status.',
            ], 500);
        }
    }

    /**
     * Handle return from PayNow for FOIA Appeal
     * Public endpoint (no authentication required)
     */
    public function appealPaymentReturn(Request $request)
    {
        $reference = $request->query('reference');
        $appealId = $request->query('appeal_id');

        if (!$reference || !$appealId) {
            return redirect()->to(config('app.website_url') . '/appeals')
                ->with('error', 'Invalid payment return.');
        }

        $appeal = Complaint::whereKey($appealId)->where('type', 'appeal')->first();

        if (!$appeal || !$appeal->payment_ref || !hash_equals($appeal->payment_ref, (string) $reference)) {
            return redirect()->to(config('app.website_url') . '/appeals')
                ->with('error', 'The payment reference could not be matched to this appeal.');
        }

        $paymentAttempt = $this->findAppealPayment($appeal);
        if (!$paymentAttempt) {
            return redirect()->to(config('app.website_url') . '/appeals')
                ->with('error', 'Payment details could not be found.');
        }

        $currency = $paymentAttempt->currency ?: ($appeal->payment_currency ?? 'USD');

        try {
            if (!$paymentAttempt->poll_url) {
                throw new \RuntimeException('Missing Paynow poll URL for returned appeal payment.');
            }

            $paynow = $this->getPaynow($currency);
            $status = $paynow->pollTransaction($paymentAttempt->poll_url);

            if ($this->appealPaymentStatusMatches($paymentAttempt, $status)
                && $this->confirmAppealPayment($appeal, $paymentAttempt, $status)) {

                Log::info('FOIA Appeal submitted after payment confirmation', [
                    'appeal_id' => $appeal->id,
                    'email' => $appeal->email,
                    'payment_ref' => $appeal->payment_ref,
                ]);

                return redirect()->to(config('app.website_url') . '/appeals/success?ref=' . urlencode($appeal->reference_number));
            }

            // Payment not confirmed
            return redirect()->to(config('app.website_url') . '/appeals/payment?appeal_id=' . $appeal->id)
                ->with('error', 'Payment was not confirmed. Please try again.');

        } catch (\Exception $e) {
            Log::error('Appeal payment verification failed', [
                'appeal_id' => $appeal->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to(config('app.website_url') . '/appeals/payment?appeal_id=' . $appeal->id)
                ->with('error', 'Payment verification failed. Please try again.');
        }
    }
}
