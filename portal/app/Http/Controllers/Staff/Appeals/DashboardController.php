<?php

namespace App\Http\Controllers\Staff\Appeals;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        // Require FOIA appeals role or higher to view dashboard
        $this->middleware(['auth', 'role:public_info_compliance|super_admin|director']);
    }

    public function index()
    {
        $baseQuery = Complaint::where('type', 'appeal');

        // Main KPIs
        $stats = [
            'total'          => (clone $baseQuery)->count(),
            'new'            => (clone $baseQuery)->where('status', 'open')->count(),
            'pending_payment'=> (clone $baseQuery)->where('payment_status', 'pending')->count(),
            'paid'           => (clone $baseQuery)->where('payment_status', 'paid')->count(),
            'under_review'   => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'closed'         => (clone $baseQuery)->where('status', 'closed')->count(),
            'this_month'     => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
            'revenue'        => (clone $baseQuery)->where('payment_status', 'paid')->sum('payment_amount'),
        ];

        // Chart 1: Appeals Submitted by Month (Last 6 months)
        $monthlyAppeals = (clone $baseQuery)
            ->select(DB::raw('COUNT(id) as count'), DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'))
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Chart 2: Appeals by Status
        $statusDistribution = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(id) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Chart 3: Appeals by Category
        $categoryDistribution = (clone $baseQuery)
            ->join('appeal_categories', 'complaints.category_id', '=', 'appeal_categories.id')
            ->select('appeal_categories.name as category', DB::raw('COUNT(complaints.id) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // Chart 4: Payment Status Distribution
        $paymentDistribution = (clone $baseQuery)
            ->select('payment_status', DB::raw('COUNT(id) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status')
            ->toArray();

        // Recent Appeals Table
        $recentAppeals = (clone $baseQuery)
            ->with(['category', 'handler', 'appealPayments'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('staff.appeals.dashboard', compact(
            'stats', 
            'monthlyAppeals', 
            'statusDistribution', 
            'categoryDistribution', 
            'paymentDistribution',
            'recentAppeals'
        ));
    }
}
