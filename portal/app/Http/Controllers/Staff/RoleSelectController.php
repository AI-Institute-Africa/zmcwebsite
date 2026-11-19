<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleSelectController extends Controller
{
    public function index(Request $request)
    {
        $request->session()->forget('staff_selected_role');

        $roles = [
            [
                'key' => 'accreditation_officer',
                'title' => 'Accreditation Officer',
                'desc' => 'Review applications, verify documents, communicate with applicants, approve for payment or return for correction.',
                'icon' => 'ri-shield-user-line',
            ],
            [
                'key' => 'registration_compliance_officer',
                'title' => 'Registration & Compliance',
                'desc' => 'Monitor media house registration validity, practitioner accreditation expiry, and annual levy payment verification.',
                'icon' => 'ri-shield-check-line',
            ],
            [
                'key' => 'accounts_payments',
                'title' => 'Accounts / Payments',
                'desc' => 'Check waivers and payment proofs, receive payments via PayNow, approve to production or return for correction.',
                'icon' => 'ri-bank-card-line',
            ],
            [
                'key' => 'registrar',
                'title' => 'Registrar',
                'desc' => 'Supervisory oversight of approved applications. Review and mark applications as reviewed.',
                'icon' => 'ri-file-list-3-line',
            ],
            [
                'key' => 'production',
                'title' => 'Production Officer',
                'desc' => 'Generate press cards for media practitioners and certificates for media houses. Handle printing and issuance.',
                'icon' => 'ri-printer-line',
            ],
            [
                'key' => 'it_admin',
                'title' => 'IT Administrator',
                'desc' => 'User management, account creation, role and permission assignment. System administration and security oversight.',
                'icon' => 'ri-settings-3-line',
            ],
            [
                'key' => 'auditor',
                'title' => 'Auditor',
                'desc' => 'Read-only access to audit trails, financial oversight, compliance reporting across the entire workflow.',
                'icon' => 'ri-eye-line',
            ],
            [
                'key' => 'director',
                'title' => 'Director MDG',
                'desc' => 'Director Media Development and Governance. Strategic oversight of accreditation, registration, and compliance.',
                'icon' => 'ri-vip-crown-line',
            ],
            [
                'key' => 'pr_officer',
                'title' => 'Public Relations',
                'desc' => 'Manage notices, events, news, press statements, and downloadable content for the public portal.',
                'icon' => 'ri-megaphone-line',
            ],
            [
                'key' => 'public_info_compliance',
                'title' => 'Public Information Compliance',
                'desc' => 'Manage FOIA appeals and monitor public information compliance.',
                'icon' => 'ri-chat-check-line',
            ],
            [
                'key' => 'complaints_officer',
                'title' => 'Complaints Handling',
                'desc' => 'Register, assign, respond to, and report on complaints received from the public website.',
                'icon' => 'ri-chat-check-line',
            ],
            [
                'key' => 'research_training',
                'title' => 'Complaints Handling',
                'desc' => 'Register, assign, respond to, and report on complaints received from the public website.',
                'icon' => 'ri-chat-check-line',
            ],
            [
                'key' => 'super_admin',
                'title' => 'Super Admin',
                'desc' => 'Complete system access including all roles, settings, and administrative functions. Highest privilege level.',
                'icon' => 'ri-admin-line',
            ],
        ];

        if ($request->user()) {
            $roles = array_values(array_filter($roles, fn ($role) => $request->user()->hasRole($role['key'])));
        }

        return view('staff.select-role', [
            'roles' => $roles,
            'currentRole' => $request->session()->get('active_staff_role'),
            'switching' => $request->user() !== null,
        ]);
    }

    public function switchToRole(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $data = $request->validate(['role' => ['required', 'string', 'max:64']]);
        abort_unless($user->hasRole($data['role']), 403, 'That dashboard is not assigned to your account.');
        abort_if($user->hasRole('super_admin') && $data['role'] !== 'super_admin', 403, 'Super Admin cannot switch into another staff role.');

        $request->session()->put('active_staff_role', $data['role']);

        $dashboards = [
            'accreditation_officer' => 'staff.officer.dashboard',
            'registration_compliance_officer' => 'staff.registration-compliance.dashboard',
            'accounts_payments' => 'staff.accounts.dashboard',
            'registrar' => 'staff.registrar.dashboard',
            'production' => 'staff.production.dashboard',
            'it_admin' => 'staff.it.dashboard',
            'auditor' => 'staff.auditor.dashboard',
            'director' => 'admin.dashboard',
            'super_admin' => 'admin.dashboard',
            'pr_officer' => 'staff.pr.dashboard',
            'public_info_compliance' => 'staff.public_info_compliance.dashboard',
            'complaints_officer' => 'staff.research.dashboard',
            'research_training' => 'staff.research.dashboard',
        ];

        $route = $dashboards[$data['role']] ?? null;
        abort_unless($route && \Illuminate\Support\Facades\Route::has($route), 404, 'The assigned dashboard is unavailable.');

        return redirect()->route($route)
            ->withCookie(\App\Http\Middleware\TokenAuth::makeAuthCookie($user, $data['role']));
    }

    public function choose(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', 'string'],
        ]);

        // Super Admin gate: super_admin is oversight-only and may not impersonate another role.
        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin') && $data['role'] !== 'super_admin') {
            return redirect()->route('admin.dashboard')
                ->with('info', 'Super Admin uses the oversight dashboard. Other role dashboards belong to their respective officers.');
        }

        $request->session()->put('staff_selected_role', $data['role']);

        return redirect()->route('staff.login', ['role' => $data['role']]);
    }
}
