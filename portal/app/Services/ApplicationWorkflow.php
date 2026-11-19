<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ApplicationWorkflow
{
    public static function transition(Application $app, string $toStatus, string $action, array $meta = []): void
    {
        $from = (string) $app->status;

        if (!self::allowed($from, $toStatus)) {
            throw ValidationException::withMessages([
                'status' => "Invalid transition from [$from] to [$toStatus].",
            ]);
        }

        $app->status = $toStatus;
        $app->current_stage = Application::stageForStatus($toStatus);
        $app->last_action_at = now();
        $app->last_action_by = Auth::id();

        if (in_array($toStatus, [
            Application::REGISTRAR_APPROVED,
        ], true)) {
            $app->approved_at = now();
            $app->rejected_at = null;
        }

        if (in_array($toStatus, [
            Application::CORRECTION_REQUESTED,
            Application::RETURNED_TO_APPLICANT,
            Application::PAYMENT_REJECTED,
        ], true)) {
            $app->rejected_at = now();
            $app->approved_at = null;
        }

        $app->save();

        ActivityLogger::log($action, $app, $from, $toStatus, $meta);
    }

    public static function allowed(string $from, string $to): bool
    {
        $map = self::transitionMap();
        return in_array($to, $map[$from] ?? [], true);
    }

    public static function transitionMap(): array
    {
        return [
            Application::DRAFT => [
                Application::SUBMITTED,
            ],

            Application::SUBMITTED => [
                Application::OFFICER_REVIEW,
                Application::FORWARDED_TO_REGISTRAR,
                Application::CORRECTION_REQUESTED,
                Application::RETURNED_TO_APPLICANT,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
            ],

            Application::SUBMITTED_WITH_APP_FEE => [
                Application::OFFICER_REVIEW,
                Application::FORWARDED_TO_REGISTRAR,
                Application::CORRECTION_REQUESTED,
                Application::RETURNED_TO_APPLICANT,
            ],

            Application::OFFICER_REVIEW => [
                Application::FORWARDED_TO_REGISTRAR,
                Application::CORRECTION_REQUESTED,
                Application::RETURNED_TO_APPLICANT,
            ],

            Application::CORRECTION_REQUESTED => [
                Application::SUBMITTED,
                Application::OFFICER_REVIEW,
                Application::FORWARDED_TO_REGISTRAR,
            ],

            Application::RETURNED_TO_APPLICANT => [
                Application::SUBMITTED,
                Application::OFFICER_REVIEW,
                Application::WITHDRAWN,
                Application::FORWARDED_TO_REGISTRAR,
            ],

            Application::FORWARDED_TO_REGISTRAR => [
                Application::REGISTRAR_REVIEW,
                Application::REGISTRAR_REJECTED,
                Application::RETURNED_TO_OFFICER,
                Application::RETURNED_TO_ACCOUNTS,
            ],

            Application::VERIFIED_BY_OFFICER => [
                Application::FORWARDED_TO_REGISTRAR,
                Application::REGISTRAR_REVIEW,
                Application::REGISTRAR_REJECTED,
                Application::RETURNED_TO_OFFICER,
            ],

            Application::REGISTRAR_REVIEW => [
                Application::REGISTRAR_APPROVED,
                Application::ACCOUNTS_REVIEW,
                Application::PAID_CONFIRMED,
                Application::RETURNED_TO_OFFICER,
                Application::REGISTRAR_FIX_REQUEST,
                Application::REGISTRAR_REJECTED,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                Application::AWAITING_REG_FEE,
            ],

            Application::AWAITING_REG_FEE => [
                Application::REG_FEE_PROOF_UPLOADED,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::REGISTERED,
            ],

            Application::REG_FEE_PROOF_UPLOADED => [
                Application::REGISTERED,
                Application::AWAITING_REG_FEE,
            ],

            Application::APP_FEE_VERIFIED => [
                Application::OFFICER_REVIEW,
                Application::FORWARDED_TO_REGISTRAR,
                Application::CORRECTION_REQUESTED,
                Application::RETURNED_TO_APPLICANT,
            ],

            Application::SUPERADMIN_APPROVED_PENDING_REG_FEE => [
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::ACCOUNTS_REVIEW,
                Application::PAID_CONFIRMED,
            ],

            Application::REGISTRAR_APPROVED => [
                Application::ACCOUNTS_REVIEW,
                Application::PAID_CONFIRMED,
                Application::PRODUCTION_QUEUE,
            ],

            Application::REGISTRAR_FIX_REQUEST => [
                Application::OFFICER_REVIEW,
                Application::CORRECTION_REQUESTED,
                Application::FORWARDED_TO_REGISTRAR,
            ],

            Application::RETURNED_TO_OFFICER => [
                Application::OFFICER_REVIEW,
                Application::CORRECTION_REQUESTED,
                Application::FORWARDED_TO_REGISTRAR,
                Application::SUBMITTED,
            ],

            Application::ACCOUNTS_REVIEW => [
                Application::PAYMENT_VERIFIED,
                Application::PAID_CONFIRMED,
                Application::PAYMENT_REJECTED,
                Application::RETURNED_TO_APPLICANT,
                Application::RETURNED_TO_OFFICER,
            ],

            Application::AWAITING_ACCOUNTS_VERIFICATION => [
                Application::PAYMENT_VERIFIED,
                Application::PAID_CONFIRMED,
                Application::PAYMENT_REJECTED,
                Application::SUBMITTED,
                Application::RETURNED_TO_OFFICER,
            ],

            Application::PAYMENT_VERIFIED => [
                Application::PRODUCTION_QUEUE,
            ],

            Application::PAID_CONFIRMED => [
                Application::PRODUCTION_QUEUE,
            ],

            Application::PAYMENT_REJECTED => [
                Application::RETURNED_TO_APPLICANT,
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
            ],

            Application::RETURNED_TO_ACCOUNTS => [
                Application::ACCOUNTS_REVIEW,
                Application::RETURNED_TO_OFFICER,
            ],

            Application::PENDING_ACCOUNTS_FROM_REGISTRAR => [
                Application::PAYMENT_VERIFIED,
                Application::PAYMENT_REJECTED,
                Application::RETURNED_TO_OFFICER,
            ],

            Application::REGISTRAR_APPROVED_PENDING_REG_FEE => [
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::ACCOUNTS_REVIEW,
                Application::PAID_CONFIRMED,
            ],

            Application::PRODUCTION_QUEUE => [
                Application::PRODUCTION_APPROVED,
                Application::CARD_GENERATED,
                Application::CERT_GENERATED,
                Application::PRODUCED_READY,
                Application::PRINTED,
            ],
            Application::PRODUCTION_APPROVED => [
                Application::CARD_GENERATED,
                Application::CERT_GENERATED,
                Application::PRODUCED_READY,
                Application::PRINTED,
            ],
            Application::CARD_GENERATED => [
                Application::PRINTED,
                Application::PRODUCED_READY,
            ],
            Application::CERT_GENERATED => [
                Application::PRINTED,
                Application::PRODUCED_READY,
            ],
            Application::PRODUCED_READY => [
                Application::PRINTED,
                Application::ISSUED,
            ],
            Application::PRINTED => [
                Application::ISSUED,
            ],

            Application::OFFICER_APPROVED => [
                Application::FORWARDED_TO_REGISTRAR,
                Application::REGISTRAR_REVIEW,
            ],

            Application::APPROVED_AWAITING_PAYMENT => [
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::ACCOUNTS_REVIEW,
            ],

            Application::REGISTRAR_REJECTED => [
                Application::RETURNED_TO_OFFICER,
            ],

            Application::OFFICER_REJECTED => [
                Application::RETURNED_TO_APPLICANT,
                Application::SUBMITTED,
            ],
        ];
    }

    public static function statusesForRole(string $role): array
    {
        return match($role) {
            'accreditation_officer' => [
                Application::SUBMITTED,
                Application::OFFICER_REVIEW,
                Application::RETURNED_TO_OFFICER,
                Application::REGISTRAR_FIX_REQUEST,
                Application::SUBMITTED_WITH_APP_FEE,
                Application::APP_FEE_VERIFIED,
            ],
            'registrar' => [
                Application::FORWARDED_TO_REGISTRAR,
                Application::REGISTRAR_REVIEW,
                Application::VERIFIED_BY_OFFICER,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            ],
            'accounts' => [
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::REGISTRAR_APPROVED,
                Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
                Application::REG_FEE_PROOF_UPLOADED,
            ],
            'production' => [
                Application::PRODUCTION_QUEUE,
                Application::PRODUCTION_APPROVED,
                Application::PAYMENT_VERIFIED,
                Application::PAID_CONFIRMED,
            ],
            default => [],
        };
    }
}
