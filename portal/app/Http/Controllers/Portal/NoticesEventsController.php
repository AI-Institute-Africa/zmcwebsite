<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class NoticesEventsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $portal = ($user && ($user->role ?? '') === 'mediahouse') ? 'mediahouse' : 'journalist';

        $notices = Notice::query()
            ->where('is_published', true)
            ->whereIn('target_portal', [$portal,'both'])
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $events = Event::query()
            ->where('is_published', true)
            ->whereIn('target_portal', [$portal,'both'])
            ->orderBy('starts_at')
            ->limit(20)
            ->get();

        return view('portal.notices-events.index', compact('notices','events','portal'));
    }
}
