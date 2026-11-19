<?php

namespace App\Services\Credentials;

use App\Models\Application;
use App\Models\AccreditationNumber;
use App\Models\RegistrationNumber;
use Illuminate\Support\Facades\DB;

/**
 * AutomaticNumberGenerationService
 * Generates accreditation and registration numbers automatically after approval
 * Format: DDMMCCSSYY
 *   DD = Issue day (01-31)
 *   MM = Issue month (01-12)
 *   CC = Category code (2 letters)
 *   SS = Sequence number (3 digits)
 *   YY = Last 2 digits of year
 * 
 * Example format: DDMMCCSSSYY (day, month, category, sequence, two-digit issue year)
 */
class AutomaticNumberGenerationService
{
    // Category codes mapping
    private const CATEGORY_CODES = [
        // Local practitioners
        'full_time_journalist' => 'JF',
        'freelancer_journalist' => 'FJ',
        'local_journalist_foreign_media' => 'LF',
        'stringer_foreign_media' => 'SF',
        'journalist_local_international' => 'JI',
        'content_creator' => 'CC',
        'photographer' => 'PH',
        'pr_digital_practitioner' => 'PR',
        'digital_social_media' => 'DS',
        'other_local' => 'OL',
        
        // Foreign practitioners
        'foreign_correspondent' => 'FC',
        'foreign_photographer' => 'FP',
        'foreign_broadcast' => 'FB',
        'temporary_foreign' => 'TF',
        'other_foreign' => 'OF',
        
        // Media houses / organizations
        'mass_media_service' => 'MS',
        'news_agency' => 'NA',
        'foreign_representative' => 'FR',
        'other_organization' => 'OO',
    ];

    /**
     * Generate accreditation number for approved practitioner
     */
    public function generateAccreditationNumber(Application $application): string
    {
        // Lock to prevent duplicate sequences
        return DB::transaction(function () use ($application) {
            // Get category code
            $categoryCode = $this->getCategoryCode($application);
            
            // Get issue date (today)
            $issueDate = now();
            $day = $issueDate->format('d');
            $month = $issueDate->format('m');
            $year = $issueDate->format('y');
            
            // Use a four-digit random component, retrying on collision.
            do {
                $sequenceNumber = random_int(0, 9999);
                $sequenceStr = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
                $accreditationNumber = strtoupper(substr($categoryCode, 0, 1) . $day . $month . $sequenceStr . $year . substr($categoryCode, -1));
            } while (DB::table('accreditation_numbers')->where('accreditation_number', $accreditationNumber)->exists());
            
            // Format as 3-digit zero-padded sequence
            $sequenceStr = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
            
            // Store in database
            AccreditationNumber::create([
                'application_id' => $application->id,
                'accreditation_number' => $accreditationNumber,
                'format' => 'DDMMCCSSYY',
                'issue_date' => $issueDate->toDateString(),
                'issue_year' => $issueDate->year,
                'category_code' => $categoryCode,
                'sequence_number' => $sequenceNumber,
                'status' => 'active',
                'valid_from' => $issueDate->toDateString(),
                'valid_until' => $this->calculateValidityEnd($application, $issueDate),
                'holder_name' => $application->applicant_name ?? $application->first_name . ' ' . $application->last_name,
                'holder_id_type' => $this->getIDType($application),
                'holder_id_number' => $this->getIDNumber($application),
                'applicant_category' => $application->accreditation_category,
                'qr_code_id' => null, // Will be populated when QR is generated
            ]);
            
            // Update application
            $application->update([
                'accreditation_number' => $accreditationNumber,
                'accreditation_issued_date' => $issueDate,
                'accreditation_valid_from' => $issueDate->toDateString(),
                'accreditation_valid_until' => $this->calculateValidityEnd($application, $issueDate),
            ]);
            
            return $accreditationNumber;
        });
    }

    /**
     * Generate registration number for approved media house
     */
    public function generateRegistrationNumber(Application $application): string
    {
        return DB::transaction(function () use ($application) {
            // Get category code
            $categoryCode = $this->getCategoryCode($application);
            
            // Get issue date (today)
            $issueDate = now();
            $day = $issueDate->format('d');
            $month = $issueDate->format('m');
            $year = $issueDate->format('y');
            
            do {
                $sequenceNumber = random_int(0, 9999);
                $sequenceStr = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
                $registrationNumber = strtoupper(substr($categoryCode, 0, 1) . $day . $month . $sequenceStr . $year . substr($categoryCode, -1));
            } while (DB::table('registration_numbers')->where('registration_number', $registrationNumber)->exists());
            
            // Format sequence
            $sequenceStr = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
            
            // Store in database
            RegistrationNumber::create([
                'application_id' => $application->id,
                'registration_number' => $registrationNumber,
                'format' => 'DDMMCCSSYY',
                'issue_date' => $issueDate->toDateString(),
                'issue_year' => $issueDate->year,
                'category_code' => $categoryCode,
                'sequence_number' => $sequenceNumber,
                'status' => 'active',
                'valid_from' => $issueDate->toDateString(),
                'valid_until' => $this->calculateValidityEnd($application, $issueDate),
                'organization_name' => $application->organization_name,
                'organization_type' => $application->organization_type,
                'qr_code_id' => null,
            ]);
            
            // Update application
            $application->update([
                'registration_number' => $registrationNumber,
                'registration_issued_date' => $issueDate,
                'registration_valid_from' => $issueDate->toDateString(),
                'registration_valid_until' => $this->calculateValidityEnd($application, $issueDate),
            ]);
            
            return $registrationNumber;
        });
    }

    /**
     * Replace a number (e.g., card lost/damaged)
     * - Old number marked as replaced
     * - New number generated with same type/holder but new date
     */
    public function replaceAccreditationNumber(Application $application, string $reason = 'lost'): string
    {
        return DB::transaction(function () use ($application, $reason) {
            $oldAccreditationNumber = $application->accreditation_number;
            
            // Mark old number as replaced
            $oldRecord = AccreditationNumber::where('accreditation_number', $oldAccreditationNumber)->first();
            if ($oldRecord) {
                $newNumber = $this->generateAccreditationNumber($application);
                $oldRecord->update([
                    'status' => 'replaced',
                    'replaced_by' => $newNumber,
                ]);
                
                // Update new record to show what it replaced
                $newRecord = AccreditationNumber::where('accreditation_number', $newNumber)->first();
                if ($newRecord) {
                    $newRecord->update([
                        'replaces' => $oldAccreditationNumber,
                    ]);
                }
            }
            
            return $application->accreditation_number;
        });
    }

    /**
     * Revoke a number (fraudulent, misconduct, etc.)
     */
    public function revokeAccreditationNumber(string $accreditationNumber, string $reason): void
    {
        $record = AccreditationNumber::where('accreditation_number', $accreditationNumber)->first();
        if ($record) {
            $record->update([
                'status' => 'revoked',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
            ]);
        }
    }

    /**
     * Helper: Get category code for application
     */
    private function getCategoryCode(Application $application): string
    {
        $category = $application->accreditation_category ?? $application->organization_type ?? 'other_local';
        
        // Map category to code
        $code = self::CATEGORY_CODES[$category] ?? 'OO';
        
        // Ensure it's 2 letters
        if (strlen($code) !== 2) {
            $code = 'XX';
        }
        
        return $code;
    }

    /**
     * Helper: Get next sequence number
     */
    private function getNextSequenceNumber(string $type, string $categoryCode, string $year): int
    {
        $table = $type === 'accreditation' ? 'accreditation_numbers' : 'registration_numbers';
        
        // Get highest sequence for this category + year
        $maxSequence = DB::table($table)
            ->where('category_code', $categoryCode)
            ->where('issue_year', $year)
            ->max('sequence_number');
        
        return ($maxSequence ?? 0) + 1;
    }

    /**
     * Helper: Calculate validity end date
     */
    private function calculateValidityEnd(Application $application, $issueDate): ?string
    {
        $category = $application->accreditation_category ?? $application->organization_type;
        
        if ($application->application_type === 'registration') {
            return $issueDate->copy()->addYears(5)->toDateString();
        }
        return $issueDate->copy()->endOfYear()->toDateString();
    }

    /**
     * Helper: Get ID type from application
     */
    private function getIDType(Application $application): string
    {
        if ($application->national_id_number) {
            return 'national_id';
        } elseif ($application->passport_number) {
            return 'passport';
        }
        
        return 'unknown';
    }

    /**
     * Helper: Get ID number from application
     */
    private function getIDNumber(Application $application): string
    {
        return $application->national_id_number ?? $application->passport_number ?? '';
    }

    /**
     * Verify number format is valid
     */
    public function isValidAccreditationNumberFormat(string $number): bool
    {
        // Should match: DDMMCCSSYY (12 characters)
        // DD: 01-31
        // MM: 01-12
        // CC: 2 letters
        // SS: 3 digits
        // YY: 2 digits
        
        if (strlen($number) !== 11) {
            return false;
        }
        
        $pattern = '/^(0[1-9]|[12]\d|3[01])(0[1-9]|1[0-2])([A-Z]{2})(\d{3})(\d{2})$/';
        
        return (bool) preg_match($pattern, $number);
    }

    /**
     * Lookup if a number has been reused or invalidated
     */
    public function getNumberStatus(string $accreditationNumber): array
    {
        $record = AccreditationNumber::where('accreditation_number', $accreditationNumber)->first();
        
        if (!$record) {
            return [
                'exists' => false,
                'status' => 'not_found',
            ];
        }
        
        return [
            'exists' => true,
            'status' => $record->status,
            'holder_name' => $record->holder_name,
            'holder_id' => $record->holder_id_number,
            'category' => $record->applicant_category,
            'valid_from' => $record->valid_from,
            'valid_until' => $record->valid_until,
            'issued_date' => $record->issue_date,
            'replaced_by' => $record->replaced_by,
            'replaces' => $record->replaces,
            'cancellation_reason' => $record->cancellation_reason,
        ];
    }
}
