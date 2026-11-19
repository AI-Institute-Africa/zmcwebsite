<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\SystemConfig;
use App\Support\MasterSettings;
use App\Support\FileType;
use App\Models\LoginActivity;
use App\Models\User;
use App\Models\RegionalOffice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Super Admin configuration console.
 *
 * NOTE: This is intentionally lightweight and uses the system_configs table
 * for flexible key/value storage.
 */
class SuperAdminConfigController extends Controller
{
    /**
     * Master settings console (all portals & dashboards).
     */
    public function masterSettings(Request $request)
    {
        $cfg = MasterSettings::all();

        if ($request->isMethod('post')) {
            // Only accept keys that exist in defaults to prevent unbounded injection.
            $defaults = MasterSettings::defaults();
            $incoming = $request->except(['_token']);
            $request->validate([
                'website_content.media_hub_url' => ['nullable', 'url', 'max:2048'],
            ]);

            // Branding files
            if ($request->hasFile('general.logo_light')) {
                $file = $request->file('general.logo_light');
                $cfg['general']['logo_light_path'] = $file->storeAs('branding', FileType::safeHashName($file));
            }
            if ($request->hasFile('general.logo_dark')) {
                $file = $request->file('general.logo_dark');
                $cfg['general']['logo_dark_path'] = $file->storeAs('branding', FileType::safeHashName($file));
            }
            if ($request->hasFile('general.favicon')) {
                $file = $request->file('general.favicon');
                $cfg['general']['favicon_path'] = $file->storeAs('branding', FileType::safeHashName($file));
            }
            if ($request->hasFile('system_settings.branding.seal')) {
                $file = $request->file('system_settings.branding.seal');
                $cfg['system_settings']['branding']['seal_path'] = $file->storeAs('branding', FileType::safeHashName($file));
            }

            // Merge posted values into config (bounded by defaults)
            $cfg = $this->mergeBounded($cfg, $incoming, $defaults);

            // Normalize booleans
            $cfg['general']['maintenance_mode'] = (bool) data_get($incoming, 'general.maintenance_mode', false);
            $cfg['general']['public_portal_enabled'] = (bool) data_get($incoming, 'general.public_portal_enabled', false);
            $cfg['general']['staff_portals_enabled'] = (bool) data_get($incoming, 'general.staff_portals_enabled', false);
            $cfg['auth_security']['otp_on_login'] = (bool) data_get($incoming, 'auth_security.otp_on_login', false);
            $cfg['auth_security']['ip_logging'] = (bool) data_get($incoming, 'auth_security.ip_logging', false);
            $cfg['portal_specific']['public']['captcha_enabled'] = (bool) data_get($incoming, 'portal_specific.public.captcha_enabled', false);

            // Categories from textareas (newline separated)
            $cfg['content_management']['news_categories'] = $this->lines((string) data_get($incoming, 'content_management.news_categories_text', implode("\n", $cfg['content_management']['news_categories'] ?? [])));
            $cfg['content_management']['notice_categories'] = $this->lines((string) data_get($incoming, 'content_management.notice_categories_text', implode("\n", $cfg['content_management']['notice_categories'] ?? [])));
            $cfg['content_management']['event_categories'] = $this->lines((string) data_get($incoming, 'content_management.event_categories_text', implode("\n", $cfg['content_management']['event_categories'] ?? [])));

            // Save
            MasterSettings::set($cfg);

            return back()->with('success', 'Master settings saved.');
        }

        return view('admin/system/master_settings', [
            'cfg' => $cfg,
            'defaults' => MasterSettings::defaults(),
        ]);
    }

    private function mergeBounded(array $current, array $incoming, array $defaults): array
    {
        foreach ($defaults as $key => $defVal) {
            if (!array_key_exists($key, $incoming)) {
                continue;
            }

            if (is_array($defVal) && is_array($incoming[$key] ?? null)) {
                $current[$key] = $this->mergeBounded($current[$key] ?? [], $incoming[$key], $defVal);
            } else {
                $current[$key] = $incoming[$key];
            }
        }
        return $current;
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text))));
    }
    public function loginActivity(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $failedLogins = LoginActivity::query()
            ->where('login_successful', false)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function($sq) use ($q) {
                    $sq->where('account_name', 'like', "%{$q}%")
                       ->orWhere('ip_address', 'like', "%{$q}%")
                       ->orWhere('device_identifier', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(25, ['*'], 'failed');

        $lastLogins = LoginActivity::query()
            ->where('login_successful', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function($sq) use ($q) {
                    $sq->where('account_name', 'like', "%{$q}%")
                       ->orWhere('ip_address', 'like', "%{$q}%")
                       ->orWhere('device_identifier', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(25, ['*'], 'logins');

        // Last active session approximation: last successful login entry per user in last 30 days.
        $lastActive = LoginActivity::query()
            ->selectRaw('user_id, MAX(login_at) as last_seen')
            ->where('login_successful', true)
            ->where('login_at', '>=', now()->subDays(30))
            ->groupBy('user_id')
            ->orderByDesc('last_seen')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                return [
                    'user' => User::find($row->user_id),
                    'last_seen' => $row->last_seen,
                ];
            });

        return view('admin/users/login_activity', compact('failedLogins', 'lastLogins', 'lastActive', 'q'));
    }

    public function workflowConfig(Request $request)
    {
        $defaults = config('zmc.workflow');
        $stored = SystemConfig::getValue('workflow', $defaults);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'sla_hours' => 'array',
                'sla_hours.*' => 'nullable|integer|min:1|max:720',
                'escalations' => 'array',
                'escalations.*.after_hours' => 'nullable|integer|min:1|max:720',
                'escalations.*.notify_roles' => 'nullable|string',
            ]);

            $stored['sla_hours'] = $data['sla_hours'] ?? ($stored['sla_hours'] ?? []);
            $stored['escalations'] = $data['escalations'] ?? ($stored['escalations'] ?? []);
            SystemConfig::setValue('workflow', $stored);

            return back()->with('success', 'Workflow settings updated.');
        }

        $stages = [
            'Officer' => ['submitted', 'officer_review'],
            'Accounts' => ['accounts_review'],
            'Registrar' => ['registrar_review'],
            'Production' => ['production_queue'],
        ];

        return view('admin/system/workflow_config', [
            'workflow' => $stored,
            'defaults' => $defaults,
            'stages' => $stages,
        ]);
    }

    public function feesConfig(Request $request)
    {
        $defaults = config('zmc.fees');
        $cfg = SystemConfig::getValue('fees', $defaults);

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'fees' => 'array',
                'fees.*.name' => 'required|string|max:120',
                'fees.*.amount' => 'required|numeric|min:0',
                'fees.*.currency' => 'required|string|max:10',
                'fees.*.active' => 'nullable|boolean',
                'payment_channels' => 'array',
                'payment_channels.*.name' => 'required|string|max:80',
                'payment_channels.*.active' => 'nullable|boolean',
                'waiver_rules' => 'nullable|string|max:2000',
                'tax' => 'array',
                'tax.vat_percent' => 'nullable|numeric|min:0|max:100',
            ]);

            $cfg['fees'] = array_values($data['fees'] ?? []);
            $cfg['payment_channels'] = array_values($data['payment_channels'] ?? []);
            $cfg['waiver_rules'] = $data['waiver_rules'] ?? '';
            $cfg['tax'] = $data['tax'] ?? [];

            SystemConfig::setValue('fees', $cfg);
            return back()->with('success', 'Fees & payment settings saved.');
        }

        // Read-only reconciliation snapshot from applications
        $recon = [
            'pending' => Application::whereIn('payment_status', ['pending', 'awaiting_confirmation'])->count(),
            'paid' => Application::where('payment_status', 'paid')->count(),
            'failed' => Application::where('payment_status', 'failed')->count(),
        ];

        return view('admin/system/fees_config', compact('cfg', 'defaults', 'recon'));
    }

    public function templates(Request $request)
    {
        $cfg = SystemConfig::getValue('templates', config('zmc.templates'));

        if ($request->isMethod('post')) {
            $request->validate([
                'type' => 'required|string|max:50',
                'file' => 'required|file|max:5120',
                'label' => 'nullable|string|max:120',
            ]);

            $type = $request->string('type');
            $label = $request->string('label')->toString() ?: strtoupper($type) . ' Template';

            $file = $request->file('file');
            $path = $file->storeAs("templates/{$type}", FileType::safeHashName($file));
            $version = now()->format('YmdHis');

            $cfg['items'][$type]['versions'][] = [
                'version' => $version,
                'label' => $label,
                'path' => $path,
                'uploaded_at' => now()->toDateTimeString(),
                'uploaded_by' => auth()->id(),
            ];
            $cfg['items'][$type]['active_version'] = $version;

            SystemConfig::setValue('templates', $cfg);
            return back()->with('success', 'Template uploaded and set active.');
        }

        return view('admin/system/templates_config', compact('cfg'));
    }

    public function contentControl(Request $request)
    {
        $cfg = SystemConfig::getValue('content_control', config('zmc.content_control'));

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'modules' => 'array',
                'modules.notices' => 'nullable|boolean',
                'modules.news' => 'nullable|boolean',
                'modules.events' => 'nullable|boolean',
                'categories' => 'nullable|string|max:2000',
                'moderation_rules' => 'nullable|string|max:3000',
            ]);

            $cfg['modules'] = [
                'notices' => (bool)($data['modules']['notices'] ?? false),
                'news' => (bool)($data['modules']['news'] ?? false),
                'events' => (bool)($data['modules']['events'] ?? false),
            ];

            $cfg['categories'] = array_values(array_filter(array_map('trim', explode("\n", (string)($data['categories'] ?? '')))));
            $cfg['moderation_rules'] = (string)($data['moderation_rules'] ?? '');

            SystemConfig::setValue('content_control', $cfg);
            return back()->with('success', 'Content control settings saved.');
        }

        return view('admin/system/content_control', compact('cfg'));
    }

    public function regionsOffices(Request $request)
    {
        if (!RegionalOffice::exists()) {
            $legacy = SystemConfig::getValue('regions_offices', config('zmc.regions_offices'));
            foreach ((array) data_get($legacy, 'offices', []) as $index => $office) {
                if (empty($office['code']) || empty($office['name'])) {
                    continue;
                }

                $record = RegionalOffice::firstOrCreate(
                    ['code' => strtoupper((string) $office['code'])],
                    [
                        'name' => $office['name'],
                        'region' => $office['region'] ?? null,
                        'schedule' => $office['schedule'] ?? null,
                        'display_order' => $index,
                        'is_published' => true,
                    ]
                );
                $staffIds = array_values(array_filter(array_map('intval', (array) ($office['assigned_user_ids'] ?? []))));
                $record->staff()->sync(User::where('account_type', 'staff')->whereIn('id', $staffIds)->pluck('id'));
            }
        }

        if ($request->isMethod('post')) {
            $data = $request->validate([
                'offices' => 'required|array|min:1',
                'offices.*.name' => 'required|string|max:120',
                'offices.*.code' => 'required|string|max:20',
                'offices.*.region' => 'nullable|string|max:80',
                'offices.*.address' => 'nullable|string|max:500',
                'offices.*.phone' => 'nullable|string|max:80',
                'offices.*.email' => 'nullable|email|max:190',
                'offices.*.schedule' => 'nullable|string|max:200',
                'offices.*.assigned_user_ids' => 'nullable|string|max:2000',
                'offices.*.is_published' => 'nullable|boolean',
            ]);

            DB::transaction(function () use ($data) {
                $savedCodes = [];
                foreach ($data['offices'] as $index => $officeData) {
                    $code = strtoupper(trim($officeData['code']));
                    $savedCodes[] = $code;
                    $office = RegionalOffice::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => $officeData['name'],
                            'region' => $officeData['region'] ?? null,
                            'address' => $officeData['address'] ?? null,
                            'phone' => $officeData['phone'] ?? null,
                            'email' => $officeData['email'] ?? null,
                            'schedule' => $officeData['schedule'] ?? null,
                            'is_published' => (bool) ($officeData['is_published'] ?? false),
                            'display_order' => $index,
                        ]
                    );

                    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($officeData['assigned_user_ids'] ?? ''))))));
                    $validStaffIds = User::where('account_type', 'staff')->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
                    if (count($validStaffIds) !== count($ids)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "offices.{$index}.assigned_user_ids" => 'Only existing staff accounts may be assigned to an office.',
                        ]);
                    }
                    $office->staff()->sync($validStaffIds);
                }

                RegionalOffice::whereNotIn('code', $savedCodes)->delete();
            });

            return back()->with('success', 'Offices saved.');
        }

        // Region performance metrics from applications (if your applications have region/office fields)
        $regionColumn = null;
        foreach (['region', 'collection_region', 'office_region'] as $candidate) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('applications', $candidate)) {
                $regionColumn = $candidate;
                break;
            }
        }

        $perf = collect();
        if ($regionColumn) {
            $perf = Application::query()
                ->selectRaw("COALESCE({$regionColumn}, 'Unknown') as region, COUNT(*) as total")
                ->groupBy($regionColumn)
                ->orderByDesc('total')
                ->limit(20)
                ->get();
        }

        $staff = User::where('account_type', 'staff')->orderBy('name')->get(['id', 'name', 'email']);
        $offices = RegionalOffice::with('staff')->orderBy('display_order')->orderBy('name')->get();
        $cfg = ['offices' => $offices->map(fn ($office) => [
            'id' => $office->id,
            'name' => $office->name,
            'code' => $office->code,
            'region' => $office->region,
            'address' => $office->address,
            'phone' => $office->phone,
            'email' => $office->email,
            'schedule' => $office->schedule,
            'is_published' => $office->is_published,
            'assigned_user_ids' => $office->staff->pluck('id')->all(),
        ])->all()];

        return view('admin/system/regions_offices', compact('cfg', 'perf', 'staff'));
    }

    public function systemSettings(Request $request)
    {
        // Backwards-compatible endpoint: forward to Master Settings.
        return $this->masterSettings($request);
    }

    public function reports(Request $request)
    {
        $from = $request->date('from') ?? now()->subDays(30);
        $to = $request->date('to') ?? now();

        $byStage = Application::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $byType = Application::query()
            ->selectRaw('application_type, COUNT(*) as total')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('application_type')
            ->orderByDesc('total')
            ->get();

        return view('admin/reports/index', compact('from', 'to', 'byStage', 'byType'));
    }
}
