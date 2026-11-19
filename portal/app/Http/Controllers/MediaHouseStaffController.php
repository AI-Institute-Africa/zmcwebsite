<?php

namespace App\Http\Controllers;

use App\Models\MediaHouseStaff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MediaHouseStaffController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Typically media house portals use specific roles or account types
        abort_unless($user, 403);

        $staff = MediaHouseStaff::with('journalist')
            ->where('media_house_user_id', $user->id)
            ->get();

        return view('portal.mediahouse.staff.index', compact('staff'));
    }

    public function link(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['nullable', 'string', 'max:100'],
        ]);

        $journalist = User::where('email', $data['email'])->first();
        
        // Ensure not already linked
        $exists = MediaHouseStaff::where('media_house_user_id', $user->id)
            ->where('journalist_user_id', $journalist->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Media practitioner already linked to your media house.');
        }

        MediaHouseStaff::create([
            'media_house_user_id' => $user->id,
            'journalist_user_id' => $journalist->id,
            'role' => $data['role'],
            'status' => 'active',
        ]);

        return back()->with('success', 'Media practitioner linked successfully.');
    }

    public function unlink(MediaHouseStaff $staff)
    {
        $user = Auth::user();
        abort_unless($user && $staff->media_house_user_id === $user->id, 403);

        $staff->delete();

        return back()->with('success', 'Media practitioner unlinked successfully.');
    }
}
