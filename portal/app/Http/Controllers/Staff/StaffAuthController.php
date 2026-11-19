<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class StaffAuthController extends Controller
{
    public function show(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $userRoles = $this->roleNamesForUser($user->id);
            $staffRoles = array_values(array_intersect($userRoles, $this->staffAllowedRoles()));

            if (count($staffRoles) > 0) {
                $active = $request->session()->get('active_staff_role');
                if ($active && in_array($active, $staffRoles, true)) {
                    return $this->redirectToRoleDashboard($active);
                }
                $role = $staffRoles[0];
                $request->session()->put('active_staff_role', $role);
                return $this->redirectToRoleDashboard($role);
            }
        }

        return view('staff.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Trim email to prevent whitespace issues
        $credentials['email'] = trim($credentials['email']);

        \Log::info('Staff login attempt', [
            'email' => $credentials['email'],
            'email_length' => strlen($credentials['email']),
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            \Log::warning('Staff login failed: User not found', ['email' => $credentials['email']]);
            \App\Support\AuditTrail::log('login_failed', null, [
                'portal' => 'staff',
                'email' => $credentials['email'],
                'reason' => 'user_not_found',
            ]);
            \App\Support\LoginHistory::record(null, $request, false, 'Invalid credentials (Staff)');

            return back()->withErrors(['email' => 'Invalid login credentials'])->withInput();
        }

        \Log::info('User found', [
            'user_id' => $user->id,
            'email' => $user->email,
            'account_type' => $user->account_type,
            'account_status' => $user->account_status,
        ]);

        // Check for temporary password first
        if ($user && $user->temp_password && $user->temp_password_expires_at && $user->temp_password_expires_at->isFuture()) {
            if (\Hash::check($credentials['password'], $user->temp_password)) {
                \Log::info('Temporary password accepted', ['user_id' => $user->id]);
                // Temporary password is valid, proceed with OTP
                return $this->processLoginWithOtp($request, $user, true);
            }
        }

        // Test password before Auth::attempt
        $passwordCheck = \Hash::check($credentials['password'], $user->password);
        \Log::info('Password check result', [
            'user_id' => $user->id,
            'password_matches' => $passwordCheck,
        ]);

        // Verify password manually
        if (!$passwordCheck) {
            \Log::warning('Password verification failed', [
                'email' => $credentials['email'],
                'user_id' => $user->id,
            ]);
            \App\Support\AuditTrail::log('login_failed', null, [
                'portal' => 'staff',
                'email' => $credentials['email'],
                'reason' => 'invalid_password',
            ]);
            \App\Support\LoginHistory::record($user, $request, false, 'Invalid credentials (Staff)');

            return back()->withErrors(['email' => 'Invalid login credentials'])->withInput();
        }

        \Log::info('Password verified successfully', ['user_id' => $user->id]);

        // Password is correct, proceed with OTP (bypass Auth::attempt which may have other issues)
        return $this->processLoginWithOtp($request, $user, false);
    }

    /**
     * Process login with OTP generation
     */
    private function processLoginWithOtp(Request $request, User $user, bool $usingTempPassword)
    {

        if (!$user) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('staff.login');
        }

        if (($user->account_status ?? 'active') !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Account is not active. Please contact the system administrator.']);
        }

        $userRoles = $this->roleNamesForUser($user->id);
        $staffRoles = array_values(array_intersect($userRoles, $this->staffAllowedRoles()));

        if (count($staffRoles) === 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Access denied. Not a staff account.']);
        }

        // --- BYPASS OTP LOGIC ---
        
        $role = $staffRoles[0];

        // Super Admin is oversight-only: never impersonate another staff dashboard.
        if (in_array('super_admin', $staffRoles, true)) {
            $role = 'super_admin';
            $request->session()->forget('staff_selected_role');
        }

        Auth::login($user);

        $request->session()->put('active_staff_role', $role);

        $authCookie = \App\Http\Middleware\TokenAuth::makeAuthCookie($user, $role);

        \App\Support\AuditTrail::log('login_staff', $user, ['role' => $role]);
        \App\Support\LoginHistory::record($user, $request, true);

        if ($usingTempPassword || $user->password_change_required) {
            return redirect()->route('staff.password.change')
                ->with('info', 'Please change your password to continue. Your temporary password has expired or requires update.')
                ->withCookie($authCookie);
        }

        return $this->redirectToRoleDashboard($role)->withCookie($authCookie);
    }

    private function makeOtpToken(User $user, array $staffRoles, bool $usingTempPassword): string
    {
        // Token lives 15 minutes — slightly longer than the 10-min OTP so the
        // expiry check in verifyOtp can give a proper "OTP expired" message
        // rather than a confusing "session expired" redirect.
        $tokenExpiry = now()->addMinutes(15)->toIso8601String();

        return base64_encode(json_encode([
            'uid' => $user->id,
            'roles' => $staffRoles,
            'temp' => $usingTempPassword,
            'exp' => $tokenExpiry,
            'sig' => hash_hmac('sha256', $user->id . '|' . $tokenExpiry, config('app.key')),
        ]));
    }

    private function resolveOtpToken(Request $request): ?array
    {
        $token = $request->input('_otp_token');
        if (!$token) return null;

        try {
            $data = json_decode(base64_decode($token), true);
            if (!$data || !isset($data['uid'], $data['sig'], $data['exp'])) return null;

            $expectedSig = hash_hmac('sha256', $data['uid'] . '|' . $data['exp'], config('app.key'));
            if (!hash_equals($expectedSig, $data['sig'])) return null;

            if (now()->gt(\Carbon\Carbon::parse($data['exp']))) return null;

            $user = User::find($data['uid']);
            if (!$user) return null;

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function showOtp(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $roles = $this->roleNamesForUser($user->id);
            $staffRoles = array_values(array_intersect($roles, $this->staffAllowedRoles()));
            if (count($staffRoles) > 0) {
                $role = $staffRoles[0];
                $request->session()->put('active_staff_role', $role);
                return $this->redirectToRoleDashboard($role);
            }
        }

        $tokenData = $this->resolveOtpToken($request);

        if (!$tokenData) {
            $userId = $request->session()->get('otp_user_id');
            if (!$userId) {
                return redirect()->route('staff.login');
            }
            $user = User::find($userId);
            $maskedEmail = $this->maskEmail($user->email ?? '');
            $otpToken = '';
            return view('staff.otp-verify', compact('maskedEmail', 'otpToken'));
        }

        $user = User::find($tokenData['uid']);
        $maskedEmail = $this->maskEmail($user->email ?? '');
        $otpToken = $request->input('_otp_token');

        return view('staff.otp-verify', compact('maskedEmail', 'otpToken'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if (Auth::check()) {
            $user = Auth::user();
            $roles = $this->roleNamesForUser($user->id);
            $staffRoles = array_values(array_intersect($roles, $this->staffAllowedRoles()));
            if (count($staffRoles) > 0) {
                $role = $staffRoles[0];
                $request->session()->put('active_staff_role', $role);
                return $this->redirectToRoleDashboard($role)
                    ->withCookie(\App\Http\Middleware\TokenAuth::makeAuthCookie($user, $role));
            }
        }

        $tokenData = $this->resolveOtpToken($request);
        $userId = $tokenData['uid'] ?? $request->session()->get('otp_user_id');
        $staffRoles = $tokenData['roles'] ?? $request->session()->get('otp_staff_roles', []);
        $usingTempPassword = $tokenData['temp'] ?? $request->session()->get('using_temp_password', false);

        if (!$userId) {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        // Always fresh-fetch from DB to avoid stale model state (e.g. double-submit race)
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        $submittedOtp = trim($request->input('otp', ''));
        $otpToken = $request->input('_otp_token', '');
        $idempotencyKey = 'otp_verified:' . hash('sha256', $userId . ':' . $submittedOtp . ':' . $otpToken);

        // Idempotency: handle double-submit (paste auto-submit + button click)
        if (!$user->otp_code) {
            $cachedRole = cache($idempotencyKey);
            if ($cachedRole) {
                Auth::login($user);
                $request->session()->put('active_staff_role', $cachedRole);
                return $this->redirectToRoleDashboard($cachedRole)
                    ->withCookie(\App\Http\Middleware\TokenAuth::makeAuthCookie($user, $cachedRole));
            }

            // OTP already used or never generated — send back to login
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Your OTP has already been used or has expired. Please log in again.']);
        }

        // Check expiry before checking the code value (better UX)
        if ($user->otp_expires_at && $user->otp_expires_at->isPast()) {
            // Clear the stale OTP
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();

            return redirect()->route('staff.login')
                ->withErrors(['email' => 'OTP has expired. Please log in again.']);
        }

        if ($user->otp_code !== $submittedOtp) {
            return redirect()->route('staff.otp.show', ['_otp_token' => $otpToken])
                ->withErrors(['otp' => 'Invalid OTP code. Please try again.']);
        }

        if (($user->account_status ?? 'active') !== 'active') {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Account is no longer active.']);
        }

        $currentRoles = $this->roleNamesForUser($user->id);
        $validStaffRoles = array_values(array_intersect($currentRoles, $this->staffAllowedRoles()));

        if (count($validStaffRoles) === 0) {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Access denied. Staff role has been revoked.']);
        }

        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        $role = $validStaffRoles[0];

        // Super Admin is oversight-only: never impersonate another staff dashboard.
        if (in_array('super_admin', $validStaffRoles, true)) {
            $role = 'super_admin';
            $request->session()->forget('staff_selected_role');
        }

        cache()->put($idempotencyKey, $role, now()->addSeconds(30));

        Auth::login($user);

        $request->session()->forget(['otp_user_id', 'otp_staff_roles', 'otp_attempts', '_otp_token']);

        $request->session()->put('active_staff_role', $role);

        $authCookie = \App\Http\Middleware\TokenAuth::makeAuthCookie($user, $role);

        \App\Support\AuditTrail::log('login_staff', $user, ['role' => $role]);
        \App\Support\LoginHistory::record($user, $request, true);

        if ($usingTempPassword || $user->password_change_required) {
            return redirect()->route('staff.password.change')
                ->with('info', 'Please change your password to continue. Your temporary password has expired or requires update.')
                ->withCookie($authCookie);
        }

        return $this->redirectToRoleDashboard($role)->withCookie($authCookie);
    }

    public function resendOtp(Request $request)
    {
        $userId = $request->session()->get('otp_user_id');

        if (!$userId) {
            $tokenData = $this->resolveOtpToken($request);
            if ($tokenData) {
                $userId = $tokenData['uid'];
                $request->session()->put('otp_user_id', $userId);
                $request->session()->put('otp_staff_roles', $tokenData['roles'] ?? []);
                $request->session()->put('using_temp_password', $tokenData['temp'] ?? false);
            }
        }

        if (!$userId) {
            return redirect()->route('staff.login');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('staff.login');
        }

        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            Mail::raw(
                "Your ZMC Staff Login OTP is: {$otp}\n\nThis code expires in 10 minutes. If you did not request this, please ignore.",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('ZMC Staff Login - OTP Verification');
                }
            );
        } catch (\Throwable $e) {
            \Log::warning('OTP resend email failed: ' . $e->getMessage());
        }

        $tokenData = $this->resolveOtpToken($request);
        $staffRoles = $tokenData['roles'] ?? $request->session()->get('otp_staff_roles', []);
        $usingTempPassword = $tokenData['temp'] ?? $request->session()->get('using_temp_password', false);
        $newToken = $this->makeOtpToken($user, $staffRoles, $usingTempPassword);

        return redirect()->route('staff.otp.show', ['_otp_token' => $newToken])
            ->with('success', 'A new OTP has been sent to your email.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget(['active_staff_role', 'staff_selected_role', 'otp_user_id', 'otp_staff_roles']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login')
            ->withCookie(\App\Http\Middleware\TokenAuth::forgetAuthCookie());
    }

    public function showActivation(Request $request, string $token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Invalid or expired activation link.']);
        }

        if ($user->activated_at) {
            return redirect()->route('staff.login')
                ->with('success', 'Account already activated. Please log in.');
        }

        return view('staff.activate', compact('token', 'user'));
    }

    public function activate(Request $request, string $token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return redirect()->route('staff.login')
                ->withErrors(['email' => 'Invalid or expired activation link.']);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = $data['password'];
        $user->activated_at = now();
        $user->activation_token = null;
        $user->account_status = 'active';
        $user->save();

        \App\Support\AuditTrail::log('account_activated', $user);

        return redirect()->route('staff.login')
            ->with('success', 'Account activated successfully. You can now log in.');
    }

    public function showPasswordChange(Request $request)
    {
        return view('staff.password-change');
    }

    public function processPasswordChange(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        
        $user->password = \Hash::make($data['password']);
        $user->temp_password = null;
        $user->temp_password_expires_at = null;
        $user->password_change_required = false;
        $user->save();

        \App\Support\AuditTrail::log('password_changed', $user);

        // Redirect to appropriate dashboard based on role
        $userRoles = $this->roleNamesForUser($user->id);
        $staffRoles = array_values(array_intersect($userRoles, $this->staffAllowedRoles()));
        
        if (count($staffRoles) > 0) {
            $role = $staffRoles[0];
            $request->session()->put('active_staff_role', $role);
            
            return $this->redirectToRoleDashboard($role)
                ->with('success', 'Password changed successfully. Welcome to the portal!');
        }

        return redirect()->route('home')->with('success', 'Password changed successfully.');
    }

    private function redirectToRoleDashboard(string $role)
    {
        return match ($role) {
            'super_admin'            => redirect()->route('admin.dashboard'),
            'accreditation_officer'  => redirect()->route('staff.officer.dashboard'),
            'registration_compliance_officer' => redirect()->route('staff.registration-compliance.dashboard'),
            'registrar'              => redirect()->route('staff.registrar.dashboard'),
            'accounts_payments'      => redirect()->route('staff.accounts.dashboard'),
            'production'             => redirect()->route('staff.production.dashboard'),
            'it_admin'               => redirect()->route('staff.it.dashboard'),
            'auditor'                => redirect()->route('staff.auditor.dashboard'),
            'director'               => redirect()->route('staff.director.dashboard'),
            'pr_officer'             => redirect()->route('staff.pr.dashboard'),
            'public_info_compliance' => redirect()->route('staff.public_info_compliance.dashboard'),
            'complaints_officer'     => redirect()->route('staff.research.dashboard'),
            'research_training'      => redirect()->route('staff.research.dashboard'),
            default                  => redirect()->route('staff.login'),
        };
    }

    private function staffAllowedRoles(): array
    {
        return [
            'super_admin',
            'accreditation_officer',
            'registration_compliance_officer',
            'registrar',
            'accounts_payments',
            'production',
            'it_admin',
            'auditor',
            'director',
            'pr_officer',
            'public_info_compliance',
            'complaints_officer',
            'research_training',
        ];
    }

    private function roleNamesForUser(int $userId): array
    {
        // First try to get roles from the roles/model_has_roles tables (Spatie permissions)
        $rolesTable = 'roles';
        $modelHasRolesTable = 'model_has_roles';

        $rolesFromTables = DB::table($modelHasRolesTable)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$modelHasRolesTable}.role_id")
            ->where("{$modelHasRolesTable}.model_id", $userId)
            ->where("{$modelHasRolesTable}.model_type", User::class)
            ->pluck("{$rolesTable}.name")
            ->map(fn ($r) => (string) $r)
            ->toArray();

        // If roles found in tables, return them
        if (count($rolesFromTables) > 0) {
            return $rolesFromTables;
        }

        // Fallback: check the user's role column
        $user = User::find($userId);
        if ($user && $user->role) {
            return [$user->role];
        }

        return [];
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '***@***';

        $name = $parts[0];
        $domain = $parts[1];

        if (strlen($name) <= 2) {
            $masked = $name[0] . '***';
        } else {
            $masked = $name[0] . str_repeat('*', strlen($name) - 2) . $name[strlen($name) - 1];
        }

        return $masked . '@' . $domain;
    }
}
