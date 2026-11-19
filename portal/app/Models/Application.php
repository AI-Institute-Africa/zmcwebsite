<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;



class Application extends Model
{
    use HasFactory;

    public static function yearOptions(): array
    {
        $currentYear = now()->year;
        $firstCreatedAt = static::query()->min('created_at');
        $firstYear = $firstCreatedAt
            ? \Illuminate\Support\Carbon::parse($firstCreatedAt)->year
            : $currentYear;

        return range($currentYear, min($currentYear, $firstYear));
    }

    // --- Draft & Submission ---
    public const DRAFT                = 'draft';
    public const SUBMITTED            = 'submitted';
    public const WITHDRAWN            = 'withdrawn';

    // --- Accreditation Officer ---
    public const OFFICER_REVIEW                  = 'officer_review';
    public const OFFICER_APPROVED                = 'officer_approved';
    public const OFFICER_REJECTED                = 'officer_rejected';
    public const CORRECTION_REQUESTED            = 'correction_requested';
    public const RETURNED_TO_APPLICANT           = 'returned_to_applicant';
    public const APPROVED_AWAITING_PAYMENT       = 'approved_awaiting_payment';
    public const FORWARDED_TO_REGISTRAR          = 'forwarded_to_registrar';
    public const REGISTRAR_FIX_REQUEST           = 'registrar_fix_request';

    // --- Registrar ---
    public const REGISTRAR_REVIEW                = 'registrar_review';
    public const REGISTRAR_APPROVED              = 'registrar_approved';
    public const REGISTRAR_REJECTED              = 'registrar_rejected';
    public const RETURNED_TO_OFFICER             = 'returned_to_officer';
    public const PENDING_ACCOUNTS_FROM_REGISTRAR = 'pending_accounts_from_registrar';
    public const REGISTRAR_APPROVED_PENDING_REG_FEE = 'registrar_approved_pending_reg_fee';
    public const SUPERADMIN_APPROVED_PENDING_REG_FEE = 'superadmin_approved_pending_reg_fee';

    // --- Application / Registration Fee Flow ---
    public const APP_FEE_VERIFIED                = 'app_fee_verified';
    public const AWAITING_REG_FEE                = 'awaiting_reg_fee';
    public const REGISTERED                      = 'registered';
    public const REG_FEE_PROOF_UPLOADED          = 'reg_fee_proof_uploaded';

    // --- Accounts / Payments ---
    public const ACCOUNTS_REVIEW                 = 'accounts_review';
    public const AWAITING_ACCOUNTS_VERIFICATION  = 'awaiting_accounts_verification';
    public const PAYMENT_VERIFIED                = 'payment_verified';
    public const PAYMENT_REJECTED                = 'payment_rejected';
    public const PAID_CONFIRMED                  = 'paid_confirmed';
    public const RETURNED_TO_ACCOUNTS            = 'returned_to_accounts';

    // --- Media House Specific ---
    public const SUBMITTED_WITH_APP_FEE          = 'submitted_with_app_fee';
    public const VERIFIED_BY_OFFICER             = 'verified_by_officer';

    // --- Production ---
    public const PRODUCTION_QUEUE                = 'production_queue';
    public const PRODUCTION_APPROVED             = 'production_approved';
    public const CARD_GENERATED                  = 'card_generated';
    public const CERT_GENERATED                  = 'certificate_generated';
    public const PRINTED                         = 'printed';
    public const ISSUED                          = 'issued';

    // Legacy aliases for backward compatibility
    public const FORWARDED_TO_REGISTRAR_NO_APPROVAL = 'forwarded_to_registrar';
    public const PENDING_ACCOUNTS_REVIEW_FROM_REGISTRAR = 'pending_accounts_from_registrar';
    public const VERIFIED_BY_OFFICER_PENDING_REGISTRAR = 'verified_by_officer';
    public const REG_FEE_SUBMITTED_AWAITING_VERIFICATION = 'awaiting_accounts_verification';
    public const APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR = 'approved_awaiting_payment';
    public const SUBMITTED_TO_ACCREDITATION_OFFICER = 'submitted';
    public const APPROVED_BY_ACCREDITATION_OFFICER_AWAITING_PAYMENT = 'approved_awaiting_payment';
    public const APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER = 'approved_awaiting_payment';
    public const REGISTRAR_RAISED_FIX_REQUEST = 'registrar_fix_request';
    public const PENDING_ACCOUNTS_REVIEW_FROM_REGISTRAR_SPECIAL = 'pending_accounts_from_registrar';
    public const REGISTRAR_APPROVED_PENDING_REGISTRATION_FEE_PAYMENT = 'registrar_approved_pending_reg_fee';
    public const REGISTRATION_FEE_AWAITING_VERIFICATION = 'awaiting_accounts_verification';
    public const CERT_GENERATED_LEGACY = 'cert_generated';
    public const PRODUCED_READY = 'produced_ready';
    public const PRODUCED_READY_FOR_COLLECTION = 'produced_ready_for_collection';
    public const DIRECTOR_REVIEW = 'director_review';

    public static function allStatuses(): array
    {
        return [
            self::DRAFT, self::SUBMITTED, self::WITHDRAWN,
            self::OFFICER_REVIEW, self::OFFICER_APPROVED, self::OFFICER_REJECTED,
            self::CORRECTION_REQUESTED, self::RETURNED_TO_APPLICANT,
            self::APPROVED_AWAITING_PAYMENT, self::FORWARDED_TO_REGISTRAR,
            self::REGISTRAR_FIX_REQUEST,
            self::REGISTRAR_REVIEW, self::REGISTRAR_APPROVED, self::REGISTRAR_REJECTED,
            self::RETURNED_TO_OFFICER, self::PENDING_ACCOUNTS_FROM_REGISTRAR,
            self::REGISTRAR_APPROVED_PENDING_REG_FEE,
            self::SUPERADMIN_APPROVED_PENDING_REG_FEE,
            self::APP_FEE_VERIFIED, self::AWAITING_REG_FEE, self::REGISTERED, self::REG_FEE_PROOF_UPLOADED,
            self::ACCOUNTS_REVIEW, self::AWAITING_ACCOUNTS_VERIFICATION,
            self::PAYMENT_VERIFIED, self::PAYMENT_REJECTED,
            self::PAID_CONFIRMED, self::RETURNED_TO_ACCOUNTS,
            self::SUBMITTED_WITH_APP_FEE, self::VERIFIED_BY_OFFICER,
            self::PRODUCTION_QUEUE, self::PRODUCTION_APPROVED,
            self::CARD_GENERATED, self::CERT_GENERATED, self::CERT_GENERATED_LEGACY,
            self::PRINTED, self::ISSUED,
        ];
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Awaiting Payment',
            self::WITHDRAWN => 'Withdrawn',
            self::OFFICER_REVIEW => 'Under Officer Review',
            self::OFFICER_APPROVED => 'Officer Approved',
            self::OFFICER_REJECTED => 'Returned for Correction (Officer)',
            self::CORRECTION_REQUESTED => 'Returned for Correction',
            self::RETURNED_TO_APPLICANT => 'Returned to Applicant',
            self::APPROVED_AWAITING_PAYMENT => 'Approved - Awaiting Payment',
            self::FORWARDED_TO_REGISTRAR => 'Forwarded to Registrar',
            self::REGISTRAR_FIX_REQUEST => 'Returned from Registrar',
            self::REGISTRAR_REVIEW => 'Under Registrar Review',
            self::REGISTRAR_APPROVED => 'Registrar Approved',
            self::REGISTRAR_REJECTED => 'Returned for Correction (Registrar)',
            self::RETURNED_TO_OFFICER => 'Returned to Officer',
            self::PENDING_ACCOUNTS_FROM_REGISTRAR => 'Pending Accounts (from Registrar)',
            self::REGISTRAR_APPROVED_PENDING_REG_FEE => 'Approved - Pending Registration Fee',
            self::SUPERADMIN_APPROVED_PENDING_REG_FEE => 'Super Admin Approved - Pending Registration Fee',
            self::APP_FEE_VERIFIED => 'App Fee Verified',
            self::AWAITING_REG_FEE => 'Awaiting Registration Fee',
            self::REGISTERED => 'Registered',
            self::REG_FEE_PROOF_UPLOADED => 'Registration Fee Proof Uploaded',
            self::ACCOUNTS_REVIEW => 'Under Accounts Review',
            self::AWAITING_ACCOUNTS_VERIFICATION => 'Awaiting Accounts Verification',
            self::PAYMENT_VERIFIED => 'Payment Verified',
            self::PAYMENT_REJECTED => 'Payment Returned',
            self::PAID_CONFIRMED => 'Approved (Paid)',
            self::RETURNED_TO_ACCOUNTS => 'Returned to Accounts',
            self::SUBMITTED_WITH_APP_FEE => 'Submitted with Application Fee',
            self::VERIFIED_BY_OFFICER => 'Verified by Officer',
            self::PRODUCTION_QUEUE => 'In Production Queue',
            self::PRODUCTION_APPROVED => 'Approved for Production',
            self::CARD_GENERATED => 'Card Generated',
            self::CERT_GENERATED, self::CERT_GENERATED_LEGACY => 'Certificate Generated',
            self::PRINTED => 'Printed',
            self::ISSUED => 'Issued',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    protected $fillable = [
        'reference',
        'applicant_user_id',
        'application_type',
        'request_type',
        'form_type',
        'registration_type',
        'journalist_scope',
        'collection_region',
        'form_data',
        'applicant_type',
        'employment_status',
        'past_work_link',
        'is_draft',
        'submitted_at',

        // workflow
        'status',
        'current_stage',
        'last_action_at',
        'last_action_by',
        'correction_notes',
        'rejection_reason',

        // optional stub fields (safe even if null)
        'id_verification_status',

        // assignments + decisions
        'assigned_officer_id',
        'assigned_at',
        'approved_at',
        'rejected_at',
        'decision_notes',

        // payments
        'paynow_reference',
        'waiver_path',
        'payment_status',
        'payment_currency',
        'payment_paid_at',

        // extended payments / workflow (safe even if null)
        'payment_proof_path',
        'payment_proof_uploaded_at',
        'waiver_status',
        'waiver_reviewed_by',
        'waiver_reviewed_at',
        'waiver_review_notes',
        'proof_status',
        'proof_reviewed_by',
        'proof_reviewed_at',
        'proof_review_notes',
        'paynow_poll_url',
        'paynow_confirmed_at',
        'paynow_webhook_last_hash',
        'proof_payer_first_name',
        'proof_payer_last_name',
        'proof_payment_date',
        'proof_amount_paid',
        'proof_bank_name',
        'proof_original_name',
        'proof_mime',
        'proof_file_hash',

        'waiver_beneficiary_first_name',
        'waiver_beneficiary_last_name',
        'waiver_offered_date',
        'waiver_offered_by_name',
        'waiver_original_name',
        'waiver_mime',
        'waiver_file_hash',

        'registrar_reviewed_at',
        'registrar_reviewed_by',

        'residency_type',
        'accreditation_category_code',
        'media_house_category_code',
        'locked_by',
        'locked_at',
        'printed_by',
        'printed_at',
        'issued_by',
        'issued_at',
        'payment_submission_method',
        'payment_submitted_at',
        'payment_stage',
        'forward_reason',
        'receipt_number',
        'paynow_ref_submitted',
    ];

    protected $casts = [
        'assigned_at'    => 'datetime',
        'approved_at'    => 'datetime',
        'rejected_at'    => 'datetime',
        'last_action_at' => 'datetime',
        'submitted_at'   => 'datetime',
        'payment_proof_uploaded_at' => 'datetime',
        'waiver_reviewed_at' => 'datetime',
        'proof_reviewed_at' => 'datetime',
        'registrar_reviewed_at' => 'datetime',
        'registrar_approved_at' => 'datetime',
        'paynow_confirmed_at' => 'datetime',
        'proof_payment_date' => 'date',
        'waiver_offered_date' => 'date',
        'form_data'      => 'array',
        'is_draft'       => 'boolean',
        'locked_at'      => 'datetime',
        'payment_submitted_at' => 'datetime',
        'payment_paid_at' => 'datetime',
    ];

    public function getApplicantTypeAttribute($value): ?string
    {
        return $value ?? $this->formDataValue('applicant_type');
    }

    public function setApplicantTypeAttribute($value): void
    {
        $this->setFormDataValue('applicant_type', $value);
    }

    public function getEmploymentStatusAttribute($value): ?string
    {
        return $value ?? $this->formDataValue('employment_status');
    }

    public function setEmploymentStatusAttribute($value): void
    {
        $this->setFormDataValue('employment_status', $value);
    }

    public function getPastWorkLinkAttribute($value): ?string
    {
        return $value ?? $this->formDataValue('past_work_link');
    }

    public function setPastWorkLinkAttribute($value): void
    {
        $this->setFormDataValue('past_work_link', $value);
    }

    private function formDataValue(string $key): mixed
    {
        $formData = $this->form_data;

        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?: [];
        }

        return is_array($formData) ? ($formData[$key] ?? null) : null;
    }

    private function setFormDataValue(string $key, mixed $value): void
    {
        $formData = $this->form_data;

        if (is_string($formData)) {
            $formData = json_decode($formData, true) ?: [];
        }

        if (!is_array($formData)) {
            $formData = [];
        }

        $formData[$key] = $value;
        $this->attributes['form_data'] = json_encode($formData);
    }

    /**
     * Boot method to register model event listeners for cache invalidation.
     */
    protected static function booted(): void
    {
        // Invalidate director dashboard caches when application status changes
        static::updated(function (Application $application) {
            if ($application->isDirty('status')) {
                Cache::forget('director.kpis.executive_overview');
                Cache::forget('director.charts.monthly_trends');
            }
        });
    }

    /* =========================
     * Relationships
     * ========================= */

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isLocked(): bool
    {
        if (!$this->locked_at) return false;
        // Optional: lock timeout (e.g. 2 hours)
        return $this->locked_at->gt(now()->subHours(2));
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('locked_at')
              ->orWhere('locked_at', '<=', now()->subHours(2));
        });
    }

    public function claim(User $user): bool
    {
        if ($this->isLocked() && $this->locked_by !== $user->id) {
            return false;
        }

        $data = [
            'locked_by' => $user->id,
            'locked_at' => now(),
        ];

        // Also assign the officer if not already assigned
        if (!$this->assigned_officer_id) {
            $data['assigned_officer_id'] = $user->id;
            $data['assigned_at'] = now();
        }

        $this->update($data);

        return true;
    }

    public function unlock(): void
    {
        $this->update([
            'locked_by' => null,
            'locked_at' => null,
        ]);
    }

    public function lastActionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_action_by');
    }

    /**
     * ✅ Required for: withCount('documents')
     *
     * IMPORTANT: This assumes you have a model + table like:
     * - App\Models\ApplicationDocument
     * - application_documents table with application_id
     *
     * If your table/model name differs, tell me the actual table name.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(\App\Models\ApplicationDocument::class, 'application_id');
    }

    public function currentDocuments(): HasMany
    {
        return $this->hasMany(\App\Models\ApplicationDocument::class, 'application_id')
            ->where('is_current', true);
    }

    public function allDocumentVersions(): HasMany
    {
        return $this->hasMany(\App\Models\ApplicationDocument::class, 'application_id')
            ->orderByDesc('version');
    }

    /**
     * ✅ Messaging (Request more info)
     * Expects App\Models\ApplicationMessage + application_messages table.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(\App\Models\ApplicationMessage::class, 'application_id')->latest();
    }

    /**
     * ✅ Audit trail / workflow activity logs
     * Expects App\Models\ApplicationActivity + application_activities table.
     */
    public function workflowLogs(): MorphMany
    {
        // uses activity_logs.entity_type + activity_logs.entity_id
        return $this->morphMany(\App\Models\ActivityLog::class, 'entity')->latest();
    }

    public function accreditationRecord(): HasOne
    {
        return $this->hasOne(\App\Models\AccreditationRecord::class, 'application_id');
    }

    public function registrationRecord(): HasOne
    {
        return $this->hasOne(\App\Models\RegistrationRecord::class, 'application_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ApplicationComment::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(PrintLog::class);
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function registrarApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrar_reviewed_by');
    }

    public function registrarApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_action_by');
    }

    /**
     * Fix requests for this application (Registrar → Officer)
     */
    public function fixRequests(): HasMany
    {
        return $this->hasMany(FixRequest::class);
    }

    /**
     * Pending fix requests
     */
    public function pendingFixRequests(): HasMany
    {
        return $this->hasMany(FixRequest::class)->where('status', 'pending');
    }

    /**
     * Payment submissions (two-stage payment tracking)
     */
    public function paymentSubmissions(): HasMany
    {
        return $this->hasMany(PaymentSubmission::class);
    }

    /**
     * Application fee payment (first stage for media house)
     */
    public function applicationFeePayment(): HasOne
    {
        return $this->hasOne(PaymentSubmission::class)
            ->where('payment_stage', 'application_fee');
    }

    /**
     * Registration fee payment (second stage for media house)
     */
    public function registrationFeePayment(): HasOne
    {
        return $this->hasOne(PaymentSubmission::class)
            ->where('payment_stage', 'registration_fee');
    }

    /**
     * Official letter uploaded by Registrar
     */
    public function officialLetter(): HasOne
    {
        return $this->hasOne(OfficialLetter::class);
    }

    /**
     * Check if application requires application fee (media house only)
     */
    public function requiresApplicationFee(): bool
    {
        return $this->application_type === 'registration';
    }

    /**
     * Check if application fee has been paid and verified
     */
    public function hasApplicationFeePaid(): bool
    {
        return $this->applicationFeePayment()
            ->where('status', 'verified')
            ->exists();
    }

    /**
     * Check if registration fee has been paid and verified
     */
    public function hasRegistrationFeePaid(): bool
    {
        return $this->registrationFeePayment()
            ->where('status', 'verified')
            ->exists();
    }

    public static function stageForStatus(string $status): ?string
    {
        return match ($status) {
            self::SUBMITTED,
            self::OFFICER_REVIEW,
            self::OFFICER_APPROVED,
            self::OFFICER_REJECTED,
            self::CORRECTION_REQUESTED,
            self::RETURNED_TO_APPLICANT,
            self::RETURNED_TO_OFFICER,
            self::REGISTRAR_FIX_REQUEST,
            self::SUBMITTED_WITH_APP_FEE,
            self::VERIFIED_BY_OFFICER => 'accreditation_officer',

            self::APPROVED_AWAITING_PAYMENT => 'applicant_payment',

            self::FORWARDED_TO_REGISTRAR,
            self::REGISTRAR_REVIEW,
            self::REGISTRAR_APPROVED,
            self::REGISTRAR_REJECTED,
            self::REGISTRAR_APPROVED_PENDING_REG_FEE => 'registrar',

            self::APP_FEE_VERIFIED => 'accreditation_officer',
            self::AWAITING_REG_FEE => 'applicant_payment',
            self::REGISTERED => 'production',
            self::REG_FEE_PROOF_UPLOADED => 'accounts_payments',

            self::ACCOUNTS_REVIEW,
            self::AWAITING_ACCOUNTS_VERIFICATION,
            self::PAYMENT_VERIFIED,
            self::PAYMENT_REJECTED,
            self::PAID_CONFIRMED,
            self::RETURNED_TO_ACCOUNTS,
            self::PENDING_ACCOUNTS_FROM_REGISTRAR => 'accounts_payments',

            self::PRODUCTION_QUEUE,
            self::CARD_GENERATED,
            self::CERT_GENERATED,
            self::CERT_GENERATED_LEGACY,
            self::PRINTED,
            self::ISSUED => 'production',

            default => null,
        };
    }

    /**
     * Unified application bucket labels used across dashboards/filters.
     */
    public static function bucketLabels(): array
    {
        return [
            'new_accreditation_local' => 'New Accreditation Local',
            'new_accreditation_foreign' => 'New Accreditation Foreign',
            'new_registration_local' => 'New Registration Local',
            'new_registration_foreign' => 'New Registration Foreign',
            'renewal_accreditation_local' => 'Renewal Accreditation Local',
            'renewal_accreditation_foreign' => 'Renewal Accreditation Foreign',
            'renewal_registration_local' => 'Renewal Registration Local',
            'renewal_registration_foreign' => 'Renewal Registration Foreign',
        ];
    }

    /**
     * Mass media categories for Media House registration approvals.
     */
    public static function massMediaCategories(): array
    {
        return [
            'MC' => 'Community Media',
            'MA' => 'Advertising agency as media service',
            'MF' => 'Local office for foreign media service',
            'MN' => 'National media service publishing newspaper',
            'DG' => 'Internet base media service',
            'MP' => 'Production house as media service',
            'MS' => 'Media service fitting multiple categories',
            'MV' => 'Media service in film and video production',
        ];
    }

    /**
     * Accreditation categories for journalist accreditation approvals.
     */
    public static function accreditationCategories(): array
    {
        return [
            'JE' => 'Local media practitioner employed on full-time basis',
            'JF' => 'Local journalist free-lancing locally',
            'JO' => 'Local journalist running an office for foreign media service',
            'JS' => 'Local journalist stringing for foreign media service',
            'JM' => 'Local journalist reporting both locally and abroad',
            'JP' => 'Local media practitioner in content creation, photography, public relations and all forms for of digital media',
            'JD' => 'Local media practitioner in digital social media',
            'JT' => 'Foreign journalist on temporary permit',
        ];
    }

    public function applicationTypeLabel(): string
    {
        $scope = $this->journalist_scope ?: 'local';
        $key = self::bucketKey((string)$this->request_type, (string)$this->application_type, (string)$scope);
        return self::bucketLabels()[$key] ?? Str::headline($this->request_type.' '.$this->application_type.' '.$scope);
    }

    public function categoryLabel(): ?string
    {
        if ($this->application_type === 'registration') {
            return self::massMediaCategories()[$this->media_house_category_code] ?? null;
        }
        return self::accreditationCategories()[$this->accreditation_category_code] ?? null;
    }

    /**
     * Convert core fields into a bucket key.
     */
    public static function bucketKey(string $requestType, string $applicationType, ?string $scope): ?string
    {
        $requestType = strtolower($requestType);
        $applicationType = strtolower($applicationType);
        $scope = strtolower($scope ?? 'local'); // Default NULL to local

        // Normalize: some DBs might store "accreditation" / "registration"
        if (!in_array($requestType, ['new','renewal'], true)) {
            return null;
        }
        if (!in_array($applicationType, ['accreditation','registration'], true)) {
            return null;
        }
        if (!in_array($scope, ['local','foreign'], true)) {
            return null;
        }

        return $requestType . '_' . $applicationType . '_' . $scope;
    }

    /**
     * Apply bucket filtering on a query.
     */
    public function scopeApplyBucket($query, ?string $bucket)
    {
        if (!$bucket) return $query;
        $bucket = strtolower(trim($bucket));
        $labels = array_keys(self::bucketLabels());
        if (!in_array($bucket, $labels, true)) return $query;

        [$requestType, $applicationType, $scope] = explode('_', $bucket);

        $query->where('request_type', $requestType)
              ->where('application_type', $applicationType);

        if ($scope === 'local') {
            $query->where(function ($w) {
                $w->where('journalist_scope', 'local')
                  ->orWhereNull('journalist_scope');
            });
        } else {
            $query->where('journalist_scope', $scope);
        }

        return $query;
    }

    /**
     * Generate a public accreditation/registration number.
     * Format: first category letter + DDMM + 4 random digits + YY + last category letter.
     * Example for JE issued on 30 October 2026: J3010XXXX26E.
     */
    public function generateFormattedNumber(): string
    {
        // Get category code
        $categoryCode = ($this->application_type === 'registration')
            ? ($this->media_house_category_code ?? 'MS')
            : ($this->accreditation_category_code ?? 'JE');

        // Ensure category code is exactly 2 characters
        if (strlen($categoryCode) < 2) {
            $categoryCode = str_pad($categoryCode, 2, $categoryCode);
        } elseif (strlen($categoryCode) > 2) {
            $categoryCode = substr($categoryCode, 0, 2);
        }

        $now = now();
        $prefix = substr($categoryCode, 0, 1);
        $suffix = substr($categoryCode, -1);
        $date = $now->format('dm');
        $year = $now->format('y');
        $table = $this->application_type === 'registration' ? 'registration_records' : 'accreditation_records';
        $column = $this->application_type === 'registration' ? 'registration_no' : 'certificate_no';

        // Retry on the very unlikely event of a random collision.
        do {
            $randomDigits = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $number = strtoupper($prefix . $date . $randomDigits . $year . $suffix);
        } while (\DB::table($table)->where($column, $number)->exists());

        return $number;
    }
}
