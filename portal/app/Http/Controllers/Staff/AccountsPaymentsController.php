<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ApplicationWorkflow;
use App\Services\ApplicationWorkflowService;
use App\Services\PaymentWorkflowService;
use App\Services\ActivityLogger;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\Refund;
use App\Http\Controllers\Portal\PaynowController;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;

class AccountsPaymentsController extends Controller
{
    public function rbzRate()
    {
        $rateSettings = Schema::hasTable('system_configs') ? SystemConfig::getValue('rbz_exchange_rate_zwg', []) : [];
        return view('staff.accounts.rbz_rate', compact('rateSettings'));
    }

    public function updateRbzRate(Request $request)
    {
        abort_unless(Schema::hasTable('system_configs'), 503, 'System configuration storage is not available. Apply the system configuration migration first.');
        $data = $request->validate([
            'rate' => ['required', 'numeric', 'gt:0', 'max:1000000000'],
            'effective_date' => ['required', 'date'],
        ]);

        $settings = [
            'rate' => (float) $data['rate'],
            'effective_date' => $data['effective_date'],
            'updated_by' => Auth::id(),
        ];
        SystemConfig::setValue('rbz_exchange_rate_zwg', $settings);
        \App\Support\AuditTrail::log('rbz_exchange_rate_updated', null, [
            'rate' => $settings['rate'],
            'effective_date' => $settings['effective_date'],
        ]);

        return back()->with('success', 'RBZ exchange rate saved and will apply to new ZWG payments.');
    }

    /**
     * 1) Payments Listing & Verification
     */
    public function index(Request $request)
    {
        $query = Payment::query()->with(['application.applicant', 'payer']);

        // Multi-level filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->whereIn('status', (array) $request->input('status'));
        }
        if ($request->filled('method')) {
            $query->whereIn('method', (array) $request->input('method'));
        }
        if ($request->filled('applicant_category')) {
            $query->whereIn('applicant_category', (array) $request->input('applicant_category'));
        }
        if ($request->filled('service_type')) {
            $query->whereIn('service_type', (array) $request->input('service_type'));
        }
        if ($request->filled('residency')) {
            $query->whereIn('residency', (array) $request->input('residency'));
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('application.applicant', function($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('staff.accounts.payments.index', compact('payments'));
    }

    /**
     * Retry failed confirmations (gateway callback issues)
     */
    public function retryPaymentStatus(Payment $payment)
    {
        if ($payment->method !== 'paynow' || !$payment->poll_url) {
            return back()->with('error', 'Only Paynow payments with poll URL can be retried.');
        }

        // Use the logic from PaynowController if possible or implement here
        $paynowController = app(PaynowController::class);
        // We might need to mock or adjust checkStatus to work with Payment model
        // For now, let's assume checkStatus can handle it or we implement a service

        // Simplified retry logic (logic placeholder)
        $payment->update([
            'last_checked_at' => now(),
        ]);

        return back()->with('success', 'Payment status updated from gateway.');
    }

    /**
     * 2) Offline Payments Handling
     */
    public function createOffline()
    {
        $applications = Application::whereIn('status', [Application::ACCOUNTS_REVIEW])->get();
        return view('staff.accounts.payments.offline_create', compact('applications'));
    }

    public function storeOffline(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string', // bank_transfer, cash, pos
            'reference' => 'required|string|unique:payments,reference',
            'bank_name' => 'nullable|string',
            'deposit_slip_ref' => 'nullable|string',
            'proof_file' => 'nullable|file|extensions:pdf,jpg,png|max:2048',
            'payer_name' => 'nullable|string',
        ]);

        $application = Application::findOrFail($validated['application_id']);

        $payment = Payment::create([
            'application_id' => $application->id,
            'payer_user_id' => $application->user_id,
            'method' => $validated['method'],
            'source' => 'offline',
            'amount' => $validated['amount'],
            'currency' => 'USD', // Default or from config
            'reference' => $validated['reference'],
            'status' => 'paid',
            'bank_name' => $validated['bank_name'],
            'deposit_slip_ref' => $validated['deposit_slip_ref'],
            'confirmed_at' => now(),
            'applicant_category' => $application->category_code, // Assuming this is set
            'service_type' => $application->application_type,
            'residency' => $application->residency_type ?? 'local',
        ]);

        if ($request->hasFile('proof_file')) {
            $path = $this->storePublicFile($request->file('proof_file'), 'payment_proofs', FileType::safeHashName($request->file('proof_file')));
            $payment->update(['proof_file_path' => $path]);
        }

        $this->logPaymentAction($payment, 'created', null, 'paid', 'Offline payment recorded.');

        // Transition application if Paid
        if ($payment->status === 'paid') {
            ApplicationWorkflow::transition($application, Application::PAID_CONFIRMED, 'Payment Confirmed (Offline)');
        }

        return redirect()->route('staff.accounts.payments.index')->with('success', 'Offline payment recorded.');
    }

    /**
     * 3) Reversals, Refunds, and Adjustments
     */
    public function reverse(Request $request, Payment $payment)
    {
        $request->validate(['reason' => 'required|string']);

        DB::transaction(function() use ($payment, $request) {
            $oldStatus = $payment->status;
            $payment->update([
                'status' => 'reversed',
                'reversal_reason' => $request->reason
            ]);

            $this->logPaymentAction($payment, 'reversed', $oldStatus, 'reversed', $request->reason);

            // If it was linked to an application that was confirmed, maybe move it back?
            // This depends on business logic.
        });

        return back()->with('success', 'Payment reversed.');
    }

    public function initiateRefund(Request $request, Payment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|max:' . $payment->amount,
            'reason' => 'required|string'
        ]);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => $request->amount,
            'reason' => $request->reason,
            'processed_by' => Auth::id(),
            'status' => 'pending'
        ]);

        return back()->with('success', 'Refund initiated and pending approval.');
    }

    public function approveRefund(Refund $refund)
    {
        DB::transaction(function() use ($refund) {
            $refund->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);

            $payment = $refund->payment;
            $oldStatus = $payment->status;
            $newStatus = ($refund->amount == $payment->amount) ? 'refunded' : 'partially_refunded';

            $payment->update(['status' => $newStatus]);
            $this->logPaymentAction($payment, 'refunded', $oldStatus, $newStatus, "Refund approved. Amount: {$refund->amount}");
        });

        return back()->with('success', 'Refund approved.');
    }

    /**
     * 4) Ledger Generation
     */
    public function ledger(Request $request)
    {
        $query = Payment::query()->with(['application.applicant']);

        // Apply filters (reusing logic or common scope)
        $this->applyFilters($query, $request);

        $payments = $query->latest()->get();

        $summary = [
            'total_transactions' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'total_paid' => $payments->where('status', 'paid')->sum('amount'),
            'total_pending' => $payments->where('status', 'pending')->sum('amount'),
            'total_refunded' => $payments->whereIn('status', ['refunded', 'partially_refunded'])->sum('amount'), // simplified
            'outstanding_balance' => $payments->where('status', 'pending')->sum('amount'),
        ];

        return view('staff.accounts.ledger', compact('payments', 'summary'));
    }

    protected function applyFilters($query, Request $request)
    {
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('status')) $query->whereIn('status', (array)$request->status);
        if ($request->filled('applicant_category')) $query->whereIn('applicant_category', (array)$request->applicant_category);
        if ($request->filled('service_type')) $query->whereIn('service_type', (array)$request->service_type);
        if ($request->filled('residency')) $query->whereIn('residency', (array)$request->residency);
    }

    /**
     * Financial Reporting (Accounts) — With Graphs
     */
    public function reportFinancial(Request $request)
    {
        $query = Payment::query();
        $this->applyFilters($query, $request);

        $payments = $query->get();

        // KPI Cards
        $stats = [
            'total_revenue' => $payments->where('status', 'paid')->sum('amount'),
            'total_transactions' => $payments->count(),
            'paid_total' => $payments->where('status', 'paid')->sum('amount'),
            'pending_total' => $payments->where('status', 'pending')->sum('amount'),
            'refunded_total' => $payments->whereIn('status', ['refunded', 'partially_refunded'])->sum('amount'),
            'outstanding_balance' => $payments->where('status', 'pending')->sum('amount'),
        ];

        // Graphs Data (Line Chart - Revenue Trend)
        $revenueTrend = $payments->where('status', 'paid')
            ->groupBy(function($d) { return $d->created_at->format('Y-m-d'); })
            ->map(function($row) { return $row->sum('amount'); });

        // Transactions Volume (Bar Chart)
        $transVolume = $payments->groupBy(function($d) { return $d->created_at->format('Y-m-d'); })
            ->map(function($row) { return $row->count(); });

        // Payment Status Breakdown (Pie)
        $statusBreakdown = $payments->groupBy('status')
            ->map(function($row) { return ['count' => $row->count(), 'amount' => $row->sum('amount')]; });

        // Revenue by Service Type (Bar)
        $revenueByService = $payments->where('status', 'paid')->groupBy('service_type')
            ->map(function($row) { return $row->sum('amount'); });

        // Revenue by Applicant Category (Bar)
        $revenueByCategory = $payments->where('status', 'paid')->groupBy('applicant_category')
            ->map(function($row) { return $row->sum('amount'); });

        // Local vs Foreigner (Bar)
        $revenueByResidency = $payments->where('status', 'paid')->groupBy('residency')
            ->map(function($row) { return $row->sum('amount'); });

        return view('staff.accounts.reports.financial', compact(
            'stats', 'revenueTrend', 'transVolume', 'statusBreakdown',
            'revenueByService', 'revenueByCategory', 'revenueByResidency'
        ));
    }

    /** Helper for logging */
    protected function logPaymentAction(Payment $payment, $action, $from, $to, $comment = null)
    {
        PaymentAuditLog::create([
            'payment_id' => $payment->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'ip_address' => request()->ip()
        ]);
    }

    /* =========================
     * Payments dashboard modules
     * ========================= */

    /**
     * Main landing dashboard for Accounts/Payments
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $year = (int) $request->input('year', now()->year);
        $year = max(now()->year - 10, min(now()->year, $year));

        $paidStatuses = [
            Application::PAID_CONFIRMED,
            Application::PRODUCTION_QUEUE,
            Application::PRODUCED_READY,
            Application::PRINTED,
            Application::ISSUED,
            Application::PAYMENT_VERIFIED,
            Application::SUBMITTED,
        ];
        
        // NOTE: Accounts is a shared queue. We must NOT filter by `assigned_officer_id`
        // (that field belongs to the Accreditation Officer who reviewed the file earlier).
        // Filtering by it would hide every application the officer claimed, even after
        // the Registrar approved & forwarded to Accounts.
        $query = Application::query()
            ->with('applicant', 'paymentSubmissions', 'documents')
            ->whereIn('status', [
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::RETURNED_TO_ACCOUNTS,
                Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
                Application::REGISTRAR_APPROVED,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            ]);
        // NOTE: We intentionally do NOT filter the Accounts overview by
        // `locked_*`. The lock is a soft per-user concurrency aid used inside
        // the Officer/Registrar stages, but Accounts is a shared queue: every
        // accounts officer must see every payment that has reached this stage,
        // including registrar-approved files that may still carry stale locks
        // from earlier reviewers. Concurrency on the actual confirm-paid
        // action is protected by the workflow validator (status transitions
        // are idempotent — a second mark-paid call on a PRODUCTION_QUEUE row
        // is rejected by StatusTransitionValidator).

        if ((int)$year !== now()->year) {
            $query->whereYear('created_at', $year);
        }

        if (request()->filled('submission_method')) {
            $query->where('payment_submission_method', request('submission_method'));
        }

        $applications = $query->latest()->paginate(20)->withQueryString();

        $pendingStatuses = [
            Application::ACCOUNTS_REVIEW,
            Application::AWAITING_ACCOUNTS_VERIFICATION,
            Application::RETURNED_TO_ACCOUNTS,
            Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
        ];

        $kpis = [
            'total_pending' => Application::whereIn('status', $pendingStatuses)->count(),
            'summary_paid' => Application::whereIn('status', $paidStatuses)->count(),
            'special_cases' => Application::where('status', Application::PENDING_ACCOUNTS_FROM_REGISTRAR)->count(),
            'paynow_submissions' => Application::whereIn('status', $pendingStatuses)->where('payment_submission_method', 'paynow_reference')->count(),
            'proof_submissions' => Application::whereIn('status', $pendingStatuses)->where('payment_submission_method', 'proof_upload')->count(),
            'waiver_submissions' => Application::whereIn('status', $pendingStatuses)->where('payment_submission_method', 'waiver')->count(),
            'no_submission' => Application::whereIn('status', $pendingStatuses)->whereNull('payment_submission_method')->count(),
            'verified_today' => Application::where('status', Application::PAYMENT_VERIFIED)->where('last_action_at', '>=', now()->startOfDay())->count(),
            'rejected_today' => Application::where('status', Application::PAYMENT_REJECTED)->where('last_action_at', '>=', now()->startOfDay())->count(),
        ];

        $currentYear = (int)now()->year;
        $availableYears = range($currentYear, $currentYear - 3);

        $isCurrentYear = ((int)$year === $currentYear);
        $from = $isCurrentYear ? now()->subDays(29)->startOfDay() : Carbon::create($year)->startOfYear();
        $to   = $isCurrentYear ? now()->endOfDay()                : Carbon::create($year)->endOfYear();

        $analyticsTotals = [
            'public_users'  => User::where('account_type', 'public')->count(),
            'staff_users'   => User::where('account_type', '!=', 'public')->count(),
            'accreditation' => Application::where('application_type', 'accreditation')->whereBetween('created_at', [$from, $to])->count(),
            'registration'  => Application::where('application_type', 'registration')->whereBetween('created_at', [$from, $to])->count(),
        ];

        $days   = $isCurrentYear ? 30 : $from->diffInDays($to) + 1;
        $labels = collect(range(0, $days - 1))
            ->map(fn($i) => $from->copy()->addDays($i)->format('Y-m-d'))
            ->values();

        $dailyApplications = Application::selectRaw("date(created_at) as d, application_type, count(*) as c")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d', 'application_type')
            ->orderBy('d')
            ->get();

        $dailyPublicUsers = User::selectRaw("date(created_at) as d, count(*) as c")
            ->where('account_type', '=', 'public')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $statusBreakdown = Application::selectRaw('status, count(*) as c')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->get();

        $appMap         = $dailyApplications->groupBy('d')->map(fn($rows) => $rows->pluck('c', 'application_type')->toArray())->toArray();
        $publicUsersMap = $dailyPublicUsers->pluck('c', 'd')->toArray();

        $trendData = [
            'labels'   => $labels->map(fn($d) => Carbon::parse($d)->format('M d'))->values()->all(),
            'datasets' => [
                'accreditation' => $labels->map(fn($d) => (int)($appMap[$d]['accreditation'] ?? 0))->values()->all(),
                'registration'  => $labels->map(fn($d) => (int)($appMap[$d]['registration'] ?? 0))->values()->all(),
                'public_users'  => $labels->map(fn($d) => (int)($publicUsersMap[$d] ?? 0))->values()->all(),
            ],
            'status_breakdown' => [
                'labels' => $statusBreakdown->pluck('status')->map(fn($s) => ucfirst(str_replace('_', ' ', $s ?? 'unknown')))->values()->all(),
                'counts' => $statusBreakdown->pluck('c')->map(fn($n) => (int) $n)->values()->all(),
            ],
        ];

        $analyticsChartData = $trendData;

        $recentPaidApplications = Application::whereIn('status', $paidStatuses)
            ->whereNotNull('receipt_number')
            ->with(['applicant', 'payments' => function ($q) {
                $q->where('status', 'paid')->with('recorder')->latest('confirmed_at');
            }])
            ->latest('last_action_at')
            ->limit(10)
            ->get();

        return view('staff.accounts.dashboard', compact('applications', 'kpis', 'year', 'availableYears', 'analyticsTotals', 'trendData', 'analyticsChartData', 'recentPaidApplications', 'paidStatuses'));
    }

    /**
     * Placeholder/Stub for manual reconciliation marking
     */
    public function markReconciled(Request $request)
    {
        $request->validate([
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'exists:payments,id',
        ]);

        \DB::table('payments')
            ->whereIn('id', $request->payment_ids)
            ->update([
                'reconciled' => true,
                'reconciled_at' => now(),
                'reconciled_by' => auth()->id(),
            ]);

        return back()->with('success', 'Selected payments marked as reconciled.');
    }

    /**
     * 1) PayNow Transactions (application-linked feed)
     * NOTE: Until a dedicated PayNow transactions table/webhook exists, this view is built from
     * application payment fields (paynow_reference, payment_status, paynow_confirmed_at, etc.).
     */
    public function paynowTransactions(Request $request)
    {
        $q = Application::query()->with('applicant');

        // show applicants who have paid using paynow
        $q->where('payment_status', 'paid');
        $q->where(function($w) {
            $w->whereNotNull('paynow_reference')
              ->orWhereNotNull('paynow_poll_url');
        });

        // basic filters
        if ($request->filled('status')) {
            $q->where('payment_status', $request->string('status'));
        }
        if ($request->filled('type')) {
            $q->where('application_type', $request->string('type'));
        }
        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->date('to'));
        }
        if ($request->filled('amount')) {
            // amount is not yet stored per application; keep as UI filter placeholder.
        }

        $applications = $q
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('staff.accounts.paynow_transactions', compact('applications'));
    }

    /** 2) Payment Proofs: Pending */
    public function proofsPending()
    {
        $applications = Application::query()
            ->with(['applicant','documents'])
            ->where('proof_status', 'submitted')
            // Only show if approved for payment or submitted for verification
            ->whereIn('status', [
                Application::ACCOUNTS_REVIEW, 
                Application::RETURNED_TO_ACCOUNTS,
                Application::AWAITING_ACCOUNTS_VERIFICATION
            ])
            ->latest('payment_proof_uploaded_at')
            ->paginate(20);

        return view('staff.accounts.proofs_pending', compact('applications'));
    }

    public function appFeeVerifications()
    {
        $applications = Application::query()
            ->with(['applicant','documents'])
            ->where('status', Application::SUBMITTED_WITH_APP_FEE)
            ->latest('updated_at')
            ->paginate(20);

        return view('staff.accounts.app_fee_verifications', compact('applications'));
    }

    public function regFeeVerifications()
    {
        $applications = Application::query()
            ->with(['applicant','documents','paymentSubmissions'])
            ->where(function ($query) {
                $query->where('status', Application::REG_FEE_PROOF_UPLOADED)
                    ->orWhere(function ($q) {
                        $q->whereIn('status', [
                            Application::AWAITING_ACCOUNTS_VERIFICATION,
                            Application::ACCOUNTS_REVIEW,
                            Application::RETURNED_TO_ACCOUNTS,
                        ])->whereHas('paymentSubmissions', function ($submission) {
                            $submission->where('payment_stage', 'registration_fee')
                                ->whereIn('status', ['submitted', 'pending']);
                        });
                    });
            })
            ->latest('updated_at')
            ->paginate(20);

        return view('staff.accounts.reg_fee_verifications', compact('applications'));
    }

    public function bulkDownloadProofs(Request $request)
    {
        $ids = $request->input('application_ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No applications selected.');
        }

        $applications = Application::whereIn('id', $ids)->with('applicant')->get();
        if ($applications->isEmpty()) {
            return back()->with('error', 'No applications found.');
        }

        $zip = new ZipArchive();
        $zipFileName = 'Payment_Proofs_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/public/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($applications as $app) {
                $proofPath = $app->payment_proof_path;
                if (!$proofPath) {
                    $proofDoc = $app->documents?->firstWhere('doc_type', 'proof_of_payment');
                    $proofPath = $proofDoc?->file_path;
                }

                if ($proofPath && Storage::disk('public')->exists($proofPath)) {
                    $extension = pathinfo($proofPath, PATHINFO_EXTENSION);
                    $applicantName = Str::slug($app->applicant?->name ?? 'applicant');
                    $newFileName = $app->reference . '_' . $applicantName . '.' . $extension;
                    $zip->addFile(Storage::disk('public')->path($proofPath), $newFileName);
                }
            }
            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Could not create ZIP file.');
    }

    /** 2) Payment Proofs: Approved */
    public function proofsApproved()
    {
        $applications = Application::query()
            ->with(['applicant','documents'])
            ->where('proof_status', 'approved')
            // Only show if approved for payment or submitted for verification
            ->whereIn('status', [
                Application::ACCOUNTS_REVIEW, 
                Application::RETURNED_TO_ACCOUNTS, 
                Application::PAID_CONFIRMED,
                Application::AWAITING_ACCOUNTS_VERIFICATION
            ])
            ->latest('proof_reviewed_at')
            ->paginate(20);

        return view('staff.accounts.proofs_approved', compact('applications'));
    }

    /** Approve a payment proof */
    public function approveProof(Request $request, Application $application)
    {
        $data = $request->validate([
            'proof_review_notes' => ['nullable', 'string', 'max:5000'],
            'paynow_reference'   => ['nullable', 'string', 'max:200'],
            'payment_status'     => ['nullable', 'string', 'max:100'],
        ]);

        $from = $application->status;
        $processed = false;

        DB::transaction(function() use ($application, $data, &$from, &$processed) {
            // Row-level lock + idempotency guard. The shared Accounts queue
            // means two officers can race on Approve Proof; serialize them
            // and short-circuit if the file has already been advanced.
            $fresh = Application::whereKey($application->id)->lockForUpdate()->first();
            if ($fresh) {
                $application->setRawAttributes($fresh->getAttributes(), true);
                $from = $application->status;
            }

            $accountsActionable = [
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::RETURNED_TO_ACCOUNTS,
                Application::REGISTRAR_APPROVED,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            ];
            if (!in_array($application->status, $accountsActionable, true)) {
                return;
            }
            $processed = true;

            // The same Accounts approval action handles both payment stages.
            // Read the pending submission so a registration-fee approval is
            // handed to Production, while an initial application-fee approval
            // returns the media house to the officer review queue.
            $pendingSubmission = \App\Models\PaymentSubmission::where('application_id', $application->id)
                ->whereIn('status', ['submitted', 'pending'])
                ->latest('submitted_at')
                ->first();
            $paymentStage = $pendingSubmission?->payment_stage;
            if (!$paymentStage) {
                $paymentStage = ($application->application_type === 'registration' && (
                    in_array($application->status, [
                        Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                        Application::AWAITING_REG_FEE,
                        Application::REG_FEE_PROOF_UPLOADED,
                    ], true)
                    || $application->current_stage === 'accounts_verification_reg_fee'
                )) ? 'registration_fee' : 'application_fee';
            }

            if ($application->status === Application::RETURNED_TO_ACCOUNTS) {
                ApplicationWorkflow::transition($application, Application::ACCOUNTS_REVIEW, 'accounts_resume_review', [
                    'reason' => 'Auto-resume from returned_to_accounts before proof approval',
                ]);
            }

            $existingReceipt = Payment::where('application_id', $application->id)
                ->where('status', 'paid')
                ->whereNotNull('receipt_number')
                ->first();
            $receiptNumber = $existingReceipt->receipt_number ?? self::generateReceiptNumber('proof');

            $application->update([
                'proof_status' => 'approved',
                'proof_reviewed_by' => Auth::id(),
                'proof_reviewed_at' => now(),
                'proof_review_notes' => $data['proof_review_notes'] ?? null,
                'paynow_reference' => $data['paynow_reference'] ?? $application->paynow_reference,
                'payment_status' => 'paid',
                'receipt_number' => $receiptNumber,
            ]);

            $payment = Payment::create([
                'application_id' => $application->id,
                'payer_user_id' => $application->applicant_user_id,
                'method' => 'proof',
                'source' => 'offline',
                'amount' => $application->proof_amount_paid ?? 0,
                'currency' => 'USD',
                'reference' => $data['paynow_reference'] ?? ('PROOF-' . $application->reference),
                'status' => 'paid',
                'confirmed_at' => now(),
                'receipt_number' => $receiptNumber,
                'applicant_category' => $application->accreditation_category_code ?? $application->media_house_category_code,
                'service_type' => $application->application_type,
                'residency' => $application->residency_type ?? 'local',
                'recorded_by' => Auth::id(),
            ]);

            $this->logPaymentAction($payment, 'approved_proof', null, 'paid', $data['proof_review_notes'] ?? 'Payment proof approved.');

            if ($pendingSubmission) {
                $pendingSubmission->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                    'verified_by' => Auth::id(),
                ]);
            }

            $transitionMeta = [
                'notes' => $data['proof_review_notes'] ?? null,
                'region' => $application->collection_region ?? null,
                'source' => 'accounts_approve_proof',
                'payment_stage' => $paymentStage,
            ];

            if ($paymentStage === 'registration_fee') {
                // Paid confirmation is the valid bridge from Accounts to the
                // Production queue. Some legacy Registrar hand-offs use the
                // special pending-Accounts status, whose valid bridge is
                // PAYMENT_VERIFIED instead.
                if ($application->status === Application::REG_FEE_PROOF_UPLOADED) {
                    ApplicationWorkflow::transition($application, Application::AWAITING_REG_FEE, 'accounts_resume_registration_fee', $transitionMeta);
                }
                if ($application->status === Application::AWAITING_REG_FEE) {
                    ApplicationWorkflow::transition($application, Application::AWAITING_ACCOUNTS_VERIFICATION, 'accounts_resume_registration_fee', $transitionMeta);
                }
                $paidStatus = ApplicationWorkflow::allowed($application->status, Application::PAID_CONFIRMED)
                    ? Application::PAID_CONFIRMED
                    : Application::PAYMENT_VERIFIED;
                ApplicationWorkflow::transition($application, $paidStatus, 'accounts_approve_registration_fee', $transitionMeta);
                ApplicationWorkflow::transition($application, Application::PRODUCTION_QUEUE, 'system_send_to_production', $transitionMeta);
            } else {
                // Application-fee approval is a pre-review hand-off, not a
                // production hand-off. This is the normal path for AP1/AP2/AP4
                // before the officer reviews the registration documents.
                if (ApplicationWorkflow::allowed($application->status, Application::SUBMITTED)) {
                    ApplicationWorkflow::transition($application, Application::SUBMITTED, 'accounts_approve_application_fee', $transitionMeta);
                } elseif (ApplicationWorkflow::allowed($application->status, Application::PAYMENT_VERIFIED)) {
                    ApplicationWorkflow::transition($application, Application::PAYMENT_VERIFIED, 'accounts_approve_application_fee', $transitionMeta);
                } else {
                    throw new \RuntimeException('The application-fee payment cannot be advanced from its current workflow status.');
                }
            }

            $application->forceFill([
                'locked_at' => null,
                'locked_by' => null,
                'assigned_officer_id' => null,
            ])->save();
        });

        if (!$processed) {
            // Another Accounts user already advanced this file. Surface a
            // neutral notice and skip the audit entry so we don't duplicate it.
            return back()->with('info', 'This payment proof was already processed by another officer.');
        }

        $this->audit('accounts_proof_approved', $application, $from, $application->status, [
            'notes' => $data['proof_review_notes'] ?? null,
        ]);

        $msg = 'Payment proof approved and sent to Production.';
        return back()->with('success', $msg);
    }

    /** Reject a payment proof — returns application to applicant for payment correction */
    public function rejectProof(Request $request, Application $application)
    {
        $data = $request->validate([
            'proof_review_notes' => ['required', 'string', 'max:5000'],
        ]);

        $from = $application->status;

        DB::transaction(function() use ($application, $data, $from) {
            $this->safeSet($application, [
                'proof_status' => 'rejected',
                'proof_reviewed_by' => Auth::id(),
                'proof_reviewed_at' => now(),
                'proof_review_notes' => $data['proof_review_notes'],
                'payment_status' => 'rejected',
            ]);

            ApplicationWorkflow::transition($application, Application::RETURNED_TO_APPLICANT, 'accounts_reject_payment', [
                'notes' => $data['proof_review_notes'],
                'reason' => 'Payment proof rejected by Accounts',
            ]);

            ActivityLogger::log('accounts_reject_payment_to_applicant', $application, $from, Application::RETURNED_TO_APPLICANT, [
                'actor_role' => 'accounts_payments',
                'actor_user_id' => Auth::id(),
                'notes' => $data['proof_review_notes'],
            ]);
        });

        $application->refresh();
        $this->audit('accounts_proof_rejected', $application, $from, $application->status, [
            'notes' => $data['proof_review_notes'],
        ]);

        return back()->with('success', 'Payment proof rejected. Application returned to applicant for payment correction.');
    }

    /** 3) Waivers: Requests */
    public function waiversRequests()
    {
        $applications = Application::query()
            ->with('applicant')
            ->where('waiver_status', 'submitted')
            // Only show if approved for payment or submitted for verification
            ->whereIn('status', [
                Application::ACCOUNTS_REVIEW, 
                Application::RETURNED_TO_ACCOUNTS,
                Application::AWAITING_ACCOUNTS_VERIFICATION
            ])
            ->latest('updated_at')
            ->paginate(20);

        return view('staff.accounts.waivers_requests', compact('applications'));
    }

    /** 3) Waivers: Approved */
    public function waiversApproved()
    {
        $applications = Application::query()
            ->with('applicant')
            ->where('waiver_status', 'approved')
            // Only show if approved for payment or submitted for verification
            ->whereIn('status', [
                Application::ACCOUNTS_REVIEW, 
                Application::RETURNED_TO_ACCOUNTS, 
                Application::PAID_CONFIRMED,
                Application::AWAITING_ACCOUNTS_VERIFICATION
            ])
            ->latest('waiver_reviewed_at')
            ->paginate(20);

        return view('staff.accounts.waivers_approved', compact('applications'));
    }

    /** 3) Waivers: Rejected */
    public function waiversRejected()
    {
        $applications = Application::query()
            ->with('applicant')
            ->where('waiver_status', 'rejected')
            // Only show if approved for payment or submitted for verification
            ->whereIn('status', [
                Application::ACCOUNTS_REVIEW, 
                Application::RETURNED_TO_ACCOUNTS,
                Application::AWAITING_ACCOUNTS_VERIFICATION
            ])
            ->latest('waiver_reviewed_at')
            ->paginate(20);

        return view('staff.accounts.waivers_rejected', compact('applications'));
    }

    public function approveWaiver(Request $request, Application $application)
    {
        $data = $request->validate([
            'waiver_review_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $from = $application->status;

        $this->safeSet($application, [
            'waiver_status' => 'approved',
            'waiver_reviewed_by' => Auth::id(),
            'waiver_reviewed_at' => now(),
            'waiver_review_notes' => $data['waiver_review_notes'] ?? null,
        ]);

        $application->refresh();
        $this->audit('accounts_waiver_approved', $application, $from, $application->status, [
            'notes' => $data['waiver_review_notes'] ?? null,
        ]);

        return back()->with('success', 'Waiver approved.');
    }

    public function rejectWaiver(Request $request, Application $application)
    {
        $data = $request->validate([
            'waiver_review_notes' => ['required', 'string', 'max:5000'],
        ]);

        $from = $application->status;

        $this->safeSet($application, [
            'waiver_status' => 'rejected',
            'waiver_reviewed_by' => Auth::id(),
            'waiver_reviewed_at' => now(),
            'waiver_review_notes' => $data['waiver_review_notes'],
        ]);

        $application->refresh();
        $this->audit('accounts_waiver_rejected', $application, $from, $application->status, [
            'notes' => $data['waiver_review_notes'],
        ]);

        return back()->with('success', 'Waiver rejected.');
    }

    /** 4) Reconciliation (basic placeholder built from application fields) */
    public function reconciliation()
    {
        $matchedQuery = Application::query()
            ->with('applicant')
            ->where(function ($w) {
                $w->whereNotNull('paynow_confirmed_at')
                  ->orWhere('payment_status', 'paid')
                  ->orWhere('proof_status', 'approved')
                  ->orWhere('status', Application::PAID_CONFIRMED);
            });

        $unmatchedQuery = Application::query()
            ->with('applicant')
            ->whereIn('status', [Application::ACCOUNTS_REVIEW, Application::RETURNED_TO_ACCOUNTS])
            ->whereNull('paynow_reference')
            ->whereNull('paynow_confirmed_at')
            ->where(fn($w) => $w->whereNull('payment_status')->orWhere('payment_status', '!=', 'paid'))
            ->where(fn($w) => $w->whereNull('proof_status')->orWhere('proof_status', '!=', 'approved'));

        $unreconciledPaymentsQuery = \DB::table('payments')
            ->where('reconciled', false)
            ->latest('created_at');

        $matched   = (clone $matchedQuery)->count();
        $unmatched = (clone $unmatchedQuery)->count();
        $pending   = Application::whereIn('status', [Application::ACCOUNTS_REVIEW, Application::RETURNED_TO_ACCOUNTS])->count();
        $unreconciledCount = (clone $unreconciledPaymentsQuery)->count();

        $matchedApplications   = $matchedQuery->latest()->paginate(15, ['*'], 'matched_page');
        $unmatchedApplications = $unmatchedQuery->latest()->paginate(15, ['*'], 'unmatched_page');
        $unreconciledPayments  = $unreconciledPaymentsQuery->paginate(15, ['*'], 'payments_page');

        return view('staff.accounts.reconciliation', compact(
            'matched', 'unmatched', 'pending', 'unreconciledCount',
            'matchedApplications', 'unmatchedApplications', 'unreconciledPayments'
        ));
    }

    /** 5) Paid Applications */
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

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
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

    public function applicationsPaid()
    {
        $applications = Application::query()
            ->with('applicant')
            ->where(function ($w) {
                $w->whereIn('status', [
                    Application::PAID_CONFIRMED,
                    Application::PRODUCTION_QUEUE,
                    Application::PRODUCED_READY,
                    Application::PRINTED,
                    Application::ISSUED,
                    Application::PAYMENT_VERIFIED,
                    Application::SUBMITTED,
                ])
                ->orWhere('payment_status', 'paid')
                ->orWhereNotNull('paynow_confirmed_at')
                ->orWhere('proof_status', 'approved');
            })
            ->latest()
            ->paginate(20);

        return view('staff.accounts.apps_paid', compact('applications'));
    }

    /** 5) Pending Payments */
    public function applicationsPending()
    {
        $applications = Application::query()
            ->with('applicant')
            ->whereIn('status', [Application::ACCOUNTS_REVIEW, Application::RETURNED_TO_ACCOUNTS])
            ->latest()
            ->paginate(20);

        return view('staff.accounts.apps_pending', compact('applications'));
    }

    /** 5) Waived Applications */
    public function applicationsWaived()
    {
        $applications = Application::query()
            ->with('applicant')
            ->where('waiver_status', 'approved')
            ->latest('waiver_reviewed_at')
            ->paginate(20);

        return view('staff.accounts.apps_waived', compact('applications'));
    }

    /** 6) Reports: Revenue */
    public function reportRevenue(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $year = max(now()->year - 10, min(now()->year + 1, $year));
        $yearStart = Carbon::create($year)->startOfYear();
        $yearEnd   = Carbon::create($year)->endOfYear();

        // Base query on the Payment table — the authoritative source
        $base = Payment::query()
            ->where('payments.status', 'paid')
            ->whereNull('payments.voided_at')
            ->with('application');

        // Optional filters
        if ($request->filled('date_from')) {
            $base->whereDate('payments.confirmed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $base->whereDate('payments.confirmed_at', '<=', $request->date_to);
        }
        if ($request->filled('method')) {
            $base->where('payments.method', $request->method);
        }
        if ($request->filled('service_type')) {
            $base->where('payments.service_type', 'like', '%' . $request->service_type . '%');
        }

        // KPIs
        $totalRevenue  = (clone $base)->sum('amount');
        $paidCount     = (clone $base)->count();
        $waivedCount   = Application::where('waiver_status', 'approved')
                            ->whereBetween('created_at', [$yearStart, $yearEnd])
                            ->count();

        // Revenue by payment method
        $byMethod = (clone $base)
            ->whereBetween('confirmed_at', [$yearStart, $yearEnd])
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        // Revenue by service type — new/renewal × accreditation/registration breakdown
        $byServiceType = (clone $base)
            ->join('applications', 'applications.id', '=', 'payments.application_id')
            ->whereBetween('payments.confirmed_at', [$yearStart, $yearEnd])
            ->where('payments.status', 'paid')
            ->whereNull('payments.voided_at')
            ->selectRaw("
                applications.application_type,
                applications.request_type,
                SUM(payments.amount) as total,
                COUNT(*) as cnt
            ")
            ->groupBy('applications.application_type', 'applications.request_type')
            ->orderBy('applications.application_type')
            ->orderBy('applications.request_type')
            ->get();

        // Monthly trend for selected year
        $isSqlite  = DB::getDriverName() === 'sqlite';
        $monthExpr = $isSqlite
            ? "strftime('%Y-%m', confirmed_at)"
            : "DATE_FORMAT(confirmed_at, '%Y-%m')";

        $monthlyRevenue = (clone $base)
            ->selectRaw("{$monthExpr} as month, SUM(amount) as total, COUNT(*) as cnt")
            ->whereBetween('confirmed_at', [$yearStart, $yearEnd])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyCount = (clone $base)
            ->selectRaw("{$monthExpr} as month, COUNT(*) as cnt")
            ->whereBetween('confirmed_at', [$yearStart, $yearEnd])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('cnt', 'month')
            ->toArray();

        $months = collect(range(1, 12))->map(fn($m) => Carbon::create($year, $m, 1)->format('Y-m'));

        $revenueChartData = [
            'labels'  => $months->map(fn($m) => Carbon::parse($m)->format('M'))->values()->all(),
            'revenue' => $months->map(fn($m) => round((float)($monthlyRevenue[$m] ?? 0), 2))->values()->all(),
            'count'   => $months->map(fn($m) => (int)($monthlyCount[$m] ?? 0))->values()->all(),
        ];

        // Method breakdown for pie chart
        $methodLabels = [
            'paynow'       => 'PayNow',
            'proof'        => 'Proof of Payment',
            'proof_upload' => 'Proof of Payment',
            'cash'         => 'Cash',
            'transfer'     => 'Bank Transfer',
            'waiver'       => 'Waiver',
            'general'      => 'Other',
        ];

        $pieData = [
            'labels' => $byMethod->map(fn($r) => $methodLabels[$r->method] ?? ucfirst($r->method))->values()->all(),
            'totals' => $byMethod->map(fn($r) => round((float)$r->total, 2))->values()->all(),
        ];

        // Recent transactions table (last 50 in selected year)
        $recentPayments = (clone $base)
            ->with(['application.applicant', 'recorder'])
            ->whereBetween('confirmed_at', [$yearStart, $yearEnd])
            ->latest('confirmed_at')
            ->limit(50)
            ->get();

        $availableYears = range((int)now()->year, (int)now()->year - 4);

        $availableMethods = Payment::where('status', 'paid')
            ->whereNull('voided_at')
            ->distinct()
            ->pluck('method')
            ->filter()
            ->values();

        return view('staff.accounts.reports_revenue', compact(
            'paidCount', 'totalRevenue', 'waivedCount',
            'byMethod', 'byServiceType',
            'revenueChartData', 'pieData',
            'recentPayments',
            'year', 'availableYears', 'availableMethods',
            'methodLabels'
        ));
    }

    public function exportLedger(Request $request)
    {
        $bucket = $request->get('bucket');
        $labels = Application::bucketLabels();
        if (!isset($labels[$bucket])) {
            return back()->with('error', 'Invalid category selected');
        }

        $query = Application::applyBucket(Application::query(), $bucket);

        // Filters
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('min_amount')) $query->where('proof_amount_paid', '>=', $request->min_amount);
        if ($request->filled('max_amount')) $query->where('proof_amount_paid', '<=', $request->max_amount);

        $query->with('applicant')
            ->where(function($w) {
                $w->where('payment_status', 'paid')
                  ->orWhereNotNull('paynow_confirmed_at')
                  ->orWhere('proof_status', 'approved')
                  ->orWhere('status', Application::PAID_CONFIRMED)
                  ->orWhere('waiver_status', 'approved');
            })
            ->latest();

        $filename = 'Ledger_' . Str::slug($labels[$bucket]) . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Reference', 'Applicant Name', 'Email', 'Type', 'Amount Paid', 'Bank/Method', 'Reference/Paynow', 'Date Paid', 'Status', 'Waiver Status'];

        $callback = function() use($query, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $query->chunk(100, function($apps) use($file) {
                foreach ($apps as $app) {
                    $row = [
                        $app->id,
                        $app->reference,
                        optional($app->applicant)->name,
                        optional($app->applicant)->email,
                        $app->applicationTypeLabel(),
                        $app->proof_amount_paid ?: '—',
                        $app->proof_bank_name ?: ($app->paynow_reference ? 'PayNow' : '—'),
                        $app->paynow_reference ?: '—',
                        $app->paynow_confirmed_at ?: ($app->proof_payment_date ?: '—'),
                        $app->payment_status ?: ($app->proof_status ?: '—'),
                        $app->waiver_status ?: 'none',
                    ];

                    fputcsv($file, $row);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /** 6) Reports: Exceptions */
    public function reportExceptions()
    {
        $rejectedProofs = Application::query()->where('proof_status', 'rejected')->count();
        $failedPaynow = Application::query()->where('payment_status', 'failed')->count();
        $unmatched = Application::query()
            ->whereIn('status', [Application::ACCOUNTS_REVIEW, Application::RETURNED_TO_ACCOUNTS])
            ->whereNull('paynow_reference')
            ->where('proof_status', '!=', 'approved')
            ->count();

        return view('staff.accounts.reports_exceptions', compact('rejectedProofs', 'failedPaynow', 'unmatched'));
    }

    /** 6) Reports: Audit */
    public function reportAudit()
    {
        $logs = ActivityLog::query()
            ->whereIn('action', [
                'accounts_confirm_paid',
                'accounts_return_to_officer',
                'accounts_proof_approved',
                'accounts_proof_rejected',
                'accounts_waiver_approved',
                'accounts_waiver_rejected',
                'applicant_proof_uploaded',
                'applicant_waiver_uploaded',
            ])
            ->latest('created_at')
            ->paginate(30);

        return view('staff.accounts.reports_audit', compact('logs'));
    }

    /** 6) Alerts */
    public function alerts()
    {
        $logs = ActivityLog::query()
            ->whereIn('action', [
                'applicant_waiver_uploaded',
                'applicant_proof_uploaded',
                'paynow_payment_received',
                'paynow_payment_reversed',
                'paynow_payment_failed',
                'accounts_proof_approved',
                'accounts_proof_rejected',
                'accounts_waiver_approved',
                'accounts_waiver_rejected',
            ])
            ->latest('created_at')
            ->paginate(30);

        return view('staff.accounts.alerts', compact('logs'));
    }

    /** 7) System tools: PayNow settings (view-only) */
    public function paynowSettings()
    {
        $settings = [
            'PAYNOW_INTEGRATION' => config('services.paynow.enabled'),
            'PAYNOW_USD_INTEGRATION_ID' => config('services.paynow.usd.integration_id'),
            'PAYNOW_ZWG_INTEGRATION_ID' => config('services.paynow.zwg.integration_id'),
            'PAYNOW_RESULT_URL' => config('services.paynow.result_url'),
            'PAYNOW_RETURN_URL' => config('services.paynow.return_url'),
            'PAYNOW_DEFAULT_CURRENCY' => config('services.paynow.default_currency'),
        ];

        return view('staff.accounts.paynow_settings', compact('settings'));
    }

    /** 7) System tools: User action logs */
    public function userActionLogs(Request $request)
    {
        $q = ActivityLog::query();
        if ($request->filled('action')) $q->where('action', $request->string('action'));
        if ($request->filled('user')) $q->where('user_id', (int) $request->input('user'));

        $logs = $q->latest('created_at')->paginate(30)->withQueryString();
        return view('staff.accounts.user_action_logs', compact('logs'));
    }

    /** Help & support */
    public function help()
    {
        return view('staff.accounts.help');
    }

    public function show(Application $application)
    {
        // Try to claim the application (concurrency lock)
        if (!$application->claim(auth()->user())) {
            $lockerName = $application->lockedBy ? $application->lockedBy->name : 'another official';
            return redirect()->back()->with('error', "This application is currently being worked on by {$lockerName}.");
        }

        $application->load(['applicant', 'documents', 'messages', 'workflowLogs', 'lockedBy', 'comments.user']);

        // Get all payments for this user (includes PayNow payments via gateway field)
        $userPayments = Payment::where('payer_user_id', $application->user_id)
            ->with(['application'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('staff.accounts.show', compact('application', 'userPayments'));
    }

    public function unlock(Application $application)
    {
        $application->locked_by = null;
        $application->locked_at = null;
        $application->save();

        return redirect()->route('staff.accounts.dashboard')->with('success', 'Application released.');
    }

    public static function normalizePaymentMethod(string $method): string
    {
        $map = [
            'paynow_reference' => 'paynow',
            'paynow_manual_reference' => 'paynow',
            'proof_upload' => 'proof',
        ];
        return $map[$method] ?? $method;
    }

    public static function generateReceiptNumber(string $method = 'general'): string
    {
        $method = self::normalizePaymentMethod($method);
        $year = (int) date('Y');
        $prefixes = [
            'paynow' => 'PN',
            'cash' => 'CSH',
            'transfer' => 'TRF',
            'waiver' => 'WVR',
            'proof' => 'POP',
            'general' => 'RCT',
        ];
        $prefix = $prefixes[$method] ?? 'RCT';

        $sequence = DB::table('receipt_sequences')
            ->where('prefix', $prefix)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($sequence) {
            $next = $sequence->last_number + 1;
            DB::table('receipt_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);
        } else {
            $next = 1;
            DB::table('receipt_sequences')->insert([
                'prefix' => $prefix,
                'year' => $year,
                'last_number' => $next,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $prefix . '-' . $year . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function generateReceipt(Application $application)
    {
        if ($application->status !== Application::PAID_CONFIRMED &&
            $application->status !== Application::SUBMITTED &&
            $application->payment_status !== 'paid' &&
            $application->proof_status !== 'approved') {
            
            // Final fallback: check if a paid Payment record exists
            $hasPaidRecord = Payment::where('application_id', $application->id)->where('status', 'paid')->exists();
            if (!$hasPaidRecord) {
                return back()->with('error', 'Application is not paid or confirmed.');
            }
        }

        $application->load(['applicant']);

        $payment = Payment::where('application_id', $application->id)
            ->where('status', 'paid')
            ->with('recorder')
            ->latest('confirmed_at')
            ->first();

        $receiptNumber = $payment->receipt_number ?? $application->receipt_number ?? null;
        if (!$receiptNumber && $payment) {
            $receiptNumber = self::generateReceiptNumber($payment->method ?? 'general');
            $payment->update(['receipt_number' => $receiptNumber]);
            if ($this->hasColumn('applications', 'receipt_number')) {
                $application->update(['receipt_number' => $receiptNumber]);
            }
        }

        $fee = $this->calculateApplicationFee($application);

        $logoPath = public_path('zmc_logo.png');
        $logoData = "";
        if (file_exists($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $data = [
            'application' => $application,
            'payment' => $payment,
            'receipt_number' => $receiptNumber ?? 'N/A',
            'amount' => $payment->amount ?? $fee,
            'payment_method' => $payment->method ?? $application->payment_submission_method ?? 'N/A',
            'payment_date' => $payment->confirmed_at ?? $application->payment_paid_at ?? now(),
            'date' => now()->format('Y-m-d H:i'),
            'receipted_by' => $payment?->recorder?->name ?? Auth::user()?->name ?? 'Accounts Officer',
            'generated_at' => now()->format('d M Y H:i'),
            'company_name' => 'Zimbabwe Media Commission',
            'company_address' => config('organization.registered_address'),
            'company_email' => config('organization.accreditation_email'),
            'company_phone' => config('organization.public_phone'),
            'company_logo' => $logoData,
        ];

        $pdf = Pdf::loadView('receipts.styled_receipt', $data);
        
        if (request()->has('download')) {
            return $pdf->download('Receipt_' . ($receiptNumber ?: $application->reference) . '.pdf');
        }
        
        return $pdf->stream('Receipt_' . ($receiptNumber ?: $application->reference) . '.pdf');
    }

    public function calculateApplicationFee(Application $application): float
    {
        // 1. Try to find the amount from the most recent pending submission
        $latestSubmission = \App\Models\PaymentSubmission::where('application_id', $application->id)
            ->where('status', 'submitted')
            ->latest()
            ->first();

        if ($latestSubmission && $latestSubmission->amount > 0) {
            return (float) $latestSubmission->amount;
        }

        // 2. Fallback to status-based logic
        if ($application->application_type === 'accreditation') {
            $scope = $application->journalist_scope ?? 'local';
            $requestType = $application->request_type ?? 'new';
            $feeKey = strtolower($scope) === 'foreign' ? "foreign_{$requestType}" : $requestType;
            $fee = config("fees.accreditation.{$feeKey}", config("fees.accreditation.{$requestType}"));
            if ($fee === null) {
                throw new \InvalidArgumentException('No accreditation fee is configured for this request type.');
            }
            return (float) $fee;
        }

        $requestType = $application->request_type ?? 'new';
        $isRegFeeStage = in_array($application->status, [
            Application::REGISTRAR_APPROVED,
            Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
            Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            Application::PRODUCTION_QUEUE,
        ]);

        if ($requestType === 'replacement') {
            return (float) config('fees.media_house.replacement_fee');
        }
        if ($requestType === 'renewal') {
            return (float) config('fees.media_house.renewal_fee');
        }
        if ($isRegFeeStage) {
            $category = $application->media_house_category_code ?? 'default';
            return (float) config(
                "fees.media_house.registration_fee.{$category}",
                config('fees.media_house.registration_fee.default')
            );
        }

        return (float) config('fees.media_house.application_fee');
    }

    /**
     * Confirm paid -> push to production_queue
     */
    public function markPaid(Request $request, Application $application)
    {
        $data = $request->validate([
            'paynow_reference' => ['nullable', 'string', 'max:200'],
            'payment_status'   => ['nullable', 'string', 'max:100'],
            'decision_notes'   => ['nullable', 'string', 'max:5000'],
        ]);

        $from = $application->status;
        $processed = false;

        DB::transaction(function() use ($application, $data, &$from, &$processed) {
            // Row-level lock + idempotency guard. Two Accounts users can race
            // on Mark Paid from the shared dashboard; without this guard the
            // workflow could double-process (duplicate Payment rows, duplicate
            // activity log entries). Reload under FOR UPDATE so the second
            // request waits for the first to commit, then short-circuits.
            $fresh = Application::whereKey($application->id)->lockForUpdate()->first();
            if ($fresh) {
                $application->setRawAttributes($fresh->getAttributes(), true);
                $from = $application->status;
            }

            $accountsActionable = [
                Application::ACCOUNTS_REVIEW,
                Application::AWAITING_ACCOUNTS_VERIFICATION,
                Application::RETURNED_TO_ACCOUNTS,
                Application::REGISTRAR_APPROVED,
                Application::REGISTRAR_APPROVED_PENDING_REG_FEE,
                Application::PENDING_ACCOUNTS_FROM_REGISTRAR,
            ];
            if (!in_array($application->status, $accountsActionable, true)) {
                // Already processed by another Accounts user — exit silently.
                return;
            }
            $processed = true;

            $rawMethod = $application->payment_submission_method ?? 'general';
            $method = self::normalizePaymentMethod($rawMethod);

            // Find the current submission to determine the stage
            $latestSubmission = \App\Models\PaymentSubmission::where('application_id', $application->id)
                ->where('status', 'submitted')
                ->latest()
                ->first();
            $paymentStage = $latestSubmission ? $latestSubmission->payment_stage : 'application_fee';

            // Check if THIS specific submission was already recorded as a payment
            $existingPayment = Payment::where('application_id', $application->id)
                ->where('status', 'paid')
                ->where('reference', $data['paynow_reference'] ?? ($application->reference . '-' . strtoupper($paymentStage)))
                ->first();

            $receiptNumber = self::generateReceiptNumber($method);

            foreach (['paynow_reference','payment_status','decision_notes'] as $col) {
                if (!empty($data[$col]) && Schema::hasColumn('applications', $col)) {
                    $application->{$col} = $data[$col];
                }
            }
            if (!empty($data['payment_status']) && strtolower($data['payment_status']) === 'paid' && Schema::hasColumn('applications', 'payment_paid_at')) {
                $application->payment_paid_at = now();
            }
            
            // For Media House, we might want to store the latest receipt number on the app
            if ($this->hasColumn('applications', 'receipt_number')) {
                $application->receipt_number = $receiptNumber;
            }
            $application->save();

            $fee = $this->calculateApplicationFee($application);

            if (!$existingPayment) {
                Payment::create([
                    'application_id' => $application->id,
                    'payer_user_id' => $application->applicant_user_id,
                    'method' => $method,
                    'source' => 'offline',
                    'amount' => $latestSubmission->amount ?? ($application->proof_amount_paid ?? $fee),
                    'currency' => 'USD',
                    'reference' => $data['paynow_reference'] ?? ($application->reference . '-' . strtoupper($paymentStage)),
                    'status' => 'paid',
                    'confirmed_at' => now(),
                    'receipt_number' => $receiptNumber,
                    'applicant_category' => $application->accreditation_category_code ?? $application->media_house_category_code,
                    'service_type' => $application->application_type . ' (' . str_replace('_', ' ', $paymentStage) . ')',
                    'residency' => $application->residency_type ?? 'local',
                    'recorded_by' => Auth::id(),
                ]);
            }

            // Update submission status
            if ($latestSubmission) {
                $latestSubmission->update(['status' => 'verified', 'verified_at' => now(), 'verified_by' => Auth::id()]);
            }

            $isPreSubmission = ($from === Application::AWAITING_ACCOUNTS_VERIFICATION &&
                !$application->approved_at);
            $nextStatus = $isPreSubmission ? Application::SUBMITTED : Application::PAID_CONFIRMED;

            ApplicationWorkflow::transition($application, $nextStatus, 'accounts_confirm_paid', $data);

            if (!$isPreSubmission) {
                ApplicationWorkflow::transition($application, Application::PRODUCTION_QUEUE, 'system_send_to_production', [
                    'region' => $application->collection_region ?? null,
                ]);

                // Hand-off to Production: release the Accounts user's
                // concurrency lock and clear the originating Accreditation
                // Officer assignment so the Production queue (which filters
                // by `assigned_officer_id IS NULL OR == current_user.id` and
                // by recent locks) can surface the file to the production team.
                $application->forceFill([
                    'locked_at' => null,
                    'locked_by' => null,
                    'assigned_officer_id' => null,
                ])->save();
            }

            ActivityLogger::log('accounts_confirm_paid', $application, $from, $application->status, [
                'actor_role' => session('active_staff_role'),
                'paynow_reference' => $data['paynow_reference'] ?? null,
                'payment_status' => $data['payment_status'] ?? null,
                'receipt_number' => $receiptNumber,
            ]);
        });

        if (!$processed) {
            return back()->with('info', 'This payment was already confirmed by another officer.');
        }

        $msg = $application->status === Application::SUBMITTED
            ? 'Payment confirmed. Application forwarded to accreditation officer for review.'
            : 'Payment confirmed and sent to Production.';
        
        $receiptUrl = route('staff.accounts.applications.receipt', $application);
        
        return back()->with('success', $msg)->with('download_receipt_url', $receiptUrl);
    }


    public function returnToOfficer(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['required', 'string', 'max:5000'],
        ]);

        $from = $application->status;

        ApplicationWorkflow::transition($application, Application::RETURNED_TO_OFFICER, 'accounts_return_to_officer', [
            'notes' => $data['decision_notes'],
        ]);

        $this->safeSet($application, ['decision_notes' => $data['decision_notes']]);

        // Hand-off back to Accreditation Officer pool: release the Accounts
        // user's concurrency lock so the officer dashboard (which hides items
        // locked by another user for up to 2 hours) can show the file.
        // assigned_officer_id is preserved so the originating officer keeps
        // ownership of their queue item.
        $application->forceFill([
            'locked_at' => null,
            'locked_by' => null,
        ])->save();

        $application->refresh();

        $this->audit('accounts_return_to_officer', $application, $from, $application->status, [
            'notes' => $data['decision_notes'],
        ]);

        return back()->with('success', 'Returned to Accreditation Officer.');
    }

    /**
     * Verify payment submission (including waivers from special cases)
     */
    public function verifyPaymentSubmission(Request $request, Application $application)
    {
        $data = $request->validate([
            'action' => ['required', 'in:verify,reject'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'payment_submission_id' => ['nullable', 'exists:payment_submissions,id'],
        ]);

        // Use new workflow service - enforces strict transitions
        try {
            if ($data['action'] === 'verify') {
                $fromStatus = $application->status;
                $application = PaymentWorkflowService::verifyPayment($application, [
                    'notes' => $data['notes'] ?? null,
                    'payment_submission_id' => $data['payment_submission_id'] ?? null,
                ]);

                // After payment verification, send to Production Queue
                if ($application->status === Application::PAYMENT_VERIFIED) {
                    $application->update([
                        'status' => Application::PRODUCTION_QUEUE,
                        'last_action_at' => now(),
                    ]);
                    $message = 'Payment verified and application sent to Production for document review and card/certificate generation.';
                } elseif ($application->status === Application::SUBMITTED) {
                    $message = 'Payment verified. Application forwarded to accreditation officer for review.';
                } else {
                    $message = 'Payment verified and application sent to Production.';
                }
                
                if ($application->requiresApplicationFee()) {
                    $bothVerified = PaymentWorkflowService::areBothPaymentStagesVerified($application);
                    if (!$bothVerified) {
                        $message = 'Payment stage verified. Waiting for second payment stage.';
                    }
                }
            } else {
                // Reject payment
                $application = PaymentWorkflowService::rejectPayment($application, $data['notes'] ?? 'Payment rejected', [
                    'payment_submission_id' => $data['payment_submission_id'] ?? null,
                ]);

                $message = 'Payment rejected. Applicant must resubmit payment.';
            }

            return back()->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
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

    private function hasColumn(string $table, string $column): bool
    {
        try { return Schema::hasColumn($table, $column); }
        catch (\Throwable $e) { return false; }
    }

    /**
     * Renewals queue for accounts verification
     */
    public function renewalsQueue(Request $request)
    {
        $query = \App\Models\RenewalApplication::query()
            ->with(['applicant', 'originalApplication'])
            ->awaitingAccountsVerification()
            ->latest('payment_submitted_at');

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by renewal type
        if ($request->filled('renewal_type')) {
            $query->where('renewal_type', $request->renewal_type);
        }

        $renewals = $query->paginate(20)->withQueryString();

        // KPIs
        $kpis = [
            'pending' => \App\Models\RenewalApplication::awaitingAccountsVerification()->count(),
            'verified_today' => \App\Models\RenewalApplication::where('payment_verified_at', '>=', now()->startOfDay())->count(),
            'paynow' => \App\Models\RenewalApplication::awaitingAccountsVerification()->where('payment_method', 'PAYNOW')->count(),
            'proof' => \App\Models\RenewalApplication::awaitingAccountsVerification()->where('payment_method', 'PROOF_UPLOAD')->count(),
        ];

        return view('staff.accounts.renewals_queue', compact('renewals', 'kpis'));
    }

    /**
     * Show renewal details for verification
     */
    public function showRenewal(\App\Models\RenewalApplication $renewal)
    {
        $renewal->load(['applicant', 'originalApplication', 'changeRequests']);

        return view('staff.accounts.renewal_show', compact('renewal'));
    }

    /**
     * Verify renewal payment
     */
    public function verifyRenewalPayment(Request $request, \App\Models\RenewalApplication $renewal)
    {
        $data = $request->validate([
            'action' => ['required', 'in:verify,reject'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $from = $renewal->status;

        DB::transaction(function() use ($renewal, $data, $from) {
            if ($data['action'] === 'verify') {
                // Verify payment
                $renewal->update([
                    'payment_verified_at' => now(),
                    'payment_verified_by' => Auth::id(),
                    'status' => \App\Models\RenewalApplication::RENEWAL_PAYMENT_VERIFIED,
                    'current_stage' => 'production',
                    'last_action_at' => now(),
                    'last_action_by' => Auth::id(),
                ]);

                ActivityLogger::log('renewal_payment_verified', $renewal, $from, $renewal->status, [
                    'renewal_type' => $renewal->renewal_type,
                    'payment_method' => $renewal->payment_method,
                    'notes' => $data['notes'] ?? null,
                ]);

            } else {
                // Reject payment
                $renewal->update([
                    'payment_verified_at' => now(),
                    'payment_verified_by' => Auth::id(),
                    'payment_rejection_reason' => $data['notes'],
                    'status' => \App\Models\RenewalApplication::RENEWAL_PAYMENT_REJECTED,
                    'last_action_at' => now(),
                    'last_action_by' => Auth::id(),
                ]);

                ActivityLogger::log('renewal_payment_rejected', $renewal, $from, $renewal->status, [
                    'renewal_type' => $renewal->renewal_type,
                    'payment_method' => $renewal->payment_method,
                    'reason' => $data['notes'],
                ]);
            }
        });

        $message = $data['action'] === 'verify'
            ? 'Renewal payment verified. Application sent to Production.'
            : 'Renewal payment rejected. Applicant must resubmit payment.';

        return back()->with('success', $message);
    }

    /* =========================
     * LEVY PAYMENT VERIFICATION
     * ========================= */

    /**
     * Display pending levy payments for verification
     */
    public function leviesPending(Request $request)
    {
        $query = \App\Models\LevyRecord::query()
            ->with(['user', 'documents'])
            ->where('payment_status', 'pending')
            ->whereHas('documents', function($q) {
                $q->where('document_type', 'proof_of_payment');
            });

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('entity_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $levies = $query->latest('created_at')->paginate(20)->withQueryString();

        // Get available years for filter
        $availableYears = \App\Models\LevyRecord::distinct()
            ->pluck('financial_year')
            ->sort()
            ->values();

        $stats = [
            'pending_count' => \App\Models\LevyRecord::where('payment_status', 'pending')->count(),
            'pending_amount' => \App\Models\LevyRecord::where('payment_status', 'pending')->sum('levy_amount'),
            'verified_today' => \App\Models\LevyRecord::whereIn('payment_status', ['paid', 'verified', 'approved'])
                ->whereDate('payment_date', now()->toDateString())
                ->count(),
        ];

        return view('staff.accounts.levies_pending', compact('levies', 'availableYears', 'stats'));
    }

    /**
     * Display verified/paid levy payments
     */
    public function leviesVerified(Request $request)
    {
        $query = \App\Models\LevyRecord::query()
            ->with(['user', 'documents'])
            ->whereIn('payment_status', ['paid', 'verified', 'approved', 'completed']);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('entity_name', 'like', "%{$search}%")
                  ->orWhere('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->financial_year);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $levies = $query->latest('payment_date')->paginate(20)->withQueryString();

        // Get available years for filter
        $availableYears = \App\Models\LevyRecord::distinct()
            ->pluck('financial_year')
            ->sort()
            ->values();

        $stats = [
            'verified_count' => \App\Models\LevyRecord::whereIn('payment_status', ['paid', 'verified', 'approved', 'completed'])->count(),
            'verified_amount' => \App\Models\LevyRecord::whereIn('payment_status', ['paid', 'verified', 'approved', 'completed'])->sum('levy_amount'),
            'this_month' => \App\Models\LevyRecord::whereIn('payment_status', ['paid', 'verified', 'approved', 'completed'])
                ->whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->count(),
        ];

        return view('staff.accounts.levies_verified', compact('levies', 'availableYears', 'stats'));
    }

    /**
     * Display rejected levy payments
     */
    public function leviesRejected(Request $request)
    {
        $query = \App\Models\LevyRecord::query()
            ->with(['user', 'documents'])
            ->where('payment_status', 'rejected');

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('entity_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->financial_year);
        }

        $levies = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Get available years for filter
        $availableYears = \App\Models\LevyRecord::distinct()
            ->pluck('financial_year')
            ->sort()
            ->values();

        return view('staff.accounts.levies_rejected', compact('levies', 'availableYears'));
    }

    /**
     * Verify/Approve a levy payment
     */
    public function verifyLevy(Request $request, \App\Models\LevyRecord $levy)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:200'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'verification_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($levy->payment_status !== 'pending') {
            return back()->with('error', 'This levy payment has already been processed.');
        }

        DB::transaction(function() use ($levy, $data) {
            $receiptNumber = $data['receipt_number'] ?? self::generateReceiptNumber('levy');

            $levy->update([
                'payment_status' => 'paid',
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'] ?? $levy->reference,
                'receipt_number' => $receiptNumber,
                'payment_date' => now(),
                'form_data' => array_merge($levy->form_data ?? [], [
                    'verified_by' => Auth::id(),
                    'verified_at' => now()->toDateTimeString(),
                    'verification_notes' => $data['verification_notes'] ?? null,
                ]),
            ]);

            ActivityLogger::log('levy_payment_verified', $levy, 'pending', 'paid', [
                'levy_reference' => $levy->reference,
                'entity_name' => $levy->entity_name,
                'financial_year' => $levy->financial_year,
                'levy_amount' => $levy->levy_amount,
                'currency' => $levy->currency,
                'payment_method' => $data['payment_method'],
                'receipt_number' => $receiptNumber,
                'notes' => $data['verification_notes'] ?? null,
            ]);

            // Send email notification to user
            try {
                \Mail::to($levy->user->email)->send(new \App\Mail\LevyPaymentVerified($levy));
            } catch (\Exception $e) {
                \Log::warning('Failed to send levy verification email', [
                    'levy_id' => $levy->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        return back()->with('success', 'Levy payment verified successfully. Receipt number: ' . $levy->receipt_number);
    }

    /**
     * Reject a levy payment
     */
    public function rejectLevy(Request $request, \App\Models\LevyRecord $levy)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:5000'],
        ]);

        if ($levy->payment_status !== 'pending') {
            return back()->with('error', 'This levy payment has already been processed.');
        }

        DB::transaction(function() use ($levy, $data) {
            $levy->update([
                'payment_status' => 'rejected',
                'form_data' => array_merge($levy->form_data ?? [], [
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now()->toDateTimeString(),
                    'rejection_reason' => $data['rejection_reason'],
                ]),
            ]);

            ActivityLogger::log('levy_payment_rejected', $levy, 'pending', 'rejected', [
                'levy_reference' => $levy->reference,
                'entity_name' => $levy->entity_name,
                'financial_year' => $levy->financial_year,
                'levy_amount' => $levy->levy_amount,
                'currency' => $levy->currency,
                'reason' => $data['rejection_reason'],
            ]);

            // Send email notification to user
            try {
                \Mail::to($levy->user->email)->send(new \App\Mail\LevyPaymentRejected($levy, $data['rejection_reason']));
            } catch (\Exception $e) {
                \Log::warning('Failed to send levy rejection email', [
                    'levy_id' => $levy->id,
                    'error' => $e->getMessage()
                ]);
            }
        });

        return back()->with('success', 'Levy payment rejected. Applicant will be notified to resubmit.');
    }

    /**
     * Download levy document (proof of payment, financial statements, etc.)
     */
    public function downloadLevyDocument(\App\Models\LevyDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'Document file not found.');
        }

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_name
        );
    }

    /**
     * Bulk download levy payment proofs
     */
    public function bulkDownloadLevyProofs(Request $request)
    {
        $ids = $request->input('levy_ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No levy records selected.');
        }

        $levies = \App\Models\LevyRecord::whereIn('id', $ids)->with(['user', 'documents'])->get();
        if ($levies->isEmpty()) {
            return back()->with('error', 'No levy records found.');
        }

        $zip = new ZipArchive();
        $zipFileName = 'Levy_Payment_Proofs_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/public/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($levies as $levy) {
                $proofDoc = $levy->documents->firstWhere('document_type', 'proof_of_payment');
                
                if ($proofDoc && Storage::disk('public')->exists($proofDoc->file_path)) {
                    $extension = pathinfo($proofDoc->file_path, PATHINFO_EXTENSION);
                    $entityName = Str::slug($levy->entity_name);
                    $newFileName = $levy->reference . '_' . $entityName . '_proof.' . $extension;
                    $zip->addFile(Storage::disk('public')->path($proofDoc->file_path), $newFileName);
                }

                // Also add financial statements if needed
                $financialDoc = $levy->documents->firstWhere('document_type', 'audited_financials');
                if ($financialDoc && Storage::disk('public')->exists($financialDoc->file_path)) {
                    $extension = pathinfo($financialDoc->file_path, PATHINFO_EXTENSION);
                    $entityName = Str::slug($levy->entity_name);
                    $newFileName = $levy->reference . '_' . $entityName . '_financials.' . $extension;
                    $zip->addFile(Storage::disk('public')->path($financialDoc->file_path), $newFileName);
                }
            }
            $zip->close();

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Could not create ZIP file.');
    }

    /**
     * Generate levy payment receipt PDF
     */
    public function generateLevyReceipt(\App\Models\LevyRecord $levy)
    {
        if (!in_array($levy->payment_status, ['paid', 'verified', 'approved', 'completed'])) {
            return back()->with('error', 'Receipt can only be generated for verified levy payments.');
        }

        if (!$levy->receipt_number) {
            $levy->update(['receipt_number' => self::generateReceiptNumber('levy')]);
        }

        $pdf = PDF::loadView('staff.accounts.levy_receipt_pdf', compact('levy'));
        
        return $pdf->download('Levy_Receipt_' . $levy->reference . '.pdf');
    }

    /**
     * FOIA Appeals Payment Verification Queue
     */
    public function foiaAppealsPending(Request $request)
    {
        $appeals = \App\Models\Complaint::where('type', 'appeal')
            ->where('payment_status', 'pending')
            ->whereNotNull('payment_proof_path')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => \App\Models\Complaint::where('type', 'appeal')->where('payment_status', 'pending')->count(),
            'approved' => \App\Models\Complaint::where('type', 'appeal')->where('payment_status', 'approved')->count(),
            'rejected' => \App\Models\Complaint::where('type', 'appeal')->where('payment_status', 'rejected')->count(),
        ];

        return view('staff.accounts.foia_appeals_pending', compact('appeals', 'stats'));
    }

    /**
     * Approve FOIA Appeal Payment
     */
    public function approveFoiaAppealPayment(\App\Models\Complaint $appeal, Request $request)
    {
        if ($appeal->type !== 'appeal') {
            return back()->with('error', 'Not a valid FOIA appeal');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $appeal->update([
            'payment_status' => 'approved',
            'payment_verified_by' => Auth::id(),
            'payment_verified_at' => now(),
            'payment_verification_notes' => $validated['notes'] ?? null,
        ]);

        \App\Support\AuditTrail::log('foia_appeal_payment_approved', null, [
            'appeal_id' => $appeal->id,
            'payment_ref' => $appeal->payment_ref,
            'verified_by' => Auth::id(),
        ]);

        return back()->with('success', 'Payment approved for Appeal #' . str_pad($appeal->id, 6, '0', STR_PAD_LEFT));
    }

    /**
     * Reject FOIA Appeal Payment
     */
    public function rejectFoiaAppealPayment(\App\Models\Complaint $appeal, Request $request)
    {
        if ($appeal->type !== 'appeal') {
            return back()->with('error', 'Not a valid FOIA appeal');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $appeal->update([
            'payment_status' => 'rejected',
            'payment_verified_by' => Auth::id(),
            'payment_verified_at' => now(),
            'payment_verification_notes' => $validated['reason'],
        ]);

        \App\Support\AuditTrail::log('foia_appeal_payment_rejected', null, [
            'appeal_id' => $appeal->id,
            'payment_ref' => $appeal->payment_ref,
            'reason' => $validated['reason'],
            'verified_by' => Auth::id(),
        ]);

        // TODO: Send email to appellant notifying them of rejection
        
        return back()->with('success', 'Payment rejected for Appeal #' . str_pad($appeal->id, 6, '0', STR_PAD_LEFT));
    }

    /**
     * Download FOIA Appeal Payment Proof
     */
    public function downloadFoiaAppealPaymentProof(\App\Models\Complaint $appeal)
    {
        if ($appeal->type !== 'appeal' || !$appeal->payment_proof_path) {
            abort(404);
        }

        if (!Storage::disk('public')->exists($appeal->payment_proof_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('public')->download(
            $appeal->payment_proof_path,
            $appeal->payment_proof_original_name ?? 'payment_proof.pdf'
        );
    }
}
