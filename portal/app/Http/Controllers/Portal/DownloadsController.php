<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DownloadsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $docs = ApplicationDocument::query()
            ->whereHas('application', function ($q) use ($user) {
                $q->where('applicant_user_id', $user->id);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('portal.downloads.index', [
            'docs' => $docs,
            'portal' => $request->get('portal', 'accreditation'),
        ]);
    }

    public function download(Request $request, ApplicationDocument $doc)
    {
        $user = Auth::user();
        abort_unless($doc->application && $doc->application->applicant_user_id === $user->id, 403);

        if (!$doc->file_path || !Storage::disk('public')->exists($doc->file_path)) {
            abort(404);
        }

        return Storage::disk('public')->download($doc->file_path, $doc->original_name ?: basename($doc->file_path));
    }
}
