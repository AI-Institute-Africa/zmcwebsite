<?php

namespace App\Http\Controllers\Staff\Appeals;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:public_info_compliance|super_admin|director']);
    }

    public function index(Request $request)
    {
        // Simple report view setup
        $baseQuery = Complaint::where('type', 'appeal');

        // Filters
        if ($request->filled('month')) {
            $baseQuery->whereMonth('created_at', date('m', strtotime($request->month)))
                      ->whereYear('created_at', date('Y', strtotime($request->month)));
        }

        $reportsData = [
            'total' => (clone $baseQuery)->count(),
            'paid' => (clone $baseQuery)->where('payment_status', 'paid')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            'by_category' => (clone $baseQuery)
                ->join('appeal_categories', 'complaints.category_id', '=', 'appeal_categories.id')
                ->select('appeal_categories.name as category', DB::raw('COUNT(complaints.id) as count'))
                ->groupBy('category')
                ->get(),
        ];

        return view('staff.appeals.reports', compact('reportsData'));
    }
}
