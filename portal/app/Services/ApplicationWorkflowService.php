<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use App\Services\StatusTransitionValidator;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApplicationWorkflowService
{
    public static function submitApplication(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::SUBMITTED_TO_ACCREDITATION_OFFICER
            );
            
            $application->update([
                'status' => Application::SUBMITTED_TO_ACCREDITATION_OFFICER,
                'current_stage' => 'accreditation_officer',
                'submitted_at' => now(),
                'is_draft' => false,
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
            ]);
            
            ActivityLogger::log(
                'application_submitted',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => 'applicant',
                    'actor_user_id' => Auth::id(),
                ], $data)
            );
            
            return $application->fresh();
        });
    }

    public static function approveApplication(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $fromStatus = $application->status;

            $newStatus = Application::FORWARDED_TO_REGISTRAR;
            $stage = 'registrar_review';
            
            StatusTransitionValidator::validateOrFail($application, $newStatus);
            
            $application->update([
                'status'              => $newStatus,
                'current_stage'       => $stage,
                'last_action_at'      => now(),
                'last_action_by'      => Auth::id(),
                'assigned_officer_id' => Auth::id(),
                'decision_notes'      => $data['notes'] ?? null,
                // Release the concurrency lock so the Registrar can open
                // the application immediately without seeing a stale lock.
                'locked_by'           => null,
                'locked_at'           => null,
            ]);

            // Automatically resolve any pending fix requests for this application
            \App\Models\FixRequest::where('application_id', $application->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->get()
                ->each(function($fr) {
                    $fr->markResolved(Auth::id(), 'Resolved via approval');
                });
            
            ActivityLogger::log(
                'officer_approved',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role'    => session('active_staff_role', 'accreditation_officer'),
                    'actor_user_id' => Auth::id(),
                ], $data)
            );
            
            $application = $application->fresh();
            
            // Check if it's a media house application to send registrar notification
            $isMediaHouseRegistration = ($application->application_type === 'registration' && 
                                       ($application->applicant_type === 'media_house' || $application->form_type === 'AP1'));
            if ($isMediaHouseRegistration) {
                self::sendRegistrarNotificationEmail($application);
            }
            
            return $application;
        });
    }


    public static function returnToApplicant(Application $application, string $reason, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $reason, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::CORRECTION_REQUESTED
            );
            
            $application->update([
                'status' => Application::CORRECTION_REQUESTED,
                'current_stage' => 'applicant_correction',
                'correction_notes' => $reason,
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
            ]);
            
            ActivityLogger::log(
                'officer_request_correction',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => session('active_staff_role', 'accreditation_officer'),
                    'actor_user_id' => Auth::id(),
                    'reason' => $reason,
                ], $data)
            );
            
            return $application->fresh();
        });
    }

    public static function forwardWithoutApproval(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::FORWARDED_TO_REGISTRAR_NO_APPROVAL
            );
            
            $application->update([
                'status' => Application::FORWARDED_TO_REGISTRAR_NO_APPROVAL,
                'current_stage' => 'registrar_special_review',
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
                'decision_notes' => $data['notes'] ?? null,
            ]);

            // Automatically resolve any pending fix requests for this application
            \App\Models\FixRequest::where('application_id', $application->id)
                ->whereIn('status', ['pending', 'in_progress'])
                ->get()
                ->each(function($fr) {
                    $fr->markResolved(Auth::id(), 'Resolved via forwarding');
                });
            
            ActivityLogger::log(
                'officer_forward_without_approval',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => session('active_staff_role', 'accreditation_officer'),
                    'actor_user_id' => Auth::id(),
                    'reason' => 'Special case / Waiver',
                ], $data)
            );
            
            return $application->fresh();
        });
    }

    public static function registrarRaiseFixRequest(Application $application, string $reason, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $reason, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::REGISTRAR_RAISED_FIX_REQUEST
            );
            
            $application->update([
                'status' => Application::REGISTRAR_RAISED_FIX_REQUEST,
                'current_stage' => 'officer_fix',
                'correction_notes' => $reason,
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
                // Release the file back to the Officer pool so any officer can pick it up.
                // (Without clearing assignment/lock, the previous officer's claim would
                // hide it from every other officer's dashboard.)
                'assigned_officer_id' => null,
                'locked_at' => null,
                'locked_by' => null,
            ]);
            
            ActivityLogger::log(
                'registrar_raise_fix_request',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => session('active_staff_role', 'registrar'),
                    'actor_user_id' => Auth::id(),
                    'reason' => $reason,
                ], $data)
            );
            
            return $application->fresh();
        });
    }

    public static function registrarApprove(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            // Auto-promote pre-review statuses to REGISTRAR_REVIEW first.
            // The transition map only allows REGISTRAR_REVIEW (or PAID_CONFIRMED)
            // to step directly into ACCOUNTS_REVIEW / PAID_CONFIRMED, so when the
            // registrar approves an application that is still sitting in
            // FORWARDED_TO_REGISTRAR, VERIFIED_BY_OFFICER, or OFFICER_APPROVED,
            // we first claim it into REGISTRAR_REVIEW. Without this step the
            // approve action silently fails validation and the application
            // appears "stuck" in the incoming queue.
            $preReviewStatuses = [
                Application::FORWARDED_TO_REGISTRAR,
                Application::VERIFIED_BY_OFFICER,
                Application::OFFICER_APPROVED,
            ];

            if (in_array($application->status, $preReviewStatuses, true)) {
                $preFrom = $application->status;
                StatusTransitionValidator::validateOrFail($application, Application::REGISTRAR_REVIEW);
                $application->update([
                    'status'         => Application::REGISTRAR_REVIEW,
                    'current_stage'  => 'registrar',
                    'last_action_at' => now(),
                    'last_action_by' => Auth::id(),
                ]);
                ActivityLogger::log(
                    'registrar_auto_claim_for_approval',
                    $application,
                    $preFrom,
                    Application::REGISTRAR_REVIEW,
                    [
                        'reason' => 'Auto-claimed into registrar review prior to approval decision.',
                        'actor_role' => session('active_staff_role', 'registrar'),
                        'actor_user_id' => Auth::id(),
                    ]
                );
                $application->refresh();
            }

            $fromStatus = $application->status;

            $hasPaynowPaid = self::hasSuccessfulPaynowPayment($application);

            $isMediaHouseRegistration = ($application->application_type === 'registration' && 
                                       ($application->applicant_type === 'media_house' || $application->form_type === 'AP1'));

            if ($isMediaHouseRegistration) {
                $newStatus = Application::AWAITING_REG_FEE;
                $stage = 'applicant_payment';
                $action = 'registrar_approved_awaiting_reg_fee';
                // Trigger email in a separate job/helper if necessary
            } elseif ($hasPaynowPaid) {
                $newStatus = Application::PAID_CONFIRMED;
                $stage = 'production';
                $action = 'registrar_approved_paynow_autopaid';
            } else {
                $newStatus = Application::ACCOUNTS_REVIEW;
                $stage = 'accounts_verification';
                $action = 'registrar_approved';
            }

            StatusTransitionValidator::validateOrFail($application, $newStatus);

            $application->update([
                'status' => $newStatus,
                'current_stage' => $stage,
                'registrar_reviewed_at' => now(),
                'registrar_reviewed_by' => Auth::id(),
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
                // Release the per-user concurrency lock when handing the file
                // off to a different team (Accounts / Production). The lock was
                // originally set by the Accreditation Officer who claimed the
                // file; without clearing it, the Accounts dashboard's lock
                // filter (`locked_by != current_user AND locked_at within 2h`)
                // hides the application from Accounts staff for up to two
                // hours after the registrar approves it.
                'locked_at' => null,
                'locked_by' => null,
            ]);

            ActivityLogger::log(
                $action,
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => session('active_staff_role', 'registrar'),
                    'actor_user_id' => Auth::id(),
                    'paynow_autopaid' => $hasPaynowPaid,
                ], $data)
            );

            if ($hasPaynowPaid) {
                $application->refresh();
                StatusTransitionValidator::validateOrFail($application, Application::PRODUCTION_QUEUE);
                $application->update([
                    'status' => Application::PRODUCTION_QUEUE,
                    'current_stage' => 'production',
                    'last_action_at' => now(),
                    'last_action_by' => Auth::id(),
                    // Hand-off to Production: clear the originating Accreditation
                    // Officer's assignment so the Production queue (which filters
                    // by `assigned_officer_id IS NULL OR == current_user.id`) can
                    // surface the file. Locks were already cleared above.
                    'assigned_officer_id' => null,
                ]);
                ActivityLogger::log(
                    'auto_forwarded_to_production',
                    $application,
                    Application::PAID_CONFIRMED,
                    Application::PRODUCTION_QUEUE,
                    [
                        'reason' => 'PayNow payment already confirmed; bypassing manual accounts approval.',
                        'actor_role' => session('active_staff_role', 'registrar'),
                        'actor_user_id' => Auth::id(),
                    ]
                );
            }

            return $application->fresh();
        });
    }

    /**
     * Returns true ONLY when there is evidence of a TERMINAL, verified PayNow
     * payment for this application. Unverified/submitted-only references must
     * NOT count, otherwise applications could bypass Accounts verification.
     */
    protected static function hasSuccessfulPaynowPayment(Application $application): bool
    {
        try {
            $hasPaynow = $application->payments()
                ->where('method', 'paynow')
                ->whereIn('status', ['paid', 'completed', 'success'])
                ->exists();
            if ($hasPaynow) return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('paynow detection (payments) failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $hasSubmission = $application->paymentSubmissions()
                ->where('method', 'PAYNOW')
                ->where('status', 'verified')
                ->exists();
            if ($hasSubmission) return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('paynow detection (submissions) failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }

        if (
            ($application->payment_submission_method ?? null) === 'paynow_reference'
            && ($application->payment_status ?? null) === 'paid'
        ) {
            return true;
        }

        return false;
    }



    public static function registrarPushToAccounts(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::PENDING_ACCOUNTS_REVIEW_FROM_REGISTRAR_SPECIAL
            );
            
            $application->update([
                'status' => Application::PENDING_ACCOUNTS_REVIEW_FROM_REGISTRAR_SPECIAL,
                'current_stage' => 'accounts_special_review',
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
                // Hand-off to Accounts: release any concurrency lock held by the
                // registrar / originating officer so the Accounts queue (which
                // hides items locked by other users for up to 2 hours) can show
                // the application immediately.
                'locked_at' => null,
                'locked_by' => null,
            ]);
            
            ActivityLogger::log(
                'registrar_push_to_accounts',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => session('active_staff_role', 'registrar'),
                    'actor_user_id' => Auth::id(),
                ], $data)
            );
            
            return $application->fresh();
        });
    }

    public static function sendToProduction(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::PRODUCTION_QUEUE
            );
            
            $application->update([
                'status' => Application::PRODUCTION_QUEUE,
                'current_stage' => 'production',
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
                // Hand-off to Production: release the prior team's lock and
                // clear the originating Accreditation Officer assignment so
                // the Production queue (which filters by `assigned_officer_id
                // IS NULL OR == current_user.id` and by recent locks) can
                // surface the file to the production team.
                'locked_at' => null,
                'locked_by' => null,
                'assigned_officer_id' => null,
            ]);

            if ($application->payment_status === 'paid' && $application->paynow_reference) {
                $existingPayment = \App\Models\Payment::where('application_id', $application->id)
                    ->where('status', 'paid')
                    ->first();

                if (!$existingPayment) {
                    \App\Models\Payment::create([
                        'application_id' => $application->id,
                        'payer_user_id' => $application->applicant_user_id,
                        'method' => 'paynow',
                        'source' => 'online',
                        'amount' => $application->proof_amount_paid ?? 0,
                        'currency' => 'USD',
                        'reference' => $application->paynow_reference,
                        'status' => 'paid',
                        'confirmed_at' => now(),
                        'receipt_number' => 'PN-' . strtoupper(\Illuminate\Support\Str::random(8)),
                        'applicant_category' => $application->accreditation_category_code ?? $application->media_house_category_code,
                        'service_type' => $application->application_type,
                        'residency' => $application->residency_type ?? 'local',
                        'recorded_by' => Auth::id(),
                    ]);
                }
            }
            
            ActivityLogger::log(
                'sent_to_production',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => 'system',
                    'actor_user_id' => Auth::id(),
                ], $data)
            );
            
            return $application->fresh();
        });
    }

    public static function markProduced(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::PRODUCED_READY_FOR_COLLECTION
            );
            
            $application->update([
                'status' => Application::PRODUCED_READY_FOR_COLLECTION,
                'current_stage' => 'ready_for_collection',
                'printed_at' => now(),
                'printed_by' => Auth::id(),
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
            ]);
            
            ActivityLogger::log(
                'production_complete',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => session('active_staff_role', 'accreditation_officer'),
                    'actor_user_id' => Auth::id(),
                ], $data)
            );
            
            return $application->fresh();
        });
    }

    public static function markIssued(Application $application, array $data = []): Application
    {
        return DB::transaction(function () use ($application, $data) {
            $fromStatus = $application->status;
            
            StatusTransitionValidator::validateOrFail(
                $application,
                Application::ISSUED
            );
            
            $application->update([
                'status' => Application::ISSUED,
                'current_stage' => 'issued',
                'issued_at' => now(),
                'issued_by' => Auth::id(),
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
            ]);
            
            ActivityLogger::log(
                'application_issued',
                $application,
                $fromStatus,
                $application->status,
                array_merge([
                    'actor_role' => session('active_staff_role', 'accreditation_officer'),
                    'actor_user_id' => Auth::id(),
                ], $data)
            );
            
            return $application->fresh();
        });
    }
    public static function handleAction(Application $application, string $action, array $data = []): Application
    {
        return match ($action) {
            'submit' => self::submitApplication($application, $data),
            'officer_approve' => self::approveApplication($application, $data),
            'officer_reject' => self::returnToApplicant($application, $data['notes'] ?? '', $data),
            'registrar_approve' => self::registrarApprove($application, $data),
            'registrar_reject' => self::registrarRaiseFixRequest($application, $data['notes'] ?? '', $data),
            default => throw new \InvalidArgumentException("Unknown action: {$action}"),
        };
    }

    public static function openRegistrationFeeModal(Application $application): array
    {
        $application->update(['reg_fee_modal_triggered_at' => now()]);
        return [
            'show_modal' => true,
            'modal_type' => 'registration_fee',
            'application_id' => $application->id,
        ];
    }

    public static function sendCollectionEmail(Application $application): void
    {
        $application->update(['collection_email_sent_at' => now()]);
        
        $applicantEmail = $application->applicantUser->email ?? null;
        if ($applicantEmail) {
            \Illuminate\Support\Facades\Mail::send(
                'emails.collection_ready',
                ['application' => $application],
                function ($message) use ($applicantEmail) {
                    $message->to($applicantEmail)->subject('ZMC Registration Certificate Ready for Collection');
                }
            );
        }
    }

    public static function sendRegistrationFeeRequiredEmail(Application $application): void
    {
        $applicantEmail = $application->applicantUser->email ?? null;
        if ($applicantEmail) {
            \Illuminate\Support\Facades\Mail::send(
                'emails.registration_fee_payment_required',
                ['application' => $application],
                function ($message) use ($applicantEmail) {
                    $message->to($applicantEmail)->subject('ZMC Registration Fee Required');
                }
            );
        }
    }

    public static function sendRegistrarNotificationEmail(Application $application): void
    {
        // Send email to a configurable registrar email or role
        $registrarEmails = User::role('registrar')->pluck('email')->toArray();
        if (!empty($registrarEmails)) {
            \Illuminate\Support\Facades\Mail::send(
                'emails.registrar_notification',
                ['application' => $application],
                function ($message) use ($registrarEmails) {
                    $message->to($registrarEmails)->subject('New Media House Application Awaiting Review');
                }
            );
        }
    }

    public static function isPaynowAutoVerified(Application $application, string $stage = 'app_fee'): bool
    {
        if ($stage === 'app_fee') {
            return $application->app_fee_verified_at !== null && $application->app_fee_payment_method === 'paynow';
        }
        
        return $application->reg_fee_verified_at !== null && $application->reg_fee_payment_method === 'paynow';
    }
}
