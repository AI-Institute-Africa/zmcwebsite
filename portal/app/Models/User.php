<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'region',
        'account_type',
        'designation',
        'locale',
        'phone_country_code',
        'phone_number',
        'phone2',
        'id_number',
        'passport_number',
        'social_media',
        'theme',
        'profile_data',
        'account_status',
        'approved_at',
        'approved_by',
        'otp_code',
        'otp_expires_at',
        'activation_token',
        'activated_at',
        'email_verified_at',
        'email_verification_token',
        'email_verification_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'temp_password_expires_at' => 'datetime',
            'activated_at' => 'datetime',
            'password' => 'hashed',
            'profile_data' => 'array',
            'social_media' => 'array',
        ];
    }

    /**
     * Get the role attribute - fallback to first Spatie role if role field is null
     */
    public function getRoleAttribute($value)
    {
        // If role field is set, return it
        if ($value) {
            return $value;
        }

        // Otherwise, return the first Spatie role
        if ($this->relationLoaded('roles') && $this->roles->isNotEmpty()) {
            return $this->roles->first()->name;
        }

        // Load roles if not loaded and return first
        if (method_exists($this, 'roles')) {
            $firstRole = $this->roles()->first();
            return $firstRole ? $firstRole->name : null;
        }

        return null;
    }

    /* ==========================
     * Relationships
     * ========================== */

    public function assignedRegions()
    {
        return $this->belongsToMany(Region::class, 'officer_regions', 'user_id', 'region_id');
    }

    /**
     * Lowercase region names for matching against Application::collection_region.
     * Returns empty array if the user has no regional jurisdiction (which means
     * they should NOT see any region-scoped applications).
     */
    public function assignedCollectionRegions(): array
    {
        return $this->assignedRegions()
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim((string) $n)))
            ->filter()
            ->values()
            ->all();
    }

    public function officerRegions()
    {
        return $this->hasMany(\App\Models\OfficerRegion::class);
    }

    public function assignedApplications()
    {
        return $this->hasMany(\App\Models\Application::class, 'assigned_officer_id');
    }

    public function applications()
    {
        return $this->hasMany(\App\Models\Application::class, 'applicant_user_id');
    }

    public function processedApplications()
    {
        return $this->hasMany(\App\Models\Application::class, 'last_action_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(\App\Models\ActivityLog::class, 'user_id');
    }

    /* ==========================
     * Portal Access Helpers
     * ========================== */

    /**
     * Check if user is a media practitioner (accreditation applicant).
     * Used to enforce portal separation.
     */
    public function isMediaPractitioner(): bool
    {
        // First check: registered portal type (most reliable)
        if ($this->profile_data && is_array($this->profile_data)) {
            $registeredPortal = $this->profile_data['portal'] ?? null;
            if ($registeredPortal === 'journalist') {
                return true;
            }
        }

        // Check if user has practitioner-related roles
        if ($this->hasAnyRole(['journalist', 'accredited_journalist', 'media_practitioner'])) {
            return true;
        }

        // Check if user has submitted accreditation applications
        return $this->applications()
            ->where('application_type', 'accreditation')
            ->exists();
    }

    /**
     * Check if user is a media house staff member.
     * Used to enforce portal separation.
     */
    public function isMediaHouseStaff(): bool
    {
        // First check: registered portal type (most reliable)
        if ($this->profile_data && is_array($this->profile_data)) {
            $registeredPortal = $this->profile_data['portal'] ?? null;
            if ($registeredPortal === 'mass_media') {
                return true;
            }
        }

        // Check if user is registered as media house staff
        if (MediaHouseStaff::where('media_house_user_id', $this->id)->exists()) {
            return true;
        }

        // Check if user has submitted media house registration applications
        return $this->applications()
            ->where('application_type', 'registration')
            ->exists();
    }

    /**
     * Get the appropriate portal for this user.
     * Returns 'accreditation' or 'mediahouse' or null if user can access both/neither.
     */
    public function getDefaultPortal(): ?string
    {
        if ($this->isMediaPractitioner()) {
            return 'accreditation';
        }

        if ($this->isMediaHouseStaff()) {
            return 'mediahouse';
        }

        return null;
    }
}
