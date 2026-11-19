<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Director\DashboardMetricsService;
use App\Services\Director\AccreditationAnalyticsService;
use App\Services\Director\FinancialAnalyticsService;
use App\Services\Director\ComplianceMonitoringService;
use App\Services\Director\MediaHouseOversightService;
use App\Services\Director\StaffPerformanceService;
use App\Services\Director\RiskIndicatorService;
use App\Services\Director\ReportGenerationService;
use App\Services\ApplicationWorkflowService;
use App\Services\ApplicationWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Director Media Development and Governance Dashboard Controller
 * 
 * Provides executive oversight and analytics for the Director role.
 * 
 * SECURITY: This controller enforces STRICT VIEW-ONLY access.
 * Directors can:
 * - View all dashboard data and analytics
 * - Generate and download reports (PDF, Excel, CSV)
 * 
 * Directors CANNOT:
 * - Edit application data
 * - Approve or reject applications
 * - Assign or reassign applications
 * - Generate certificates or cards
 * - Print or reprint documents
 * - Modify payment records
 * - Grant waivers
 * - Perform any operational actions
 * 
 * All methods in this controller are read-only except for report generation
 * which only creates downloadable files without modifying system data.
 * 
 * @see \App\Http\Middleware\DirectorViewOnly
 */
class DirectorController extends Controller
{
    public function __construct(
        private DashboardMetricsService $metricsService,
        private AccreditationAnalyticsService $accreditationService,
        private FinancialAnalyticsService $financialService,
        private ComplianceMonitoringService $complianceService,
        private MediaHouseOversightService $mediaHouseService,
        private StaffPerformanceService $staffService,
        private RiskIndicatorService $riskService,
        private ReportGenerationService $reportService
    ) {
        // Ensure view-only access - directors and registrars can only view data and generate reports
        $this->middleware(['auth', 'role:director|super_admin|registrar', 'director.view_only']);

    }

    /**
     * Verify that the current user has director role and view-only access
     * This is a defense-in-depth check in addition to middleware
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function ensureViewOnlyAccess(): void
    {
        $user = auth()->user();
        if (!auth()->check() || !$user->hasAnyRole(['director', 'super_admin', 'registrar'])) {
            abort(403, 'This action requires director or registrar role.');
        }
    }


    /**
     * Verify that a request is for a read-only operation
     * Directors cannot perform any data modification operations
     *
     * @param Request $request
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function ensureReadOnlyOperation(Request $request): void
    {
        // Report generation is allowed (POST) but no other modifications
        $allowedPostRoutes = [
            'staff.director.generate.monthly-accreditation',
            'staff.director.generate.revenue-financial',
            'staff.director.generate.compliance-audit',
            'staff.director.generate.mediahouse-status',
            'staff.director.generate.operational-performance',
        ];

        if (!$request->isMethod('GET') && !in_array($request->route()->getName(), $allowedPostRoutes)) {
            abort(403, 'Directors have view-only access and cannot perform operational actions.');
        }
    }

    /**
     * Director MDG Strategic Oversight Dashboard
     * Requirements: 1.1-1.10
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function dashboard(Request $request)
    {
        // Executive users share one consolidated overview dashboard.
        return redirect()->route('executive.dashboard');

        $activeTab = $request->get('tab', 'perf');
        $year = $request->get('year', now()->year);
        $availableYears = Application::yearOptions();
        rsort($availableYears);

        // KPIs and risk indicators for main dashboard
        $kpis = $this->metricsService->getExecutiveKPIs($year);
        $riskIndicators = $this->riskService->getAllRiskIndicators(); // Risk indicators are usually immediate
        $highRiskActivity = $this->complianceService->getHighRiskActions(5);

        // Handle AJAX requests for real-time updates
        if ($request->ajax() || $request->get('ajax') == '1') {
            $section = $request->get('section');
            
            if ($section === 'highRiskActivity') {
                return response()->json([
                    'highRiskActivity' => $highRiskActivity
                ]);
            }
            
            // Return KPIs and risk indicators for polling
            return response()->json([
                'kpis' => $kpis,
                'riskIndicators' => $riskIndicators,
                'highRiskActivity' => $highRiskActivity
            ]);
        }

        // Performance partial data (performance.blade.php)
        $ratios = $this->accreditationService->getApprovalRatioByApplicationType();
        $ratio = [
            'journalist' => $ratios['accreditation'] ?? 0,
            'mass_media' => $ratios['registration'] ?? 0,
        ];
        $categories = $this->accreditationService->getCategoryDistribution();
        
        // Get monthly trends for dashboard (always current year's last 12 months)
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }
        $trendsData = $this->accreditationService->getMonthlyTrends(12)->keyBy('month');
        
        // Fill in missing months with zeros
        $monthlyTrends = $months->map(function($month) use ($trendsData) {
            $data = $trendsData->get($month);
            return (object)[
                'month' => $month,
                'submitted' => $data->total_submitted ?? 0,
                'approved' => $data->total_approved ?? 0,
                'rejected' => $data->total_rejected ?? 0,
            ];
        });

        // Financial partial data (financial.blade.php)
        $aging = $this->financialService->getOutstandingPaymentsAging();
        $waivers = $this->financialService->getWaiverStatistics();
        $revenueTrendData = $this->financialService->getMonthlyRevenueTrend();
        $revenueTrend = $revenueTrendData['current_year'] ?? collect([]);
        $breakdown = [
            'service' => $this->financialService->getRevenueByServiceType()
        ];
        
        // Debug logging
        \Log::info('Director Media Development and Governance Dashboard Financial Data', [
            'revenue_trend_count' => $revenueTrend->count(),
            'service_breakdown_count' => $breakdown['service']->count(),
            'aging' => $aging,
            'waivers_count' => $waivers['count'],
        ]);

        // Compliance partial data (compliance.blade.php)
        $categoryReassignmentsStat = $this->complianceService->getCategoryReassignments();
        $reopenedApplicationsStat = $this->complianceService->getReopenedApplications();
        $manualOverridesStat = $this->complianceService->getManualOverrides();
        $certificateEditsStat = $this->complianceService->getCertificateEdits();
        $printStatistics = $this->complianceService->getPrintStatistics();
        $excessiveReprints = $this->complianceService->getExcessiveReprints();

        $auditSnapshot = [
            'category_reassignments' => $categoryReassignmentsStat['total'] ?? 0,
            'manual_payment_overrides' => $manualOverridesStat['total'] ?? 0,
            'certificate_edits' => $certificateEditsStat['total'] ?? 0,
            'reopened_applications' => $reopenedApplicationsStat['total'] ?? 0,
        ];

        $reprints = [
            'total' => ($printStatistics['total_prints'] ?? 0) + ($printStatistics['total_reprints'] ?? 0),
            'reprints' => $printStatistics['total_reprints'] ?? 0,
            'top_staff' => $excessiveReprints['by_staff'] ?? collect([]),
        ];
        
        // Debug logging
        \Log::info('Director Media Development and Governance Dashboard Compliance Data', [
            'audit_snapshot' => $auditSnapshot,
            'print_stats' => $printStatistics,
            'top_staff_count' => $reprints['top_staff']->count(),
        ]);

        // Issuance partial data (issuance.blade.php)
        $issuance = [
            'total_issued' => Application::where('status', 'issued')
                ->whereBetween('issued_at', [now()->startOfMonth(), now()])
                ->count(),
            'unprinted_approvals' => Application::where('status', 'issued')
                ->where(function($q) {
                    $q->whereNull('print_count')->orWhere('print_count', 0);
                })
                ->count(),
            'prints_vs_reprints' => [
                'prints' => $printStatistics['total_prints'] ?? 0,
                'reprints' => $printStatistics['total_reprints'] ?? 0,
            ],
        ];

        // Media house partial data (media_oversight.blade.php)
        $mediaHouses = $this->mediaHouseService->getMediaHouseStatusCounts();
        $averageStaffPerHouse = $this->mediaHouseService->getAverageStaffPerHouse();
        $housesExceedingThresholds = $this->mediaHouseService->getHousesExceedingThresholds();
        $accreditationsNearingExpiry = $this->mediaHouseService->getAccreditationsNearingExpiry();
        
        // Regional distribution (placeholder - no regional data in current schema)
        $regional = collect([]);
        
        // Debug logging
        \Log::info('Director Media Development and Governance Dashboard Entity & Issuance Data', [
            'media_houses' => $mediaHouses,
            'avg_staff' => $averageStaffPerHouse,
            'issuance' => $issuance,
            'houses_exceeding' => $housesExceedingThresholds->count(),
        ]);
        $regional = collect([]);

        // Staff performance partial data (staff_performance.blade.php)
        $staffPerformance = $this->staffService->getApplicationsProcessedPerOfficer()->map(function($user) {
            $user->processed_applications_count = $user->processed_count;
            return $user;
        });
        
        $applicationsProcessed = $staffPerformance; // for backward compatibility if needed

        $approvalDistribution = $this->staffService->getApprovalDistributionPerOfficer();

        return view('staff.director.dashboard', compact(
            'kpis',
            'riskIndicators',
            'highRiskActivity',
            'activeTab',
            'year',
            'availableYears',
            // Performance partial
            'ratio',
            'categories',
            'monthlyTrends',
            // Financial partial
            'aging',
            'waivers',
            'revenueTrend',
            'breakdown',
            // Compliance partial
            'auditSnapshot',
            'reprints',
            'printStatistics',
            // Issuance partial
            'issuance',
            // Media house partial
            'mediaHouses',
            'regional',
            'averageStaffPerHouse',
            'housesExceedingThresholds',
            'accreditationsNearingExpiry',
            // Staff performance partial
            'staffPerformance',
            'applicationsProcessed',
            'approvalDistribution'
        ));
    }

    /**
     * Accreditation Performance Report
     * Requirements: 2.1-2.6
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function accreditationPerformance()
    {
        // Get all monthly trends (combined)
        $monthlyTrendsData = $this->accreditationService->getMonthlyTrends(12);
        $monthlyTrends = $monthlyTrendsData; // Raw collection for table
        
        // Get separate trends for accreditations and registrations
        // Ensure we have data for all 12 months
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }
        
        $dateExpr = DB::getDriverName() === 'sqlite' 
            ? "strftime('%Y-%m', created_at)" 
            : "DATE_FORMAT(created_at, '%Y-%m')";

        // Accreditation trends (excluding registrations)
        $accreditationData = Application::selectRaw("$dateExpr as month")
            ->selectRaw('COUNT(*) as total_submitted')
            ->selectRaw("SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) as total_approved")
            ->where('application_type', '!=', 'registration')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');
        
        // Registration trends
        $registrationData = Application::selectRaw("$dateExpr as month")
            ->selectRaw('COUNT(*) as total_submitted')
            ->selectRaw("SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) as total_approved")
            ->where('application_type', 'registration')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');
        
        // Fill in missing months with zeros
        $accreditationTrends = $months->map(function($month) use ($accreditationData) {
            $data = $accreditationData->get($month);
            return (object) [
                'month' => $month,
                'total_submitted' => $data ? $data->total_submitted : 0,
                'total_approved' => $data ? $data->total_approved : 0,
            ];
        });
        
        $registrationTrends = $months->map(function($month) use ($registrationData) {
            $data = $registrationData->get($month);
            return (object) [
                'month' => $month,
                'total_submitted' => $data ? $data->total_submitted : 0,
                'total_approved' => $data ? $data->total_approved : 0,
            ];
        });
        
        $approvalRatioByCategory = $this->accreditationService->getApprovalRatioByCategory();
        $categoryDistribution = $this->accreditationService->getCategoryDistribution();
        
        
        $categories = $categoryDistribution;
        
        // Get media house registrations count
        $mediaHouseRegistrations = Application::where('application_type', 'registration')
            ->whereIn('status', ['issued', 'pending_payment', 'officer_review', 'registrar_review'])
            ->count();
        
        return view('staff.director.reports.accreditation', compact(
            'monthlyTrends',
            'accreditationTrends',
            'registrationTrends',
            'months',
            'categories',
            'categoryDistribution',
            'approvalRatioByCategory',
            'mediaHouseRegistrations'
        ));
    }

    /**
     * Financial Performance Report
     * Requirements: 3.1-3.8
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function financialOverview()
    {
        $trendData = $this->financialService->getMonthlyRevenueTrend();
        
        $monthlyRevenueTrend = [
            'months' => $trendData['current_year']->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->toArray(),
            'current_year' => $trendData['current_year']->pluck('total_revenue')->toArray(),
            'previous_year' => $trendData['previous_year']->pluck('total_revenue')->toArray(),
        ];
        
        $revenueByServiceType = $this->financialService->getRevenueByServiceType()->map(function($item) {
            $item->total = $item->total_revenue;
            return $item;
        });
        
        $revenueByApplicantType = $this->financialService->getRevenueByApplicantType()->map(function($item) {
            $item->total = $item->total_revenue;
            return $item;
        });
        
        $revenueByResidency = $this->financialService->getRevenueByResidency()->map(function($item) {
            $item->total = $item->total_revenue;
            $item->residency_type = $item->residency ?? $item->residency_type;
            return $item;
        });
        
        $revenueByPaymentMethod = $this->financialService->getRevenueByPaymentMethod()->map(function($item) {
            $item->total = $item->total_revenue;
            $item->payment_method = $item->method ?? $item->payment_method;
            return $item;
        });
        
        $waiverStatistics = $this->financialService->getWaiverStatistics();
        $outstandingPaymentsAging = $this->financialService->getOutstandingPaymentsAging();
        
        return view('staff.director.reports.financial', compact(
            'monthlyRevenueTrend',
            'revenueByServiceType',
            'revenueByApplicantType',
            'revenueByResidency',
            'revenueByPaymentMethod',
            'waiverStatistics',
            'outstandingPaymentsAging'
        ));
    }

    /**
     * Compliance and Risk Monitoring
     * Requirements: 4.1-4.12
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function complianceRisk()
    {
        $categoryReassignments = $this->complianceService->getCategoryReassignments();
        $reopenedApplications = $this->complianceService->getReopenedApplications();
        $manualOverrides = $this->complianceService->getManualOverrides();
        $certificateEdits = $this->complianceService->getCertificateEdits();
        $excessiveReprints = $this->complianceService->getExcessiveReprints();
        $printStatistics = $this->complianceService->getPrintStatistics();
        $suspiciousActivity = $this->complianceService->getSuspiciousActivityAlerts();
        
        return view('staff.director.reports.compliance', compact(
            'categoryReassignments',
            'reopenedApplications',
            'manualOverrides',
            'certificateEdits',
            'excessiveReprints',
            'printStatistics',
            'suspiciousActivity'
        ));
    }

    /**
     * Media House Oversight
     * Requirements: 5.1-5.7
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function mediaHouseOversight()
    {
        $statusCounts = $this->mediaHouseService->getMediaHouseStatusCounts();
        $averageStaffPerHouse = $this->mediaHouseService->getAverageStaffPerHouse();
        $housesExceedingThresholds = $this->mediaHouseService->getHousesExceedingThresholds();
        $accreditationsNearingExpiry = $this->mediaHouseService->getAccreditationsNearingExpiry();
        $highRiskNonRenewals = $this->mediaHouseService->getHighRiskNonRenewals();
        
        // Get detailed status breakdown
        $statusBreakdown = Application::where('application_type', 'registration')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();
        
        return view('staff.director.reports.mediahouses', compact(
            'statusCounts',
            'statusBreakdown',
            'averageStaffPerHouse',
            'housesExceedingThresholds',
            'accreditationsNearingExpiry',
            'highRiskNonRenewals'
        ));
    }

    /**
     * Accreditation Performance Metrics
     * Requirements: 7.1-7.6
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function staffPerformance(Request $request)
    {
        $period = $request->get('period', 'month');
        $from = match($period) {
            'week'    => now()->startOfWeek(),
            'quarter' => now()->startOfQuarter(),
            'year'    => now()->startOfYear(),
            'all'     => Carbon::parse(DB::table('applications')->min('created_at') ?? now()),
            default   => now()->startOfMonth(),
        };

        $applicationsProcessed = \App\Models\User::role(['accreditation_officer', 'registrar'])
            ->withCount(['processedApplications as processed_count' => fn($q) => $q->where('updated_at', '>=', $from)])
            ->orderBy('processed_count', 'desc')
            ->get(['id', 'name', 'email']);

        $paymentVerificationVolume = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'accounts'))
            ->withCount(['assignedApplications as verified_count' => fn($q) => $q->where('status', 'issued')->where('issued_at', '>=', $from)])
            ->orderBy('verified_count', 'desc')
            ->get(['id', 'name', 'email']);

        $approvalDistribution = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'accreditation_officer'))
            ->withCount([
                'assignedApplications as total_reviewed' => fn($q) => $q->whereIn('status', ['officer_approved', 'officer_rejected'])->where('last_action_at', '>=', $from),
                'assignedApplications as approved_count' => fn($q) => $q->where('status', 'officer_approved')->where('last_action_at', '>=', $from),
                'assignedApplications as rejected_count' => fn($q) => $q->where('status', 'officer_rejected')->where('last_action_at', '>=', $from),
            ])
            ->get(['id', 'name', 'email'])
            ->map(function($o) {
                $o->approval_rate = $o->total_reviewed > 0 ? round(($o->approved_count / $o->total_reviewed) * 100, 1) : 0;
                return $o;
            })
            ->sortByDesc('total_reviewed')
            ->values();

        $reassignmentFrequency = \App\Models\ActivityLog::where('action', 'registrar_reassign_category')
            ->where('created_at', '>=', $from)
            ->select('user_id', 'user_role', DB::raw('COUNT(*) as reassignment_count'))
            ->groupBy('user_id', 'user_role')
            ->with('user:id,name,email')
            ->orderBy('reassignment_count', 'desc')
            ->get()
            ->map(function($r) {
                $r->name = $r->user?->name ?? 'Unknown';
                $r->role = $r->user_role;
                return $r;
            });

        $kpis = [
            'total_processed'   => (int) $applicationsProcessed->sum('processed_count'),
            'total_verified'    => (int) $paymentVerificationVolume->sum('verified_count'),
            'total_approved'    => (int) $approvalDistribution->sum('approved_count'),
            'total_rejected'    => (int) $approvalDistribution->sum('rejected_count'),
            'total_reassign'    => (int) $reassignmentFrequency->sum('reassignment_count'),
            'avg_approval_rate' => $approvalDistribution->where('total_reviewed', '>', 0)->avg('approval_rate') ?: 0,
            'active_officers'   => (int) $applicationsProcessed->where('processed_count', '>', 0)->count(),
        ];

        if ($request->get('export') === 'csv') {
            return $this->exportStaffPerformanceCsv($applicationsProcessed, $paymentVerificationVolume, $approvalDistribution, $reassignmentFrequency, $period);
        }

        $performance = $applicationsProcessed;
        $periodLabel = match($period) {
            'week'    => 'This Week',
            'quarter' => 'This Quarter',
            'year'    => 'This Year',
            'all'     => 'All Time',
            default   => 'This Month',
        };

        return view('staff.director.reports.staff', compact(
            'applicationsProcessed',
            'performance',
            'paymentVerificationVolume',
            'approvalDistribution',
            'reassignmentFrequency',
            'kpis',
            'period',
            'periodLabel'
        ));
    }

    private function exportStaffPerformanceCsv($processed, $payments, $approvals, $reassign, $period)
    {
        $filename = 'staff-performance-' . $period . '-' . now()->format('Ymd-His') . '.csv';
        return response()->streamDownload(function() use ($processed, $payments, $approvals, $reassign, $period) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Staff Performance Report', 'Period: ' . $period, 'Generated: ' . now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['Applications Processed (Officers & Registrars)']);
            fputcsv($out, ['Name', 'Email', 'Processed Count']);
            foreach ($processed as $r) fputcsv($out, [$r->name, $r->email, $r->processed_count]);
            fputcsv($out, []);
            fputcsv($out, ['Payment Verification Volume (Accounts)']);
            fputcsv($out, ['Name', 'Email', 'Verified Count']);
            foreach ($payments as $r) fputcsv($out, [$r->name, $r->email, $r->verified_count]);
            fputcsv($out, []);
            fputcsv($out, ['Approval Distribution (Accreditation Officers)']);
            fputcsv($out, ['Name', 'Email', 'Total Reviewed', 'Approved', 'Returned', 'Approval Rate %']);
            foreach ($approvals as $r) fputcsv($out, [$r->name, $r->email, $r->total_reviewed, $r->approved_count, $r->rejected_count, $r->approval_rate]);
            fputcsv($out, []);
            fputcsv($out, ['Category Reassignment Frequency']);
            fputcsv($out, ['Name', 'Role', 'Reassignment Count']);
            foreach ($reassign as $r) fputcsv($out, [$r->name ?? '—', $r->role ?? $r->user_role, $r->reassignment_count]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Issuance and Print Oversight
     * Requirements: 8.1-8.5
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function issuanceOversight()
    {
        $printStatistics = $this->complianceService->getPrintStatistics();
        $printsByStaff = $this->staffService->getPrintActionsByStaff();
        
        // Fix printRatio variable name mismatch
        $printRatio = $printStatistics;
        
        // Get monthly issuance trend (Applications with status 'issued')
        $dateExpr = DB::getDriverName() === 'sqlite' 
            ? "strftime('%Y-%m', issued_at)" 
            : "DATE_FORMAT(issued_at, '%Y-%m')";

        $monthlyIssuance = Application::select(
            DB::raw("$dateExpr as month"),
            DB::raw("COUNT(*) as count")
        )
        ->where('status', 'issued')
        ->whereNotNull('issued_at')
        ->where('issued_at', '>=', now()->subMonths(12)->startOfMonth())
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->get();
        
        // Get outstanding unprinted approvals
        $outstandingUnprinted = Application::whereIn('status', [
            Application::PRODUCTION_QUEUE,
            Application::PRODUCED_READY,
            Application::CARD_GENERATED,
            Application::CERT_GENERATED
        ])
        ->with('applicant:id,name,email')
        ->get()
        ->map(function($app) {
            $app->days_pending = $app->updated_at ? now()->diffInDays($app->updated_at) : 0;
            $app->applicant_name = $app->applicant ? $app->applicant->name : 'N/A';
            return $app;
        });
        
        return view('staff.director.reports.issuance', compact(
            'printRatio',
            'printsByStaff',
            'monthlyIssuance',
            'outstandingUnprinted'
        ));
    }

    /**
     * Reports and Downloads Page
     * Requirements: 10.1-10.8, 12.1
     * 
     * SECURITY: Read-only operation - displays report generation interface
     */
    public function reportsDownloads()
    {
        return view('staff.director.reports.downloads');
    }

    /**
     * Generate Monthly Accreditation Report
     * Requirements: 10.1, 10.6, 10.7, 10.8
     * 
     * SECURITY: Read-only operation - generates report without modifying data
     */
    public function generateMonthlyAccreditationReport(Request $request)
    {
        $this->ensureViewOnlyAccess();
        
        $format = $request->input('format', 'pdf');
        $month = $request->input('month', now()->format('Y-m'));
        
        return $this->reportService->generateMonthlyAccreditationReport($format, [
            'month' => $month
        ]);
    }

    /**
     * Generate Revenue and Financial Report
     * Requirements: 10.2, 10.6, 10.7, 10.8
     * 
     * SECURITY: Read-only operation - generates report without modifying data
     */
    public function generateRevenueFinancialReport(Request $request)
    {
        $this->ensureViewOnlyAccess();
        
        $format = $request->input('format', 'pdf');
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        return $this->reportService->generateRevenueFinancialReport($format, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    /**
     * Generate Compliance and Audit Report
     * Requirements: 10.3, 10.6, 10.7, 10.8
     * 
     * SECURITY: Read-only operation - generates report without modifying data
     */
    public function generateComplianceAuditReport(Request $request)
    {
        $this->ensureViewOnlyAccess();
        
        $format = $request->input('format', 'pdf');
        $month = $request->input('month', now()->format('Y-m'));
        
        return $this->reportService->generateComplianceAuditReport($format, [
            'month' => $month
        ]);
    }

    /**
     * Generate Media House Status Report
     * Requirements: 10.4, 10.6, 10.7, 10.8
     * 
     * SECURITY: Read-only operation - generates report without modifying data
     */
    public function generateMediaHouseStatusReport(Request $request)
    {
        $this->ensureViewOnlyAccess();
        
        $format = $request->input('format', 'pdf');
        
        return $this->reportService->generateMediaHouseStatusReport($format, []);
    }

    /**
     * Generate Operational Performance Report
     * Requirements: 10.5, 10.6, 10.7, 10.8
     * 
     * SECURITY: Read-only operation - generates report without modifying data
     */
    public function generateOperationalPerformanceReport(Request $request)
    {
        $this->ensureViewOnlyAccess();
        
        $format = $request->input('format', 'pdf');
        $month = $request->input('month', now()->format('Y-m'));
        
        return $this->reportService->generateOperationalPerformanceReport($format, [
            'month' => $month
        ]);
    }

    /**
     * Geographic Distribution
     * Requirements: 9.1-9.5
     * 
     * SECURITY: Read-only operation - displays data without modification
     */
    public function geographicDistribution()
    {
        // Placeholder - geographic data not available in current schema
        return view('staff.director.reports.geographic', [
            'accreditationsByRegion' => collect([]),
            'revenueByRegion' => collect([]),
            'processingTimeByRegion' => collect([]),
            'mediaHousesByRegion' => collect([]),
        ]);
    }

    /**
     * Applications awaiting Director Review
     */
    public function applications(Request $request)
    {
        $query = Application::query()
            ->with(['applicant'])
            ->where('status', Application::DIRECTOR_REVIEW);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                    ->orWhereHas('applicant', function ($aq) use ($s) {
                        $aq->where('name', 'like', "%$s%");
                    });
            });
        }

        $applications = $query->latest('updated_at')->paginate(20)->withQueryString();

        return view('staff.director.applications.index', compact('applications'));
    }

    public function showApplication(Application $application)
    {
        $application->load(['applicant', 'documents', 'messages', 'workflowLogs']);

        return view('staff.director.applications.show', compact('application'));
    }

    public function approveApplication(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            ApplicationWorkflowService::directorApprove($application, [
                'notes' => $data['decision_notes'] ?? null,
            ]);

            return redirect()->route('staff.director.applications.index')->with('success', 'Application approved and forwarded to Superadmin.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }

    public function rejectApplication(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['required', 'string', 'max:5000'],
        ]);

        try {
            ApplicationWorkflowService::directorReject($application, [
                'notes' => $data['decision_notes'],
            ]);

            return redirect()->route('staff.director.applications.index')->with('success', 'Application returned to Applicant for correction.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }
}
