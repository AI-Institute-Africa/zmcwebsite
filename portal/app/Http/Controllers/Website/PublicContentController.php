<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Notice;
use App\Models\Event;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    public function news(Request $request)
    {
        $items = News::where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($items);
    }

    public function notices(Request $request)
    {
        $items = Notice::where('is_published', true)
            ->orderByDesc('published_at')
            ->paginate(20);
        return response()->json($items);
    }

    public function events(Request $request)
    {
        $items = Event::where('is_published', true)
            ->orderBy('starts_at')
            ->paginate(20);
        return response()->json($items);
    }

    public function showNotice(Notice $notice)
    {
        if (!$notice->is_published) {
            abort(404);
        }
        return view('website.notice-show', compact('notice'));
    }

    public function showEvent(Event $event)
    {
        if (!$event->is_published) {
            abort(404);
        }
        return view('website.event-show', compact('event'));
    }
}
