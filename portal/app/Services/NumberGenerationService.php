<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NumberGenerationService
{
    /**
     * Category codes for different application types
     */
    private const CATEGORY_CODES = [
        // Local media practitioner categories
        'JF' => 'Full-time Local Journalist',
        'JL' => 'Local Freelancer',
        'JG' => 'Local Stringer',
        'JD' => 'Digital/Social Media Practitioner',
        'JC' => 'Content Creator',
        'JP' => 'Photographer',
        'JR' => 'PR/Digital Media',
        
        // Foreign media practitioner categories
        'FF' => 'Foreign Correspondent',
        'FS' => 'Foreign Stringer',
        'FT' => 'Temporary Foreign Journalist',
        'FR' => 'Foreign Reporter',
        
        // Media house categories
        'MP' => 'Mass Media Service - Print',
        'ME' => 'Mass Media Service - Electronic',
        'MB' => 'Mass Media Service - Both',
        
        // News agency
        'NA' => 'News Agency',
        
        // Foreign representative office
        'FO' => 'Foreign Representative Office',
        
        // Special codes
        'RN' => 'Renewal',
        'RP' => 'Replacement'
    ];
    
    /**
     * Generate accreditation number
     * 
     * Format: DDMM + CATEGORY_CODE + SEQUENCE + YY
     * Example: 2811JF23425
     * 
     * @param string $category
     * @param string $applicationType
     * @return array
     */
    public function generateAccreditationNumber(string $category, string $applicationType): array
    {
        $result = [
            'success' => false,
            'number' => null,
            'components' => [],
            'errors' => []
        ];
        
        try {
            // Validate category code
            if (!array_key_exists($category, self::CATEGORY_CODES)) {
                $result['errors'][] = 'Invalid category code';
                return $result;
            }
            
            // Get date components
            $dateComponents = $this->getDateComponents();
            
            // Get sequence number
            $sequence = $this->getNextSequence($category, $applicationType);
            
            // Get year component
            $yearComponent = date('y');
            
            // Assemble number
            $number = $dateComponents . $category . $sequence . $yearComponent;
            
            // Store components for reference
            $result['components'] = [
                'date' => $dateComponents,
                'category_code' => $category,
                'category_description' => self::CATEGORY_CODES[$category],
                'sequence' => $sequence,
                'year' => $yearComponent
            ];
            
            // Verify number doesn't already exist
            if ($this->numberExists($number, 'accreditation')) {
                $result['errors'][] = 'Generated number already exists - retrying';
                // Retry once with new sequence
                $sequence = $this->getNextSequence($category, $applicationType, true);
                $number = $dateComponents . $category . $sequence . $yearComponent;
                $result['components']['sequence'] = $sequence;
                
                if ($this->numberExists($number, 'accreditation')) {
                    $result['errors'][] = 'Unable to generate unique number';
                    return $result;
                }
            }
            
            $result['number'] = $number;
            $result['success'] = true;
            
            Log::info('Accreditation number generated', [
                'number' => $number,
                'category' => $category,
                'application_type' => $applicationType
            ]);
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Number generation failed: ' . $e->getMessage();
            Log::error('Accreditation number generation failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Generate registration number
     * 
     * Format: DDMM + CATEGORY_CODE + SEQUENCE + YY
     * 
     * @param string $category
     * @param string $applicationType
     * @return array
     */
    public function generateRegistrationNumber(string $category, string $applicationType): array
    {
        $result = [
            'success' => false,
            'number' => null,
            'components' => [],
            'errors' => []
        ];
        
        try {
            // Validate category code
            if (!array_key_exists($category, self::CATEGORY_CODES)) {
                $result['errors'][] = 'Invalid category code';
                return $result;
            }
            
            // Get date components
            $dateComponents = $this->getDateComponents();
            
            // Get sequence number (registration numbers use separate sequence)
            $sequence = $this->getNextSequence($category, $applicationType, false, 'registration');
            
            // Get year component
            $yearComponent = date('y');
            
            // Assemble number
            $number = $dateComponents . $category . $sequence . $yearComponent;
            
            // Store components for reference
            $result['components'] = [
                'date' => $dateComponents,
                'category_code' => $category,
                'category_description' => self::CATEGORY_CODES[$category],
                'sequence' => $sequence,
                'year' => $yearComponent
            ];
            
            // Verify number doesn't already exist
            if ($this->numberExists($number, 'registration')) {
                $result['errors'][] = 'Generated number already exists - retrying';
                $sequence = $this->getNextSequence($category, $applicationType, true, 'registration');
                $number = $dateComponents . $category . $sequence . $yearComponent;
                $result['components']['sequence'] = $sequence;
                
                if ($this->numberExists($number, 'registration')) {
                    $result['errors'][] = 'Unable to generate unique number';
                    return $result;
                }
            }
            
            $result['number'] = $number;
            $result['success'] = true;
            
            Log::info('Registration number generated', [
                'number' => $number,
                'category' => $category,
                'application_type' => $applicationType
            ]);
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Number generation failed: ' . $e->getMessage();
            Log::error('Registration number generation failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get date components (DDMM)
     * 
     * @return string
     */
    private function getDateComponents(): string
    {
        return date('dm');
    }
    
    /**
     * Get next sequence number
     * 
     * @param string $category
     * @param string $applicationType
     * @param bool $forceIncrement
     * @param string $numberType
     * @return string
     */
    private function getNextSequence(string $category, string $applicationType, bool $forceIncrement = false, string $numberType = 'accreditation'): string
    {
        $tableName = $numberType === 'accreditation' ? 'accreditation_records' : 'registration_records';
        $numberField = $numberType === 'accreditation' ? 'accreditation_number' : 'registration_number';
        
        // Lock the sequence row to prevent race conditions
        DB::beginTransaction();
        
        try {
            // Get or create sequence record
            $sequenceRecord = DB::table('number_sequences')
                ->where('category', $category)
                ->where('application_type', $applicationType)
                ->where('number_type', $numberType)
                ->lockForUpdate()
                ->first();
            
            if (!$sequenceRecord) {
                // Create new sequence record starting at 1
                DB::table('number_sequences')->insert([
                    'category' => $category,
                    'application_type' => $applicationType,
                    'number_type' => $numberType,
                    'current_sequence' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $sequence = 1;
            } else {
                if ($forceIncrement) {
                    $sequence = $sequenceRecord->current_sequence + 1;
                    DB::table('number_sequences')
                        ->where('id', $sequenceRecord->id)
                        ->update([
                            'current_sequence' => $sequence,
                            'updated_at' => now()
                        ]);
                } else {
                    $sequence = $sequenceRecord->current_sequence;
                    
                    // Increment for next use
                    DB::table('number_sequences')
                        ->where('id', $sequenceRecord->id)
                        ->update([
                            'current_sequence' => $sequence + 1,
                            'updated_at' => now()
                        ]);
                }
            }
            
            DB::commit();
            
            // Format as 3-digit with leading zeros
            return str_pad($sequence, 3, '0', STR_PAD_LEFT);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sequence generation failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Check if number already exists
     * 
     * @param string $number
     * @param string $type
     * @return bool
     */
    private function numberExists(string $number, string $type): bool
    {
        $tableName = $type === 'accreditation' ? 'accreditation_records' : 'registration_records';
        $numberField = $type === 'accreditation' ? 'accreditation_number' : 'registration_number';
        
        return DB::table($tableName)
            ->where($numberField, $number)
            ->exists();
    }
    
    /**
     * Validate number format
     * 
     * @param string $number
     * @param string $type
     * @return array
     */
    public function validateNumberFormat(string $number, string $type): array
    {
        $result = [
            'valid' => false,
            'errors' => [],
            'components' => null
        ];
        
        // Check basic format (DDMM + 2-letter category + 3-digit sequence + 2-digit year)
        $pattern = '/^\d{4}[A-Z]{2}\d{3}\d{2}$/';
        
        if (!preg_match($pattern, $number)) {
            $result['errors'][] = 'Invalid number format. Expected format: DDMM + CC + NNN + YY';
            return $result;
        }
        
        // Parse components
        $components = [
            'date' => substr($number, 0, 4),
            'category_code' => substr($number, 4, 2),
            'sequence' => substr($number, 6, 3),
            'year' => substr($number, 9, 2)
        ];
        
        // Validate category code
        if (!array_key_exists($components['category_code'], self::CATEGORY_CODES)) {
            $result['errors'][] = 'Invalid category code: ' . $components['category_code'];
        }
        
        // Validate date component (should be valid day/month)
        $day = substr($components['date'], 0, 2);
        $month = substr($components['date'], 2, 2);
        
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            $result['errors'][] = 'Invalid date component in number';
        }
        
        // Validate sequence is numeric
        if (!is_numeric($components['sequence'])) {
            $result['errors'][] = 'Invalid sequence component';
        }
        
        // Validate year component
        $expectedYear = substr(date('Y'), 2, 2);
        if ($components['year'] !== $expectedYear) {
            $result['errors'][] = 'Number year component does not match current year';
        }
        
        if (empty($result['errors'])) {
            $result['valid'] = true;
            $result['components'] = $components;
        }
        
        return $result;
    }
    
    /**
     * Parse number and return information
     * 
     * @param string $number
     * @param string $type
     * @return array
     */
    public function parseNumber(string $number, string $type): array
    {
        $validation = $this->validateNumberFormat($number, $type);
        
        if (!$validation['valid']) {
            return [
                'valid' => false,
                'errors' => $validation['errors']
            ];
        }
        
        $components = $validation['components'];
        
        return [
            'valid' => true,
            'number' => $number,
            'type' => $type,
            'issue_date' => $this->parseDateComponent($components['date']),
            'category_code' => $components['category_code'],
            'category_description' => self::CATEGORY_CODES[$components['category_code']],
            'sequence' => (int) $components['sequence'],
            'year' => '20' . $components['year']
        ];
    }
    
    /**
     * Parse date component (DDMM) to full date
     * 
     * @param string $dateComponent
     * @return string
     */
    private function parseDateComponent(string $dateComponent): string
    {
        $day = substr($dateComponent, 0, 2);
        $month = substr($dateComponent, 2, 2);
        $year = date('Y');
        
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    
    /**
     * Get category code from description
     * 
     * @param string $description
     * @return string|null
     */
    public function getCategoryCodeFromDescription(string $description): ?string
    {
        foreach (self::CATEGORY_CODES as $code => $desc) {
            if (stripos($desc, $description) !== false || stripos($description, $desc) !== false) {
                return $code;
            }
        }
        
        return null;
    }
    
    /**
     * Get all category codes
     * 
     * @return array
     */
    public function getAllCategoryCodes(): array
    {
        return self::CATEGORY_CODES;
    }
    
    /**
     * Reserve number (for future use or migration)
     * 
     * @param string $number
     * @param string $type
     * @param string $reason
     * @return array
     */
    public function reserveNumber(string $number, string $type, string $reason): array
    {
        $result = [
            'success' => false,
            'errors' => []
        ];
        
        try {
            // Validate format
            $validation = $this->validateNumberFormat($number, $type);
            if (!$validation['valid']) {
                $result['errors'] = $validation['errors'];
                return $result;
            }
            
            // Check if already exists
            if ($this->numberExists($number, $type)) {
                $result['errors'][] = 'Number already exists';
                return $result;
            }
            
            // Reserve number
            DB::table('reserved_numbers')->insert([
                'number' => $number,
                'type' => $type,
                'reason' => $reason,
                'reserved_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $result['success'] = true;
            
            Log::info('Number reserved', [
                'number' => $number,
                'type' => $type,
                'reason' => $reason
            ]);
            
        } catch (\Exception $e) {
            $result['errors'][] = 'Number reservation failed: ' . $e->getMessage();
            Log::error('Number reservation failed', [
                'number' => $number,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }
    
    /**
     * Check if number is reserved
     * 
     * @param string $number
     * @return bool
     */
    public function isNumberReserved(string $number): bool
    {
        return DB::table('reserved_numbers')
            ->where('number', $number)
            ->exists();
    }
    
    /**
     * Generate numbers for legacy migration
     * 
     * @param array $legacyRecords
     * @return array
     */
    public function generateMigrationNumbers(array $legacyRecords): array
    {
        $results = [];
        
        foreach ($legacyRecords as $record) {
            $result = match ($record['type']) {
                'accreditation' => $this->generateAccreditationNumber($record['category'], 'migration'),
                'registration' => $this->generateRegistrationNumber($record['category'], 'migration'),
                default => ['success' => false, 'errors' => ['Invalid type']]
            };
            
            $results[] = [
                'legacy_id' => $record['id'] ?? null,
                'generated_number' => $result['number'] ?? null,
                'success' => $result['success'],
                'errors' => $result['errors'] ?? []
            ];
        }
        
        return $results;
    }
    
    /**
     * Generate unique reference number for applications
     * 
     * @param string $applicationType
     * @return string
     */
    public function generateApplicationReference(string $applicationType): string
    {
        $prefix = match ($applicationType) {
            'accreditation' => 'ACC',
            'foreign_accreditation' => 'FAC',
            'media_house' => 'MH',
            'news_agency' => 'NA',
            'ap4' => 'AP4',
            'renewal' => 'REN',
            'replacement' => 'REP',
            'levy' => 'LEV',
            default => 'APP'
        };
        
        $timestamp = date('YmdHis');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
        
        return $prefix . $timestamp . $random;
    }
}