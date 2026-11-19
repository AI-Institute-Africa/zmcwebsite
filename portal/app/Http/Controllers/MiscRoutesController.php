<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MiscRoutesController extends Controller
{
    public function choosePortal(Request $request)
    {
        $data = $request->validate([
            'portal' => ['required', 'in:journalist,mass_media'],
        ]);

        $request->session()->put('public_selected_portal', $data['portal']);

        return redirect()->route('login');
    }

    public function switchApplicantProfile(Request $request)
    {
        $data = $request->validate(['portal' => ['required', 'in:accreditation,mediahouse']]);
        $user = $request->user();
        abort_unless($user, 401);

        $canAccess = $data['portal'] === 'accreditation'
            ? $user->isMediaPractitioner()
            : $user->isMediaHouseStaff();
        abort_unless($canAccess, 403, 'This profile is not linked to your account.');

        $request->session()->put('active_applicant_portal', $data['portal']);

        return redirect()->route($data['portal'] === 'accreditation' ? 'accreditation.home' : 'mediahouse.portal');
    }

    public function switchLang(Request $request, string $locale)
    {
        abort_unless(in_array($locale, ['en','sn','nd','ny','cwa','kck','nmq','ndc','tso','st','toi','tn','ven','xh'], true), 404);
        $request->session()->put('app_locale', $locale);
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
        return back();
    }

    public function home(Request $request)
    {
        $selected = $request->session()->get('public_selected_portal');

        if ($selected === 'journalist') {
            $request->session()->forget('public_selected_portal');
            return redirect()->route('accreditation.home');
        }

        if ($selected === 'mass_media') {
            $request->session()->forget('public_selected_portal');
            return redirect()->route('mediahouse.portal');
        }

        return redirect()->route('portal');
    }
}
