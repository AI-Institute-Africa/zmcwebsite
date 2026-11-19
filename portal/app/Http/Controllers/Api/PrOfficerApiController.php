<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Notice;
use App\Models\Event;
use App\Models\Vacancy;
use App\Models\Tender;
use App\Models\WebsiteDownload;
use App\Models\Subscriber;
use App\Services\SubscriberNotificationService;
use App\Support\AuditTrail;
use App\Support\FileType;
use App\Support\NewsBodySanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * PR Officer Content Management API
 *
 * Authenticated endpoints (Laravel Sanctum token) for the PR Officer role.
 * These are intended to allow the ZMC Website CMS (or any authorised client)
 * to push/pull content from the Portal.
 *
 * Authentication:
 *   POST /api/v1/pr/login  →  returns { token }
 *   All other routes:  Authorization: Bearer <token>
 *
 * Content types managed:
 *   News       /api/v1/pr/news
 *   Notices    /api/v1/pr/notices
 *   Events     /api/v1/pr/events
 *   Vacancies  /api/v1/pr/vacancies
 *   Tenders    /api/v1/pr/tenders
 *   Downloads  /api/v1/pr/downloads
 *   Stats      /api/v1/pr/stats        (read-only summary)
 *   Subscribers /api/v1/pr/subscribers  (read-only)
 */
class PrOfficerApiController extends Controller
{
    // ─── Auth ─────────────────────────────────────────────────────────────────

    /**
     * POST /api/v1/pr/login
     * Issue a Sanctum token for a staff user with the pr_officer or super_admin role.
     *
     * Body: { "email": "...", "password": "..." }
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!\Illuminate\Support\Facades\Auth::attempt($credentials)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user = \Illuminate\Support\Facades\Auth::user();

        // Only PR Officer and Super Admin can use this API
        if (!$user->hasAnyRole(['pr_officer', 'super_admin'])) {
            \Illuminate\Support\Facades\Auth::logout();
            return response()->json([
                'status'  => 'error',
                'message' => 'Access denied. This API is restricted to PR Officers.',
            ], 403);
        }

        // Revoke any old PR API tokens and issue a fresh one
        $user->tokens()->where('name', 'pr-api')->delete();
        $token = $user->createToken('pr-api', ['pr-officer'])->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token'  => $token,
            'user'   => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->getRoleNames()->first(),
            ],
        ]);
    }

    /**
     * POST /api/v1/pr/logout
     * Revoke the current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    // ─── Dashboard Stats ───────────────────────────────────────────────────────

    /**
     * GET /api/v1/pr/stats
     */
    public function stats()
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'news'      => ['total' => News::count(),      'published' => News::where('is_published', true)->count()],
                'notices'   => ['total' => Notice::count(),    'published' => Notice::where('is_published', true)->count()],
                'events'    => ['total' => Event::count(),     'published' => Event::where('is_published', true)->count()],
                'vacancies' => ['total' => Vacancy::count(),   'published' => Vacancy::where('is_published', true)->count(), 'open' => Vacancy::where('is_published', true)->where(fn($q) => $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()))->count()],
                'tenders'   => ['total' => Tender::count(),    'published' => Tender::where('is_published', true)->count(),  'open' => Tender::where('is_published', true)->where(fn($q) => $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()))->count()],
                'downloads' => ['total' => WebsiteDownload::count(), 'published' => WebsiteDownload::where('is_published', true)->count()],
                'subscribers' => Subscriber::count(),
            ],
        ]);
    }

    // ─── Subscribers (read-only) ───────────────────────────────────────────────

    /**
     * GET /api/v1/pr/subscribers
     */
    public function subscribers(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 50), 200);
        $search  = $request->input('search', '');

        $query = Subscriber::orderByDesc('created_at');
        if ($search) {
            $query->where(fn($q) => $q->where('email', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%"));
        }

        $records = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $records->items(),
            'meta'   => [
                'total'        => $records->total(),
                'current_page' => $records->currentPage(),
                'last_page'    => $records->lastPage(),
                'per_page'     => $records->perPage(),
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  NEWS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/v1/pr/news
     * List all news (published + drafts) for the PR Officer.
     */
    public function indexNews(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = News::orderByDesc('id');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('body', 'like', "%{$s}%"));
        }
        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }

        $records = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $records->map(fn($n) => $this->formatNews($n))->values(),
            'meta'   => $this->paginationMeta($records),
        ]);
    }

    /**
     * POST /api/v1/pr/news
     */
    public function storeNews(Request $request)
    {
        $data = $request->validate([
            'title'              => 'required|string|max:200',
            'body'               => 'required|string',
            'category'           => 'nullable|string|max:100',
            'tags'               => 'nullable|string|max:500',
            'excerpt'            => 'nullable|string|max:160',
            'author_name'        => 'nullable|string|max:120',
            'featured'           => 'nullable|boolean',
            'language'           => 'nullable|string|max:50',
            'source_reference'   => 'nullable|string|max:200',
            'image_alt_text'     => 'nullable|string|max:200',
            'allow_comments'     => 'nullable|boolean',
            'notify_subscribers' => 'nullable|boolean',
            'meta_description'   => 'nullable|string|max:320',
            'slug'               => 'nullable|string|max:200',
            'is_published'       => 'nullable|boolean',
            'published_at'       => 'nullable|date',
            'image'              => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,gif',
            'images'             => 'nullable|array|max:10',
            'images.*'           => 'image|max:5120|mimes:jpg,jpeg,png,webp,gif',
            'attachment'         => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp',
        ]);

        $data['category']     = $this->normalizeNewsCategory($data['category'] ?? null);
        $data['body']         = NewsBodySanitizer::clean($data['body']);
        $data['slug']         = $data['slug'] ?? News::makeSlug($data['title']);
        $data['is_published'] = (bool) ($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['created_by']   = $request->user()->id;

        $this->handleFileUploads($request, $data, 'content/news');
        $data = array_merge($data, $this->storeNewsGallery($request, null, $data['image_path'] ?? null));

        $news = News::create($data);

        if ($data['is_published'] && ($data['notify_subscribers'] ?? false)) {
            $this->dispatchSubscriberNotification('news', $news);
        }

        AuditTrail::log('api_news_create', null, ['news_id' => $news->id, 'title' => $news->title, 'by' => $request->user()->id]);

        return response()->json([
            'status'  => 'success',
            'message' => 'News article created.',
            'data'    => $this->formatNews($news),
        ], 201);
    }

    /**
     * GET /api/v1/pr/news/{id}
     */
    public function showNews(News $news)
    {
        return response()->json(['status' => 'success', 'data' => $this->formatNews($news)]);
    }

    /**
     * PUT/PATCH /api/v1/pr/news/{id}
     */
    public function updateNews(Request $request, News $news)
    {
        $data = $request->validate([
            'title'              => 'sometimes|required|string|max:200',
            'body'               => 'sometimes|required|string',
            'category'           => 'nullable|string|max:100',
            'tags'               => 'nullable|string|max:500',
            'excerpt'            => 'nullable|string|max:160',
            'author_name'        => 'nullable|string|max:120',
            'featured'           => 'nullable|boolean',
            'language'           => 'nullable|string|max:50',
            'source_reference'   => 'nullable|string|max:200',
            'image_alt_text'     => 'nullable|string|max:200',
            'allow_comments'     => 'nullable|boolean',
            'notify_subscribers' => 'nullable|boolean',
            'meta_description'   => 'nullable|string|max:320',
            'is_published'       => 'nullable|boolean',
            'published_at'       => 'nullable|date',
            'image'              => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp,gif',
            'images'             => 'nullable|array|max:10',
            'images.*'           => 'image|max:5120|mimes:jpg,jpeg,png,webp,gif',
            'remove_image_paths' => 'nullable|array',
            'remove_image_paths.*' => 'string|max:500',
            'attachment'         => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp',
        ]);

        $wasPublished = $news->is_published;
        if (array_key_exists('category', $data)) {
            $data['category'] = $this->normalizeNewsCategory($data['category']);
        }
        if (array_key_exists('body', $data)) {
            $data['body'] = NewsBodySanitizer::clean($data['body']);
        }
        if (isset($data['is_published'])) {
            $data['is_published'] = (bool) $data['is_published'];
            if ($data['is_published'] && !$wasPublished) {
                $data['published_at'] = $data['published_at'] ?? now();
            }
        }

        $this->handleFileUploads($request, $data, 'content/news');
        $data = array_merge($data, $this->storeNewsGallery($request, $news, $data['image_path'] ?? null));
        $this->cleanupOldFiles($news, $data);
        $news->update($data);

        AuditTrail::log('api_news_update', null, ['news_id' => $news->id, 'by' => $request->user()->id]);

        return response()->json(['status' => 'success', 'message' => 'News article updated.', 'data' => $this->formatNews($news->fresh())]);
    }

    /**
     * DELETE /api/v1/pr/news/{id}
     */
    public function destroyNews(Request $request, News $news)
    {
        $id = $news->id;
        foreach (array_unique(array_filter(array_merge((array) $news->image_paths, [$news->image_path]))) as $imagePath) {
            $this->deleteFileIfExists($imagePath);
        }
        $this->deleteFileIfExists($news->attachment_path);
        $news->delete();

        AuditTrail::log('api_news_delete', null, ['news_id' => $id, 'by' => $request->user()->id]);

        return response()->json(['status' => 'success', 'message' => 'News article deleted.']);
    }

    /**
     * PATCH /api/v1/pr/news/{id}/publish
     * Toggle publish status.
     */
    public function publishNews(Request $request, News $news)
    {
        $publish = filter_var($request->input('publish', true), FILTER_VALIDATE_BOOLEAN);
        $news->update([
            'is_published' => $publish,
            'published_at' => $publish ? ($news->published_at ?? now()) : null,
        ]);
        return response()->json(['status' => 'success', 'message' => $publish ? 'Article published.' : 'Article unpublished.', 'is_published' => $publish]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  NOTICES
    // ═══════════════════════════════════════════════════════════════════════════

    /** GET /api/v1/pr/notices */
    public function indexNotices(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query = Notice::orderByDesc('id');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('body', 'like', "%{$s}%"));
        }
        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }

        $records = $query->paginate($perPage);
        return response()->json([
            'status' => 'success',
            'data'   => $records->map(fn($n) => $this->withFileUrls($n->toArray()))->values(),
            'meta'   => $this->paginationMeta($records),
        ]);
    }

    /** POST /api/v1/pr/notices */
    public function storeNotice(Request $request)
    {
        $data = $request->validate([
            'title'                   => 'required|string|max:200',
            'notice_category'         => 'nullable|string|max:100',
            'reference_number'        => 'nullable|string|max:100',
            'body'                    => 'nullable|string',
            'summary'                 => 'nullable|string|max:500',
            'target_portal'           => 'nullable|in:journalist,mediahouse,both',
            'target_audience'         => 'nullable|string|max:200',
            'issuing_authority'       => 'nullable|string|max:200',
            'signed_by'               => 'nullable|string|max:200',
            'urgency_level'           => 'nullable|string|max:50',
            'effective_date'          => 'nullable|date',
            'expires_at'              => 'nullable|date',
            'language'                => 'nullable|string|max:50',
            'attachment_label'        => 'nullable|string|max:100',
            'related_legal_reference' => 'nullable|string|max:300',
            'contact_name'            => 'nullable|string|max:150',
            'contact_phone'           => 'nullable|string|max:50',
            'contact_email'           => 'nullable|email|max:200',
            'is_published'            => 'nullable|boolean',
            'published_at'            => 'nullable|date',
            'stick_to_top'            => 'nullable|boolean',
            'show_on_homepage'        => 'nullable|boolean',
            'image'                   => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp',
            'attachment'              => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx',
        ]);

        $data['target_portal']    = $data['target_portal'] ?? 'both';
        $data['is_published']     = (bool) ($data['is_published'] ?? true);
        $data['published_at']     = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['stick_to_top']     = (bool) ($data['stick_to_top'] ?? false);
        $data['show_on_homepage'] = (bool) ($data['show_on_homepage'] ?? false);
        $data['created_by']       = $request->user()->id;

        $this->handleFileUploads($request, $data, 'content/notices');

        $notice = Notice::create($data);
        AuditTrail::log('api_notice_create', null, ['notice_id' => $notice->id, 'by' => $request->user()->id]);

        return response()->json(['status' => 'success', 'message' => 'Notice created.', 'data' => $this->withFileUrls($notice->toArray())], 201);
    }

    /** GET /api/v1/pr/notices/{id} */
    public function showNotice(Notice $notice)
    {
        return response()->json(['status' => 'success', 'data' => $this->withFileUrls($notice->toArray())]);
    }

    /** PUT /api/v1/pr/notices/{id} */
    public function updateNotice(Request $request, Notice $notice)
    {
        $data = $request->validate([
            'title'                   => 'sometimes|required|string|max:200',
            'notice_category'         => 'nullable|string|max:100',
            'reference_number'        => 'nullable|string|max:100',
            'body'                    => 'nullable|string',
            'summary'                 => 'nullable|string|max:500',
            'target_portal'           => 'nullable|in:journalist,mediahouse,both',
            'target_audience'         => 'nullable|string|max:200',
            'issuing_authority'       => 'nullable|string|max:200',
            'signed_by'               => 'nullable|string|max:200',
            'urgency_level'           => 'nullable|string|max:50',
            'effective_date'          => 'nullable|date',
            'expires_at'              => 'nullable|date',
            'language'                => 'nullable|string|max:50',
            'attachment_label'        => 'nullable|string|max:100',
            'related_legal_reference' => 'nullable|string|max:300',
            'contact_name'            => 'nullable|string|max:150',
            'contact_phone'           => 'nullable|string|max:50',
            'contact_email'           => 'nullable|email|max:200',
            'is_published'            => 'nullable|boolean',
            'published_at'            => 'nullable|date',
            'stick_to_top'            => 'nullable|boolean',
            'show_on_homepage'        => 'nullable|boolean',
            'image'                   => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp',
            'attachment'              => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx',
        ]);

        if (isset($data['is_published'])) {
            $data['is_published'] = (bool) $data['is_published'];
        }

        $this->handleFileUploads($request, $data, 'content/notices');
        $this->cleanupOldFiles($notice, $data);
        $notice->update($data);

        AuditTrail::log('api_notice_update', null, ['notice_id' => $notice->id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Notice updated.', 'data' => $this->withFileUrls($notice->fresh()->toArray())]);
    }

    /** DELETE /api/v1/pr/notices/{id} */
    public function destroyNotice(Request $request, Notice $notice)
    {
        $id = $notice->id;
        $this->deleteFileIfExists($notice->image_path);
        $this->deleteFileIfExists($notice->attachment_path);
        $notice->delete();
        AuditTrail::log('api_notice_delete', null, ['notice_id' => $id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Notice deleted.']);
    }

    /** PATCH /api/v1/pr/notices/{id}/publish */
    public function publishNotice(Request $request, Notice $notice)
    {
        $publish = filter_var($request->input('publish', true), FILTER_VALIDATE_BOOLEAN);
        $notice->update(['is_published' => $publish, 'published_at' => $publish ? ($notice->published_at ?? now()) : null]);
        return response()->json(['status' => 'success', 'is_published' => $publish]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  EVENTS
    // ═══════════════════════════════════════════════════════════════════════════

    /** GET /api/v1/pr/events */
    public function indexEvents(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query   = Event::orderByDesc('starts_at');
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%"));
        }
        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }
        $records = $query->paginate($perPage);
        return response()->json([
            'status' => 'success',
            'data'   => $records->map(fn($e) => $this->withFileUrls($e->toArray()))->values(),
            'meta'   => $this->paginationMeta($records),
        ]);
    }

    /** POST /api/v1/pr/events */
    public function storeEvent(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:200',
            'description'   => 'nullable|string',
            'location'      => 'nullable|string|max:200',
            'starts_at'     => 'nullable|date',
            'ends_at'       => 'nullable|date|after_or_equal:starts_at',
            'target_portal' => 'nullable|in:journalist,mediahouse,both',
            'is_published'  => 'nullable|boolean',
            'image'         => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp',
            'attachment'    => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp',
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? true);
        $data['published_at'] = $data['is_published'] ? now() : null;
        $data['starts_at']    = $data['starts_at'] ?? now();
        $data['target_portal'] = $data['target_portal'] ?? 'both';
        $data['created_by']   = $request->user()->id;

        $this->handleFileUploads($request, $data, 'content/events');
        $event = Event::create($data);

        if ($data['is_published']) {
            $this->dispatchSubscriberNotification('event', $event);
        }

        AuditTrail::log('api_event_create', null, ['event_id' => $event->id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Event created.', 'data' => $this->withFileUrls($event->toArray())], 201);
    }

    /** GET /api/v1/pr/events/{id} */
    public function showEvent(Event $event)
    {
        return response()->json(['status' => 'success', 'data' => $this->withFileUrls($event->toArray())]);
    }

    /** PUT /api/v1/pr/events/{id} */
    public function updateEvent(Request $request, Event $event)
    {
        $data = $request->validate([
            'title'         => 'sometimes|required|string|max:200',
            'description'   => 'nullable|string',
            'location'      => 'nullable|string|max:200',
            'starts_at'     => 'nullable|date',
            'ends_at'       => 'nullable|date|after_or_equal:starts_at',
            'target_portal' => 'nullable|in:journalist,mediahouse,both',
            'is_published'  => 'nullable|boolean',
            'image'         => 'nullable|file|max:5120|mimes:jpg,jpeg,png,webp',
            'attachment'    => 'nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp',
        ]);

        $wasPublished = $event->is_published;
        if (isset($data['is_published'])) {
            $data['is_published'] = (bool) $data['is_published'];
            if ($data['is_published'] && !$wasPublished) {
                $data['published_at'] = now();
                $this->dispatchSubscriberNotification('event', $event);
            }
        }

        $this->handleFileUploads($request, $data, 'content/events');
        $this->cleanupOldFiles($event, $data);
        $event->update($data);

        AuditTrail::log('api_event_update', null, ['event_id' => $event->id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Event updated.', 'data' => $this->withFileUrls($event->fresh()->toArray())]);
    }

    /** DELETE /api/v1/pr/events/{id} */
    public function destroyEvent(Request $request, Event $event)
    {
        $id = $event->id;
        $this->deleteFileIfExists($event->image_path);
        $this->deleteFileIfExists($event->attachment_path);
        $event->delete();
        AuditTrail::log('api_event_delete', null, ['event_id' => $id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Event deleted.']);
    }

    /** PATCH /api/v1/pr/events/{id}/publish */
    public function publishEvent(Request $request, Event $event)
    {
        $publish = filter_var($request->input('publish', true), FILTER_VALIDATE_BOOLEAN);
        $event->update(['is_published' => $publish, 'published_at' => $publish ? ($event->published_at ?? now()) : null]);
        return response()->json(['status' => 'success', 'is_published' => $publish]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  VACANCIES
    // ═══════════════════════════════════════════════════════════════════════════

    /** GET /api/v1/pr/vacancies */
    public function indexVacancies(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query   = Vacancy::orderByDesc('id');
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('department', 'like', "%{$s}%"));
        }
        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }
        $records = $query->paginate($perPage);
        return response()->json([
            'status' => 'success',
            'data'   => $records->map(fn($v) => $this->withFileUrls($v->toArray()))->values(),
            'meta'   => $this->paginationMeta($records),
        ]);
    }

    /** POST /api/v1/pr/vacancies */
    public function storeVacancy(Request $request)
    {
        $data = $request->validate([
            'title'                         => 'required|string|max:200',
            'vacancy_reference'             => 'nullable|string|max:100',
            'department'                    => 'nullable|string|max:100',
            'job_grade'                     => 'nullable|string|max:50',
            'employment_type'               => 'nullable|string|max:50',
            'contract_duration'             => 'nullable|string|max:100',
            'salary_range'                  => 'nullable|string|max:100',
            'salary_display'                => 'nullable|boolean',
            'body'                          => 'nullable|string',
            'qualifications_required'       => 'nullable|string',
            'experience_required'           => 'nullable|string',
            'skills_competencies'           => 'nullable|string',
            'duty_station'                  => 'nullable|string|max:100',
            'submission_method'             => 'nullable|string|max:100',
            'submission_email'              => 'nullable|email|max:200',
            'submission_address'            => 'nullable|string',
            'closing_at'                    => 'nullable|date',
            'contact_name'                  => 'nullable|string|max:150',
            'contact_title'                 => 'nullable|string|max:100',
            'contact_email'                 => 'nullable|email|max:200',
            'contact_phone'                 => 'nullable|string|max:50',
            'number_of_positions'           => 'nullable|integer|min:1',
            'target_portal'                 => 'nullable|in:journalist,mediahouse,both',
            'is_published'                  => 'nullable|boolean',
            'published_at'                  => 'nullable|date',
            'attachment'                    => 'nullable|file|max:10240|mimes:pdf,doc,docx',
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['target_portal'] = $data['target_portal'] ?? 'both';
        $data['created_by']   = $request->user()->id;

        $this->handleFileUploads($request, $data, 'content/vacancies');
        $vacancy = Vacancy::create($data);

        AuditTrail::log('api_vacancy_create', null, ['vacancy_id' => $vacancy->id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Vacancy created.', 'data' => $this->withFileUrls($vacancy->toArray())], 201);
    }

    /** GET /api/v1/pr/vacancies/{id} */
    public function showVacancy(Vacancy $vacancy)
    {
        return response()->json(['status' => 'success', 'data' => $this->withFileUrls($vacancy->toArray())]);
    }

    /** PUT /api/v1/pr/vacancies/{id} */
    public function updateVacancy(Request $request, Vacancy $vacancy)
    {
        $data = $request->validate([
            'title'               => 'sometimes|required|string|max:200',
            'vacancy_reference'   => 'nullable|string|max:100',
            'department'          => 'nullable|string|max:100',
            'employment_type'     => 'nullable|string|max:50',
            'body'                => 'nullable|string',
            'closing_at'          => 'nullable|date',
            'contact_email'       => 'nullable|email|max:200',
            'contact_phone'       => 'nullable|string|max:50',
            'number_of_positions' => 'nullable|integer|min:1',
            'is_published'        => 'nullable|boolean',
            'published_at'        => 'nullable|date',
            'attachment'          => 'nullable|file|max:10240|mimes:pdf,doc,docx',
        ]);

        if (isset($data['is_published'])) {
            $data['is_published'] = (bool) $data['is_published'];
            if ($data['is_published'] && !$vacancy->is_published) {
                $data['published_at'] = $data['published_at'] ?? now();
            }
        }

        $this->handleFileUploads($request, $data, 'content/vacancies');
        $this->cleanupOldFiles($vacancy, $data);
        $vacancy->update($data);

        AuditTrail::log('api_vacancy_update', null, ['vacancy_id' => $vacancy->id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Vacancy updated.', 'data' => $this->withFileUrls($vacancy->fresh()->toArray())]);
    }

    /** DELETE /api/v1/pr/vacancies/{id} */
    public function destroyVacancy(Request $request, Vacancy $vacancy)
    {
        $id = $vacancy->id;
        $this->deleteFileIfExists($vacancy->attachment_path);
        $vacancy->delete();
        AuditTrail::log('api_vacancy_delete', null, ['vacancy_id' => $id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Vacancy deleted.']);
    }

    /** PATCH /api/v1/pr/vacancies/{id}/publish */
    public function publishVacancy(Request $request, Vacancy $vacancy)
    {
        $publish = filter_var($request->input('publish', true), FILTER_VALIDATE_BOOLEAN);
        $vacancy->update(['is_published' => $publish, 'published_at' => $publish ? ($vacancy->published_at ?? now()) : null]);
        return response()->json(['status' => 'success', 'is_published' => $publish]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  TENDERS
    // ═══════════════════════════════════════════════════════════════════════════

    /** GET /api/v1/pr/tenders */
    public function indexTenders(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $query   = Tender::orderByDesc('id');
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('tender_reference', 'like', "%{$s}%"));
        }
        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }
        $records = $query->paginate($perPage);
        return response()->json([
            'status' => 'success',
            'data'   => $records->map(fn($t) => $this->withFileUrls($t->toArray()))->values(),
            'meta'   => $this->paginationMeta($records),
        ]);
    }

    /** POST /api/v1/pr/tenders */
    public function storeTender(Request $request)
    {
        $data = $request->validate([
            'title'                     => 'required|string|max:200',
            'tender_reference'          => 'nullable|string|max:100',
            'issuing_organisation'      => 'nullable|string|max:190',
            'summary'                   => 'nullable|string|max:5000',
            'egp_url'                   => 'nullable|url|max:2048',
            'procurement_method'        => 'nullable|string|max:100',
            'tender_category'           => 'nullable|string|max:100',
            'description'               => 'nullable|string',
            'contract_value_estimate'   => 'nullable|numeric',
            'bid_security_required'     => 'nullable|boolean',
            'bid_security_amount'       => 'nullable|numeric',
            'closing_at'                => 'nullable|date',
            'opening_at'                => 'nullable|date',
            'opening_venue'             => 'nullable|string|max:200',
            'submission_method'         => 'nullable|string|max:100',
            'submission_address'        => 'nullable|string',
            'submission_email'          => 'nullable|email|max:200',
            'contact_name'              => 'nullable|string|max:150',
            'contact_title'             => 'nullable|string|max:100',
            'contact_email'             => 'nullable|email|max:200',
            'contact_phone'             => 'nullable|string|max:50',
            'questions_deadline'        => 'nullable|date',
            'bidding_documents_link'    => 'nullable|url|max:500',
            'evaluation_criteria'       => 'nullable|string',
            'tender_status'             => 'nullable|string|max:50',
            'language'                  => 'nullable|string|max:50',
            'is_published'              => 'nullable|boolean',
            'published_at'              => 'nullable|date',
            'attachment'                => 'nullable|file|max:10240|mimes:pdf,doc,docx',
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['created_by']   = $request->user()->id;
        if (array_key_exists('egp_url', $data)) {
            $data['bidding_documents_link'] = $data['egp_url'];
            unset($data['egp_url']);
        }

        $this->handleFileUploads($request, $data, 'content/tenders');
        $tender = Tender::create($data);

        AuditTrail::log('api_tender_create', null, ['tender_id' => $tender->id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Tender created.', 'data' => $this->withFileUrls($tender->toArray())], 201);
    }

    /** GET /api/v1/pr/tenders/{id} */
    public function showTender(Tender $tender)
    {
        return response()->json(['status' => 'success', 'data' => $this->withFileUrls($tender->toArray())]);
    }

    /** PUT /api/v1/pr/tenders/{id} */
    public function updateTender(Request $request, Tender $tender)
    {
        $data = $request->validate([
            'title'              => 'sometimes|required|string|max:200',
            'tender_reference'   => 'nullable|string|max:100',
            'issuing_organisation' => 'sometimes|nullable|string|max:190',
            'summary'            => 'sometimes|nullable|string|max:5000',
            'egp_url'            => 'sometimes|nullable|url|max:2048',
            'description'        => 'nullable|string',
            'closing_at'         => 'nullable|date',
            'tender_status'      => 'nullable|string|max:50',
            'is_published'       => 'nullable|boolean',
            'published_at'       => 'nullable|date',
            'attachment'         => 'nullable|file|max:10240|mimes:pdf,doc,docx',
        ]);

        if (isset($data['is_published'])) {
            $data['is_published'] = (bool) $data['is_published'];
            if ($data['is_published'] && !$tender->is_published) {
                $data['published_at'] = $data['published_at'] ?? now();
            }
        }
        if (array_key_exists('egp_url', $data)) {
            $data['bidding_documents_link'] = $data['egp_url'];
            unset($data['egp_url']);
        }

        $this->handleFileUploads($request, $data, 'content/tenders');
        $this->cleanupOldFiles($tender, $data);
        $tender->update($data);

        AuditTrail::log('api_tender_update', null, ['tender_id' => $tender->id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Tender updated.', 'data' => $this->withFileUrls($tender->fresh()->toArray())]);
    }

    /** DELETE /api/v1/pr/tenders/{id} */
    public function destroyTender(Request $request, Tender $tender)
    {
        $id = $tender->id;
        $this->deleteFileIfExists($tender->attachment_path);
        $tender->delete();
        AuditTrail::log('api_tender_delete', null, ['tender_id' => $id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Tender deleted.']);
    }

    /** PATCH /api/v1/pr/tenders/{id}/publish */
    public function publishTender(Request $request, Tender $tender)
    {
        $publish = filter_var($request->input('publish', true), FILTER_VALIDATE_BOOLEAN);
        $tender->update(['is_published' => $publish, 'published_at' => $publish ? ($tender->published_at ?? now()) : null]);
        return response()->json(['status' => 'success', 'is_published' => $publish]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  DOWNLOADS
    // ═══════════════════════════════════════════════════════════════════════════

    /** GET /api/v1/pr/downloads */
    public function indexDownloads(Request $request)
    {
        $query = WebsiteDownload::orderBy('category')->orderByDesc('id');
        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }
        $records = $query->paginate(min((int) $request->input('per_page', 50), 200));
        return response()->json([
            'status' => 'success',
            'data'   => $records->map(fn($d) => $this->withFileUrls($d->toArray()))->values(),
            'meta'   => $this->paginationMeta($records),
        ]);
    }

    /** POST /api/v1/pr/downloads */
    public function storeDownload(Request $request)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:300',
            'description'    => 'nullable|string|max:1000',
            'category'       => 'required|string|max:100',
            'subcategory'    => 'nullable|string|max:100',
            'version'        => 'nullable|string|max:50',
            'language'       => 'nullable|string|max:50',
            'effective_date' => 'nullable|date',
            'display_order'  => 'nullable|integer',
            'featured'       => 'nullable|boolean',
            'is_published'   => 'nullable|boolean',
            'published_at'   => 'nullable|date',
            'file'           => 'required|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt',
        ]);

        $data['is_published'] = (bool) ($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['created_by']   = $request->user()->id;

        if ($request->hasFile('file')) {
            $f = $request->file('file');
            $data['file_path']          = $this->storePublicFile($f, 'content/downloads', FileType::safeHashName($f));
            $data['file_original_name'] = $f->getClientOriginalName();
            $data['file_mime']          = FileType::safeUploadedMime($f);
            $data['file_size_bytes']    = $f->getSize();
        }

        $download = WebsiteDownload::create($data);
        AuditTrail::log('api_download_create', null, ['download_id' => $download->id, 'by' => $request->user()->id]);

        return response()->json(['status' => 'success', 'message' => 'Download created.', 'data' => $this->withFileUrls($download->toArray())], 201);
    }

    /** GET /api/v1/pr/downloads/{id} */
    public function showDownload(WebsiteDownload $download)
    {
        return response()->json(['status' => 'success', 'data' => $this->withFileUrls($download->toArray())]);
    }

    /** PUT /api/v1/pr/downloads/{id} */
    public function updateDownload(Request $request, WebsiteDownload $download)
    {
        $data = $request->validate([
            'title'          => 'sometimes|required|string|max:300',
            'description'    => 'nullable|string|max:1000',
            'category'       => 'sometimes|required|string|max:100',
            'subcategory'    => 'nullable|string|max:100',
            'version'        => 'nullable|string|max:50',
            'effective_date' => 'nullable|date',
            'display_order'  => 'nullable|integer',
            'featured'       => 'nullable|boolean',
            'is_published'   => 'nullable|boolean',
            'file'           => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt',
        ]);

        if (isset($data['is_published'])) {
            $data['is_published'] = (bool) $data['is_published'];
        }

        if ($request->hasFile('file')) {
            $this->deleteFileIfExists($download->file_path);
            $f = $request->file('file');
            $data['file_path']          = $this->storePublicFile($f, 'content/downloads', FileType::safeHashName($f));
            $data['file_original_name'] = $f->getClientOriginalName();
            $data['file_mime']          = FileType::safeUploadedMime($f);
            $data['file_size_bytes']    = $f->getSize();
        }

        $download->update($data);
        AuditTrail::log('api_download_update', null, ['download_id' => $download->id, 'by' => $request->user()->id]);

        return response()->json(['status' => 'success', 'message' => 'Download updated.', 'data' => $this->withFileUrls($download->fresh()->toArray())]);
    }

    /** DELETE /api/v1/pr/downloads/{id} */
    public function destroyDownload(Request $request, WebsiteDownload $download)
    {
        $id = $download->id;
        $this->deleteFileIfExists($download->file_path);
        $download->delete();
        AuditTrail::log('api_download_delete', null, ['download_id' => $id, 'by' => $request->user()->id]);
        return response()->json(['status' => 'success', 'message' => 'Download deleted.']);
    }

    // ─── Shared private helpers ────────────────────────────────────────────────

    private function handleFileUploads(Request $request, array &$data, string $folder): void
    {
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $data['image_path'] = $this->storePublicFile($img, $folder . '/images', FileType::safeHashName($img));
        }
        if ($request->hasFile('attachment')) {
            $f = $request->file('attachment');
            $data['attachment_path']          = $this->storePublicFile($f, $folder . '/attachments', FileType::safeHashName($f));
            $data['attachment_original_name'] = $f->getClientOriginalName();
            $data['attachment_mime']          = FileType::safeUploadedMime($f);
        }
    }

    private function normalizeNewsCategory(?string $category): ?string
    {
        return strcasecmp((string) $category, 'Statement') === 0 ? 'Press Release' : $category;
    }

    private function storeNewsGallery(Request $request, ?News $news, ?string $newPrimaryPath = null): array
    {
        $existing = $news ? array_values(array_unique(array_filter(array_merge((array) $news->image_paths, [$news->image_path])))) : [];
        $removed = array_values(array_intersect($existing, array_map('strval', $request->input('remove_image_paths', []))));
        $paths = array_values(array_diff($existing, $removed));

        if ($newPrimaryPath) {
            if ($news && $news->image_path && in_array($news->image_path, $paths, true)) {
                $paths = array_values(array_diff($paths, [$news->image_path]));
            }
            array_unshift($paths, $newPrimaryPath);
        }

        $incomingImages = (array) $request->file('images', []);
        if (count($paths) + count($incomingImages) > 10) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'images' => 'An article can have at most 10 images. Remove an existing image before adding more.',
            ]);
        }

        foreach ($incomingImages as $image) {
            if ($image) {
                $paths[] = $this->storePublicFile($image, 'content/news/images', FileType::safeHashName($image));
            }
        }

        $paths = array_values(array_unique($paths));
        foreach ($removed as $path) {
            $this->deleteFileIfExists($path);
        }

        return ['image_paths' => $paths, 'image_path' => $paths[0] ?? null];
    }

    private function cleanupOldFiles($model, array $incoming): void
    {
        if (isset($incoming['image_path']) && $model->image_path && $model->image_path !== $incoming['image_path']) {
            $this->deleteFileIfExists($model->image_path);
        }
        if (isset($incoming['attachment_path']) && $model->attachment_path && $model->attachment_path !== $incoming['attachment_path']) {
            $this->deleteFileIfExists($model->attachment_path);
        }
    }

    private function deleteFileIfExists(?string $path): void
    {
        if ($path) Storage::disk('public')->delete($path);
    }

    private function withFileUrls(array $arr): array
    {
        $base = asset('storage');
        foreach (['image_path', 'attachment_path', 'file_path'] as $field) {
            if (!empty($arr[$field])) {
                $key = str_replace('_path', '_url', $field);
                $arr[$key] = $base . '/' . $arr[$field];
            }
        }
        return $arr;
    }

    private function formatNews(News $n): array
    {
        $arr = $n->toArray();
        $arr = $this->withFileUrls($arr);
        $paths = array_values(array_unique(array_filter(array_merge((array) ($arr['image_paths'] ?? []), [$arr['image_path'] ?? null]))));
        $base = asset('storage');
        $arr['image_paths'] = $paths;
        $arr['image_urls'] = array_map(fn ($path) => $base . '/' . $path, $paths);
        $arr['image_url'] = $arr['image_urls'][0] ?? null;
        if (strcasecmp((string) ($arr['category'] ?? ''), 'Statement') === 0) {
            $arr['category'] = 'Press Release';
        }
        return $arr;
    }

    private function paginationMeta($paginator): array
    {
        return [
            'total'        => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }

    private function dispatchSubscriberNotification(string $type, $model): void
    {
        try {
            $service = app(SubscriberNotificationService::class);
            match ($type) {
                'news'  => $service->queueNewsNotification($model),
                'event' => $service->queueEventNotification($model),
                default => null,
            };
        } catch (\Throwable $e) {
            \Log::warning("Subscriber notification failed for {$type} #{$model->id}: " . $e->getMessage());
        }
    }
}
