<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\PaymentSubmission;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\PrintLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->role;
        $config = $this->getRoleConfig($role);

        return view('staff.reports.index', compact('config', 'role'));
    }

    public function getConfig()
    {
        return response()->json($this->getRoleConfig(Auth::user()->role));
    }

    private function getRoleConfig($role)
    {
        $allTabs = [
            'overview' => 'Overview',
            'applications' => 'Applications',
            'registration' => 'Registration',
            'accreditation' => 'Accreditation',
            'applicant-accounts' => 'Applicant Accounts',
            'payments' => 'Payments',
            'annual-levy' => 'Annual Levy',
            'user-activity' => 'User Activity',
            'audit-logs' => 'Audit Logs',
            'printing-collection' => 'Printing & Collection',
        ];

        $configs = [
            'super_admin' => [
                'allowedTabs' => ['overview', 'applications', 'applicant-accounts', 'payments', 'annual-levy', 'user-activity', 'audit-logs', 'printing-collection'],
            ],
            'auditor' => [
                'allowedTabs' => ['audit-logs', 'user-activity', 'applications', 'applicant-accounts', 'payments', 'annual-levy', 'printing-collection'],
            ],
            'accounts_payments' => [
                'allowedTabs' => ['payments', 'annual-levy', 'applicant-accounts'],
            ],
            'registrar' => [
                'allowedTabs' => ['overview', 'applications', 'registration', 'accreditation', 'applicant-accounts', 'printing-collection'],
            ],
        ];

        $config = $configs[$role] ?? $configs['registrar'];

        $config['tabs'] = array_intersect_key($allTabs, array_flip($config['allowedTabs']));

        $config['filterOptions'] = [
            'application_types' => [
                '' => 'All',
                'registration' => 'Registration',
                'accreditation' => 'Accreditation',
                'renewal' => 'Renewal',
                'replacement' => 'Replacement',
                'annual-levy' => 'Annual Levy'
            ],
            'statuses' => [
                '' => 'All',
                Application::OFFICER_REVIEW => 'Under Review',
                Application::REGISTRAR_APPROVED => 'Approved',
                Application::REGISTRAR_REJECTED => 'Rejected / Not Approved',
                Application::CORRECTION_REQUESTED => 'Returned for Correction',
                Application::APPROVED_AWAITING_PAYMENT => 'Pending Payment'
            ],
            'scopes' => [
                '' => 'All',
                'local' => 'Local',
                'foreign' => 'Foreign'
            ],
            'regions' => [
                '' => 'All',
                'Harare' => 'Harare',
                'Bulawayo' => 'Bulawayo',
                'Mutare' => 'Mutare',
                'Gweru' => 'Gweru',
                'Masvingo' => 'Masvingo',
                'Other' => 'Other'
            ],
            'payment_statuses' => [
                '' => 'All',
                'paid' => 'Paid',
                'unpaid' => 'Unpaid',
                'pending' => 'Pending Verification',
                'failed' => 'Failed',
                'refunded' => 'Refunded'
            ],
            'form_types' => [
                '' => 'All',
                'AP1' => 'AP1', 'AP2' => 'AP2', 'AP3' => 'AP3', 'AP4' => 'AP4', 'AP5' => 'AP5', 'AP6' => 'AP6'
            ],
            'officers' => User::role(['accreditation_officer', 'registrar', 'accounts_payments', 'super_admin'])->pluck('name', 'id')->prepend('All Users', '')->toArray(),
            'categories' => [
                'registration' => [
                    'MC' => 'Community Media', 'MA' => 'Advertising Agency', 'MF' => 'Foreign Media Service',
                    'MN' => 'National Media Service', 'DG' => 'Internet Based Media', 'MP' => 'Production House',
                    'MS' => 'Multiple Categories', 'MV' => 'Film and Video'
                ],
                'accreditation' => [
                    'JE' => 'Local Full-time', 'JF' => 'Local Freelance', 'JO' => 'Local Foreign Office',
                    'JS' => 'Local Stringer', 'JM' => 'Local Multi-report', 'JP' => 'Local Content/PR',
                    'JD' => 'Local Social Media', 'JT' => 'Foreign Temp'
                ]
            ]
        ];

        return $config;
    }

    public function getData(Request $request, $tab)
    {
        $role = Auth::user()->role;
        $config = $this->getRoleConfig($role);

        if (!in_array($tab, $config['allowedTabs'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $method = 'get' . str_replace(' ', '', ucwords(str_replace('-', ' ', $tab))) . 'Data';
        if (method_exists($this, $method)) {
            return $this->$method($request);
        }

        return response()->json(['error' => 'Report not implemented'], 501);
    }

    private function applyCommonFilters($query, Request $request)
    {
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        } else {
            $query->whereDate('created_at', '>=', Carbon::now()->startOfYear());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('application_type')) {
            $query->where('application_type', $request->application_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('scope')) {
            $query->where(function($q) use ($request) {
                if ($request->scope === 'local') {
                    $q->where('journalist_scope', 'local')->orWhere('residency_type', 'local');
                } else {
                    $q->where('journalist_scope', 'foreign')->orWhere('residency_type', 'non-resident');
                }
            });
        }

        if ($request->filled('region')) {
            $query->where('collection_region', $request->region);
        }

        if ($request->filled('officer_id')) {
            $query->where('assigned_officer_id', $request->officer_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('form_type')) {
            $query->where('form_type', $request->form_type);
        }

        if ($request->filled('category')) {
            $query->where(function($q) use ($request) {
                $q->where('media_house_category_code', $request->category)
                  ->orWhere('accreditation_category_code', $request->category);
            });
        }

        return $query;
    }

    private function getOverviewData(Request $request)
    {
        $query = Application::whereNotIn('status', [Application::DRAFT, Application::WITHDRAWN])->with('applicant');
        $this->applyCommonFilters($query, $request);

        $data['summary'] = [
            'total_apps' => (clone $query)->count(),
            'under_review' => (clone $query)->whereIn('status', [Application::OFFICER_REVIEW, Application::REGISTRAR_REVIEW])->count(),
            'approved' => (clone $query)->where('status', Application::REGISTRAR_APPROVED)->count(),
            'rejected' => (clone $query)->whereIn('status', [Application::OFFICER_REJECTED, Application::REGISTRAR_REJECTED])->count(),
            'paid_apps' => (clone $query)->where('payment_status', 'paid')->count(),
            'pending_payments' => (clone $query)->where('status', Application::APPROVED_AWAITING_PAYMENT)->count(),
        ];

        $data['charts'] = [
            'type_distribution' => (clone $query)->select('application_type', \DB::raw('count(*) as count'))
                ->groupBy('application_type')->get()
                ->map(fn($c) => ['label' => ucfirst($c->application_type), 'value' => $c->count]),
            'status_distribution' => (clone $query)->select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')->get()
                ->map(fn($c) => ['label' => Application::statusLabel($c->status), 'value' => $c->count]),
        ];

        $data['data'] = $query->latest()->limit(20)->get()->map(function($app) {
            return [
                'ref' => $app->reference,
                'applicant' => $app->applicant?->name ?? '—',
                'type' => ucfirst($app->application_type),
                'status' => Application::statusLabel($app->status),
                'date' => $app->created_at->format('d M Y'),
            ];
        });
        
        return response()->json($data);
    }

    private function getApplicationsData(Request $request)
    {
        $query = Application::query()->with(['applicant', 'assignedOfficer']);
        $this->applyCommonFilters($query, $request);

        $data['summary'] = [
            'total' => (clone $query)->count(),
            'under_review' => (clone $query)->whereIn('status', [Application::OFFICER_REVIEW, Application::REGISTRAR_REVIEW])->count(),
            'approved' => (clone $query)->where('status', Application::REGISTRAR_APPROVED)->count(),
            'rejected' => (clone $query)->whereIn('status', [Application::OFFICER_REJECTED, Application::REGISTRAR_REJECTED])->count(),
            'paid' => (clone $query)->where('payment_status', 'paid')->count(),
            'overdue' => $this->getOverdueCount(),
        ];

        $data['charts'] = [
            'byType' => (clone $query)->select('application_type as label', DB::raw('count(*) as value'))->groupBy('application_type')->get(),
            'byStatus' => (clone $query)->select('status as label', DB::raw('count(*) as value'))->groupBy('status')->get(),
        ];

        $data['data'] = $query->latest()->limit(50)->get()->map(function ($app) {
            return [
                'ref' => $app->reference,
                'applicant' => $app->applicant?->name ?? '—',
                'type' => ucfirst($app->application_type),
                'form' => $app->form_type ?? '—',
                'category' => $app->media_house_category_code ?? $app->journalist_category_code ?? '—',
                'region' => $app->collection_region ?? '—',
                'status' => Application::statusLabel($app->status),
                'payment' => ucfirst($app->payment_status ?? 'pending'),
                'officer' => $app->assignedOfficer?->name ?? 'Unassigned',
                'date' => $app->created_at->format('d M Y'),
                'days' => $app->created_at->diffInDays(now()) . 'd',
            ];
        });

        return response()->json($data);
    }

    private function getPaymentsData(Request $request)
    {
        $query = \App\Models\Payment::query()->with(['payer', 'application']);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('payment_status')) $query->where('status', $request->payment_status);

        $data['summary'] = [
            'total_revenue' => (clone $query)->where('status', 'paid')->sum('amount'),
            'registration' => (clone $query)->where('status', 'paid')->whereHas('application', fn($q) => $q->where('application_type', 'registration'))->sum('amount'),
            'accreditation' => (clone $query)->where('status', 'paid')->whereHas('application', fn($q) => $q->where('application_type', 'accreditation'))->sum('amount'),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
        ];

        $data['data'] = $query->latest()->limit(50)->get()->map(function($p) {
            return [
                'pay_ref' => $p->reference ?? '—',
                'app_ref' => $p->application?->reference ?? '—',
                'applicant' => $p->payer?->name ?? '—',
                'type' => ucfirst($p->application?->application_type ?? '—'),
                'amount' => number_format($p->amount, 2),
                'currency' => $p->currency ?? 'USD',
                'method' => strtoupper($p->method ?? 'bank'),
                'status' => ucfirst($p->status),
                'date' => $p->created_at->format('d M Y'),
                'verified_by' => User::find($p->reconciled_by)?->name ?? '—',
            ];
        });

        return response()->json($data);
    }

    private function getAnnualLevyData(Request $request)
    {
        $query = Application::where('form_type', 'AP6')->with('applicant');
        $this->applyCommonFilters($query, $request);

        $data['summary'] = [
            'total_submissions' => (clone $query)->count(),
            'total_revenue' => \App\Models\Payment::whereHas('application', fn($q) => $q->where('form_type', 'AP6'))->where('status', 'paid')->sum('amount'),
            'paid' => (clone $query)->where('payment_status', 'paid')->count(),
            'pending' => (clone $query)->where('payment_status', '!=', 'paid')->count(),
        ];

        $data['data'] = $query->latest()->limit(50)->get()->map(function($app) {
            $turnover = $app->form_data['gross_annual_turnover'] ?? 0;
            return [
                'name' => $app->applicant?->name ?? '—',
                'proprietor' => $app->applicant?->profile_data['proprietor_name'] ?? '—',
                'category' => $app->media_house_category_code ?? '—',
                'year' => $app->form_data['financial_year'] ?? '—',
                'turnover' => number_format($turnover, 2),
                'levy' => number_format($turnover * (float) config('fees.media_house.levy_rate'), 2),
                'status' => ucfirst($app->payment_status ?? 'pending'),
                'date' => $app->created_at->format('d M Y'),
            ];
        });

        return response()->json($data);
    }

    private function getUserActivityData(Request $request)
    {
        $query = User::role(['accreditation_officer', 'registrar', 'accounts_payments', 'super_admin']);
        
        $data['summary'] = [
            'active_users' => (clone $query)->count(),
            'total_logs' => ActivityLog::count(),
            'revenue_verified' => \App\Models\Payment::where('status', 'paid')->sum('amount'),
        ];

        // Optimize: Group counts and sums to avoid N+1
        $user_ids = (clone $query)->pluck('id');
        
        $activityCounts = ActivityLog::whereIn('user_id', $user_ids)
            ->select('user_id', \DB::raw('count(*) as total'), 
                \DB::raw('sum(case when action in ("application_reviewed", "officer_approve", "registrar_approve") then 1 else 0 end) as reviewed'),
                \DB::raw('sum(case when action in ("officer_approve", "registrar_approve") then 1 else 0 end) as approved')
            )->groupBy('user_id')->get()->keyBy('user_id');

        $revenueSums = \App\Models\Payment::whereIn('reconciled_by', $user_ids)
            ->where('status', 'paid')
            ->select('reconciled_by', \DB::raw('sum(amount) as total'))
            ->groupBy('reconciled_by')->get()->keyBy('reconciled_by');

        $data['data'] = $query->get()->map(function($user) use ($activityCounts, $revenueSums) {
            $stats = $activityCounts->get($user->id);
            $rev = $revenueSums->get($user->id);
            
            return [
                'name' => $user->name,
                'role' => ucwords(str_replace('_', ' ', $user->roles->first()?->name ?? '—')),
                'reviewed' => $stats?->reviewed ?? 0,
                'approved' => $stats?->approved ?? 0,
                'revenue' => '$' . number_format($rev?->total ?? 0, 2),
                'last_act' => ActivityLog::where('user_id', $user->id)->latest()->first()?->created_at?->format('d M Y H:i') ?? 'Never',
            ];
        });

        return response()->json($data);
    }

    private function getAuditLogsData(Request $request)
    {
        $query = ActivityLog::with('user');
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);

        $data['data'] = $query->latest()->limit(100)->get()->map(function($log) {
            return [
                'time' => $log->created_at->format('d M Y H:i'),
                'user' => $log->user?->name ?? 'System',
                'role' => $log->user?->roles->first()?->name ?? '—',
                'action' => ucwords(str_replace('_', ' ', $log->action)),
                'details' => Str::limit(json_encode($log->meta), 50),
                'ip' => $log->ip ?? '—',
            ];
        });

        return response()->json($data);
    }

    private function getPrintingCollectionData(Request $request)
    {
        $query = PrintLog::query();
        $query->leftJoin('applications', 'print_logs.application_reference', '=', 'applications.reference_code')
            ->select('print_logs.*', 'applications.collection_region', 'applications.id as application_id', 'applications.application_type');

        if ($request->filled('date_from')) $query->whereDate('print_logs.created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('print_logs.created_at', '<=', $request->date_to);

        $data['summary'] = [
            'total_prints' => (clone $query)->count(),
            'cards' => (clone $query)->where('print_logs.type', 'card')->count(),
            'certs' => (clone $query)->where('print_logs.type', 'certificate')->count(),
            'pending_collection' => Application::where('status', Application::PRODUCED_READY)->count(),
        ];

        $data['data'] = $query->latest('print_logs.created_at')->limit(50)->get()->map(function($log) {
            return [
                'type' => ucfirst($log->type),
                'ref' => $log->application_reference,
                'region' => $log->collection_region ?? '—',
                'printed_by' => User::find($log->user_id)?->name ?? '—',
                'date' => $log->created_at->format('d M Y H:i'),
                'actions' => ['id' => $log->application_id, 'type' => $log->type]
            ];
        });

        return response()->json($data);
    }

    private function getOverdueCount()
    {
        return Application::where('created_at', '<', Carbon::now()->subDays(14))
            ->whereNotIn('status', [Application::REGISTRAR_APPROVED, Application::ISSUED, Application::PRINTED])
            ->count();
    }

    private function getApplicantAccountsData(Request $request)
    {
        $query = User::whereDoesntHave('roles');
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);

        $data['summary'] = [
            'total_accounts' => (clone $query)->count(),
            'verified' => (clone $query)->whereNotNull('email_verified_at')->count(),
            'with_apps' => (clone $query)->whereHas('applications')->count(),
            'no_apps' => (clone $query)->whereDoesntHave('applications')->count(),
            'cards_ready' => Application::where('application_type', 'accreditation')
                ->whereIn('status', [Application::PRODUCTION_QUEUE, Application::CARD_GENERATED])
                ->count(),
            'certs_ready' => Application::where('application_type', 'registration')
                ->whereIn('status', [Application::PRODUCTION_QUEUE, Application::CERT_GENERATED])
                ->count(),
        ];

        $data['data'] = $query->latest()->limit(50)->get()->map(function($user) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'type' => ucfirst($user->account_type ?? 'individual'),
                'status' => $user->email_verified_at ? 'Verified' : 'Pending',
                'created' => $user->created_at->format('d M Y'),
                'apps' => Application::where('applicant_user_id', $user->id)->count(),
            ];
        });

        return response()->json($data);
    }

    private function getRegistrationData(Request $request)
    {
        $query = Application::where('application_type', 'registration')->with(['applicant', 'assignedOfficer']);
        $this->applyCommonFilters($query, $request);

        $data['summary'] = [
            'total_reg' => (clone $query)->count(),
            'new' => (clone $query)->where('form_type', 'AP1')->count(),
            'renewals' => (clone $query)->where('form_type', 'AP2')->count(),
            'replacements' => (clone $query)->where('request_type', 'replacement')->count(),
            'approved' => (clone $query)->where('status', Application::REGISTRAR_APPROVED)->count(),
        ];

        // Optimize: Get all paid levy applicant IDs in one go
        $paidLevyUserIds = Application::where('form_type', 'AP6')
            ->where('payment_status', 'paid')
            ->pluck('applicant_user_id')
            ->toArray();

        $data['data'] = $query->latest()->limit(50)->get()->map(function($app) use ($paidLevyUserIds) {
            $hasPaidLevy = in_array($app->applicant_user_id, $paidLevyUserIds);

            return [
                'ref' => $app->reference,
                'media_house' => $app->applicant?->name ?? '—',
                'form' => $app->form_type,
                'category' => $app->media_house_category_code ?? '—',
                'status' => Application::statusLabel($app->status),
                'levy_paid' => $hasPaidLevy ? 'Yes' : 'No',
                'cert_status' => $app->status === Application::ISSUED ? 'Issued' : ($app->status === Application::PRINTED ? 'Printed' : 'Pending'),
                'date' => $app->created_at->format('d M Y'),
            ];
        });

        // Add charts for Registration
        $data['charts'] = [
            'category_distribution' => (clone $query)->select('media_house_category_code', \DB::raw('count(*) as count'))
                ->groupBy('media_house_category_code')->get()
                ->map(fn($c) => ['label' => $c->media_house_category_code ?? 'Unknown', 'value' => $c->count]),
            'payment_status' => (clone $query)->select('payment_status', \DB::raw('count(*) as count'))
                ->groupBy('payment_status')->get()
                ->map(fn($c) => ['label' => ucfirst($c->payment_status ?? 'pending'), 'value' => $c->count]),
        ];

        return response()->json($data);
    }
    private function getAccreditationData(Request $request)
    {
        $query = Application::where('application_type', 'accreditation')->with(['applicant', 'assignedOfficer']);
        $this->applyCommonFilters($query, $request);

        // Optimize: Single query for all summary counts
        $summaryCounts = (clone $query)->select('journalist_scope', \DB::raw('count(*) as count'), \DB::raw('sum(case when status = "'.Application::REGISTRAR_APPROVED.'" then 1 else 0 end) as approved'))
            ->groupBy('journalist_scope')->get();

        $data['summary'] = [
            'total_acc' => $summaryCounts->sum('count'),
            'local' => $summaryCounts->where('journalist_scope', 'local')->first()?->count ?? 0,
            'foreign' => $summaryCounts->where('journalist_scope', 'foreign')->first()?->count ?? 0,
            'approved' => $summaryCounts->sum('approved'),
        ];

        $data['data'] = $query->latest()->limit(50)->get()->map(function($app) {
            return [
                'ref' => $app->reference,
                'journalist' => $app->applicant?->name ?? '—',
                'category' => $app->accreditation_category_code ?? '—',
                'status' => Application::statusLabel($app->status),
                'card_status' => $app->status === Application::ISSUED ? 'Issued' : ($app->status === Application::PRINTED ? 'Printed' : 'Pending'),
                'date' => $app->created_at->format('d M Y'),
            ];
        });

        // Add charts for Accreditation
        $data['charts'] = [
            'journalist_categories' => (clone $query)->select('accreditation_category_code', \DB::raw('count(*) as count'))
                ->groupBy('accreditation_category_code')->get()
                ->map(fn($c) => ['label' => $c->accreditation_category_code ?? 'Unknown', 'value' => $c->count]),
            'card_status' => (clone $query)->select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')->get()
                ->map(fn($c) => ['label' => Application::statusLabel($c->status), 'value' => $c->count]),
        ];

        return response()->json($data);
    }

    public function export(Request $request)
    {
        $tab = $request->tab ?? 'applications';
        $format = $request->format ?? 'csv';
        
        $response = $this->getData($request, $tab);
        $data = $response->getData(true);
        $list = $data['data'] ?? [];

        if (empty($list)) {
            return back()->with('error', 'No data to export');
        }

        $filename = 'zmc-report-' . $tab . '-' . date('Y-m-d') . '.' . $format;

        // Log the export
        ActivityLog::create([
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->role,
            'action' => 'report_export',
            'meta' => ['tab' => $tab, 'format' => $format, 'filters' => $request->all()],
            'ip' => $request->ip(),
            'entity_type' => 'report',
            'entity_id' => 0
        ]);

        if ($format === 'csv') {
            $headers = [
                "Content-type" => "text/csv",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma" => "no-cache",
                "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
                "Expires" => "0"
            ];

            $callback = function() use ($list) {
                $file = fopen('php://output', 'w');
                fputcsv($file, array_keys($list[0]));
                foreach ($list as $row) {
                    // Remove internal actions metadata before CSV export
                    unset($row['actions']);
                    fputcsv($file, array_values($row));
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // PDF and Excel would typically use libraries like DomPDF or Maatwebsite/Laravel-Excel
        // For now, we'll return CSV for all until libraries are confirmed
        return response()->json(['message' => 'Format ' . $format . ' export triggered for ' . count($list) . ' records.']);
    }
}
