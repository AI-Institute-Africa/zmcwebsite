<?php

namespace App\Services;

use Paynow\Payments\Paynow;
use Illuminate\Support\Facades\Log;
use Paynow\Payments\FluentBuilder;
use Paynow\Core\StatusResponse;
use InvalidArgumentException;

/**
 * Paynow Service with Dual Currency Support
 * 
 * Handles USD and ZWG integrations for Zimbabwe Media Commission
 */
class PayNowService
{
    /**
     * Get Paynow instance for specified currency
     * 
     * @param string $currency USD or ZWG
     * @return Paynow
     * @throws \Exception
     */
    public function getPaynow(string $currency = 'USD', ?string $returnUrl = null, ?string $resultUrl = null): Paynow
    {
        $currency = $this->normalizeCurrency($currency);

        // Get configuration for the currency
        $configKey = strtolower($currency);
        $config = config("services.paynow.{$configKey}");

        if (empty($config['integration_id']) || empty($config['integration_key'])) {
            throw new \Exception("Paynow {$currency} credentials are not configured. Please set PAYNOW_{$currency}_INTEGRATION_ID and PAYNOW_{$currency}_INTEGRATION_KEY in .env");
        }

        $returnUrl = $returnUrl ?: config('services.paynow.return_url') ?: route('paynow.return');
        $resultUrl = $resultUrl ?: config('services.paynow.result_url') ?: route('paynow.callback');

        $paynow = new Paynow(
            $config['integration_id'],
            $config['integration_key'],
            $returnUrl,
            $resultUrl
        );

        Log::info("Paynow {$currency} instance created", [
            'result_url' => $resultUrl,
            'return_url' => $returnUrl,
        ]);

        return $paynow;
    }

    public function createPayment(string $reference, string $authEmail, ?string $currency = null): FluentBuilder
    {
        return $this->getPaynow($currency ?? $this->getDefaultCurrency())
            ->createPayment($reference, $authEmail);
    }

    public function send(FluentBuilder $payment, ?string $currency = null)
    {
        return $this->getPaynow($currency ?? $this->getDefaultCurrency())
            ->send($payment);
    }

    public function sendMobile(FluentBuilder $payment, string $phone, string $method, ?string $currency = null)
    {
        return $this->getPaynow($currency ?? $this->getDefaultCurrency())
            ->sendMobile($payment, $phone, $method);
    }

    public function pollTransaction(string $pollUrl, ?string $currency = null)
    {
        return $this->getPaynow($currency ?? $this->getDefaultCurrency())
            ->pollTransaction($pollUrl);
    }

    public function processStatusUpdate(?string $currency = null, ?string $returnUrl = null, ?string $resultUrl = null): StatusResponse
    {
        return $this->getPaynow($currency ?? $this->getDefaultCurrency(), $returnUrl, $resultUrl)
            ->processStatusUpdate();
    }

    /**
     * Get default currency from config
     */
    public function getDefaultCurrency(): string
    {
        return $this->normalizeCurrency(config('services.paynow.default_currency', 'USD'));
    }

    /**
     * Check if Paynow is enabled
     */
    public function isEnabled(): bool
    {
        return (bool) config('services.paynow.enabled', false);
    }

    /**
     * Get currency configuration
     */
    public function getCurrencyConfig(string $currency): array
    {
        try {
            $currency = $this->normalizeCurrency($currency);
        } catch (InvalidArgumentException $e) {
            return [];
        }

        $key = strtolower($currency);
        $config = config("services.paynow.{$key}", []);

        return [
            'currency' => $currency,
            'integration_id' => $config['integration_id'] ?? null,
            'is_configured' => !empty($config['integration_id']) && !empty($config['integration_key']),
        ];
    }

    /**
     * Get all supported currencies
     */
    public function getSupportedCurrencies(): array
    {
        return ['USD', 'ZWG'];
    }

    public function getMerchantEmail(): string
    {
        return (string) (config('services.paynow.merchant_email') ?: config('mail.from.address'));
    }

    /**
     * Convert amount between currencies (if needed)
     * Note: This is a placeholder - implement actual exchange rate logic if needed
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Placeholder conversion - implement actual exchange rate API if needed
        Log::warning('Currency conversion not implemented', [
            'amount' => $amount,
            'from' => $fromCurrency,
            'to' => $toCurrency,
        ]);

        return $amount;
    }

    private function normalizeCurrency(?string $currency): string
    {
        $currency = strtoupper(trim((string) ($currency ?: 'USD')));

        if (!in_array($currency, ['USD', 'ZWG'], true)) {
            throw new InvalidArgumentException("Invalid Paynow currency: {$currency}. Must be USD or ZWG.");
        }

        return $currency;
    }
}
