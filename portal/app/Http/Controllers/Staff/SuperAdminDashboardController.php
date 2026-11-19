<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\ApplicationWorkflowService;
use App\Services\ApplicationWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminDashboardController extends Controller
{
    /**
     * Applications awaiting Superadmin Review
     */
    public function applications(Request $request)
    {
        $user = Auth::user();
        $isSuperOversight = $user && $user->hasAnyRole(['super_admin', 'director']);

        $query = Application::query()
            ->with(['applicant']);

        if ($isSuperOversight) {
            $query->where('status', '!=', 'draft');
        } else {
            $query->where('status', Application::SUPERADMIN_REVIEW);
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

        $applications = $query->latest('updated_at')->paginate(20)->withQueryString();

        return view('staff.superadmin.applications.index', compact('applications'));
    }

    public function showApplication(Application $application)
    {
        $application->load(['applicant', 'documents', 'messages', 'workflowLogs']);

        return view('staff.superadmin.applications.show', [
            'application' => $application,
            'auditTrail' => $application->workflowLogs()->latest()->paginate(15),
        ]);
    }

    public function approveApplication(Request $request, Application $application)
    {
        $data = $request->validate([
            'decision_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            ApplicationWorkflowService::superadminApprove($application, [
                'notes' => $data['decision_notes'] ?? null,
            ]);

            return redirect()->route('staff.superadmin.applications.index')->with('success', 'Application approved. Applicant will be prompted to pay Registration Fee.');
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
            ApplicationWorkflowService::superadminReject($application, [
                'notes' => $data['decision_notes'],
            ]);

            return redirect()->route('staff.superadmin.applications.index')->with('success', 'Application returned to Applicant for correction.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Workflow error: ' . $e->getMessage());
        }
    }
}
