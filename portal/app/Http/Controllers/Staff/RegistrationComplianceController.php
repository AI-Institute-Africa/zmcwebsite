<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AccreditationRecord;
use App\Models\LevyRecord;
use App\Models\RegistrationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RegistrationComplianceController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();
        $reviewWindow = $today->copy()->addDays(90);

        $registrations = RegistrationRecord::query()->with('contact:id,name,email')->whereNotNull('expires_at');
        $accreditations = AccreditationRecord::query()->with('holder:id,name,email')->whereNotNull('expires_at');
        $unpaidLevies = LevyRecord::query()
            ->with('user:id,name,email')
            ->whereNotIn('payment_status', ['paid', 'verified', 'successful', 'completed']);

        $registeredTotal = (clone $registrations)->where('status', 'active')->count();
        $accreditedTotal = (clone $accreditations)->where('status', 'active')->count();
        $compliantRegistrations = (clone $registrations)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today);
            });
        $compliantTotal = (clone $compliantRegistrations)->count();
        $nonCompliantTotal = max(0, $registeredTotal - $compliantTotal);

        $stats = [
            'registered_total' => $registeredTotal,
            'accredited_total' => $accreditedTotal,
            'compliant_total' => $compliantTotal,
            'non_compliant_total' => $nonCompliantTotal,
            'compliance_percentage' => $registeredTotal > 0 ? round(($compliantTotal / $registeredTotal) * 100, 1) : 0,
            'non_compliance_percentage' => $registeredTotal > 0 ? round(($nonCompliantTotal / $registeredTotal) * 100, 1) : 0,
            'expiring_total' => (clone $registrations)->where('status', 'active')->whereBetween('expires_at', [$today, $reviewWindow])->count(),
            'registrations_expired' => (clone $registrations)->whereDate('expires_at', '<', $today)->count(),
            'registrations_due' => (clone $registrations)->whereBetween('expires_at', [$today, $reviewWindow])->count(),
            'accreditations_expired' => (clone $accreditations)->whereDate('expires_at', '<', $today)->count(),
            'accreditations_due' => (clone $accreditations)->whereBetween('expires_at', [$today, $reviewWindow])->count(),
            'levies_pending' => (clone $unpaidLevies)->count(),
        ];

        $registrationRecords = (clone $registrations)
            ->whereDate('expires_at', '<=', $reviewWindow)
            ->orderBy('expires_at')
            ->simplePaginate(10, ['*'], 'registration_page');

        $accreditationRecords = (clone $accreditations)
            ->whereDate('expires_at', '<=', $reviewWindow)
            ->orderBy('expires_at')
            ->simplePaginate(10, ['*'], 'accreditation_page');

        $levies = (clone $unpaidLevies)
            ->latest('created_at')
            ->simplePaginate(10, ['*'], 'levy_page');

        return view('staff.registration_compliance.dashboard', compact(
            'stats', 'registrationRecords', 'accreditationRecords', 'levies'
        ));
    }

    public function analytics(Request $request)
    {
        $today = Carbon::today();
        $from = $request->date('from')?->startOfDay() ?? $today->copy()->startOfYear();
        $to = $request->date('to')?->endOfDay() ?? $today->copy()->endOfDay();
        if ($from->greaterThan($to)) [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];

        $registrationQuery = RegistrationRecord::query()->whereBetween('created_at', [$from, $to]);
        $accreditationQuery = AccreditationRecord::query()->whereBetween('created_at', [$from, $to]);
        $levyQuery = LevyRecord::query()->whereBetween('created_at', [$from, $to]);

        $months = [];
        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($to)) {
            $monthEnd = $cursor->copy()->endOfMonth();
            $months[] = [
                'label' => $cursor->format('M Y'),
                'registrations' => (clone $registrationQuery)->whereBetween('created_at', [$cursor, $monthEnd])->count(),
                'accreditations' => (clone $accreditationQuery)->whereBetween('created_at', [$cursor, $monthEnd])->count(),
                'levies' => (clone $levyQuery)->whereBetween('created_at', [$cursor, $monthEnd])->count(),
            ];
            $cursor->addMonth();
        }

        $today = Carbon::today();
        $registeredTotal = RegistrationRecord::where('status', 'active')->count();
        $compliantTotal = RegistrationRecord::where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today);
            })->count();

        $metrics = [
            'registered_total' => $registeredTotal,
            'accredited_total' => AccreditationRecord::where('status', 'active')->count(),
            'compliant_total' => $compliantTotal,
            'non_compliant_total' => max(0, $registeredTotal - $compliantTotal),
            'expiring_total' => RegistrationRecord::where('status', 'active')->whereBetween('expires_at', [$today, $today->copy()->addDays(90)])->count(),
            'levies_pending' => LevyRecord::whereNotIn('payment_status', ['paid', 'verified', 'successful', 'completed'])->count(),
        ];

        if ($request->boolean('export')) {
            return response()->streamDownload(function () use ($months, $metrics, $from, $to) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Registration and Accreditation Compliance Report']);
                fputcsv($handle, ['Period', $from->format('Y-m-d') . ' to ' . $to->format('Y-m-d')]);
                fputcsv($handle, []);
                fputcsv($handle, ['Metric', 'Value']);
                foreach ($metrics as $metric => $value) fputcsv($handle, [ucwords(str_replace('_', ' ', $metric)), $value]);
                fputcsv($handle, []);
                fputcsv($handle, ['Month', 'Registrations', 'Accreditations', 'Levies']);
                foreach ($months as $month) fputcsv($handle, [$month['label'], $month['registrations'], $month['accreditations'], $month['levies']]);
                fclose($handle);
            }, 'zmc-compliance-report-' . now()->format('Ymd-His') . '.csv', ['Content-Type' => 'text/csv']);
        }

        return view('staff.registration_compliance.analytics', compact('months', 'metrics', 'from', 'to'));
    }

    public function registers(string $type)
    {
        if ($type === 'media_houses') {
            $records = RegistrationRecord::with('contact:id,name,email')->latest()->paginate(20);
            return view('staff.registration_compliance.registers', ['type' => $type, 'records' => $records]);
        }

        $query = AccreditationRecord::with('holder:id,name,email')->latest();
        if ($type === 'foreign_practitioners') $query->where(function ($q) { $q->whereNotNull('passport_number')->orWhere('nationality', '!=', 'Zimbabwean'); });
        if ($type === 'local_practitioners') $query->where(function ($q) { $q->whereNull('passport_number')->orWhere('nationality', 'Zimbabwean'); });
        $records = $query->paginate(20);
        return view('staff.registration_compliance.registers', ['type' => $type, 'records' => $records]);
    }

    public function complianceList(string $type)
    {
        $today = Carbon::today();
        $window = $today->copy()->addDays(90);
        $query = RegistrationRecord::with('contact:id,name,email')->latest('expires_at');
        $title = 'Compliance records';
        if ($type === 'expiring' || $type === 'reminders') {
            $query->where('status', 'active')->whereBetween('expires_at', [$today, $window]);
            $title = $type === 'reminders' ? 'Reminders and notices' : 'Renewals and expiries';
        } elseif ($type === 'non_compliant') {
            $query->where(function ($q) use ($today) { $q->whereIn('status', ['suspended', 'revoked', 'expired'])->orWhereDate('expires_at', '<', $today); });
            $title = 'Non-compliant cases';
        } elseif ($type === 'compliant') {
            $query->where('status', 'active')->where(function ($q) use ($today) { $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today); });
            $title = 'Compliant cases';
        } elseif ($type === 'inspections') {
            $query->where('status', 'active')->whereBetween('expires_at', [$today, $window]);
            $title = 'Inspections and enforcement';
        }
        $records = $query->paginate(20);
        return view('staff.registration_compliance.compliance_list', compact('records', 'title', 'type'));
    }

    public function levies()
    {
        $levies = LevyRecord::with('user:id,name,email')->latest()->paginate(20);
        return view('staff.registration_compliance.levies', compact('levies'));
    }

    public function settings()
    {
        return view('staff.registration_compliance.settings');
    }

    public function sendBulkNotification(Request $request)
    {
        $data = $request->validate([
            'recipient_type' => ['required', 'in:media_houses,local_practitioners,foreign_practitioners'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:10000'],
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['integer'],
        ]);

        $emails = $data['recipient_type'] === 'media_houses'
            ? RegistrationRecord::whereIn('id', $data['recipient_ids'])->with('contact:id,email')->get()->map(fn ($record) => $record->email ?: $record->contact?->email)
            : AccreditationRecord::whereIn('id', $data['recipient_ids'])->with('holder:id,email')->get()->map(fn ($record) => $record->email ?: $record->holder?->email);
        $sent = 0;
        foreach ($emails->filter()->unique() as $email) {
            try {
                Mail::raw($data['message'], fn ($mail) => $mail->to($email)->subject($data['subject']));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
            }
        }
        return back()->with($sent ? 'success' : 'error', $sent ? "Notification sent to {$sent} recipient(s)." : 'No recipient emails could be sent.');
    }
}
