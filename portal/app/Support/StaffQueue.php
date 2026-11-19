<?php

namespace App\Support;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * "My queue" numbers for staff dashboards and sidebar badges.
 * Read-only: counts applications that are waiting on a given staff role.
 * Respects region scoping (accreditation officer / production) and the SLA
 * hours already configured in config('zmc.workflow.sla_hours').
 */
class StaffQueue
{
    private const DEFAULT_SLA_HOURS = 72;

    /** role => [statuses waiting on that role, route to open the queue, label] */
    public static function definitions(): array
    {
        return [
            'accreditation_officer' => [
                'label' => 'Applications waiting for your review',
                'route' => 'staff.officer.applications.new-apps',
                'statuses' => [
                    Application::SUBMITTED,
                    Application::SUBMITTED_WITH_APP_FEE,
                    Application::OFFICER_REVIEW,
                    Application::RETURNED_TO_OFFICER,
                ],
            ],
            'registrar' => [
                'label' => 'Applications waiting for registrar decision',
                'route' => 'staff.registrar.applications.list',
                'statuses' => [
                    Application::REGISTRAR_REVIEW,
                    Application::FORWARDED_TO_REGISTRAR,
                ],
            ],
            'accounts_payments' => [
                'label' => 'Payments waiting for verification',
                'route' => 'staff.accounts.app-fee-verifications',
                'statuses' => [
                    Application::ACCOUNTS_REVIEW,
                    Application::AWAITING_ACCOUNTS_VERIFICATION,
                    Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
                    Application::REG_FEE_PROOF_UPLOADED,
                ],
            ],
            'production' => [
                'label' => 'Cards and certificates waiting to be produced',
                'route' => 'staff.production.queue',
                'statuses' => [
                    Application::PRODUCTION_QUEUE,
                    Application::PRODUCTION_APPROVED,
                ],
            ],
        ];
    }

    private static function baseQuery(?User $user, array $statuses)
    {
        $q = Application::query()->whereIn('status', $statuses);

        if ($user && RegionAccessGuard::userIsScoped($user)) {
            $regions = $user->assignedCollectionRegions();
            if (empty($regions)) {
                $q->whereRaw('1 = 0');
            } else {
                $q->whereIn(\DB::raw('LOWER(TRIM(collection_region))'), $regions);
            }
        }

        return $q;
    }

    /**
     * @return array{label:string,route:?string,url:?string,waiting:int,overdue:int,today:int,oldest_hours:?int}|null
     */
    public static function summary(?User $user, ?string $role): ?array
    {
        $defs = self::definitions();
        if (!$user || !$role || !isset($defs[$role])) {
            return null;
        }
        $def = $defs[$role];

        return Cache::remember("staff_queue:{$role}:{$user->id}", 60, function () use ($user, $def) {
            $sla = (array) config('zmc.workflow.sla_hours', []);

            $waiting = (int) self::baseQuery($user, $def['statuses'])->count();

            // Overdue = sitting in a stage longer than that stage's SLA hours.
            $overdue = 0;
            foreach ($def['statuses'] as $status) {
                $hours = (int) ($sla[$status] ?? self::DEFAULT_SLA_HOURS);
                $overdue += (int) self::baseQuery($user, [$status])
                    ->where('updated_at', '<', now()->subHours($hours))
                    ->count();
            }

            $today = (int) self::baseQuery($user, $def['statuses'])
                ->where('updated_at', '>=', now()->subDay())
                ->count();

            $oldest = self::baseQuery($user, $def['statuses'])->min('updated_at');
            $oldestHours = $oldest ? (int) abs(now()->diffInHours(\Carbon\Carbon::parse($oldest))) : null;

            return [
                'label' => $def['label'],
                'route' => $def['route'],
                'url' => ($def['route'] && Route::has($def['route'])) ? route($def['route']) : null,
                'waiting' => $waiting,
                'overdue' => $overdue,
                'today' => $today,
                'oldest_hours' => $oldestHours,
            ];
        });
    }

    /**
     * Sidebar count badges: [url path => count]. Empty when the role has no queue.
     */
    public static function sidebarBadges(?User $user, ?string $role): array
    {
        $summary = self::summary($user, $role);
        if (!$summary || !$summary['url'] || $summary['waiting'] < 1) {
            return [];
        }
        $path = parse_url($summary['url'], PHP_URL_PATH) ?: null;
        return $path ? [$path => $summary['waiting']] : [];
    }
}
