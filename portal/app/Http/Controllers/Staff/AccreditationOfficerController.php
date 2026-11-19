<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationWorkflow;
use App\Services\ApplicationWorkflowService;
use App\Services\ActivityLogger;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccreditationOfficerController extends Controller
{
    /**
     * Officer dashboard statuses (your list + submitted).
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        $isSuperAdmin = $user->hasAnyRole(['super_admin', 'admin']);
        $isScopedUser = \App\Support\RegionAccessGuard::userIsScoped($user);

        // Officers see applications from their assigned regions only.
        // Super admin / unscoped roles see all; an officer with no assigned regions sees none.
        $assignedRegions = $isScopedUser ? $user->assignedCollectionRegions() : [];

        $officerScope = function ($query) use ($isScopedUser, $user, $assignedRegions) {
            if ($isScopedUser) {
                $query->where(function ($sq) use ($user) {
                    $sq->whereNull('assigned_officer_id')
                        ->orWhere('assigned_officer_id', $user->id);
                });

                if (empty($assignedRegions)) {
                    $query->whereRaw('1=0');
                } else {
                    $query->whereIn('collection_region', $assignedRegions);
                }
            }
        };

        // Active officer queue: only statuses where the officer can take action.
        // Statuses that were sent back to the applicant (correction_requested,
        // returned_to_applicant) are intentionally excluded — those files leave
        // the staff queue until the applicant resubmits (status returns to
        // SUBMITTED) so officers don't see items they can't act on.
        $pendingStatuses = [
            Application::SUBMITTED,
            Application::OFFICER_REVIEW,
            Application::RETURNED_TO_OFFICER,
            Application::REGISTRAR_FIX_REQUEST,
        ];

        $officerStatuses = [
            Application::SUBMITTED,
            Application::OFFICER_REVIEW,
            Application::RETURNED_TO_OFFICER,
            Application::REGISTRAR_FIX_REQUEST,
            Application::SUBMITTED_WITH_APP_FEE,
            // Keep completed hand-offs and returned files visible in the
            // officer list. Action buttons remain guarded by workflow status.
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::CORRECTION_REQUESTED,
            Application::RETURNED_TO_APPLICANT,
        ];

        $statuses = $officerStatuses;

        if ($isSuperAdmin) {
            $allStatuses = Application::where('status', '!=', 'draft')
                ->distinct()
                ->pluck('status')
                ->toArray();
            $statuses = array_unique(array_merge($statuses, $allStatuses));
        }

        $kpis = [
            'total_applications' => Application::whereIn('status', $statuses)
                ->where('status', '!=', 'draft')
                ->tap($officerScope)
                ->count(),
            'pending_applications' => Application::whereIn('status', $pendingStatuses)
                ->tap($officerScope)
                ->count(),
            'corrections' => Application::whereIn('status', [
                Application::CORRECTION_REQUESTED,
                'corrections_requested',
                'needs_correction'
            ])
                ->tap($officerScope)
                ->count(),
            'new_this_week' => Application::where('created_at', '>=', now()->startOfWeek())
                ->whereIn('status', $statuses)
                ->where('status', '!=', 'draft')
                ->tap($officerScope)
                ->count(),
            'pending_fix_requests' => \App\Models\FixRequest::whereIn('status', ['pending', 'in_progress'])
                ->when($isScopedUser, function ($q) use ($user, $assignedRegions) {
                    $q->whereHas('application', function ($sq) use ($user, $assignedRegions) {
                        $sq->where(function ($asq) use ($user) {
                            $asq->where('assigned_officer_id', $user->id)
                                ->orWhereNull('assigned_officer_id');
                        });
                        if (empty($assignedRegions)) {
                            $sq->whereRaw('1=0');
                        } else {
                            $sq->whereIn('collection_region', $assignedRegions);
                        }
                    });
                })
                ->count(),

            'media_practitioners_total' => (class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records'))
                ? \App\Models\AccreditationRecord::count()
                : 0,
            'media_practitioners_accredited' => (function () {
                if (!(class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records')))
                    return 0;
                $q = \App\Models\AccreditationRecord::whereIn('status', ['active', 'issued'])->whereNotNull('issued_at');
                return $q->count();
            })(),

            'media_houses_total' => (class_exists(\App\Models\RegistrationRecord::class) && Schema::hasTable('registration_records'))
                ? \App\Models\RegistrationRecord::count()
                : 0,
            'media_houses_registered' => (function () {
                if (!(class_exists(\App\Models\RegistrationRecord::class) && Schema::hasTable('registration_records')))
                    return 0;
                $q = \App\Models\RegistrationRecord::whereIn('status', ['active', 'issued'])->whereNotNull('issued_at');
                return $q->count();
            })(),
        ];

        $applications = Application::query()
            ->with(['applicant', 'documents'])
            ->withCount('documents')
            ->whereIn('status', $statuses)
            ->when($isScopedUser, function ($q) use ($user, $assignedRegions) {
                $q->where(function ($sq) use ($user) {
                    $sq->whereNull('assigned_officer_id')
                        ->orWhere('assigned_officer_id', $user->id);
                })
                    ->where(function ($sq) use ($user) {
                        $sq->whereNull('locked_at')
                            ->orWhere('locked_at', '<=', now()->subHours(2))
                            ->orWhere('locked_by', $user->id);
                    });

                if (empty($assignedRegions)) {
                    $q->whereRaw('1=0');
                } else {
                    $q->whereIn('collection_region', $assignedRegions);
                }
            })
            ->where('status', '!=', 'draft')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Renewals due (expiring within 90 days)
        $cutoff = now()->addDays(90);
        $expiringJournalists = collect();
        $expiringMediaHouses = collect();

        if (class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records')) {
            $ejq = \App\Models\AccreditationRecord::query()
                ->with('holder')
                ->whereNotNull('expires_at')
                ->where('expires_at', '>=', now())
                ->where('expires_at', '<=', $cutoff);
            $this->scopeRecordsByRegion($ejq);
            $expiringJournalists = $ejq->orderBy('expires_at')->limit(10)->get();
        }

        if (class_exists(\App\Models\RegistrationRecord::class) && Schema::hasTable('registration_records')) {
            $emq = \App\Models\RegistrationRecord::query()
                ->with('contact')
                ->whereNotNull('expires_at')
                ->where('expires_at', '>=', now())
                ->where('expires_at', '<=', $cutoff);
            $this->scopeRecordsByRegion($emq);
            $expiringMediaHouses = $emq->orderBy('expires_at')->limit(10)->get();
        }

        // Inactive journalists (2-3 years without login)
        $inactiveJournalists = collect();
        $twoYearsAgo = now()->subYears(2);
        $threeYearsAgo = now()->subYears(3);

        if (class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records')) {
            $ijq = \App\Models\AccreditationRecord::query()
                ->join('users', 'accreditation_records.holder_user_id', '=', 'users.id')
                ->select('accreditation_records.*', 'users.updated_at as last_activity')
                ->with('holder')
                ->whereNotNull('accreditation_records.holder_user_id')
                ->where('users.updated_at', '<=', $twoYearsAgo)
                ->where('users.updated_at', '>=', $threeYearsAgo);
            $this->scopeRecordsByRegion($ijq);
            $inactiveJournalists = $ijq->orderBy('users.updated_at')->limit(10)->get();
        }

        $accAppQ = Application::where('application_type', 'accreditation');
        $regAppQ = Application::where('application_type', 'registration');
        $this->scopeApplicationsByRegion($accAppQ);
        $this->scopeApplicationsByRegion($regAppQ);
        $analyticsTotals = [
            'public_users' => \App\Models\User::where('account_type', 'public')->count(),
            'staff_users' => \App\Models\User::where('account_type', '!=', 'public')->count(),
            'accreditation' => $accAppQ->count(),
            'registration' => $regAppQ->count(),
        ];

        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $appTrendsQ = Application::selectRaw("date(created_at) as d, application_type, count(*) as cnt")
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupByRaw('date(created_at), application_type');
        $this->scopeApplicationsByRegion($appTrendsQ);
        $appTrends = $appTrendsQ->get()->groupBy('application_type');

        $userTrends = \App\Models\User::selectRaw("date(created_at) as d, count(*) as cnt")
            ->where('account_type', 'public')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupByRaw('date(created_at)')
            ->pluck('cnt', 'd');

        $accreditationByDate = ($appTrends['accreditation'] ?? collect())->pluck('cnt', 'd');
        $registrationByDate = ($appTrends['registration'] ?? collect())->pluck('cnt', 'd');

        $trendLabels = [];
        $trendAccreditation = [];
        $trendRegistration = [];
        $trendPublicUsers = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trendLabels[] = now()->subDays($i)->format('M d');
            $trendAccreditation[] = (int) ($accreditationByDate[$date] ?? 0);
            $trendRegistration[] = (int) ($registrationByDate[$date] ?? 0);
            $trendPublicUsers[] = (int) ($userTrends[$date] ?? 0);
        }

        $statusGroupsQ = Application::selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->orderByDesc('cnt')
            ->limit(8);
        $this->scopeApplicationsByRegion($statusGroupsQ);
        $statusGroups = $statusGroupsQ->pluck('cnt', 'status');

        $statusLabels = $statusGroups->keys()->map(fn($s) => ucwords(str_replace('_', ' ', $s)))->values()->toArray();
        $statusCounts = $statusGroups->values()->toArray();

        $analyticsChartData = [
            'labels' => $trendLabels,
            'datasets' => [
                'accreditation' => $trendAccreditation,
                'registration' => $trendRegistration,
                'public_users' => $trendPublicUsers,
            ],
            'status_breakdown' => [
                'labels' => $statusLabels,
                'counts' => $statusCounts,
            ],
        ];

        return view('staff.officer.dashboard', compact('applications', 'kpis', 'expiringJournalists', 'expiringMediaHouses', 'inactiveJournalists', 'analyticsTotals', 'analyticsChartData'));
    }

    /**
     * Apply region scoping directly to an Application query.
     * Bypasses for super_admin/admin and any UNSCOPED_ROLE (registrar, accounts, director, etc.).
     * Only restricts users whose roles are limited to accreditation_officer/production.
     */
    private function scopeApplicationsByRegion($query): void
    {
        $user = Auth::user();
        if (!$user)
            return;
        if ($user->hasAnyRole(\App\Support\RegionAccessGuard::ADMIN_ROLES))
            return;
        if ($user->hasAnyRole(\App\Support\RegionAccessGuard::UNSCOPED_ROLES))
            return;
        if (!$user->hasAnyRole(\App\Support\RegionAccessGuard::SCOPED_ROLES))
            return;

        $regions = $user->assignedCollectionRegions();
        if (empty($regions)) {
            $query->whereRaw('1=0');
        } else {
            $query->whereIn('collection_region', $regions);
        }
    }

    /**
     * Apply region scoping to an AccreditationRecord/RegistrationRecord query
     * via the related application's collection_region. Same bypass rules as
     * scopeApplicationsByRegion.
     */
    private function scopeRecordsByRegion($query): void
    {
        $user = Auth::user();
        if (!$user)
            return;
        if ($user->hasAnyRole(\App\Support\RegionAccessGuard::ADMIN_ROLES))
            return;
        if ($user->hasAnyRole(\App\Support\RegionAccessGuard::UNSCOPED_ROLES))
            return;
        if (!$user->hasAnyRole(\App\Support\RegionAccessGuard::SCOPED_ROLES))
            return;

        $regions = $user->assignedCollectionRegions();
        if (empty($regions)) {
            $query->whereRaw('1=0');
            return;
        }

        $query->where(function ($outer) use ($regions) {
            // Legacy register entries may have no linked application at all;
            // there is no region to compare, so keep them visible.
            $outer->whereDoesntHave('application')
                ->orWhereHas('application', function ($q) use ($regions) {
                    // Older official register entries may predate collection-region
                    // assignment. Keep those records visible; region-tagged records
                    // remain restricted to the officer's assigned regions.
                    $q->where(function ($regionQuery) use ($regions) {
                        $regionQuery->whereIn('collection_region', $regions)
                            ->orWhereNull('collection_region');
                    });
                });
        });
    }

    /**
     * Region authorization guard. Officers may only act on applications whose
     * collection_region matches one of their assigned regions. Super admin
     * bypasses. An officer with no assigned regions cannot access any application.
     */
    private function ensureCanAccessRegion(Application $application): void
    {
        \App\Support\RegionAccessGuard::ensure($application);
    }

    public function show(Application $application)
    {
        $this->ensureCanAccessRegion($application);

        // Statuses that come from the fix-request / returned queue — never hard-block on lock
        $bypassLockStatuses = [
            Application::REGISTRAR_FIX_REQUEST,
            Application::RETURNED_TO_OFFICER,
            Application::CORRECTION_REQUESTED,
            Application::RETURNED_TO_APPLICANT,
        ];

        $skipLock = in_array($application->status, $bypassLockStatuses, true);

        // Also release stale locks (older than 2 hours) automatically
        if (!$skipLock && $application->locked_by && $application->locked_at) {
            $lockedAt = \Carbon\Carbon::parse($application->locked_at);
            if ($lockedAt->diffInHours(now()) >= 2) {
                $application->unlock();
            }
        }

        if (!$skipLock) {
            if (!$application->claim(auth()->user())) {
                $lockerName = $application->lockedBy ? $application->lockedBy->name : 'another officer';
                return redirect()->back()->with('error', "This application is currently being reviewed by {$lockerName}. Please try again later.");
            }
        }

        $application->load([
            'applicant',
            'documents',
            'messages',
            'workflowLogs',
            'lockedBy',
            'paymentSubmissions',
            'payments',
            'comments.user',
        ]);

        $prevQ = Application::where('applicant_user_id', $application->applicant_user_id)
            ->where('id', '!=', $application->id)
            ->latest();
        $this->scopeApplicationsByRegion($prevQ);
        $previousApplications = $prevQ->get();

        $userPayments = \App\Models\Payment::where('payer_user_id', $application->applicant_user_id)
            ->latest()
            ->get();

        return view('staff.officer.show', compact('application', 'previousApplications', 'userPayments'));
    }

    public function downloadApplicationDocumentsZip(Application $application)
    {
        $this->ensureCanAccessRegion($application);
        return app(\App\Http\Controllers\Staff\RegistrarController::class)->downloadDocumentsZip($application);
    }

    public function unlock(Application $application)
    {
        $this->ensureCanAccessRegion($application);
        if ($application->locked_by === auth()->id()) {
            $application->unlock();
            return redirect()->route('staff.officer.dashboard')->with('success', 'Application released back to pool.');
        }
        return back();
    }

    public function openReview(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $from = $application->status;

        if ($application->status !== Application::OFFICER_REVIEW) {
            ApplicationWorkflow::transition($application, Application::OFFICER_REVIEW, 'officer_open_review');
        }

        $application->refresh();
        $this->audit('officer_open_review', $application, $from, $application->status);

        return back()->with('success', 'Application moved to Officer Review.');
    }

    /**
     * ✅ Officer Approve -> PUSH TO REGISTRAR DASHBOARD (registrar_review)
     */
    public function approve(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:5000'],
            'category_code' => ['required', 'string', 'max:10'],
        ]);


        if ($application->application_type === 'registration') {
            $allowed = array_keys(Application::massMediaCategories());
            abort_unless(in_array($data['category_code'], $allowed, true), 422, 'Invalid media house category.');
            if (Schema::hasColumn('applications', 'media_house_category_code')) {
                $application->media_house_category_code = $data['category_code'];
            }
        } else {
            $allowed = array_keys(Application::accreditationCategories());
            abort_unless(in_array($data['category_code'], $allowed, true), 422, 'Invalid accreditation category.');
            if (Schema::hasColumn('applications', 'accreditation_category_code')) {
                $application->accreditation_category_code = $data['category_code'];
            }
        }
        $application->save();

        // Use new workflow service - enforces strict transitions
        try {
            $application = ApplicationWorkflowService::approveApplication($application, [
                'notes' => $data['decision_notes'] ?? null,
                'category_code' => $data['category_code'],
            ]);


            return back()->with('success', 'Application approved and forwarded to the Registrar for review.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }


    public function reject(Request $request, Application $application)
    {
        abort(403, 'Accreditation Officers cannot reject applications. Use Request Correction or forward to Registrar.');
    }

    public function requestCorrection(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        // Use new workflow service - enforces strict transitions
        try {
            $application = ApplicationWorkflowService::returnToApplicant($application, $data['notes'], [
                'actor_role' => session('active_staff_role', 'accreditation_officer'),
            ]);

            $this->persistMessageIfAvailable($application, $data['notes']);

            return back()->with('success', 'Correction requested. Application returned to applicant.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }

    public function processedApplications(Request $request)
    {
        $processedStatuses = [
            Application::PRODUCTION_QUEUE,
            Application::CARD_GENERATED,
            Application::CERT_GENERATED,
            Application::PRODUCED_READY,
            Application::PRINTED,
            Application::ISSUED,
        ];

        $query = Application::query()
            ->with(['applicant'])
            ->whereIn('status', $processedStatuses)
            ->latest('updated_at');

        // Region scoping: officers see only their assigned region(s).
        $this->scopeApplicationsByRegion($query);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                    ->orWhereHas('applicant', fn($aq) => $aq->where('name', 'like', "%$s%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('application_type', $request->type);
        }

        $applications = $query->paginate(20)->withQueryString();
        $processedStatusLabels = [
            Application::PRODUCTION_QUEUE => 'In Production Queue',
            Application::CARD_GENERATED => 'Card Generated',
            Application::CERT_GENERATED => 'Certificate Generated',
            Application::PRODUCED_READY => 'Produced & Ready',
            Application::PRINTED => 'Printed',
            Application::ISSUED => 'Issued / Collected',
        ];

        $officers = User::whereIn('account_type', ['staff', 'admin'])
            ->orderBy('name')
            ->get();

        return view('staff.shared.processed_applications', compact('applications', 'processedStatuses', 'processedStatusLabels', 'officers'));
    }

    /**
     * Display paginated list of accredited journalists with filters
     */
    public function accreditedJournalists(Request $request)
    {
        $hasRecords = Schema::hasTable('accreditation_records') && \App\Models\AccreditationRecord::exists();
        $tab = $request->input('tab', 'local');

        $baseAppQuery = Application::where('application_type', 'accreditation')
            ->whereNotIn('status', ['draft', 'abandoned']);
        $this->scopeApplicationsByRegion($baseAppQuery);

        if ($hasRecords) {
            $query = \App\Models\AccreditationRecord::query()
                ->with(['holder', 'application.assignedOfficer', 'application.registrarApprover'])
                ->addSelect([
                    'extensions_count' => Application::selectRaw('count(*)')
                        ->whereColumn('applicant_user_id', 'accreditation_records.holder_user_id')
                        ->where('application_type', 'renewal')
                        ->whereIn('status', [Application::ISSUED, Application::PRINTED, Application::PRODUCED_READY, Application::PAID_CONFIRMED])
                ])
                ->orderBy('issued_at', 'desc');
            $this->scopeRecordsByRegion($query);

            // Tab filtering
            if ($tab === 'local') {
                $query->where(function ($q) {
                    $q->whereNull('nationality')->orWhere('nationality', 'Zimbabwean');
                });
            } elseif ($tab === 'foreign') {
                $query->where(function ($q) {
                    $q->whereNotNull('nationality')->where('nationality', '!=', 'Zimbabwean');
                });
            }

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('certificate_no', 'like', "%{$s}%")
                        ->orWhere('accreditation_number', 'like', "%{$s}%")
                        ->orWhere('first_name', 'like', "%{$s}%")
                        ->orWhere('surname', 'like', "%{$s}%")
                        ->orWhere('id_number', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('organisation', 'like', "%{$s}%")
                        ->orWhereHas('holder', fn($uq) => $uq->where('name', 'like', "%{$s}%"));
                });
            }
            if ($request->filled('category')) {
                $query->where('category', $request->input('category'));
            }
            if ($request->filled('sex')) {
                $query->where('sex', $request->input('sex'));
            }
            if ($request->filled('nationality')) {
                $query->where('nationality', 'like', "%{$request->input('nationality')}%");
            }
            if ($request->filled('province')) {
                $query->where('province', 'like', "%{$request->input('province')}%");
            }
            if ($request->filled('medium')) {
                $query->where('medium', 'like', "%{$request->input('medium')}%");
            }
            if ($request->filled('organisation')) {
                $query->where('organisation', 'like', "%{$request->input('organisation')}%");
            }
            if ($request->filled('year')) {
                $query->where('accreditation_year', (int) $request->input('year'));
            }
            if ($request->filled('month')) {
                $query->whereMonth('issued_at', $request->month);
            }
            if ($request->filled('collection_status')) {
                $cs = $request->input('collection_status');
                if ($cs === 'collected')
                    $query->whereNotNull('collected_at');
                elseif ($cs === 'uncollected')
                    $query->whereNull('collected_at');
            }
            if ($request->filled('expiry_status')) {
                $es = $request->input('expiry_status');
                if ($es === 'expiring_soon') {
                    $query->whereNotNull('expires_at')->where('expires_at', '>=', now())->where('expires_at', '<=', now()->addDays(90));
                } elseif ($es === 'expired') {
                    $query->whereNotNull('expires_at')->where('expires_at', '<', now());
                } elseif ($es === 'active') {
                    $query->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                    });
                }
            }

            if ($request->filled('collection_region')) {
                $query->whereHas('application', fn($aq) => $aq->where('collection_region', $request->collection_region));
            }

            if ($request->filled('officer_id')) {
                $query->whereHas('application', fn($aq) => $aq->where('assigned_officer_id', $request->officer_id));
            }
            if ($request->filled('is_extension')) {
                $val = $request->is_extension === 'yes';
                $query->where(function ($q) use ($val) {
                    $sub = Application::selectRaw('1')
                        ->whereColumn('applicant_user_id', 'accreditation_records.holder_user_id')
                        ->where('application_type', 'renewal')
                        ->whereIn('status', [Application::ISSUED, Application::PRINTED, Application::PRODUCED_READY, Application::PAID_CONFIRMED]);
                    if ($val)
                        $q->whereRaw('exists (' . $sub->toSql() . ')', $sub->getBindings());
                    else
                        $q->whereRaw('not exists (' . $sub->toSql() . ')', $sub->getBindings());
                });
            }

            $journalists = $query->paginate(25)->appends($request->query());

            $allRecords = \App\Models\AccreditationRecord::query();
            $this->scopeRecordsByRegion($allRecords);
            $stats = [
                'total' => $allRecords->count(),
                'collected' => (clone $allRecords)->whereNotNull('collected_at')->count(),
                'uncollected' => (clone $allRecords)->whereNull('collected_at')->count(),
                'expired' => (clone $allRecords)->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
                'active' => (clone $allRecords)->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()); })->count(),
                'harare' => (clone $allRecords)->whereHas('application', fn($q) => $q->where('collection_region', 'Harare'))->count(),
                'bulawayo' => (clone $allRecords)->whereHas('application', fn($q) => $q->where('collection_region', 'Bulawayo'))->count(),
            ];

            $foBase = function () {
                $q = \App\Models\AccreditationRecord::query();
                $this->scopeRecordsByRegion($q);
                return $q;
            };
            $firstRecordDate = $foBase()->min('created_at');
            $firstYear = $firstRecordDate ? (int) date('Y', strtotime($firstRecordDate)) : now()->year;
            $allYears = collect(range(now()->year + 1, $firstYear))->toArray();

            $filterOptions = [
                'categories' => collect(['JE', 'JF', 'JO', 'JS', 'JM', 'JP', 'JD', 'JT'])->merge(
                    $foBase()->whereNotNull('category')
                        ->whereNotIn('category', ['Newspaper', 'Broadcast', 'Online', 'Print', 'newspaper', 'broadcast', 'online', 'print'])
                        ->distinct()
                        ->pluck('category')
                )->unique()->sort()->values()->toArray(),
                'provinces' => $foBase()->whereNotNull('province')->where('province', '!=', '')->distinct()->pluck('province')->sort()->values()->toArray(),
                'nationalities' => $foBase()->whereNotNull('nationality')->where('nationality', '!=', '')->distinct()->pluck('nationality')->sort()->values()->toArray(),
                'media' => $foBase()->whereNotNull('medium')->where('medium', '!=', '')->distinct()->pluck('medium')->sort()->values()->toArray(),
                'organisations' => $foBase()->whereNotNull('organisation')->where('organisation', '!=', '')->distinct()->pluck('organisation')->sort()->values()->toArray(),
                'years' => $allYears,
                'officers' => \App\Models\User::role('accreditation_officer')->pluck('name', 'id')->toArray(),
                'collection_regions' => collect(['Harare', 'Bulawayo'])->merge(
                    Application::whereNotNull('collection_region')
                        ->where('collection_region', '!=', '')
                        ->distinct()
                        ->pluck('collection_region')
                )->unique()->sort()->values()->toArray(),
            ];
        } else {
            $appQuery = (clone $baseAppQuery)->with(['applicant', 'assignedOfficer', 'registrarApprover'])->orderBy('submitted_at', 'desc');

            $appQuery->addSelect([
                'extensions_count' => Application::selectRaw('count(*)')
                    ->whereColumn('applicant_user_id', 'applications.applicant_user_id')
                    ->where('application_type', 'renewal')
                    ->whereIn('status', [Application::ISSUED, Application::PRINTED, Application::PRODUCED_READY, Application::PAID_CONFIRMED])
            ]);

            // Tab filtering for applications
            if ($tab === 'local') {
                $appQuery->where(function ($q) {
                    $q->where('residency_type', 'local')->orWhere('journalist_scope', 'local');
                });
            } elseif ($tab === 'foreign') {
                $appQuery->where(function ($q) {
                    $q->where('residency_type', 'foreign')->orWhere('journalist_scope', 'foreign');
                });
            }

            if ($request->filled('search')) {
                $s = $request->input('search');
                $isSqlite = \DB::getDriverName() === 'sqlite';
                $appQuery->where(function ($q) use ($s, $isSqlite) {
                    $q->where('reference', 'like', "%{$s}%");
                    if ($isSqlite) {
                        $q->orWhereRaw("json_extract(form_data, '$.first_name') LIKE ?", ["%{$s}%"])
                            ->orWhereRaw("json_extract(form_data, '$.surname') LIKE ?", ["%{$s}%"])
                            ->orWhereRaw("json_extract(form_data, '$.id_number') LIKE ?", ["%{$s}%"])
                            ->orWhereRaw("json_extract(form_data, '$.employer') LIKE ?", ["%{$s}%"]);
                    } else {
                        $q->orWhereRaw("form_data->>'first_name' LIKE ?", ["%{$s}%"])
                            ->orWhereRaw("form_data->>'surname' LIKE ?", ["%{$s}%"])
                            ->orWhereRaw("form_data->>'id_number' LIKE ?", ["%{$s}%"])
                            ->orWhereRaw("form_data->>'employer' LIKE ?", ["%{$s}%"]);
                    }
                    $q->orWhereHas('applicant', fn($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
                });
            }
            if ($request->filled('category')) {
                $cat = $request->input('category');
                $appQuery->where(function ($q) use ($cat) {
                    $q->whereRaw("form_data->>'category' = ?", [$cat])
                        ->orWhereRaw("form_data->>'medium_type' = ?", [$cat]);
                });
            }
            if ($request->filled('sex')) {
                $appQuery->whereRaw("form_data->>'sex' = ?", [$request->input('sex')]);
            }
            if ($request->filled('year')) {
                $appQuery->whereYear('submitted_at', (int) $request->input('year'));
            }
            if ($request->filled('month')) {
                $appQuery->whereMonth('submitted_at', $request->month);
            }
            if ($request->filled('organisation')) {
                $appQuery->where('form_data', 'like', '%' . $request->organisation . '%');
            }
            if ($request->filled('status_filter')) {
                $sf = $request->input('status_filter');
                if ($sf === 'approved') {
                    $appQuery->whereIn('status', ['approved', 'approved_awaiting_payment', 'payment_verified', 'production_queue', 'certificate_generated', 'ready_for_collection', 'collected', 'completed']);
                } elseif ($sf === 'pending') {
                    $appQuery->whereIn('status', ['submitted', 'submitted_with_app_fee', 'under_review', 'pending_documents', 'pending_payment']);
                } elseif ($sf === 'returned') {
                    $appQuery->whereIn('status', ['returned_for_correction', 'returned_from_registrar']);
                }
            }

            if ($request->filled('collection_region')) {
                $appQuery->where('collection_region', $request->collection_region);
            }

            if ($request->filled('officer_id')) {
                $appQuery->where('assigned_officer_id', $request->officer_id);
            }
            if ($request->filled('is_extension')) {
                $val = $request->is_extension === 'yes';
                $appQuery->where(function ($q) use ($val) {
                    $sub = Application::selectRaw('1')
                        ->whereColumn('applicant_user_id', 'applications.applicant_user_id')
                        ->where('application_type', 'renewal')
                        ->whereIn('status', [Application::ISSUED, Application::PRINTED, Application::PRODUCED_READY, Application::PAID_CONFIRMED]);
                    if ($val)
                        $q->whereRaw('exists (' . $sub->toSql() . ')', $sub->getBindings());
                    else
                        $q->whereRaw('not exists (' . $sub->toSql() . ')', $sub->getBindings());
                });
            }

            $journalists = $appQuery->paginate(25)->appends($request->query());

            $allApps = (clone $baseAppQuery);
            $stats = [
                'total' => $allApps->count(),
                'collected' => (clone $allApps)->whereIn('status', ['collected', 'completed'])->count(),
                'uncollected' => (clone $allApps)->whereIn('status', ['ready_for_collection', 'certificate_generated'])->count(),
                'expired' => 0,
                'active' => (clone $allApps)->whereIn('status', ['approved', 'payment_verified', 'production_queue', 'completed', 'collected'])->count(),
                'harare' => (clone $allApps)->where('collection_region', 'Harare')->count(),
                'bulawayo' => (clone $allApps)->where('collection_region', 'Bulawayo')->count(),
            ];

            $allAppsForCats = (clone $baseAppQuery)->get();
            $firstApplicationDate = (clone $baseAppQuery)->min('created_at');
            $firstYear = $firstApplicationDate ? (int) date('Y', strtotime($firstApplicationDate)) : now()->year;
            $allYears = collect(range(now()->year + 1, $firstYear))->toArray();

            $filterOptions = [
                'categories' => collect(['JE', 'JF', 'JO', 'JS', 'JM', 'JP', 'JD', 'JT'])->merge(
                    $allAppsForCats->map(function ($a) {
                        $fd = is_array($a->form_data) ? $a->form_data : json_decode($a->form_data ?? '{}', true);
                        $val = $fd['category'] ?? $fd['medium_type'] ?? $a->accreditation_category_code ?? null;
                        if (in_array($val, ['Newspaper', 'Broadcast', 'Online', 'Print', 'newspaper', 'broadcast', 'online', 'print']))
                            return null;
                        return $val;
                    })->filter()
                )->unique()->sort()->values()->toArray(),
                'provinces' => [],
                'nationalities' => $allAppsForCats->map(function ($a) {
                    $fd = is_array($a->form_data) ? $a->form_data : json_decode($a->form_data ?? '{}', true);
                    return $fd['nationality'] ?? $fd['origin'] ?? null;
                })->filter()->unique()->sort()->values()->toArray(),
                'media' => [],
                'organisations' => $allAppsForCats->map(function ($a) {
                    $fd = is_array($a->form_data) ? $a->form_data : json_decode($a->form_data ?? '{}', true);
                    return $fd['employer'] ?? $fd['organisation_name'] ?? null;
                })->filter()->unique()->sort()->values()->toArray(),
                'years' => $allYears,
                'officers' => \App\Models\User::role('accreditation_officer')->pluck('name', 'id')->toArray(),
                'collection_regions' => ['Harare', 'Bulawayo'],
            ];
        }

        $monthExpr = \DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', submitted_at)"
            : "DATE_FORMAT(submitted_at, '%Y-%m')";
        $monthlyData = (clone $baseAppQuery)
            ->whereNotNull('submitted_at')
            ->selectRaw("{$monthExpr} as month, count(*) as cnt")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->pluck('cnt', 'month')
            ->toArray();

        $categoryData = (clone $baseAppQuery)
            ->whereNotNull('submitted_at')
            ->get()
            ->groupBy(function ($app) {
                $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
                return $fd['category'] ?? $fd['medium_type'] ?? 'Unknown';
            })
            ->map->count()
            ->toArray();

        $statusData = (clone $baseAppQuery)
            ->selectRaw("status, count(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $yrExpr = \DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', submitted_at) AS INTEGER)"
            : "YEAR(submitted_at)";
        $yearlyData = (clone $baseAppQuery)
            ->whereNotNull('submitted_at')
            ->selectRaw("{$yrExpr} as yr, count(*) as cnt")
            ->groupByRaw($yrExpr)
            ->orderBy('yr')
            ->pluck('cnt', 'yr')
            ->toArray();

        $sexData = (clone $baseAppQuery)
            ->whereNotNull('submitted_at')
            ->get()
            ->groupBy(function ($app) {
                $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
                return $fd['sex'] ?? $fd['gender'] ?? 'Unknown';
            })
            ->map->count()
            ->toArray();

        $analytics = [
            'monthly' => $monthlyData,
            'categories' => $categoryData,
            'statuses' => $statusData,
            'yearly' => $yearlyData,
            'sex' => $sexData,
        ];

        $tabCounts = [
            'local' => (function () use ($hasRecords, $baseAppQuery) {
                if ($hasRecords) {
                    $q = \App\Models\AccreditationRecord::query();
                    $this->scopeRecordsByRegion($q);
                    return $q->where(fn($sq) => $sq->whereNull('nationality')->orWhere('nationality', 'Zimbabwean'))->count();
                }
                return (clone $baseAppQuery)->where(fn($sq) => $sq->where('residency_type', 'local')->orWhere('journalist_scope', 'local'))->count();
            })(),
            'foreign' => (function () use ($hasRecords, $baseAppQuery) {
                if ($hasRecords) {
                    $q = \App\Models\AccreditationRecord::query();
                    $this->scopeRecordsByRegion($q);
                    return $q->where(fn($sq) => $sq->whereNotNull('nationality')->where('nationality', '!=', 'Zimbabwean'))->count();
                }
                return (clone $baseAppQuery)->where(fn($sq) => $sq->where('residency_type', 'foreign')->orWhere('journalist_scope', 'foreign'))->count();
            })(),
        ];

        return view('staff.officer.accredited_journalists', compact('journalists', 'stats', 'analytics', 'hasRecords', 'filterOptions', 'tab', 'tabCounts'));
    }

    /**
     * Display paginated list of registered media houses with filters
     */
    public function registeredMediaHouses(Request $request)
    {
        $hasRecords = Schema::hasTable('registration_records') && \App\Models\RegistrationRecord::exists();

        if ($hasRecords) {
            $query = \App\Models\RegistrationRecord::query()
                ->with(['contact', 'publications'])
                ->orderBy('issued_at', 'desc');
            $this->scopeRecordsByRegion($query);

            if ($request->filled('search')) {
                $s = '%' . $request->input('search') . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('registration_no', 'like', $s)
                        ->orWhere('entity_name', 'like', $s)
                        ->orWhere('organisation_name', 'like', $s)
                        ->orWhere('directors', 'like', $s)
                        ->orWhere('email', 'like', $s)
                        ->orWhere('contact_person_name', 'like', $s);
                });
            }

            if ($request->filled('category'))
                $query->where('category', $request->input('category'));
            if ($request->filled('province'))
                $query->where('province', $request->input('province'));
            if ($request->filled('year'))
                $query->where('registration_year', (int) $request->input('year'));
            if ($request->filled('registration_status'))
                $query->where('registration_status', $request->input('registration_status'));

            if ($request->filled('collection_status')) {
                $cs = $request->input('collection_status');
                if ($cs === 'collected')
                    $query->whereNotNull('collected_at');
                elseif ($cs === 'uncollected')
                    $query->whereNull('collected_at');
            }

            if ($request->filled('expiry_status')) {
                $es = $request->input('expiry_status');
                if ($es === 'active')
                    $query->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
                elseif ($es === 'expiring_soon')
                    $query->whereNotNull('expires_at')->where('expires_at', '>=', now())->where('expires_at', '<=', now()->addDays(90));
                elseif ($es === 'expired')
                    $query->whereNotNull('expires_at')->where('expires_at', '<', now());
            }

            if ($request->filled('pub_medium')) {
                $pm = $request->input('pub_medium');
                $query->whereHas('publications', fn($q) => $q->where('medium', $pm));
            }

            if ($request->filled('pub_status')) {
                $ps = $request->input('pub_status');
                $query->whereHas('publications', fn($q) => $q->where('operational_status', $ps));
            }

            if ($request->filled('levy_status')) {
                $ls = $request->input('levy_status');
                if ($ls === 'paid') {
                    $query->whereHas('levyPayments', fn($q) => $q->where('year', date('Y'))->where('status', 'verified'));
                } elseif ($ls === 'unpaid') {
                    $query->whereDoesntHave('levyPayments', fn($q) => $q->where('year', date('Y'))->where('status', 'verified'));
                }
            }

            if ($request->filled('operational_status')) {
                $os = $request->input('operational_status');
                if ($os === 'operational') {
                    $query->whereHas('publications', fn($q) => $q->where('operational_status', 'active'));
                } elseif ($os === 'not_operational') {
                    $query->whereDoesntHave('publications', fn($q) => $q->where('operational_status', 'active'));
                }
            }

            if ($request->filled('scope')) {
                $sc = $request->input('scope');
                $query->whereHas('application', function($q) use ($sc) {
                    $isSqlite = \DB::getDriverName() === 'sqlite';
                    if ($isSqlite) {
                        $q->whereRaw("json_extract(form_data, '$.registration_scope') = ?", [$sc])
                          ->orWhereRaw("json_extract(form_data, '$.scope') = ?", [$sc]);
                    } else {
                        $q->whereRaw("form_data->>'registration_scope' = ?", [$sc])
                          ->orWhereRaw("form_data->>'scope' = ?", [$sc]);
                    }
                });
            }

            $mediaHouses = $query->paginate(20)->appends($request->query());

            $allMH = \App\Models\RegistrationRecord::query();
            $this->scopeRecordsByRegion($allMH);
            $mhStats = [
                'total' => $allMH->count(),
                'active' => (clone $allMH)->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
                'collected' => (clone $allMH)->whereNotNull('collected_at')->count(),
                'uncollected' => (clone $allMH)->whereNull('collected_at')->count(),
                'expired' => (clone $allMH)->whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            ];

            $rrBase = function () {
                $q = \App\Models\RegistrationRecord::query();
                $this->scopeRecordsByRegion($q);
                return $q;
            };
            $filterOptions = [
                'categories' => $rrBase()->whereNotNull('category')->distinct()->pluck('category')->sort()->values()->toArray(),
                'provinces' => $rrBase()->whereNotNull('province')->where('province', '!=', '')->distinct()->pluck('province')->sort()->values()->toArray(),
                'years' => $rrBase()->whereNotNull('registration_year')->distinct()->orderBy('registration_year', 'desc')->pluck('registration_year')->toArray(),
                'statuses' => $rrBase()->whereNotNull('registration_status')->distinct()->pluck('registration_status')->sort()->values()->toArray(),
                'pub_media' => \App\Models\MediaHousePublication::query()
                    ->whereNotNull('medium')
                    ->whereIn('registration_record_id', (clone $rrBase())->pluck('id'))
                    ->distinct()->pluck('medium')->sort()->values()->toArray(),
                'pub_statuses' => \App\Models\MediaHousePublication::query()
                    ->whereNotNull('operational_status')
                    ->whereIn('registration_record_id', (clone $rrBase())->pluck('id'))
                    ->distinct()->pluck('operational_status')->sort()->values()->toArray(),
                'scopes' => ['local', 'national', 'foreign'],
            ];

            $monthExpr = \DB::getDriverName() === 'sqlite'
                ? "strftime('%Y-%m', issued_at)"
                : "DATE_FORMAT(issued_at, '%Y-%m')";
            $monthlyData = $rrBase()->whereNotNull('issued_at')
                ->selectRaw("{$monthExpr} as month, count(*) as cnt")
                ->groupByRaw($monthExpr)
                ->orderBy('month')->pluck('cnt', 'month')->toArray();

            $yearlyData = $rrBase()->whereNotNull('registration_year')
                ->selectRaw("registration_year as yr, count(*) as cnt")
                ->groupBy('registration_year')->orderBy('yr')->pluck('cnt', 'yr')->toArray();

            $categoryData = $rrBase()->whereNotNull('category')
                ->selectRaw("category, count(*) as cnt")
                ->groupBy('category')->pluck('cnt', 'category')->toArray();

            $provinceData = $rrBase()->whereNotNull('province')->where('province', '!=', '')
                ->selectRaw("province, count(*) as cnt")
                ->groupBy('province')->pluck('cnt', 'province')->toArray();

            $statusDistData = $rrBase()->whereNotNull('registration_status')
                ->selectRaw("registration_status, count(*) as cnt")
                ->groupBy('registration_status')->pluck('cnt', 'registration_status')->toArray();
        } else {
            $appQuery = Application::where('application_type', 'registration')
                ->whereNotIn('status', ['draft', 'abandoned'])
                ->with('applicant')
                ->orderBy('submitted_at', 'desc');

            if ($request->filled('search')) {
                $s = '%' . $request->input('search') . '%';
                $appQuery->where(function ($q) use ($s) {
                    $isSqlite = \DB::getDriverName() === 'sqlite';
                    $q->where('reference', 'like', $s);
                    if ($isSqlite) {
                        $q->orWhereRaw("json_extract(form_data, '$.organisation_name') LIKE ?", [$s])
                            ->orWhereRaw("json_extract(form_data, '$.org_name') LIKE ?", [$s])
                            ->orWhereRaw("json_extract(form_data, '$.agency_name') LIKE ?", [$s]);
                    } else {
                        $q->orWhereRaw("form_data->>'organisation_name' LIKE ?", [$s])
                            ->orWhereRaw("form_data->>'org_name' LIKE ?", [$s])
                            ->orWhereRaw("form_data->>'agency_name' LIKE ?", [$s]);
                    }
                    $q->orWhereHas('applicant', fn($uq) => $uq->where('name', 'like', $s));
                });
            }

            if ($request->filled('form_type'))
                $appQuery->where('form_type', $request->input('form_type'));
            if ($request->filled('year'))
                $appQuery->whereYear('submitted_at', (int) $request->input('year'));

            if ($request->filled('status_filter')) {
                $sf = $request->input('status_filter');
                if ($sf === 'approved')
                    $appQuery->whereIn('status', ['approved', 'approved_awaiting_payment', 'payment_verified', 'production_queue', 'certificate_generated', 'ready_for_collection', 'collected', 'completed']);
                elseif ($sf === 'pending')
                    $appQuery->whereIn('status', ['submitted', 'submitted_with_app_fee', 'under_review', 'pending_documents', 'pending_payment']);
                elseif ($sf === 'returned')
                    $appQuery->whereIn('status', ['returned_for_correction', 'returned_from_registrar']);
            }

            if ($request->filled('category')) {
                $cat = $request->input('category');
                $appQuery->where(function($q) use ($cat) {
                    $isSqlite = \DB::getDriverName() === 'sqlite';
                    if ($isSqlite) {
                        $q->whereRaw("json_extract(form_data, '$.category') = ?", [$cat])
                          ->orWhereRaw("json_extract(form_data, '$.media_category') = ?", [$cat]);
                    } else {
                        $q->whereRaw("form_data->>'category' = ?", [$cat])
                          ->orWhereRaw("form_data->>'media_category' = ?", [$cat]);
                    }
                });
            }

            if ($request->filled('province')) {
                $prov = $request->input('province');
                $appQuery->where(function($q) use ($prov) {
                    $isSqlite = \DB::getDriverName() === 'sqlite';
                    if ($isSqlite) {
                        $q->whereRaw("json_extract(form_data, '$.province') = ?", [$prov])
                          ->orWhereRaw("json_extract(form_data, '$.hq_province') = ?", [$prov]);
                    } else {
                        $q->whereRaw("form_data->>'province' = ?", [$prov])
                          ->orWhereRaw("form_data->>'hq_province' = ?", [$prov]);
                    }
                });
            }

            if ($request->filled('levy_status')) {
                $ls = $request->input('levy_status');
                if ($ls === 'paid') {
                    $appQuery->whereHas('levyPayments', fn($q) => $q->where('year', date('Y'))->where('status', 'verified'));
                } elseif ($ls === 'unpaid') {
                    $appQuery->whereDoesntHave('levyPayments', fn($q) => $q->where('year', date('Y'))->where('status', 'verified'));
                }
            }

            if ($request->filled('operational_status')) {
                $os = $request->input('operational_status');
                $appQuery->where(function($q) use ($os) {
                    $isSqlite = \DB::getDriverName() === 'sqlite';
                    $val = $os === 'operational' ? 'yes' : 'no';
                    if ($isSqlite) {
                        $q->whereRaw("json_extract(form_data, '$.operational_status') = ?", [$val]);
                    } else {
                        $q->whereRaw("form_data->>'operational_status' = ?", [$val]);
                    }
                });
            }

            if ($request->filled('scope')) {
                $sc = $request->input('scope');
                $appQuery->where(function($q) use ($sc) {
                    $isSqlite = \DB::getDriverName() === 'sqlite';
                    if ($isSqlite) {
                        $q->whereRaw("json_extract(form_data, '$.registration_scope') = ?", [$sc])
                          ->orWhereRaw("json_extract(form_data, '$.scope') = ?", [$sc]);
                    } else {
                        $q->whereRaw("form_data->>'registration_scope' = ?", [$sc])
                          ->orWhereRaw("form_data->>'scope' = ?", [$sc]);
                    }
                });
            }

            $this->scopeApplicationsByRegion($appQuery);
            $mediaHouses = $appQuery->paginate(20)->appends($request->query());

            $regBaseApps = function () {
                $q = Application::where('application_type', 'registration')->whereNotIn('status', ['draft', 'abandoned']);
                $this->scopeApplicationsByRegion($q);
                return $q;
            };
            $allApps = $regBaseApps();
            $mhStats = [
                'total' => $allApps->count(),
                'active' => 0,
                'collected' => (clone $allApps)->whereIn('status', ['collected', 'completed'])->count(),
                'uncollected' => (clone $allApps)->whereIn('status', ['ready_for_collection', 'certificate_generated'])->count(),
                'expired' => 0,
            ];

            $yearExpr = \DB::getDriverName() === 'sqlite'
                ? "DISTINCT CAST(strftime('%Y', submitted_at) AS INTEGER) as yr"
                : "DISTINCT YEAR(submitted_at) as yr";
            $yearsQ = Application::where('application_type', 'registration')->whereNotNull('submitted_at')
                ->selectRaw($yearExpr)->orderBy('yr', 'desc');
            $this->scopeApplicationsByRegion($yearsQ);
            $allAppsList = $regBaseApps()->get();
            $cats = $allAppsList->map(function($app) {
                $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
                return $fd['media_category'] ?? ($fd['category'] ?? null);
            })->filter()->unique()->sort()->values()->toArray();

            $provs = $allAppsList->map(function($app) {
                $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
                return $fd['province'] ?? ($fd['hq_province'] ?? null);
            })->filter()->unique()->sort()->values()->toArray();

            $scopes = $allAppsList->map(function($app) {
                $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
                return $fd['registration_scope'] ?? ($fd['scope'] ?? null);
            })->filter()->unique()->sort()->values()->toArray();

            $filterOptions = [
                'categories' => $cats,
                'provinces' => $provs,
                'years' => $yearsQ->pluck('yr')->toArray(),
                'statuses' => [],
                'pub_media' => [],
                'pub_statuses' => [],
                'scopes' => $scopes,
            ];

            $monthExpr = \DB::getDriverName() === 'sqlite'
                ? "strftime('%Y-%m', submitted_at)"
                : "DATE_FORMAT(submitted_at, '%Y-%m')";
            $monthlyData = $regBaseApps()->whereNotNull('submitted_at')
                ->selectRaw("{$monthExpr} as month, count(*) as cnt")
                ->groupByRaw($monthExpr)->orderBy('month')->pluck('cnt', 'month')->toArray();

            $yrExpr = \DB::getDriverName() === 'sqlite'
                ? "CAST(strftime('%Y', submitted_at) AS INTEGER)"
                : "YEAR(submitted_at)";
            $yearlyData = $regBaseApps()->whereNotNull('submitted_at')
                ->selectRaw("{$yrExpr} as yr, count(*) as cnt")
                ->groupByRaw($yrExpr)->orderBy('yr')->pluck('cnt', 'yr')->toArray();

            $categoryData = [];
            $provinceData = [];

            $statusDistData = $regBaseApps()
                ->selectRaw("status, count(*) as cnt")->groupBy('status')->pluck('cnt', 'status')->toArray();
        }

        $analytics = [
            'monthly' => $monthlyData,
            'yearly' => $yearlyData,
            'categories' => $categoryData,
            'provinces' => $provinceData,
            'statuses' => $statusDistData,
        ];

        return view('staff.officer.registered_mediahouses', compact('mediaHouses', 'mhStats', 'analytics', 'filterOptions', 'hasRecords'));
    }

    /**
     * Update media house record (details / contact)
     */
    public function updateMediaHouseRecord(Request $request, $id)
    {
        $statusVal = $request->input('status');
        $statusDb = $statusVal;
        if ($statusVal === 'Not Expired') {
            $statusDb = 'Valid';
        }

        // Use the record_type passed from the form to decide which model to update.
        $recordType = $request->input('record_type', 'registration');
        $isRegRecord = ($recordType === 'registration')
            && \Schema::hasTable('registration_records')
            && \App\Models\RegistrationRecord::where('id', $id)->exists();

        if ($isRegRecord) {
            $record = \App\Models\RegistrationRecord::findOrFail($id);
            $record->update([
                'registration_no'     => $request->input('registration_no'),
                'issued_at'           => $request->input('registration_date') ? \Carbon\Carbon::parse($request->input('registration_date')) : null,
                'expires_at'          => $request->input('registration_expiry') ? \Carbon\Carbon::parse($request->input('registration_expiry')) : null,
                'category'            => $request->input('category'),
                'organisation_name'   => $request->input('entity_name'),
                'entity_name'         => $request->input('entity_name'),
                'physical_address'    => $request->input('physical_address'),
                'phone'               => $request->input('phone'),
                'email'               => $request->input('email'),
                'website'             => $request->input('website'),
                'directors'           => $request->input('directors'),
                'shareholding'        => $request->input('shareholding'),
                'status'              => $statusDb ?: $record->status,
                'registration_status' => $statusDb ?: $record->registration_status,
            ]);
        } else {
            $app = \App\Models\Application::findOrFail($id);
            $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
            
            if ($request->has('entity_name')) {
                $fd['organisation_name'] = $request->input('entity_name');
                $fd['org_name'] = $request->input('entity_name');
            }
            if ($request->has('category')) {
                $fd['category'] = $request->input('category');
                $fd['media_category'] = $request->input('category');
            }
            if ($request->has('physical_address')) $fd['contact_address'] = $request->input('physical_address');
            if ($request->has('phone')) $fd['contact_phone'] = $request->input('phone');
            if ($request->has('email')) $fd['contact_email'] = $request->input('email');
            if ($request->has('website')) $fd['website'] = $request->input('website');
            if ($request->has('directors')) {
                $dirs = $request->input('directors');
                if (is_string($dirs)) {
                    $parsed = json_decode($dirs, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $fd['directors'] = $parsed;
                    } else {
                        $fd['directors'] = array_map(function($d) {
                            return ['name' => trim($d), 'gender' => 'male', 'shareholding' => 0];
                        }, explode(',', $dirs));
                    }
                } else {
                    $fd['directors'] = $dirs;
                }
            }
            if ($request->has('shareholding')) {
                $sh = $request->input('shareholding');
                if (is_string($sh)) {
                    $fd['shareholding_notes'] = $sh;
                }
            }
            if ($request->has('registration_no')) {
                $app->reference = $request->input('registration_no');
            }
            if ($request->has('status')) {
                $app->status = $statusDb;
            }
            
            $app->form_data = $fd;
            $app->save();
        }
        
        return back()->with('success', 'Media house record updated successfully.');
    }

    /**
     * Update publication / service record
     */
    public function updatePublicationRecord(Request $request, $id)
    {
        // Use record_type to decide which model to update.
        $recordType = $request->input('record_type', 'registration');
        $isRegRecord = ($recordType === 'registration')
            && \Schema::hasTable('registration_records')
            && is_numeric($id)
            && \App\Models\MediaHousePublication::where('id', $id)->exists();

        if ($isRegRecord) {
            $pub = \App\Models\MediaHousePublication::findOrFail($id);
            $pub->update([
                'publication_name'   => $request->input('publication_name'),
                'type'               => $request->input('type'),
                'medium'             => $request->input('medium'),
                'editor_manager'     => $request->input('editor_manager'),
                'frequency'          => $request->input('frequency'),
                'language'           => $request->input('language'),
                'focus'              => $request->input('focus'),
                'scope'              => $request->input('scope'),
                'reach_cities'       => $request->input('reach_cities'),
                'reach_provinces'    => $request->input('reach_provinces'),
                'pub_contacts'       => $request->input('pub_contacts'),
                'pub_email'          => $request->input('pub_email'),
                'pub_website'        => $request->input('pub_website'),
                'operational_status' => $request->input('operational_status'),
            ]);
        } else {
            $parts = explode('-', $id);
            $appId = $parts[0];
            $pubIndex = $parts[1] ?? null;
            if ($pubIndex !== null) {
                $app = \App\Models\Application::findOrFail($appId);
                $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);
                
                $key = isset($fd['services']) ? 'services' : (isset($fd['publications']) ? 'publications' : null);
                if ($key && isset($fd[$key][$pubIndex])) {
                    $fd[$key][$pubIndex]['publication_name']    = $request->input('publication_name');
                    $fd[$key][$pubIndex]['mass_media_activity'] = $request->input('type');
                    $fd[$key][$pubIndex]['medium']              = $request->input('medium');
                    $fd[$key][$pubIndex]['editor_publisher_name'] = $request->input('editor_manager');
                    $fd[$key][$pubIndex]['frequency']           = $request->input('frequency');
                    $fd[$key][$pubIndex]['language_of_publication'] = $request->input('language');
                    $fd[$key][$pubIndex]['focus']               = $request->input('focus');
                    $fd[$key][$pubIndex]['scope']               = $request->input('scope');
                    $fd[$key][$pubIndex]['reach']               = $request->input('reach_cities');
                    $fd[$key][$pubIndex]['provincial_reach']    = $request->input('reach_provinces');
                    $fd[$key][$pubIndex]['phone']               = $request->input('pub_contacts');
                    $fd[$key][$pubIndex]['email']               = $request->input('pub_email');
                    $fd[$key][$pubIndex]['website']             = $request->input('pub_website');
                    $fd[$key][$pubIndex]['operational_status']  = $request->input('operational_status');
                } else {
                    $fd['publication_name']      = $request->input('publication_name');
                    $fd['mass_media_activity']   = $request->input('type');
                    $fd['editor_publisher_name'] = $request->input('editor_manager');
                    $fd['frequency']             = $request->input('frequency');
                    $fd['language_of_publication'] = $request->input('language');
                    $fd['focus']                 = $request->input('focus');
                    $fd['scope']                 = $request->input('scope');
                    $fd['reach']                 = $request->input('reach_cities');
                    $fd['provincial_reach']      = $request->input('reach_provinces');
                    $fd['phone']                 = $request->input('pub_contacts');
                    $fd['email']                 = $request->input('pub_email');
                    $fd['website']               = $request->input('pub_website');
                }
                
                $app->form_data = $fd;
                $app->save();
            }
        }
        
        return back()->with('success', 'Publication record updated successfully.');
    }

    /**
     * Update accredited practitioner record
     */
    public function updateJournalistRecord(Request $request, $id)
    {
        $recordType = $request->input('record_type', 'accreditation');
        $isRegRecord = ($recordType === 'accreditation')
            && \Schema::hasTable('accreditation_records')
            && \App\Models\AccreditationRecord::where('id', $id)->exists();

        if ($isRegRecord) {
            $record = \App\Models\AccreditationRecord::findOrFail($id);
            $record->update([
                'first_name'           => $request->input('first_name'),
                'surname'              => $request->input('surname'),
                'accreditation_number' => $request->input('accreditation_number'),
                'certificate_no'       => $request->input('accreditation_number'),
                'organisation'         => $request->input('organisation'),
                'category'             => $request->input('category'),
                'date_of_birth'        => $request->input('date_of_birth') ? \Carbon\Carbon::parse($request->input('date_of_birth')) : null,
                'id_number'            => $request->input('id_number'),
                'passport_number'      => $request->input('passport_number'),
                'nationality'          => $request->input('nationality'),
                'sex'                  => $request->input('sex'),
                'physical_address'     => $request->input('physical_address'),
                'town'                 => $request->input('town'),
                'province'             => $request->input('province'),
                'phone'                => $request->input('phone'),
                'cell'                 => $request->input('phone'),
                'email'                => $request->input('email'),
                'designation'          => $request->input('designation'),
                'status'               => $request->input('status') ?: $record->status,
            ]);
            
            if ($record->application) {
                $record->application->update([
                    'collection_region' => $request->input('collection_region'),
                ]);
            }
        } else {
            $app = \App\Models\Application::findOrFail($id);
            $fd = is_array($app->form_data) ? $app->form_data : json_decode($app->form_data ?? '{}', true);

            if ($request->has('first_name')) $fd['first_name'] = $request->input('first_name');
            if ($request->has('surname')) {
                $fd['surname'] = $request->input('surname');
                $fd['last_name'] = $request->input('surname');
            }
            if ($request->has('accreditation_number')) {
                $app->card_number = $request->input('accreditation_number');
                $app->certificate_number = $request->input('accreditation_number');
            }
            if ($request->has('organisation')) {
                $fd['employer'] = $request->input('organisation');
                $fd['organisation_name'] = $request->input('organisation');
                $fd['media_org'] = $request->input('organisation');
            }
            if ($request->has('category')) {
                $fd['category'] = $request->input('category');
                $fd['medium_type'] = $request->input('category');
            }
            if ($request->has('date_of_birth')) {
                $fd['dob'] = $request->input('date_of_birth');
                $fd['date_of_birth'] = $request->input('date_of_birth');
            }
            if ($request->has('id_number')) {
                $fd['id_number'] = $request->input('id_number');
                $fd['national_id'] = $request->input('id_number');
            }
            if ($request->has('passport_number')) $fd['passport_number'] = $request->input('passport_number');
            if ($request->has('nationality')) {
                $fd['nationality'] = $request->input('nationality');
                $fd['origin'] = $request->input('nationality');
            }
            if ($request->has('sex')) {
                $fd['sex'] = $request->input('sex');
                $fd['gender'] = $request->input('sex');
            }
            if ($request->has('physical_address')) {
                $fd['address'] = $request->input('physical_address');
                $fd['residential_address'] = $request->input('physical_address');
            }
            if ($request->has('town')) {
                $fd['town'] = $request->input('town');
                $fd['city'] = $request->input('town');
            }
            if ($request->has('province')) $fd['province'] = $request->input('province');
            if ($request->has('phone')) {
                $fd['phone'] = $request->input('phone');
                $fd['phone_number'] = $request->input('phone');
            }
            if ($request->has('email')) $fd['email'] = $request->input('email');
            if ($request->has('designation')) {
                $fd['designation'] = $request->input('designation');
                $fd['position'] = $request->input('designation');
            }
            if ($request->has('status')) $app->status = $request->input('status');
            if ($request->has('collection_region')) $app->collection_region = $request->input('collection_region');

            $app->form_data = $fd;
            $app->save();
        }

        return back()->with('success', 'Practitioner record updated successfully.');
    }

    /**
     * Send collection notification to journalists/media houses
     */
    public function physicalIntakeForm()
    {
        return view('staff.officer.physical_intake');
    }

    public function physicalIntakeProcess(Request $request)
    {
        $baseRules = [
            'application_type' => ['required', 'in:accreditation,registration'],
            'request_type' => ['required', 'in:new,renewal,replacement'],
        ];

        $requestType = $request->input('request_type');
        $appType = $request->input('application_type');

        if ($requestType === 'new' && $appType === 'accreditation') {
            $isFreelance = $request->input('is_freelance') === 'yes';
            $rules = array_merge($baseRules, [
                'first_name' => ['required', 'string', 'max:100'],
                'surname' => ['required', 'string', 'max:100'],
                'id_number' => ['required', 'string', 'regex:/^\d{2}-\d+[A-Z]\d{2}$/i'],
                'category' => ['required', 'string', 'max:10'],
                'is_freelance' => ['required', 'in:yes,no'],
                'receipt_number' => ['required', 'string', 'max:100'],
                'employer_name' => [$isFreelance ? 'nullable' : 'required', 'string', 'max:255'],
                'designation' => ['nullable', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:30'],
                'physical_address' => ['required', 'string', 'max:500'],
                'province' => ['required', 'string', 'max:50'],
                'city' => ['required', 'string', 'max:100'],
                'applicant_photo' => $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png']),
            ]);
        } elseif ($requestType === 'new' && $appType === 'registration') {
            $rules = array_merge($baseRules, [
                'entity_name' => ['required', 'string', 'max:255'],
                'trading_name' => ['required', 'string', 'max:255'],
                'business_registration' => ['required', 'string', 'max:100'],
                'tax_number' => ['required', 'string', 'max:100'],
                'business_type' => ['required', 'string', 'max:50'],
                'ownership_type' => ['required', 'string', 'max:50'],
                'local_ownership' => ['required', 'numeric', 'min:0', 'max:100'],
                'website' => ['nullable', 'url', 'max:255'],
                'media_category' => ['required', 'string', 'max:10'],
                'publication_name' => ['required', 'string', 'max:255'],
                'publication_frequency' => ['nullable', 'string', 'max:30'],
                'editor_name' => ['required', 'string', 'max:255'],
                'editor_contact' => ['required', 'string', 'max:255'],
                'mh_email' => ['required', 'email', 'max:255'],
                'mh_phone' => ['required', 'string', 'max:30'],
                'mh_physical_address' => ['required', 'string', 'max:500'],
                'postal_address' => ['required', 'string', 'max:500'],
                'mh_province' => ['required', 'string', 'max:50'],
                'mh_city' => ['required', 'string', 'max:100'],
                'mh_receipt_number' => ['required', 'string', 'max:100'],
            ]);
        } else {
            $rules = array_merge($baseRules, [
                'renewal_first_name' => ['required', 'string', 'max:100'],
                'renewal_surname' => ['required', 'string', 'max:100'],
                'renewal_id_number' => ['required', 'string', 'regex:/^\d{2}-\d+[A-Z]\d{2}$/i'],
                'lookup_number' => ['required', 'string', 'max:50'],
                'renewal_receipt_number' => ['required', 'string', 'max:100'],
                'renewal_email' => ['required', 'email', 'max:255'],
                'renewal_phone' => ['required', 'string', 'max:30'],
                'renewal_physical_address' => ['required', 'string', 'max:500'],
                'renewal_city' => ['required', 'string', 'max:100'],
                'renewal_province' => ['required', 'string', 'max:50'],
            ]);
        }

        $data = $request->validate($rules);

        $year = now()->format('Y');

        if ($requestType === 'new' && $appType === 'accreditation') {
            $prefix = "ZMC-AP3-{$year}-";
            $lastRef = Application::where('reference', 'like', $prefix . '%')
                ->where('reference', 'not like', 'DRAFT%')
                ->orderByRaw("CAST(SUBSTRING(reference, -4) AS UNSIGNED) DESC")
                ->value('reference');
            $nextNum = $lastRef ? ((int) substr($lastRef, -4)) + 1 : 1;
            $reference = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $applicantName = trim($data['first_name'] . ' ' . $data['surname']);

            $user = \App\Models\User::where('email', $data['email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $applicantName,
                    'email' => $data['email'],
                    'password' => bcrypt(Str::random(32)),
                    'account_type' => 'journalist',
                    'phone_number' => $data['phone'],
                    'id_number' => $data['id_number'],
                    'account_status' => 'active',
                    'region' => $data['province'] ?? 'harare',
                ]);
            }

            $photoPath = null;
            $photoFile = null;
            $photoBinary = null;
            if ($request->hasFile('applicant_photo')) {
                $photoFile = $request->file('applicant_photo');
                $photoBinary = @file_get_contents($photoFile->getRealPath()) ?: null;
                $photoPath = $this->storePublicFile($photoFile, 'intake-photos', FileType::safeHashName($photoFile));
            }

            $formData = [
                'first_name' => $data['first_name'],
                'surname' => $data['surname'],
                'national_id' => $data['id_number'],
                'id_number' => $data['id_number'],
                'category' => $data['category'],
                'is_freelance' => ($data['is_freelance'] ?? 'no') === 'yes',
                'employer_name' => ($data['is_freelance'] ?? 'no') === 'yes' ? 'Freelance' : ($data['employer_name'] ?? ''),
                'designation' => $data['designation'] ?? '',
                'email' => $data['email'],
                'phone' => $data['phone'],
                'physical_address' => $data['physical_address'],
                'province' => $data['province'],
                'city' => $data['city'],
                'photo_path' => $photoPath,
                'intake_method' => 'physical',
                'intake_officer' => auth()->user()?->name,
                'intake_date' => now()->toDateString(),
            ];

            $application = Application::create([
                'reference' => $reference,
                'applicant_user_id' => $user->id,
                'application_type' => 'accreditation',
                'request_type' => 'new',
                'status' => Application::PRODUCTION_QUEUE,
                'receipt_number' => $data['receipt_number'],
                'accreditation_category_code' => $data['category'],
                'collection_region' => $data['province'] ?? 'harare',
                'form_data' => $formData,
                'is_draft' => false,
                'submitted_at' => now(),
                'approved_at' => now(),
                'assigned_officer_id' => null,
                'last_action_by' => auth()->id(),
                'payment_status' => 'verified',
                'payment_stage' => 'complete',
            ]);

            if ($photoPath && $photoFile) {
                $photoDoc = \App\Models\ApplicationDocument::create([
                    'application_id' => $application->id,
                    'owner_id' => $user->id,
                    'doc_type' => 'passport_photo',
                    'file_path' => $photoPath,
                    'original_name' => $photoFile->getClientOriginalName(),
                    'mime' => FileType::safeUploadedMime($photoFile),
                    'size' => $photoFile->getSize(),
                    'status' => 'uploaded',
                ]);
                $photoDoc->storeBinary($photoBinary);
            }

        } elseif ($requestType === 'new' && $appType === 'registration') {
            $prefix = "ZMC-AP1-{$year}-";
            $lastRef = Application::where('reference', 'like', $prefix . '%')
                ->where('reference', 'not like', 'DRAFT%')
                ->orderByRaw("CAST(SUBSTRING(reference, -4) AS UNSIGNED) DESC")
                ->value('reference');
            $nextNum = $lastRef ? ((int) substr($lastRef, -4)) + 1 : 1;
            $reference = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $user = \App\Models\User::where('email', $data['mh_email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $data['entity_name'],
                    'email' => $data['mh_email'],
                    'password' => bcrypt(Str::random(32)),
                    'account_type' => 'mediahouse',
                    'phone_number' => $data['mh_phone'],
                    'account_status' => 'active',
                    'region' => $data['mh_province'] ?? 'harare',
                ]);
            }

            $formData = [
                'entity_name' => $data['entity_name'],
                'trading_name' => $data['trading_name'],
                'business_registration' => $data['business_registration'],
                'tax_number' => $data['tax_number'],
                'business_type' => $data['business_type'],
                'ownership_type' => $data['ownership_type'],
                'local_ownership' => $data['local_ownership'],
                'website' => $data['website'] ?? null,
                'media_category' => $data['media_category'],
                'publication_name' => $data['publication_name'],
                'publication_frequency' => $data['publication_frequency'] ?? null,
                'editor_name' => $data['editor_name'],
                'editor_contact' => $data['editor_contact'],
                'email' => $data['mh_email'],
                'phone' => $data['mh_phone'],
                'physical_address' => $data['mh_physical_address'],
                'postal_address' => $data['postal_address'],
                'province' => $data['mh_province'],
                'city' => $data['mh_city'],
                'intake_method' => 'physical',
                'intake_officer' => auth()->user()?->name,
                'intake_date' => now()->toDateString(),
            ];

            $application = Application::create([
                'reference' => $reference,
                'applicant_user_id' => $user->id,
                'application_type' => 'registration',
                'request_type' => 'new',
                'status' => Application::PRODUCTION_QUEUE,
                'receipt_number' => $data['mh_receipt_number'],
                'media_house_category_code' => $data['media_category'],
                'collection_region' => $data['mh_province'] ?? 'harare',
                'form_data' => $formData,
                'is_draft' => false,
                'submitted_at' => now(),
                'approved_at' => now(),
                'assigned_officer_id' => null,
                'last_action_by' => auth()->id(),
                'payment_status' => 'verified',
                'payment_stage' => 'complete',
            ]);

        } else {
            $applicantName = trim(($data['renewal_first_name'] ?? '') . ' ' . ($data['renewal_surname'] ?? ''));

            $existingApp = Application::where('reference', $data['lookup_number'])->first();

            $user = \App\Models\User::where('email', $data['renewal_email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $applicantName,
                    'email' => $data['renewal_email'],
                    'password' => bcrypt(Str::random(32)),
                    'account_type' => $appType === 'registration' ? 'mediahouse' : 'journalist',
                    'phone_number' => $data['renewal_phone'],
                    'id_number' => $data['renewal_id_number'],
                    'account_status' => 'active',
                    'region' => $data['renewal_province'] ?? 'harare',
                ]);
            }

            $refPrefix = $appType === 'registration' ? "ZMC-AP1-{$year}-" : "ZMC-AP3-{$year}-";
            $lastRef = Application::where('reference', 'like', $refPrefix . '%')
                ->where('reference', 'not like', 'DRAFT%')
                ->orderByRaw("CAST(SUBSTRING(reference, -4) AS UNSIGNED) DESC")
                ->value('reference');
            $nextNum = $lastRef ? ((int) substr($lastRef, -4)) + 1 : 1;
            $reference = $refPrefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $formData = [
                'first_name' => $data['renewal_first_name'] ?? '',
                'surname' => $data['renewal_surname'] ?? '',
                'id_number' => $data['renewal_id_number'] ?? '',
                'email' => $data['renewal_email'],
                'phone' => $data['renewal_phone'],
                'physical_address' => $data['renewal_physical_address'] ?? '',
                'city' => $data['renewal_city'] ?? '',
                'province' => $data['renewal_province'] ?? '',
                'lookup_number' => $data['lookup_number'],
                'previous_reference' => $existingApp?->reference,
                'intake_method' => 'physical',
                'intake_officer' => auth()->user()?->name,
                'intake_date' => now()->toDateString(),
            ];

            $application = Application::create([
                'reference' => $reference,
                'applicant_user_id' => $user->id,
                'application_type' => $appType,
                'request_type' => $requestType,
                'status' => Application::PRODUCTION_QUEUE,
                'receipt_number' => $data['renewal_receipt_number'],
                'collection_region' => $data['renewal_province'] ?? 'harare',
                'form_data' => $formData,
                'is_draft' => false,
                'submitted_at' => now(),
                'approved_at' => now(),
                'assigned_officer_id' => null,
                'last_action_by' => auth()->id(),
                'payment_status' => 'verified',
                'payment_stage' => 'complete',
            ]);
        }

        if (class_exists(\App\Services\ActivityLogger::class)) {
            \App\Services\ActivityLogger::log('physical_intake_created', $application, null, Application::PRODUCTION_QUEUE, [
                'receipt_number' => $application->receipt_number,
                'application_type' => $appType,
                'request_type' => $requestType,
                'officer' => auth()->user()?->name,
            ]);
        }

        return redirect()->route('staff.officer.physical-intake')
            ->with('success', "Physical intake recorded successfully (Ref: {$application->reference}). Application added to production queue.");
    }

    public function sendCollectionNotification(Request $request)
    {
        $data = $request->validate([
            'record_type' => ['required', 'in:accreditation,registration'],
            'record_ids' => ['nullable', 'array'],
            'record_ids.*' => ['integer'],
        ]);

        $notificationService = new \App\Services\NotificationService();
        $count = 0;
        $type = $data['record_type'];
        $ids = $data['record_ids'] ?? [];

        if ($type === 'accreditation') {
            $query = \App\Models\AccreditationRecord::query()->with('holder')->whereNull('collected_at');
            $this->scopeRecordsByRegion($query);
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
            $records = $query->get();

            foreach ($records as $record) {
                if ($notificationService->sendCollectionReminder($record, 'accreditation')) {
                    activity()
                        ->causedBy(Auth::user())
                        ->performedOn($record)
                        ->log('collection_reminder_sent');
                    $count++;
                }
            }
        } else {
            $query = \App\Models\RegistrationRecord::query()->with('contact')->whereNull('collected_at');
            $this->scopeRecordsByRegion($query);
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            }
            $records = $query->get();

            foreach ($records as $record) {
                if ($notificationService->sendCollectionReminder($record, 'registration')) {
                    activity()
                        ->causedBy(Auth::user())
                        ->performedOn($record)
                        ->log('collection_reminder_sent');
                    $count++;
                }
            }
        }

        return back()->with('success', "Collection reminders sent to {$count} " . ($type === 'accreditation' ? 'media practitioners' : 'media houses') . ".");
    }

    public function recordsExport(Request $request, string $type)
    {
        $format = $request->input('format', 'csv');
        $hasAccredRecords = Schema::hasTable('accreditation_records') && \App\Models\AccreditationRecord::exists();
        $hasRegRecords = Schema::hasTable('registration_records') && \App\Models\RegistrationRecord::exists();

        $exportHeaders = [
            'Accreditation Number',
            'First Name',
            'Surname',
            'Other Names',
            'Organisation',
            'Category',
            'Valid From',
            'Valid To',
            'Year',
            'ID Number',
            'Passport Number',
            'Photo',
            'Marital Status',
            'Sex',
            'Date of Birth',
            'Birth Place',
            'Nationality',
            'Physical Address',
            'Town',
            'Province',
            'Phone',
            'Cell',
            'Telephone',
            'Email',
            'Medium',
            'Designation',
            'Status',
            'Collection Status',
        ];

        $rows = [];
        if ($type === 'journalists') {
            if ($hasAccredRecords) {
                $query = \App\Models\AccreditationRecord::query()->with('holder')->orderBy('issued_at', 'desc');
                $this->scopeRecordsByRegion($query);
                if ($request->filled('year'))
                    $query->where('accreditation_year', (int) $request->input('year'));
                if ($request->filled('category'))
                    $query->where('category', $request->input('category'));
                if ($request->filled('sex'))
                    $query->where('sex', $request->input('sex'));
                $records = $query->get();
                foreach ($records as $r) {
                    $rows[] = [
                        $r->accreditation_number ?? $r->certificate_no ?? '',
                        $r->first_name ?? '',
                        $r->surname ?? '',
                        $r->other_names ?? '',
                        $r->organisation ?? '',
                        $r->category ?? '',
                        optional($r->issued_at)->format('d/m/Y') ?? '',
                        optional($r->expires_at)->format('d/m/Y') ?? '',
                        $r->accreditation_year ?? '',
                        $r->id_number ?? '',
                        $r->passport_number ?? '',
                        $r->photo_path ? 'Yes' : 'No',
                        $r->marital_status ?? '',
                        $r->sex ?? '',
                        optional($r->date_of_birth)->format('d/m/Y') ?? '',
                        $r->birth_place ?? '',
                        $r->nationality ?? '',
                        $r->physical_address ?? '',
                        $r->town ?? '',
                        $r->province ?? '',
                        $r->phone ?? '',
                        $r->cell ?? '',
                        $r->telephone ?? '',
                        $r->email ?? '',
                        $r->medium ?? '',
                        $r->designation ?? '',
                        $r->status ?? '',
                        $r->collected_at ? 'Collected' : 'Uncollected',
                    ];
                }
            } else {
                $expQ = Application::where('application_type', 'accreditation')
                    ->whereNotIn('status', ['draft', 'abandoned'])->with('applicant')
                    ->orderBy('submitted_at', 'desc');
                $this->scopeApplicationsByRegion($expQ);
                $records = $expQ->get();
                foreach ($records as $a) {
                    $fd = is_array($a->form_data) ? $a->form_data : json_decode($a->form_data ?? '{}', true);
                    $rows[] = [
                        $a->reference ?? '',
                        $fd['first_name'] ?? '',
                        $fd['surname'] ?? $fd['last_name'] ?? '',
                        '',
                        $fd['employer'] ?? $fd['organisation_name'] ?? '',
                        $fd['category'] ?? $fd['medium_type'] ?? '',
                        optional($a->submitted_at)->format('d/m/Y') ?? '',
                        '',
                        optional($a->submitted_at)->format('Y') ?? '',
                        $fd['id_number'] ?? $fd['national_id'] ?? '',
                        '',
                        'No',
                        $fd['marital_status'] ?? '',
                        $fd['sex'] ?? $fd['gender'] ?? '',
                        $fd['dob'] ?? $fd['date_of_birth'] ?? '',
                        $fd['birth_place'] ?? '',
                        $fd['nationality'] ?? $fd['origin'] ?? '',
                        $fd['physical_address'] ?? $fd['address'] ?? '',
                        $fd['town'] ?? $fd['city'] ?? '',
                        $fd['province'] ?? '',
                        $fd['phone'] ?? $fd['mobile'] ?? '',
                        $fd['cell'] ?? '',
                        $fd['telephone'] ?? '',
                        $fd['email'] ?? $a->applicant?->email ?? '',
                        $fd['medium'] ?? $fd['medium_type'] ?? '',
                        $fd['designation'] ?? $fd['position'] ?? '',
                        str_replace('_', ' ', ucfirst($a->status)),
                        '',
                    ];
                }
            }
        } else {
            $exportHeaders = [
                'Registration No',
                'Organisation Name',
                'Directors',
                'Directors Gender',
                'Shareholding',
                'Address',
                'Town',
                'Province',
                'Phone',
                'Cell',
                'Email',
                'Website',
                'Category',
                'Registration Status',
                'Registration Date',
                'Registration Year',
                'Contact Person',
                'Contact Email',
                'Contact Phone',
                'Valid From',
                'Valid To',
                'Collection Status',
            ];
            if ($hasRegRecords) {
                $query = \App\Models\RegistrationRecord::query()->with('contact')->orderBy('issued_at', 'desc');
                $this->scopeRecordsByRegion($query);
                if ($request->filled('year'))
                    $query->where('registration_year', (int) $request->input('year'));
                if ($request->filled('category'))
                    $query->where('category', $request->input('category'));
                if ($request->filled('province'))
                    $query->where('province', $request->input('province'));
                $records = $query->get();
                foreach ($records as $r) {
                    $rows[] = [
                        $r->registration_no ?? '',
                        $r->organisation_name ?? $r->entity_name ?? '',
                        $r->directors ?? '',
                        $r->directors_gender ?? '',
                        $r->shareholding ?? '',
                        $r->physical_address ?? '',
                        $r->town ?? '',
                        $r->province ?? '',
                        $r->phone ?? '',
                        $r->cell ?? '',
                        $r->email ?? '',
                        $r->website ?? '',
                        $r->category ?? '',
                        $r->registration_status ?? '',
                        optional($r->registration_date)->format('d/m/Y') ?? '',
                        $r->registration_year ?? '',
                        $r->contact_person_name ?? $r->contact?->name ?? '',
                        $r->contact_person_email ?? $r->contact?->email ?? '',
                        $r->contact_person_phone ?? $r->contact?->phone ?? '',
                        optional($r->issued_at)->format('d/m/Y') ?? '',
                        optional($r->expires_at)->format('d/m/Y') ?? '',
                        $r->collected_at ? 'Collected' : 'Uncollected',
                    ];
                }
            } else {
                $expQ2 = Application::where('application_type', 'registration')
                    ->whereNotIn('status', ['draft', 'abandoned'])->with('applicant')
                    ->orderBy('submitted_at', 'desc');
                $this->scopeApplicationsByRegion($expQ2);
                $records = $expQ2->get();
                foreach ($records as $a) {
                    $fd = is_array($a->form_data) ? $a->form_data : json_decode($a->form_data ?? '{}', true);
                    $rows[] = [
                        $a->reference ?? '',
                        $fd['organisation_name'] ?? $fd['org_name'] ?? $fd['agency_name'] ?? '',
                        $fd['directors'] ?? '',
                        $fd['directors_gender'] ?? '',
                        $fd['shareholding'] ?? '',
                        $fd['physical_address'] ?? $fd['address'] ?? '',
                        $fd['town'] ?? $fd['city'] ?? '',
                        $fd['province'] ?? '',
                        $fd['phone'] ?? '',
                        $fd['cell'] ?? '',
                        $fd['email'] ?? $a->applicant?->email ?? '',
                        $fd['website'] ?? '',
                        $fd['category'] ?? '',
                        str_replace('_', ' ', ucfirst($a->status)),
                        optional($a->submitted_at)->format('d/m/Y') ?? '',
                        optional($a->submitted_at)->format('Y') ?? '',
                        $fd['contact_name'] ?? $fd['contact_person'] ?? '',
                        $fd['contact_email'] ?? '',
                        $fd['contact_phone'] ?? '',
                        optional($a->submitted_at)->format('d/m/Y') ?? '',
                        '',
                        '',
                    ];
                }
            }
        }

        if ($format === 'pdf') {
            return $this->exportPdf($exportHeaders, $rows, $type);
        }
        if ($format === 'excel') {
            return $this->exportExcel($exportHeaders, $rows, $type);
        }

        $filename = $type . '_records_' . now()->format('Y-m-d') . '.csv';
        $callback = function () use ($exportHeaders, $rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $exportHeaders);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportExcel(array $headers, array $rows, string $type)
    {
        $filename = $type . '_records_' . now()->format('Y-m-d') . '.xlsx';
        $tmpPath = storage_path("app/{$filename}");

        $xmlRows = '';
        $xmlRows .= '<Row>';
        foreach ($headers as $h) {
            $xmlRows .= '<Cell><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>';
        }
        $xmlRows .= '</Row>';
        foreach ($rows as $row) {
            $xmlRows .= '<Row>';
            foreach ($row as $cell) {
                $xmlRows .= '<Cell><Data ss:Type="String">' . htmlspecialchars((string) $cell) . '</Data></Cell>';
            }
            $xmlRows .= '</Row>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<?mso-application progid="Excel.Sheet"?>' . "\n" .
            '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' .
            '<Styles><Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#F5C518" ss:Pattern="Solid"/></Style></Styles>' .
            '<Worksheet ss:Name="Records"><Table>' . $xmlRows . '</Table></Worksheet></Workbook>';

        file_put_contents($tmpPath, $xml);
        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
        ])->deleteFileAfterSend(true);
    }

    private function exportPdf(array $headers, array $rows, string $type)
    {
        $title = $type === 'journalists' ? 'Accredited Media Practitioners' : 'Registered Media Houses';
        $date = now()->format('d M Y');
        $visibleHeaders = array_slice($headers, 0, 12);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $title . '</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;font-size:10px;margin:20px;}';
        $html .= 'h1{font-size:16px;color:#1a1a1a;border-bottom:3px solid #f5c518;padding-bottom:8px;}';
        $html .= '.meta{color:#666;margin-bottom:15px;font-size:11px;}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-top:10px;}';
        $html .= 'th{background:#1a1a1a;color:#f5c518;padding:6px 4px;text-align:left;font-size:9px;}';
        $html .= 'td{border-bottom:1px solid #e0e0e0;padding:5px 4px;font-size:9px;}';
        $html .= 'tr:nth-child(even){background:#f9f9f9;}';
        $html .= '.footer{margin-top:20px;text-align:center;color:#999;font-size:9px;}';
        $html .= '@media print{@page{size:landscape;margin:10mm;}}</style></head><body>';
        $html .= '<h1>Zimbabwe Media Commission — ' . $title . '</h1>';
        $html .= '<div class="meta">Generated: ' . $date . ' | Total Records: ' . count($rows) . '</div>';
        $html .= '<table><thead><tr>';
        foreach ($visibleHeaders as $h) {
            $html .= '<th>' . htmlspecialchars($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (array_slice($row, 0, 12) as $cell) {
                $html .= '<td>' . htmlspecialchars((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<div class="footer">Zimbabwe Media Commission &copy; ' . date('Y') . ' — Confidential</div>';
        $html .= '<script>window.onload=function(){window.print();}</script>';
        $html .= '</body></html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    public function sendMessage(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $this->persistMessageIfAvailable($application, $data['message']);

        $this->audit('officer_message_sent', $application, null, null, [
            'message_preview' => Str::limit($data['message'], 100),
        ]);

        return back()->with('success', 'Message sent to applicant.');
    }

    public function sendRenewalReminders(Request $request)
    {
        $data = $request->validate([
            'record_type' => ['required', 'in:accreditation,registration'],
            'record_ids' => ['nullable', 'array'],
            'record_ids.*' => ['integer'],
        ]);

        $count = 0;
        $type = $data['record_type'];
        $ids = $data['record_ids'] ?? [];

        if ($type === 'accreditation' && class_exists(\App\Models\AccreditationRecord::class)) {
            $query = \App\Models\AccreditationRecord::query()->with('holder');
            $this->scopeRecordsByRegion($query);
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                $cutoff = now()->addDays(90);
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '>=', now())
                    ->where('expires_at', '<=', $cutoff);
            }
            $records = $query->get();
            foreach ($records as $record) {
                // TODO: Implement actual notification sending (email/SMS/portal notification)
                // For now, just log the action
                if ($record->holder) {
                    activity()
                        ->causedBy(Auth::user())
                        ->performedOn($record)
                        ->withProperties(['holder_email' => $record->holder->email])
                        ->log('renewal_reminder_sent');
                    $count++;
                }
            }
        } elseif ($type === 'registration' && class_exists(\App\Models\RegistrationRecord::class)) {
            $query = \App\Models\RegistrationRecord::query()->with('contact');
            $this->scopeRecordsByRegion($query);
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
            } else {
                $cutoff = now()->addDays(90);
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '>=', now())
                    ->where('expires_at', '<=', $cutoff);
            }
            $records = $query->get();
            foreach ($records as $record) {
                // TODO: Implement actual notification sending
                if ($record->contact) {
                    activity()
                        ->causedBy(Auth::user())
                        ->performedOn($record)
                        ->withProperties(['contact_email' => $record->contact->email])
                        ->log('renewal_reminder_sent');
                    $count++;
                }
            }
        }

        return back()->with('success', "Renewal reminders sent to {$count} " . ($type === 'accreditation' ? 'media practitioners' : 'media houses') . ".");
    }

    public function idVerify(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'result' => ['required', 'in:pending,passed,failed'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $from = $application->status;

        if ($this->hasColumn('applications', 'id_verification_status')) {
            $application->id_verification_status = $data['result'];
            $application->save();
        }

        $this->audit('officer_id_verify_stub', $application, $from, $from, [
            'result' => $data['result'],
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'ID verification (stub) updated.');
    }

    /* =========================
     * Menu pages (v1)
     * ========================= */

    // --- Applications lists ---
    public function applicationsIndex()
    {
        return $this->applicationsList('all');
    }
    public function applicationsNew()
    {
        return $this->applicationsList('new');
    }
    public function applicationsPending()
    {
        return $this->applicationsList('pending');
    }
    public function applicationsApproved()
    {
        return $this->applicationsList('approved');
    }
    public function applicationsRejected()
    {
        return $this->applicationsList('rejected');
    }
    public function applicationsNewApps()
    {
        return $this->applicationsList('all', 'new');
    }
    public function applicationsRenewals()
    {
        return $this->applicationsList('all', 'renewal');
    }
    public function applicationsReplacements()
    {
        return $this->applicationsList('all', 'replacement');
    }
    public function applicationsIncomplete()
    {
        abort(404);
    } // drafts remain in applicant portal only
    public function applicationsBulk(Request $request)
    {
        $query = Application::query()
            ->with(['applicant'])
            ->whereIn('status', [
                Application::SUBMITTED,
                Application::OFFICER_REVIEW,
                Application::RETURNED_TO_OFFICER,
                Application::REGISTRAR_FIX_REQUEST,
                Application::SUBMITTED_WITH_APP_FEE,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
            ])
            ->latest();
        $this->scopeApplicationsByRegion($query);

        if ($request->filled('q')) {
            $s = $request->q;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%{$s}%")
                    ->orWhereHas('applicant', function ($aq) use ($s) {
                        $aq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                    });
            });
        }
        if ($request->filled('type')) {
            $query->where('application_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->paginate(25)->withQueryString();

        return view('staff.officer.bulk_review', compact('applications'));
    }

    public function applicationsBulkAction(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', 'in:request_correction,send_message'],
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        $applications = Application::whereIn('id', $data['ids'])->get();
        $updated = 0;
        $failed = [];

        foreach ($applications as $application) {
            try {
                $this->ensureCanAccessRegion($application);

                if ($data['action'] === 'request_correction') {
                    ApplicationWorkflowService::returnToApplicant($application, $data['notes'], [
                        'actor_role' => session('active_staff_role', 'accreditation_officer'),
                    ]);
                }

                $this->persistMessageIfAvailable($application, $data['notes']);
                $updated++;
            } catch (\Throwable $e) {
                $failed[] = ($application->reference ?: '#' . $application->id) . ': ' . $e->getMessage();
            }
        }

        $message = "{$updated} application(s) updated.";
        if (!empty($failed)) {
            $message .= ' ' . count($failed) . ' could not be processed.';
        }

        return back()
            ->with($updated > 0 ? 'success' : 'error', $message)
            ->with('bulk_failures', $failed);
    }

    public function recordsHistory(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $status = $request->query('status');
        $search = $request->query('q');

        $acc = null;
        $reg = null;

        if (class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records')) {
            $q = \App\Models\AccreditationRecord::query()->with('holder')->latest('issued_at');
            $this->scopeRecordsByRegion($q);
            if ($year) {
                $q->whereYear('issued_at', $year);
            }
            if ($status) {
                $q->where('status', $status);
            }
            if ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('certificate_no', 'like', "%{$search}%")
                        ->orWhere('record_number', 'like', "%{$search}%")
                        ->orWhere('accreditation_number', 'like', "%{$search}%")
                        ->orWhereHas('holder', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }
            $acc = $q->paginate(15, ['*'], 'acc')->withQueryString();
        }

        if (class_exists(\App\Models\RegistrationRecord::class) && Schema::hasTable('registration_records')) {
            $q = \App\Models\RegistrationRecord::query()->with('contact')->latest('issued_at');
            $this->scopeRecordsByRegion($q);
            if ($year) {
                $q->whereYear('issued_at', $year);
            }
            if ($status) {
                $q->where('status', $status);
            }
            if ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('registration_no', 'like', "%{$search}%")
                        ->orWhere('record_number', 'like', "%{$search}%")
                        ->orWhere('entity_name', 'like', "%{$search}%")
                        ->orWhere('organisation_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }
            $reg = $q->paginate(15, ['*'], 'reg')->withQueryString();
        }

        $accTotal = 0;
        $accActive = 0;
        if ($acc) {
            $accTotal = $this->scopedRecordCount(\App\Models\AccreditationRecord::class);
            $accActive = $this->scopedRecordCount(\App\Models\AccreditationRecord::class, ['active', 'issued']);
        }
        $regTotal = 0;
        $regActive = 0;
        if ($reg) {
            $regTotal = $this->scopedRecordCount(\App\Models\RegistrationRecord::class);
            $regActive = $this->scopedRecordCount(\App\Models\RegistrationRecord::class, ['active', 'issued']);
        }

        $accRows = $acc ? $acc->getCollection()->map(fn ($r) => [
            e($r->certificate_no ?: $r->record_number ?: '#' . $r->id),
            e($r->holder->name ?? '—'),
            $this->statusBadge($r->status),
            optional($r->issued_at)->format('Y-m-d') ?: '—',
            optional($r->expires_at)->format('Y-m-d') ?: '—',
        ])->all() : [];

        $regRows = $reg ? $reg->getCollection()->map(fn ($r) => [
            e($r->registration_no ?: $r->record_number ?: '#' . $r->id),
            e($r->entity_name ?: $r->organisation_name ?: $r->contact->name ?? '—'),
            $this->statusBadge($r->status),
            optional($r->issued_at)->format('Y-m-d') ?: '—',
            optional($r->expires_at)->format('Y-m-d') ?: '—',
        ])->all() : [];

        $statuses = [];
        foreach (['AccreditationRecord', 'RegistrationRecord'] as $modelName) {
            $class = '\\App\\Models\\' . $modelName;
            if (class_exists($class)) {
                $table = (new $class)->getTable();
                if (Schema::hasTable($table)) {
                    foreach ($class::query()->select('status')->distinct()->pluck('status') as $s) {
                        if ($s) {
                            $statuses[$s] = ucwords(str_replace('_', ' ', $s));
                        }
                    }
                }
            }
        }
        ksort($statuses);

        return $this->reportView([
            'title' => 'Accreditation & Registration History',
            'subtitle' => 'Issued, expired, suspended and revoked records across both registers.',
            'kpis' => [
                ['label' => 'Accreditation records', 'value' => number_format($accTotal), 'color' => 'primary', 'hint' => $accActive . ' active'],
                ['label' => 'Registration records', 'value' => number_format($regTotal), 'color' => 'success', 'hint' => $regActive . ' active'],
                ['label' => 'Shown this year', 'value' => number_format(($acc?->total() ?? 0) + ($reg?->total() ?? 0)), 'color' => 'info', 'hint' => (string) $year],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'value' => $search, 'placeholder' => 'Certificate / registration number or name'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'value' => $status, 'options' => ['' => 'All statuses'] + $statuses],
                ['name' => 'year', 'label' => 'Year', 'type' => 'select', 'value' => (string) $year, 'options' => $this->yearOptions()],
            ],
            'sections' => [
                [
                    'title' => 'Accredited Media Practitioners',
                    'columns' => ['Number', 'Holder', 'Status', 'Issued', 'Expires'],
                    'rows' => $accRows,
                    'empty' => 'No accreditation records match these filters.',
                    'paginator' => $acc,
                ],
                [
                    'title' => 'Registered Media Houses',
                    'columns' => ['Number', 'Entity', 'Status', 'Issued', 'Expires'],
                    'rows' => $regRows,
                    'empty' => 'No registration records match these filters.',
                    'paginator' => $reg,
                ],
            ],
        ]);
    }

    public function recordsRenewals()
    {
        return $this->renewalApplicationsPage('Renewals Management', false);
    }

    public function renewalsRequests()
    {
        return $this->renewalApplicationsPage('Renewal Requests', true);
    }

    private function renewalApplicationsPage(string $title, bool $requestsOnly)
    {
        $status = request()->query('status');
        $type = request()->query('renewal_type');
        $search = request()->query('q');

        $query = \App\Models\RenewalApplication::query()
            ->with(['applicant', 'originalApplication'])
            ->latest('updated_at');

        if ($requestsOnly) {
            $query->whereNotIn('status', [
                \App\Models\RenewalApplication::RENEWAL_IN_PRODUCTION,
                \App\Models\RenewalApplication::RENEWAL_PRODUCED_READY_FOR_COLLECTION,
            ]);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($type) {
            $query->where('renewal_type', $type);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_number', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhereHas('applicant', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $renewals = $query->paginate(20)->withQueryString();

        $base = \App\Models\RenewalApplication::query();
        $kpis = [
            ['label' => 'Total renewals', 'value' => number_format((clone $base)->count()), 'color' => 'primary'],
            ['label' => 'Awaiting payment', 'value' => number_format((clone $base)->whereIn('status', [
                \App\Models\RenewalApplication::RENEWAL_CONFIRMED_NO_CHANGES,
                \App\Models\RenewalApplication::RENEWAL_CONFIRMED_WITH_CHANGES,
                \App\Models\RenewalApplication::RENEWAL_PAYMENT_INITIATED,
            ])->count()), 'color' => 'warning'],
            ['label' => 'Payment verified', 'value' => number_format((clone $base)->where('status', \App\Models\RenewalApplication::RENEWAL_PAYMENT_VERIFIED)->count()), 'color' => 'info'],
            ['label' => 'In production', 'value' => number_format((clone $base)->where('status', \App\Models\RenewalApplication::RENEWAL_IN_PRODUCTION)->count()), 'color' => 'success'],
        ];

        $rows = $renewals->getCollection()->map(function ($r) {
            $showUrl = route('staff.officer.renewals.production.show', $r);
            return [
                e($r->applicant->name ?? '—'),
                e(ucfirst($r->renewal_type ?? '—')),
                '<a href="' . e($showUrl) . '" class="fw-semibold text-decoration-none">' . e($r->original_number ?: $r->registration_number ?: '#' . $r->id) . '</a>',
                $this->statusBadge($r->status),
                optional($r->payment_verified_at)->format('Y-m-d H:i') ?: '—',
                optional($r->updated_at)->format('Y-m-d H:i') ?: '—',
            ];
        })->all();

        $statusOptions = ['' => 'All statuses'];
        foreach (\App\Models\RenewalApplication::query()->select('status')->distinct()->pluck('status') as $s) {
            if ($s) {
                $statusOptions[$s] = ucwords(str_replace('_', ' ', $s));
            }
        }

        return $this->reportView([
            'title' => $title,
            'subtitle' => $requestsOnly
                ? 'Renewal submissions received from applicants and awaiting officer/accounts action.'
                : 'All renewal applications, their payment state and production progress.',
            'kpis' => $kpis,
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'value' => $search, 'placeholder' => 'Number, applicant name or email'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'value' => $status, 'options' => $statusOptions],
                ['name' => 'renewal_type', 'label' => 'Type', 'type' => 'select', 'value' => $type, 'options' => [
                    '' => 'All types',
                    'renewal' => 'Renewal',
                    'replacement' => 'Replacement',
                    'accreditation' => 'Accreditation (legacy)',
                    'registration' => 'Registration (legacy)',
                    'permission' => 'Permission',
                ]],
            ],
            'headerActions' => [
                ['label' => 'Production Queue', 'url' => route('staff.officer.renewals.production'), 'icon' => 'ri-printer-line'],
                ['label' => 'Expiring Soon', 'url' => route('staff.officer.renewals.expiring'), 'icon' => 'ri-calendar-todo-line'],
            ],
            'sections' => [[
                'title' => 'Renewal Applications',
                'columns' => ['Applicant', 'Type', 'Original number', 'Status', 'Payment verified', 'Updated'],
                'rows' => $rows,
                'empty' => 'No renewal applications match these filters.',
                'paginator' => $renewals,
            ]],
        ]);
    }

    public function complianceMonitoring()
    {
        $accTotal = $this->scopedRecordCount(\App\Models\AccreditationRecord::class);
        $accActive = $this->scopedRecordCount(\App\Models\AccreditationRecord::class, ['active', 'issued']);
        $regTotal = $this->scopedRecordCount(\App\Models\RegistrationRecord::class);
        $regActive = $this->scopedRecordCount(\App\Models\RegistrationRecord::class, ['active', 'issued']);

        $expiring = collect();
        $expired = 0;
        if (Schema::hasTable('accreditation_records')) {
            $q = \App\Models\AccreditationRecord::query()->with('holder')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(90)]);
            $this->scopeRecordsByRegion($q);
            $expiring = $q->orderBy('expires_at')->limit(20)->get();
            $expired = $this->scopedRecordCount(\App\Models\AccreditationRecord::class, null, fn ($rq) => $rq->whereNotNull('expires_at')->where('expires_at', '<', now()));
        }

        $openViolations = Schema::hasTable('compliance_violations')
            ? \App\Models\ComplianceViolation::whereNotIn('status', ['resolved', 'closed', 'dismissed'])->count()
            : 0;
        $openCases = Schema::hasTable('compliance_cases')
            ? \App\Models\ComplianceCase::whereNotIn('status', ['resolved', 'closed', 'dismissed'])->count()
            : 0;
        $openReports = Schema::hasTable('unaccredited_reports')
            ? \App\Models\UnaccreditedReport::whereNotIn('status', ['resolved', 'closed', 'dismissed'])->count()
            : 0;

        $renRows = $expiring->map(fn ($r) => [
            e($r->holder->name ?? '—'),
            e($r->certificate_no ?: $r->record_number ?: '#' . $r->id),
            optional($r->expires_at)->format('Y-m-d') ?: '—',
            (string) (optional($r->expires_at)->isFuture() ? now()->startOfDay()->diffInDays($r->expires_at->startOfDay()) : 0),
        ])->all();

        $violationRows = [];
        if (Schema::hasTable('compliance_violations')) {
            $violationRows = \App\Models\ComplianceViolation::latest()->limit(10)->get()->map(fn ($v) => [
                e($v->reference ?: '#' . $v->id),
                e($v->category ?: '—'),
                $this->statusBadge($v->status),
                \Illuminate\Support\Str::limit(e($v->summary ?? ''), 90),
                optional($v->created_at)->format('Y-m-d') ?: '—',
            ])->all();
        }

        $caseRows = [];
        if (Schema::hasTable('compliance_cases')) {
            $caseRows = \App\Models\ComplianceCase::latest()->limit(10)->get()->map(fn ($c) => [
                e($c->reference ?: '#' . $c->id),
                $this->statusBadge($c->status),
                \Illuminate\Support\Str::limit(e($c->summary ?? ''), 90),
                optional($c->created_at)->format('Y-m-d') ?: '—',
            ])->all();
        }

        $reportRows = [];
        if (Schema::hasTable('unaccredited_reports')) {
            $reportRows = \App\Models\UnaccreditedReport::latest()->limit(10)->get()->map(fn ($r) => [
                e($r->reference ?: '#' . $r->id),
                e($r->subject_name ?: $r->entity_name ?: '—'),
                $this->statusBadge($r->status),
                optional($r->created_at)->format('Y-m-d') ?: '—',
            ])->all();
        }

        return $this->reportView([
            'title' => 'Compliance Monitoring',
            'subtitle' => 'Live compliance posture across records, violations, investigations and unaccredited practice reports.',
            'kpis' => [
                ['label' => 'Active accreditations', 'value' => number_format($accActive), 'color' => 'success', 'hint' => number_format($accTotal) . ' total records'],
                ['label' => 'Active registrations', 'value' => number_format($regActive), 'color' => 'success', 'hint' => number_format($regTotal) . ' total records'],
                ['label' => 'Expiring in 90 days', 'value' => number_format($expiring->count()), 'color' => 'warning'],
                ['label' => 'Already expired', 'value' => number_format($expired), 'color' => 'danger'],
                ['label' => 'Open violations', 'value' => number_format($openViolations), 'color' => 'danger'],
                ['label' => 'Open cases', 'value' => number_format($openCases), 'color' => 'warning'],
                ['label' => 'Unaccredited reports', 'value' => number_format($openReports), 'color' => 'info'],
            ],
            'headerActions' => [
                ['label' => 'Violations', 'url' => route('staff.officer.compliance.violations'), 'icon' => 'ri-error-warning-line'],
                ['label' => 'Cases', 'url' => route('staff.officer.compliance.cases'), 'icon' => 'ri-folder-warning-line'],
                ['label' => 'Expiring Soon', 'url' => route('staff.officer.renewals.expiring'), 'icon' => 'ri-calendar-todo-line'],
            ],
            'sections' => [
                ['title' => 'Accreditations expiring within 90 days', 'columns' => ['Holder', 'Certificate', 'Expires', 'Days left'], 'rows' => $renRows, 'empty' => 'Nothing expiring in the next 90 days.'],
                ['title' => 'Recent reported violations', 'columns' => ['Ref', 'Category', 'Status', 'Summary', 'Reported'], 'rows' => $violationRows, 'empty' => 'No violations have been reported.'],
                ['title' => 'Recent investigation cases', 'columns' => ['Ref', 'Status', 'Summary', 'Opened'], 'rows' => $caseRows, 'empty' => 'No investigation cases recorded.'],
                ['title' => 'Recent unaccredited practice reports', 'columns' => ['Ref', 'Subject', 'Status', 'Reported'], 'rows' => $reportRows, 'empty' => 'No unaccredited practice reports recorded.'],
            ],
        ]);
    }

    public function reportsStats(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        $byStatus = Application::query()
            ->whereYear('created_at', $year)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->orderByDesc('cnt')
            ->get();

        $byType = Application::query()
            ->whereYear('created_at', $year)
            ->selectRaw('application_type, count(*) as cnt')
            ->groupBy('application_type')
            ->pluck('cnt', 'application_type');

        $byRegion = Application::query()
            ->whereYear('created_at', $year)
            ->selectRaw("COALESCE(NULLIF(collection_region, ''), 'Unassigned') as region, count(*) as cnt")
            ->groupBy('region')
            ->orderByDesc('cnt')
            ->get();

        $approvedStatuses = [
            Application::OFFICER_APPROVED,
            Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
            Application::VERIFIED_BY_OFFICER_PENDING_REGISTRAR,
            Application::REGISTRAR_REVIEW,
            Application::REGISTRAR_APPROVED,
            Application::ACCOUNTS_REVIEW,
            Application::PAYMENT_VERIFIED,
            Application::PRODUCTION_QUEUE,
            Application::CARD_GENERATED,
            Application::CERT_GENERATED,
            Application::PRODUCED_READY,
            Application::PRINTED,
            Application::ISSUED,
        ];

        $total = (int) $byStatus->sum('cnt');
        $approved = (int) $byStatus->whereIn('status', $approvedStatuses)->sum('cnt');
        $pending = (int) $byStatus->whereIn('status', [
            Application::SUBMITTED,
            Application::OFFICER_REVIEW,
            Application::RETURNED_TO_OFFICER,
            Application::REGISTRAR_FIX_REQUEST,
        ])->sum('cnt');

        $statusRows = $byStatus->map(fn ($row) => [
            e(ucwords(str_replace('_', ' ', $row->status ?: 'unknown'))),
            number_format((int) $row->cnt),
            $this->statusBadge($row->status),
        ])->all();

        $regionRows = $byRegion->map(fn ($row) => [
            e(ucwords(str_replace('_', ' ', $row->region))),
            number_format((int) $row->cnt),
        ])->all();

        $typeRows = [
            ['Accreditation applications', number_format((int) ($byType['accreditation'] ?? 0))],
            ['Registration applications', number_format((int) ($byType['registration'] ?? 0))],
            ['Other / unspecified', number_format(max(0, $total - (int) ($byType['accreditation'] ?? 0) - (int) ($byType['registration'] ?? 0)))],
        ];

        $bars = [
            'title' => 'Applications by status',
            'labels' => $byStatus->take(8)->map(fn ($r) => ucwords(str_replace('_', ' ', $r->status ?: 'unknown')))->values()->all(),
            'series' => [[
                'label' => 'Applications',
                'color' => '#1a1a1a',
                'values' => $byStatus->take(8)->map(fn ($r) => (int) $r->cnt)->values()->all(),
            ]],
        ];

        return $this->reportView([
            'title' => 'Accreditation Statistics',
            'subtitle' => 'Application volumes for ' . $year . ' by status, type and collection region.',
            'kpis' => [
                ['label' => 'Applications', 'value' => number_format($total), 'color' => 'primary', 'hint' => (string) $year],
                ['label' => 'Awaiting officer action', 'value' => number_format($pending), 'color' => 'warning'],
                ['label' => 'Forwarded / approved', 'value' => number_format($approved), 'color' => 'success'],
                ['label' => 'Completion rate', 'value' => ($total > 0 ? round(($approved / $total) * 100, 1) : 0) . '%', 'color' => 'info'],
            ],
            'filters' => [
                ['name' => 'year', 'label' => 'Year', 'type' => 'select', 'value' => (string) $year, 'options' => $this->yearOptions()],
            ],
            'sections' => [
                ['title' => 'By status', 'columns' => ['Status', 'Applications', 'Badge'], 'rows' => $statusRows, 'empty' => 'No applications in this year.'],
                ['title' => 'By application type', 'columns' => ['Type', 'Applications'], 'rows' => $typeRows, 'empty' => 'No applications in this year.'],
                ['title' => 'By collection region', 'columns' => ['Region', 'Applications'], 'rows' => $regionRows, 'empty' => 'No regional data for this year.'],
            ],
            'bars' => $bars,
        ]);
    }

    public function reportsMonthly(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $driver = DB::getDriverName();
        $monthExpr = fn (string $col) => $driver === 'sqlite'
            ? "strftime('%m', {$col})"
            : "MONTH({$col})";

        $apps = Application::query()->whereYear('created_at', $year)
            ->selectRaw($monthExpr('created_at') . ' as m, count(*) as cnt')
            ->groupBy('m')->pluck('cnt', 'm');

        $approved = Application::query()->whereYear('approved_at', $year)
            ->whereNotNull('approved_at')
            ->selectRaw($monthExpr('approved_at') . ' as m, count(*) as cnt')
            ->groupBy('m')->pluck('cnt', 'm');

        $certs = collect();
        if (Schema::hasTable('accreditation_records')) {
            $certs = \App\Models\AccreditationRecord::query()->whereYear('issued_at', $year)
                ->selectRaw($monthExpr('issued_at') . ' as m, count(*) as cnt')
                ->groupBy('m')->pluck('cnt', 'm');
        }

        $regs = collect();
        if (Schema::hasTable('registration_records')) {
            $regs = \App\Models\RegistrationRecord::query()->whereYear('issued_at', $year)
                ->selectRaw($monthExpr('issued_at') . ' as m, count(*) as cnt')
                ->groupBy('m')->pluck('cnt', 'm');
        }

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $rows = [];
        $appSeries = [];
        foreach ($monthNames as $i => $name) {
            $m = $i + 1;
            $appCount = (int) ($apps[$m] ?? $apps[(string) $m] ?? $apps[sprintf('%02d', $m)] ?? 0);
            $apprCount = (int) ($approved[$m] ?? $approved[(string) $m] ?? $approved[sprintf('%02d', $m)] ?? 0);
            $certCount = (int) ($certs[$m] ?? $certs[(string) $m] ?? $certs[sprintf('%02d', $m)] ?? 0);
            $regCount = (int) ($regs[$m] ?? $regs[(string) $m] ?? $regs[sprintf('%02d', $m)] ?? 0);
            $appSeries[] = $appCount;
            $rows[] = [
                $name . ' ' . $year,
                number_format($appCount),
                number_format($apprCount),
                number_format($certCount),
                number_format($regCount),
            ];
        }

        $totals = array_reduce($rows, function ($carry, $row) {
            $carry[0] += (int) str_replace(',', '', $row[1]);
            $carry[1] += (int) str_replace(',', '', $row[2]);
            $carry[2] += (int) str_replace(',', '', $row[3]);
            $carry[3] += (int) str_replace(',', '', $row[4]);
            return $carry;
        }, [0, 0, 0, 0]);

        $rows[] = ['<strong>Total</strong>', '<strong>' . number_format($totals[0]) . '</strong>', '<strong>' . number_format($totals[1]) . '</strong>', '<strong>' . number_format($totals[2]) . '</strong>', '<strong>' . number_format($totals[3]) . '</strong>'];

        return $this->reportView([
            'title' => 'Monthly / Annual Reports',
            'subtitle' => 'Month-by-month application, approval and issuance activity for ' . $year . '.',
            'kpis' => [
                ['label' => 'New applications', 'value' => number_format($totals[0]), 'color' => 'primary'],
                ['label' => 'Forwarded / approved', 'value' => number_format($totals[1]), 'color' => 'success'],
                ['label' => 'Accreditation certificates', 'value' => number_format($totals[2]), 'color' => 'info'],
                ['label' => 'Registration certificates', 'value' => number_format($totals[3]), 'color' => 'warning'],
            ],
            'filters' => [
                ['name' => 'year', 'label' => 'Year', 'type' => 'select', 'value' => (string) $year, 'options' => $this->yearOptions()],
            ],
            'sections' => [[
                'title' => 'Monthly breakdown — ' . $year,
                'columns' => ['Month', 'New applications', 'Approved / forwarded', 'Certificates issued', 'Registrations issued'],
                'rows' => $rows,
                'empty' => 'No activity recorded for this year.',
            ]],
            'bars' => [
                'title' => 'New applications per month',
                'labels' => $monthNames,
                'series' => [['label' => 'Applications', 'color' => '#1a1a1a', 'values' => $appSeries]],
            ],
        ]);
    }

    public function reportsTrends()
    {
        $driver = DB::getDriverName();
        $monthExpr = fn (string $col) => $driver === 'sqlite'
            ? "strftime('%Y-%m', {$col})"
            : "DATE_FORMAT({$col}, '%Y-%m')";

        $start = now()->startOfMonth()->subMonths(11);
        $labels = [];
        $keys = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $keys[] = $month->format('Y-m');
            $labels[] = $month->format('M Y');
        }

        $acc = Application::query()
            ->where('application_type', 'accreditation')
            ->where('created_at', '>=', $start)
            ->selectRaw($monthExpr('created_at') . ' as ym, count(*) as cnt')
            ->groupBy('ym')->pluck('cnt', 'ym');

        $reg = Application::query()
            ->where('application_type', 'registration')
            ->where('created_at', '>=', $start)
            ->selectRaw($monthExpr('created_at') . ' as ym, count(*) as cnt')
            ->groupBy('ym')->pluck('cnt', 'ym');

        $rows = [];
        $accSeries = [];
        $regSeries = [];
        $totalSeries = [];
        foreach ($keys as $i => $key) {
            $a = (int) ($acc[$key] ?? 0);
            $r = (int) ($reg[$key] ?? 0);
            $accSeries[] = $a;
            $regSeries[] = $r;
            $totalSeries[] = $a + $r;
            $rows[] = [$labels[$i], number_format($a), number_format($r), number_format($a + $r)];
        }

        return $this->reportView([
            'title' => 'Accreditation Trends',
            'subtitle' => 'Rolling 12-month submission trend by application type.',
            'kpis' => [
                ['label' => 'Accreditation submissions', 'value' => number_format(array_sum($accSeries)), 'color' => 'primary'],
                ['label' => 'Registration submissions', 'value' => number_format(array_sum($regSeries)), 'color' => 'success'],
                ['label' => 'Total submissions', 'value' => number_format(array_sum($totalSeries)), 'color' => 'info'],
                ['label' => 'Monthly average', 'value' => number_format(array_sum($totalSeries) / 12, 1), 'color' => 'warning'],
            ],
            'sections' => [[
                'title' => 'Last 12 months',
                'columns' => ['Month', 'Accreditation', 'Registration', 'Total'],
                'rows' => $rows,
                'empty' => 'No submissions in the last 12 months.',
            ]],
            'bars' => [
                'title' => 'Submissions per month',
                'labels' => $labels,
                'series' => [
                    ['label' => 'Accreditation', 'color' => '#1a1a1a', 'values' => $accSeries],
                    ['label' => 'Registration', 'color' => '#facc15', 'values' => $regSeries],
                ],
            ],
        ]);
    }

    public function reportsCompliance(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        $violations = Schema::hasTable('compliance_violations')
            ? \App\Models\ComplianceViolation::query()->whereYear('created_at', $year)->selectRaw('status, count(*) as cnt')->groupBy('status')->orderByDesc('cnt')->get()
            : collect();
        $cases = Schema::hasTable('compliance_cases')
            ? \App\Models\ComplianceCase::query()->whereYear('created_at', $year)->selectRaw('status, count(*) as cnt')->groupBy('status')->orderByDesc('cnt')->get()
            : collect();
        $reports = Schema::hasTable('unaccredited_reports')
            ? \App\Models\UnaccreditedReport::query()->whereYear('created_at', $year)->selectRaw('status, count(*) as cnt')->groupBy('status')->orderByDesc('cnt')->get()
            : collect();

        $closed = ['resolved', 'closed', 'dismissed'];
        $openViolations = $violations->whereNotIn('status', $closed)->sum('cnt');
        $openCases = $cases->whereNotIn('status', $closed)->sum('cnt');
        $openReports = $reports->whereNotIn('status', $closed)->sum('cnt');
        $totalItems = $violations->sum('cnt') + $cases->sum('cnt') + $reports->sum('cnt');
        $closedItems = $violations->whereIn('status', $closed)->sum('cnt')
            + $cases->whereIn('status', $closed)->sum('cnt')
            + $reports->whereIn('status', $closed)->sum('cnt');

        $toRows = fn ($collection, string $label) => $collection->map(fn ($row) => [
            $label,
            e(ucwords(str_replace('_', ' ', $row->status ?: 'unknown'))),
            number_format((int) $row->cnt),
            $this->statusBadge($row->status),
        ])->all();

        $allRows = array_merge(
            $toRows($violations, 'Violation'),
            $toRows($cases, 'Case'),
            $toRows($reports, 'Unaccredited report')
        );

        return $this->reportView([
            'title' => 'Compliance Reports',
            'subtitle' => 'Investigation and enforcement outcomes for ' . $year . '.',
            'kpis' => [
                ['label' => 'Open items', 'value' => number_format($openViolations + $openCases + $openReports), 'color' => 'danger'],
                ['label' => 'Open violations', 'value' => number_format($openViolations), 'color' => 'warning'],
                ['label' => 'Open cases', 'value' => number_format($openCases), 'color' => 'info'],
                ['label' => 'Closure rate', 'value' => ($totalItems > 0 ? round(($closedItems / $totalItems) * 100, 1) : 0) . '%', 'color' => 'success'],
            ],
            'filters' => [
                ['name' => 'year', 'label' => 'Year', 'type' => 'select', 'value' => (string) $year, 'options' => $this->yearOptions()],
            ],
            'headerActions' => [
                ['label' => 'Compliance Monitoring', 'url' => route('staff.officer.compliance.monitoring'), 'icon' => 'ri-shield-keyhole-line'],
            ],
            'sections' => [[
                'title' => 'Compliance items by status — ' . $year,
                'columns' => ['Item type', 'Status', 'Count', 'Badge'],
                'rows' => $allRows,
                'empty' => 'No compliance activity recorded for this year.',
            ]],
        ]);
    }

    public function commNotices(Request $request)
    {
        return $this->noticesPage(
            'Accreditation Notices',
            'Official notices published by the Commission and visible to staff.',
            fn ($q) => $q,
            $request
        );
    }

    public function commAnnouncements(Request $request)
    {
        return $this->noticesPage(
            'Public Announcements',
            'Announcements published to applicants and the public website.',
            fn ($q) => $q->where(function ($sq) {
                $sq->where('show_on_homepage', true)->orWhere('notice_category', 'like', '%announce%');
            }),
            $request
        );
    }

    public function commMemos(Request $request)
    {
        return $this->noticesPage(
            'Internal Memos',
            'Internal staff communications. Memos issued by administration appear here.',
            fn ($q) => $q->where(function ($sq) {
                $sq->where('target_portal', 'staff')->orWhere('notice_category', 'like', '%memo%');
            }),
            $request
        );
    }

    private function noticesPage(string $title, string $subtitle, \Closure $scope, Request $request)
    {
        $rows = collect();
        $categories = [];

        if (Schema::hasTable('notices')) {
            $query = \App\Models\Notice::query();
            $scope($query);

            if ($request->filled('q')) {
                $s = $request->q;
                $query->where(function ($sq) use ($s) {
                    $sq->where('title', 'like', "%{$s}%")
                        ->orWhere('summary', 'like', "%{$s}%")
                        ->orWhere('body', 'like', "%{$s}%");
                });
            }
            if ($request->filled('category')) {
                $query->where('notice_category', $request->category);
            }

            $rows = $query->latest('published_at')->paginate(20)->withQueryString();
            $categories = \App\Models\Notice::query()->select('notice_category')->distinct()->whereNotNull('notice_category')->pluck('notice_category', 'notice_category')->all();
        }

        $tableRows = $rows->getCollection()->map(fn ($n) => [
            e($n->title ?: '#' . $n->id),
            e($n->notice_category ? ucwords(str_replace('_', ' ', $n->notice_category)) : '—'),
            e($n->target_audience ? ucwords(str_replace('_', ' ', $n->target_audience)) : ($n->show_on_homepage ? 'Public' : 'Staff')),
            optional($n->published_at)->format('Y-m-d') ?: '—',
            optional($n->expires_at)->format('Y-m-d') ?: 'No expiry',
            $this->statusBadge($n->urgency_level ?: 'normal'),
        ])->all();

        return $this->reportView([
            'title' => $title,
            'subtitle' => $subtitle,
            'kpis' => [
                ['label' => 'Notices shown', 'value' => number_format($rows->total()), 'color' => 'primary'],
                ['label' => 'Categories', 'value' => number_format(count($categories)), 'color' => 'info'],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'value' => $request->query('q'), 'placeholder' => 'Title or body text'],
                ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'value' => $request->query('category'), 'options' => ['' => 'All categories'] + $categories],
            ],
            'sections' => [[
                'title' => 'Notices',
                'columns' => ['Title', 'Category', 'Audience', 'Published', 'Expires', 'Priority'],
                'rows' => $tableRows,
                'empty' => 'No notices have been published yet.',
                'paginator' => $rows,
            ]],
        ]);
    }

    public function commMessaging(Request $request)
    {
        $audiences = [
            'officer_queue' => 'Applicants in the officer review queue',
            'pending_documents' => 'Applicants with documents pending verification',
            'expiring' => 'Practitioners / media houses expiring within 90 days',
            'all_active' => 'All applicants with non-draft applications',
        ];

        $counts = [];
        foreach ($audiences as $key => $label) {
            $counts[$key] = $this->messagingRecipients($key)->count();
        }

        $recent = collect();
        if (Schema::hasTable('application_messages')) {
            $recent = \App\Models\ApplicationMessage::query()
                ->with(['application.applicant'])
                ->where('from_user_id', Auth::id())
                ->latest('sent_at')
                ->limit(15)
                ->get();
        }

        return view('staff.officer.comm_messaging', compact('audiences', 'counts', 'recent'));
    }

    public function commMessagingSend(Request $request)
    {
        $data = $request->validate([
            'audience' => ['required', 'in:officer_queue,pending_documents,expiring,all_active'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        if (!Schema::hasTable('application_messages')) {
            return back()->with('error', 'Message storage is not available.');
        }

        $recipients = $this->messagingRecipients($data['audience']);
        $sent = 0;

        foreach ($recipients as $application) {
            if (!$application->applicant_user_id) {
                continue;
            }
            \App\Models\ApplicationMessage::create([
                'application_id' => $application->id,
                'from_user_id' => Auth::id(),
                'to_user_id' => $application->applicant_user_id,
                'channel' => 'internal',
                'subject' => $data['subject'] ?: null,
                'body' => $data['body'],
                'sent_at' => now(),
                'read_at' => null,
            ]);
            $sent++;
        }

        return back()->with($sent > 0 ? 'success' : 'error', $sent . ' message(s) sent to applicants.');
    }

    private function messagingRecipients(string $audience)
    {
        if ($audience === 'expiring') {
            $userIds = collect();
            if (Schema::hasTable('accreditation_records')) {
                $userIds = $userIds->merge(
                    \App\Models\AccreditationRecord::query()
                        ->whereNotNull('holder_user_id')
                        ->whereBetween('expires_at', [now(), now()->addDays(90)])
                        ->pluck('holder_user_id')
                );
            }
            if (Schema::hasTable('registration_records')) {
                $userIds = $userIds->merge(
                    \App\Models\RegistrationRecord::query()
                        ->whereNotNull('contact_user_id')
                        ->whereBetween('expires_at', [now(), now()->addDays(90)])
                        ->pluck('contact_user_id')
                );
            }
            $expiringQuery = Application::query()
                ->whereIn('applicant_user_id', $userIds->unique()->values())
                ->where('status', '!=', Application::DRAFT);
            $this->scopeApplicationsByRegion($expiringQuery);

            return $expiringQuery
                ->orderByDesc('created_at')
                ->get()
                ->unique('applicant_user_id')
                ->values();
        }

        $query = Application::query()
            ->whereNotNull('applicant_user_id')
            ->where('status', '!=', Application::DRAFT);
        $this->scopeApplicationsByRegion($query);

        if ($audience === 'officer_queue') {
            $query->whereIn('status', [
                Application::SUBMITTED,
                Application::OFFICER_REVIEW,
                Application::RETURNED_TO_OFFICER,
                Application::REGISTRAR_FIX_REQUEST,
            ]);
        } elseif ($audience === 'pending_documents') {
            $query->whereHas('documents', fn ($dq) => $dq->where('status', 'pending'));
        }

        return $query->orderByDesc('created_at')->get()->unique('applicant_user_id')->values();
    }

    public function advancedDuplicates(Request $request)
    {
        $fields = [
            'email' => 'Email address',
            'national_id_number' => 'National ID number',
            'passport_number' => 'Passport number',
            'phone_number' => 'Phone number',
            'phone_number_2' => 'Alternate phone number',
        ];
        $field = $request->query('field', 'email');
        if (!array_key_exists($field, $fields)) {
            $field = 'email';
        }

        $groups = collect();
        if ($this->hasColumn('users', $field)) {
            $groups = \App\Models\User::query()
                ->select($field)
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->groupBy($field)
                ->havingRaw('COUNT(*) > 1')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(100)
                ->get();
        }

        $rows = [];
        $involved = 0;
        foreach ($groups as $group) {
            $value = $group->{$field};
            $matches = \App\Models\User::query()->where($field, $value)->limit(10)->get();
            $involved += $matches->count();
            $rows[] = [
                e($value),
                number_format($matches->count()),
                $matches->map(fn ($u) => e($u->name ?: $u->email) . ' <span class="text-muted">(' . e($u->email) . ')</span>')->implode('<br>'),
            ];
        }

        return $this->reportView([
            'title' => 'Duplicate Applicant Detection',
            'subtitle' => 'Potential duplicate accounts sharing the same identity or contact details.',
            'kpis' => [
                ['label' => 'Duplicate groups', 'value' => number_format($groups->count()), 'color' => 'warning'],
                ['label' => 'Accounts involved', 'value' => number_format($involved), 'color' => 'danger'],
                ['label' => 'Match field', 'value' => $fields[$field], 'color' => 'info'],
            ],
            'filters' => [
                ['name' => 'field', 'label' => 'Match on', 'type' => 'select', 'value' => $field, 'options' => $fields],
            ],
            'sections' => [[
                'title' => 'Potential duplicates by ' . strtolower($fields[$field]),
                'columns' => ['Value', 'Accounts', 'Matched users'],
                'rows' => $rows,
                'empty' => 'No duplicate records detected for this field.',
            ]],
        ]);
    }

    public function advancedForgery(Request $request)
    {
        $risk = $request->query('risk', 'flagged');
        $search = $request->query('q');
        $rows = collect();
        $riskCount = 0;
        $highRisk = 0;
        $failedChecks = 0;

        if (Schema::hasTable('application_documents')) {
            $riskCount = \App\Models\ApplicationDocument::query()->whereNotNull('risk_score')->count();
            $highRisk = \App\Models\ApplicationDocument::query()->where('risk_score', '>=', 70)->count();

            $query = \App\Models\ApplicationDocument::query()
                ->with(['application.applicant'])
                ->latest();

            if ($risk === 'high') {
                $query->where('risk_score', '>=', 70);
            } elseif ($risk === 'medium') {
                $query->whereBetween('risk_score', [40, 69]);
            } elseif ($risk === 'flagged') {
                $query->where(function ($q) {
                    $q->where('risk_score', '>=', 40)
                        ->orWhereIn('verification_status', ['rejected', 'flagged', 'suspicious'])
                        ->orWhereIn('status', ['rejected']);
                });
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('original_name', 'like', "%{$search}%")
                        ->orWhere('doc_type', 'like', "%{$search}%")
                        ->orWhereHas('application', function ($aq) use ($search) {
                            $aq->where('reference', 'like', "%{$search}%")
                                ->orWhereHas('applicant', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                        });
                });
            }

            $rows = $query->paginate(20)->withQueryString();
        }

        if (Schema::hasTable('document_checks')) {
            $failedChecks = \App\Models\DocumentCheck::query()->whereIn('status', ['failed', 'fail'])->count();
        }

        $tableRows = $rows->getCollection()->map(fn ($d) => [
            '<a href="' . e(route('staff.officer.applications.show', $d->application_id)) . '" class="fw-semibold text-decoration-none">' . e($d->application->reference ?? ('#' . $d->application_id)) . '</a>',
            e($d->application->applicant->name ?? '—'),
            e(ucwords(str_replace('_', ' ', $d->doc_type ?: 'document'))),
            e($d->original_name ?: '—'),
            $d->risk_score === null ? '<span class="text-muted">Not scored</span>' : $this->riskBadge((int) $d->risk_score),
            $this->statusBadge($d->verification_status ?: $d->status),
            optional($d->created_at)->format('Y-m-d H:i') ?: '—',
        ])->all();

        return $this->reportView([
            'title' => 'AI Document Forgery Checks',
            'subtitle' => 'Document risk scores and verification outcomes across submitted applications.',
            'kpis' => [
                ['label' => 'Scored documents', 'value' => number_format($riskCount), 'color' => 'primary'],
                ['label' => 'High risk (70+)', 'value' => number_format($highRisk), 'color' => 'danger'],
                ['label' => 'Failed checks', 'value' => number_format($failedChecks), 'color' => 'warning'],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'value' => $search, 'placeholder' => 'Application ref, applicant or document'],
                ['name' => 'risk', 'label' => 'Risk level', 'type' => 'select', 'value' => $risk, 'options' => [
                    'flagged' => 'Flagged only',
                    'high' => 'High risk (70+)',
                    'medium' => 'Medium risk (40–69)',
                    'all' => 'All documents',
                ]],
            ],
            'sections' => [[
                'title' => 'Document risk register',
                'columns' => ['Application', 'Applicant', 'Document type', 'File', 'Risk score', 'Verification', 'Uploaded'],
                'rows' => $tableRows,
                'empty' => 'No documents match these filters.',
                'paginator' => $rows,
            ]],
        ]);
    }

    public function advancedQr(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        $acc = collect();
        $reg = collect();

        if ($term !== '') {
            if (Schema::hasTable('accreditation_records')) {
                $acc = \App\Models\AccreditationRecord::query()->with('holder')
                    ->where(function ($q) use ($term) {
                        $q->where('certificate_no', $term)
                            ->orWhere('record_number', $term)
                            ->orWhere('accreditation_number', $term)
                            ->orWhere('qr_token', $term);
                    })->get();
            }
            if (Schema::hasTable('registration_records')) {
                $reg = \App\Models\RegistrationRecord::query()->with('contact')
                    ->where(function ($q) use ($term) {
                        $q->where('registration_no', $term)
                            ->orWhere('record_number', $term)
                            ->orWhere('qr_token', $term);
                    })->get();
            }
        }

        $recentAcc = collect();
        if (Schema::hasTable('accreditation_records')) {
            $recentAcc = \App\Models\AccreditationRecord::query()->with('holder')->latest('issued_at')->limit(10)->get();
        }
        $recentReg = collect();
        if (Schema::hasTable('registration_records')) {
            $recentReg = \App\Models\RegistrationRecord::query()->with('contact')->latest('issued_at')->limit(10)->get();
        }

        return view('staff.officer.qr_verify', compact('term', 'acc', 'reg', 'recentAcc', 'recentReg'));
    }

    public function toolsTasks(Request $request)
    {
        $status = $request->query('status');

        $fixRequests = collect();
        if (Schema::hasTable('fix_requests')) {
            $q = \App\Models\FixRequest::query()->with(['application.applicant', 'requester'])->latest();
            if ($status) {
                $q->where('status', $status);
            } else {
                $q->whereIn('status', ['pending', 'in_progress']);
            }
            $fixRequests = $q->limit(15)->get();
        }

        $pendingDocs = collect();
        if (Schema::hasTable('application_documents')) {
            $pendingDocs = \App\Models\ApplicationDocument::query()
                ->with(['application.applicant'])
                ->where('status', 'pending')
                ->latest()
                ->limit(15)
                ->get();
        }

        $queue = Application::query()
            ->with('applicant')
            ->whereIn('status', [
                Application::SUBMITTED,
                Application::OFFICER_REVIEW,
                Application::RETURNED_TO_OFFICER,
                Application::REGISTRAR_FIX_REQUEST,
            ])
            ->latest()
            ->limit(15)
            ->get();

        $followUps = collect();
        if (Schema::hasTable('officer_followups')) {
            $followUps = \App\Models\OfficerFollowUp::query()
                ->whereNotIn('status', ['done', 'completed', 'resolved', 'cancelled'])
                ->orderBy('due_date')
                ->limit(15)
                ->get();
        }

        $fixRows = $fixRequests->map(fn ($f) => [
            '<a href="' . e(route('staff.officer.applications.show', $f->application_id)) . '" class="fw-semibold text-decoration-none">' . e($f->application->reference ?? ('#' . $f->application_id)) . '</a>',
            e($f->application->applicant->name ?? '—'),
            e($f->requester->name ?? '—'),
            $this->statusBadge($f->status),
            optional($f->created_at)->format('Y-m-d H:i') ?: '—',
        ])->all();

        $docRows = $pendingDocs->map(fn ($d) => [
            '<a href="' . e(route('staff.officer.applications.show', $d->application_id)) . '" class="fw-semibold text-decoration-none">' . e($d->application->reference ?? ('#' . $d->application_id)) . '</a>',
            e($d->application->applicant->name ?? '—'),
            e(ucwords(str_replace('_', ' ', $d->doc_type ?: 'document'))),
            e($d->original_name ?: '—'),
            optional($d->created_at)->format('Y-m-d') ?: '—',
        ])->all();

        $queueRows = $queue->map(fn ($a) => [
            '<a href="' . e(route('staff.officer.applications.show', $a->id)) . '" class="fw-semibold text-decoration-none">' . e($a->reference ?: '#' . $a->id) . '</a>',
            e($a->applicant->name ?? '—'),
            e(ucfirst($a->application_type ?: '—')),
            $this->statusBadge($a->status),
            optional($a->created_at)->format('Y-m-d') ?: '—',
        ])->all();

        $followRows = $followUps->map(fn ($f) => [
            e(ucwords(str_replace('_', ' ', $f->type ?: 'task'))),
            e($f->title ?: '—'),
            optional($f->due_date)->format('Y-m-d') ?: '—',
            $this->statusBadge($f->status),
        ])->all();

        return $this->reportView([
            'title' => 'Task Queue',
            'subtitle' => 'Everything awaiting officer action, grouped by workflow.',
            'kpis' => [
                ['label' => 'Returned applications', 'value' => number_format($fixRequests->count()), 'color' => 'danger'],
                ['label' => 'Documents to verify', 'value' => number_format($pendingDocs->count()), 'color' => 'warning'],
                ['label' => 'In officer queue', 'value' => number_format($queue->count()), 'color' => 'primary'],
                ['label' => 'Follow-ups due', 'value' => number_format($followUps->count()), 'color' => 'info'],
            ],
            'filters' => [
                ['name' => 'status', 'label' => 'Fix request status', 'type' => 'select', 'value' => $status, 'options' => [
                    '' => 'Open only',
                    'pending' => 'Pending',
                    'in_progress' => 'In progress',
                    'resolved' => 'Resolved',
                    'cancelled' => 'Cancelled',
                ]],
            ],
            'headerActions' => [
                ['label' => 'Documents', 'url' => route('staff.officer.documents.pending'), 'icon' => 'ri-hourglass-line'],
                ['label' => 'Applications', 'url' => route('staff.officer.applications.index'), 'icon' => 'ri-file-list-3-line'],
            ],
            'sections' => [
                ['title' => 'Returned applications (fix requests)', 'columns' => ['Application', 'Applicant', 'Requested by', 'Status', 'Raised'], 'rows' => $fixRows, 'empty' => 'No returned applications.'],
                ['title' => 'Documents pending verification', 'columns' => ['Application', 'Applicant', 'Document type', 'File', 'Uploaded'], 'rows' => $docRows, 'empty' => 'No documents are awaiting verification.'],
                ['title' => 'Officer review queue', 'columns' => ['Application', 'Applicant', 'Type', 'Status', 'Submitted'], 'rows' => $queueRows, 'empty' => 'The officer queue is empty.'],
                ['title' => 'Follow-ups', 'columns' => ['Type', 'Title', 'Due', 'Status'], 'rows' => $followRows, 'empty' => 'No follow-ups in the queue.'],
            ],
        ]);
    }

    public function toolsDrafts(Request $request)
    {
        $search = $request->query('q');
        $query = Application::query()
            ->with('applicant')
            ->where(function ($q) {
                $q->where('status', Application::DRAFT);
                if ($this->hasColumn('applications', 'is_draft')) {
                    $q->orWhere('is_draft', true);
                }
            })
            ->latest('updated_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('applicant', fn ($aq) => $aq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $drafts = $query->paginate(25)->withQueryString();

        $rows = $drafts->getCollection()->map(fn ($a) => [
            e($a->reference ?: '#' . $a->id),
            e($a->applicant->name ?? '—'),
            e($a->applicant->email ?? '—'),
            e(ucfirst($a->application_type ?: '—')),
            e(ucwords(str_replace('_', ' ', $a->request_type ?: '—'))),
            optional($a->updated_at)->format('Y-m-d H:i') ?: '—',
        ])->all();

        return $this->reportView([
            'title' => 'Saved Drafts',
            'subtitle' => 'Applications started by applicants but not yet submitted — visible for oversight and follow-up.',
            'kpis' => [
                ['label' => 'Draft applications', 'value' => number_format($drafts->total()), 'color' => 'warning'],
                ['label' => 'Oldest in view', 'value' => optional($drafts->getCollection()->last()?->updated_at)->format('Y-m-d') ?: '—', 'color' => 'info'],
            ],
            'filters' => [
                ['name' => 'q', 'label' => 'Search', 'type' => 'text', 'value' => $search, 'placeholder' => 'Reference, applicant name or email'],
            ],
            'sections' => [[
                'title' => 'Draft applications',
                'columns' => ['Reference', 'Applicant', 'Email', 'Type', 'Request', 'Last updated'],
                'rows' => $rows,
                'empty' => 'No draft applications found.',
                'paginator' => $drafts,
            ]],
        ]);
    }

    public function toolsSops()
    {
        return view('staff.officer.sops');
    }

    // --- Renewals & expiry wrapper pages ---
    public function renewalsExpiring()
    {
        return $this->expiriesPage('Expiring Soon');
    }

    public function renewalsExpired()
    {
        return $this->expiriesPage('Expired', true);
    }

    public function renewalsQueue()
    {
        return $this->followUpQueuePage();
    }

    // --- Compliance wrapper pages ---
    public function complianceViolations()
    {
        return $this->complianceListPage('Reported Violations');
    }

    public function complianceCases()
    {
        return $this->complianceCasesPage();
    }

    public function complianceUnaccredited()
    {
        return $this->unaccreditedReportsPage();
    }

    // --- Advanced / tools wrapper pages ---
    public function advancedAudit()
    {
        return $this->auditTrailPage();
    }

    public function toolsProfile()
    {
        return redirect()->route('settings');
    }

    // --- CSV export ---
    public function applicationsExportCsv(Request $request, string $list)
    {
        $q = $this->applicationsBaseQuery($list, $request);

        $filename = 'applications_' . $list . '_' . now()->format('Ymd_His') . '.csv';
        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ref', 'Applicant', 'Email', 'Type', 'Scope', 'Request', 'Status', 'Submitted', 'Category']);
            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $app) {
                    fputcsv($out, [
                        $app->reference,
                        $app->applicant?->name,
                        $app->applicant?->email,
                        $app->application_type,
                        $app->journalist_scope,
                        $app->request_type,
                        $app->status,
                        optional($app->created_at)->format('Y-m-d H:i'),
                        $app->categoryLabel(),
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // --- Records wrapper pages ---
    public function recordsJournalists()
    {
        return redirect()->route('staff.officer.records.accredited-journalists');
    }

    public function recordsMediaHouses()
    {
        return redirect()->route('staff.officer.records.registered-mediahouses');
    }

    public function recordsSuspended()
    {
        abort(404);
    }

    // --- Documents wrapper pages ---
    public function documentsUploaded()
    {
        return $this->documentsListPage('Uploaded Documents', null);
    }

    public function documentsPending()
    {
        return $this->documentsListPage('Documents Pending Verification', 'pending');
    }

    public function documentsVerified()
    {
        return $this->documentsListPage('Verified Documents', 'verified');
    }

    public function documentsRejected()
    {
        return $this->documentsListPage('Returned Documents', 'rejected');
    }

    /* =========================
     * Internal page builders
     * ========================= */

    private function reportView(array $payload)
    {
        return view('staff.officer.report', $payload);
    }

    private function yearOptions(): array
    {
        $years = [];
        for ($y = now()->year; $y >= now()->year - 5; $y--) {
            $years[(string) $y] = (string) $y;
        }
        return $years;
    }

    private function statusBadge(?string $status): string
    {
        $value = $status ?: 'unknown';
        $label = ucwords(str_replace('_', ' ', $value));
        $green = ['active', 'issued', 'approved', 'resolved', 'completed', 'paid', 'verified', 'accepted', 'published', 'done'];
        $amber = ['pending', 'submitted', 'review', 'in_progress', 'processing', 'awaiting', 'warning', 'normal', 'medium'];
        $red = ['expired', 'rejected', 'failed', 'suspended', 'revoked', 'high', 'critical', 'urgent', 'cancelled'];

        $class = 'bg-secondary';
        foreach ($green as $key) {
            if (str_contains($value, $key)) {
                $class = 'bg-success';
                break;
            }
        }
        if ($class === 'bg-secondary') {
            foreach ($amber as $key) {
                if (str_contains($value, $key)) {
                    $class = 'bg-warning text-dark';
                    break;
                }
            }
        }
        if ($class === 'bg-secondary') {
            foreach ($red as $key) {
                if (str_contains($value, $key)) {
                    $class = 'bg-danger';
                    break;
                }
            }
        }

        return '<span class="badge ' . $class . '">' . e($label) . '</span>';
    }

    private function riskBadge(int $score): string
    {
        $class = $score >= 70 ? 'bg-danger' : ($score >= 40 ? 'bg-warning text-dark' : 'bg-success');
        return '<span class="badge ' . $class . '">' . $score . '/100</span>';
    }

    private function scopedRecordCount(string $class, ?array $statuses = null, ?\Closure $extra = null): int
    {
        if (!class_exists($class)) {
            return 0;
        }
        $model = new $class;
        if (!Schema::hasTable($model->getTable())) {
            return 0;
        }
        $query = $class::query();
        $this->scopeRecordsByRegion($query);
        if ($statuses) {
            $query->whereIn('status', $statuses);
        }
        if ($extra) {
            $extra($query);
        }
        return (int) $query->count();
    }

    private function applicationsList(string $list, ?string $requestTypeFilter = null)
    {
        $titles = [
            'all' => 'All Applications',
            'new' => 'New Applications',
            'pending' => 'Pending Accounts Review',
            'approved' => 'Approved',
            'rejected' => 'Returned for Correction',
        ];

        $categoryTitles = [
            'new' => 'New Applications',
            'renewal' => 'Renewal Applications',
            'replacement' => 'Replacement Applications',
        ];

        $requestTypeFilter = $requestTypeFilter ?: request()->query('request_type');

        $q = $this->applicationsBaseQuery($list, request());

        if ($requestTypeFilter && in_array($requestTypeFilter, ['new', 'renewal', 'replacement'])) {
            $q->where('request_type', $requestTypeFilter);
        }

        $scopeFilter = request()->query('scope');
        if ($scopeFilter && in_array($scopeFilter, ['local', 'foreign'])) {
            $q->where('journalist_scope', $scopeFilter);
        }

        $applications = $q->paginate(20)->withQueryString();

        $title = $titles[$list] ?? 'Applications';
        if ($requestTypeFilter && isset($categoryTitles[$requestTypeFilter])) {
            $title = $categoryTitles[$requestTypeFilter];
        }

        return view('staff.officer.applications_list_filtered', [
            'title' => $title,
            'list' => $list,
            'applications' => $applications,
            'bucketLabels' => Application::bucketLabels(),
            'requestTypeFilter' => $requestTypeFilter,
            'scopeFilter' => $scopeFilter ?? null,
        ]);
    }

    private function applicationsBaseQuery(string $list, Request $request)
    {
        $user = Auth::user();
        $q = Application::query()->with(['applicant', 'assignedOfficer'])->latest();

        // Region scoping: officers see only their assigned region(s).
        // Super admin / unscoped roles see all.
        $this->scopeApplicationsByRegion($q);

        // New/pending work is pooled, but completed review results must remain
        // visible to the officer even when another officer owns the lock.
        // Otherwise approved applications disappear from the Approved page.
        if (!in_array(strtolower($list), ['approved', 'rejected'], true)) {
            $q->where(function ($qq) use ($user) {
                $qq->whereNull('assigned_officer_id')
                    ->orWhere('assigned_officer_id', $user->id);
            });

            $q->where(function ($qq) use ($user) {
                $qq->whereNull('locked_at')
                    ->orWhere('locked_at', '<=', now()->subHours(2))
                    ->orWhere('locked_by', $user->id);
            });
        }

        // exclude drafts (drafts remain in applicant portal)
        $q->where(function ($qq) {
            $qq->whereNull('status')->orWhere('status', '!=', Application::DRAFT);
        });
        if ($this->hasColumn('applications', 'is_draft')) {
            $q->where(function ($qq) {
                $qq->whereNull('is_draft')->orWhere('is_draft', false);
            });
        }

        // Year filter (default to current year)
        $year = $request->query('year', now()->year);
        $q->whereYear('created_at', $year);

        // Processing status filter
        $processingStatus = $request->query('processing_status', 'all');
        if ($processingStatus === 'processed') {
            // Applications reviewed by Officer and sent forward (Registrar/Accounts)
            $q->whereIn('status', [
                Application::OFFICER_APPROVED,
                Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
                Application::VERIFIED_BY_OFFICER_PENDING_REGISTRAR,
                Application::REGISTRAR_REVIEW,
                Application::REGISTRAR_APPROVED,
                Application::ACCOUNTS_REVIEW,
                Application::PAYMENT_VERIFIED,
                'production_queue',
                'produced_ready_for_collection',
                'issued',
            ]);
        } elseif ($processingStatus === 'unprocessed') {
            // Applications not yet reviewed by Officer.
            // Files sent back to the applicant (correction_requested) are
            // excluded from the active queue — they reappear when the
            // applicant resubmits (status returns to SUBMITTED).
            $q->whereIn('status', [
                Application::SUBMITTED,
                Application::OFFICER_REVIEW,
                Application::RETURNED_TO_OFFICER,
            ]);
        }

        // list scopes
        $list = strtolower($list);
        if ($list === 'new') {
            $q->where('status', Application::SUBMITTED)
                ->whereNull('assigned_officer_id')
                ->where(function ($lq) {
                    $lq->whereNull('locked_at')
                        ->orWhere('locked_at', '<=', now()->subHours(2));
                });
        } elseif ($list === 'pending') {
            // REDEFINED: Applications processed by Officer but NOT yet attended to by Accounts
            $q->whereIn('status', [
                Application::ACCOUNTS_REVIEW,
                Application::PENDING_ACCOUNTS_REVIEW_FROM_REGISTRAR,
                Application::REG_FEE_SUBMITTED_AWAITING_VERIFICATION,
                'renewal_submitted_awaiting_accounts_verification',
            ]);
        } elseif ($list === 'approved') {
            $q->whereIn('status', [
                Application::OFFICER_APPROVED,
                Application::APPROVED_BY_OFFICER_AWAITING_PAYMENT_AND_REGISTRAR_MASTER,
                Application::VERIFIED_BY_OFFICER_PENDING_REGISTRAR,
                Application::REGISTRAR_REVIEW,
                Application::REGISTRAR_APPROVED,
                Application::APP_FEE_VERIFIED,
                Application::AWAITING_REG_FEE,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                Application::PAYMENT_VERIFIED,
                Application::PAID_CONFIRMED,
                Application::REGISTERED,
                Application::PRODUCTION_QUEUE,
                Application::PRODUCED_READY,
                Application::ISSUED,
                Application::PRINTED,
                Application::PRODUCED_READY_FOR_COLLECTION,
            ]);
        } elseif ($list === 'rejected') {
            $q->whereIn('status', [Application::CORRECTION_REQUESTED, Application::OFFICER_REJECTED, Application::RETURNED_TO_OFFICER])
                ->where('last_action_by', $user->id);
        }

        // Advanced filters
        if ($gender = $request->query('gender')) {
            if (in_array($gender, ['male', 'female', 'other'], true) && $this->hasColumn('applications', 'gender')) {
                $q->where('gender', $gender);
            }
        }

        if ($ageMin = $request->query('age_min')) {
            if ($this->hasColumn('applications', 'dob')) {
                $q->whereNotNull('dob')
                    ->whereDate('dob', '<=', now()->subYears((int) $ageMin));
            }
        }

        if ($ageMax = $request->query('age_max')) {
            if ($this->hasColumn('applications', 'dob')) {
                $q->whereNotNull('dob')
                    ->whereDate('dob', '>=', now()->subYears((int) $ageMax + 1)->addDay());
            }
        }

        if ($org = $request->query('organisation')) {
            if ($this->hasColumn('applications', 'employer_name')) {
                $q->where('employer_name', 'like', "%{$org}%");
            }
        }

        if ($province = $request->query('province')) {
            if ($this->hasColumn('applications', 'province')) {
                $q->where('province', $province);
            } else {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.province')) = ?", [$province]);
            }
        }

        if ($collectionRegion = $request->query('collection_region')) {
            if ($this->hasColumn('applications', 'collection_region')) {
                $q->where('collection_region', $collectionRegion);
            }
        }

        if ($category = $request->query('category')) {
            $validCategories = array_merge(array_keys(Application::accreditationCategories()), array_keys(Application::massMediaCategories()));
            if (in_array($category, $validCategories, true)) {
                $q->where(function ($categoryQuery) use ($category) {
                    if ($this->hasColumn('applications', 'accreditation_category_code')) {
                        $categoryQuery->orWhere('accreditation_category_code', $category);
                    }
                    if ($this->hasColumn('applications', 'media_house_category_code')) {
                        $categoryQuery->orWhere('media_house_category_code', $category);
                    }
                });
            }
        }

        if ($nationality = $request->query('nationality')) {
            if ($this->hasColumn('applications', 'nationality')) {
                $q->where('nationality', 'like', "%{$nationality}%");
            }
        }

        // filters
        if ($bucket = $request->query('bucket')) {
            $q->applyBucket($bucket);
        } else {
            // Individual filters as fallback
            if ($rt = $request->query('request_type')) {
                if (in_array($rt, ['new', 'renewal', 'replacement'], true))
                    $q->where('request_type', $rt);
            }
            if ($sc = $request->query('scope')) {
                if (in_array($sc, ['local', 'foreign'], true)) {
                    if ($sc === 'local') {
                        $q->where(fn($w) => $w->where('journalist_scope', 'local')->orWhereNull('journalist_scope'));
                    } else {
                        $q->where('journalist_scope', 'foreign');
                    }
                }
            }
        }

        if ($t = $request->query('application_type')) {
            if (in_array($t, ['accreditation', 'registration'], true))
                $q->where('application_type', $t);
        }
        // Status is not exposed as a general filter (list defaults apply).
        // Only the Rejected/Returned tabs use an exact status filter.
        if ($list === 'rejected') {
            $st = $request->query('status');
            if (in_array($st, [Application::OFFICER_REJECTED, Application::RETURNED_TO_OFFICER], true)) {
                $q->where('status', $st);
            }
        }
        if ($from = $request->query('date_from')) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $q->whereDate('created_at', '<=', $to);
        }
        if ($search = trim((string) $request->query('q'))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('applicant', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });

                // Search by accreditation/registration number if available
                if ($this->hasColumn('applications', 'accreditation_number')) {
                    $qq->orWhere('accreditation_number', 'like', "%{$search}%");
                }
                if ($this->hasColumn('applications', 'registration_number')) {
                    $qq->orWhere('registration_number', 'like', "%{$search}%");
                }
            });
        }

        return $q;
    }

    private function recordsListPage(string $title, string $type)
    {
        $rows = collect();
        if ($type === 'accreditation' && class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records')) {
            $q = \App\Models\AccreditationRecord::query()->with('holder')->latest('issued_at');
            $this->scopeRecordsByRegion($q);
            $rows = $q->paginate(20);
        }
        if ($type === 'registration' && class_exists(\App\Models\RegistrationRecord::class) && Schema::hasTable('registration_records')) {
            $q = \App\Models\RegistrationRecord::query()->with('contact')->latest('issued_at');
            $this->scopeRecordsByRegion($q);
            $rows = $q->paginate(20);
        }

        return view('staff.officer.records_list', [
            'title' => $title,
            'type' => $type,
            'rows' => $rows,
        ]);
    }

    private function recordsSuspendedPage()
    {
        $acc = collect();
        $reg = collect();
        if (class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records')) {
            $q = \App\Models\AccreditationRecord::whereIn('status', ['suspended', 'revoked'])->latest('updated_at');
            $this->scopeRecordsByRegion($q);
            $acc = $q->paginate(20, ['*'], 'acc');
        }
        if (class_exists(\App\Models\RegistrationRecord::class) && Schema::hasTable('registration_records')) {
            $q = \App\Models\RegistrationRecord::whereIn('status', ['suspended', 'revoked'])->latest('updated_at');
            $this->scopeRecordsByRegion($q);
            $reg = $q->paginate(20, ['*'], 'reg');
        }

        return view('staff.officer.records_suspended', [
            'acc' => $acc,
            'reg' => $reg,
        ]);
    }

    private function documentsListPage(string $title, ?string $verificationStatus)
    {
        $apps = collect();

        if (class_exists(\App\Models\ApplicationDocument::class) && Schema::hasTable('application_documents')) {
            $docStatus = null;
            if ($verificationStatus === 'pending')
                $docStatus = 'pending';
            if ($verificationStatus === 'rejected')
                $docStatus = 'rejected';

            // Different upload/review paths historically used different
            // labels. Treat all accepted verification labels consistently so
            // verified documents are not hidden by the old "accepted" filter.
            $verifiedStatuses = ['accepted', 'verified', 'approved', 'complete', 'completed'];
            $verifiedReviewStatuses = ['accepted', 'verified', 'approved', 'complete', 'completed'];

            $appsQ = Application::query()
                ->with(['applicant', 'documents'])
                ->latest();

            // exclude drafts
            $appsQ->where(function ($qq) {
                $qq->whereNull('status')->orWhere('status', '!=', Application::DRAFT);
            });

            // region scoping for accreditation_officer / production
            $this->scopeApplicationsByRegion($appsQ);

            if ($t = request()->query('application_type')) {
                if (in_array($t, ['accreditation', 'registration'], true))
                    $appsQ->where('application_type', $t);
            }

            if ($from = request()->query('date_from')) {
                $appsQ->whereDate('created_at', '>=', $from);
            }
            if ($to = request()->query('date_to')) {
                $appsQ->whereDate('created_at', '<=', $to);
            }

            $appsQ->whereHas('documents', function ($dq) use ($docStatus, $verificationStatus, $verifiedStatuses, $verifiedReviewStatuses) {
                if ($docStatus) {
                    $dq->where('status', $docStatus);
                } elseif ($verificationStatus === 'verified') {
                    $dq->where(function ($statusQuery) use ($verifiedStatuses, $verifiedReviewStatuses) {
                        $statusQuery->whereIn('status', $verifiedStatuses)
                            ->orWhereIn('verification_status', $verifiedReviewStatuses);
                    });
                }
            });

            if ($docStatus || $verificationStatus === 'verified') {
                $appsQ->with(['documents' => function ($dq) use ($docStatus, $verificationStatus, $verifiedStatuses, $verifiedReviewStatuses) {
                    if ($docStatus) {
                        $dq->where('status', $docStatus);
                    } elseif ($verificationStatus === 'verified') {
                        $dq->where(function ($statusQuery) use ($verifiedStatuses, $verifiedReviewStatuses) {
                            $statusQuery->whereIn('status', $verifiedStatuses)
                                ->orWhereIn('verification_status', $verifiedReviewStatuses);
                        });
                    }
                    $dq->latest();
                }]);
            }

            $apps = $appsQ->paginate(15)->withQueryString();
        }

        return view('staff.officer.documents_grouped', [
            'title' => $title,
            'applications' => $apps,
        ]);
    }

    private function expiriesPage(string $title, bool $expiredOnly = false)
    {
        $months = (int) request()->query('months', 3);
        $cutoff = now()->addMonths(max(1, $months));

        $acc = collect();
        $reg = collect();

        if (class_exists(\App\Models\AccreditationRecord::class) && Schema::hasTable('accreditation_records')) {
            $q = \App\Models\AccreditationRecord::query()->with('holder');
            $this->scopeRecordsByRegion($q);
            $q->whereNotNull('expires_at');
            if ($expiredOnly) {
                $q->where('expires_at', '<', now());
            } else {
                $q->where('expires_at', '<=', $cutoff)->where('expires_at', '>=', now());
            }
            $acc = $q->orderBy('expires_at')->paginate(20, ['*'], 'acc');
        }

        if (class_exists(\App\Models\RegistrationRecord::class) && Schema::hasTable('registration_records')) {
            $q = \App\Models\RegistrationRecord::query()->with('contact');
            $this->scopeRecordsByRegion($q);
            $q->whereNotNull('expires_at');
            if ($expiredOnly) {
                $q->where('expires_at', '<', now());
            } else {
                $q->where('expires_at', '<=', $cutoff)->where('expires_at', '>=', now());
            }
            $reg = $q->orderBy('expires_at')->paginate(20, ['*'], 'reg');
        }

        return view('staff.officer.expiries', [
            'title' => $title,
            'months' => $months,
            'isExpired' => $expiredOnly,
            'accreditation' => $acc,
            'registration' => $reg,
            // Backwards-compatible aliases.
            'acc' => $acc,
            'reg' => $reg,
        ]);
    }

    private function followUpQueuePage()
    {
        $rows = collect();
        if (class_exists(\App\Models\OfficerFollowUp::class)) {
            $model = new \App\Models\OfficerFollowUp;
            if (Schema::hasTable($model->getTable())) {
                $query = \App\Models\OfficerFollowUp::query()->latest();

                if ($status = request()->query('status')) {
                    $query->where('status', $status);
                }
                if ($type = request()->query('type')) {
                    $query->where('type', $type);
                }
                if ($search = request()->query('q')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                            ->orWhere('notes', 'like', "%{$search}%");
                    });
                }

                $rows = $query->paginate(20)->withQueryString();
            }
        }
        return view('staff.officer.followups', [
            'title' => 'Officer Follow-up Queue',
            'rows' => $rows,
            // The view historically used $items.
            'items' => $rows,
        ]);
    }

    private function complianceListPage(string $title)
    {
        $rows = collect();
        if (class_exists(\App\Models\ComplianceViolation::class) && Schema::hasTable('compliance_violations')) {
            $query = \App\Models\ComplianceViolation::query()->latest();

            if ($status = request()->query('status')) {
                $query->where('status', $status);
            }
            if ($search = request()->query('q')) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $rows = $query->paginate(20)->withQueryString();
        }
        return view('staff.officer.compliance_list', [
            'title' => $title,
            'rows' => $rows,
        ]);
    }

    private function complianceCasesPage()
    {
        $rows = collect();
        if (class_exists(\App\Models\ComplianceCase::class) && Schema::hasTable('compliance_cases')) {
            $query = \App\Models\ComplianceCase::query()->with('assignedOfficer')->latest();

            if ($status = request()->query('status')) {
                $query->where('status', $status);
            }
            if ($search = request()->query('q')) {
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%");
                });
            }

            $rows = $query->paginate(20)->withQueryString();
        }
        return view('staff.officer.compliance_cases', [
            'title' => 'Investigation Cases',
            'rows' => $rows,
        ]);
    }

    private function unaccreditedReportsPage()
    {
        $rows = collect();
        if (class_exists(\App\Models\UnaccreditedReport::class) && Schema::hasTable('unaccredited_reports')) {
            $rows = \App\Models\UnaccreditedReport::query()->latest()->paginate(20);
        }
        return view('staff.officer.unaccredited_reports', [
            'title' => 'Unaccredited Practice Reports',
            'rows' => $rows,
        ]);
    }

    private function auditTrailPage()
    {
        $applicationId = request()->query('application_id');
        $logs = collect();
        if (class_exists(\App\Models\ActivityLog::class) && Schema::hasTable('activity_logs')) {
            $user = Auth::user();
            $isScopedUser = \App\Support\RegionAccessGuard::userIsScoped($user);

            if ($applicationId) {
                // If a single application is requested, enforce region access.
                $application = Application::find($applicationId);
                if ($isScopedUser) {
                    if (!$application || !\App\Support\RegionAccessGuard::userCanAccess($user, $application)) {
                        $logs = \App\Models\ActivityLog::query()->whereRaw('1=0')->paginate(25);
                        return view('staff.officer.audit_trail', [
                            'title' => 'Audit Trail (Per Application)',
                            'logs' => $logs,
                            'applicationId' => $applicationId,
                        ]);
                    }
                }
                $logs = \App\Models\ActivityLog::query()
                    ->where('entity_type', Application::class)
                    ->where('entity_id', $applicationId)
                    ->latest()
                    ->paginate(25);
            } else {
                $q = \App\Models\ActivityLog::query()->latest();
                if ($isScopedUser) {
                    $regions = $user->assignedCollectionRegions();
                    if (empty($regions)) {
                        $q->whereRaw('1=0');
                    } else {
                        // Limit to logs whose Application subject is in the
                        // user's regions. Logs for other entity_types are
                        // hidden from scoped users to avoid cross-region leaks.
                        $appIds = Application::whereIn('collection_region', $regions)->pluck('id');
                        $q->where('entity_type', Application::class)
                            ->whereIn('entity_id', $appIds);
                    }
                }
                $logs = $q->paginate(25);
            }
        }

        return view('staff.officer.audit_trail', [
            'title' => 'Audit Trail (Per Application)',
            'logs' => $logs,
            'applicationId' => $applicationId,
        ]);
    }

    /**
     * Forward application to Registrar without approval (for waivers/special cases)
     */
    public function forwardWithoutApproval(Request $request, Application $application)
    {
        $this->ensureCanAccessRegion($application);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        // Use new workflow service - enforces strict transitions
        try {
            $application = ApplicationWorkflowService::forwardWithoutApproval($application, [
                'notes' => $data['reason'],
                'actor_role' => session('active_staff_role', 'accreditation_officer'),
            ]);

            // Save reason in additional field if column exists
            $this->safeSet($application, [
                'forward_no_approval_reason' => $data['reason'],
            ]);

            return back()->with('success', 'Application forwarded to Registrar for review. Reason: ' . $data['reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }

    /* =========================
    /* ========================= Helpers ========================= */

    private function audit(string $action, Application $application, ?string $from, ?string $to, array $meta = []): void
    {
        $payload = array_merge([
            'actor_role' => session('active_staff_role'),
            'actor_user_id' => Auth::id(),
            'from_status' => $from,
            'to_status' => $to,
        ], $meta);

        ActivityLogger::log($action, $application, $from, $to, $payload);
        \App\Support\AuditTrail::log($action, $application, $payload);
    }

    private function safeSet(Application $application, array $fields): void
    {
        foreach ($fields as $k => $v) {
            if ($this->hasColumn('applications', $k)) {
                $application->{$k} = $v;
            }
        }
        $application->save();
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function persistMessageIfAvailable(Application $application, string $message): void
    {
        if (class_exists(\App\Models\ApplicationMessage::class) && Schema::hasTable('application_messages')) {
            \App\Models\ApplicationMessage::create([
                'application_id' => $application->id,
                'from_user_id' => Auth::id(),
                'to_user_id' => $application->applicant_user_id,
                'channel' => 'internal',
                'subject' => null,
                'body' => $message,
                'sent_at' => now(),
                'read_at' => null,
            ]);
        }
    }

    /**
     * Renewals production queue
     */
    public function renewalsProductionQueue(Request $request)
    {
        $query = \App\Models\RenewalApplication::query()
            ->with(['applicant', 'originalApplication'])
            ->readyForProduction()
            ->latest('payment_verified_at');

        // Region scoping: officers see renewals whose original application was
        // collected in their assigned region(s). Renewals that are not yet linked
        // to an original application have no region to compare and stay visible.
        // Admins / unscoped roles see all.
        $user = Auth::user();
        $isScopedUser = \App\Support\RegionAccessGuard::userIsScoped($user);
        if ($isScopedUser) {
            $regions = $user->assignedCollectionRegions();
            if (empty($regions)) {
                $query->whereRaw('1=0');
            } else {
                $query->where(function ($q) use ($regions) {
                    $q->whereNull('original_application_id')
                        ->orWhereHas('originalApplication', function ($oq) use ($regions) {
                            $oq->whereIn('collection_region', $regions);
                        });
                });
            }
        }

        // Filter by renewal type
        if ($request->filled('renewal_type')) {
            $query->where('renewal_type', $request->renewal_type);
        }

        $renewals = $query->paginate(20)->withQueryString();

        // KPIs (region-scoped via originalApplication; admins / unscoped roles bypass)
        $renewalRegionScope = function ($q) {
            $u = Auth::user();
            if (!\App\Support\RegionAccessGuard::userIsScoped($u))
                return;
            $regions = $u->assignedCollectionRegions();
            if (empty($regions)) {
                $q->whereRaw('1=0');
            } else {
                $q->where(function ($oq) use ($regions) {
                    $oq->whereNull('original_application_id')
                        ->orWhereHas('originalApplication', function ($inner) use ($regions) {
                            $inner->whereIn('collection_region', $regions);
                        });
                });
            }
        };

        $kpis = [
            'ready' => \App\Models\RenewalApplication::readyForProduction()->tap($renewalRegionScope)->count(),
            'in_production' => \App\Models\RenewalApplication::inProduction()->tap($renewalRegionScope)->count(),
            'ready_for_collection' => \App\Models\RenewalApplication::readyForCollection()->tap($renewalRegionScope)->count(),
        ];

        return view('staff.officer.renewals_production', compact('renewals', 'kpis'));
    }

    /**
     * Show renewal for production
     */
    public function showRenewalProduction(\App\Models\RenewalApplication $renewal)
    {
        $renewal->load(['applicant', 'originalApplication', 'changeRequests']);
        if ($renewal->originalApplication) {
            $this->ensureCanAccessRegion($renewal->originalApplication);
        }

        return view('staff.officer.renewal_production_show', compact('renewal'));
    }

    /**
     * Generate renewed document
     */
    public function generateRenewalDocument(Request $request, \App\Models\RenewalApplication $renewal)
    {
        $renewal->loadMissing('originalApplication');
        if ($renewal->originalApplication) {
            $this->ensureCanAccessRegion($renewal->originalApplication);
        }

        // Validate status
        if ($renewal->status !== \App\Models\RenewalApplication::RENEWAL_PAYMENT_VERIFIED) {
            return back()->with('error', 'Renewal is not ready for production.');
        }

        $from = $renewal->status;

        DB::transaction(function () use ($renewal, $from) {
            // Update renewal status
            $renewal->update([
                'status' => \App\Models\RenewalApplication::RENEWAL_IN_PRODUCTION,
                'current_stage' => 'production',
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
            ]);

            ActivityLogger::log('renewal_production_started', $renewal, $from, $renewal->status, [
                'renewal_type' => $renewal->renewal_type,
                'original_number' => $renewal->original_number,
            ]);
        });

        return back()->with('success', 'Renewal document generation started.');
    }

    /**
     * Mark renewal as produced and ready for collection
     */
    public function markRenewalProduced(Request $request, \App\Models\RenewalApplication $renewal)
    {
        $renewal->loadMissing('originalApplication');
        if ($renewal->originalApplication) {
            $this->ensureCanAccessRegion($renewal->originalApplication);
        }

        $data = $request->validate([
            'collection_location' => ['required', 'string', 'max:255'],
        ]);

        // Validate status
        if ($renewal->status !== \App\Models\RenewalApplication::RENEWAL_IN_PRODUCTION) {
            return back()->with('error', 'Renewal is not in production.');
        }

        $from = $renewal->status;

        DB::transaction(function () use ($renewal, $data, $from) {
            $renewal->update([
                'status' => \App\Models\RenewalApplication::RENEWAL_PRODUCED_READY_FOR_COLLECTION,
                'current_stage' => 'collection',
                'produced_at' => now(),
                'produced_by' => Auth::id(),
                'collection_location' => $data['collection_location'],
                'last_action_at' => now(),
                'last_action_by' => Auth::id(),
            ]);

            ActivityLogger::log('renewal_produced', $renewal, $from, $renewal->status, [
                'renewal_type' => $renewal->renewal_type,
                'collection_location' => $data['collection_location'],
            ]);
        });

        return back()->with('success', 'Renewal marked as produced and ready for collection.');
    }

    /**
     * Print renewal document
     */
    public function printRenewalDocument(\App\Models\RenewalApplication $renewal)
    {
        $renewal->loadMissing('originalApplication');
        if ($renewal->originalApplication) {
            $this->ensureCanAccessRegion($renewal->originalApplication);
        }

        // Increment print count
        $renewal->increment('print_count');

        ActivityLogger::log('renewal_document_printed', $renewal, $renewal->status, $renewal->status, [
            'renewal_type' => $renewal->renewal_type,
            'print_count' => $renewal->print_count,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Print logged successfully.',
            'print_count' => $renewal->print_count,
        ]);
    }
}
