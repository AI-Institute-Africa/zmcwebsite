<?php

namespace App\Services\FraudDetection;

use App\Models\Application;
use App\Models\FraudRiskScore;
use App\Models\HardStopError;
use App\Models\SoftWarning;
use App\Models\DuplicateApplicationFlag;

/**
 * RiskScoringEngine
 * Calculates overall fraud/risk score for applications
 * Routes applications based on risk level: auto-approve, normal review, officer review, senior review, block
 * Risk score ranges: 0-20 low, 21-50 medium, 51-75 high, 76-100 critical
 */
class RiskScoringEngine
{
    // Risk factors and their weights (total should not exceed 100)
    private const RISK_WEIGHTS = [
        'name_mismatch' => 15,
        'id_mismatch' => 20,
        'document_tampering' => 25,
        'duplicate_document' => 18,
        'duplicate_identity' => 20,
        'unregistered_employer' => 12,
        'company_name_mismatch' => 15,
        'director_mismatch' => 12,
        'payment_mismatch' => 10,
        'prior_rejection' => 20,
        'prior_revocation' => 25,
        'multiple_applications_same_device' => 15,
        'low_ocr_confidence' => 8,
        'suspicious_upload_pattern' => 10,
        'foreign_assignment_inconsistency' => 12,
        'levy_turnover_mismatch' => 12,
        'officer_override_request' => 10,
        'suspicious_address' => 8,
        'suspicious_contact' => 8,
        'reused_documents' => 18,
    ];

    /**
     * Calculate overall risk score for an application
     */
    public function calculateRiskScore(Application $application): array
    {
        $riskFactors = [];
        $categoryScores = [
            'identity_risk_score' => 0,
            'document_risk_score' => 0,
            'duplicate_risk_score' => 0,
            'employment_risk_score' => 0,
            'company_risk_score' => 0,
            'payment_risk_score' => 0,
            'insider_risk_score' => 0,
        ];

        // 1. Check for hard-stop errors (highest risk)
        $hardStopErrors = HardStopError::where('application_id', $application->id)
            ->where('status', '!=', 'resolved')
            ->count();

        if ($hardStopErrors > 0) {
            $riskFactors[] = [
                'factor' => 'hard_stop_errors',
                'value' => $hardStopErrors,
                'weight' => 30, // Highest weight
                'reason' => "$hardStopErrors unresolved hard-stop errors",
            ];
        }

        // 2. Identity risk factors
        $identityRisk = $this->assessIdentityRisk($application);
        $categoryScores['identity_risk_score'] = $identityRisk['score'];
        $riskFactors = array_merge($riskFactors, $identityRisk['factors']);

        // 3. Document risk factors
        $documentRisk = $this->assessDocumentRisk($application);
        $categoryScores['document_risk_score'] = $documentRisk['score'];
        $riskFactors = array_merge($riskFactors, $documentRisk['factors']);

        // 4. Duplicate risk factors
        $duplicateRisk = $this->assessDuplicateRisk($application);
        $categoryScores['duplicate_risk_score'] = $duplicateRisk['score'];
        $riskFactors = array_merge($riskFactors, $duplicateRisk['factors']);

        // 5. Employment risk factors (if applicable)
        $employmentRisk = $this->assessEmploymentRisk($application);
        $categoryScores['employment_risk_score'] = $employmentRisk['score'];
        $riskFactors = array_merge($riskFactors, $employmentRisk['factors']);

        // 6. Company risk factors (if media house)
        $companyRisk = $this->assessCompanyRisk($application);
        $categoryScores['company_risk_score'] = $companyRisk['score'];
        $riskFactors = array_merge($riskFactors, $companyRisk['factors']);

        // 7. Payment risk factors
        $paymentRisk = $this->assessPaymentRisk($application);
        $categoryScores['payment_risk_score'] = $paymentRisk['score'];
        $riskFactors = array_merge($riskFactors, $paymentRisk['factors']);

        // 8. Insider risk factors
        $insiderRisk = $this->assessInsiderRisk($application);
        $categoryScores['insider_risk_score'] = $insiderRisk['score'];
        $riskFactors = array_merge($riskFactors, $insiderRisk['factors']);

        // Calculate overall score
        $overallScore = $this->aggregateRiskScore($categoryScores);

        // Determine risk level and routing
        $riskLevel = $this->determineRiskLevel($overallScore);
        $routingDecision = $this->determineRoutingDecision($overallScore, $hardStopErrors);

        return [
            'overall_score' => $overallScore,
            'risk_level' => $riskLevel,
            'routing_decision' => $routingDecision,
            'category_scores' => $categoryScores,
            'risk_factors' => $riskFactors,
            'total_flags' => count($riskFactors),
            'recommendation' => $this->getRecommendation($riskLevel, $routingDecision),
        ];
    }

    /**
     * Assess identity-related risks
     */
    private function assessIdentityRisk(Application $application): array
    {
        $factors = [];
        $score = 0;

        // Check for name/ID mismatches (from name_matching_results table)
        $nameMatch = \DB::table('name_matching_results')
            ->where('application_id', $application->id)
            ->first();

        if ($nameMatch && $nameMatch->validation_status === 'hard_stop') {
            $factors[] = [
                'factor' => 'name_mismatch',
                'weight' => self::RISK_WEIGHTS['name_mismatch'],
                'reason' => $nameMatch->validation_message,
            ];
            $score += self::RISK_WEIGHTS['name_mismatch'];
        }

        // Check for ID validation issues
        $idValidation = \DB::table('id_validation_results')
            ->where('application_id', $application->id)
            ->first();

        if ($idValidation && $idValidation->validation_status === 'hard_stop') {
            $factors[] = [
                'factor' => 'id_mismatch',
                'weight' => self::RISK_WEIGHTS['id_mismatch'],
                'reason' => $idValidation->validation_message,
            ];
            $score += self::RISK_WEIGHTS['id_mismatch'];
        }

        return ['score' => $score, 'factors' => $factors];
    }

    /**
     * Assess document-related risks
     */
    private function assessDocumentRisk(Application $application): array
    {
        $factors = [];
        $score = 0;

        // Check for document tampering flags
        $tamperedDocs = \DB::table('document_tampering_flags')
            ->where('application_id', $application->id)
            ->where(function ($query) {
                $query->where('appears_altered', true)
                      ->orWhere('recommendation', 'reject')
                      ->orWhere('low_ocr_confidence', true);
            })
            ->count();

        if ($tamperedDocs > 0) {
            $factors[] = [
                'factor' => 'document_tampering',
                'weight' => self::RISK_WEIGHTS['document_tampering'],
                'reason' => "$tamperedDocs documents show signs of tampering or quality issues",
            ];
            $score += self::RISK_WEIGHTS['document_tampering'];
        }

        // Check for low OCR confidence
        $lowOCRDocs = \DB::table('document_extraction_data')
            ->where('application_id', $application->id)
            ->where('ocr_confidence', '<', 70)
            ->count();

        if ($lowOCRDocs > 0) {
            $factors[] = [
                'factor' => 'low_ocr_confidence',
                'weight' => self::RISK_WEIGHTS['low_ocr_confidence'] * $lowOCRDocs,
                'reason' => "$lowOCRDocs documents have OCR confidence below 70%",
            ];
            $score += self::RISK_WEIGHTS['low_ocr_confidence'] * min($lowOCRDocs, 3); // Cap multiplier at 3
        }

        return ['score' => min($score, 30), 'factors' => $factors]; // Cap at 30 for this category
    }

    /**
     * Assess duplicate/fraud pattern risks
     */
    private function assessDuplicateRisk(Application $application): array
    {
        $factors = [];
        $score = 0;

        // Check for flagged duplicates
        $duplicateFlags = DuplicateApplicationFlag::where('application_id', $application->id)
            ->where('flag_severity', '!=', 'low')
            ->count();

        if ($duplicateFlags > 0) {
            $factors[] = [
                'factor' => 'duplicate_identity',
                'weight' => self::RISK_WEIGHTS['duplicate_identity'],
                'reason' => "$duplicateFlags potential duplicate applications detected",
            ];
            $score += self::RISK_WEIGHTS['duplicate_identity'];
        }

        // Check for reused documents
        $reusedDocs = \DB::table('reused_document_tracking')
            ->where('application_id', $application->id)
            ->where('suspicious_reuse_detected', true)
            ->count();

        if ($reusedDocs > 0) {
            $factors[] = [
                'factor' => 'reused_documents',
                'weight' => self::RISK_WEIGHTS['reused_documents'],
                'reason' => "$reusedDocs documents appear to be reused from other applications",
            ];
            $score += self::RISK_WEIGHTS['reused_documents'];
        }

        return ['score' => min($score, 40), 'factors' => $factors];
    }

    /**
     * Assess employment-related risks
     */
    private function assessEmploymentRisk(Application $application): array
    {
        $factors = [];
        $score = 0;

        // Check for unregistered employer
        if ($application->employer_name && !$this->isEmployerRegistered($application->employer_name)) {
            $factors[] = [
                'factor' => 'unregistered_employer',
                'weight' => self::RISK_WEIGHTS['unregistered_employer'],
                'reason' => "Employer '{$application->employer_name}' is not registered in system",
            ];
            $score += self::RISK_WEIGHTS['unregistered_employer'];
        }

        return ['score' => $score, 'factors' => $factors];
    }

    /**
     * Assess company-related risks (for media house applications)
     */
    private function assessCompanyRisk(Application $application): array
    {
        $factors = [];
        $score = 0;

        if ($application->application_type !== 'media_house') {
            return ['score' => 0, 'factors' => []];
        }

        // Check for company name mismatches
        $companyMatches = \DB::table('organization_name_mapping')
            ->where('application_id', $application->id)
            ->where('name_difference_verified', false)
            ->count();

        if ($companyMatches > 0) {
            $factors[] = [
                'factor' => 'company_name_mismatch',
                'weight' => self::RISK_WEIGHTS['company_name_mismatch'],
                'reason' => "Company name inconsistencies detected across documents",
            ];
            $score += self::RISK_WEIGHTS['company_name_mismatch'];
        }

        // Check for director mismatches
        $directorIssues = \DB::table('director_cross_reference')
            ->where('application_id', $application->id)
            ->where('flagged_for_review', true)
            ->count();

        if ($directorIssues > 0) {
            $factors[] = [
                'factor' => 'director_mismatch',
                'weight' => self::RISK_WEIGHTS['director_mismatch'],
                'reason' => "$directorIssues director validation issues flagged",
            ];
            $score += self::RISK_WEIGHTS['director_mismatch'];
        }

        return ['score' => min($score, 30), 'factors' => $factors];
    }

    /**
     * Assess payment-related risks
     */
    private function assessPaymentRisk(Application $application): array
    {
        $factors = [];
        $score = 0;

        // Check for payment status issues
        $paymentRecord = \DB::table('payment_verification_records')
            ->where('application_id', $application->id)
            ->first();

        if ($paymentRecord && $paymentRecord->is_duplicate_payment) {
            $factors[] = [
                'factor' => 'payment_mismatch',
                'weight' => self::RISK_WEIGHTS['payment_mismatch'],
                'reason' => 'Duplicate payment detected',
            ];
            $score += self::RISK_WEIGHTS['payment_mismatch'];
        }

        return ['score' => $score, 'factors' => $factors];
    }

    /**
     * Assess insider-related risks
     */
    private function assessInsiderRisk(Application $application): array
    {
        $factors = [];
        $score = 0;

        // Check for officer overrides on this application
        $overrides = \DB::table('officer_overrides')
            ->where('application_id', $application->id)
            ->where('override_status', 'approved')
            ->count();

        if ($overrides > 0) {
            $factors[] = [
                'factor' => 'officer_override',
                'weight' => self::RISK_WEIGHTS['officer_override_request'],
                'reason' => "$overrides validation(s) bypassed by officers",
            ];
            $score += self::RISK_WEIGHTS['officer_override_request'];
        }

        return ['score' => $score, 'factors' => $factors];
    }

    /**
     * Aggregate category scores into overall score
     */
    private function aggregateRiskScore(array $categoryScores): int
    {
        // Weighted average of category scores
        $weights = [
            'identity_risk_score' => 0.25,
            'document_risk_score' => 0.20,
            'duplicate_risk_score' => 0.25,
            'employment_risk_score' => 0.10,
            'company_risk_score' => 0.10,
            'payment_risk_score' => 0.05,
            'insider_risk_score' => 0.05,
        ];

        $total = 0;
        foreach ($categoryScores as $category => $score) {
            $total += ($score * ($weights[$category] ?? 0));
        }

        return (int) min($total, 100); // Cap at 100
    }

    /**
     * Determine risk level based on score
     */
    private function determineRiskLevel(int $score): string
    {
        if ($score <= 20) {
            return 'low';
        } elseif ($score <= 50) {
            return 'medium';
        } elseif ($score <= 75) {
            return 'high';
        } else {
            return 'critical';
        }
    }

    /**
     * Determine routing decision based on risk level and hard stops
     */
    private function determineRoutingDecision(int $score, int $hardStopCount): string
    {
        if ($hardStopCount > 0) {
            return 'block_for_investigation';
        }

        switch ($this->determineRiskLevel($score)) {
            case 'low':
                return 'auto_approve';
            case 'medium':
                return 'normal_review';
            case 'high':
                return 'officer_review';
            case 'critical':
                return 'senior_review';
            default:
                return 'pending_clarification';
        }
    }

    /**
     * Get recommendation for officers
     */
    private function getRecommendation(string $riskLevel, string $routingDecision): string
    {
        $recommendations = [
            'auto_approve' => 'Low risk - can be auto-approved pending final payment verification',
            'normal_review' => 'Medium risk - standard officer review recommended',
            'officer_review' => 'High risk - officer review required before approval',
            'senior_review' => 'Critical risk - senior officer/registrar review required',
            'block_for_investigation' => 'Application has unresolved hard-stop errors - cannot proceed',
            'pending_clarification' => 'Applicant clarification required before proceeding',
        ];

        return $recommendations[$routingDecision] ?? 'Review recommended';
    }

    /**
     * Helper: Check if employer is registered
     */
    private function isEmployerRegistered(string $employerName): bool
    {
        return \DB::table('employer_verification')
            ->where('employer_name', 'LIKE', "%$employerName%")
            ->where('registration_status', 'registered')
            ->exists();
    }
}
