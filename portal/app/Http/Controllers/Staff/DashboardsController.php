<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardsController extends Controller
{
    public function home()
    {
        $role = Auth::user()->role;

        return match ($role) {
            'accreditation_officer' => redirect()->route('staff.accreditation'),
            'registrar'             => redirect()->route('staff.registrar'),
            'payments_accounts'     => redirect()->route('staff.payments'),
            'production'            => redirect()->route('staff.production'),
            'internal_auditor'      => redirect()->route('staff.audit'),
            'director_mdg'          => redirect()->route('staff.oversight'),
            default                 => abort(403),
        };
    }

    public function accreditation() { return view('staff.dashboards.accreditation'); }
    public function registrar()      { return view('staff.dashboards.registrar'); }
    public function payments()       { return view('staff.dashboards.payments'); }
    public function production()     { return view('staff.dashboards.production'); }

    public function audit()          { return view('staff.dashboards.audit'); }
    public function oversight()      { return view('staff.dashboards.oversight'); }
}
