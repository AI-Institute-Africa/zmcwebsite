<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\PrintLog;
use App\Models\DocumentVersion;
use App\Models\Notice;
use App\Models\Event;
use App\Models\News;
use App\Services\ApplicationWorkflow;
use App\Services\ApplicationWorkflowService;
use App\Services\ActivityLogger;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RegistrarController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();

        // The registrar's "approved" universe — covers everything the registrar
        // has signed off on, even if the application is still moving through
        // downstream stages (Accounts → Production → Printed → Issued).
        $registrarApprovedStatuses = [
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            Application::ACCOUNTS_REVIEW,
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::PAYMENT_VERIFIED,
            Application::PAID_CONFIRMED,
            Application::PRODUCTION_QUEUE,
            Application::PRODUCED_READY,
            Application::CARD_GENERATED,
            Application::CERT_GENERATED,
            Application::PRINTED,
            Application::ISSUED,
        ];

        // KPIs
        $kpis = [
            // Total applications across the system. Powers the "All Applications"
            // card so the registrar can see overall volume at a glance.
            'total_applications' => Application::count(),
            'awaiting_registrar' => Application::whereIn('status', [
                Application::REGISTRAR_REVIEW,
                Application::FORWARDED_TO_REGISTRAR,
                Application::VERIFIED_BY_OFFICER,
                Application::OFFICER_APPROVED,
            ])->count(),
            // Use COALESCE so we count the actual registrar review timestamp
            // (falling back to last_action_at only when the timestamp is NULL,
            // e.g. for legacy rows). Using OR would over-count by including
            // any downstream activity that touched last_action_at.
            'approved_today' => Application::whereIn('status', $registrarApprovedStatuses)
                ->whereRaw('COALESCE(registrar_reviewed_at, last_action_at) >= ?', [$today])
                ->count(),
            'approved_this_week' => Application::whereIn('status', $registrarApprovedStatuses)
                ->whereRaw('COALESCE(registrar_reviewed_at, last_action_at) >= ?', [$thisWeek])
                ->count(),
            'returned_to_officer' => Application::whereIn('status', [
                Application::RETURNED_TO_OFFICER,
                Application::REGISTRAR_FIX_REQUEST,
            ])->count(),
            // Anything currently sitting in a payment-related stage —
            // either the registrar has approved and Accounts has the file,
            // the applicant is being asked to pay, payment is being verified,
            // or registrar bounced the file back to Accounts.
            'awaiting_payment_approval' => Application::whereIn('status', [
                Application::REGISTRAR_APPROVED,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::RETURNED_TO_ACCOUNTS,
            ])->count(),
            'category_mismatches' => ActivityLog::where('action', 'registrar_reassign_category')->where('created_at', '>=', $thisWeek)->count(),
            'certificates_generated_today' => DocumentVersion::where('document_type', 'certificate')->where('created_at', '>=', $today)->count(),
            'prints_today' => PrintLog::where('created_at', '>=', $today)->count(),
            'flagged_reprints' => Application::where('print_count', '>', 1)->count(),
        ];

        $query = Application::query()
            ->with(['applicant', 'lastActionBy'])
            ->withCount('printLogs');

        $year = $request->input('year', now()->year);
        $isCurrentYear = (int) $year === now()->year;
        if (!$isCurrentYear) {
            $query->whereYear('created_at', $year);
        }

        // NOTE: We deliberately do NOT filter by assigned_officer_id or locked_at
        // here. Those concurrency filters are for accreditation officers who
        // claim individual applications. Registrars must see EVERY application
        // that has been forwarded to them, regardless of which officer
        // originally handled it. Filtering by assigned_officer_id (which is
        // always set after an officer touches an app) was hiding the entire
        // queue from the registrar.

        if ($request->filled('type')) {
            $query->where('application_type', $request->type);
        }
        if ($request->filled('classification')) {
            $query->where('journalist_scope', $request->classification);
        }
        if ($request->filled('residency')) {
            $query->where('residency_type', $request->residency);
        }
        // Whitelist of statuses a registrar is allowed to view from this
        // dashboard. The dropdown options visible in the UI also include some
        // post-registrar statuses (e.g. registrar_approved, returned_to_officer)
        // so the registrar can drill into recently-handled work.
        $registrarVisibleStatuses = [
            Application::FORWARDED_TO_REGISTRAR,
            Application::REGISTRAR_REVIEW,
            Application::VERIFIED_BY_OFFICER,
            Application::OFFICER_APPROVED,
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            Application::RETURNED_TO_OFFICER,
            Application::REGISTRAR_FIX_REQUEST,
            Application::REGISTRAR_REJECTED,
            Application::PAID_CONFIRMED,
            Application::ACCOUNTS_REVIEW,
        ];

        // Keep a simple "Awaiting Action" queue for the dashboard overview
        if ($request->filled('status') && in_array($request->status, $registrarVisibleStatuses, true)) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', [
                Application::FORWARDED_TO_REGISTRAR,
                Application::REGISTRAR_REVIEW,
                Application::VERIFIED_BY_OFFICER,
                Application::OFFICER_APPROVED,
                Application::PAID_CONFIRMED,
            ]);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                    ->orWhereHas('applicant', function ($aq) use ($s) {
                        $aq->where('name', 'like', "%$s%");
                    });
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applications = $query->latest()->paginate(20)->withQueryString();

        // Activity feed - recent registrar-related actions.
        // IMPORTANT: action names here must match the strings written by
        // ApplicationWorkflowService / RegistrarController / AccountsPaymentsController
        // exactly. Earlier versions of this list used singular forms
        // (e.g. "registrar_approve", "officer_approve") that are never logged,
        // which caused the feed to render empty.
        $activity = ActivityLog::query()
            ->whereIn('action', [
                // Submission & officer review
                'application_submitted',
                'officer_approved',
                // Registrar review
                'registrar_auto_claim_for_approval',
                'registrar_approved',
                'registrar_approved_paynow_autopaid',
                'registrar_raise_fix_request',
                'registrar_reject',
                'registrar_return_to_officer',
                'registrar_return_to_accounts',
                'application_assigned',
                'application_reassigned',
                'registrar_reassign_category',
                // Accounts / payments
                'accounts_confirm_paid',
                'payment_completed',
                'annual_levy_submitted',
                'annual_levy_paid',
                // Renewals
                'renewal_payment_verified',
                // Production / issuance
                'accreditation_card_printed',
                'registration_certificate_printed',
                'registration_license_printed',
                'application_issued',
                // Documents and controlled edits
                'important_document_uploaded',
                'authorized_record_modified',
            ])
            ->with(['user', 'entity'])
            ->latest()
            ->limit(15)
            ->get();

        $analyticsTotals = [
            'public_users' => User::where('account_type', 'public')->count(),
            'staff_users' => User::where('account_type', '!=', 'public')->count(),
            'accreditation' => Application::where('application_type', 'accreditation')->count(),
            'registration' => Application::where('application_type', 'registration')->count(),
        ];

        $thirtyDaysAgo = now()->subDays(30)->startOfDay();

        $appTrends = Application::selectRaw("date(created_at) as d, application_type, count(*) as cnt")
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupByRaw('date(created_at), application_type')
            ->get()
            ->groupBy('application_type');

        $userTrends = User::selectRaw("date(created_at) as d, count(*) as cnt")
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

        $statusGroups = Application::selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->orderByDesc('cnt')
            ->limit(8)
            ->pluck('cnt', 'status');

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

        $detailsUrlTemplate = route('staff.applications.details', ['application' => '__ID__']);
        $commentUrlTemplate = url('/staff/applications/__ID__/comment');

        return view('staff.registrar.dashboard', compact(
            'applications',
            'kpis',
            'activity',
            'year',
            'isCurrentYear',
            'analyticsTotals',
            'analyticsChartData',
            'detailsUrlTemplate',
            'commentUrlTemplate'
        ));
    }

    /**
     * 2) Incoming Queue: “Confirmed by Accreditation Officer”
     * Driven by workflow: confirmed items that cleared accounts.
     */
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
            ->with(['applicant', 'assignedOfficer', 'accreditationRecord', 'registrationRecord'])
            ->whereIn('status', $processedStatuses);

        // Search (Ref, Applicant, Email, Phone, Company)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                    ->orWhereHas('applicant', function ($aq) use ($s) {
                        $aq->where('name', 'like', "%$s%")
                            ->orWhere('email', 'like', "%$s%")
                            ->orWhere('phone_number', 'like', "%$s%");
                    })
                    ->orWhere('form_data', 'like', "%$s%");
            });
        }

        // Core Filters
        if ($request->filled('type')) {
            $query->where('application_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        // Advanced Filters
        if ($request->filled('foreignOrLocal')) {
            $scope = $request->foreignOrLocal;
            if ($scope === 'local') {
                $query->where(fn($q) => $q->where('journalist_scope', 'local')->orWhereNull('journalist_scope'));
            } else {
                $query->where('journalist_scope', 'foreign');
            }
        }
        if ($request->filled('applicationNature')) {
            $query->where('request_type', $request->applicationNature);
        }
        if ($request->filled('collectionRegion')) {
            $query->where('collection_region', 'like', '%' . $request->collectionRegion . '%');
        }
        if ($request->filled('paymentStatus')) {
            $query->where('payment_status', $request->paymentStatus);
        }
        if ($request->filled('assignedOfficer')) {
            $query->where('assigned_officer_id', $request->assignedOfficer);
        }

        // Type-specific filters
        if ($request->filled('category')) {
            $query->where(function ($q) use ($request) {
                $q->where('accreditation_category_code', $request->category)
                    ->orWhere('media_house_category_code', $request->category);
            });
        }

        if ($request->type === 'registration') {
            if ($request->filled('registrationForm')) {
                $query->where('form_type', $request->registrationForm);
            }
            if ($request->filled('massMediaCategory')) {
                $query->where('media_house_category_code', $request->massMediaCategory);
            }
            if ($request->filled('levyYear')) {
                $query->where('levy_year', $request->levyYear);
            }
        }

        if ($request->type === 'accreditation') {
            if ($request->filled('accreditationCategory')) {
                $query->where('accreditation_category_code', $request->accreditationCategory);
            }
            if ($request->filled('expiryStatus')) {
                $now = now();
                $soon = now()->addDays(30);
                if ($request->expiryStatus === 'expired') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->where('expires_at', '<', $now));
                } elseif ($request->expiryStatus === 'expiring_soon') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->whereBetween('expires_at', [$now, $soon]));
                } elseif ($request->expiryStatus === 'active') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->where('expires_at', '>=', $now));
                }
            }
        }

        $applications = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Data for filters
        $officers = User::role('accreditation_officer')->get();

        $processedStatusLabels = [
            Application::PRODUCTION_QUEUE => 'In Production Queue',
            Application::CARD_GENERATED => 'Card Generated',
            Application::CERT_GENERATED => 'Certificate Generated',
            Application::PRODUCED_READY => 'Produced & Ready',
            Application::PRINTED => 'Printed',
            Application::ISSUED => 'Issued / Collected',
        ];

        return view('staff.shared.processed_applications', compact('applications', 'processedStatuses', 'processedStatusLabels', 'officers'));
    }

    /**
     * Applications that the registrar has already approved.
     * These are no longer in the incoming queue. They are tracked here so the
     * registrar can see WHERE each approved application currently sits in the
     * downstream pipeline (Accounts → Production → Printed → Issued).
     */
    public function approvedApplications(Request $request)
    {
        $approvedStatuses = [
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            Application::ACCOUNTS_REVIEW,
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::PAYMENT_VERIFIED,
            Application::PAID_CONFIRMED,
            Application::PRODUCTION_QUEUE,
            Application::PRODUCED_READY,
            Application::CARD_GENERATED,
            Application::CERT_GENERATED,
            Application::PRINTED,
            Application::ISSUED,
        ];

        $approvedStatusLabels = [
            Application::REGISTRAR_APPROVED => 'Registrar Approved',
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE => 'Approved · Awaiting Reg. Fee',
            Application::PENDING_ACCOUNTS_FROM_REGISTRAR => 'With Accounts (Pending)',
            Application::ACCOUNTS_REVIEW => 'With Accounts (Review)',
            Application::AWAITING_ACCOUNTS_VERIFICATION => 'Awaiting Payment Verification',
            Application::PAYMENT_VERIFIED => 'Payment Verified',
            Application::PAID_CONFIRMED => 'Paid · Confirmed',
            Application::PRODUCTION_QUEUE => 'In Production Queue',
            Application::PRODUCED_READY => 'Produced · Ready',
            Application::CARD_GENERATED => 'Card Generated',
            Application::CERT_GENERATED => 'Certificate Generated',
            Application::PRINTED => 'Printed',
            Application::ISSUED => 'Issued / Collected',
        ];

        // Trust the workflow status as the source of truth for "approved by
        // registrar". Requiring registrar_reviewed_at to be set was hiding
        // legitimately-approved applications whose timestamp was not back-
        // filled (e.g. apps approved before that column was added).
        $query = Application::query()
            ->with(['applicant', 'assignedOfficer', 'accreditationRecord', 'registrationRecord'])
            ->whereIn('status', $approvedStatuses);

        // Search (Ref, Applicant, Email, Phone, Company)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                    ->orWhereHas('applicant', function ($aq) use ($s) {
                        $aq->where('name', 'like', "%$s%")
                            ->orWhere('email', 'like', "%$s%")
                            ->orWhere('phone_number', 'like', "%$s%");
                    })
                    ->orWhere('form_data', 'like', "%$s%");
            });
        }

        // Core Filters
        if ($request->filled('status') && in_array($request->status, $approvedStatuses, true)) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('application_type', $request->type);
        }
        if ($request->filled('dateFrom')) {
            $query->whereDate('created_at', '>=', $request->dateFrom);
        }
        if ($request->filled('dateTo')) {
            $query->whereDate('created_at', '<=', $request->dateTo);
        }

        // Advanced Filters
        if ($request->filled('foreignOrLocal')) {
            $scope = $request->foreignOrLocal;
            if ($scope === 'local') {
                $query->where(fn($q) => $q->where('journalist_scope', 'local')->orWhereNull('journalist_scope'));
            } else {
                $query->where('journalist_scope', 'foreign');
            }
        }
        if ($request->filled('applicationNature')) {
            $query->where('request_type', $request->applicationNature);
        }
        if ($request->filled('collectionRegion')) {
            $query->where('collection_region', 'like', '%' . $request->collectionRegion . '%');
        }
        if ($request->filled('paymentStatus')) {
            $query->where('payment_status', $request->paymentStatus);
        }
        if ($request->filled('assignedOfficer')) {
            $query->where('assigned_officer_id', $request->assignedOfficer);
        }

        // Type-specific filters
        if ($request->filled('category')) {
            $query->where(function ($q) use ($request) {
                $q->where('accreditation_category_code', $request->category)
                    ->orWhere('media_house_category_code', $request->category);
            });
        }

        if ($request->type === 'registration') {
            if ($request->filled('registrationForm')) {
                $query->where('form_type', $request->registrationForm);
            }
            if ($request->filled('massMediaCategory')) {
                $query->where('media_house_category_code', $request->massMediaCategory);
            }
            if ($request->filled('levyYear')) {
                $query->where('levy_year', $request->levyYear);
            }
        }

        if ($request->type === 'accreditation') {
            if ($request->filled('accreditationCategory')) {
                $query->where('accreditation_category_code', $request->accreditationCategory);
            }
            if ($request->filled('expiryStatus')) {
                $now = now();
                $soon = now()->addDays(30);
                if ($request->expiryStatus === 'expired') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->where('expires_at', '<', $now));
                } elseif ($request->expiryStatus === 'expiring_soon') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->whereBetween('expires_at', [$now, $soon]));
                } elseif ($request->expiryStatus === 'active') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->where('expires_at', '>=', $now));
                }
            }
        }

        // Data for filters
        $officers = User::role('accreditation_officer')->get();


        // Order by whichever timestamp is most recent — fall back to
        // last_action_at and finally created_at so rows are never dropped
        // due to a NULL ordering column.
        $applications = $query
            ->orderByRaw('COALESCE(registrar_reviewed_at, last_action_at, created_at) DESC')
            ->paginate(20)
            ->withQueryString();

        $detailsUrlTemplate = route('staff.applications.details', ['application' => '__ID__']);

        return view(
            'staff.registrar.approved_applications',
            compact('applications', 'approvedStatuses', 'approvedStatusLabels', 'detailsUrlTemplate', 'officers')
        );
    }


    /**
     * Registrar approves for payment.
     */
    public function approveForPayment(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $application = ApplicationWorkflowService::registrarApprove($application, [
                'notes' => $data['decision_notes'] ?? null,
            ]);

            $this->safeSet($application, [
                'registrar_reviewed_at' => now(),
                'registrar_reviewed_by' => Auth::id(),
                'decision_notes' => $data['decision_notes'] ?? $application->decision_notes,
            ]);

            return back()->with('success', 'Application approved and forwarded to Accounts for payment verification.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }


    public function placeholder(string $title, string $hint = '')
    {
        return view('staff.registrar.placeholder', compact('title', 'hint'));
    }

    public function downloadAllDocuments(Application $application)
    {
        $application->load('documents');
        
        $zipFileName = $application->reference . '_documents.zip';
        $zipFilePath = storage_path('app/temp/' . $zipFileName);
        
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($application->documents as $doc) {
                if (\Storage::disk('public')->exists($doc->file_path)) {
                    $fileName = $doc->document_type . '_' . basename($doc->file_path);
                    $zip->addFile(\Storage::disk('public')->path($doc->file_path), $fileName);
                }
            }
            $zip->close();
        }

        if (file_exists($zipFilePath)) {
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Could not create zip file or no documents found.');
    }

    public function show(Application $application)
    {
        // Registrars are a senior oversight role — they must always be able to
        // open any application regardless of officer-level concurrency locks.
        // If a stale lock exists (e.g. officer navigated away without completing
        // an action), silently release it so the Registrar (and later parties)
        // are not blocked.
        if ($application->isLocked() && $application->locked_by !== auth()->id()) {
            $application->unlock();
        }

        // Claim the application for the Registrar (refreshes the lock timestamp).
        $application->claim(auth()->user());

        $application->load(['applicant', 'documents', 'messages', 'workflowLogs', 'payments', 'printLogs', 'documentVersions', 'lockedBy', 'paymentSubmissions', 'comments.user']);

        $auditTrail = ActivityLog::where('entity_type', get_class($application))
            ->where('entity_id', $application->id)
            ->with('user')
            ->latest()
            ->paginate(25, ['*'], 'timeline_page')
            ->withQueryString();

        $previousApplications = Application::where('applicant_user_id', $application->applicant_user_id)
            ->where('id', '!=', $application->id)
            ->latest()
            ->get();

        return view('staff.registrar.show', compact('application', 'auditTrail', 'previousApplications'));
    }


    /**
     * Registrar reassigns category.
     */
    public function reassignCategory(Request $request, Application $application)
    {
        $data = $request->validate([
            'category_code' => 'required|string',
            'reason' => 'required|string|max:1000',
        ]);

        $oldCategory = $application->accreditation_category_code ?? $application->media_house_category_code;

        if ($application->application_type === 'registration') {
            $application->media_house_category_code = $data['category_code'];
        } else {
            $application->accreditation_category_code = $data['category_code'];
        }

        $application->save();

        ActivityLogger::log('registrar_reassign_category', $application, $application->status, $application->status, [
            'old_category' => $oldCategory,
            'new_category' => $data['category_code'],
            'reason' => $data['reason'],
        ]);

        return back()->with('success', 'Category reassigned successfully.');
    }

    /**
     * Registrar approves -> moves to Accounts for payment
     */
    public function approve(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:5000'],
            'category_code' => ['required', 'string', 'max:10'],
        ]);

        try {
            // Apply category first as it might be needed for fee calculation
            if ($application->application_type === 'registration') {
                $application->media_house_category_code = $data['category_code'];
            } else {
                $application->accreditation_category_code = $data['category_code'];
            }
            $application->save();

            $application = ApplicationWorkflowService::registrarApprove($application, [
                'notes' => $data['decision_notes'] ?? null,
                'category_code' => $data['category_code'],
            ]);

            return back()->with('success', 'Application approved and forwarded to Accounts for payment verification.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }


    public function markReviewed(Request $request, Application $application)
    {
        $application->registrar_reviewed_at = now();
        $application->registrar_reviewed_by = auth()->id();
        $application->save();

        if (class_exists(\App\Support\AuditTrail::class)) {
            \App\Support\AuditTrail::log('registrar_reviewed', $application, [
                'application_id' => $application->id,
                'reviewed_by' => auth()->user()?->name,
            ]);
        }

        return back()->with('success', 'Application marked as reviewed.');
    }

    public function batchMarkReviewed(Request $request)
    {
        $data = $request->validate([
            'application_ids' => ['required', 'array'],
            'application_ids.*' => ['integer'],
        ]);

        $registrarStatuses = [
            Application::REGISTRAR_REVIEW,
            Application::FORWARDED_TO_REGISTRAR,
            Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
        ];

        $count = Application::whereIn('id', $data['application_ids'])
            ->whereNull('registrar_reviewed_at')
            ->whereIn('status', $registrarStatuses)
            ->update([
                'registrar_reviewed_at' => now(),
                'registrar_reviewed_by' => auth()->id(),
            ]);

        return back()->with('success', "{$count} application(s) marked as reviewed.");
    }

    public function reject(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['required', 'string', 'max:5000'],
        ]);

        $from = $application->status;

        $this->safeSet($application, [
            'rejection_reason' => $data['decision_notes'],
            'decision_notes' => $data['decision_notes'],
        ]);

        ApplicationWorkflow::transition($application, Application::REGISTRAR_REJECTED, 'registrar_reject', [
            'reason' => $data['decision_notes'],
        ]);

        $application->refresh();

        $this->audit('registrar_reject', $application, $from, $application->status, [
            'reason' => $data['decision_notes'],
        ]);

        return back()->with('success', 'Returned for correction.');
    }

    public function returnToOfficer(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['required', 'string', 'max:5000'],
        ]);

        $from = $application->status;

        ApplicationWorkflow::transition($application, Application::RETURNED_TO_OFFICER, 'registrar_return_to_officer', [
            'notes' => $data['decision_notes'],
        ]);

        $this->safeSet($application, ['decision_notes' => $data['decision_notes']]);

        // Keep the officer's "Returned from Registrar" queue in sync with
        // direct registrar returns as well as the structured fix-request flow.
        // The queue is driven by FixRequest records, so create one here when
        // the registrar sends an application back without using sendFixRequest.
        if (class_exists(\App\Models\FixRequest::class)) {
            \App\Models\FixRequest::firstOrCreate(
                [
                    'application_id' => $application->id,
                    'requested_by' => Auth::id(),
                    'status' => 'pending',
                ],
                [
                    'request_type' => 'data_correction',
                    'description' => $data['decision_notes'],
                ]
            );
        }

        // Hand-off back to Accreditation Officer pool: release any concurrency
        // lock so the officer dashboard can show it immediately.
        $application->forceFill([
            'locked_at' => null,
            'locked_by' => null,
        ])->save();

        $application->refresh();

        $this->audit('registrar_return_to_officer', $application, $from, $application->status, [
            'notes' => $data['decision_notes'],
        ]);

        return back()->with('success', 'Returned to Accreditation Officer.');
    }

    /** Alias for backward compatibility / cached routes */
    public function returnToAccounts(Request $request, Application $application)
    {
        return $this->returnToOfficer($request, $application);
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

    public function remindersIndex(Request $request)
    {
        $reminders = \App\Models\Reminder::query()
            ->with('creator')
            ->latest()
            ->paginate(20);

        return view('staff.registrar.reminders', compact('reminders'));
    }

    public function storeReminder(Request $request)
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:media_practitioner,media_house,bulk'],
            'target_id' => ['required', 'integer', 'min:1'],
            'reminder_type' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        \App\Models\Reminder::create([
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'reminder_type' => $data['reminder_type'],
            'title' => $data['title'] ?? ucfirst(str_replace('_', ' ', $data['reminder_type'])),
            'message' => $data['message'],
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log('reminder_created', null, null, null, [
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'reminder_type' => $data['reminder_type'],
            'actor_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Reminder created successfully.');
    }

    /* helpers */
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

    public function reports(Request $request)
    {
        $query = Application::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $approvals = (clone $query)->where('status', Application::REGISTRAR_APPROVED)->count();
        $reassignments = ActivityLog::where('action', 'registrar_reassign_category')
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->count();

        $totalPrints = PrintLog::when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->count();

        $edits = DocumentVersion::when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->count();

        $recentApprovals = (clone $query)
            ->whereIn('status', [
                Application::REGISTRAR_APPROVED,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::PAYMENT_VERIFIED,
                Application::PAID_CONFIRMED,
                Application::PRODUCTION_QUEUE,
                Application::PRODUCED_READY,
                Application::CARD_GENERATED,
                Application::CERT_GENERATED,
                Application::PRINTED,
                Application::ISSUED,
            ])
            ->with('applicant')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $exceptions = ActivityLog::whereIn('action', ['registrar_reassign_category', 'approval_with_official_letter', 'registrar_rejected'])
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest()
            ->limit(10)
            ->get();

        return view('staff.registrar.reports', compact('approvals', 'reassignments', 'totalPrints', 'edits', 'recentApprovals', 'exceptions'));
    }

    public function reportsPdf(Request $request)
    {
        $query = Application::query();
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $approvals = (clone $query)->where('status', Application::REGISTRAR_APPROVED)->count();
        $reassignments = ActivityLog::where('action', 'registrar_reassign_category')
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->count();
        $totalPrints = PrintLog::when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->count();
        $edits = DocumentVersion::when($request->filled('date_from'), fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->count();

        $recentApprovals = (clone $query)->with('applicant')->latest('updated_at')->limit(20)->get();

        $pdf = Pdf::loadView('staff.registrar.reports_pdf', compact('approvals', 'reassignments', 'totalPrints', 'edits', 'recentApprovals'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('registrar_operational_report_' . now()->format('Ymd_His') . '.pdf');
    }

    public function auditTrailSearch(Request $request)
    {
        // Do not eager-load the polymorphic entity here: older audit rows may
        // contain legacy entity types that no longer map to a PHP model.
        $query = ActivityLog::query()->with('user');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('action', 'like', "%$s%")
                    ->orWhere('meta', 'like', "%$s%")
                    ->orWhereHas('user', function ($uq) use ($s) {
                        $uq->where('name', 'like', "%$s%");
                    });
            });
        }

        $logs = $query->latest()->paginate(50);
        return view('staff.registrar.audit_trail', compact('logs'));
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * View Notices & Events (Read-only)
     */
    public function noticesEvents(Request $request)
    {
        $notices = Notice::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->paginate(20);

        $events = Event::query()
            ->where('is_published', true)
            ->orderBy('starts_at')
            ->paginate(20);

        return view('staff.registrar.notices_events', compact('notices', 'events'));
    }

    /**
     * View News / Press Statements (Read-only)
     */
    public function news(Request $request)
    {
        $news = News::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->paginate(20);

        return view('staff.registrar.news', compact('news'));
    }

    /**
     * Send fix request to Accreditation Officer
     */
    public function sendFixRequest(Request $request, Application $application)
    {
        $data = $request->validate([
            'request_type' => ['required', 'in:data_correction,category_change,document_issue'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        // Create fix request
        $fixRequest = \App\Models\FixRequest::create([
            'application_id' => $application->id,
            'requested_by' => Auth::id(),
            'request_type' => $data['request_type'],
            'description' => $data['description'],
            'status' => 'pending',
        ]);

        // Use new workflow service - enforces strict transitions
        try {
            $application = ApplicationWorkflowService::registrarRaiseFixRequest($application, $data['description'], [
                'fix_request_id' => $fixRequest->id,
                'request_type' => $data['request_type'],
            ]);

            return back()->with('success', 'Application returned to Accreditation Officer.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }

    /**
     * View fix requests sent by this registrar
     */
    public function fixRequests(Request $request)
    {
        $query = \App\Models\FixRequest::query()
            ->with(['application.applicant', 'resolver'])
            ->where('requested_by', Auth::id());

        if ($request->filled('status')) {
            $filter = $request->status;

            if ($filter === 'awaiting_officer') {
                $query->where('status', '!=', 'resolved')
                    ->whereHas('application', function ($q) {
                        $q->where('status', Application::REGISTRAR_FIX_REQUEST);
                    });
            } elseif ($filter === 'officer_fixed') {
                $query->where('status', '!=', 'resolved')
                    ->whereHas('application', function ($q) {
                        $q->where('status', '!=', Application::REGISTRAR_FIX_REQUEST);
                    });
            } elseif ($filter === 'resolved') {
                $query->where('status', 'resolved');
            }
        }

        $fixRequests = $query->latest()->paginate(20)->withQueryString();

        return view('staff.registrar.fix_requests', compact('fixRequests'));
    }

    /**
     * Approve special case (forwarded without approval)
     */
    public function approveSpecialCase(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // Validate status
        if ($application->status !== Application::FORWARDED_TO_REGISTRAR_NO_APPROVAL) {
            return back()->with('error', 'This application is not a special case awaiting approval.');
        }

        // Save decision notes
        if (!empty($data['decision_notes'])) {
            $application->decision_notes = $data['decision_notes'];
            $application->save();
        }

        // Use new workflow service - enforces strict transitions
        try {
            $application = ApplicationWorkflowService::registrarPushToAccounts($application, [
                'notes' => $data['decision_notes'] ?? null,
                'forward_reason' => $application->forward_no_approval_reason,
            ]);

            $this->safeSet($application, [
                'registrar_reviewed_at' => now(),
                'registrar_reviewed_by' => Auth::id(),
            ]);

            return back()->with('success', 'Special case approved and sent to Accounts for payment verification.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }

    /**
     * Approve media house application with official letter upload
     * (Two-stage payment: after this, applicant pays registration fee)
     */
    public function approveWithOfficialLetter(Request $request, Application $application)
    {
        $data = $request->validate([
            'official_letter' => ['required', 'file', 'extensions:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
            'decision_notes' => ['nullable', 'string', 'max:5000'],
            'category_code' => ['required', 'string', 'max:10'],
        ]);

        // Validate: Media house only
        if ($application->application_type !== 'registration') {
            return back()->with('error', 'Official letter is only required for media house registrations.');
        }

        try {
            \DB::transaction(function () use ($application, $data) {
                // Apply category first
                $application->media_house_category_code = $data['category_code'];

                // Upload official letter
                $file = $data['official_letter'];
                $path = $this->storePublicFile($file, 'official_letters', FileType::safeHashName($file));
                $hash = hash_file('sha256', \Storage::disk('public')->path($path));

                $officialLetter = \App\Models\OfficialLetter::create([
                    'application_id' => $application->id,
                    'uploaded_by' => \Auth::id(),
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_hash' => $hash,
                    'uploaded_at' => now(),
                ]);

                // Link to application
                $application->official_letter_id = $officialLetter->id;
                $application->save();

                // Use workflow service
                ApplicationWorkflowService::registrarApprove($application, [
                    'notes' => $data['decision_notes'] ?? null,
                    'category_code' => $data['category_code'],
                ]);
            });

            return back()->with('success', 'Application approved. Official letter uploaded. Applicant will be prompted to pay registration fee.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }

    /**
     * Payment Oversight Dashboard (Read-Only)
     */
    public function paymentOversight(Request $request)
    {
        $isLegacySource = false;
        // Prefer the complete legacy payment ledger when it contains settled
        // payments. Some older applications were recorded there before the
        // payment_submissions table was introduced; using only the newer table
        // made the Registrar see one verified payment when two were actually
        // paid and present in the ledger.
        $hasSubmissionData = \App\Models\PaymentSubmission::query()->exists()
            && !\App\Models\Payment::query()->whereIn('status', ['paid', 'verified', 'confirmed'])->exists();

        if ($hasSubmissionData) {
            // Query payment submissions with filters
            $query = \App\Models\PaymentSubmission::query()
                ->with(['application.applicant', 'verifier'])
                ->latest('submitted_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('method')) {
                $query->where('method', $request->method);
            }
            if ($request->filled('payment_stage')) {
                $query->where('payment_stage', $request->payment_stage);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('submitted_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('submitted_at', '<=', $request->date_to);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('application', function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%");
                });
            }

            $payments = $query->paginate(20)->withQueryString();

            $kpis = [
                'pending' => \App\Models\PaymentSubmission::where('status', 'submitted')->count(),
                'verified' => \App\Models\PaymentSubmission::where('status', 'verified')->count(),
                'rejected' => \App\Models\PaymentSubmission::where('status', 'rejected')->count(),
                'paynow' => \App\Models\PaymentSubmission::where('method', 'PAYNOW')->count(),
                'proof' => \App\Models\PaymentSubmission::where('method', 'PROOF_UPLOAD')->count(),
                'waiver' => \App\Models\PaymentSubmission::where('method', 'WAIVER')->count(),
                'app_fee' => \App\Models\PaymentSubmission::where('payment_stage', 'application_fee')->count(),
                'reg_fee' => \App\Models\PaymentSubmission::where('payment_stage', 'registration_fee')->count(),
            ];
        } else {
            // Fallback for older environments where payment activity is stored in `payments`.
            $isLegacySource = true;
            $query = \App\Models\Payment::query()
                ->with(['application.applicant', 'payer'])
                ->latest('created_at');

            if ($request->filled('status')) {
                $status = $request->status;
                if ($status === 'submitted') {
                    $query->whereIn('status', ['pending', 'submitted', 'initiated']);
                } elseif ($status === 'verified') {
                    $query->whereIn('status', ['paid', 'verified', 'confirmed']);
                } elseif ($status === 'rejected') {
                    $query->whereIn('status', ['rejected', 'failed']);
                }
            }
            if ($request->filled('method')) {
                $method = strtoupper((string) $request->method);
                if ($method === 'PAYNOW') {
                    $query->whereRaw('LOWER(method) LIKE ?', ['%paynow%']);
                } elseif ($method === 'PROOF_UPLOAD') {
                    $query->where(function ($q) {
                        $q->whereNotNull('proof_file_path')
                            ->orWhereRaw('LOWER(method) LIKE ?', ['%proof%'])
                            ->orWhereRaw('LOWER(method) LIKE ?', ['%bank%']);
                    });
                } elseif ($method === 'WAIVER') {
                    $query->whereRaw('LOWER(method) LIKE ?', ['%waiver%']);
                }
            }
            if ($request->filled('payment_stage')) {
                $stage = (string) $request->payment_stage;
                if ($stage === 'application_fee') {
                    $query->where('service_type', 'accreditation');
                } elseif ($stage === 'registration_fee') {
                    $query->where('service_type', 'registration');
                }
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhereHas('application', fn($aq) => $aq->where('reference', 'like', "%{$search}%"));
                });
            }

            $payments = $query->paginate(20)->withQueryString();

            $kpis = [
                'pending' => \App\Models\Payment::whereIn('status', ['pending', 'submitted', 'initiated'])->count(),
                'verified' => \App\Models\Payment::whereIn('status', ['paid', 'verified', 'confirmed'])->count(),
                'rejected' => \App\Models\Payment::whereIn('status', ['rejected', 'failed'])->count(),
                'paynow' => \App\Models\Payment::whereRaw('LOWER(method) LIKE ?', ['%paynow%'])->count(),
                'proof' => \App\Models\Payment::whereNotNull('proof_file_path')->count(),
                'waiver' => \App\Models\Payment::whereRaw('LOWER(method) LIKE ?', ['%waiver%'])->count(),
                'app_fee' => \App\Models\Payment::where('service_type', 'accreditation')->count(),
                'reg_fee' => \App\Models\Payment::where('service_type', 'registration')->count(),
            ];
        }

        // Log oversight access - using Auth user as entity since this is a view action
        ActivityLogger::log('registrar_view_payment_oversight', Auth::user(), null, null, [
            'actor_role' => session('active_staff_role'),
            'filters' => $request->only(['status', 'method', 'payment_stage', 'date_from', 'date_to', 'search']),
        ]);

        return view('staff.registrar.payment_oversight', compact('payments', 'kpis', 'isLegacySource'));
    }

    /**
     * Payment Detail View (Read-Only)
     */
    public function paymentDetail(\App\Models\PaymentSubmission $paymentSubmission)
    {
        $paymentSubmission->load(['application.applicant', 'application.workflowLogs', 'verifier']);

        // Get all payment submissions for this application (for context)
        $allPayments = \App\Models\PaymentSubmission::where('application_id', $paymentSubmission->application_id)
            ->with('verifier')
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Log detail view access
        ActivityLogger::log('registrar_view_payment_detail', $paymentSubmission->application, null, null, [
            'actor_role' => session('active_staff_role'),
            'payment_submission_id' => $paymentSubmission->id,
            'payment_stage' => $paymentSubmission->payment_stage,
        ]);

        return view('staff.registrar.payment_detail', compact('paymentSubmission', 'allPayments'));
    }

    public function applicationsList(Request $request, $bucket = 'under-review')
    {
        $registrarVisibleStatuses = [
            Application::FORWARDED_TO_REGISTRAR,
            Application::REGISTRAR_REVIEW,
            Application::VERIFIED_BY_OFFICER,
            Application::OFFICER_APPROVED,
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            Application::RETURNED_TO_OFFICER,
            Application::REGISTRAR_FIX_REQUEST,
            Application::REGISTRAR_REJECTED,
            Application::PAID_CONFIRMED,
            Application::ACCOUNTS_REVIEW,
            Application::ISSUED,
        ];

        // Bucket mapping to statuses
        $bucketStatuses = [
            'all' => $registrarVisibleStatuses,
            'under-review' => [
                Application::FORWARDED_TO_REGISTRAR,
                Application::REGISTRAR_REVIEW,
                Application::VERIFIED_BY_OFFICER,
                Application::OFFICER_APPROVED,
                Application::PAID_CONFIRMED,
            ],
            'approved' => [
                Application::REGISTRAR_APPROVED,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                Application::ISSUED,
            ],
            'returned' => [
                Application::RETURNED_TO_OFFICER,
                Application::REGISTRAR_FIX_REQUEST,
                Application::REGISTRAR_REJECTED,
                Application::RETURNED_TO_ACCOUNTS,
            ],
        ];

        $tabStatuses = $bucketStatuses[$bucket] ?? $bucketStatuses['under-review'];

        $query = Application::query()
            ->with(['applicant', 'assignedOfficer', 'payments', 'accreditationRecord', 'registrationRecord'])
            ->latest('last_action_at');

        // 1. Tab-based status filtering (Base filter)
        $query->whereIn('status', $tabStatuses);

        // 2. Global Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                    ->orWhereHas('applicant', function ($aq) use ($s) {
                        $aq->where('name', 'like', "%$s%")
                            ->orWhere('email', 'like', "%$s%")
                            ->orWhere('phone_number', 'like', "%$s%");
                    })
                    ->orWhere('form_data', 'like', "%$s%"); // To match company name etc stored in JSON
            });
        }

        // 3. Core Filters
        if ($request->filled('applicationType')) {
            $query->where('application_type', $request->applicationType);
        }

        if ($request->filled('status')) {
            // Ensure status filter is within the tab's visible statuses
            if (in_array($request->status, $tabStatuses)) {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('dateFrom')) {
            $query->whereDate('submitted_at', '>=', $request->dateFrom);
        }

        if ($request->filled('dateTo')) {
            $query->whereDate('submitted_at', '<=', $request->dateTo);
        }

        // 4. Advanced Filters
        if ($request->filled('foreignOrLocal')) {
            $val = strtolower($request->foreignOrLocal);
            if ($val === 'local') {
                $query->where(function ($q) {
                    $q->where('journalist_scope', 'local')->orWhereNull('journalist_scope');
                });
            } else {
                $query->where('journalist_scope', 'foreign');
            }
        }

        if ($request->filled('applicationNature')) {
            $query->where('request_type', $request->applicationNature);
        }

        if ($request->filled('collectionRegion')) {
            $query->where('collection_region', $request->collectionRegion);
        }

        if ($request->filled('paymentStatus')) {
            $query->where('payment_status', $request->paymentStatus);
        }

        if ($request->filled('assignedOfficer')) {
            $query->where('assigned_officer_id', $request->assignedOfficer);
        }

        if ($request->filled('category')) {
            $cat = $request->category;
            $query->where(function ($q) use ($cat) {
                $q->where('accreditation_category_code', $cat)
                    ->orWhere('media_house_category_code', $cat);
            });
        }

        // 5. Registration Specific
        if ($request->applicationType === 'registration') {
            if ($request->filled('registrationForm')) {
                $query->where('form_code', $request->registrationForm);
            }
            // Add other registration-specific filters if they map to columns
            if ($request->filled('massMediaCategory')) {
                $query->where('media_house_category_code', $request->massMediaCategory);
            }
        }

        // 6. Accreditation Specific
        if ($request->applicationType === 'accreditation') {
            if ($request->filled('accreditationCategory')) {
                $query->where('accreditation_category_code', $request->accreditationCategory);
            }
            if ($request->filled('expiryStatus')) {
                $now = now();
                $soon = now()->addDays(30);
                if ($request->expiryStatus === 'expired') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->where('expires_at', '<', $now));
                } elseif ($request->expiryStatus === 'expiring_soon') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->whereBetween('expires_at', [$now, $soon]));
                } elseif ($request->expiryStatus === 'active') {
                    $query->whereHas('accreditationRecord', fn($q) => $q->where('expires_at', '>=', $now));
                }
            }
        }

        $applications = $query->paginate(20)->withQueryString();

        // Data for filters
        $officers = User::role('accreditation_officer')->get();

        $statuses = [];
        foreach ($tabStatuses as $st) {
            $statuses[$st] = Application::statusLabel($st);
        }

        $detailsUrlTemplate = route('staff.applications.details', ['application' => '__ID__']);

        return view('staff.registrar.applications_list', compact(
            'applications',
            'bucket',
            'detailsUrlTemplate',
            'officers',
            'statuses'
        ));
    }

    public function documentsUploaded(Request $request)
    {
        $apps = collect();

        if (class_exists(\App\Models\ApplicationDocument::class) && Schema::hasTable('application_documents')) {
            $appsQ = Application::query()
                ->with(['applicant', 'documents'])
                ->latest();

            // exclude drafts
            $appsQ->where(function ($qq) {
                $qq->whereNull('status')->orWhere('status', '!=', Application::DRAFT);
            });

            if ($t = $request->query('application_type')) {
                if (in_array($t, ['accreditation', 'registration'], true)) {
                    $appsQ->where('application_type', $t);
                }
            }

            if ($from = $request->query('date_from')) {
                $appsQ->whereDate('created_at', '>=', $from);
            }
            if ($to = $request->query('date_to')) {
                $appsQ->whereDate('created_at', '<=', $to);
            }

            $apps = $appsQ->paginate(15)->withQueryString();
        }

        return view('staff.registrar.uploaded_documents', [
            'title' => 'Uploaded Documents',
            'applications' => $apps,
        ]);
    }

    public function downloadDocumentsZip(Application $application)
    {
        $documents = $application->documents;
        if ($documents->isEmpty()) {
            return back()->with('error', 'No documents found for this application.');
        }

        $zip = new \ZipArchive();
        $zipFileName = 'documents_' . $application->reference . '_' . time() . '.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        $addedFilesCount = 0;
        foreach ($documents as $doc) {
            $downloadName = $doc->original_name ?: basename($doc->file_path ?? 'document');
            $downloadName = str_replace(['/', '\\', '?', '%', '*', ':', '|', '"', '<', '>'], '_', $downloadName);
            
            $prefix = $doc->doc_type . '_' . $doc->id . '_';
            $zipEntryName = $prefix . $downloadName;

            $relPath = ltrim((string) $doc->file_path, '/');
            $fileData = null;
            $foundOnDisk = false;

            if ($relPath !== '') {
                foreach (['public', 'local'] as $disk) {
                    try {
                        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($relPath)) {
                            $fileData = \Illuminate\Support\Facades\Storage::disk($disk)->get($relPath);
                            $foundOnDisk = true;
                            break;
                        }
                    } catch (\Throwable $e) {}
                }

                if (!$foundOnDisk) {
                    $candidatePaths = [
                        storage_path('app/public/' . $relPath),
                        storage_path('app/' . $relPath),
                        public_path('storage/' . $relPath),
                        public_path($relPath),
                        base_path($relPath),
                    ];
                    foreach ($candidatePaths as $abs) {
                        if (is_file($abs) && is_readable($abs)) {
                            $fileData = file_get_contents($abs);
                            $foundOnDisk = true;
                            break;
                        }
                    }
                }
            }

            if (!$foundOnDisk && \Illuminate\Support\Facades\Schema::hasColumn($doc->getTable(), 'file_data')) {
                $dbData = $doc->getAttribute('file_data');
                if (is_resource($dbData)) {
                    $dbData = stream_get_contents($dbData);
                }
                if (is_string($dbData) && $dbData !== '') {
                    $fileData = $dbData;
                }
            }

            if ($fileData !== null && $fileData !== '') {
                $zip->addFromString($zipEntryName, $fileData);
                $addedFilesCount++;
            }
        }

        $zip->close();

        if ($addedFilesCount === 0) {
            if (file_exists($zipPath)) {
                @unlink($zipPath);
            }
            return back()->with('error', 'None of the uploaded document files could be found on the server.');
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}

