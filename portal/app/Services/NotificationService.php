<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class NotificationService
{
    protected $twilioClient;
    protected $twilioSid;
    protected $twilioToken;
    protected $twilioWhatsAppFrom;

    public function __construct()
    {
        $this->twilioSid = config('services.twilio.sid');
        $this->twilioToken = config('services.twilio.token');
        $this->twilioWhatsAppFrom = config('services.twilio.whatsapp_from');

        if ($this->twilioSid && $this->twilioToken) {
            $this->twilioClient = new Client($this->twilioSid, $this->twilioToken);
        }
    }

    /**
     * Send SMS notification
     * 
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function sendSMS(string $phoneNumber, string $message): bool
    {
        if (!$this->twilioClient) {
            Log::warning('Twilio not configured. SMS not sent.', [
                'phone' => $phoneNumber,
                'message' => $message,
            ]);
            return false;
        }

        try {
            $twilioMessage = $this->twilioClient->messages->create(
                $phoneNumber,
                [
                    'from' => config('services.twilio.sms_from'),
                    'body' => $message,
                ]
            );

            Log::info('SMS sent successfully', [
                'phone' => $phoneNumber,
                'sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send WhatsApp notification
     * 
     * @param string $phoneNumber
     * @param string $message
     * @return bool
     */
    public function sendWhatsApp(string $phoneNumber, string $message): bool
    {
        if (!$this->twilioClient) {
            Log::warning('Twilio not configured. WhatsApp message not sent.', [
                'phone' => $phoneNumber,
                'message' => $message,
            ]);
            return false;
        }

        try {
            // Format phone number for WhatsApp
            $whatsappNumber = 'whatsapp:' . $phoneNumber;
            $whatsappFrom = 'whatsapp:' . $this->twilioWhatsAppFrom;

            $twilioMessage = $this->twilioClient->messages->create(
                $whatsappNumber,
                [
                    'from' => $whatsappFrom,
                    'body' => $message,
                ]
            );

            Log::info('WhatsApp message sent successfully', [
                'phone' => $phoneNumber,
                'sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send collection reminder notification
     * 
     * @param object $record AccreditationRecord or RegistrationRecord
     * @param string $type 'accreditation' or 'registration'
     * @return bool
     */
    public function sendCollectionReminder($record, string $type = 'accreditation'): bool
    {
        $user = $type === 'accreditation' ? $record->holder : $record->contact;
        
        if (!$user || !$user->phone_number) {
            return false;
        }

        $phoneNumber = $this->formatPhoneNumber($user->phone_country_code, $user->phone_number);
        
        $documentType = $type === 'accreditation' ? 'accreditation card' : 'registration certificate';
        $number = $type === 'accreditation' ? $record->certificate_no : $record->registration_no;
        
        $message = "Dear {$user->name}, your {$documentType} (No: {$number}) is ready for collection at the ZMC offices. Please bring your ID for verification. Contact us for more information.";

        // Send via WhatsApp (preferred) or SMS as fallback
        $whatsappSent = $this->sendWhatsApp($phoneNumber, $message);
        
        if (!$whatsappSent) {
            // Fallback to SMS if WhatsApp fails
            $smsSent = $this->sendSMS($phoneNumber, $message);
            return $smsSent;
        }

        return true;
    }

    /**
     * Send renewal reminder notification
     * 
     * @param object $record AccreditationRecord or RegistrationRecord
     * @param string $type 'accreditation' or 'registration'
     * @return bool
     */
    public function sendRenewalReminder($record, string $type = 'accreditation'): bool
    {
        $user = $type === 'accreditation' ? $record->holder : $record->contact;
        
        if (!$user || !$user->phone_number) {
            return false;
        }

        $phoneNumber = $this->formatPhoneNumber($user->phone_country_code, $user->phone_number);
        
        $documentType = $type === 'accreditation' ? 'accreditation' : 'registration';
        $number = $type === 'accreditation' ? $record->certificate_no : $record->registration_no;
        $expiryDate = $record->expires_at ? $record->expires_at->format('d M Y') : 'soon';
        
        $message = "Dear {$user->name}, your {$documentType} (No: {$number}) expires on {$expiryDate}. Please submit your renewal application through the ZMC portal to avoid service interruption.";

        // Send via WhatsApp (preferred) or SMS as fallback
        $whatsappSent = $this->sendWhatsApp($phoneNumber, $message);
        
        if (!$whatsappSent) {
            // Fallback to SMS if WhatsApp fails
            $smsSent = $this->sendSMS($phoneNumber, $message);
            return $smsSent;
        }

        return true;
    }

    /**
     * Format phone number for international use
     * 
     * @param string|null $countryCode
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber(?string $countryCode, string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if provided
        if ($countryCode) {
            $code = preg_replace('/[^0-9]/', '', $countryCode);
            return '+' . $code . $phone;
        }
        
        // Default to Zimbabwe (+263) if no country code
        return '+263' . ltrim($phone, '0');
    }
}
