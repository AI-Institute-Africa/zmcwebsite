<?php

namespace App\Services;

use App\Models\Application;

class ApplicationCategoryAssignmentService
{
    /**
     * Assign a category to an application based on applicant profile and documents.
     */
    public function assignCategory(Application $application): array
    {
        $matchedRules = [];
        $score = 0;

        // Rule 1: Applicant type + Employment status
        $typeEmploymentRule = $this->checkTypeEmploymentStatus($application);
        if ($typeEmploymentRule['matched']) {
            $matchedRules[] = $typeEmploymentRule;
            $score += $typeEmploymentRule['weight'];
        }

        // Rule 2: Document evidence
        $documentRule = $this->checkDocumentEvidence($application);
        if ($documentRule['matched']) {
            $matchedRules[] = $documentRule;
            $score += $documentRule['weight'];
        }

        // Rule 3: Employment evidence
        $employmentRule = $this->checkEmploymentEvidence($application);
        if ($employmentRule['matched']) {
            $matchedRules[] = $employmentRule;
            $score += $employmentRule['weight'];
        }

        // Determine assigned category
        $assignedCategory = $this->determineCategory(
            $application,
            $matchedRules
        );

        $confidence = count($matchedRules) > 0 ? min($score / (count($matchedRules) * 20) * 100, 100) : 0;

        return [
            'assigned_category' => $assignedCategory,
            'confidence_score' => (int) $confidence,
            'matched_rules' => $matchedRules,
            'missing_data' => $this->identifyMissingData($application),
            'needs_manual_confirmation' => $confidence < 70,
            'details' => [
                'applicant_type' => $application->applicant_type,
                'employment_status' => $application->employment_status,
                'has_employment_letter' => (bool) $application->documents()
                    ->where('doc_type', 'employment_letter')
                    ->exists(),
                'has_past_work_evidence' => !empty($application->past_work_link) || 
                    (bool) $application->documents()
                        ->where('doc_type', 'past_work')
                        ->exists(),
            ],
        ];
    }

    /**
     * Check type + employment status rule.
     */
    private function checkTypeEmploymentStatus(Application $application): array
    {
        $rule = [
            'rule' => 'applicant_type_employment_status',
            'matched' => false,
            'category' => null,
            'weight' => 0,
        ];

        if ($application->applicant_type === 'local') {
            if ($application->employment_status === 'employed') {
                $rule['matched'] = true;
                $rule['category'] = 'local_employed_practitioner';
                $rule['weight'] = 20;
            } elseif ($application->employment_status === 'freelancer') {
                $rule['matched'] = true;
                $rule['category'] = 'local_freelancer_practitioner';
                $rule['weight'] = 20;
            } elseif ($application->employment_status === 'student') {
                $rule['matched'] = true;
                $rule['category'] = 'local_student_practitioner';
                $rule['weight'] = 15;
            } elseif ($application->employment_status === 'unemployed') {
                $rule['matched'] = true;
                $rule['category'] = 'local_aspiring_practitioner';
                $rule['weight'] = 10;
            }
        } elseif ($application->applicant_type === 'international') {
            if ($application->employment_status === 'employed') {
                $rule['matched'] = true;
                $rule['category'] = 'international_employed_practitioner';
                $rule['weight'] = 20;
            } elseif ($application->employment_status === 'freelancer') {
                $rule['matched'] = true;
                $rule['category'] = 'international_freelancer_practitioner';
                $rule['weight'] = 20;
            } else {
                $rule['matched'] = true;
                $rule['category'] = 'international_temporary_practitioner';
                $rule['weight'] = 15;
            }
        }

        return $rule;
    }

    /**
     * Check document evidence rule.
     */
    private function checkDocumentEvidence(Application $application): array
    {
        $rule = [
            'rule' => 'document_evidence',
            'matched' => false,
            'evidence' => [],
            'weight' => 0,
        ];

        $documents = $application->documents()->get();

        // Check for specialized credentials
        if ($documents->where('doc_type', 'graduation_certificate')->isNotEmpty()) {
            $rule['evidence'][] = 'has_graduation_certificate';
            $rule['weight'] += 5;
        }

        if ($documents->where('doc_type', 'employment_letter')->isNotEmpty()) {
            $rule['evidence'][] = 'has_employment_letter';
            $rule['weight'] += 5;
        }

        if ($documents->where('doc_type', 'clearance_letter')->isNotEmpty()) {
            $rule['evidence'][] = 'has_clearance_letter';
            $rule['weight'] += 5;
        }

        $rule['matched'] = count($rule['evidence']) > 0;

        return $rule;
    }

    /**
     * Check employment evidence rule.
     */
    private function checkEmploymentEvidence(Application $application): array
    {
        $rule = [
            'rule' => 'employment_evidence',
            'matched' => false,
            'evidence' => [],
            'weight' => 0,
        ];

        // Check for past work evidence
        if (!empty($application->past_work_link)) {
            $rule['evidence'][] = 'has_past_work_link';
            $rule['weight'] += 5;
        }

        if ($application->documents()
            ->where('doc_type', 'past_work')
            ->exists()) {
            $rule['evidence'][] = 'has_past_work_file';
            $rule['weight'] += 5;
        }

        $rule['matched'] = count($rule['evidence']) > 0;

        return $rule;
    }

    /**
     * Determine the category based on matched rules.
     */
    private function determineCategory(Application $application, array $matchedRules): string
    {
        // Find the most specific category from matched rules
        $categories = [];

        foreach ($matchedRules as $rule) {
            if (!empty($rule['category'])) {
                $categories[] = $rule['category'];
            }
        }

        // Return the first (most specific) category found
        if (count($categories) > 0) {
            return $categories[0];
        }

        // Fallback: determine by type and employment
        if ($application->applicant_type === 'local') {
            return 'local_practitioner';
        } else {
            return 'international_practitioner';
        }
    }

    /**
     * Identify missing data that would improve category confidence.
     */
    private function identifyMissingData(Application $application): array
    {
        $missing = [];

        if (empty($application->past_work_link) && !$application->documents()
            ->where('doc_type', 'past_work')
            ->exists()) {
            $missing[] = 'past_work_evidence';
        }

        if ($application->employment_status === 'employed' && !$application->documents()
            ->where('doc_type', 'employment_letter')
            ->exists()) {
            $missing[] = 'employment_letter';
        }

        if ($application->applicant_type === 'international' && !$application->documents()
            ->where('doc_type', 'clearance_letter')
            ->exists()) {
            $missing[] = 'clearance_letter';
        }

        return $missing;
    }
}
