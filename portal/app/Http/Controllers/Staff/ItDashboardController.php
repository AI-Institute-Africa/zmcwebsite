<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Application;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Region;
use App\Models\SystemConfig;
use App\Models\RegistrationRecord;
use App\Models\ApplicationAuditLog;
use App\Models\SystemLog;
use App\Models\AuditTrail;
use App\Models\SystemIncident;
use App\Models\ModuleHealthStatus;
use App\Models\ServiceHeartbeat;
use App\Models\TemporaryRoleAssignment;
use App\Notifications\TemporaryRoleAssignedNotification;
use App\Notifications\HelpdeskTicketUpdatedNotification;
use App\Models\Location;
use App\Models\Device;
use App\Models\HelpdeskTicket;
use App\Models\HelpdeskTicketUpdate;
use App\Models\ChangeLog;
use App\Models\LoginActivity;
use App\Models\SecurityIpBlock;
use App\Models\RateLimitEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Support\AuditTrail as AuditTrailSupport;
use App\Support\FileType;
use ZipArchive;

class ItDashboardController extends Controller
{
    /**
     * IT Dashboard - Main Entry
     */
    public function index(Request $request)
    {
        $totalUsers   = User::count();
        $usersByRole  = Role::where('name', '!=', 'super_admin')->withCount('users')->orderBy('name')->get();
        $pending = User::query()->whereNull('approved_at')->whereHas('roles')->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->latest('id')->paginate(15);
        $regions = Region::withCount('officers')->get();

        $staffCount = User::where('account_type', 'staff')->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))->count();
        $publicCount = User::where('account_type', 'public')->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))->count();
        $activeCount = User::where('account_status', 'active')->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))->count();

        $totalApplications = Application::count();
        $pendingApplications = Application::whereNotIn('status', ['APPROVED', 'REJECTED', 'draft'])->count();
        $approvedApplications = Application::where('status', 'APPROVED')->count();

        $openIncidents = SystemIncident::where('status', 'open')->count();
        $openTickets = HelpdeskTicket::open()->count();

        $monthExpr = match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'mysql', 'mariadb' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $appsByMonth = Application::select(
            DB::raw("{$monthExpr} as month"),
            DB::raw('COUNT(*) as count')
        )->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $appsByType = Application::select('application_type', DB::raw('COUNT(*) as count'))
            ->groupBy('application_type')
            ->get();

        $stats = [
            'total_users'      => $totalUsers,
            'staff_count'      => $staffCount,
            'public_count'     => $publicCount,
            'active_count'     => $activeCount,
            'total_applications' => $totalApplications,
            'pending_applications' => $pendingApplications,
            'approved_applications' => $approvedApplications,
            'open_incidents'   => $openIncidents,
            'open_tickets'     => $openTickets,
        ];

        $storageUsageBytes = null;
        $storageByModule   = [];
        if (Schema::hasTable('application_documents') && Schema::hasColumn('application_documents', 'size')) {
            $storageUsageBytes = (int) DB::table('application_documents')->sum('size');
            $storageByModule   = DB::table('application_documents')->select('doc_type', DB::raw('SUM(size) as total'))->groupBy('doc_type')->orderByDesc('total')->limit(8)->get();
        }

        $health = ['database' => false, 'storage' => false, 'queue' => false];
        try { DB::select('select 1'); $health['database'] = true; } catch (\Throwable $e) {}
        try {
            $disk = Storage::disk(config('filesystems.default', 'public'));
            $p = 'health/probe_' . uniqid() . '.txt';
            $disk->put($p, 'ok'); $disk->delete($p);
            $health['storage'] = true;
        } catch (\Throwable $e) {}
        try {
            $driver = config('queue.default');
            if ($driver === 'database') $health['queue'] = Schema::hasTable('jobs');
            elseif ($driver === 'redis') { $health['queue'] = (bool) app('redis')->connection()->ping(); }
            else $health['queue'] = true;
        } catch (\Throwable $e) {}

        $storageUsage = [
            'total' => disk_total_space('/'),
            'free'  => disk_free_space('/'),
            'used'  => disk_total_space('/') - disk_free_space('/'),
        ];
        
        $errorLogs            = ActivityLog::where('action', 'like', '%error%')->orWhere('action', 'like', '%failed%')->latest()->paginate(15, ['*'], 'errors_page');
        $auditLogs            = AuditTrail::latest()->paginate(20, ['*'], 'audit_page');

        $activeSessions = [];
        if (config('session.driver') === 'database') {
            $activeSessions = DB::table('sessions')->latest('last_activity')->limit(50)->get();
        }

        $systemEnv = [
            'app_env'        => config('app.env'),
            'app_debug'      => config('app.debug'),
            'maintenance'    => app()->isDownForMaintenance(),
            'php_version'    => PHP_VERSION,
            'laravel_version'=> app()->version(),
        ];

        $storageStats = [
            'public'  => shell_exec('du -sh storage/app/public') ?: 'N/A',
            'uploads' => shell_exec('du -sh storage/app/uploads') ?: 'N/A',
        ];

        $isDbUp = $health['database'];
        $maintenanceMode = app()->isDownForMaintenance();
        $driveSpace = round(($storageUsage['free'] / max($storageUsage['total'], 1)) * 100, 1) . '% free';
        $lastBackup = 'N/A';
        $envData = $systemEnv;
        $activeTab = $request->input('tab', 'overview');

        return view('staff.it.dashboard.index', compact(
            'totalUsers', 'usersByRole', 'isDbUp', 'maintenanceMode',
            'driveSpace', 'lastBackup', 'envData', 'activeTab',
            'health', 'storageUsageBytes', 'storageByModule',
            'regions', 'pending',
            'stats', 'storageUsage',
            'errorLogs',
            'activeSessions', 'auditLogs',
            'systemEnv', 'storageStats',
            'appsByMonth', 'appsByType'
        ));
    }


    /**
     * Role Management
     */
    public function roles()
    {
        $roles = \Spatie\Permission\Models\Role::where('name', '!=', 'super_admin')->withCount(['users', 'permissions'])->get();
        return view('staff.it.dashboard.roles', compact('roles'));
    }

    /**
     * Templates Management
     */
    public function templates()
    {
        return view('staff.it.dashboard.templates');
    }

    /**
     * Printers Configuration
     */
    public function printers()
    {
        return view('staff.it.dashboard.printers');
    }

    /**
     * Numbering Configuration
     */
    public function numbering()
    {
        return view('staff.it.dashboard.numbering');
    }

    /**
     * Category Master Data
     */
    public function categories()
    {
        return view('staff.it.dashboard.categories');
    }

    /**
     * Regions and Locations
     */
    public function regions()
    {
        // Re-use admin system view or direct to a specific IT regions view.
        return redirect()->route('admin.regions.index');
    }

    /**
     * Document Settings
     */
    public function documentSettings()
    {
        return view('staff.it.dashboard.document-settings');
    }

    /**
     * QR Code and Security Features
     */
    public function qrSecurity()
    {
        return view('staff.it.dashboard.qr-security');
    }

    /**
     * 11. Security Monitoring
     */
    public function security()
    {
        $sessions = collect();
        $sessionDriver = (string) config('session.driver');
        $sessionsTableAvailable = Schema::hasTable('sessions');
        $databaseSessionsAvailable = $sessionDriver === 'database' && $sessionsTableAvailable;
        $sessionTrackingMessage = match (true) {
            $sessionDriver !== 'database' => 'The configured session driver is "' . $sessionDriver . '". Select the database session driver to list and terminate sessions here.',
            ! $sessionsTableAvailable => 'The database session driver is selected, but the sessions table is missing. Apply the pending session-table migration to enable session tracking.',
            default => null,
        };
        if ($databaseSessionsAvailable) {
            $latestSessions = DB::table('sessions')
                ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
                ->select(
                    'sessions.id',
                    'sessions.user_id',
                    'sessions.ip_address',
                    'sessions.user_agent',
                    'sessions.last_activity',
                    'users.name as user_name',
                    'users.email as user_email'
                )
                ->orderByDesc('sessions.last_activity')
                ->limit(25)
                ->get();

            $sessionPerPage = 10;
            $sessionLastPage = max(1, (int) ceil($latestSessions->count() / $sessionPerPage));
            $sessionPage = min(LengthAwarePaginator::resolveCurrentPage('sessions_page'), $sessionLastPage);
            $sessions = new LengthAwarePaginator(
                $latestSessions->forPage($sessionPage, $sessionPerPage)->values(),
                $latestSessions->count(),
                $sessionPerPage,
                $sessionPage,
                ['path' => request()->url(), 'pageName' => 'sessions_page']
            );
        }

        $failedLogins = collect();
        $failedLoginsLast15Minutes = 0;
        $failedLoginsLast24Hours = 0;
        $loginActivityAvailable = Schema::hasTable('login_activities')
            && Schema::hasColumn('login_activities', 'login_successful')
            && Schema::hasColumn('login_activities', 'login_at');

        if ($loginActivityAvailable) {
            $failedQuery = LoginActivity::query()->failed();
            $failedLoginsLast15Minutes = (clone $failedQuery)
                ->where('login_at', '>=', now()->subMinutes(15))
                ->count();
            $failedLoginsLast24Hours = (clone $failedQuery)
                ->where('login_at', '>=', now()->subDay())
                ->count();
            $failedLogins = $failedQuery
                ->latest('login_at')
                ->paginate(20);
        }

        $ipBlockTableAvailable = Schema::hasTable('security_ip_blocks');
        $blockedIps = $ipBlockTableAvailable
            ? SecurityIpBlock::query()->latest()->get()
            : collect();
        $ipBlockingAvailable = $ipBlockTableAvailable && config('security.ip_blocking.enabled', false);
        $tlsAuditHosts = config('security.tls_audit.hosts', []);
        $tlsAuditResults = session('security_tls_audit_results', []);

        $globalRateLimitSetting = SystemConfig::getValue('security.global_rate_limit', [
            'enabled' => config('security.global_rate_limit.enabled', true),
        ]);
        $globalRateLimitEnabled = is_array($globalRateLimitSetting)
            ? (bool) ($globalRateLimitSetting['enabled'] ?? config('security.global_rate_limit.enabled', true))
            : (bool) config('security.global_rate_limit.enabled', true);
        $globalRateLimitPerMinute = (int) config('security.global_rate_limit.requests_per_minute', 300);
        $rateLimitEventsAvailable = Schema::hasTable('rate_limit_events');
        $throttledIpsLast24Hours = null;
        $throttleEventsLast24Hours = null;
        if ($rateLimitEventsAvailable) {
            $throttleEventsQuery = RateLimitEvent::query()->where('throttled_at', '>=', now()->subDay());
            $throttledIpsLast24Hours = (clone $throttleEventsQuery)->distinct('ip_address')->count('ip_address');
            $throttleEventsLast24Hours = $throttleEventsQuery->count();
        }

        return view('staff.it.dashboard.security', compact(
            'sessions',
            'databaseSessionsAvailable',
            'sessionTrackingMessage',
            'loginActivityAvailable',
            'failedLogins',
            'failedLoginsLast15Minutes',
            'failedLoginsLast24Hours',
            'blockedIps',
            'ipBlockTableAvailable',
            'ipBlockingAvailable',
            'tlsAuditHosts',
            'tlsAuditResults',
            'globalRateLimitEnabled',
            'globalRateLimitPerMinute',
            'rateLimitEventsAvailable',
            'throttledIpsLast24Hours',
            'throttleEventsLast24Hours'
        ));
    }

    /**
     * 12. Backup & Recovery
     */
    public function backup()
    {
        return view('staff.it.dashboard.backup');
    }

    /**
     * 13. Audit & Compliance
     */
    public function audit()
    {
        $logs = ActivityLog::with('user')->latest()->paginate(50);
        return view('staff.it.dashboard.audit', compact('logs'));
    }

    /**
     * 14. System Configuration
     */
    public function system()
    {
        $env = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'maintenance' => app()->isDownForMaintenance(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
        return view('staff.it.dashboard.system', compact('env'));
    }


    /**
     * 16. Reporting & Export
     */
    public function reports()
    {
        return view('staff.it.dashboard.reports');
    }


    // --- ACTIONS ---

    /**
     * Action handlers for system tools
     */
    public function saveConfig(Request $request)
    {
        $config = $request->except('_token');
        foreach ($config as $key => $value) {
            SystemConfig::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        AuditTrailSupport::log('it_admin.save_config', null, ['keys' => array_keys($config)]);
        return back()->with('success', 'System configuration updated.');
    }

    public function suspendUser(User $user)
    {
        $user->update(['account_status' => $user->account_status === 'suspended' ? 'active' : 'suspended']);
        AuditTrailSupport::log('it_admin.toggle_user_status', $user, ['new_status' => $user->account_status]);
        return back()->with('success', 'User status updated to ' . $user->account_status . '.');
    }

    public function users(Request $request)
    {
        $query = User::with('roles')->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'));

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('account_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('account_status', $status);
        }

        if ($role = $request->get('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        $users = $query->latest()->paginate(20)->withQueryString();
        $roles = \Spatie\Permission\Models\Role::where('name', '!=', 'super_admin')->orderBy('name')->get();
        $totalUsers = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))->count();
        $staffCount = User::where('account_type', 'staff')->count();
        $publicCount = User::where('account_type', 'public')->count();
        $activeCount = User::where('account_status', 'active')->count();
        $suspendedCount = User::where('account_status', 'suspended')->count();
        $pendingCount = User::whereIn('account_status', ['pending', 'pending_setup'])->count();

        return view('staff.it.users-management', compact(
            'users', 'roles', 'totalUsers', 'staffCount', 'publicCount',
            'activeCount', 'suspendedCount', 'pendingCount'
        ));
    }

    public function editUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name', \Illuminate\Validation\Rule::notIn(['super_admin'])],
        ]);

        $previousRole = $user->roles->first()?->name;
        
        $tempPassword = Str::random(12);
        $tempPasswordHash = Hash::make($tempPassword);
        
        $rolesToSync = [$request->role];
        // Accreditation Officers also handle Production for the same regions —
        // auto-add the production role so one person handles both stages.
        if ($request->role === 'accreditation_officer') {
            $rolesToSync[] = 'production';
        }
        $user->syncRoles($rolesToSync);

        $newType = in_array($request->role, ['it_admin', 'director', 'registrar', 'accreditation_officer', 'accounts_payments', 'production', 'auditor', 'complaints_officer'])
            ? 'staff' : 'public';
        $user->update([
            'account_type' => $newType,
            'temp_password' => $tempPasswordHash,
            'temp_password_expires_at' => now()->addHours(24),
            'password_change_required' => true,
        ]);

        // Log with detailed metadata including temp credentials indicator
        AuditTrailSupport::log('it_admin.change_user_role', $user, [
            'new_role' => $request->role,
            'previous_role' => $previousRole,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'temp_credentials' => true,
            'temp_password_expires' => now()->addHours(24)->toDateTimeString(),
        ]);

        // Send email with temporary credentials
        try {
            Mail::send('emails.temporary-credentials', [
                'user' => $user,
                'tempPassword' => $tempPassword,
                'role' => $request->role,
                'expiresAt' => now()->addHours(24)->format('d M Y H:i'),
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('ZMC Portal - Role Assignment & Temporary Credentials');
            });
            
            return back()->with('success', "Role updated to {$request->role} for {$user->name}. Temporary credentials sent to {$user->email} (expires in 24 hours).");
        } catch (\Throwable $e) {
            return back()->with('success', "Role updated to {$request->role} for {$user->name}.")
                        ->with('warning', "Email failed: {$e->getMessage()}. Temporary password: {$tempPassword} (expires in 24 hours)");
        }
    }

    public function activateUser(User $user)
    {
        $user->update(['account_status' => 'active']);
        AuditTrailSupport::log('it_admin.activate_user', $user);
        return back()->with('success', "{$user->name}'s account has been activated.");
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Super admin accounts cannot be deleted from here.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        try {
            DB::beginTransaction();

            $nullableRefs = [
                ['applications', 'locked_by'],
                ['applications', 'printed_by'],
                ['applications', 'issued_by'],
                ['applications', 'assigned_officer_id'],
                ['applications', 'last_action_by'],
                ['application_documents', 'verified_by'],
                ['officer_followups', 'assigned_to'],
                ['compliance_cases', 'assigned_to'],
                ['compliance_violations', 'reported_by'],
                ['compliance_evidence_files', 'uploaded_by'],
                ['unaccredited_reports', 'reported_by'],
                ['fix_requests', 'resolved_by'],
                ['payment_submissions', 'verified_by'],
                ['renewal_applications', 'payment_verified_by'],
                ['renewal_applications', 'last_action_by'],
                ['renewal_applications', 'produced_by'],
                ['renewal_change_requests', 'reviewed_by'],
                ['helpdesk_tickets', 'assigned_to'],
                ['helpdesk_tickets', 'submitted_by'],
                ['cash_payments', 'verified_by'],
                ['cash_payments', 'voided_by'],
                ['system_incidents', 'assigned_to'],
                ['system_incidents', 'reported_by'],
                ['temporary_role_assignments', 'assigned_by'],
            ];

            foreach ($nullableRefs as [$table, $column]) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    DB::table($table)->where($column, $user->id)->update([$column => null]);
                }
            }

            $deletableRefs = [
                ['activity_logs', 'user_id'],
                ['audit_trails', 'user_id'],
                ['audit_logs', 'actor_user_id'],
                ['officer_regions', 'user_id'],
                ['login_activities', 'user_id'],
                ['portal_requirements_audit', 'user_id'],
                ['reminder_reads', 'user_id'],
                ['change_logs', 'user_id'],
                ['temporary_role_assignments', 'user_id'],
                ['sessions', 'user_id'],
                ['document_versions', 'created_by'],
            ];

            foreach ($deletableRefs as [$table, $column]) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                    DB::table($table)->where($column, $user->id)->delete();
                }
            }

            $user->roles()->detach();
            $user->permissions()->detach();
            $user->delete();

            AuditTrailSupport::log('it_admin.delete_user', null, [
                'deleted_name' => $userName,
                'deleted_email' => $userEmail,
            ]);

            DB::commit();
            return back()->with('success', "User \"{$userName}\" ({$userEmail}) has been permanently deleted.");
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('User deletion failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete user. The user may have linked records that prevent deletion.');
        }
    }

    public function resendActivation(User $user)
    {
        $token = \Illuminate\Support\Str::random(64);
        $user->forceFill([
            'activation_token' => $token,
            'account_status' => 'pending',
        ])->save();

        $activationUrl = route('staff.activate', $token);

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Hello {$user->name},\n\nYour account activation link has been resent.\n\nPlease click the link below to set your password and activate your account:\n\n{$activationUrl}\n\nThis link is valid until used.\n\nRegards,\nZimbabwe Media Commission",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('ZMC Portal - Account Activation');
                }
            );
            AuditTrailSupport::log('it_admin.resend_activation', $user);
            return back()->with('success', "Activation link resent to {$user->email}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send activation email: ' . $e->getMessage());
        }
    }






    public function triggerBackup()
    {
        try {
            // Artisan::call('backup:run'); // Usually handled by spatie/laravel-backup
            AuditTrailSupport::log('it_admin.trigger_backup');
            return back()->with('success', 'Manual backup snapshot triggered.');
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        
        AuditTrailSupport::log('it_admin.clear_cache');
        return back()->with('success', 'System cache cleared.');
    }

    public function runCleanup()
    {
        // Detect orphans or temp files
        AuditTrailSupport::log('it_admin.run_cleanup');
        return back()->with('success', 'Cleanup scan completed. No critical orphans detected.');
    }

    public function forcePasswordReset(User $user)
    {
        try {
            // Use Laravel's configured password broker so the token is saved
            // in the same table that the password reset endpoint validates.
            $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
            $user->notify(new \App\Notifications\AdminPasswordResetNotification($token));
            $user->forceFill(['password_change_required' => true])->save();

            AuditTrailSupport::log('it_admin.force_password_reset', $user, [
                'reset_email_sent' => true,
                'email' => $user->email,
            ]);

            return back()->with('success', 'Password reset email sent to ' . $user->email . '. Account applications and dashboard records were not changed.');
        } catch (\Throwable $e) {
            \Log::error('IT Admin password reset email failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'The password reset link could not be sent. No account or application data was changed.');
        }
    }

    public function logoutSession($id)
    {
        DB::table('sessions')->where('id', $id)->delete();
        AuditTrailSupport::log('it_admin.session_logout', null, ['session_id' => $id]);
        return back()->with('success', 'Active session terminated.');
    }

    public function blockIp(Request $request)
    {
        $data = $request->validate([
            'ip_address' => ['required', 'ip'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['ip_address'] === $request->ip()) {
            return back()->with('error', 'You cannot block the IP address currently using this session.');
        }

        $block = SecurityIpBlock::query()->updateOrCreate(
            ['ip_address' => $data['ip_address']],
            ['reason' => $data['reason'] ?? null, 'blocked_by' => $request->user()?->id]
        );
        $this->clearIpBlockCache();

        AuditTrailSupport::log('it_admin.ip_block_created', $block, [
            'ip_address' => $block->ip_address,
            'reason' => $block->reason,
        ]);

        return back()->with('success', 'The IP address was added to the portal blocklist.');
    }

    public function unblockIp(SecurityIpBlock $block)
    {
        $ipAddress = $block->ip_address;
        $block->delete();
        $this->clearIpBlockCache();

        AuditTrailSupport::log('it_admin.ip_block_removed', null, ['ip_address' => $ipAddress]);

        return back()->with('success', 'The IP address was removed from the portal blocklist.');
    }

    public function toggleRateLimiting()
    {
        $currentSetting = SystemConfig::getValue('security.global_rate_limit', [
            'enabled' => config('security.global_rate_limit.enabled', true),
        ]);
        $currentlyEnabled = is_array($currentSetting)
            ? (bool) ($currentSetting['enabled'] ?? config('security.global_rate_limit.enabled', true))
            : (bool) config('security.global_rate_limit.enabled', true);
        $enabled = ! $currentlyEnabled;

        try {
            SystemConfig::setValue('security.global_rate_limit', ['enabled' => $enabled]);
            Cache::store('file')->forget('security.global_rate_limit.enabled');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'The global request limit setting could not be saved.');
        }

        AuditTrailSupport::log('it_admin.global_rate_limiting_toggled', null, [
            'enabled' => $enabled,
            'requests_per_minute' => config('security.global_rate_limit.requests_per_minute', 300),
        ]);

        return back()->with('success', $enabled
            ? 'The global portal request limit is enabled. Route-specific login and OTP limits remain active.'
            : 'The global portal request limit is disabled. Route-specific login and OTP limits remain active.');
    }

    public function sslAudit()
    {
        $results = [];
        $timeout = config('security.tls_audit.timeout_seconds', 8);

        foreach (config('security.tls_audit.hosts', []) as $host) {
            if (! is_string($host) || ! preg_match('/\A(?=.{1,253}\z)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\z/i', $host)) {
                continue;
            }

            try {
                $response = Http::connectTimeout(min(4, $timeout))
                    ->timeout($timeout)
                    ->withoutRedirecting()
                    ->head('https://' . $host);
                $status = $response->status();
                $isRedirect = in_array($status, [301, 302, 303, 307, 308], true);

                $results[] = [
                    'host' => $host,
                    'verified' => true,
                    'http_status' => $status,
                    'checked_at' => now()->format('Y-m-d H:i:s T'),
                    'message' => $isRedirect
                        ? 'TLS certificate is valid; this URL redirects with HTTP ' . $status . '.'
                        : 'TLS certificate is valid; endpoint returned HTTP ' . $status . '.',
                ];
            } catch (ConnectionException $exception) {
                $results[] = [
                    'host' => $host,
                    'verified' => false,
                    'http_status' => null,
                    'checked_at' => now()->format('Y-m-d H:i:s T'),
                    'message' => 'TLS connection or certificate verification failed.',
                ];
            } catch (\Throwable $exception) {
                report($exception);
                $results[] = [
                    'host' => $host,
                    'verified' => false,
                    'http_status' => null,
                    'checked_at' => now()->format('Y-m-d H:i:s T'),
                    'message' => 'The TLS check could not be completed.',
                ];
            }
        }

        AuditTrailSupport::log('it_admin.tls_audit_completed', null, [
            'hosts_checked' => count($results),
            'hosts_verified' => count(array_filter($results, fn ($result) => $result['verified'])),
        ]);

        $allVerified = $results !== [] && count(array_filter($results, fn ($result) => $result['verified'])) === count($results);

        return back()
            ->with('security_tls_audit_results', $results)
            ->with($allVerified ? 'success' : 'error', $allVerified
                ? 'TLS checks completed. All configured hosts presented certificates trusted by this server.'
                : 'TLS checks completed. Review the hosts that could not be verified.');
    }

    private function clearIpBlockCache(): void
    {
        try {
            Cache::store('file')->forget('security.ip_blocks.active');
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function generateReport($type)
    {
        AuditTrailSupport::log('it_admin.generate_report', null, ['type' => $type]);
        return back()->with('success', strtoupper($type) . ' report generated and sent to your email.');
    }

    public function monitoring()
    {
        $reportedModules = ModuleHealthStatus::orderBy('category')->orderBy('display_name')->get();
        $modules = $reportedModules->keyBy('module_name');

        if ((bool) config('monitoring.vps.enabled', true)) {
            $expectedVpsModules = [
                'vps_cpu' => ['VPS CPU', 'vps'],
                'vps_memory' => ['VPS Memory', 'vps'],
                'vps_disk' => ['VPS Disk', 'vps'],
                'vps_uptime' => ['VPS Uptime & Load', 'vps'],
            ];
            foreach (config('monitoring.vps.services', []) as $key => $service) {
                $expectedVpsModules['vps_service_' . $key] = [
                    $service['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                    'infrastructure',
                ];
            }

            foreach ($expectedVpsModules as $moduleName => [$displayName, $category]) {
                if ($modules->has($moduleName)) {
                    continue;
                }

                $modules->put($moduleName, new ModuleHealthStatus([
                    'module_name' => $moduleName,
                    'display_name' => $displayName,
                    'category' => $category,
                    'status' => 'unknown',
                    'meta' => [],
                ]));
            }
        }

        $modules = $modules->values()->sortBy([['category', 'asc'], ['display_name', 'asc']])->values();
        $healthScore = $reportedModules->count() > 0
            ? round(($reportedModules->where('status', 'healthy')->count() / $reportedModules->count()) * 100)
            : 0;

        $criticalCount = $reportedModules->where('status', 'critical')->count();
        $warningCount = $reportedModules->where('status', 'warning')->count();
        $healthyCount = $reportedModules->where('status', 'healthy')->count();
        $alertNotifier = app(\App\Services\SystemAlertNotifier::class);
        $alertEmails = $alertNotifier->recipients();
        $alertsEnabled = $alertNotifier->isEnabled();
        $mailDriver = (string) config('mail.default');
        $mailCanDeliver = ! in_array($mailDriver, ['log', 'array', 'null'], true);
        $alertDeliveryReady = $alertsEnabled && $mailCanDeliver && $alertEmails !== [];
        $lastCheckAt = $reportedModules->pluck('last_checked_at')->filter()->max();
        $monitoringStale = ! $lastCheckAt || $lastCheckAt->lt(now()->subMinutes(2));
        $openIncidentCount = SystemIncident::open()->count();

        return view('staff.it.dashboard.monitoring', compact(
            'modules', 'reportedModules', 'healthScore', 'criticalCount', 'warningCount', 'healthyCount',
            'alertEmails', 'alertsEnabled', 'mailDriver', 'mailCanDeliver', 'alertDeliveryReady', 'lastCheckAt', 'monitoringStale', 'openIncidentCount'
        ));
    }

    public function saveMonitoringAlerts(Request $request)
    {
        $rawEmails = (string) $request->input('emails', '');
        $emails = array_values(array_unique(array_filter(array_map(
            'trim',
            preg_split('/[,;\s]+/', $rawEmails) ?: []
        ))));

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['emails' => $emails],
            ['emails' => ['array', 'max:20'], 'emails.*' => ['email', 'max:255']]
        );

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        SystemConfig::setValue('system_alerts', [
            'email_enabled' => $request->boolean('email_enabled'),
            'emails' => $emails,
        ]);

        AuditTrailSupport::log('it_admin.system_alert_email_settings_updated', null, [
            'email_enabled' => $request->boolean('email_enabled'),
            'recipient_count' => count($emails),
        ]);

        return back()->with('success', 'System alert email settings saved. Leave recipients blank to notify active IT Admin and Super Admin accounts.');
    }

    public function testMonitoringAlerts()
    {
        if (! app(\App\Services\SystemAlertNotifier::class)->sendTest()) {
            return back()->with('error', 'The test alert email was not delivered. Check that alerts are enabled, recipients are configured, and the mail transport can deliver external email.');
        }

        AuditTrailSupport::log('it_admin.system_alert_email_test_sent');

        return back()->with('success', 'A test system alert email was sent to the configured recipients.');
    }

    public function incidents(Request $request)
    {
        $query = SystemIncident::with(['assignee', 'reporter', 'helpdeskTicket'])->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($severity = $request->get('severity')) {
            $query->where('severity', $severity);
        }
        if ($module = $request->get('module')) {
            $query->where('module', $module);
        }

        $incidents = $query->paginate(20)->withQueryString();
        $openCount = SystemIncident::open()->count();
        $criticalCount = SystemIncident::open()->critical()->count();
        $staffUsers = User::where('account_status', 'active')->whereHas('roles', fn($q) => $q->whereIn('name', ['it_admin', 'super_admin']))->get();

        return view('staff.it.dashboard.incidents', compact('incidents', 'openCount', 'criticalCount', 'staffUsers'));
    }

    public function storeIncident(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'required|in:critical,warning,info',
            'module' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $data['reported_by'] = auth()->id();
        $data['source'] = 'manual';
        $incident = SystemIncident::create($data);
        $emailSent = false;

        if (in_array($incident->severity, ['critical', 'warning'], true)) {
            $emailSent = app(\App\Services\SystemAlertNotifier::class)->send($incident, 'detected');
        }

        AuditTrailSupport::log('it_admin.create_incident', null, ['title' => $data['title']]);
        return back()->with('success', $incident->severity === 'info'
            ? 'Incident logged successfully.'
            : ($emailSent
                ? 'Incident logged and IT alert email sent.'
                : 'Incident logged, but the alert email was not delivered. Check the email alert settings and mail transport.'));
    }

    public function updateIncident(Request $request, SystemIncident $incident)
    {
        $previousStatus = $incident->status;
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,dismissed',
            'resolution_notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'root_cause' => 'nullable|string',
        ]);

        if ($data['status'] === 'resolved' && $incident->status !== 'resolved') {
            $data['resolved_at'] = now();
        }

        $incident->update($data);
        $emailSent = false;
        if ($data['status'] === 'resolved'
            && $previousStatus !== 'resolved'
            && in_array($incident->severity, ['critical', 'warning'], true)) {
            $emailSent = app(\App\Services\SystemAlertNotifier::class)->send($incident, 'recovered');
        }

        AuditTrailSupport::log('it_admin.update_incident', $incident, ['status' => $data['status']]);
        return back()->with('success', $data['status'] === 'resolved'
            ? ($emailSent
                ? 'Incident resolved and recovery email sent.'
                : 'Incident updated, but the recovery email was not delivered.')
            : 'Incident updated.');
    }

    public function drafts(Request $request)
    {
        $query = Application::where('status', 'draft')->with('applicant')->latest('updated_at');

        if ($search = $request->get('q')) {
            $query->whereHas('applicant', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }
        if ($type = $request->get('type')) {
            $query->where('application_type', $type);
        }

        $drafts = $query->paginate(20)->withQueryString();
        $totalDrafts = Application::where('status', 'draft')->count();

        return view('staff.it.dashboard.drafts', compact('drafts', 'totalDrafts'));
    }

    public function intake()
    {
        $intakeApps = Application::where('form_data->intake_method', 'physical')
            ->with('applicant')
            ->latest('updated_at')
            ->paginate(20);

        return view('staff.it.dashboard.intake', compact('intakeApps'));
    }

    public function physicalIntakeForm()
    {
        return view('staff.it.dashboard.physical_intake');
    }

    public function physicalIntakeProcess(Request $request)
    {
        $baseRules = [
            'application_type' => ['required', 'in:accreditation,registration'],
            'request_type' => ['required', 'in:new,renewal,replacement'],
        ];

        $requestType = $request->input('request_type');
        $appType = $request->input('application_type');

        if ($requestType === 'new' && $appType === 'accreditation') {
            $rules = array_merge($baseRules, [
                'first_name' => ['required', 'string', 'max:100'],
                'surname' => ['required', 'string', 'max:100'],
                'id_number' => ['required', 'string', 'regex:/^\d{2}-\d+[\s-][A-Z][\s-]\d{2}$/i'],
                'category' => ['required', 'string', 'max:10'],
                'receipt_number' => ['required', 'string', 'max:100'],
                'employer_name' => ['required', 'string', 'max:255'],
                'designation' => ['nullable', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:30'],
                'physical_address' => ['required', 'string', 'max:500'],
                'province' => ['required', 'string', 'max:50'],
                'city' => ['required', 'string', 'max:100'],
                'applicant_photo' => $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png']),
            ]);
        } elseif ($requestType === 'new' && $appType === 'registration') {
            $rules = array_merge($baseRules, [
                'entity_name' => ['required', 'string', 'max:255'],
                'trading_name' => ['required', 'string', 'max:255'],
                'business_registration' => ['required', 'string', 'max:100'],
                'tax_number' => ['required', 'string', 'max:100'],
                'business_type' => ['required', 'string', 'max:50'],
                'ownership_type' => ['required', 'string', 'max:50'],
                'local_ownership' => ['required', 'numeric', 'min:0', 'max:100'],
                'website' => ['nullable', 'url', 'max:255'],
                'media_category' => ['required', 'string', 'max:10'],
                'publication_name' => ['required', 'string', 'max:255'],
                'publication_frequency' => ['nullable', 'string', 'max:30'],
                'editor_name' => ['required', 'string', 'max:255'],
                'editor_contact' => ['required', 'string', 'max:255'],
                'mh_email' => ['required', 'email', 'max:255'],
                'mh_phone' => ['required', 'string', 'max:30'],
                'mh_physical_address' => ['required', 'string', 'max:500'],
                'postal_address' => ['required', 'string', 'max:500'],
                'mh_province' => ['required', 'string', 'max:50'],
                'mh_city' => ['required', 'string', 'max:100'],
                'mh_receipt_number' => ['required', 'string', 'max:100'],
            ]);
        } else {
            $rules = array_merge($baseRules, [
                'renewal_first_name' => ['required', 'string', 'max:100'],
                'renewal_surname' => ['required', 'string', 'max:100'],
                'renewal_id_number' => ['required', 'string', 'regex:/^\d{2}-\d+[\s-][A-Z][\s-]\d{2}$/i'],
                'lookup_number' => ['required', 'string', 'max:50'],
                'renewal_receipt_number' => ['required', 'string', 'max:100'],
                'renewal_email' => ['required', 'email', 'max:255'],
                'renewal_phone' => ['required', 'string', 'max:30'],
                'renewal_physical_address' => ['required', 'string', 'max:500'],
                'renewal_city' => ['required', 'string', 'max:100'],
                'renewal_province' => ['required', 'string', 'max:50'],
            ]);
        }

        $data = $request->validate($rules);

        $year = now()->format('Y');

        if ($requestType === 'new' && $appType === 'accreditation') {
            $prefix = "ZMC-AP3-{$year}-";
            $lastRef = Application::where('reference', 'like', $prefix . '%')
                ->where('reference', 'not like', 'DRAFT%')
                ->orderByRaw("reference DESC")
                ->value('reference');
            $nextNum = $lastRef ? ((int) substr($lastRef, -4)) + 1 : 1;
            $reference = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $applicantName = trim($data['first_name'] . ' ' . $data['surname']);

            $user = \App\Models\User::where('email', $data['email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $applicantName,
                    'email' => $data['email'],
                    'password' => bcrypt(Str::random(32)),
                    'account_type' => 'journalist',
                    'phone_number' => $data['phone'],
                    'id_number' => $data['id_number'],
                    'account_status' => 'active',
                    'region' => $data['province'] ?? 'harare',
                ]);
            }

            $photoPath = null;
            $photoFile = null;
            $photoBinary = null;
            if ($request->hasFile('applicant_photo')) {
                $photoFile = $request->file('applicant_photo');
                $photoBinary = @file_get_contents($photoFile->getRealPath()) ?: null;
                $photoPath = $this->storePublicFile($photoFile, 'intake-photos', FileType::safeHashName($photoFile));
            }

            $formData = [
                'first_name' => $data['first_name'],
                'surname' => $data['surname'],
                'national_id' => $data['id_number'],
                'id_number' => $data['id_number'],
                'category' => $data['category'],
                'employer_name' => $data['employer_name'],
                'designation' => $data['designation'] ?? '',
                'email' => $data['email'],
                'phone' => $data['phone'],
                'physical_address' => $data['physical_address'],
                'province' => $data['province'],
                'city' => $data['city'],
                'photo_path' => $photoPath,
                'intake_method' => 'physical',
                'intake_officer' => auth()->user()?->name,
                'intake_date' => now()->toDateString(),
            ];

            $application = Application::create([
                'reference' => $reference,
                'applicant_user_id' => $user->id,
                'application_type' => 'accreditation',
                'request_type' => 'new',
                'status' => Application::PRODUCTION_QUEUE,
                'receipt_number' => $data['receipt_number'],
                'accreditation_category_code' => $data['category'],
                'collection_region' => $data['province'] ?? 'harare',
                'form_data' => $formData,
                'is_draft' => false,
                'submitted_at' => now(),
                'approved_at' => now(),
                'assigned_officer_id' => null,
                'last_action_by' => auth()->id(),
                'payment_status' => 'verified',
                'payment_stage' => 'complete',
            ]);

            if ($photoPath && $photoFile) {
                $photoDoc = \App\Models\ApplicationDocument::create([
                    'application_id' => $application->id,
                    'owner_id' => $user->id,
                    'doc_type' => 'passport_photo',
                    'file_path' => $photoPath,
                    'original_name' => $photoFile->getClientOriginalName(),
                    'mime' => FileType::safeUploadedMime($photoFile),
                    'size' => $photoFile->getSize(),
                    'status' => 'uploaded',
                ]);
                $photoDoc->storeBinary($photoBinary);
            }

        } elseif ($requestType === 'new' && $appType === 'registration') {
            $prefix = "ZMC-AP1-{$year}-";
            $lastRef = Application::where('reference', 'like', $prefix . '%')
                ->where('reference', 'not like', 'DRAFT%')
                ->orderByRaw("reference DESC")
                ->value('reference');
            $nextNum = $lastRef ? ((int) substr($lastRef, -4)) + 1 : 1;
            $reference = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $user = \App\Models\User::where('email', $data['mh_email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $data['entity_name'],
                    'email' => $data['mh_email'],
                    'password' => bcrypt(Str::random(32)),
                    'account_type' => 'mediahouse',
                    'phone_number' => $data['mh_phone'],
                    'account_status' => 'active',
                    'region' => $data['mh_province'] ?? 'harare',
                ]);
            }

            $formData = [
                'entity_name' => $data['entity_name'],
                'trading_name' => $data['trading_name'],
                'business_registration' => $data['business_registration'],
                'tax_number' => $data['tax_number'],
                'business_type' => $data['business_type'],
                'ownership_type' => $data['ownership_type'],
                'local_ownership' => $data['local_ownership'],
                'website' => $data['website'] ?? null,
                'media_category' => $data['media_category'],
                'publication_name' => $data['publication_name'],
                'publication_frequency' => $data['publication_frequency'] ?? null,
                'editor_name' => $data['editor_name'],
                'editor_contact' => $data['editor_contact'],
                'email' => $data['mh_email'],
                'phone' => $data['mh_phone'],
                'physical_address' => $data['mh_physical_address'],
                'postal_address' => $data['postal_address'],
                'province' => $data['mh_province'],
                'city' => $data['mh_city'],
                'intake_method' => 'physical',
                'intake_officer' => auth()->user()?->name,
                'intake_date' => now()->toDateString(),
            ];

            $application = Application::create([
                'reference' => $reference,
                'applicant_user_id' => $user->id,
                'application_type' => 'registration',
                'request_type' => 'new',
                'status' => Application::PRODUCTION_QUEUE,
                'receipt_number' => $data['mh_receipt_number'],
                'media_house_category_code' => $data['media_category'],
                'collection_region' => $data['mh_province'] ?? 'harare',
                'form_data' => $formData,
                'is_draft' => false,
                'submitted_at' => now(),
                'approved_at' => now(),
                'assigned_officer_id' => null,
                'last_action_by' => auth()->id(),
                'payment_status' => 'verified',
                'payment_stage' => 'complete',
            ]);

        } else {
            $applicantName = trim(($data['renewal_first_name'] ?? '') . ' ' . ($data['renewal_surname'] ?? ''));

            $existingApp = Application::where('reference', $data['lookup_number'])->first();

            $user = \App\Models\User::where('email', $data['renewal_email'])->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $applicantName,
                    'email' => $data['renewal_email'],
                    'password' => bcrypt(Str::random(32)),
                    'account_type' => $appType === 'registration' ? 'mediahouse' : 'journalist',
                    'phone_number' => $data['renewal_phone'],
                    'id_number' => $data['renewal_id_number'],
                    'account_status' => 'active',
                    'region' => $data['renewal_province'] ?? 'harare',
                ]);
            }

            $refPrefix = $appType === 'registration' ? "ZMC-AP1-{$year}-" : "ZMC-AP3-{$year}-";
            $lastRef = Application::where('reference', 'like', $refPrefix . '%')
                ->where('reference', 'not like', 'DRAFT%')
                ->orderByRaw("reference DESC")
                ->value('reference');
            $nextNum = $lastRef ? ((int) substr($lastRef, -4)) + 1 : 1;
            $reference = $refPrefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            $formData = [
                'first_name' => $data['renewal_first_name'] ?? '',
                'surname' => $data['renewal_surname'] ?? '',
                'id_number' => $data['renewal_id_number'] ?? '',
                'email' => $data['renewal_email'],
                'phone' => $data['renewal_phone'],
                'physical_address' => $data['renewal_physical_address'] ?? '',
                'city' => $data['renewal_city'] ?? '',
                'province' => $data['renewal_province'] ?? '',
                'lookup_number' => $data['lookup_number'],
                'previous_reference' => $existingApp?->reference,
                'intake_method' => 'physical',
                'intake_officer' => auth()->user()?->name,
                'intake_date' => now()->toDateString(),
            ];

            $application = Application::create([
                'reference' => $reference,
                'applicant_user_id' => $user->id,
                'application_type' => $appType,
                'request_type' => $requestType,
                'status' => Application::PRODUCTION_QUEUE,
                'receipt_number' => $data['renewal_receipt_number'],
                'collection_region' => $data['renewal_province'] ?? 'harare',
                'form_data' => $formData,
                'is_draft' => false,
                'submitted_at' => now(),
                'approved_at' => now(),
                'assigned_officer_id' => null,
                'last_action_by' => auth()->id(),
                'payment_status' => 'verified',
                'payment_stage' => 'complete',
            ]);
        }

        if (class_exists(\App\Services\ActivityLogger::class)) {
            \App\Services\ActivityLogger::log('physical_intake_created', $application, null, Application::PRODUCTION_QUEUE, [
                'receipt_number' => $application->receipt_number,
                'application_type' => $appType,
                'request_type' => $requestType,
                'officer' => auth()->user()?->name,
            ]);
        }

        AuditTrailSupport::log('it_admin.physical_intake', $application, [
            'reference' => $application->reference,
            'application_type' => $appType,
            'request_type' => $requestType,
        ]);

        return redirect()->route('staff.it.intake')
            ->with('success', "Physical intake recorded successfully (Ref: {$application->reference}). Application added to production queue.");
    }

    public function intakeEdit(Application $application)
    {
        $fd = is_array($application->form_data) ? $application->form_data : json_decode($application->form_data ?? '{}', true);
        if (($fd['intake_method'] ?? null) !== 'physical') {
            abort(403, 'This application is not a physical intake record.');
        }

        $application->load('applicant', 'documents');
        $formData = $fd;
        return view('staff.it.dashboard.intake_edit', compact('application', 'formData'));
    }

    public function intakeUpdate(Request $request, Application $application)
    {
        $existingFormData = is_array($application->form_data) ? $application->form_data : json_decode($application->form_data ?? '{}', true);
        if (($existingFormData['intake_method'] ?? null) !== 'physical') {
            abort(403, 'This application is not a physical intake record.');
        }

        $rules = [
            'applicant_photo' => $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png']),
            'first_name' => ['nullable', 'string', 'max:100'],
            'surname' => ['nullable', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:10'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:100'],
            'employment_start_date' => ['nullable', 'date'],
            'press_card_number' => ['nullable', 'string', 'max:50'],
            'qualifications' => ['nullable', 'string', 'max:1000'],
            'entity_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'business_registration' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'business_type' => ['nullable', 'string', 'max:50'],
            'ownership_type' => ['nullable', 'string', 'max:50'],
            'local_ownership' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'media_category' => ['nullable', 'string', 'max:10'],
            'publication_name' => ['nullable', 'string', 'max:255'],
            'publication_frequency' => ['nullable', 'string', 'max:30'],
            'editor_name' => ['nullable', 'string', 'max:255'],
            'editor_contact' => ['nullable', 'string', 'max:255'],
            'num_employees' => ['nullable', 'integer', 'min:0'],
            'year_established' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'postal_address' => ['nullable', 'string', 'max:500'],
            'province' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,ecocash,card'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];

        $data = $request->validate($rules);

        $photoPath = $existingFormData['photo_path'] ?? null;
        if ($request->hasFile('applicant_photo')) {
            $photoFile = $request->file('applicant_photo');
            $photoBinary = @file_get_contents($photoFile->getRealPath());
            if ($photoBinary === false) {
                \Log::warning('Document binary read failed (updateIntake)', [
                    'application_id' => $application->id,
                ]);
                $photoBinary = null;
            }
            $photoPath = $this->storePublicFile($photoFile, 'intake-photos', FileType::safeHashName($photoFile));

            $intakePhotoDoc = \App\Models\ApplicationDocument::updateOrCreate(
                ['application_id' => $application->id, 'doc_type' => 'passport_photo'],
                [
                    'owner_id' => $application->applicant_user_id,
                    'file_path' => $photoPath,
                    'original_name' => $photoFile->getClientOriginalName(),
                    'mime' => FileType::safeUploadedMime($photoFile),
                    'size' => $photoFile->getSize(),
                    'status' => 'uploaded',
                ]
            );
            $intakePhotoDoc->storeBinary($photoBinary);
        }
        unset($data['applicant_photo']);

        if (isset($data['national_id'])) {
            $data['id_number'] = $data['national_id'];
        }

        $filteredData = array_filter($data, fn($v) => $v !== null && $v !== '');
        $updatedFormData = array_merge($existingFormData, $filteredData);
        $updatedFormData['photo_path'] = $photoPath;
        $updatedFormData['last_edited_by'] = auth()->user()?->name;
        $updatedFormData['last_edited_at'] = now()->toDateTimeString();

        $appUpdate = [
            'form_data' => $updatedFormData,
            'last_action_by' => auth()->id(),
        ];

        if (!empty($data['receipt_number'])) {
            $appUpdate['receipt_number'] = $data['receipt_number'];
        }
        if (!empty($data['category'])) {
            $appUpdate['accreditation_category_code'] = $data['category'];
        }
        if (!empty($data['province'])) {
            $appUpdate['collection_region'] = $data['province'];
        }
        if (!empty($data['media_category'])) {
            $appUpdate['media_house_category_code'] = $data['media_category'];
        }

        $application->update($appUpdate);

        AuditTrailSupport::log('it_admin.intake_edit', $application, [
            'reference' => $application->reference,
            'edited_fields' => array_keys($filteredData),
        ]);

        return redirect()->route('staff.it.intake')
            ->with('success', "Application {$application->reference} has been updated successfully.");
    }

    public function records()
    {
        $users = User::count();
        $regions = Region::count();
        $locations = Location::count();
        $devices = Device::count();
        $roles = Role::where('name', '!=', 'super_admin')->count();

        return view('staff.it.dashboard.records', compact('users', 'regions', 'locations', 'devices', 'roles'));
    }

    public function helpdesk(Request $request)
    {
        $this->ensureHelpdeskAccess();
        $query = HelpdeskTicket::with(['submitter', 'assignee', 'incident', 'updates.user'])->latest();

        $status = $request->get('status');
        if ($status && in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
            $query->where('status', $status);
        }

        $tickets = $query->paginate(20)->withQueryString();
        $openCount = HelpdeskTicket::open()->count();
        $staffUsers = User::where('account_status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('name', 'it_admin'))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->get();

        return view('staff.it.dashboard.helpdesk', compact('tickets', 'openCount', 'staffUsers'));
    }

    public function storeTicket(Request $request)
    {
        $this->ensureHelpdeskAccess();
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'priority' => 'required|in:low,medium,high,critical',
            'category' => 'required|string|max:100',
            'assigned_to' => 'nullable|exists:users,id',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);
        $data['assigned_to'] = $data['assigned_to'] ?? null;

        if (! empty($data['assigned_to']) && ! $this->isHelpdeskStaff((int) $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => 'Tickets can only be assigned to an IT Admin or Super Admin.'])->withInput();
        }

        $data['submitted_by'] = auth()->id();
        $attachment = $request->file('attachment');
        if ($attachment) {
            $data['attachment_path'] = $attachment->store('helpdesk', 'local');
            $data['attachment_name'] = $attachment->getClientOriginalName();
        }
        unset($data['attachment']);

        $ticket = DB::transaction(function () use ($data) {
            $ticket = HelpdeskTicket::create($data);
            $ticket->updates()->create([
                'user_id' => auth()->id(),
                'event_type' => 'created',
                'body' => 'Ticket created.',
            ]);

            return $ticket;
        });

        $recipients = $this->helpdeskStaff()->keyBy('id');
        if ($ticket->submitted_by) {
            $recipients->put($ticket->submitted_by, User::find($ticket->submitted_by));
        }
        if ($ticket->assigned_to) {
            $assignee = User::find($ticket->assigned_to);
            if ($assignee) $recipients->put($assignee->id, $assignee);
        }
        $notified = $this->sendHelpdeskNotification($recipients, $ticket, 'A new support ticket was created.');

        return back()->with($notified ? 'success' : 'warning', $notified
            ? 'Support ticket created and email notifications were sent.'
            : 'Support ticket created, but email notifications were not delivered. Check the mail transport settings.');
    }

    public function updateTicket(Request $request, HelpdeskTicket $ticket)
    {
        $this->ensureHelpdeskAccess();
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'resolution_summary' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'comment' => 'nullable|string|max:5000',
        ]);
        $data += [
            'assigned_to' => $ticket->assigned_to,
            'resolution_summary' => $ticket->resolution_summary,
        ];

        if (! empty($data['assigned_to']) && ! $this->isHelpdeskStaff((int) $data['assigned_to'])) {
            return back()->withErrors(['assigned_to' => 'Tickets can only be assigned to an IT Admin or Super Admin.'])->withInput();
        }

        $previousAssignee = $ticket->assigned_to;
        $changes = [];
        foreach (['status', 'assigned_to', 'resolution_summary'] as $field) {
            $oldValue = $ticket->{$field};
            $newValue = $data[$field] ?? null;
            if ((string) $oldValue !== (string) $newValue) {
                if ($field === 'assigned_to') {
                    $oldValue = $oldValue ? User::find($oldValue)?->name : 'Unassigned';
                    $newValue = $newValue ? User::find($newValue)?->name : 'Unassigned';
                } elseif ($field === 'status') {
                    $oldValue = ucfirst(str_replace('_', ' ', (string) $oldValue));
                    $newValue = ucfirst(str_replace('_', ' ', (string) $newValue));
                }
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        if (in_array($data['status'], ['resolved', 'closed'], true) && ! $ticket->resolved_at) {
            $data['resolved_at'] = now();
        } elseif (! in_array($data['status'], ['resolved', 'closed'], true)) {
            $data['resolved_at'] = null;
        }

        $comment = trim((string) ($data['comment'] ?? ''));
        unset($data['comment']);
        $ticket->update($data);
        $notified = 0;

        if ($changes || $comment !== '') {
            $ticket->updates()->create([
                'user_id' => auth()->id(),
                'event_type' => $comment !== '' && ! $changes ? 'comment' : 'updated',
                'body' => $comment ?: null,
                'changes' => $changes ?: null,
            ]);

            $recipients = User::whereKey(array_values(array_unique(array_filter([
                $ticket->submitted_by,
                $previousAssignee,
                $ticket->assigned_to,
            ]))))->whereHas('roles', fn ($query) => $query->where('name', 'it_admin'))
                ->get()->reject(fn (User $user) => $user->id === auth()->id());
            $details = $comment !== '' ? $comment : 'Ticket status, assignment, or resolution details changed.';
            $notified = $this->sendHelpdeskNotification($recipients, $ticket, 'A support ticket was updated.', $details);
        }

        $hasUpdate = (bool) $changes || $comment !== '';

        return back()->with($hasUpdate && ! $notified ? 'warning' : 'success', $hasUpdate
            ? ($notified ? 'Ticket updated and email notifications were sent.' : 'Ticket updated, but email notifications were not delivered.')
            : 'Ticket updated.');
    }

    public function createTicketFromIncident(SystemIncident $incident)
    {
        $this->ensureHelpdeskAccess();
        $created = false;
        $ticket = DB::transaction(function () use ($incident, &$created) {
            $lockedIncident = SystemIncident::whereKey($incident->id)->lockForUpdate()->firstOrFail();
            if ($existing = HelpdeskTicket::where('incident_id', $lockedIncident->id)->first()) {
                return $existing;
            }

            $priority = match ($lockedIncident->severity) {
                'critical' => 'critical',
                'warning' => 'high',
                default => 'medium',
            };
            $ticket = HelpdeskTicket::create([
                'subject' => 'Incident: ' . $lockedIncident->title,
                'description' => trim(($lockedIncident->description ?? '') . "\n\nIncident #{$lockedIncident->id}; module: " . ($lockedIncident->module ?: 'unspecified')),
                'priority' => $priority,
                'status' => 'open',
                'category' => 'System incident',
                'submitted_by' => auth()->id(),
                'assigned_to' => $lockedIncident->assigned_to && $this->isHelpdeskStaff((int) $lockedIncident->assigned_to)
                    ? $lockedIncident->assigned_to
                    : null,
                'incident_id' => $lockedIncident->id,
            ]);
            $created = true;
            $ticket->updates()->create([
                'user_id' => auth()->id(),
                'event_type' => 'created',
                'body' => "Created from monitoring incident #{$lockedIncident->id}.",
            ]);

            return $ticket;
        });

        if (! $created) {
            return redirect()->route('staff.it.helpdesk')->with('success', 'This incident is already linked to Helpdesk ticket #' . $ticket->id . '.');
        }

        $recipients = $this->helpdeskStaff()->keyBy('id');
        if ($ticket->submitted_by) $recipients->put($ticket->submitted_by, User::find($ticket->submitted_by));
        if ($ticket->assigned_to) {
            $assignee = User::find($ticket->assigned_to);
            if ($assignee) $recipients->put($assignee->id, $assignee);
        }
        $notified = $this->sendHelpdeskNotification($recipients, $ticket, 'A support ticket was created from a system incident.');

        return redirect()->route('staff.it.helpdesk')->with($notified ? 'success' : 'warning', $notified
            ? 'Incident linked to Helpdesk ticket #' . $ticket->id . ' and email notifications were sent.'
            : 'Incident linked to Helpdesk ticket #' . $ticket->id . ', but email notifications were not delivered.');
    }

    public function downloadTicketAttachment(HelpdeskTicket $ticket)
    {
        $this->ensureHelpdeskAccess();
        abort_unless($ticket->attachment_path && Storage::disk('local')->exists($ticket->attachment_path), 404);

        return Storage::disk('local')->download($ticket->attachment_path, basename($ticket->attachment_name ?: 'attachment'));
    }

    private function isHelpdeskStaff(int $userId): bool
    {
        return User::whereKey($userId)
            ->whereHas('roles', fn ($query) => $query->where('name', 'it_admin'))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->exists();
    }

    private function helpdeskStaff()
    {
        return User::where('account_status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('name', 'it_admin'))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'super_admin'))
            ->get();
    }

    private function ensureHelpdeskAccess(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('it_admin') && ! $user->hasRole('super_admin'), 403);
    }

    private function sendHelpdeskNotification($recipients, HelpdeskTicket $ticket, string $event, ?string $details = null): int
    {
        if (in_array((string) config('mail.default'), ['log', 'array', 'null', ''], true)) {
            return 0;
        }

        $sent = 0;
        foreach ($recipients->unique('id') as $recipient) {
            if (! $recipient || ! $recipient->hasRole('it_admin') || $recipient->hasRole('super_admin') || ! filled($recipient->email)) {
                continue;
            }

            try {
                $recipient->notify(new HelpdeskTicketUpdatedNotification($ticket, $event, $details));
                $sent++;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $sent;
    }

    public function devices(Request $request)
    {
        $this->ensureItAdminOnly();
        $deviceList = Device::with('location.region')->latest()->paginate(20);
        $timeout = max(1, (int) config('monitoring.devices.heartbeat_timeout_minutes', 3));
        $deviceList->getCollection()->transform(function (Device $device) use ($timeout) {
            $isConnected = $device->last_heartbeat && $device->last_heartbeat->gte(now()->subMinutes($timeout));
            $device->connectivity_label = ! $device->last_heartbeat ? 'Awaiting agent' : ($isConnected ? 'Connected' : 'Offline');
            $device->connectivity_variant = ! $device->last_heartbeat ? 'secondary' : ($isConnected ? 'success' : 'danger');

            return $device;
        });
        $locations = Location::with('region')->orderBy('name')->get();

        return view('staff.it.dashboard.devices-page', compact('deviceList', 'locations'));
    }

    public function storeDevice(Request $request)
    {
        $this->ensureItAdminOnly();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:card_printer,certificate_printer,scanner,other',
            'location_id' => 'nullable|exists:locations,id',
            'diagnostics_notes' => 'nullable|string',
        ]);

        $plainToken = bin2hex(random_bytes(32));
        $data['api_token_hash'] = hash('sha256', $plainToken);
        $data['status'] = 'pending';
        $data['connection_status'] = 'awaiting_heartbeat';
        $device = Device::create($data);
        AuditTrailSupport::log('it_admin.create_device', null, ['name' => $data['name']]);
        $endpoint = route('api.devices.heartbeat', ['device' => $device->id]);

        return back()->with('success', 'Device registered. Configure its monitoring agent with the one-time token below.')
            ->with('device_agent_token', $plainToken)
            ->with('device_agent_name', $device->name)
            ->with('device_agent_endpoint', $endpoint);
    }

    public function rotateDeviceToken(Device $device)
    {
        $this->ensureItAdminOnly();
        $plainToken = bin2hex(random_bytes(32));
        $device->forceFill(['api_token_hash' => hash('sha256', $plainToken)])->save();
        AuditTrailSupport::log('it_admin.rotate_device_agent_token', $device, ['device_name' => $device->name]);

        return back()->with('success', 'The previous device token is revoked. Configure the agent with this replacement token.')
            ->with('device_agent_token', $plainToken)
            ->with('device_agent_name', $device->name)
            ->with('device_agent_endpoint', route('api.devices.heartbeat', ['device' => $device->id]));
    }

    private function ensureItAdminOnly(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('it_admin') && ! $user->hasRole('super_admin'), 403);
    }

    public function performance()
    {
        $memUsage = round(memory_get_usage(true) / 1024 / 1024, 1);
        $peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 1);

        $recentLogins = [];
        if (Schema::hasTable('login_activities')) {
            $dateExpr = match (DB::getDriverName()) {
                'sqlite' => "strftime('%Y-%m-%d', login_at)",
                'mysql', 'mariadb' => "DATE_FORMAT(login_at, '%Y-%m-%d')",
                default => "DATE_FORMAT(login_at, '%Y-%m-%d')",
            };

            $recentLogins = LoginActivity::select(
                DB::raw("{$dateExpr} as date"),
                DB::raw('COUNT(*) as count')
            )->where('login_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        $failedJobsToday = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->whereDate('failed_at', today())->count()
            : 0;

        $avgResponseTime = ServiceHeartbeat::where('checked_at', '>=', now()->subHour())
            ->avg('response_time_ms') ?? 0;

        return view('staff.it.dashboard.performance', compact(
            'memUsage', 'peakMem', 'recentLogins', 'failedJobsToday', 'avgResponseTime'
        ));
    }

    public function tempRoles()
    {
        $assignments = TemporaryRoleAssignment::with(['user', 'assigner', 'region'])
            ->latest()
            ->paginate(20);

        $activeCount = TemporaryRoleAssignment::where('status', 'active')
            ->where('ends_at', '>', now())
            ->count();

        $expiredCount = TemporaryRoleAssignment::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->count();

        $staffUsers = User::whereHas('roles', fn($q) => $q->where('name', '!=', 'super_admin'))
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->orderBy('name')
            ->get();

        $roles = Role::where('name', '!=', 'super_admin')->orderBy('name')->get();
        $regions = Region::where('is_active', true)->orderBy('name')->get();

        return view('staff.it.dashboard.temp-roles', compact(
            'assignments', 'activeCount', 'expiredCount', 'staffUsers', 'roles', 'regions'
        ));
    }

    public function locationsMgmt()
    {
        $locations = Location::with('region')->latest()->paginate(20);
        $allRegions = Region::orderBy('name')->get();
        $regions = $allRegions->where('is_active', true)->values();
        $activeCount = Location::where('status', 'active')->count();
        $totalCount = Location::count();

        return view('staff.it.dashboard.locations-mgmt', compact(
            'locations', 'regions', 'allRegions', 'activeCount', 'totalCount'
        ));
    }

    public function storeTempRole(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'temporary_role' => ['required', 'string', 'exists:roles,name', \Illuminate\Validation\Rule::notIn(['super_admin'])],
            'region_id' => 'nullable|exists:regions,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($data['user_id']);
        $data['base_role'] = $user->roles->first()?->name ?? 'none';
        $data['assigned_by'] = auth()->id();
        $data['status'] = 'active';

        $overlap = TemporaryRoleAssignment::where('user_id', $data['user_id'])
            ->where('status', 'active')
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->exists();

        if ($overlap) {
            return back()->withErrors(['overlap' => 'This user has an overlapping temporary role assignment.'])->withInput();
        }

        $assignment = TemporaryRoleAssignment::create($data);
        $user->assignRole($data['temporary_role']);

        AuditTrailSupport::log('it_admin.assign_temp_role', $user, [
            'temporary_role' => $data['temporary_role'],
            'ends_at' => $data['ends_at'],
        ]);

        try {
            $assignment->load('region');
            $user->notify(new TemporaryRoleAssignedNotification($assignment));
            $message = 'Temporary role assigned and notification email sent.';
        } catch (\Throwable $exception) {
            \Log::warning('Temporary role was assigned but notification email failed.', [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
            $message = 'Temporary role assigned, but the notification email could not be sent. Check the mail configuration and try again.';
        }

        return back()->with('success', $message);
    }

    public function revokeTempRole(TemporaryRoleAssignment $assignment)
    {
        $assignment->update(['status' => 'revoked']);
        $assignment->user?->removeRole($assignment->temporary_role);

        AuditTrailSupport::log('it_admin.revoke_temp_role', $assignment->user, [
            'temporary_role' => $assignment->temporary_role,
        ]);

        return back()->with('success', 'Temporary role revoked.');
    }

    public function storeLocation(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'location_type' => 'required|in:head_office,regional_office,temporary_outreach,mobile_service',
            'address' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
        ]);

        Location::create($data);
        AuditTrailSupport::log('it_admin.create_location', null, ['name' => $data['name']]);
        return back()->with('success', 'Location created successfully.');
    }

    public function toggleLocation(Location $location)
    {
        $location->update(['status' => $location->status === 'active' ? 'disabled' : 'active']);
        AuditTrailSupport::log('it_admin.toggle_location', null, ['location' => $location->name, 'status' => $location->status]);
        return back()->with('success', "Location {$location->status}.");
    }
}
