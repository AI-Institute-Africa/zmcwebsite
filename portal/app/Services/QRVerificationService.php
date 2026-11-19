<?php

namespace App\Services;

use App\Models\AccreditationRecord;
use App\Models\RegistrationRecord;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

class QRVerificationService
{
    /**
     * Generate QR code for accreditation card or certificate
     * 
     * @param string $recordType 'accreditation' or 'registration'
     * @param string $recordNumber
     * @param array $additionalData
     * @return array
     */
    public function generateQRCode(string $recordType, string $recordNumber, array $additionalData = []): array
    {
        $result = [
            'success' => false,
            'qr_code' => null,
            'verification_url' => null,
            'signed_token' => null,
            'errors' => []
        ];
        
        try {
            // Create verification payload
            $payload = [
                'record_type' => $recordType,
                'record_number' => $recordNumber,
                'generated_at' => now()->toIso8601String(),
                'expires_at' => now()->addYear()->toIso8601String(),
                'version' => '1.0'
            ];
            
            // Add additional data if provided
            if (!empty($additionalData)) {
                $payload = array_merge($payload, $additionalData);
            }
            
            // Create signed token (cryptographic signature)
            $signedToken = $this->createSignedToken($payload);
            $result['signed_token'] = $signedToken;
            
            // Create verification URL
            $verificationUrl = route('verification.verify', ['token' => $signedToken]);
            $result['verification_url'] = $verificationUrl;
            
            // Generate QR code containing the verification URL
            $qrCode = new QrCode(
                data: $verificationUrl,
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 300,
                margin: 2,
            );
            $qrCodeImage = (new PngWriter())->write($qrCode)->getString();

            $result['qr_code'] = base64_encode($qrCodeImage);
            $result['success'] = true;
            
            Log::info('QR code generated', [
                'record_type' => $recordType,
                'record_number' => $recordNumber
            ]);
            
        } catch (\Exception $e) {
            $result['errors'][] = 'QR code generation failed: ' . $e->getMessage();
            Log::error('QR code generation failed', [
                'record_type' => $recordType,
                'record_number' => $recordNumber,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Verify QR code token and return record information
     * 
     * @param string $token
     * @return array
     */
    public function verifyQRToken(string $token): array
    {
        $result = [
            'valid' => false,
            'record' => null,
            'status' => null,
            'warnings' => [],
            'errors' => [],
            'verification_timestamp' => now()->toIso8601String()
        ];
        
        try {
            // Decrypt and validate token
            $payload = $this->validateSignedToken($token);
            
            if (!$payload) {
                $result['errors'][] = 'Invalid or expired QR code token';
                return $result;
            }
            
            // Retrieve record based on type
            $record = match($payload['record_type']) {
                'accreditation' => $this->getAccreditationRecord($payload['record_number']),
                'registration' => $this->getRegistrationRecord($payload['record_number']),
                default => null
            };
            
            if (!$record) {
                $result['errors'][] = 'Record not found';
                return $result;
            }
            
            // Check if token has expired
            if (isset($payload['expires_at']) && now()->gt(\DateTime::createFromFormat('Y-m-d H:i:s', $payload['expires_at']))) {
                $result['warnings'][] = 'QR code token has expired - record status may have changed';
            }
            
            // Populate result with record information
            $result['valid'] = true;
            $result['record'] = $this->formatRecordForVerification($record, $payload['record_type']);
            $result['status'] = $this->determineRecordStatus($record);
            
            // Log verification event
            $this->logVerificationEvent($record, $payload['record_type']);
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Verification failed: ' . $e->getMessage();
            Log::error('QR verification failed', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Create signed token for QR code
     * 
     * @param array $payload
     * @return string
     */
    private function createSignedToken(array $payload): string
    {
        // Serialize payload
        $serialized = json_encode($payload);
        
        // Encrypt with application key
        $encrypted = Crypt::encrypt($serialized);
        
        // Add signature hash
        $signature = hash_hmac('sha256', $encrypted, config('app.key'));
        
        return base64_encode($encrypted . '|' . $signature);
    }
    
    /**
     * Validate signed token
     * 
     * @param string $token
     * @return array|null
     */
    private function validateSignedToken(string $token): ?array
    {
        try {
            // Decode base64
            $decoded = base64_decode($token);
            
            // Split encrypted data and signature
            $parts = explode('|', $decoded);
            if (count($parts) !== 2) {
                return null;
            }
            
            [$encrypted, $signature] = $parts;
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $encrypted, config('app.key'));
            if (!hash_equals($expectedSignature, $signature)) {
                return null; // Invalid signature
            }
            
            // Decrypt payload
            $decrypted = Crypt::decrypt($encrypted);
            $payload = json_decode($decrypted, true);
            
            if (!$payload) {
                return null;
            }
            
            return $payload;
            
        } catch (\Exception $e) {
            Log::error('Token validation failed', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get accreditation record
     * 
     * @param string $accreditationNumber
     * @return AccreditationRecord|null
     */
    private function getAccreditationRecord(string $accreditationNumber): ?AccreditationRecord
    {
        return AccreditationRecord::where('accreditation_number', $accreditationNumber)->first();
    }
    
    /**
     * Get registration record
     * 
     * @param string $registrationNumber
     * @return RegistrationRecord|null
     */
    private function getRegistrationRecord(string $registrationNumber): ?RegistrationRecord
    {
        return RegistrationRecord::where('registration_number', $registrationNumber)->first();
    }
    
    /**
     * Format record for verification response
     * 
     * @param mixed $record
     * @param string $recordType
     * @return array
     */
    private function formatRecordForVerification($record, string $recordType): array
    {
        if ($recordType === 'accreditation') {
            return [
                'type' => 'accreditation',
                'accreditation_number' => $record->accreditation_number,
                'name' => $record->name,
                'category' => $record->category,
                'category_description' => $record->category_description,
                'validity_period' => [
                    'from' => $record->valid_from,
                    'to' => $record->valid_to
                ],
                'photo_url' => $record->photo_url ?? null,
                'issuing_office' => $record->issuing_office ?? 'ZMC Headquarters',
                'issued_date' => $record->issued_at
            ];
        } else {
            return [
                'type' => 'registration',
                'registration_number' => $record->registration_number,
                'organisation_name' => $record->organisation_name,
                'category' => $record->category,
                'category_description' => $record->category_description,
                'validity_period' => [
                    'from' => $record->valid_from,
                    'to' => $record->valid_to
                ],
                'issuing_office' => $record->issuing_office ?? 'ZMC Headquarters',
                'issued_date' => $record->issued_at
            ];
        }
    }
    
    /**
     * Determine current record status
     * 
     * @param mixed $record
     * @return string
     */
    private function determineRecordStatus($record): string
    {
        $validTo = \DateTime::createFromFormat('Y-m-d', $record->valid_to);
        $now = new \DateTime();
        
        // Check for explicit status fields
        if (isset($record->status)) {
            $status = strtolower($record->status);
            
            if (in_array($status, ['revoked', 'suspended', 'rejected'])) {
                return 'Invalid';
            }
            if (in_array($status, ['replaced'])) {
                return 'Replaced';
            }
        }
        
        // Check validity period
        if ($validTo < $now) {
            return 'Expired';
        }
        
        // Check if replaced by newer record
        if (isset($record->replaced_by_id) && $record->replaced_by_id) {
            return 'Replaced';
        }
        
        return 'Active';
    }
    
    /**
     * Log verification event
     * 
     * @param mixed $record
     * @param string $recordType
     * @return void
     */
    private function logVerificationEvent($record, string $recordType): void
    {
        Log::info('QR code verification', [
            'record_type' => $recordType,
            'record_number' => $recordType === 'accreditation' ? $record->accreditation_number : $record->registration_number,
            'timestamp' => now()->toIso8601String()
        ]);
        
        // In production, this would also store in a verification_events table
        // for analytics and fraud detection (unusual verification patterns)
    }
    
    /**
     * Check for unusual verification patterns (fraud detection)
     * 
     * @param string $recordNumber
     * @param string $ipAddress
     * @param string $userAgent
     * @return array
     */
    public function checkVerificationPatterns(string $recordNumber, string $ipAddress, string $userAgent): array
    {
        $result = [
            'suspicious' => false,
            'flags' => [],
            'warnings' => []
        ];
        
        // Check for excessive verification attempts
        $recentVerifications = $this->getRecentVerificationCount($recordNumber, $ipAddress);
        
        if ($recentVerifications > 10) {
            $result['suspicious'] = true;
            $result['flags'][] = [
                'type' => 'excessive_verifications',
                'severity' => 'medium',
                'description' => "High number of verification attempts ({$recentVerifications})",
                'count' => $recentVerifications
            ];
        }
        
        // Check for verifications from multiple IPs in short time
        $multipleIPs = $this->checkMultipleIPVerifications($recordNumber);
        
        if ($multipleIPs['suspicious']) {
            $result['suspicious'] = true;
            $result['flags'][] = [
                'type' => 'multiple_ip_verifications',
                'severity' => 'high',
                'description' => 'Record verified from multiple IP addresses in short period',
                'ip_count' => $multipleIPs['count'],
                'time_period' => '1 hour'
            ];
        }
        
        return $result;
    }
    
    /**
     * Get recent verification count
     * 
     * @param string $recordNumber
     * @param string $ipAddress
     * @return int
     */
    private function getRecentVerificationCount(string $recordNumber, string $ipAddress): int
    {
        // In production, this would query a verification_events table
        // For now, return a placeholder
        return 0;
    }
    
    /**
     * Check for multiple IP verifications
     * 
     * @param string $recordNumber
     * @return array
     */
    private function checkMultipleIPVerifications(string $recordNumber): array
    {
        // In production, this would analyze verification_events table
        // for verifications from different IPs within a short time window
        return [
            'suspicious' => false,
            'count' => 0
        ];
    }
    
    /**
     * Invalidate QR codes for replaced or revoked records
     * 
     * @param string $recordType
     * @param string $recordNumber
     * @return bool
     */
    public function invalidateQRCode(string $recordType, string $recordNumber): bool
    {
        try {
            // Add to invalidation list
            // In production, this would store in an invalid_qr_codes table
            // with record details and revocation reason
            
            Log::info('QR code invalidated', [
                'record_type' => $recordType,
                'record_number' => $recordNumber,
                'timestamp' => now()->toIso8601String()
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('QR code invalidation failed', [
                'record_type' => $recordType,
                'record_number' => $recordNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Generate batch QR codes for multiple records
     * 
     * @param array $records
     * @return array
     */
    public function generateBatchQRCodes(array $records): array
    {
        $results = [];
        
        foreach ($records as $record) {
            $result = $this->generateQRCode(
                $record['type'],
                $record['number'],
                $record['additional_data'] ?? []
            );
            
            $results[] = [
                'record_number' => $record['number'],
                'success' => $result['success'],
                'qr_code' => $result['qr_code'] ?? null,
                'errors' => $result['errors'] ?? []
            ];
        }
        
        return $results;
    }
    
    /**
     * Validate QR code data integrity
     * 
     * @param string $qrData
     * @return array
     */
    public function validateQRIntegrity(string $qrData): array
    {
        $result = [
            'valid' => true,
            'issues' => []
        ];
        
        // Check if QR data is a valid URL
        if (!filter_var($qrData, FILTER_VALIDATE_URL)) {
            $result['valid'] = false;
            $result['issues'][] = 'QR code does not contain a valid verification URL';
        }
        
        // Check if QR data is suspiciously short (could be plain text)
        if (strlen($qrData) < 50 && !filter_var($qrData, FILTER_VALIDATE_URL)) {
            $result['valid'] = false;
            $result['issues'][] = 'QR code contains insufficient data - may be plain text instead of secure token';
        }
        
        // Check if QR data matches expected format
        if (filter_var($qrData, FILTER_VALIDATE_URL)) {
            $expectedDomain = parse_url($qrData, PHP_URL_HOST);
            $currentDomain = parse_url(config('app.url'), PHP_URL_HOST);
            
            if ($expectedDomain !== $currentDomain) {
                $result['valid'] = false;
                $result['issues'][] = 'QR code points to unexpected domain';
            }
        }
        
        return $result;
    }
}
