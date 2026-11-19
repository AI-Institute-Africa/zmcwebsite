<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AppealCommunication;
use App\Models\Complaint;
use App\Models\SystemConfig;
use App\Models\User;
use App\Support\FileType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ResearchTrainingController extends Controller
{
    private const STATUSES = ['open', 'in_progress', 'resolved', 'closed'];
    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function dashboard(Request $request)
    {
        $data = $this->dashboardData($request);
        return view('staff.research_training.dashboard', $data + ['page' => 'dashboard']);
    }

    public function register(Request $request)
    {
        $data = $this->dashboardData($request);
        return view('staff.research_training.dashboard', $data + ['page' => 'register']);
    }

    public function reports(Request $request)
    {
        $from = $request->date('from')?->startOfDay() ?? now()->copy()->startOfYear();
        $to = $request->date('to')?->endOfDay() ?? now()->copy()->endOfDay();
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $base = Complaint::where('type', 'complaint')->whereBetween('created_at', [$from, $to]);
        $statusCounts = collect(self::STATUSES)->mapWithKeys(fn ($status) => [
            $status => (clone $base)->where('status', $status)->count(),
        ]);
        $priorityCounts = collect(self::PRIORITIES)->mapWithKeys(fn ($priority) => [
            $priority => (clone $base)->where('priority', $priority)->count(),
        ]);
        $monthly = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($to)) {
            $monthEnd = $cursor->copy()->endOfMonth();
            $monthly[] = [
                'label' => $cursor->format('M Y'),
                'count' => (clone $base)->whereBetween('created_at', [$cursor, $monthEnd])->count(),
                'resolved' => (clone $base)->whereIn('status', ['resolved', 'closed'])
                    ->whereBetween('created_at', [$cursor, $monthEnd])->count(),
            ];
            $cursor->addMonth();
        }

        if ($request->boolean('export')) return $this->export($request);
        return view('staff.research_training.reports', compact(
            'from', 'to', 'statusCounts', 'priorityCounts', 'monthly'
        ) + ['page' => 'reports']);
    }

    public function export(Request $request)
    {
        $query = Complaint::where('type', 'complaint')->with('handler')->latest('created_at');
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->input('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->input('to'));
        if ($request->filled('status') && in_array($request->input('status'), self::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }
        $rows = $query->get();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Received', 'Complainant', 'Email', 'Subject', 'Priority', 'Status', 'Assigned Officer', 'Response Due']);
            foreach ($rows as $complaint) {
                fputcsv($out, [
                    $complaint->reference_number ?: 'COMP-' . $complaint->created_at?->format('Ymd') . '-' . str_pad($complaint->id, 6, '0', STR_PAD_LEFT),
                    $complaint->created_at?->toDateTimeString(), $complaint->full_name, $complaint->email,
                    $complaint->subject, $complaint->priority ?: 'normal', $complaint->status,
                    $complaint->handler?->name, $complaint->response_due_at?->toDateString(),
                ]);
            }
            fclose($out);
        }, 'complaints-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function downloads()
    {
        $complaints = Complaint::where('type', 'complaint')->whereNotNull('attachment_path')->latest('created_at')->get();
        $communications = AppealCommunication::whereHas('complaint', fn ($q) => $q->where('type', 'complaint'))
            ->whereNotNull('attachments')->with('complaint')->latest('created_at')->get();
        return view('staff.research_training.downloads', compact('complaints', 'communications') + ['page' => 'downloads']);
    }

    public function downloadAttachment(Complaint $complaint, ?int $communication = null, ?int $index = null)
    {
        abort_unless($complaint->type === 'complaint', 404);
        $path = $complaint->attachment_path;
        $name = $complaint->attachment_original_name ?: basename((string) $path);
        if ($communication) {
            $record = AppealCommunication::whereKey($communication)->where('complaint_id', $complaint->id)->firstOrFail();
            $attachment = collect($record->attachments ?? [])->get($index ?? 0);
            abort_unless(is_array($attachment) && !empty($attachment['path']), 404);
            $path = $attachment['path'];
            $name = $attachment['name'] ?? basename($path);
        }
        abort_unless($path && Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->download($path, $name);
    }

    public function settings()
    {
        $settings = SystemConfig::getValue('complaints.workflow', [
            'acknowledgement_enabled' => true,
            'default_response_days' => 10,
        ]);
        return view('staff.research_training.settings', compact('settings') + ['page' => 'settings']);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'acknowledgement_enabled' => ['nullable', 'boolean'],
            'default_response_days' => ['required', 'integer', 'min:1', 'max:90'],
        ]);
        SystemConfig::setValue('complaints.workflow', [
            'acknowledgement_enabled' => $request->boolean('acknowledgement_enabled'),
            'default_response_days' => (int) $data['default_response_days'],
        ]);
        return back()->with('success', 'Complaint workflow settings saved.');
    }

    public function update(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->type === 'complaint', 404);
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', self::STATUSES)],
            'priority' => ['required', 'in:' . implode(',', self::PRIORITIES)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'response_due_at' => ['nullable', 'date'],
        ]);
        $oldStatus = $complaint->status;
        $assignedTo = $data['assigned_to'] ?? null;
        $complaint->update([
            'status' => $data['status'],
            'priority' => $data['priority'],
            'handled_by' => $assignedTo ?: Auth::id(),
            'response_due_at' => $data['response_due_at'] ?? null,
            'handled_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : $complaint->handled_at,
            'first_responded_at' => $oldStatus === 'open' && $data['status'] !== 'open' && !$complaint->first_responded_at
                ? now() : $complaint->first_responded_at,
        ]);
        \App\Support\AuditTrail::log('complaint_update', $complaint, [
            'status' => $data['status'], 'priority' => $data['priority'], 'assigned_to' => $assignedTo,
        ]);
        return back()->with('success', 'Complaint workflow updated.');
    }

    public function reply(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->type === 'complaint', 404);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'extensions:pdf,doc,docx,jpg,jpeg,png,xlsx,csv', 'max:10240'],
        ]);
        $files = array_values(array_filter($request->file('attachments', [])));
        if ($request->boolean('require_supporting_document') && count($files) === 0) {
            return back()->withErrors(['attachments' => 'Attach at least one supporting document before sending this response.']);
        }
        $stored = [];
        try {
            foreach ($files as $file) {
                $stored[] = [
                    'path' => $this->storePublicFile($file, 'complaints/responses', FileType::safeHashName($file)),
                    'name' => $file->getClientOriginalName(), 'mime' => FileType::safeUploadedMime($file),
                ];
            }
            Mail::raw($data['body'], function ($mail) use ($complaint, $data, $stored) {
                $mail->to($complaint->email)->subject($data['subject']);
                foreach ($stored as $attachment) {
                    $mail->attach(Storage::disk('public')->path($attachment['path']), [
                        'as' => $attachment['name'], 'mime' => $attachment['mime'],
                    ]);
                }
            });
            AppealCommunication::create([
                'complaint_id' => $complaint->id, 'sender_id' => Auth::id(),
                'subject' => $data['subject'], 'body' => $data['body'],
                'attachments' => $stored ?: null, 'delivery_status' => 'sent', 'sent_at' => now(),
            ]);
            $complaint->update([
                'first_responded_at' => $complaint->first_responded_at ?: now(),
                'status' => $complaint->status === 'open' ? 'in_progress' : $complaint->status,
            ]);
        } catch (\Throwable $exception) {
            foreach ($stored as $attachment) Storage::disk('public')->delete($attachment['path']);
            report($exception);
            return back()->with('error', 'The response could not be sent. Check the email settings and try again.');
        }
        return back()->with('success', 'Response sent and recorded.');
    }

    private function dashboardData(Request $request): array
    {
        $query = Complaint::where('type', 'complaint')->with('handler');
        if ($request->filled('status') && in_array($request->input('status'), self::STATUSES, true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority') && in_array($request->input('priority'), self::PRIORITIES, true)) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(fn ($q) => $q->where('reference_number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")->orWhere('message', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        $complaints = $query->latest('created_at')->paginate(15)->withQueryString();
        $base = Complaint::where('type', 'complaint');
        $stats = [
            'total' => (clone $base)->count(), 'open' => (clone $base)->where('status', 'open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'resolved' => (clone $base)->where('status', 'resolved')->count(),
            'closed' => (clone $base)->where('status', 'closed')->count(),
            'this_month' => (clone $base)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'overdue' => (clone $base)->whereNotIn('status', ['resolved', 'closed'])->whereNotNull('response_due_at')->where('response_due_at', '<', now())->count(),
        ];
        return compact('complaints', 'stats') + [
            'officers' => User::role('complaints_officer')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
