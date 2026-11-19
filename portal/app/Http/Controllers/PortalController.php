<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user && method_exists($user, 'hasRole')) {
            if ($user->hasRole('media_house')) {
                return redirect()->route('mediahouse.portal');
            }
            return redirect()->route('accreditation.portal');
        }
        return view('portal.welcome');
    }

    public function markNotificationsRead()
    {
        $user = auth()->user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }
        return response()->json(['success' => true]);
    }

    public function markNotificationRead($id)
    {
        $user = auth()->user();
        if ($user) {
            $notification = $user->notifications()->find($id);
            if ($notification) {
                $notification->markAsRead();
                return response()->json(['success' => true]);
            }
        }
        return response()->json(['success' => false], 404);
    }
}
