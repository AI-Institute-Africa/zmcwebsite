<?php

namespace App\Services\Validation;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Support\Str;

/**
 * NameNormalizerService
 * Normalizes personal and organization names for consistent matching
 */
class NameNormalizerService
{
    private const TITLES = ['MR', 'MRS', 'MS', 'MISS', 'DR', 'PROF', 'PROFESSOR', 'DOCTOR', 'REVEREND', 'SIR', 'MADAM'];

    /**
     * Normalize a personal name for comparison
     */
    public function normalizePersonalName(string $name): array
    {
        // Convert to uppercase
        $name = mb_strtoupper($name);
        
        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        // Remove titles
        foreach (self::TITLES as $title) {
            $name = preg_replace('/^\s*' . $title . '\s+/i', '', $name);
        }
        
        // Standardize punctuation
        $name = str_replace(['á', 'à', 'ä'], 'a', $name);
        $name = str_replace(['é', 'è', 'ë'], 'e', $name);
        $name = str_replace(['í', 'ì', 'ï'], 'i', $name);
        $name = str_replace(['ó', 'ò', 'ö'], 'o', $name);
        $name = str_replace(['ú', 'ù', 'ü'], 'u', $name);
        
        // Split into components
        $parts = explode(' ', trim($name));
        
        // Build normalized result
        return [
            'full_name' => trim($name),
            'surname' => $parts[0] ?? '',
            'first_name' => $parts[1] ?? '',
            'other_names' => implode(' ', array_slice($parts, 2)) ?? '',
            'parts_count' => count($parts),
            'normalized_string' => str_replace(' ', '', $name),
        ];
    }

    /**
     * Normalize organization name for comparison
     */
    public function normalizeOrganizationName(string $name, bool $removeLegalSuffixes = true): array
    {
        // Convert to uppercase
        $name = mb_strtoupper($name);
        
        // Remove extra spaces
        $name = preg_replace('/\s+/', ' ', trim($name));
        
        $original = $name;
        
        // Remove legal suffixes if requested
        if ($removeLegalSuffixes) {
            $suffixes = ['(PVT) LTD', 'PVT LTD', 'PRIVATE LIMITED', 'LIMITED', 'LTD', 'PLC', 'TRUST', 'ASSOCIATION', '(PTY) LTD', '(EON)'];
            foreach ($suffixes as $suffix) {
                $name = preg_replace('/\s*' . preg_quote($suffix) . '\s*$/', '', $name);
            }
        }
        
        // Standardize accents
        $name = str_replace(['á', 'à', 'ä'], 'a', $name);
        $name = str_replace(['é', 'è', 'ë'], 'e', $name);
        $name = str_replace(['í', 'ì', 'ï'], 'i', $name);
        $name = str_replace(['ó', 'ò', 'ö'], 'o', $name);
        $name = str_replace(['ú', 'ù', 'ü'], 'u', $name);
        
        return [
            'original_name' => $original,
            'normalized_name' => trim($name),
            'normalized_string' => str_replace(' ', '', trim($name)),
            'legal_suffix_removed' => $removeLegalSuffixes && ($original !== trim($name)),
        ];
    }

    /**
     * Calculate similarity score between two normalized names (0-100)
     */
    public function calculateSimilarityScore(string $name1, string $name2): int
    {
        // Exact match
        if ($name1 === $name2) {
            return 100;
        }
        
        // Levenshtein distance
        $distance = levenshtein($name1, $name2);
        $maxLength = max(strlen($name1), strlen($name2));
        
        if ($maxLength === 0) {
            return 100;
        }
        
        $similarity = 100 - (($distance / $maxLength) * 100);
        
        return max(0, (int) $similarity);
    }

    /**
     * Check if names are substantially equivalent (accounting for name reordering, middle names, etc.)
     */
    public function areNamesEquivalent(array $name1, array $name2, int $threshold = 85): bool
    {
        // If both have surname and first name, check those
        if (!empty($name1['surname']) && !empty($name2['surname'])) {
            $surnameSimilarity = $this->calculateSimilarityScore($name1['surname'], $name2['surname']);
            $firstNameSimilarity = $this->calculateSimilarityScore($name1['first_name'], $name2['first_name']);
            
            // Both surname and first name must match above threshold
            return $surnameSimilarity >= $threshold && $firstNameSimilarity >= $threshold;
        }
        
        // Fall back to full name comparison
        return $this->calculateSimilarityScore($name1['full_name'], $name2['full_name']) >= $threshold;
    }

    /**
     * Identify why names don't match
     */
    public function analyzeNameMismatch(array $enteredName, array $documentName): array
    {
        $reasons = [];
        
        // Surname mismatch
        if ($enteredName['surname'] !== $documentName['surname']) {
            $similarity = $this->calculateSimilarityScore($enteredName['surname'], $documentName['surname']);
            $reasons[] = [
                'type' => 'surname_mismatch',
                'entered' => $enteredName['surname'],
                'document' => $documentName['surname'],
                'similarity' => $similarity,
            ];
        }
        
        // First name mismatch
        if ($enteredName['first_name'] !== $documentName['first_name']) {
            $similarity = $this->calculateSimilarityScore($enteredName['first_name'], $documentName['first_name']);
            $reasons[] = [
                'type' => 'first_name_mismatch',
                'entered' => $enteredName['first_name'],
                'document' => $documentName['first_name'],
                'similarity' => $similarity,
            ];
        }
        
        // Name reordering (surname vs first name swapped)
        if (($enteredName['surname'] === $documentName['first_name']) && ($enteredName['first_name'] === $documentName['surname'])) {
            $reasons[] = [
                'type' => 'name_reordering',
                'details' => 'Surname and first name appear to be swapped',
            ];
        }
        
        // Middle names missing
        if (!empty($documentName['other_names']) && empty($enteredName['other_names'])) {
            $reasons[] = [
                'type' => 'missing_middle_names',
                'document_middle_names' => $documentName['other_names'],
            ];
        }
        
        // Part of name appears to be initials only
        if (strlen($enteredName['first_name']) <= 2 && strlen($documentName['first_name']) > 2) {
            $reasons[] = [
                'type' => 'initials_vs_full_name',
                'entered' => $enteredName['first_name'],
                'document' => $documentName['first_name'],
            ];
        }
        
        return $reasons;
    }
}
