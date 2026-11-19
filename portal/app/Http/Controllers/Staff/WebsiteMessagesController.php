<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\AppealCommunication;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class WebsiteMessagesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'staff.portal', 'role:pr_officer|super_admin']);
    }

    public function index(Request $request)
    {
        $query = Complaint::query()
            ->where('type', 'enquiry')
            ->with('handler')
            ->withCount('communications');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'priority'] as $filter) {
            if ($request->filled($filter)) {
                $allowed = $filter === 'status'
                    ? ['open', 'in_progress', 'resolved', 'closed']
                    : ['low', 'normal', 'high', 'urgent'];
                if (in_array($request->input($filter), $allowed, true)) {
                    $query->where($filter, $request->input($filter));
                }
            }
        }

        if ($request->input('assigned') === 'unassigned') {
            $query->whereNull('handled_by');
        } elseif ($request->filled('assigned') && is_numeric($request->input('assigned'))) {
            $query->where('handled_by', (int) $request->input('assigned'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $items = $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')->paginate(25)->withQueryString();
        $officers = $this->officers();
        $base = Complaint::where('type', 'enquiry');
        $stats = [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->whereIn('status', ['open', 'in_progress'])->count(),
            'urgent' => (clone $base)->whereIn('priority', ['urgent', 'high'])->whereNotIn('status', ['resolved', 'closed'])->count(),
            'overdue' => (clone $base)->whereNotIn('status', ['resolved', 'closed'])->whereNotNull('response_due_at')->where('response_due_at', '<', now())->count(),
        ];
        $reportRows = (clone $base)->get(['subject', 'created_at', 'first_responded_at']);
        $responseHours = $reportRows->filter(fn ($row) => $row->first_responded_at && $row->created_at)
            ->map(fn ($row) => $row->created_at->diffInMinutes($row->first_responded_at) / 60);
        $stats['avg_response_hours'] = $responseHours->isNotEmpty() ? round($responseHours->avg(), 1) : null;
        $topSubjects = $reportRows->map(fn ($row) => trim((string) ($row->subject ?: 'General enquiry')))
            ->countBy()->sortDesc()->take(5);

        return view('staff.pr.messages.index', compact('items', 'officers', 'stats', 'topSubjects'));
    }

    public function show(Complaint $message)
    {
        $this->ensureMessage($message);
        $message->load(['handler', 'communications.sender']);

        return view('staff.pr.messages.show', [
            'message' => $message,
            'officers' => $this->officers(),
            'ageHours' => $message->created_at?->diffInHours(now()) ?? 0,
        ]);
    }

    public function update(Request $request, Complaint $message)
    {
        $this->ensureMessage($message);
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'response_due_at' => ['nullable', 'date'],
        ]);

        if (!empty($data['assigned_to']) && !$this->officers()->contains('id', (int) $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => 'Select a PR Officer or administrator.']);
        }

        $message->update([
            'status' => $data['status'],
            'priority' => $data['priority'],
            'handled_by' => $data['assigned_to'] ?? null,
            'response_due_at' => $data['response_due_at'] ?? null,
            'handled_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : $message->handled_at,
        ]);
        AuditTrail::log('website_message_updated', $message, ['status' => $data['status'], 'priority' => $data['priority'], 'assigned_to' => $data['assigned_to'] ?? null]);

        return back()->with('success', 'Message workflow updated.');
    }

    public function note(Request $request, Complaint $message)
    {
        $this->ensureMessage($message);
        $data = $request->validate(['internal_notes' => ['nullable', 'string', 'max:10000']]);
        $message->update(['internal_notes' => $data['internal_notes'] ?? null]);
        AuditTrail::log('website_message_note_updated', $message);
        return back()->with('success', 'Private notes saved.');
    }

    public function reply(Request $request, Complaint $message)
    {
        $this->ensureMessage($message);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        try {
            Mail::raw($data['body'], function ($mail) use ($message, $data) {
                $mail->to($message->email)->subject($data['subject']);
            });
            AppealCommunication::create([
                'complaint_id' => $message->id,
                'sender_id' => Auth::id(),
                'subject' => $data['subject'],
                'body' => $data['body'],
                'delivery_status' => 'sent',
                'sent_at' => now(),
            ]);
            $message->update([
                'first_responded_at' => $message->first_responded_at ?: now(),
                'status' => in_array($message->status, ['open'], true) ? 'in_progress' : $message->status,
            ]);
            AuditTrail::log('website_message_reply_sent', $message, ['subject' => $data['subject']]);
            return back()->with('success', 'Reply sent and added to the message history.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'The reply could not be sent. Check mail settings and try again.');
        }
    }

    public function export(Request $request)
    {
        $rows = Complaint::where('type', 'enquiry')->with('handler')->latest()->get();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Received', 'Name', 'Email', 'Subject', 'Status', 'Priority', 'Assigned To', 'First Response']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->reference_number ?: 'ENQ-' . str_pad($row->id, 6, '0', STR_PAD_LEFT), $row->created_at?->toDateTimeString(), $row->full_name, $row->email, $row->subject, $row->status, $row->priority ?: 'normal', $row->handler?->name, $row->first_responded_at?->toDateTimeString()]);
            }
            fclose($out);
        }, 'website-messages-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function ensureMessage(Complaint $message): void
    {
        abort_unless($message->type === 'enquiry', 404);
    }

    private function officers()
    {
        return User::query()->where(function ($q) {
            $q->where('account_type', 'staff')->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['pr_officer', 'super_admin']));
        })->orderBy('name')->get(['id', 'name', 'email']);
    }
}
