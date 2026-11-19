<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditFlag;
use App\Models\AuditLog;
use App\Models\AuditLogArchive;
use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\RegistrationRecord;
use App\Models\AccreditationRecord;
use App\Models\ComplianceCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AuditorController extends Controller
{
    /**
     * Auditor dashboard (KPIs)
     */
    public function dashboard(Request $request)
    {
        $base = Application::query();

        $totalApplications = (clone $base)->count();
        $approvedCount = (clone $base)->whereIn('status', [
            Application::OFFICER_APPROVED,
            Application::REGISTRAR_APPROVED,
            Application::PAID_CONFIRMED,
            Application::PRODUCTION_QUEUE,
            Application::CARD_GENERATED,
            Application::CERT_GENERATED,
            Application::PRINTED,
            Application::ISSUED,
        ])->count();

        $rejectedCount = (clone $base)->whereIn('status', [
            Application::OFFICER_REJECTED,
            Application::REGISTRAR_REJECTED,
        ])->count();

        $waiversApproved = (clone $base)->where('waiver_status', 'approved')->count();
        $proofsApproved  = (clone $base)->where('proof_status', 'approved')->count();
        $paynowConfirmed = (clone $base)->whereNotNull('paynow_confirmed_at')->count();

        $irregularApprovedWithoutPayment = (clone $base)
            ->whereIn('status', [
                Application::PAID_CONFIRMED,
                Application::PRODUCTION_QUEUE,
                Application::CARD_GENERATED,
                Application::CERT_GENERATED,
                Application::PRINTED,
                Application::ISSUED,
            ])
            ->where(function ($w) {
                $w->whereNull('paynow_confirmed_at')
                  ->where(function ($x) {
                      $x->whereNull('proof_status')->orWhere('proof_status', '!=', 'approved');
                  })
                  ->where(function ($x) {
                      $x->whereNull('waiver_status')->orWhere('waiver_status', '!=', 'approved');
                  });
            })
            ->count();

        $recentFlags = AuditFlag::query()
            ->latest()->limit(10)->get();

        // System-wide activity feed (all roles)
        $activity = ActivityLog::query()
            ->with(['entity','user'])
            ->latest('created_at')
            ->limit(12)
            ->get();

        return view('staff.auditor.dashboard', compact(
            'totalApplications','approvedCount','rejectedCount',
            'waiversApproved','proofsApproved','paynowConfirmed',
            'irregularApprovedWithoutPayment','recentFlags','activity',
        ));
    }

    /**
     * Analytics page (charts)
     */
    public function analytics(Request $request)
    {
        $days = (int)($request->get('days') ?? 30);
        $days = max(7, min(365, $days));
        $to = Carbon::now()->endOfDay();
        $from = (clone $to)->subDays($days - 1)->startOfDay();

        // Line chart: applications per day
        $daily = Application::query()
            ->selectRaw("date(created_at) as d, count(*) as c")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->toArray();

        $approvedDaily = Application::query()
            ->selectRaw("date(created_at) as d, count(*) as c")
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', [
                Application::OFFICER_APPROVED,
                Application::REGISTRAR_APPROVED,
                Application::PAID_CONFIRMED,
                Application::PRODUCTION_QUEUE,
                Application::CARD_GENERATED,
                Application::CERT_GENERATED,
                Application::PRINTED,
                Application::ISSUED,
            ])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->toArray();

        $rejectedDaily = Application::query()
            ->selectRaw("date(created_at) as d, count(*) as c")
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', [
                Application::OFFICER_REJECTED,
                Application::REGISTRAR_REJECTED,
            ])
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('c', 'd')
            ->toArray();

        $labels = [];
        $seriesApplications = [];
        $seriesApproved     = [];
        $seriesRejected     = [];

        for ($i = 0; $i < $days; $i++) {
            $date = (clone $from)->addDays($i)->toDateString();
            $labels[] = $date;
            $seriesApplications[] = (int)($daily[$date] ?? 0);
            $seriesApproved[]     = (int)($approvedDaily[$date] ?? 0);
            $seriesRejected[]     = (int)($rejectedDaily[$date] ?? 0);
        }

        // Anomaly Severity Breakdown
        $severityCounts = AuditFlag::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('severity, count(*) as c')
            ->groupBy('severity')
            ->pluck('c', 'severity')
            ->toArray();

        $anomalyLabels = ['High', 'Medium', 'Low'];
        $anomalySeries = [
            (int)($severityCounts['high'] ?? 0),
            (int)($severityCounts['medium'] ?? 0),
            (int)($severityCounts['low'] ?? 0),
        ];

        // Payment Verification Distribution
        $paynowCount = Application::whereBetween('created_at', [$from, $to])->whereNotNull('paynow_confirmed_at')->count();
        $proofCount  = Application::whereBetween('created_at', [$from, $to])->where('proof_status', 'approved')->count();
        $waiverCount = Application::whereBetween('created_at', [$from, $to])->where('waiver_status', 'approved')->count();

        $paymentLabels = ['Paynow Confirmed', 'Proof Approved', 'Waiver Approved'];
        $paymentSeries = [$paynowCount, $proofCount, $waiverCount];

        // Cross-functional oversight trends (registration, accreditation, complaints and appeals).
        $registrationDaily = RegistrationRecord::query()->whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();
        $accreditationDaily = AccreditationRecord::query()->whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();
        $complaintDaily = Complaint::query()->whereBetween('created_at', [$from, $to])->where('type', 'complaint')
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();
        $appealDaily = Complaint::query()->whereBetween('created_at', [$from, $to])->where('type', 'appeal')
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();
        $complianceDaily = ComplianceCase::query()->whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->orderBy('d')->pluck('c', 'd')->toArray();
        $seriesRegistration = $seriesAccreditation = $seriesComplaints = $seriesAppeals = $seriesCompliance = [];
        for ($i = 0; $i < $days; $i++) {
            $date = (clone $from)->addDays($i)->toDateString();
            $seriesRegistration[] = (int)($registrationDaily[$date] ?? 0);
            $seriesAccreditation[] = (int)($accreditationDaily[$date] ?? 0);
            $seriesComplaints[] = (int)($complaintDaily[$date] ?? 0);
            $seriesAppeals[] = (int)($appealDaily[$date] ?? 0);
            $seriesCompliance[] = (int)($complianceDaily[$date] ?? 0);
        }
        $areaLabels = ['Registration', 'Accreditation', 'Complaints', 'Appeals', 'Public Information Compliance'];
        $areaSeries = [array_sum($seriesRegistration), array_sum($seriesAccreditation), array_sum($seriesComplaints), array_sum($seriesAppeals), array_sum($seriesCompliance)];

        // Pie chart: application buckets (Existing logic refined)
        $bucketCounts = Application::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw("request_type, application_type, journalist_scope, count(*) as c")
            ->groupBy('request_type', 'application_type', 'journalist_scope')
            ->get()
            ->reduce(function ($carry, $row) {
                $bucket = Application::bucketKey(
                    (string)$row->request_type,
                    (string)$row->application_type,
                    (string)$row->journalist_scope
                );
                if ($bucket) {
                    $carry[$bucket] = ($carry[$bucket] ?? 0) + (int)$row->c;
                }
                return $carry;
            }, []);

        $bucketLabels = Application::bucketLabels();
        $pieLabels = [];
        $pieValues = [];
        foreach ($bucketLabels as $key => $label) {
            $pieLabels[] = $label;
            $pieValues[] = (int)($bucketCounts[$key] ?? 0);
        }

        return view('staff.auditor.analytics', compact(
            'days','from','to',
            'labels','seriesApplications','seriesApproved','seriesRejected',
            'anomalyLabels','anomalySeries',
            'paymentLabels','paymentSeries',
            'pieLabels','pieValues',
            'seriesRegistration','seriesAccreditation','seriesComplaints','seriesAppeals',
            'seriesCompliance',
            'areaLabels','areaSeries'
        ));
    }

    /**
     * User login events + per-user activity trails
     */
    public function logins(Request $request)
    {
        $q = trim((string)$request->get('q'));
        $from = $request->date('date_from') ?? $request->date('from');
        $to = $request->date('date_to') ?? $request->date('to');

        $logs = AuditLog::query()
            ->with(['actor'])
            ->where('action', 'like', '%login%')
            ->when($from, fn($x) => $x->whereDate('created_at', '>=', $from))
            ->when($to, fn($x) => $x->whereDate('created_at', '<=', $to))
            ->when($q, function ($query) use ($q) {
                $query->where('ip', 'like', "%{$q}%")
                    ->orWhere('action', 'like', "%{$q}%")
                    ->orWhereHas('actor', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        // For each login event, fetch a compact slice of recent actions by that user
        $actorIds = $logs->pluck('actor_user_id')->filter()->unique()->values()->all();
        $recentByUser = [];
        if (!empty($actorIds)) {
            $recent = AuditLog::query()
                ->with(['actor'])
                ->whereIn('actor_user_id', $actorIds)
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            foreach ($recent as $r) {
                $recentByUser[$r->actor_user_id] = $recentByUser[$r->actor_user_id] ?? [];
                if (count($recentByUser[$r->actor_user_id]) < 8) {
                    $recentByUser[$r->actor_user_id][] = $r;
                }
            }
        }

        return view('staff.auditor.logins', compact('logs','q','from','to','recentByUser'));
    }

    /** Application & accreditation audits */
    public function applications(Request $request)
    {
        // Limit the audit dataset to non-personal workflow and financial metadata.
        $q = Application::query()->select([
            'id','reference','application_type','request_type','journalist_scope','status',
            'payment_status','payment_submission_method','payment_stage','proof_amount_paid',
            'paynow_reference','paynow_confirmed_at','payment_proof_path','payment_proof_uploaded_at',
            'proof_status','waiver_path','waiver_status','created_at','updated_at',
        ]);

        // Filter: only show applications approved for payment or further
        $q->whereNotIn('status', [Application::DRAFT, Application::SUBMITTED, Application::OFFICER_REVIEW, Application::CORRECTION_REQUESTED]);

        // Unified application bucket filter (8 types)
        if ($request->filled('application_type')) {
            $q->applyBucket((string)$request->get('application_type'));
        } else {
            // Backward-compatible filters (if provided)
            if ($request->filled('request_type')) $q->where('request_type', $request->string('request_type'));
            if ($request->filled('scope')) $q->where('journalist_scope', $request->string('scope'));
            if ($request->filled('legacy_application_type')) $q->where('application_type', $request->string('legacy_application_type'));
        }
        if ($request->filled('status')) $q->where('status', $request->string('status'));
        if ($request->filled('date_from')) $q->whereDate('created_at', '>=', $request->date('date_from'));
        else if ($request->filled('from')) $q->whereDate('created_at', '>=', $request->date('from'));

        if ($request->filled('date_to')) $q->whereDate('created_at', '<=', $request->date('date_to'));
        else if ($request->filled('to')) $q->whereDate('created_at', '<=', $request->date('to'));
        if ($request->filled('search')) {
            $s = trim((string)$request->get('search'));
            $q->where(function($w) use ($s) {
                $w->where('reference', 'like', "%{$s}%")
                  ->orWhere('paynow_reference', 'like', "%{$s}%")
                  ->orWhere('payment_status', 'like', "%{$s}%");
            });
        }

        $applications = $q->latest()->paginate(25)->withQueryString();

        return view('staff.auditor.applications_audit', compact('applications'));
    }

    /** PayNow & fees audit (read-only; derived from Application payment fields) */
    public function paynow(Request $request)
    {
        $q = Application::query()->select([
            'id','reference','application_type','request_type','journalist_scope','status',
            'payment_status','payment_submission_method','payment_stage','proof_amount_paid',
            'paynow_reference','paynow_poll_url','paynow_confirmed_at','created_at','updated_at',
        ])
            ->where(function ($w) {
                $w->whereNotNull('paynow_reference')
                  ->orWhereNotNull('paynow_poll_url')
                  ->orWhereNotNull('payment_status')
                  ->orWhereNotNull('paynow_confirmed_at');
            });

        if ($request->filled('status')) $q->where('payment_status', $request->string('status'));
        if ($request->filled('from')) $q->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $q->whereDate('created_at', '<=', $request->date('to'));

        $applications = $q->latest()->paginate(25)->withQueryString();

        // Duplicate PayNow references (simple detection)
        $duplicateRefs = Application::query()
            ->selectRaw('paynow_reference, COUNT(*) as c')
            ->whereNotNull('paynow_reference')
            ->groupBy('paynow_reference')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('paynow_reference')
            ->toArray();

        return view('staff.auditor.payment_audit', ['applications' => $applications, 'duplicateRefs' => $duplicateRefs, 'mode' => 'paynow']);
    }

    public function proofs(Request $request)
    {
        $q = Application::query()->select([
            'id','reference','application_type','request_type','journalist_scope','status',
            'payment_status','payment_submission_method','payment_stage','proof_amount_paid',
            'payment_proof_path','payment_proof_uploaded_at','proof_status','created_at','updated_at',
        ])
            ->whereNotNull('payment_proof_path');

        if ($request->filled('status')) $q->where('proof_status', $request->string('status'));
        if ($request->filled('date_from')) $q->whereDate('payment_proof_uploaded_at', '>=', $request->date('date_from'));
        elseif ($request->filled('from')) $q->whereDate('payment_proof_uploaded_at', '>=', $request->date('from'));

        if ($request->filled('date_to')) $q->whereDate('payment_proof_uploaded_at', '<=', $request->date('date_to'));
        elseif ($request->filled('to')) $q->whereDate('payment_proof_uploaded_at', '<=', $request->date('to'));

        $applications = $q->latest('payment_proof_uploaded_at')->paginate(25)->withQueryString();
        return view('staff.auditor.payment_audit', ['applications' => $applications, 'duplicateRefs' => [], 'mode' => 'proofs']);
    }

    public function waivers(Request $request)
    {
        $q = Application::query()->select([
            'id','reference','application_type','request_type','journalist_scope','status',
            'payment_status','payment_submission_method','payment_stage','proof_amount_paid',
            'waiver_path','waiver_status','created_at','updated_at',
        ])
            ->whereNotNull('waiver_path');

        if ($request->filled('status')) $q->where('waiver_status', $request->string('status'));
        if ($request->filled('date_from')) $q->whereDate('updated_at', '>=', $request->date('date_from'));
        elseif ($request->filled('from')) $q->whereDate('updated_at', '>=', $request->date('from'));

        if ($request->filled('date_to')) $q->whereDate('updated_at', '<=', $request->date('date_to'));
        elseif ($request->filled('to')) $q->whereDate('updated_at', '<=', $request->date('to'));

        $applications = $q->latest('updated_at')->paginate(25)->withQueryString();
        return view('staff.auditor.payment_audit', ['applications' => $applications, 'duplicateRefs' => [], 'mode' => 'waivers']);
    }

    /** Immutable logs (full audit trail) */
    public function logs(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveAuditDateRange($request);
        $request->merge([
            'from' => $fromDate?->toDateString(),
            'to' => $toDate?->toDateString(),
        ]);

        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $source = (string) $request->input('source', 'current');
        if (!in_array($source, ['current', 'archived', 'all'], true)) {
            $source = 'current';
        }

        if ($source === 'archived') {
            $query = AuditLogArchive::query()->with(['actor']);
            $this->applyAuditLogFilters($query, $request);
            $logs = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
        } elseif ($source === 'all') {
            $currentQuery = AuditLog::query()->with(['actor']);
            $archiveQuery = AuditLogArchive::query()->with(['actor']);
            $this->applyAuditLogFilters($currentQuery, $request);
            $this->applyAuditLogFilters($archiveQuery, $request);

            $current = $currentQuery->orderByDesc('created_at')->limit(2000)->get()->map(function ($log) {
                $log->source = 'current';
                return $log;
            });
            $archived = $archiveQuery->orderByDesc('created_at')->limit(2000)->get()->map(function ($log) {
                $log->source = 'archived';
                return $log;
            });

            $combined = $current->concat($archived)->sortByDesc('created_at')->values();
            $page = LengthAwarePaginator::resolveCurrentPage();
            $slice = $combined->slice(($page - 1) * $perPage, $perPage)->values();
            $logs = new LengthAwarePaginator($slice, $combined->count(), $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        } else {
            $query = AuditLog::query()->with(['actor']);
            $this->applyAuditLogFilters($query, $request);
            $logs = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
        }

        return view('staff.auditor.logs', [
            'logs' => $logs,
            'q' => trim((string) $request->get('q')),
            'perPage' => $perPage,
            'source' => $source,
            'fromDate' => $fromDate?->toDateString(),
            'toDate' => $toDate?->toDateString(),
            'datePreset' => $request->input('date_preset', 'last_7_days'),
        ]);
    }

    /** Security oversight: focused view of security-related logs */
    public function security(Request $request)
    {
        $q = trim((string)$request->get('q'));
        $logs = AuditLog::query()
            ->where(function ($w) {
                $w->where('action', 'like', '%login%')
                  ->orWhere('action', 'like', '%otp%')
                  ->orWhere('action', 'like', '%2fa%')
                  ->orWhere('action', 'like', '%password%')
                  ->orWhere('action', 'like', '%role%')
                  ->orWhere('action', 'like', '%permission%');
            })
            ->when($q, fn($x) => $x->where('action', 'like', "%{$q}%")
                                  ->orWhere('ip', 'like', "%{$q}%")
                                  ->orWhere('user_agent', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('staff.auditor.security', compact('logs','q'));
    }

    /** Reports (CSV export) */
    public function reports(Request $request)
    {
        $from = ($request->date('date_from') ?? $request->date('from'))?->startOfDay();
        $to   = ($request->date('date_to') ?? $request->date('to'))?->endOfDay();

        $base = Application::query()->when($from, fn($q) => $q->where('created_at', '>=', $from))
                                     ->when($to, fn($q) => $q->where('created_at', '<=', $to));

        $stats = [
            'total' => (clone $base)->count(),
            'approved' => (clone $base)->whereIn('status', [
                Application::OFFICER_APPROVED,
                Application::REGISTRAR_APPROVED,
                Application::PAID_CONFIRMED,
                Application::PRODUCTION_QUEUE,
                Application::CARD_GENERATED,
                Application::CERT_GENERATED,
                Application::PRINTED,
                Application::ISSUED,
            ])->count(),
            'rejected' => (clone $base)->whereIn('status', [
                Application::OFFICER_REJECTED,
                Application::REGISTRAR_REJECTED,
            ])->count(),
            'waivers_approved' => (clone $base)->where('waiver_status','approved')->count(),
            'proofs_approved'  => (clone $base)->where('proof_status','approved')->count(),
            'paynow_confirmed' => (clone $base)->whereNotNull('paynow_confirmed_at')->count(),
            'registrations' => RegistrationRecord::query()->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to))->count(),
            'accreditations' => AccreditationRecord::query()->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to))->count(),
            'complaints' => Complaint::query()->where('type', 'complaint')->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to))->count(),
            'appeals' => Complaint::query()->where('type', 'appeal')->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to))->count(),
            'public_info_cases' => ComplianceCase::query()->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to))->count(),
            'audit_logs' => AuditLog::query()->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to))->count(),
            'security_events' => AuditLog::query()->where(function ($q) { $q->where('action', 'like', '%login%')->orWhere('action', 'like', '%password%')->orWhere('action', 'like', '%role%')->orWhere('action', 'like', '%permission%'); })->when($from, fn($q) => $q->where('created_at', '>=', $from))->when($to, fn($q) => $q->where('created_at', '<=', $to))->count(),
            'open_flags' => AuditFlag::query()->where(function ($q) { $q->whereNull('status')->orWhereNotIn('status', ['resolved', 'closed']); })->count(),
        ];

        return view('staff.auditor.reports', compact('from','to','stats'));
    }

    public function reportsCsv(Request $request): StreamedResponse
    {
        $from = ($request->date('date_from') ?? $request->date('from'))?->startOfDay();
        $to   = ($request->date('date_to') ?? $request->date('to'))?->endOfDay();

        $base = Application::query()->select([
                'id','reference','application_type','request_type','journalist_scope','status',
                'created_at','paynow_reference','payment_status','paynow_confirmed_at','proof_status','waiver_status',
            ])
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to))
            ->orderByDesc('created_at');

        $filename = 'audit_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($base) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Reference','Application Type','Request Type','Scope','Status','Created At',
                'PayNow Ref','Payment Status','PayNow Confirmed At','Proof Status','Waiver Status'
            ]);
            $base->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        $a->reference,
                        $a->application_type,
                        $a->request_type,
                        $a->journalist_scope,
                        $a->status,
                        optional($a->created_at)->format('Y-m-d H:i:s'),
                        $a->paynow_reference,
                        $a->payment_status,
                        optional($a->paynow_confirmed_at)->format('Y-m-d H:i:s'),
                        $a->proof_status,
                        $a->waiver_status,
                    ]);
                }
            });
            fclose($out);
        }, $filename);
    }

    /** Export User Logins as CSV */
    public function loginsCsv(Request $request): StreamedResponse
    {
        $q = $request->input('q');
        $from = $request->date('date_from') ?? $request->date('from');
        $to = $request->date('date_to') ?? $request->date('to');

        // Fetch all login-related audit logs (using AuditLog model, not ActivityLog)
        $logs = AuditLog::with('actor:id,name,email')
            ->where('action', 'like', '%login%')
            ->when($from, fn($x) => $x->whereDate('created_at', '>=', $from))
            ->when($to, fn($x) => $x->whereDate('created_at', '<=', $to))
            ->when($q, function ($query) use ($q) {
                $query->where('ip', 'like', "%{$q}%")
                    ->orWhere('action', 'like', "%{$q}%")
                    ->orWhereHas('actor', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    });
            })
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $filename = 'user_logins_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($out, ['Time', 'User', 'Email', 'Action', 'IP Address']);
            
            // Write data rows
            if ($logs->count() > 0) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                        $log->actor ? $log->actor->name : 'User #' . ($log->actor_user_id ?? 'Unknown'),
                        $log->actor ? $log->actor->email : '',
                        str_replace('_', ' ', $log->action ?? ''),
                        $log->ip ?? '',
                    ]);
                }
            } else {
                // Add a row indicating no data
                fputcsv($out, ['No login records found for the selected criteria', '', '', '', '']);
            }
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Export Applications Audit as CSV */
    public function applicationsCsv(Request $request): StreamedResponse
    {
        $type = $request->input('type');
        $status = $request->input('status');
        $from = $request->date('from');
        $to = $request->date('to');

        $applications = Application::query()
            ->when($type, fn($q) => $q->where('application_type', $type))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($from, fn($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to->endOfDay()))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $filename = 'applications_audit_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($applications) {
            $out = fopen('php://output', 'w');
            
            fputcsv($out, ['Reference', 'Type', 'Status', 'Created', 'Updated']);
            
            if ($applications->count() > 0) {
                foreach ($applications as $app) {
                    fputcsv($out, [
                        $app->reference ?? '',
                        $app->application_type ?? '',
                        $app->status ?? '',
                        $app->created_at ? $app->created_at->format('Y-m-d H:i:s') : '',
                        $app->updated_at ? $app->updated_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            } else {
                fputcsv($out, ['No applications found for the selected criteria', '', '', '', '']);
            }
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Export PayNow Payments as CSV */
    public function paynowCsv(Request $request): StreamedResponse
    {
        $q = $request->input('q');
        $from = $request->date('from');
        $to = $request->date('to');

        $payments = Application::query()
            ->whereNotNull('paynow_reference')
            ->when($q, fn($qb) => $qb->where(function($sub) use ($q) {
                $sub->where('paynow_reference', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%")
                    ;
            }))
            ->when($from, fn($qb) => $qb->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn($qb) => $qb->where('created_at', '<=', $to->endOfDay()))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $filename = 'paynow_payments_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            
            fputcsv($out, ['Reference', 'PayNow Ref', 'Status', 'Confirmed At', 'Created']);
            
            if ($payments->count() > 0) {
                foreach ($payments as $app) {
                    fputcsv($out, [
                        $app->reference ?? '',
                        $app->paynow_reference ?? '',
                        $app->payment_status ?? '',
                        $app->paynow_confirmed_at ? $app->paynow_confirmed_at->format('Y-m-d H:i:s') : '',
                        $app->created_at ? $app->created_at->format('Y-m-d H:i:s') : '',
                    ]);
                }
            } else {
                fputcsv($out, ['No PayNow payments found for the selected criteria', '', '', '', '']);
            }
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Export Audit Logs as CSV */
    public function logsCsv(Request $request): StreamedResponse
    {
        [$fromDate, $toDate] = $this->resolveAuditDateRange($request);
        $request->merge([
            'from' => $fromDate?->toDateString(),
            'to' => $toDate?->toDateString(),
        ]);

        $source = (string) $request->input('source', 'current');
        if (!in_array($source, ['current', 'archived', 'all'], true)) {
            $source = 'current';
        }

        $filename = 'audit_logs_' . now()->format('Ymd_His') . '.xls';
        $exportedBy = Auth::user()?->name ?? 'System';
        $filterSummary = $this->auditFilterSummary($request, $source, $fromDate?->toDateString(), $toDate?->toDateString());

        return response()->streamDownload(function () use ($request, $source, $exportedBy, $filterSummary) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['ZMC Audit Log Report']);
            fputcsv($out, ['Exported By', $exportedBy]);
            fputcsv($out, ['Exported At', now()->format('Y-m-d H:i:s')]);
            foreach ($filterSummary as [$label, $value]) {
                fputcsv($out, [$label, $value]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Log ID', 'Time', 'Actor', 'Role', 'Action', 'Module', 'Record Number', 'Model Type', 'Model ID', 'Status', 'IP Address', 'Device/Browser', 'Source']);

            $writers = [];
            if ($source === 'all' || $source === 'current') {
                $currentBuilder = AuditLog::query();
                $this->applyAuditLogFilters($currentBuilder, $request);
                $writers[] = [$currentBuilder, 'current'];
            }
            if ($source === 'all' || $source === 'archived') {
                $archiveBuilder = AuditLogArchive::query();
                $this->applyAuditLogFilters($archiveBuilder, $request);
                $writers[] = [$archiveBuilder, 'archived'];
            }

            foreach ($writers as [$builder, $src]) {
                $builder->with('actor:id,name,email')->orderByDesc('created_at')->chunk(500, function ($rows) use ($out, $src) {
                    foreach ($rows as $log) {
                        $meta = is_array($log->meta) ? $log->meta : [];
                        $recordNumber = $meta['record_number'] ?? $meta['reference'] ?? ($meta['application_reference'] ?? '');
                        $status = $meta['to_status'] ?? $meta['status'] ?? '';
                        fputcsv($out, [
                            $log->id,
                            optional($log->created_at)->format('Y-m-d H:i:s'),
                            $log->actor?->name ?? ('User #' . ($log->actor_user_id ?? 'System')),
                            $log->user_role ?? $meta['actor_role'] ?? '',
                            (string) $log->action,
                            $meta['module'] ?? '',
                            $recordNumber,
                            $log->model_type ? class_basename((string) $log->model_type) : '',
                            $log->model_id ?? '',
                            $status,
                            $log->ip ?? '',
                            $log->user_agent ?? '',
                            $src,
                        ]);
                    }
                });
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function logsPdf(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveAuditDateRange($request);
        $request->merge([
            'from' => $fromDate?->toDateString(),
            'to' => $toDate?->toDateString(),
        ]);

        $source = (string) $request->input('source', 'current');
        if (!in_array($source, ['current', 'archived', 'all'], true)) {
            $source = 'current';
        }

        if ($source === 'all') {
            $currentBuilder = AuditLog::query();
            $archiveBuilder = AuditLogArchive::query();
            $this->applyAuditLogFilters($currentBuilder, $request);
            $this->applyAuditLogFilters($archiveBuilder, $request);
            $logs = $currentBuilder->with('actor:id,name,email')->orderByDesc('created_at')->limit(750)->get()
                ->concat($archiveBuilder->with('actor:id,name,email')->orderByDesc('created_at')->limit(750)->get())
                ->sortByDesc('created_at')
                ->values();
        } else {
            $builder = $source === 'archived' ? AuditLogArchive::query() : AuditLog::query();
            $this->applyAuditLogFilters($builder, $request);
            $logs = $builder->with('actor:id,name,email')->orderByDesc('created_at')->limit(1500)->get();
        }

        $pdf = Pdf::loadView('staff.auditor.logs_pdf', [
            'logs' => $logs,
            'exportedBy' => Auth::user()?->name ?? 'System',
            'exportedAt' => now(),
            'filters' => $this->auditFilterSummary($request, $source, $fromDate?->toDateString(), $toDate?->toDateString()),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit_logs_' . now()->format('Ymd_His') . '.pdf');
    }

    private function resolveAuditDateRange(Request $request): array
    {
        $preset = (string) $request->input('date_preset', 'last_7_days');
        $today = now()->startOfDay();

        $from = $request->date('from') ?? $request->date('date_from');
        $to = $request->date('to') ?? $request->date('date_to');

        if ($from || $to) {
            return [$from ?? $today->copy()->subDays(6), $to ?? now()->endOfDay()];
        }

        return match ($preset) {
            'today' => [$today, now()->endOfDay()],
            'last_30_days' => [$today->copy()->subDays(29), now()->endOfDay()],
            'custom' => [$today->copy()->subDays(6), now()->endOfDay()],
            default => [$today->copy()->subDays(6), now()->endOfDay()],
        };
    }

    private function applyAuditLogFilters($query, Request $request): void
    {
        $q = trim((string) $request->input('q'));

        $query->when($request->filled('from'), fn($x) => $x->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn($x) => $x->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('user_id'), fn($x) => $x->where('actor_user_id', $request->integer('user_id')))
            ->when($request->filled('role'), fn($x) => $x->where('user_role', $request->input('role')))
            ->when($request->filled('office_branch'), fn($x) => $x->where('office_branch', 'like', '%' . $request->input('office_branch') . '%'))
            ->when($request->filled('module'), fn($x) => $x->where(function ($w) use ($request) {
                $module = $request->input('module');
                $w->where('model_type', 'like', '%' . $module . '%')
                    ->orWhere('meta', 'like', '%"module":"' . $module . '"%');
            }))
            ->when($request->filled('action_type'), fn($x) => $x->where('action', 'like', '%' . $request->input('action_type') . '%'))
            ->when($request->filled('application_type'), fn($x) => $x->where('meta', 'like', '%"application_type":"' . $request->input('application_type') . '"%'))
            ->when($request->filled('record_number'), fn($x) => $x->where('meta', 'like', '%'.$request->input('record_number').'%'))
            ->when($request->filled('status'), fn($x) => $x->where('meta', 'like', '%'.$request->input('status').'%'))
            ->when($request->filled('ip'), fn($x) => $x->where('ip', 'like', '%' . $request->input('ip') . '%'))
            ->when($request->filled('device'), fn($x) => $x->where('user_agent', 'like', '%' . $request->input('device') . '%'))
            ->when($q, function($sub) use ($q) {
                $sub->where(function ($inner) use ($q) {
                    $inner->where('action', 'like', "%{$q}%")
                        ->orWhere('model_type', 'like', "%{$q}%")
                        ->orWhere('model_id', 'like', "%{$q}%")
                        ->orWhere('ip', 'like', "%{$q}%")
                        ->orWhere('meta', 'like', "%{$q}%")
                        ->orWhereHas('actor', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            });
    }

    private function auditFilterSummary(Request $request, string $source, ?string $from, ?string $to): array
    {
        return [
            ['Source', ucfirst($source)],
            ['Date Range', ($from ?? 'N/A') . ' to ' . ($to ?? 'N/A')],
            ['Search', $request->input('q', '—')],
            ['User ID', $request->input('user_id', '—')],
            ['Role', $request->input('role', '—')],
            ['Office/Branch', $request->input('office_branch', '—')],
            ['Module', $request->input('module', '—')],
            ['Action Type', $request->input('action_type', '—')],
            ['Application Type', $request->input('application_type', '—')],
            ['Record Number', $request->input('record_number', '—')],
            ['Status', $request->input('status', '—')],
            ['IP', $request->input('ip', '—')],
            ['Device', $request->input('device', '—')],
        ];
    }

    /** Export Security Logs as CSV */
    public function securityCsv(Request $request): StreamedResponse
    {
        $q = $request->input('q');

        // Use AuditLog to match the security() method
        $logs = AuditLog::with('actor:id,name,email')
            ->where(function ($w) {
                $w->where('action', 'like', '%login%')
                  ->orWhere('action', 'like', '%otp%')
                  ->orWhere('action', 'like', '%2fa%')
                  ->orWhere('action', 'like', '%password%')
                  ->orWhere('action', 'like', '%role%')
                  ->orWhere('action', 'like', '%permission%');
            })
            ->when($q, fn($x) => $x->where('action', 'like', "%{$q}%")
                                  ->orWhere('ip', 'like', "%{$q}%")
                                  ->orWhere('user_agent', 'like', "%{$q}%"))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $filename = 'security_logs_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            
            fputcsv($out, ['Time', 'User', 'Action', 'IP Address', 'User Agent']);
            
            if ($logs->count() > 0) {
                foreach ($logs as $log) {
                    fputcsv($out, [
                        $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                        $log->actor ? $log->actor->name : 'User #' . ($log->actor_user_id ?? 'Unknown'),
                        str_replace('_', ' ', $log->action ?? ''),
                        $log->ip ?? '',
                        $log->user_agent ?? '',
                    ]);
                }
            } else {
                fputcsv($out, ['No security logs found for the selected criteria', '', '', '', '']);
            }
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Export Activity Feed as CSV */
    public function activityCsv(Request $request): StreamedResponse
    {
        // Use ActivityLog with correct 'user' relationship (not 'actor')
        $logs = ActivityLog::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(1000)
            ->get();

        $filename = 'activity_feed_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            
            fputcsv($out, ['Time', 'User', 'Role', 'Action', 'Reference', 'From Status', 'To Status']);
            
            if ($logs->count() > 0) {
                foreach ($logs as $log) {
                    $ref = '';
                    try {
                        if ($log->entity) {
                            $ref = $log->entity->reference ?? '';
                        }
                    } catch (\Throwable $e) {}
                    
                    if (!$ref && $log->entity_type && $log->entity_id) {
                        $ref = class_basename($log->entity_type) . '-' . $log->entity_id;
                    }

                    fputcsv($out, [
                        $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                        $log->user ? $log->user->name : 'User #' . ($log->user_id ?? 'Unknown'),
                        $log->user_role ?? '',
                        str_replace('_', ' ', $log->action ?? ''),
                        $ref,
                        $log->from_status ?? '',
                        $log->to_status ?? '',
                    ]);
                }
            } else {
                fputcsv($out, ['No activity found', '', '', '', '', '', '']);
            }
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /** Flag an anomaly (Auditor-only) */
    public function flag(Request $request)
    {
        $data = $request->validate([
            'entity_type' => ['required','string','max:80'],
            'entity_id'   => ['required','integer'],
            'severity'    => ['nullable','in:low,medium,high'],
            'reason'      => ['required','string','max:5000'],
        ]);

        $flag = AuditFlag::create([
            'auditor_user_id' => Auth::id(),
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'severity' => $data['severity'] ?? 'medium',
            'reason' => $data['reason'],
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'auditor_flagged_anomaly',
            'model_type' => $data['entity_type'],
            'model_id' => $data['entity_id'],
            'meta' => [
                'severity' => $flag->severity,
                'reason' => $flag->reason,
                'flag_id' => $flag->id,
            ],
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 500),
        ]);

        return back()->with('success', 'Anomaly flagged.');
    }

    /** Consolidated payment transactions, proofs, waivers and reconciliation exceptions. */
    public function reconciliation(Request $request)
    {
        $q = Application::query()->select([
            'id','reference','application_type','request_type','status','payment_status',
            'payment_submission_method','payment_stage','proof_amount_paid','paynow_reference',
            'paynow_confirmed_at','payment_proof_path','payment_proof_uploaded_at','proof_status',
            'waiver_path','waiver_status','created_at','updated_at',
        ])->where(function ($w) {
            $w->whereNotNull('paynow_reference')->orWhereNotNull('payment_proof_path')->orWhereNotNull('waiver_path');
        });
        if ($request->filled('method')) $q->where('payment_submission_method', $request->string('method'));
        if ($request->filled('status')) $q->where('payment_status', $request->string('status'));
        if ($request->filled('from')) $q->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $q->whereDate('created_at', '<=', $request->date('to'));
        $transactions = $q->latest()->paginate(25)->withQueryString();
        $stats = [
            'transactions' => (clone $q)->count(),
            'paynow' => (clone $q)->whereNotNull('paynow_confirmed_at')->count(),
            'proofs' => (clone $q)->where('proof_status', 'approved')->count(),
            'waivers' => (clone $q)->where('waiver_status', 'approved')->count(),
            'exceptions' => (clone $q)->where(function ($w) {
                $w->where(function ($x) { $x->whereIn('payment_status', ['success','paid'])->whereNull('paynow_confirmed_at'); })
                  ->orWhere(function ($x) { $x->where('proof_status', 'approved')->whereNotIn('payment_status', ['paid','success']); });
            })->count(),
            'appeal_payments' => Complaint::query()->where('type', 'appeal')->where(function ($w) { $w->whereNotNull('payment_ref')->orWhereNotNull('payment_proof_path'); })->count(),
        ];
        $appealPayments = Complaint::query()
            ->select(['id','reference_number','payment_ref','payment_status','payment_currency','payment_amount','payment_proof_path','payment_approved_at','created_at'])
            ->where('type', 'appeal')
            ->where(function ($w) { $w->whereNotNull('payment_ref')->orWhereNotNull('payment_proof_path'); })
            ->latest()->limit(100)->get();
        return view('staff.auditor.reconciliation', compact('transactions','stats','appealPayments'));
    }

    /** Findings register with unit assignment and corrective-action tracking. */
    public function flags(Request $request)
    {
        $flags = AuditFlag::query()->with('auditor:id,name')
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('unit'), fn($q) => $q->where('assigned_unit', $request->string('unit')))
            ->latest()->paginate(25)->withQueryString();
        $stats = [
            'total' => AuditFlag::count(),
            'open' => AuditFlag::where(function ($q) { $q->whereNull('status')->orWhereIn('status', ['open','under_review','awaiting_response','corrective_action']); })->count(),
            'resolved' => AuditFlag::whereIn('status', ['resolved','closed'])->count(),
            'high' => AuditFlag::where('severity', 'high')->whereNotIn('status', ['resolved','closed'])->count(),
        ];
        $units = ['Accreditation','Registration','Complaints','Appeals','Finance / Accounts','Production','IT / Security','Public Information'];
        return view('staff.auditor.flags', compact('flags','stats','units'));
    }

    public function updateFlag(Request $request, AuditFlag $flag)
    {
        $data = $request->validate([
            'assigned_unit' => ['nullable','string','max:120'],
            'status' => ['required','in:open,under_review,awaiting_response,corrective_action,resolved,closed'],
            'response_notes' => ['nullable','string','max:10000'],
            'corrective_action' => ['nullable','string','max:10000'],
            'due_at' => ['nullable','date'],
            'attachment' => ['nullable','file','max:10240','mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('audit-findings', 'public');
            $data['attachment_original_name'] = $file->getClientOriginalName();
        }
        if (in_array($data['status'], ['resolved','closed'], true)) {
            $data['resolved_by'] = Auth::id();
            $data['resolved_at'] = now();
        } else {
            $data['resolved_by'] = null;
            $data['resolved_at'] = null;
        }
        unset($data['attachment']);
        $flag->update($data);
        AuditLog::create([
            'actor_user_id' => Auth::id(), 'action' => 'auditor_updated_finding',
            'model_type' => 'audit_flag', 'model_id' => $flag->id,
            'meta' => ['status' => $flag->status, 'assigned_unit' => $flag->assigned_unit],
            'ip' => $request->ip(), 'user_agent' => substr((string)$request->userAgent(), 0, 500),
        ]);
        return back()->with('success', 'Finding updated.');
    }

    public function proofsCsv(Request $request): StreamedResponse
    {
        $status = $request->input('status');
        $from = $request->date('date_from') ?? $request->date('from');
        $to = $request->date('date_to') ?? $request->date('to');

        $applications = Application::query()
            ->whereNotNull('payment_proof_path')
            ->when($status, fn($q) => $q->where('proof_status', $status))
            ->when($from, fn($q) => $q->whereDate('payment_proof_uploaded_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('payment_proof_uploaded_at', '<=', $to))
            ->orderByDesc('payment_proof_uploaded_at')
            ->limit(5000)
            ->get();

        $filename = 'payment_proofs_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($applications) {
            $out = fopen('php://output', 'w');
            
            fputcsv($out, ['Reference', 'Proof Status', 'Uploaded At', 'Payment Status']);
            
            if ($applications->count() > 0) {
                foreach ($applications as $app) {
                    fputcsv($out, [
                        $app->reference ?? '',
                        $app->proof_status ?? '',
                        $app->payment_proof_uploaded_at ? $app->payment_proof_uploaded_at->format('Y-m-d H:i:s') : '',
                        $app->payment_status ?? '',
                    ]);
                }
            } else {
                fputcsv($out, ['No payment proofs found for the selected criteria', '', '', '']);
            }
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function waiversCsv(Request $request): StreamedResponse
    {
        $status = $request->input('status');
        $from = $request->date('date_from') ?? $request->date('from');
        $to = $request->date('date_to') ?? $request->date('to');

        $applications = Application::query()
            ->whereNotNull('waiver_path')
            ->when($status, fn($q) => $q->where('waiver_status', $status))
            ->when($from, fn($q) => $q->whereDate('updated_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('updated_at', '<=', $to))
            ->orderByDesc('updated_at')
            ->limit(5000)
            ->get();

        $filename = 'waivers_exemptions_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($applications) {
            $out = fopen('php://output', 'w');
            
            fputcsv($out, ['Reference', 'Waiver Status', 'Updated At', 'Payment Status']);
            
            if ($applications->count() > 0) {
                foreach ($applications as $app) {
                    fputcsv($out, [
                        $app->reference ?? '',
                        $app->waiver_status ?? '',
                        $app->updated_at ? $app->updated_at->format('Y-m-d H:i:s') : '',
                        $app->payment_status ?? '',
                    ]);
                }
            } else {
                fputcsv($out, ['No waivers found for the selected criteria', '', '', '']);
            }
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * View complaints and appeals (Auditor oversight access)
     */
    public function complaints()
    {
        // Oversight view intentionally excludes names, contact details, messages and internal notes.
        $complaints = Complaint::query()
            ->select(['id','reference_number','type','category_id','status','priority','response_due_at','created_at','handled_at'])
            ->with('category:id,name')
            ->orderByDesc('created_at')->paginate(15);
        
        $stats = [
            'total' => Complaint::count(),
            'pending' => Complaint::whereNotIn('status', ['resolved','closed'])->count(),
            'resolved' => Complaint::whereIn('status', ['resolved','closed'])->count(),
            'complaints' => Complaint::where('type', 'complaint')->count(),
            'appeals' => Complaint::where('type', 'appeal')->count(),
        ];
        
        return view('staff.auditor.complaints_overview', compact('complaints', 'stats'));
    }

    public function comments(Request $request)
    {
        $query = \App\Models\ApplicationComment::query()
            ->with(['application', 'user'])
            ->latest();

        if ($request->filled('role')) {
            $query->where('user_role', $request->role);
        }

        if ($request->filled('type')) {
            $query->where('comment_type', $request->type);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(body) LIKE ?', ['%' . strtolower($s) . '%'])
                  ->orWhereHas('application', function ($aq) use ($s) {
                      $aq->whereRaw('LOWER(reference) LIKE ?', ['%' . strtolower($s) . '%']);
                  })
                  ->orWhereHas('user', function ($uq) use ($s) {
                      $uq->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($s) . '%']);
                  });
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $comments = $query->paginate(25)->withQueryString();

        $stats = [
            'total'     => \App\Models\ApplicationComment::count(),
            'today'     => \App\Models\ApplicationComment::whereDate('created_at', today())->count(),
            'by_role'   => \App\Models\ApplicationComment::selectRaw('user_role, count(*) as cnt')
                             ->groupBy('user_role')
                             ->pluck('cnt', 'user_role')
                             ->toArray(),
            'by_type'   => \App\Models\ApplicationComment::selectRaw('comment_type, count(*) as cnt')
                             ->groupBy('comment_type')
                             ->pluck('cnt', 'comment_type')
                             ->toArray(),
        ];

        $roles = \App\Models\ApplicationComment::distinct()->pluck('user_role')->sort()->values();
        $types = \App\Models\ApplicationComment::commentTypes();

        return view('staff.auditor.comments', compact('comments', 'stats', 'roles', 'types'));
    }

    public function commentsCsv(Request $request): StreamedResponse
    {
        $query = \App\Models\ApplicationComment::query()
            ->with(['application', 'user'])
            ->latest();

        if ($request->filled('role')) {
            $query->where('user_role', $request->role);
        }
        if ($request->filled('type')) {
            $query->where('comment_type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Application Ref', 'Commenter', 'Role', 'Type', 'Visibility', 'Body', 'App Status At Time', 'IP', 'Created At']);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $c) {
                    fputcsv($handle, [
                        $c->id,
                        $c->application?->reference ?? '-',
                        $c->user?->name ?? '-',
                        $c->user_role,
                        $c->comment_type,
                        $c->visibility,
                        $c->body,
                        $c->application_status_at_time,
                        $c->ip_address,
                        $c->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 'application_comments_' . now()->format('Ymd_His') . '.csv');
    }
}
