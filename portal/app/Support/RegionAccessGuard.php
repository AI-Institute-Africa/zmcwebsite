<?php

namespace App\Support;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegionAccessGuard
{
    public const SCOPED_ROLES = ['accreditation_officer', 'production'];

    public const UNSCOPED_ROLES = [
        'registrar',
        'accounts_payments',
        'director',
        'auditor',
        'it_admin',
        'complaints_officer',
        'pr_officer',
        'public_info_compliance',
        'research_training',
    ];

    public const ADMIN_ROLES = ['super_admin', 'admin'];

    /**
     * Region scoping can be disabled temporarily for controlled testing.
     * Keep the production default enabled; the live test environment can set
     * ZMC_REGION_SCOPE_ENABLED=false without changing officer assignments.
     */
    public static function regionScopingEnabled(): bool
    {
        return (bool) config('zmc.region_scope_enabled', true);
    }

    /**
     * Returns true when the given user may access the given application based
     * on collection_region. Privileged (super_admin/admin) users always pass.
     * Users with at least one unscoped role (registrar/accounts/etc.) always
     * pass. Users whose ONLY scoped roles are officer/production must have the
     * application's collection_region in their assigned regions.
     */
    /**
     * Returns true when the user's access must be region-restricted: i.e. the
     * user is NOT an admin, has NO unscoped role, and DOES have a scoped role
     * (accreditation_officer/production). All other users bypass region scoping.
     */
    public static function userIsScoped(?User $user): bool
    {
        if (!self::regionScopingEnabled()) return false;
        if (!$user) return false;
        if ($user->hasAnyRole(self::ADMIN_ROLES)) return false;
        if ($user->hasAnyRole(self::UNSCOPED_ROLES)) return false;
        return $user->hasAnyRole(self::SCOPED_ROLES);
    }

    public static function userCanAccess(?User $user, ?Application $application): bool
    {
        if (!$user || !$application) return false;

        if (!self::regionScopingEnabled()) return true;

        if ($user->hasAnyRole(self::ADMIN_ROLES)) return true;
        if ($user->hasAnyRole(self::UNSCOPED_ROLES)) return true;

        if (!$user->hasAnyRole(self::SCOPED_ROLES)) {
            return true;
        }

        $regions = $user->assignedCollectionRegions();
        $appRegion = $application->collection_region
            ? strtolower(trim($application->collection_region))
            : null;

        return !empty($regions) && $appRegion && in_array($appRegion, $regions, true);
    }

    /**
     * Aborts with 403 when the currently authenticated user cannot access the
     * given application's region.
     */
    public static function ensure(?Application $application, ?string $message = null): void
    {
        $user = Auth::user();
        if (!self::userCanAccess($user, $application)) {
            abort(403, $message ?: 'This application is outside your assigned region(s).');
        }
    }
}
