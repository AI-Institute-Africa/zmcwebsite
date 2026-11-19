<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationComment extends Model
{
    protected $fillable = [
        'application_id',
        'user_id',
        'user_role',
        'visibility',
        'comment_type',
        'body',
        'application_status_at_time',
        'ip_address',
    ];

    public const TYPE_GENERAL   = 'general';
    public const TYPE_REJECTION = 'rejection';
    public const TYPE_RETURN    = 'return';
    public const TYPE_FIX       = 'fix_request';
    public const TYPE_OVERRIDE  = 'override';
    public const TYPE_ESCALATION = 'escalation';
    public const TYPE_WAIVER    = 'waiver';
    public const TYPE_PAYMENT   = 'payment';

    public const VISIBILITY_INTERNAL = 'internal';
    public const VISIBILITY_APPLICANT = 'applicant';

    public static function commentTypes(): array
    {
        return [
            self::TYPE_GENERAL    => 'General Note',
            self::TYPE_REJECTION  => 'Rejection Reason',
            self::TYPE_RETURN     => 'Return Note',
            self::TYPE_FIX        => 'Returned',
            self::TYPE_OVERRIDE   => 'Override Note',
            self::TYPE_ESCALATION => 'Escalation',
            self::TYPE_WAIVER     => 'Waiver Note',
            self::TYPE_PAYMENT    => 'Payment Note',
            'profile_change_request' => 'Profile Change Request',
            'amendment_request' => 'Applicant Amendment Request',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabel(): string
    {
        return self::commentTypes()[$this->comment_type] ?? ucfirst($this->comment_type);
    }

    public function getTypeBadgeColor(): string
    {
        return match($this->comment_type) {
            self::TYPE_REJECTION  => 'danger',
            self::TYPE_RETURN     => 'warning',
            self::TYPE_FIX        => 'info',
            self::TYPE_OVERRIDE   => 'dark',
            self::TYPE_ESCALATION => 'purple',
            self::TYPE_WAIVER     => 'secondary',
            self::TYPE_PAYMENT    => 'success',
            'profile_change_request' => 'info',
            'amendment_request' => 'warning',
            default               => 'primary',
        };
    }

    public function getRoleBadgeColor(): string
    {
        return match($this->user_role) {
            'accreditation_officer' => 'primary',
            'registrar'            => 'info',
            'accounts_payments'    => 'success',
            'super_admin'          => 'danger',
            'director'             => 'dark',
            'auditor'              => 'secondary',
            'it_admin'             => 'warning',
            default                => 'light',
        };
    }

    public function getRoleLabel(): string
    {
        return match($this->user_role) {
            'accreditation_officer' => 'Accreditation Officer',
            'registrar'            => 'Registrar',
            'accounts_payments'    => 'Accounts Officer',
            'super_admin'          => 'Super Admin',
            'director'             => 'Director',
            'auditor'              => 'Auditor',
            'it_admin'             => 'IT Admin',
            'production'           => 'Production',
            default                => ucfirst(str_replace('_', ' ', $this->user_role)),
        };
    }
}
