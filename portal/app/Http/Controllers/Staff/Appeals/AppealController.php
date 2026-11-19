<?php

namespace App\Http\Controllers\Staff\Appeals;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppealController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:public_info_compliance|super_admin|director']);
    }

    public function index(Request $request)
    {
        $query = Complaint::where('type', 'appeal')->with(['category', 'handler']);

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhere('reference_number', 'like', "%{$s}%");
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Payment Filter
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $appeals = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('staff.appeals.index', compact('appeals'));
    }

    public function show(Complaint $appeal)
    {
        if ($appeal->type !== 'appeal') {
            abort(404, 'Not a FOIA appeal');
        }

        $appeal->load(['category', 'handler', 'communications.sender', 'appealPayments']);
        
        $officers = User::role('public_info_compliance')->get();

        return view('staff.appeals.show', compact('appeal', 'officers'));
    }

    public function updateStatus(Request $request, Complaint $appeal)
    {
        if ($appeal->type !== 'appeal') {
            return back()->with('error', 'Invalid request.');
        }

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,under_review,awaiting_information,response_sent,resolved,rejected,closed',
        ]);

        $oldStatus = $appeal->status;
        $appeal->update([
            'status' => $validated['status']
        ]);

        \App\Support\AuditTrail::log('appeal_status_changed', $appeal, [
            'appeal_id' => $appeal->id,
            'reference' => $appeal->reference_number,
            'old_value' => ['status' => $oldStatus],
            'new_value' => ['status' => $validated['status']],
            'new_status' => $validated['status']
        ]);

        return back()->with('success', 'Appeal status updated successfully.');
    }

    public function assignOfficer(Request $request, Complaint $appeal)
    {
        if ($appeal->type !== 'appeal') {
            return back()->with('error', 'Invalid request.');
        }

        $validated = $request->validate([
            'officer_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value && !User::role('public_info_compliance')->whereKey($value)->exists()) {
                        $fail('The selected user is not an appeals officer.');
                    }
                },
            ],
        ]);

        $previousOfficerId = $appeal->handled_by;
        $appeal->update([
            'handled_by' => $validated['officer_id']
        ]);

        \App\Support\AuditTrail::log('appeal_officer_assigned', $appeal, [
            'appeal_id' => $appeal->id,
            'reference' => $appeal->reference_number,
            'old_value' => ['handled_by' => $previousOfficerId],
            'new_value' => ['handled_by' => $validated['officer_id']],
        ]);

        return back()->with('success', 'Officer assigned successfully.');
    }

    public function addNote(Request $request, Complaint $appeal)
    {
        if ($appeal->type !== 'appeal') {
            return back()->with('error', 'Invalid request.');
        }

        $validated = $request->validate([
            'internal_notes' => 'required|string|max:10000',
        ]);

        $previousNotes = $appeal->internal_notes;
        $appeal->update([
            'internal_notes' => $validated['internal_notes']
        ]);

        \App\Support\AuditTrail::log('appeal_internal_note_updated', $appeal, [
            'appeal_id' => $appeal->id,
            'reference' => $appeal->reference_number,
            'notes_changed' => $previousNotes !== $validated['internal_notes'],
        ]);

        return back()->with('success', 'Internal notes updated.');
    }
}
