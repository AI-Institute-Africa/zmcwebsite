<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DesignStudioTemplate;
use App\Models\User;
use App\Notifications\DesignStudioTemplateNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignStudioApprovalController extends Controller
{
    // ── Approval Queue ───────────────────────────────────────────────────────

    public function queue(Request $request)
    {
        $query = DesignStudioTemplate::with('creator')
            ->where('status', DesignStudioTemplate::STATUS_PENDING_APPROVAL)
            ->orderBy('submitted_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $templates = $query->paginate(20)->withQueryString();

        return view('staff.design-studio.approval.queue', compact('templates'));
    }

    // ── Approve ──────────────────────────────────────────────────────────────

    public function approve(DesignStudioTemplate $designStudioTemplate)
    {
        if ($designStudioTemplate->status !== DesignStudioTemplate::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'This template is not pending approval.');
        }

        $old = ['status' => $designStudioTemplate->status];

        $designStudioTemplate->update([
            'status'      => DesignStudioTemplate::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $designStudioTemplate->auditLog('template_approved', $old, [
            'status'      => DesignStudioTemplate::STATUS_APPROVED,
            'approved_by' => Auth::id(),
        ]);

        // Notify the designer
        $designer = $designStudioTemplate->creator;
        if ($designer) {
            $designer->notify(new DesignStudioTemplateNotification(
                $designStudioTemplate,
                'Your template "' . $designStudioTemplate->name . '" has been approved and is now active.',
                'view'
            ));
        }

        return back()->with('success', '"' . $designStudioTemplate->name . '" approved and is now active.');
    }

    // ── Reject ───────────────────────────────────────────────────────────────

    public function reject(Request $request, DesignStudioTemplate $designStudioTemplate)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($designStudioTemplate->status !== DesignStudioTemplate::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'This template is not pending approval.');
        }

        $old = ['status' => $designStudioTemplate->status];

        $designStudioTemplate->update([
            'status'           => DesignStudioTemplate::STATUS_REJECTED,
            'rejected_by'      => Auth::id(),
            'rejected_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
            'approved_by'      => null,
            'approved_at'      => null,
        ]);

        $designStudioTemplate->auditLog('template_rejected', $old, [
            'status'           => DesignStudioTemplate::STATUS_REJECTED,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        // Notify the designer
        $designer = $designStudioTemplate->creator;
        if ($designer) {
            $designer->notify(new DesignStudioTemplateNotification(
                $designStudioTemplate,
                'Your template "' . $designStudioTemplate->name . '" was rejected. Reason: ' . $data['rejection_reason'],
                'revise'
            ));
        }

        return back()->with('success', '"' . $designStudioTemplate->name . '" rejected. Designer has been notified.');
    }

    // ── Archive ──────────────────────────────────────────────────────────────

    public function archive(DesignStudioTemplate $designStudioTemplate)
    {
        if ($designStudioTemplate->status !== DesignStudioTemplate::STATUS_APPROVED) {
            return back()->with('error', 'Only approved templates can be archived.');
        }

        $old = ['status' => $designStudioTemplate->status];

        $designStudioTemplate->update([
            'status'      => DesignStudioTemplate::STATUS_ARCHIVED,
            'archived_by' => Auth::id(),
            'archived_at' => now(),
        ]);

        $designStudioTemplate->auditLog('template_archived', $old, [
            'status' => DesignStudioTemplate::STATUS_ARCHIVED,
        ]);

        // Notify designer
        $designer = $designStudioTemplate->creator;
        if ($designer) {
            $designer->notify(new DesignStudioTemplateNotification(
                $designStudioTemplate,
                'Your template "' . $designStudioTemplate->name . '" has been archived.',
                'view'
            ));
        }

        return back()->with('success', '"' . $designStudioTemplate->name . '" archived.');
    }

    // ── Restore ──────────────────────────────────────────────────────────────

    public function restore(DesignStudioTemplate $designStudioTemplate)
    {
        if ($designStudioTemplate->status !== DesignStudioTemplate::STATUS_ARCHIVED) {
            return back()->with('error', 'Only archived templates can be restored.');
        }

        $old = ['status' => $designStudioTemplate->status];

        $designStudioTemplate->update([
            'status'      => DesignStudioTemplate::STATUS_APPROVED,
            'archived_by' => null,
            'archived_at' => null,
        ]);

        $designStudioTemplate->auditLog('template_restored', $old, [
            'status' => DesignStudioTemplate::STATUS_APPROVED,
        ]);

        return back()->with('success', '"' . $designStudioTemplate->name . '" restored to active.');
    }
}
