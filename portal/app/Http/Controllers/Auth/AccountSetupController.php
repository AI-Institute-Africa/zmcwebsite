<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Support\AuditTrail;

class AccountSetupController extends Controller
{
    /**
     * Show the account setup form.
     */
    public function show($token)
    {
        $user = User::where('setup_token', $token)->firstOrFail();

        return view('auth.account_setup', compact('user', 'token'));
    }

    /**
     * Handle the account setup request.
     */
    public function update(Request $request, $token)
    {
        $user = User::where('setup_token', $token)->firstOrFail();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->forceFill([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'setup_token' => null,
            'account_status' => 'active',
        ])->save();

        AuditTrail::log('account_setup_completed', $user, [
            'new_email' => $request->email,
            'new_name' => $request->name,
        ]);

        return redirect()->route('login')->with('success', 'Account setup complete. You can now login with your new credentials.');
    }
}
