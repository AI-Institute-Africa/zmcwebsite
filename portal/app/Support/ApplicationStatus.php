<?php

namespace App\Support;

use App\Models\Application;

/**
 * One place that defines how an application status is worded and coloured,
 * so staff, applicants and emails can use the same language.
 *
 * Text comes from Application::statusLabel(); this class adds a shared colour,
 * icon and broad stage group (Draft, Submitted, Under review, Returned,
 * Awaiting payment, Approved, In production, Issued, Withdrawn).
 */
class ApplicationStatus
{
    /** group => [label, tone (bootstrap colour name), remix icon] */
    public const GROUPS = [
        'draft'     => ['Draft',            'secondary', 'ri-draft-line'],
        'submitted' => ['Submitted',        'info',      'ri-send-plane-line'],
        'review'    => ['Under review',     'warning',   'ri-search-eye-line'],
        'returned'  => ['Returned',         'orange',    'ri-arrow-go-back-line'],
        'payment'   => ['Awaiting payment', 'info',      'ri-bank-card-line'],
        'approved'  => ['Approved',         'success',   'ri-checkbox-circle-line'],
        'production' => ['In production',   'primary',   'ri-printer-line'],
        'issued'    => ['Issued',           'success',   'ri-medal-line'],
        'rejected'  => ['Rejected',         'danger',    'ri-close-circle-line'],
        'withdrawn' => ['Withdrawn',        'secondary', 'ri-forbid-line'],
    ];

    /** status code => group */
    private const MAP = [
        Application::DRAFT => 'draft',
        // Business wording: a submitted application waits for payment first
        // (see Application::statusLabel), so it shares the "payment" colour.
        Application::SUBMITTED => 'payment',
        Application::SUBMITTED_WITH_APP_FEE => 'submitted',
        Application::WITHDRAWN => 'withdrawn',

        Application::OFFICER_REVIEW => 'review',
        Application::REGISTRAR_REVIEW => 'review',
        Application::FORWARDED_TO_REGISTRAR => 'review',
        Application::VERIFIED_BY_OFFICER => 'review',
        Application::ACCOUNTS_REVIEW => 'review',

        Application::CORRECTION_REQUESTED => 'returned',
        Application::RETURNED_TO_APPLICANT => 'returned',
        Application::REGISTRAR_FIX_REQUEST => 'returned',
        Application::RETURNED_TO_OFFICER => 'returned',
        Application::RETURNED_TO_ACCOUNTS => 'returned',

        Application::APPROVED_AWAITING_PAYMENT => 'payment',
        Application::PENDING_ACCOUNTS_FROM_REGISTRAR => 'payment',
        Application::REGISTRAR_APPROVED_PENDING_REG_FEE => 'payment',
        Application::SUPERADMIN_APPROVED_PENDING_REG_FEE => 'payment',
        Application::AWAITING_REG_FEE => 'payment',
        Application::REG_FEE_PROOF_UPLOADED => 'payment',
        Application::AWAITING_ACCOUNTS_VERIFICATION => 'payment',

        Application::OFFICER_APPROVED => 'approved',
        Application::REGISTRAR_APPROVED => 'approved',
        Application::APP_FEE_VERIFIED => 'approved',
        Application::PAYMENT_VERIFIED => 'approved',
        Application::PAID_CONFIRMED => 'approved',
        Application::REGISTERED => 'approved',

        Application::PRODUCTION_QUEUE => 'production',
        Application::PRODUCTION_APPROVED => 'production',
        Application::CARD_GENERATED => 'production',
        Application::CERT_GENERATED => 'production',
        Application::PRODUCED_READY => 'production',
        Application::PRINTED => 'production',

        Application::ISSUED => 'issued',

        // Legacy / newer stage values not covered by a named constant
        'cert_generated' => 'production',
        'produced_ready_for_collection' => 'production',
        'director_review' => 'review',

        // The portal words these as returns for correction, not rejections.
        Application::OFFICER_REJECTED => 'returned',
        Application::REGISTRAR_REJECTED => 'returned',
        Application::PAYMENT_REJECTED => 'returned',
    ];

    public static function group(?string $status): ?string
    {
        $status = strtolower(trim((string) $status));
        return self::MAP[$status] ?? null;
    }

    /**
     * Label text. Single source of truth stays Application::statusLabel() so the
     * chip never contradicts wording staff already use.
     */
    public static function label(?string $status): string
    {
        $status = trim((string) $status);
        return $status === '' ? '—' : Application::statusLabel($status);
    }

    /** Broad stage (e.g. "Under review"), shown as the chip tooltip. */
    public static function detail(?string $status): string
    {
        $g = self::group($status);
        return $g ? self::GROUPS[$g][0] : '';
    }

    public static function tone(?string $status): string
    {
        $g = self::group($status);
        return $g ? self::GROUPS[$g][1] : 'secondary';
    }

    public static function icon(?string $status): string
    {
        $g = self::group($status);
        return $g ? self::GROUPS[$g][2] : 'ri-information-line';
    }
}
