<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationComment;
use App\Services\ActivityLogger;
use App\Services\ApplicationWorkflow;
use App\Support\RegionAccessGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationCommentController extends Controller
{
    public function store(Request $request, Application $application)
    {
        $role = session('active_staff_role');

        if (!$role) {
            abort(403, 'No active staff role found.');
        }

        // Region authorization: officers/production may only comment on
        // applications inside their assigned regions.
        RegionAccessGuard::ensure($application);

        $data = $request->validate([
            'body'         => ['required', 'string', 'max:5000'],
            'comment_type' => ['required', 'string', 'in:' . implode(',', array_keys(ApplicationComment::commentTypes()))],
            'visibility'   => ['sometimes', 'in:internal,applicant'],
        ]);

        // Comments visible to the applicant are still gated to roles that
        // currently "own" the application's stage (plus super roles).
        if (($data['visibility'] ?? 'internal') === 'applicant') {
            // Map active staff role → workflow role used by statusesForRole().
            // Without this, role names like `accounts_payments` silently fall
            // through to the default empty list and bypass the stage check.
            $workflowRole = match ($role) {
                'accounts_payments' => 'accounts',
                default => $role,
            };
            $allowedStatuses = ApplicationWorkflow::statusesForRole($workflowRole);
            $isSuperRole = in_array($role, ['super_admin', 'director', 'it_admin'], true);
            if (!$isSuperRole && !in_array($application->status, $allowedStatuses, true)) {
                abort(403, 'Only staff currently handling this application can post comments visible to the applicant.');
            }
        }

        $comment = ApplicationComment::create([
            'application_id'             => $application->id,
            'user_id'                    => Auth::id(),
            'user_role'                  => $role,
            'visibility'                 => $data['visibility'] ?? 'internal',
            'comment_type'               => $data['comment_type'],
            'body'                       => $data['body'],
            'application_status_at_time' => $application->status,
            'ip_address'                 => $request->ip(),
        ]);

        ActivityLogger::log('application_comment_added', $application, $application->status, $application->status, [
            'comment_id'    => $comment->id,
            'comment_type'  => $comment->comment_type,
            'actor_role'    => $role,
            'actor_user_id' => Auth::id(),
            'visibility'    => $comment->visibility,
        ]);

        // Notify the applicant in-app when an applicant-visible message is
        // posted (so payment/correction notes from staff reach the applicant
        // without them having to log in and look). Failures here must never
        // block the staff action.
        if ($comment->visibility === ApplicationComment::VISIBILITY_APPLICANT) {
            try {
                $applicant = $application->applicant ?? \App\Models\User::find($application->applicant_user_id ?? $application->user_id);
                if ($applicant) {
                    $excerpt = \Illuminate\Support\Str::limit(trim((string) $comment->body), 180);
                    $typeLabel = $comment->getTypeLabel();
                    $msg = "{$typeLabel} from staff on application {$application->reference}: {$excerpt}";
                    $applicant->notify(new \App\Notifications\ApplicationStatusChanged($application, $msg));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'comment' => [
                    'id'         => $comment->id,
                    'body'       => $comment->body,
                    'visibility' => $comment->visibility,
                    'user_name'  => Auth::user()?->name ?? 'Staff',
                    'user_role'  => $comment->user_role,
                    'created_at' => optional($comment->created_at)->toDateTimeString(),
                    'created_human' => optional($comment->created_at)->diffForHumans(),
                ],
            ]);
        }

        return back()->with('success', 'Comment added successfully.');
    }
}
