<?php

namespace App\Http\Controllers\Staff\Appeals;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:public_info_compliance|super_admin|director']);
    }

    public function index(Request $request)
    {
        $query = Complaint::where('type', 'appeal')
            ->whereNotNull('payment_status')
            ->with(['appealPayments' => fn ($payments) => $payments->latest('id')]);

        if ($request->filled('status')) {
            $status = $request->status === 'failed' ? 'rejected' : $request->status;
            $query->where('payment_status', $status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('payment_ref', 'like', "%{$s}%")
                  ->orWhereHas('appealPayments', fn ($payments) => $payments->where('reference', 'like', "%{$s}%"))
                  ->orWhere('full_name', 'like', "%{$s}%")
                  ->orWhere('reference_number', 'like', "%{$s}%");
            });
        }

        $payments = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('staff.appeals.payments', compact('payments'));
    }
}
