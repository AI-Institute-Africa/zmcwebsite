<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Support\AuditTrail;

class UserApprovalController extends Controller
{
    public function index(Request $request)
    {
        $pending = User::query()
            ->whereNull('approved_at')
            ->where(function ($q) {
                $q->whereNull('account_status')
                  ->orWhereNotIn('account_status', ['suspended', 'rejected', 'inactive']);
            })
            ->whereHas('roles')
            ->latest('id')
            ->paginate(20);

        return view('admin.approvals.index', compact('pending'));
    }

    public function approve(Request $request, User $user)
    {
        if (!empty($user->approved_at)) {
            return back()->with('success', 'User already approved.');
        }

        $user->forceFill([
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'account_status' => 'active',
        ])->save();

        AuditTrail::log('account_approved', $user);

        return back()->with('success', 'User approved.');
    }

    public function reject(Request $request, User $user)
    {
        // Soft “reject”: deactivate account (do not delete)
        $user->forceFill([
            'account_status' => 'suspended',
        ])->save();

        AuditTrail::log('account_rejected', $user);

        return back()->with('success', 'User rejected / suspended.');
    }
}
