<?php

namespace App\Services\Credentials;

use App\Models\Application;
use App\Models\QRCodeRegistry;
use App\Models\QRScanAuditLog;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * QRCodeSecurityService
 * Generates and verifies QR codes with cryptographic signing
 * QR codes contain signed JWT tokens to prevent tampering
 * QR data cannot be copied to fake cards - signature verification will fail
 */
class QRCodeSecurityService
{
    private const ALGORITHM = 'HS256';
    private const VALIDITY_PERIOD = 365 * 10; // 10 years in days

    /**
     * Generate QR code for accreditation number
     */
    public function generateAccreditationQRCode(Application $application, string $accreditationNumber): array
    {
        return DB::transaction(function () use ($application, $accreditationNumber) {
            // Get accreditation record
            $accRecord = \DB::table('accreditation_numbers')
                ->where('accreditation_number', $accreditationNumber)
                ->first();

            if (!$accRecord) {
                throw new \Exception('Accreditation number not found');
            }

            // Generate unique QR ID
            $qrCodeId = 'ACC_' . bin2hex(random_bytes(16));

            $secretKey = (string) config('services.qr.secret_key');
            if ($secretKey === '') {
                throw new \RuntimeException('Configure QR_SECRET_KEY or APP_KEY before generating credential QR codes.');
            }

            // Create JWT payload with credential data
            $payload = [
                'type' => 'accreditation',
                'credential_number' => $accreditationNumber,
                'holder_name' => $accRecord->holder_name,
                'holder_id' => $accRecord->holder_id_number,
                'category' => $accRecord->applicant_category,
                'issue_date' => $accRecord->issue_date,
                'valid_from' => $accRecord->valid_from,
                'valid_until' => $accRecord->valid_until,
                'status' => $accRecord->status,
                'generated_at' => now()->toIso8601String(),
                'qr_id' => $qrCodeId,
            ];

            // Sign the payload
            $token = JWT::encode($payload, $secretKey, self::ALGORITHM);

            // Generate QR code image (using QR code library)
            $qrImage = $this->generateQRImage($token);

            // Store in registry
            $qrRecord = QRCodeRegistry::create([
                'qr_code_id' => $qrCodeId,
                'credential_type' => 'accreditation',
                'credential_number' => $accreditationNumber,
                'application_id' => $application->id,
                'qr_data' => $token,
                'qr_signature' => hash_hmac('sha256', $token, $secretKey),
                'qr_public_key_id' => 'default', // Can track multiple keys
                'generated_at' => now(),
                'expires_at' => now()->addDays(self::VALIDITY_PERIOD),
                'is_valid' => true,
                'current_status' => 'active',
                'photo_url' => $application->applicant_photo_url,
            ]);

            // Update accreditation record
            \DB::table('accreditation_numbers')
                ->where('accreditation_number', $accreditationNumber)
                ->update(['qr_code_id' => $qrCodeId]);

            return [
                'success' => true,
                'qr_code_id' => $qrCodeId,
                'qr_image' => $qrImage,
                'qr_token' => $token,
                'generated_at' => now(),
            ];
        });
    }

    /**
     * Generate QR code for registration number
     */
    public function generateRegistrationQRCode(Application $application, string $registrationNumber): array
    {
        return DB::transaction(function () use ($application, $registrationNumber) {
            // Get registration record
            $regRecord = \DB::table('registration_numbers')
                ->where('registration_number', $registrationNumber)
                ->first();

            if (!$regRecord) {
                throw new \Exception('Registration number not found');
            }

            $qrCodeId = 'REG_' . bin2hex(random_bytes(16));
            $secretKey = (string) config('services.qr.secret_key');
            if ($secretKey === '') {
                throw new \RuntimeException('Configure QR_SECRET_KEY or APP_KEY before generating credential QR codes.');
            }

            // Create JWT payload
            $payload = [
                'type' => 'registration',
                'credential_number' => $registrationNumber,
                'organization_name' => $regRecord->organization_name,
                'organization_type' => $regRecord->organization_type,
                'issue_date' => $regRecord->issue_date,
                'valid_from' => $regRecord->valid_from,
                'valid_until' => $regRecord->valid_until,
                'status' => $regRecord->status,
                'generated_at' => now()->toIso8601String(),
                'qr_id' => $qrCodeId,
            ];

            // Sign the payload
            $token = JWT::encode($payload, $secretKey, self::ALGORITHM);

            // Generate QR code image
            $qrImage = $this->generateQRImage($token);

            // Store in registry
            $qrRecord = QRCodeRegistry::create([
                'qr_code_id' => $qrCodeId,
                'credential_type' => 'registration',
                'credential_number' => $registrationNumber,
                'application_id' => $application->id,
                'qr_data' => $token,
                'qr_signature' => hash_hmac('sha256', $token, $secretKey),
                'qr_public_key_id' => 'default',
                'generated_at' => now(),
                'expires_at' => now()->addDays(self::VALIDITY_PERIOD),
                'is_valid' => true,
                'current_status' => 'active',
            ]);

            // Update registration record
            \DB::table('registration_numbers')
                ->where('registration_number', $registrationNumber)
                ->update(['qr_code_id' => $qrCodeId]);

            return [
                'success' => true,
                'qr_code_id' => $qrCodeId,
                'qr_image' => $qrImage,
                'qr_token' => $token,
                'generated_at' => now(),
            ];
        });
    }

    /**
     * Verify QR code when scanned
     * Returns credential data if QR is valid
     * Returns error if QR has been tampered with or credential is invalid
     */
    public function verifyQRCode(string $qrToken, ?string $ipAddress = null, ?string $location = null): array
    {
        try {
            $secretKey = (string) config('services.qr.secret_key');
            if ($secretKey === '') {
                return [
                    'valid' => false,
                    'error' => 'QR_SIGNING_KEY_NOT_CONFIGURED',
                    'message' => 'QR verification is temporarily unavailable',
                ];
            }

            try {
                $decoded = JWT::decode($qrToken, new Key($secretKey, self::ALGORITHM));
                $qrRecord = QRCodeRegistry::where('qr_code_id', $decoded->qr_id ?? null)->first();
            } catch (\Throwable $signatureError) {
                // Older QR codes were signed with a built-in legacy key. Accept one
                // only when the exact token is already registered in the database.
                $qrRecord = QRCodeRegistry::where('qr_data', $qrToken)->first();
                $parts = explode('.', $qrToken);
                if (!$qrRecord || count($parts) !== 3) {
                    throw $signatureError;
                }

                $decoded = JWT::jsonDecode(JWT::urlsafeB64Decode($parts[1]));
                if (($decoded->qr_id ?? null) !== $qrRecord->qr_code_id) {
                    throw $signatureError;
                }
            }

            if (!$qrRecord) {
                return [
                    'valid' => false,
                    'error' => 'QR_NOT_FOUND',
                    'message' => 'This QR code is not recognized in our system',
                ];
            }

            // Check if QR is still valid
            if (!$qrRecord->is_valid || $qrRecord->current_status !== 'active') {
                return [
                    'valid' => false,
                    'error' => 'QR_INVALID',
                    'message' => 'This credential is ' . $qrRecord->current_status,
                    'status' => $qrRecord->current_status,
                ];
            }

            // Check expiration
            if ($qrRecord->expires_at && now()->isAfter($qrRecord->expires_at)) {
                return [
                    'valid' => false,
                    'error' => 'QR_EXPIRED',
                    'message' => 'This QR code has expired',
                ];
            }

            // Log the scan
            $this->logQRScan($qrRecord, $decoded, $ipAddress, $location);

            // Return credential information
            $result = [
                'valid' => true,
                'type' => $decoded->type,
                'credential_number' => $decoded->credential_number,
            ];

            if ($decoded->type === 'accreditation') {
                $result = array_merge($result, [
                    'holder_name' => $decoded->holder_name,
                    'holder_id' => $decoded->holder_id,
                    'category' => $decoded->category,
                    'photo_url' => $qrRecord->photo_url,
                ]);
            } else {
                $result = array_merge($result, [
                    'organization_name' => $decoded->organization_name,
                    'organization_type' => $decoded->organization_type,
                ]);
            }

            $result = array_merge($result, [
                'valid_from' => $decoded->valid_from,
                'valid_until' => $decoded->valid_until,
                'status' => $decoded->status,
                'verification_time' => now(),
            ]);

            return $result;

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'SIGNATURE_INVALID',
                'message' => 'QR code signature is invalid. This may be a fake or altered card.',
                'exception' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mark QR code as revoked (fraud, misconduct, etc.)
     */
    public function revokeQRCode(string $qrCodeId, string $reason): void
    {
        $qrRecord = QRCodeRegistry::where('qr_code_id', $qrCodeId)->first();
        
        if ($qrRecord) {
            $qrRecord->update([
                'is_valid' => false,
                'current_status' => 'revoked',
                'status_reason' => $reason,
            ]);
        }
    }

    /**
     * Mark QR as suspended (temporary)
     */
    public function suspendQRCode(string $qrCodeId, string $reason): void
    {
        $qrRecord = QRCodeRegistry::where('qr_code_id', $qrCodeId)->first();
        
        if ($qrRecord) {
            $qrRecord->update([
                'current_status' => 'suspended',
                'status_reason' => $reason,
            ]);
        }
    }

    /**
     * Helper: Generate actual QR code image
     */
    private function generateQRImage(string $data): string
    {
        $qrCode = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 2,
        );

        return (new PngWriter())->write($qrCode)->getDataUri();
    }

    /**
     * Helper: Log QR scan for audit trail
     */
    private function logQRScan(QRCodeRegistry $qrRecord, \stdClass $decoded, ?string $ipAddress, ?string $location): void
    {
        QRScanAuditLog::create([
            'qr_code_id' => $qrRecord->qr_code_id,
            'credential_number' => $qrRecord->credential_number,
            'credential_type' => $qrRecord->credential_type,
            'scanner_ip_address' => $ipAddress,
            'scanner_device' => request()->header('User-Agent'),
            'scanner_location' => $location,
            'holder_name' => $decoded->holder_name ?? $decoded->organization_name ?? 'Unknown',
            'holder_category' => $decoded->category ?? $decoded->organization_type ?? null,
            'current_status' => $qrRecord->current_status,
            'valid_from' => $decoded->valid_from,
            'valid_until' => $decoded->valid_until,
            'photo_url' => $qrRecord->photo_url,
            'qr_signature_valid' => true,
            'credential_exists' => true,
            'credential_active' => $qrRecord->current_status === 'active',
            'scanned_at' => now(),
        ]);
    }

    /**
     * Get QR code history
     */
    public function getQRCodeScanHistory(string $credentialNumber, int $limit = 50): array
    {
        return QRScanAuditLog::where('credential_number', $credentialNumber)
            ->orderByDesc('scanned_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get scan statistics for a credential
     */
    public function getQRCodeScanStatistics(string $credentialNumber): array
    {
        $scans = QRScanAuditLog::where('credential_number', $credentialNumber);

        return [
            'total_scans' => $scans->count(),
            'scans_today' => $scans->whereDate('scanned_at', now())->count(),
            'last_scan' => $scans->latest('scanned_at')->first()?->scanned_at,
            'unique_locations' => $scans->distinct('scanner_location')->count(),
            'unique_ips' => $scans->distinct('scanner_ip_address')->count(),
        ];
    }
}
