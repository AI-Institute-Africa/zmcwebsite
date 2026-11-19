<?php

namespace App\Services\Validation;

use Illuminate\Support\Str;

class DataNormalizationService
{
    /**
     * Normalize personal names for comparison
     * 
     * @param string $name
     * @return string
     */
    public function normalizeName(string $name): string
    {
        // Convert to uppercase
        $normalized = mb_strtoupper($name, 'UTF-8');
        
        // Remove extra spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);
        
        // Remove titles
        $titles = ['MR', 'MRS', 'MS', 'MISS', 'DR', 'PROF', 'HON', 'REV', 'PROFESSOR', 'DOCTOR'];
        foreach ($titles as $title) {
            // Remove title if it appears at the start followed by space or dot
            $normalized = preg_replace('/^' . preg_quote($title, '/') . '\.?[\s,]*/', '', $normalized);
            // Remove title if it appears anywhere with dots
            $normalized = preg_replace('/\b' . preg_quote($title, '/') . '\./', '', $normalized);
        }
        
        // Standardize punctuation - replace multiple punctuation with single
        $normalized = preg_replace('/[.,;:_-]+/', ' ', $normalized);
        
        // Remove extra spaces again after punctuation removal
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);
        
        return $normalized;
    }
    
    /**
     * Parse a full name into components
     * 
     * @param string $fullName
     * @return array
     */
    public function parseFullName(string $fullName): array
    {
        $normalized = $this->normalizeName($fullName);
        $parts = explode(' ', $normalized);
        
        $result = [
            'first_name' => '',
            'surname' => '',
            'other_names' => [],
            'full_name_normalized' => $normalized
        ];
        
        if (count($parts) >= 2) {
            // Last part is typically surname
            $result['surname'] = array_pop($parts);
            
            // First part is first name
            $result['first_name'] = array_shift($parts);
            
            // Middle parts are other names
            $result['other_names'] = $parts;
        } elseif (count($parts) === 1) {
            // Only one name provided - assume it's surname for Zimbabwe context
            $result['surname'] = $parts[0];
        }
        
        return $result;
    }
    
    /**
     * Compare two names and return match details
     * 
     * @param string $name1
     * @param string $name2
     * @return array
     */
    public function compareNames(string $name1, string $name2): array
    {
        $parsed1 = $this->parseFullName($name1);
        $parsed2 = $this->parseFullName($name2);
        
        $result = [
            'match_status' => 'unknown',
            'match_score' => 0,
            'mismatch_reason' => '',
            'details' => [
                'first_name_match' => false,
                'surname_match' => false,
                'other_names_match' => false,
                'order_difference' => false,
                'minor_mismatch' => false
            ]
        ];
        
        // Compare surnames - must match exactly or with very minor differences
        $surname1 = $this->normalizeName($parsed1['surname']);
        $surname2 = $this->normalizeName($parsed2['surname']);
        
        if ($surname1 === $surname2 && !empty($surname1)) {
            $result['details']['surname_match'] = true;
            $result['match_score'] += 50;
        } else {
            $result['match_status'] = 'hard_stop';
            $result['mismatch_reason'] = 'Surname does not match';
            return $result;
        }
        
        // Compare first names
        $firstName1 = $this->normalizeName($parsed1['first_name']);
        $firstName2 = $this->normalizeName($parsed2['first_name']);
        
        if ($firstName1 === $firstName2 && !empty($firstName1)) {
            $result['details']['first_name_match'] = true;
            $result['match_score'] += 30;
        } else {
            // Check if first name from one appears in other names of the other
            if (in_array($firstName1, $parsed2['other_names']) || in_array($firstName2, $parsed1['other_names'])) {
                $result['details']['order_difference'] = true;
                $result['details']['first_name_match'] = true;
                $result['match_score'] += 20;
                $result['mismatch_reason'] = 'First name order differs but name present';
            } else {
                $result['match_status'] = 'hard_stop';
                $result['mismatch_reason'] = 'First name does not match';
                return $result;
            }
        }
        
        // Compare other names
        $otherNames1 = array_map([$this, 'normalizeName'], $parsed1['other_names']);
        $otherNames2 = array_map([$this, 'normalizeName'], $parsed2['other_names']);
        
        $commonOtherNames = array_intersect($otherNames1, $otherNames2);
        $totalOtherNames = max(count($otherNames1), count($otherNames2));
        
        if ($totalOtherNames > 0) {
            $otherNameMatchRatio = count($commonOtherNames) / $totalOtherNames;
            $result['match_score'] += $otherNameMatchRatio * 20;
            
            if ($otherNameMatchRatio >= 0.8) {
                $result['details']['other_names_match'] = true;
            } elseif ($otherNameMatchRatio >= 0.5) {
                $result['details']['minor_mismatch'] = true;
                $result['mismatch_reason'] = 'Some other names differ';
            }
        } else {
            // No other names to compare
            $result['match_score'] += 10;
        }
        
        // Determine final match status
        if ($result['match_score'] >= 90) {
            $result['match_status'] = 'exact_match';
        } elseif ($result['match_score'] >= 70) {
            $result['match_status'] = 'likely_match';
            if (empty($result['mismatch_reason'])) {
                $result['mismatch_reason'] = 'Minor name differences detected';
            }
        } elseif ($result['match_score'] >= 50) {
            $result['match_status'] = 'manual_review';
            $result['mismatch_reason'] = 'Name match uncertain - manual review required';
        } else {
            $result['match_status'] = 'hard_stop';
            $result['mismatch_reason'] = 'Name does not match sufficiently';
        }
        
        return $result;
    }
    
    /**
     * Normalize organization name
     * 
     * @param string $orgName
     * @return array
     */
    public function normalizeOrganizationName(string $orgName): array
    {
        $normalized = mb_strtoupper($orgName, 'UTF-8');
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);
        
        // Remove legal suffixes for comparison
        $suffixes = [
            'PVT LTD', 'PRIVATE LIMITED', 'LTD', 'PLC', 
            'TRUST', 'ASSOCIATION', 'INC', 'INCORPORATED',
            'CORP', 'CORPORATION', 'LLC', 'CO', 'COMPANY'
        ];
        
        $withoutSuffix = $normalized;
        foreach ($suffixes as $suffix) {
            $withoutSuffix = preg_replace('/\s' . preg_quote($suffix, '/') . '\.?$/i', '', $withoutSuffix);
            $withoutSuffix = preg_replace('/^' . preg_quote($suffix, '/') . '\.?\s/i', '', $withoutSuffix);
        }
        
        $withoutSuffix = trim($withoutSuffix);
        $withoutSuffix = preg_replace('/\s+/', ' ', $withoutSuffix);
        
        return [
            'original' => $orgName,
            'normalized' => $normalized,
            'without_suffix' => $withoutSuffix
        ];
    }
    
    /**
     * Compare organization names
     * 
     * @param string $orgName1
     * @param string $orgName2
     * @return array
     */
    public function compareOrganizationNames(string $orgName1, string $orgName2): array
    {
        $norm1 = $this->normalizeOrganizationName($orgName1);
        $norm2 = $this->normalizeOrganizationName($orgName2);
        
        $result = [
            'match_status' => 'unknown',
            'match_score' => 0,
            'mismatch_reason' => '',
            'details' => []
        ];
        
        // Compare with suffixes
        if ($norm1['normalized'] === $norm2['normalized']) {
            $result['match_score'] = 100;
            $result['match_status'] = 'exact_match';
            return $result;
        }
        
        // Compare without suffixes
        if ($norm1['without_suffix'] === $norm2['without_suffix']) {
            $result['match_score'] = 85;
            $result['match_status'] = 'likely_match';
            $result['mismatch_reason'] = 'Legal suffix difference detected';
            return $result;
        }
        
        // Check if one contains the other (trading name scenario)
        if (str_contains($norm1['without_suffix'], $norm2['without_suffix']) || 
            str_contains($norm2['without_suffix'], $norm1['without_suffix'])) {
            $result['match_score'] = 60;
            $result['match_status'] = 'manual_review';
            $result['mismatch_reason'] = 'Trading name scenario detected - manual review required';
            return $result;
        }
        
        // Calculate similarity
        $similarity = similar_text($norm1['without_suffix'], $norm2['without_suffix'], $percent);
        $result['match_score'] = (int) $percent;
        
        if ($percent >= 80) {
            $result['match_status'] = 'likely_match';
            $result['mismatch_reason'] = 'High similarity but not exact match';
        } elseif ($percent >= 50) {
            $result['match_status'] = 'manual_review';
            $result['mismatch_reason'] = 'Moderate similarity - manual review required';
        } else {
            $result['match_status'] = 'hard_stop';
            $result['mismatch_reason'] = 'Organization names do not match';
        }
        
        return $result;
    }
    
    /**
     * Normalize Zimbabwe National ID
     * Format: 63-123456X70 or similar
     * 
     * @param string $idNumber
     * @return string
     */
    public function normalizeNationalId(string $idNumber): string
    {
        // Remove all spaces
        $normalized = preg_replace('/\s+/', '', $idNumber);
        
        // Preserve existing format but ensure consistency
        // The format should be: XX-XXXXXXX (or similar)
        // We preserve the exact digits and letters as entered
        
        return $normalized;
    }
    
    /**
     * Validate Zimbabwe National ID format
     * 
     * @param string $idNumber
     * @return array
     */
    public function validateNationalId(string $idNumber): array
    {
        $result = [
            'valid' => false,
            'normalized' => '',
            'errors' => []
        ];
        
        $normalized = $this->normalizeNationalId($idNumber);
        $result['normalized'] = $normalized;
        
        // Check if it matches expected pattern (e.g., 63-123456X70 or 63123456X70)
        // Allow both with and without hyphen
        $pattern = '/^(\d{2})-?(\d{6,7}[A-Za-z]\d{1,2})$/';
        
        if (!preg_match($pattern, $normalized, $matches)) {
            $result['errors'][] = 'ID format does not match expected pattern (e.g., 63-123456X70)';
            return $result;
        }
        
        $result['valid'] = true;
        
        // Extract components
        $result['district_code'] = $matches[1];
        $result['serial_number'] = $matches[2];
        
        return $result;
    }
    
    /**
     * Normalize passport number
     * 
     * @param string $passportNumber
     * @return string
     */
    public function normalizePassportNumber(string $passportNumber): string
    {
        // Remove spaces
        $normalized = preg_replace('/\s+/', '', $passportNumber);
        
        // Convert to uppercase
        $normalized = mb_strtoupper($normalized, 'UTF-8');
        
        return $normalized;
    }
    
    /**
     * Validate passport number
     * 
     * @param string $passportNumber
     * @return array
     */
    public function validatePassportNumber(string $passportNumber): array
    {
        $result = [
            'valid' => false,
            'normalized' => '',
            'errors' => []
        ];
        
        $normalized = $this->normalizePassportNumber($passportNumber);
        $result['normalized'] = $normalized;
        
        // Passport numbers vary by country but typically are alphanumeric
        // Length is usually 6-9 characters
        if (strlen($normalized) < 6 || strlen($normalized) > 15) {
            $result['errors'][] = 'Passport number length appears invalid';
        }
        
        if (!preg_match('/^[A-Z0-9]+$/', $normalized)) {
            $result['errors'][] = 'Passport number contains invalid characters';
        } else {
            $result['valid'] = true;
        }
        
        return $result;
    }
    
    /**
     * Normalize phone number
     * 
     * @param string $phone
     * @return string
     */
    public function normalizePhone(string $phone): string
    {
        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phone);
        
        return $normalized;
    }
    
    /**
     * Normalize email address
     * 
     * @param string $email
     * @return string
     */
    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }
    
    /**
     * Compare two ID numbers
     * 
     * @param string $id1
     * @param string $id2
     * @param string $type 'national_id' or 'passport'
     * @return array
     */
    public function compareIds(string $id1, string $id2, string $type = 'national_id'): array
    {
        $result = [
            'match' => false,
            'match_status' => 'unknown',
            'mismatch_reason' => ''
        ];
        
        if ($type === 'national_id') {
            $norm1 = $this->normalizeNationalId($id1);
            $norm2 = $this->normalizeNationalId($id2);
        } else {
            $norm1 = $this->normalizePassportNumber($id1);
            $norm2 = $this->normalizePassportNumber($id2);
        }
        
        if ($norm1 === $norm2) {
            $result['match'] = true;
            $result['match_status'] = 'exact_match';
        } else {
            $result['match_status'] = 'hard_stop';
            $result['mismatch_reason'] = 'ID numbers do not match';
        }
        
        return $result;
    }
}