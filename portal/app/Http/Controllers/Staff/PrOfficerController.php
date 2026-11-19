<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Event;
use App\Models\News;
use App\Models\Tender;
use App\Models\Vacancy;
use App\Models\WebsiteDownload;
use App\Models\WebsiteMediaItem;
use App\Models\Subscriber;
use App\Models\AuditLog;
use App\Services\SubscriberNotificationService;
use App\Support\FileType;
use App\Support\NewsBodySanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class PrOfficerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'staff.portal', 'role:pr_officer|super_admin']);
    }

    public function contactMessages() { $messages = \App\Models\Complaint::where('type', 'enquiry')->latest()->paginate(20); return view('staff.pr.contact_messages', compact('messages')); }

    public function gallery() { $media = WebsiteMediaItem::latest()->paginate(24); return view('staff.pr.gallery', compact('media')); }

    public function storeGalleryItem(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:image,video'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:100'],
            'album' => ['nullable', 'string', 'max:100'],
            'file' => ['nullable', 'file', 'max:102400'],
            'files' => ['nullable', 'array', 'max:50'],
            'files.*' => ['file', 'max:102400'],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $uploads = [];
        if ($request->hasFile('files')) {
            foreach ((array) $request->file('files') as $file) {
                if ($file && $file->isValid()) {
                    $uploads[] = $file;
                }
            }
        }
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $uploads[] = $request->file('file');
        }

        if ($data['type'] === 'image' && !$uploads) {
            return back()->withInput()->withErrors(['files' => 'Upload at least one image for the album.']);
        }
        if ($data['type'] === 'video' && !$uploads && empty($data['external_url'])) {
            return back()->withInput()->withErrors(['files' => 'Upload a video or provide a video URL.']);
        }

        $category = trim((string) ($data['album'] ?? $data['category'] ?? '')) ?: null;
        $created = 0;
        foreach ($uploads as $file) {
            WebsiteMediaItem::create([
                'type' => $data['type'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $category,
                'file_path' => $file->store('website/media', 'public'),
                'external_url' => null,
                'is_published' => true,
                'published_at' => now(),
                'created_by' => Auth::id(),
            ]);
            $created++;
        }

        if (!$uploads && $data['type'] === 'video' && !empty($data['external_url'])) {
            WebsiteMediaItem::create([
                'type' => 'video',
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $category,
                'file_path' => null,
                'external_url' => $data['external_url'],
                'is_published' => true,
                'published_at' => now(),
                'created_by' => Auth::id(),
            ]);
            $created = 1;
        }

        return back()->with('success', $created . ' gallery ' . ($created === 1 ? 'item' : 'items') . ' uploaded and published.');
    }

    /**
     * Display the PR Officer dashboard with Notices, Events, and News.
     */
    public function dashboard(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $status = $request->input('status');
        $noticesQuery = Notice::query()->orderByDesc('id');
        $eventsQuery = Event::query()->orderByDesc('starts_at');
        $newsQuery = News::query()->orderByDesc('published_at')->orderByDesc('id');
        $tendersQuery = Tender::query()->orderByDesc('id');
        $vacanciesQuery = Vacancy::query()->orderByDesc('id');
        $downloadsQuery = WebsiteDownload::query()->orderBy('category')->orderByDesc('id');
        if ($q !== '') {
            $noticesQuery->where(fn($x) => $x->where('title', 'like', "%{$q}%")->orWhere('body', 'like', "%{$q}%"));
            $eventsQuery->where(fn($x) => $x->where('title', 'like', "%{$q}%")->orWhere('location', 'like', "%{$q}%"));
            $newsQuery->where(fn($x) => $x->where('title', 'like', "%{$q}%")->orWhere('excerpt', 'like', "%{$q}%"));
            $tendersQuery->where(fn($x) => $x->where('title', 'like', "%{$q}%")->orWhere('reference_number', 'like', "%{$q}%"));
            $vacanciesQuery->where(fn($x) => $x->where('title', 'like', "%{$q}%")->orWhere('department', 'like', "%{$q}%"));
            $downloadsQuery->where(fn($x) => $x->where('title', 'like', "%{$q}%")->orWhere('category', 'like', "%{$q}%"));
        }
        if ($status === 'published') foreach ([$noticesQuery, $eventsQuery, $newsQuery, $tendersQuery, $vacanciesQuery, $downloadsQuery] as $query) $query->where('is_published', true);
        if ($status === 'draft') foreach ([$noticesQuery, $eventsQuery, $newsQuery, $tendersQuery, $vacanciesQuery, $downloadsQuery] as $query) $query->where('is_published', false);
        $notices = $noticesQuery->paginate(15, ['*'], 'notices')->withQueryString();
        $events = $eventsQuery->paginate(15, ['*'], 'events')->withQueryString();
        $news = $newsQuery->paginate(15, ['*'], 'news')->withQueryString();
        $tenders = $tendersQuery->paginate(15, ['*'], 'tenders')->withQueryString();
        $vacancies = $vacanciesQuery->paginate(15, ['*'], 'vacancies')->withQueryString();
        $downloads = $downloadsQuery->get();

        $media = WebsiteMediaItem::orderBy('display_order')->latest('published_at')->orderByDesc('id')->get();
        $historyActions = ['notice_create','notice_update','notice_delete','event_create','event_update','event_delete','news_create','news_update','news_delete','tender_create','tender_update','tender_delete','vacancy_create','vacancy_update','vacancy_delete','download_create','download_update','download_delete','website_media_create','website_media_update','website_media_delete','website_page_person_create','website_page_person_update','website_page_person_delete','subscriber_delete'];
        $history = AuditLog::with('actor')->whereIn('action', $historyActions)->latest('created_at')->limit(5)->get();

        $pageTabs = ['staff.pr.notices'=>'notices','staff.pr.events'=>'events','staff.pr.news'=>'news','staff.pr.tenders'=>'tenders','staff.pr.vacancies'=>'vacancies','staff.pr.documents'=>'downloads'];
        $routeName = $request->route()?->getName();
        $activeTab = $pageTabs[$routeName] ?? null;
        return view('staff.pr.dashboard', compact('notices', 'events', 'news', 'tenders', 'vacancies', 'downloads', 'media', 'activeTab', 'history'));
    }

    /**
     * Store a new notice.
     */
    public function storeNotice(Request $request)
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ];
        $request->validate($rules);

        $data = $request->only([
            'title',
            'notice_category',
            'reference_number',
            'body',
            'summary',
            'target_portal',
            'target_audience',
            'issuing_authority',
            'signed_by',
            'urgency_level',
            'effective_date',
            'expires_at',
            'language',
            'attachment_label',
            'related_legal_reference',
            'contact_name',
            'contact_phone',
            'contact_email',
            'is_published',
            'published_at',
            'stick_to_top',
            'show_on_homepage',
            'send_email_alert',
        ]);

        $data['body'] = NewsBodySanitizer::clean($request->input('body', ''));
        $data['target_portal'] = $request->input('target_portal', 'both');
        $data['is_published'] = (bool)($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['created_by'] = Auth::id();
        $data['stick_to_top'] = (bool)($data['stick_to_top'] ?? false);
        $data['show_on_homepage'] = (bool)($data['show_on_homepage'] ?? false);
        $data['send_email_alert'] = (bool)($data['send_email_alert'] ?? false);

        $this->storeUploadedFiles($request, $data, 'content/notices');

        Notice::create($data);

        \App\Support\AuditTrail::log('notice_create', null, ['title' => $data['title']]);

        return back()->with('success', 'Notice created successfully.');
    }

    public function updateNotice(Request $request, Notice $notice)
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ];
        $request->validate($rules);

        $data = $request->only([
            'title',
            'notice_category',
            'reference_number',
            'body',
            'summary',
            'target_portal',
            'target_audience',
            'issuing_authority',
            'signed_by',
            'urgency_level',
            'effective_date',
            'expires_at',
            'language',
            'attachment_label',
            'related_legal_reference',
            'contact_name',
            'contact_phone',
            'contact_email',
            'is_published',
            'published_at',
            'stick_to_top',
            'show_on_homepage',
            'send_email_alert',
        ]);

        $data['body'] = NewsBodySanitizer::clean($request->input('body', ''));
        $data['target_portal'] = $request->input('target_portal', 'both');
        $data['is_published'] = (bool)($data['is_published'] ?? false);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? ($notice->published_at ?? now()) : null);
        $data['stick_to_top'] = (bool)($data['stick_to_top'] ?? false);
        $data['show_on_homepage'] = (bool)($data['show_on_homepage'] ?? false);
        $data['send_email_alert'] = (bool)($data['send_email_alert'] ?? false);

        $this->storeUploadedFiles($request, $data, 'content/notices');
        $this->cleanupOldFiles($notice, $data);

        $notice->update($data);

        \App\Support\AuditTrail::log('notice_update', null, ['notice_id' => $notice->id]);

        return back()->with('success', 'Notice updated successfully.');
    }

    /**
     * Delete a notice.
     */
    public function destroyNotice(Notice $notice)
    {
        $id = $notice->id;
        if ($notice->image_path) $this->deletePublicFile($notice->image_path);
        if ($notice->attachment_path) $this->deletePublicFile($notice->attachment_path);
        $notice->delete();

        \App\Support\AuditTrail::log('notice_delete', null, ['notice_id' => $id]);
        return back()->with('success', 'Notice deleted successfully.');
    }

    /**
     * Store a new event.
     */
    public function storeEvent(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:200'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'target_portal' => ['required', 'in:journalist,mediahouse,both'],
            'is_published' => ['nullable', 'boolean'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ]);

        $data['description'] = NewsBodySanitizer::clean($data['description'] ?? null);
        $data['is_published'] = (bool)($data['is_published'] ?? true);
        $data['published_at'] = $data['is_published'] ? now() : null;
        $data['created_by'] = Auth::id();
        $data['starts_at'] = $data['starts_at'] ?? now();

        $this->storeUploadedFiles($request, $data, 'content/events');

        $event = Event::create($data);

        \App\Support\AuditTrail::log('event_create', null, ['title' => $data['title']]);

        // Send notification to subscribers if event is published
        if ($data['is_published']) {
            try {
                $notificationService = app(SubscriberNotificationService::class);
                $notificationService->queueEventNotification($event);
                \Log::info('Event notification queued', ['event_id' => $event->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to queue event notification', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Event created successfully.');
    }

    /**
     * Update an event.
     */
    public function updateEvent(Request $request, Event $event)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:200'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'target_portal' => ['required', 'in:journalist,mediahouse,both'],
            'is_published' => ['nullable', 'boolean'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ]);

        $data['description'] = NewsBodySanitizer::clean($data['description'] ?? null);
        $data['is_published'] = (bool)($data['is_published'] ?? false);
        $data['published_at'] = $data['is_published'] ? ($event->published_at ?? now()) : null;
        $data['starts_at'] = $data['starts_at'] ?? $event->starts_at;

        $this->storeUploadedFiles($request, $data, 'content/events');
        $this->cleanupOldFiles($event, $data);

        // Check if status changed to published
        $wasUnpublished = !$event->is_published;
        $nowPublished = $data['is_published'];

        $event->update($data);

        \App\Support\AuditTrail::log('event_update', null, ['event_id' => $event->id]);

        // Send notification to subscribers if event was just published
        if ($wasUnpublished && $nowPublished) {
            try {
                $notificationService = app(SubscriberNotificationService::class);
                $notificationService->queueEventNotification($event);
                \Log::info('Event notification queued (status changed to published)', ['event_id' => $event->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to queue event notification', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Event updated successfully.');
    }

    /**
     * Delete an event.
     */
    public function destroyEvent(Event $event)
    {
        $id = $event->id;
        if ($event->image_path) $this->deletePublicFile($event->image_path);
        if ($event->attachment_path) $this->deletePublicFile($event->attachment_path);
        $event->delete();

        \App\Support\AuditTrail::log('event_delete', null, ['event_id' => $id]);
        return back()->with('success', 'Event deleted successfully.');
    }

    /**
     * Helper: Store uploaded files.
     */
    private function storeUploadedFiles(Request $request, array &$data, string $folder): void
    {
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $data['image_path'] = $this->storePublicUpload($img, $folder . '/images');
        }

        if ($request->hasFile('attachment')) {
            $f = $request->file('attachment');
            $data['attachment_path'] = $this->storePublicUpload($f, $folder . '/attachments');
            $data['attachment_original_name'] = $f->getClientOriginalName();
            $data['attachment_mime'] = FileType::safeUploadedMime($f);
        }
    }

    private function storePublicUpload(\Illuminate\Http\UploadedFile $file, string $folder): string
    {
        return $this->storePublicFile($file, $folder, FileType::safeHashName($file));
    }

    private function publicDiskRoot(string $path = ''): string
    {
        $root = config('filesystems.disks.public.root', storage_path('app/public'));

        return rtrim($root, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . trim($path, '/\\') : '');
    }

    private function deletePublicFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Helper: Cleanup old files.
     */
    private function cleanupOldFiles($model, array $incoming): void
    {
        if (isset($incoming['image_path']) && $model->image_path && $model->image_path !== $incoming['image_path']) {
            $this->deletePublicFile($model->image_path);
        }
        if (isset($incoming['attachment_path']) && $model->attachment_path && $model->attachment_path !== $incoming['attachment_path']) {
            $this->deletePublicFile($model->attachment_path);
        }
    }

    /**
     * Upload an image for insertion inside an article body.
     */
    public function uploadInlineImage(Request $request)
    {
        $request->validate([
            'image' => $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'required'),
        ]);

        $path = $this->storePublicUpload($request->file('image'), 'content/news/inline');

        return response()->json([
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    }

    /**
     * Store a new news item.
     */
    public function storeNews(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'category' => ['required', 'in:Press Release,Media Advisory,Commission Update,Legal Notice,Speech,Report'],
            'tags' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:160'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
            'featured' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:50'],
            'source_reference' => ['nullable', 'string', 'max:200'],
            'image_alt_text' => ['nullable', 'string', 'max:200'],
            'allow_comments' => ['nullable', 'boolean'],
            'notify_subscribers' => ['nullable', 'boolean'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'slug' => ['nullable', 'string', 'max:200'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'image' => $this->imageUploadRules(5120),
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => array_merge(['image'], $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'file')),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ]);

        $data['category'] = $this->normalizeNewsCategory($data['category']);
        $data['body'] = NewsBodySanitizer::clean($data['body']);
        $data['slug'] = $data['slug'] ?? News::makeSlug($data['title']);
        $data['is_published'] = (bool)($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['created_by'] = Auth::id();

        $this->validateNewsImageCapacity($request, null, false);
        $this->storeUploadedFiles($request, $data, 'content/news');
        $data = array_merge($data, $this->saveNewsImages($request, null, $data['image_path'] ?? null));

        News::create($data);

        \App\Support\AuditTrail::log('news_create', null, ['title' => $data['title']]);

        return back()->with('success', 'News posted successfully.');
    }

    public function updateNews(Request $request, News $news)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'category' => ['required', 'in:Press Release,Media Advisory,Commission Update,Legal Notice,Speech,Report'],
            'tags' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:160'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
            'featured' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'max:50'],
            'source_reference' => ['nullable', 'string', 'max:200'],
            'image_alt_text' => ['nullable', 'string', 'max:200'],
            'allow_comments' => ['nullable', 'boolean'],
            'notify_subscribers' => ['nullable', 'boolean'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'slug' => ['nullable', 'string', 'max:200'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'image' => $this->imageUploadRules(5120),
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => array_merge(['image'], $this->imageUploadRules(5120, ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'file')),
            'remove_image_paths' => ['nullable', 'array'],
            'remove_image_paths.*' => ['string', 'max:500'],
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ]);

        $data['category'] = $this->normalizeNewsCategory($data['category']);
        $data['body'] = NewsBodySanitizer::clean($data['body']);
        if (($news->title ?? null) !== $data['title']) {
            $data['slug'] = $data['slug'] ?? News::makeSlug($data['title']);
        }

        $data['is_published'] = (bool)($data['is_published'] ?? false);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? ($news->published_at ?? now()) : null);

        $this->validateNewsImageCapacity($request, $news, $request->hasFile('image'));
        $this->storeUploadedFiles($request, $data, 'content/news');
        $imageData = $this->saveNewsImages($request, $news, $data['image_path'] ?? null);
        $this->cleanupOldFiles($news, $data);
        $data = array_merge($data, $imageData);

        $news->update($data);

        \App\Support\AuditTrail::log('news_update', null, ['news_id' => $news->id]);

        return back()->with('success', 'News updated successfully.');
    }

    /**
     * Delete a news item.
     */
    public function destroyNews(News $news)
    {
        $id = $news->id;
        foreach (array_unique(array_filter(array_merge((array) $news->image_paths, [$news->image_path]))) as $imagePath) {
            $this->deletePublicFile($imagePath);
        }
        if ($news->attachment_path) $this->deletePublicFile($news->attachment_path);
        $news->delete();

        \App\Support\AuditTrail::log('news_delete', null, ['news_id' => $id]);
        return back()->with('success', 'News deleted successfully.');
    }

    private function normalizeNewsCategory(?string $category): string
    {
        return strcasecmp((string) $category, 'Statement') === 0 ? 'Press Release' : (string) $category;
    }

    private function saveNewsImages(Request $request, ?News $news, ?string $newPrimaryPath = null): array
    {
        $existing = $news ? array_values(array_unique(array_filter(array_merge((array) $news->image_paths, [$news->image_path])))) : [];
        $removed = array_values(array_intersect($existing, array_map('strval', $request->input('remove_image_paths', []))));
        foreach ($removed as $path) {
            $this->deletePublicFile($path);
        }
        $paths = array_values(array_diff($existing, $removed));

        if ($newPrimaryPath) {
            if ($news && $news->image_path && in_array($news->image_path, $paths, true)) {
                $this->deletePublicFile($news->image_path);
                $paths = array_values(array_diff($paths, [$news->image_path]));
            }
            array_unshift($paths, $newPrimaryPath);
        }

        foreach ((array) $request->file('images', []) as $file) {
            if (!$file) continue;
            $paths[] = $this->storePublicUpload($file, 'content/news/images');
        }

        $paths = array_values(array_unique($paths));
        return ['image_paths' => $paths, 'image_path' => $paths[0] ?? null];
    }

    private function validateNewsImageCapacity(Request $request, ?News $news, bool $replacingPrimary): void
    {
        $existing = $news ? array_values(array_unique(array_filter(array_merge((array) $news->image_paths, [$news->image_path])))) : [];
        $removed = array_intersect($existing, array_map('strval', $request->input('remove_image_paths', [])));
        $remainingCount = count(array_diff($existing, $removed));
        if ($replacingPrimary && $news?->image_path && in_array($news->image_path, $existing, true) && !in_array($news->image_path, $removed, true)) {
            $remainingCount--;
        }

        if ($remainingCount + count((array) $request->file('images', [])) > 10) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'images' => 'An article can have at most 10 images. Remove an existing image before adding more.',
            ]);
        }
    }

    public function storeTender(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'tender_reference' => ['required', 'string', 'max:120'],
            'issuing_organisation' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:10000'],
            'closing_at' => ['required', 'date'],
            'bidding_documents_link' => ['required', 'url', 'max:2048'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ]);

        unset($data['image'], $data['attachment']);

        $data = array_merge($data, $request->only([
            'title',
            'tender_reference',
            'issuing_organisation',
            'summary',
            'quantity',
            'procurement_method',
            'tender_category',
            'description',
            'contract_value_estimate',
            'bid_security_required',
            'bid_security_amount',
            'preferred_bidder_categories',
            'site_visit_scheduled',
            'site_visit_date',
            'site_visit_time',
            'site_visit_notes',
            'pre_bid_meeting_scheduled',
            'pre_bid_meeting_date',
            'pre_bid_meeting_time',
            'pre_bid_meeting_venue',
            'pre_bid_meeting_mandatory',
            'submission_method',
            'submission_address',
            'submission_email',
            'closing_at',
            'opening_at',
            'opening_venue',
            'contact_name',
            'contact_title',
            'contact_email',
            'contact_phone',
            'questions_deadline',
            'bidding_documents_link',
            'evaluation_criteria',
            'tender_status',
            'awarded_to',
            'award_amount',
            'contract_number',
            'language',
            'is_published',
            'published_at',
        ]));

        $data['description'] = NewsBodySanitizer::clean($request->input('description', ''));
        $data['preferred_bidder_categories'] = $request->has('preferred_bidder_categories') ? implode(',', $request->input('preferred_bidder_categories', [])) : null;
        $data['is_published'] = (bool)($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['created_by'] = Auth::id();

        if ($request->hasFile('attachment')) {
            $f = $request->file('attachment');
            $data['attachment_path'] = $this->storePublicUpload($f, 'content/tenders');
            $data['attachment_original_name'] = $f->getClientOriginalName();
            $data['attachment_mime'] = FileType::safeUploadedMime($f);
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storePublicUpload($request->file('image'), 'content/tenders/images');
        }

        Tender::create($data);

        \App\Support\AuditTrail::log('tender_create', null, ['title' => $data['title']]);

        return back()->with('success', 'Tender created successfully.');
    }

    public function updateTender(Request $request, Tender $tender)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'tender_reference' => ['required', 'string', 'max:120'],
            'issuing_organisation' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:10000'],
            'closing_at' => ['required', 'date'],
            'bidding_documents_link' => ['required', 'url', 'max:2048'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ]);

        unset($data['image'], $data['attachment']);

        $data = array_merge($data, $request->only([
            'title',
            'tender_reference',
            'issuing_organisation',
            'summary',
            'quantity',
            'procurement_method',
            'tender_category',
            'description',
            'contract_value_estimate',
            'bid_security_required',
            'bid_security_amount',
            'preferred_bidder_categories',
            'site_visit_scheduled',
            'site_visit_date',
            'site_visit_time',
            'site_visit_notes',
            'pre_bid_meeting_scheduled',
            'pre_bid_meeting_date',
            'pre_bid_meeting_time',
            'pre_bid_meeting_venue',
            'pre_bid_meeting_mandatory',
            'submission_method',
            'submission_address',
            'submission_email',
            'closing_at',
            'opening_at',
            'opening_venue',
            'contact_name',
            'contact_title',
            'contact_email',
            'contact_phone',
            'questions_deadline',
            'bidding_documents_link',
            'evaluation_criteria',
            'tender_status',
            'awarded_to',
            'award_amount',
            'contract_number',
            'language',
            'is_published',
            'published_at',
        ]));

        $data['description'] = NewsBodySanitizer::clean($request->input('description', ''));
        $data['preferred_bidder_categories'] = $request->has('preferred_bidder_categories') ? implode(',', $request->input('preferred_bidder_categories', [])) : null;
        $data['is_published'] = (bool)($data['is_published'] ?? false);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? ($tender->published_at ?? now()) : null);

        if ($request->hasFile('attachment')) {
            if ($tender->attachment_path) {
                $this->deletePublicFile($tender->attachment_path);
            }
            $f = $request->file('attachment');
            $data['attachment_path'] = $this->storePublicUpload($f, 'content/tenders');
            $data['attachment_original_name'] = $f->getClientOriginalName();
            $data['attachment_mime'] = FileType::safeUploadedMime($f);
        }

        if ($request->hasFile('image')) {
            if ($tender->image_path) {
                $this->deletePublicFile($tender->image_path);
            }
            $data['image_path'] = $this->storePublicUpload($request->file('image'), 'content/tenders/images');
        }

        $tender->update($data);

        \App\Support\AuditTrail::log('tender_update', null, ['tender_id' => $tender->id]);

        return back()->with('success', 'Tender updated successfully.');
    }

    public function destroyTender(Tender $tender)
    {
        $id = $tender->id;
        if ($tender->attachment_path) $this->deletePublicFile($tender->attachment_path);
        if ($tender->image_path) $this->deletePublicFile($tender->image_path);
        $tender->delete();

        \App\Support\AuditTrail::log('tender_delete', null, ['tender_id' => $id]);
        return back()->with('success', 'Tender deleted successfully.');
    }

    public function storeVacancy(Request $request)
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'submission_email' => ['required', 'email', 'max:255'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ];
        $request->validate($rules);

        $data = $request->only([
            'title',
            'vacancy_reference',
            'department',
            'job_grade',
            'employment_type',
            'contract_duration',
            'body',
            'qualifications_required',
            'experience_required',
            'skills_competencies',
            'duty_station',
            'submission_email',
            'closing_at',
            'contact_name',
            'contact_title',
            'contact_email',
            'contact_phone',
            'preferred_applicant_categories',
            'number_of_positions',
            'language_requirements',
            'target_portal',
            'is_published',
            'published_at',
        ]);

        $data['body'] = NewsBodySanitizer::clean($request->input('body', ''));
        $data['qualifications_required'] = NewsBodySanitizer::clean($request->input('qualifications_required'));
        $data['experience_required'] = NewsBodySanitizer::clean($request->input('experience_required'));
        $data['skills_competencies'] = NewsBodySanitizer::clean($request->input('skills_competencies'));
        $data['is_published'] = (bool)($data['is_published'] ?? true);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? now() : null);
        $data['created_by'] = Auth::id();
        $data['preferred_applicant_categories'] = $request->input('preferred_applicant_categories', []);
        $data['vacancy_status'] = $this->vacancyStatusFor($data['closing_at'] ?? null);

        $this->storeUploadedFiles($request, $data, 'content/vacancies');

        $vacancy = Vacancy::create($data);

        \App\Support\AuditTrail::log('vacancy_create', null, ['title' => $data['title']]);

        // Send notification to subscribers if vacancy is published
        if ($data['is_published']) {
            try {
                $notificationService = app(SubscriberNotificationService::class);
                $notificationService->queueVacancyNotification($vacancy);
                \Log::info('Vacancy notification queued', ['vacancy_id' => $vacancy->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to queue vacancy notification', [
                    'vacancy_id' => $vacancy->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Vacancy created successfully.');
    }

    public function updateVacancy(Request $request, Vacancy $vacancy)
    {
        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'submission_email' => ['required', 'email', 'max:255'],
            'image' => $this->imageUploadRules(5120),
            'attachment' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,webp'],
        ];
        $request->validate($rules);

        $data = $request->only([
            'title',
            'vacancy_reference',
            'department',
            'job_grade',
            'employment_type',
            'contract_duration',
            'body',
            'qualifications_required',
            'experience_required',
            'skills_competencies',
            'duty_station',
            'submission_email',
            'closing_at',
            'contact_name',
            'contact_title',
            'contact_email',
            'contact_phone',
            'preferred_applicant_categories',
            'number_of_positions',
            'language_requirements',
            'target_portal',
            'is_published',
            'published_at',
        ]);

        $data['body'] = NewsBodySanitizer::clean($request->input('body', ''));
        $data['qualifications_required'] = NewsBodySanitizer::clean($request->input('qualifications_required'));
        $data['experience_required'] = NewsBodySanitizer::clean($request->input('experience_required'));
        $data['skills_competencies'] = NewsBodySanitizer::clean($request->input('skills_competencies'));
        $data['is_published'] = (bool)($data['is_published'] ?? false);
        $data['published_at'] = $data['published_at'] ?? ($data['is_published'] ? ($vacancy->published_at ?? now()) : null);
        $data['preferred_applicant_categories'] = $request->has('preferred_applicant_categories')
            ? json_encode($request->input('preferred_applicant_categories', [])) : null;
        $data['vacancy_status'] = $this->vacancyStatusFor($data['closing_at'] ?? null);

        $this->storeUploadedFiles($request, $data, 'content/vacancies');
        $this->cleanupOldFiles($vacancy, $data);

        // Check if status changed to published
        $wasUnpublished = !$vacancy->is_published;
        $nowPublished = $data['is_published'];

        $vacancy->update($data);

        \App\Support\AuditTrail::log('vacancy_update', null, ['vacancy_id' => $vacancy->id]);

        // Send notification to subscribers if vacancy was just published
        if ($wasUnpublished && $nowPublished) {
            try {
                $notificationService = app(SubscriberNotificationService::class);
                $notificationService->queueVacancyNotification($vacancy);
                \Log::info('Vacancy notification queued (status changed to published)', ['vacancy_id' => $vacancy->id]);
            } catch (\Exception $e) {
                \Log::error('Failed to queue vacancy notification', [
                    'vacancy_id' => $vacancy->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Vacancy updated successfully.');
    }

    private function vacancyStatusFor(?string $closingAt): string
    {
        if (!$closingAt) return 'Open';
        $closingDate = \Illuminate\Support\Carbon::parse($closingAt)->startOfDay();
        if ($closingDate->lt(now()->startOfDay())) return 'Closed';
        if ($closingDate->lte(now()->addDays(7)->startOfDay())) return 'Closing Soon';
        return 'Open';
    }

    public function destroyVacancy(Vacancy $vacancy)
    {
        $id = $vacancy->id;
        if ($vacancy->image_path) $this->deletePublicFile($vacancy->image_path);
        if ($vacancy->attachment_path) $this->deletePublicFile($vacancy->attachment_path);
        $vacancy->delete();

        \App\Support\AuditTrail::log('vacancy_delete', null, ['vacancy_id' => $id]);
        return back()->with('success', 'Vacancy deleted successfully.');
    }

    // ─── Documents & Reports ─────────────────────────────────────────────────

    public function storeDownload(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['required', 'in:registration_accreditation_forms,foia_forms,organizational_documents,policies_guidelines,legislation,statutory_instruments'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'version'     => ['nullable', 'string', 'max:50'],
            'language'    => ['nullable', 'string', 'max:50'],
            'effective_date' => ['nullable', 'date'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'featured'    => ['nullable', 'boolean'],
            'is_published'=> ['nullable', 'boolean'],
            'file'        => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt'],
        ]);

        $data['description'] = NewsBodySanitizer::clean($data['description'] ?? null);
        $data['is_published'] = (bool)($data['is_published'] ?? true);
        $data['published_at'] = $data['is_published'] ? now() : null;
        $data['created_by']   = Auth::id();
        $data['display_order'] = (int)($data['display_order'] ?? 0);
        $data['featured'] = (bool)($data['featured'] ?? false);

        if ($request->hasFile('file')) {
            $f = $request->file('file');
            $data['file_path']          = $this->storePublicUpload($f, 'content/downloads');
            $data['file_original_name'] = $f->getClientOriginalName();
            $data['file_mime']          = FileType::safeUploadedMime($f);
            $data['file_size_bytes']    = $f->getSize();
        }

        WebsiteDownload::create($data);

        \App\Support\AuditTrail::log('download_create', null, ['title' => $data['title']]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function updateDownload(Request $request, WebsiteDownload $download)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['required', 'in:registration_accreditation_forms,foia_forms,organizational_documents,policies_guidelines,legislation,statutory_instruments'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'version'     => ['nullable', 'string', 'max:50'],
            'language'    => ['nullable', 'string', 'max:50'],
            'effective_date' => ['nullable', 'date'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'featured'    => ['nullable', 'boolean'],
            'is_published'=> ['nullable', 'boolean'],
            'file'        => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt'],
        ]);

        $data['description'] = NewsBodySanitizer::clean($data['description'] ?? null);
        $data['is_published'] = (bool)($data['is_published'] ?? false);
        $data['published_at'] = $data['is_published'] ? ($download->published_at ?? now()) : null;
        $data['display_order'] = (int)($data['display_order'] ?? 0);
        $data['featured'] = (bool)($data['featured'] ?? false);

        if ($request->hasFile('file')) {
            if ($download->file_path) {
                $this->deletePublicFile($download->file_path);
            }
            $f = $request->file('file');
            $data['file_path']          = $this->storePublicUpload($f, 'content/downloads');
            $data['file_original_name'] = $f->getClientOriginalName();
            $data['file_mime']          = FileType::safeUploadedMime($f);
            $data['file_size_bytes']    = $f->getSize();
        }

        $download->update($data);

        \App\Support\AuditTrail::log('download_update', null, ['download_id' => $download->id]);

        return back()->with('success', 'Document updated successfully.');
    }

    public function destroyDownload(WebsiteDownload $download)
    {
        $id = $download->id;
        if ($download->file_path) $this->deletePublicFile($download->file_path);
        $download->delete();

        \App\Support\AuditTrail::log('download_delete', null, ['download_id' => $id]);
        return back()->with('success', 'Document deleted successfully.');
    }

    // ─── Subscribers Management ───────────────────────────────────────────────

    /**
     * Display subscribers list
     */
    public function subscribers(Request $request)
    {
        $query = Subscriber::query()->orderByDesc('created_at');

        // Search functionality
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->paginate(50);
        $totalCount = Subscriber::count();

        return view('staff.pr.subscribers', compact('subscribers', 'totalCount'));
    }

    /**
     * Export subscribers as CSV
     */
    public function exportSubscribersCSV()
    {
        $subscribers = Subscriber::orderBy('created_at', 'desc')->get();

        $filename = 'subscribers_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($subscribers) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, ['Full Name', 'Email', 'Subscribed Date']);

            // Data rows
            foreach ($subscribers as $subscriber) {
                fputcsv($file, [
                    $subscriber->full_name,
                    $subscriber->email,
                    $subscriber->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        \App\Support\AuditTrail::log('subscribers_export_csv', null, ['count' => $subscribers->count()]);

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export subscribers as Excel (XLSX)
     */
    public function exportSubscribersExcel()
    {
        $subscribers = Subscriber::orderBy('created_at', 'desc')->get();

        $filename = 'subscribers_' . date('Y-m-d_His') . '.xlsx';

        // Simple XML-based XLSX generation
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($subscribers) {
            $tempDir = sys_get_temp_dir() . '/xlsx_' . uniqid();
            mkdir($tempDir);
            mkdir($tempDir . '/_rels');
            mkdir($tempDir . '/xl');
            mkdir($tempDir . '/xl/_rels');
            mkdir($tempDir . '/xl/worksheets');

            // [Content_Types].xml
            file_put_contents($tempDir . '/[Content_Types].xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');

            // _rels/.rels
            file_put_contents($tempDir . '/_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

            // xl/_rels/workbook.xml.rels
            file_put_contents($tempDir . '/xl/_rels/workbook.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');

            // xl/workbook.xml
            file_put_contents($tempDir . '/xl/workbook.xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="Subscribers" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>');

            // xl/worksheets/sheet1.xml
            $sheetData = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>
<row r="1">
<c t="inlineStr" r="A1"><is><t>Full Name</t></is></c>
<c t="inlineStr" r="B1"><is><t>Email</t></is></c>
<c t="inlineStr" r="C1"><is><t>Subscribed Date</t></is></c>
</row>';

            $rowNum = 2;
            foreach ($subscribers as $subscriber) {
                $name = htmlspecialchars($subscriber->full_name, ENT_XML1, 'UTF-8');
                $email = htmlspecialchars($subscriber->email, ENT_XML1, 'UTF-8');
                $date = htmlspecialchars($subscriber->created_at->format('Y-m-d H:i:s'), ENT_XML1, 'UTF-8');

                $sheetData .= "<row r=\"{$rowNum}\">";
                $sheetData .= "<c t=\"inlineStr\" r=\"A{$rowNum}\"><is><t>{$name}</t></is></c>";
                $sheetData .= "<c t=\"inlineStr\" r=\"B{$rowNum}\"><is><t>{$email}</t></is></c>";
                $sheetData .= "<c t=\"inlineStr\" r=\"C{$rowNum}\"><is><t>{$date}</t></is></c>";
                $sheetData .= '</row>';
                $rowNum++;
            }

            $sheetData .= '</sheetData></worksheet>';
            file_put_contents($tempDir . '/xl/worksheets/sheet1.xml', $sheetData);

            // Create ZIP
            $zip = new \ZipArchive();
            $zipPath = sys_get_temp_dir() . '/subscribers_' . uniqid() . '.xlsx';
            $zip->open($zipPath, \ZipArchive::CREATE);

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($tempDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            // Output and cleanup
            readfile($zipPath);

            // Cleanup
            array_map('unlink', glob("{$tempDir}/_rels/*"));
            array_map('unlink', glob("{$tempDir}/xl/_rels/*"));
            array_map('unlink', glob("{$tempDir}/xl/worksheets/*"));
            rmdir("{$tempDir}/_rels");
            rmdir("{$tempDir}/xl/_rels");
            rmdir("{$tempDir}/xl/worksheets");
            rmdir("{$tempDir}/xl");
            unlink("{$tempDir}/[Content_Types].xml");
            rmdir($tempDir);
            unlink($zipPath);
        };

        \App\Support\AuditTrail::log('subscribers_export_excel', null, ['count' => $subscribers->count()]);

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export subscribers as PDF
     */
    public function exportSubscribersPDF()
    {
        $subscribers = Subscriber::orderBy('created_at', 'desc')->get();

        \App\Support\AuditTrail::log('subscribers_export_pdf', null, ['count' => $subscribers->count()]);

        return view('staff.pr.subscribers_pdf', compact('subscribers'));
    }

    /**
     * Delete a subscriber
     */
    public function destroySubscriber(Subscriber $subscriber)
    {
        $email = $subscriber->email;
        $subscriber->delete();

        \App\Support\AuditTrail::log('subscriber_delete', null, ['email' => $email]);
        return back()->with('success', 'Subscriber removed successfully.');
    }

    // ─── Gallery / Media Library Management ───────────────────────────────────

    public function storeMedia(Request $request)
    {
        $data = $request->validate([
            'type'          => ['required', 'in:image,video'],
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['nullable', 'string', 'max:10000'],
            'category'      => ['nullable', 'string', 'max:100'],
            'external_url'  => ['nullable', 'url', 'max:2048'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_published'  => ['nullable', 'boolean'],
            'file'          => ['nullable', 'file', 'max:102400'],
            'image_file'    => ['nullable', 'file', 'max:102400'],
            'video_file'    => ['nullable', 'file', 'max:102400'],
            'thumbnail'     => ['nullable', 'image', 'max:10240'],
        ]);

        $upload = $this->resolveMediaFile($request, $data['type']);

        if ($data['type'] === 'image' && !$upload) {
            return back()->withInput()->with('error', 'An image file is required.');
        }
        if ($data['type'] === 'video' && !$upload && empty($data['external_url'])) {
            return back()->withInput()->with('error', 'Provide a video file or a hosted video URL.');
        }

        $payload = [
            'type'          => $data['type'],
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'category'      => $data['category'] ?? null,
            'external_url'  => $data['external_url'] ?? null,
            'display_order' => (int) ($data['display_order'] ?? 0),
            'is_published'  => (bool) ($data['is_published'] ?? true),
            'created_by'    => Auth::id(),
        ];
        $payload['published_at'] = $payload['is_published'] ? now() : null;

        if ($upload) {
            $payload['file_path'] = $this->storePublicUpload($upload, 'website-media');
        }
        if ($request->hasFile('thumbnail')) {
            $payload['thumbnail_path'] = $this->storePublicUpload($request->file('thumbnail'), 'website-media/thumbnails');
        }

        $media = WebsiteMediaItem::create($payload);

        \App\Support\AuditTrail::log('website_media_create', $media, ['type' => $media->type, 'title' => $media->title]);

        return back()->with('success', 'Gallery item added successfully.');
    }

    /**
     * Resolve the uploaded media file from any of the accepted input names.
     */
    private function resolveMediaFile(Request $request, string $type): ?\Illuminate\Http\UploadedFile
    {
        foreach (['file', 'image_file', 'video_file'] as $name) {
            if ($request->hasFile($name)) {
                $file = $request->file($name);
                if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                    return $file;
                }
            }
        }
        return null;
    }

    public function updateMedia(Request $request, WebsiteMediaItem $media)
    {
        $data = $request->validate([
            'type'          => ['required', 'in:image,video'],
            'title'         => ['required', 'string', 'max:200'],
            'description'   => ['nullable', 'string', 'max:10000'],
            'category'      => ['nullable', 'string', 'max:100'],
            'external_url'  => ['nullable', 'url', 'max:2048'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_published'  => ['nullable', 'boolean'],
            'file'          => ['nullable', 'file', 'max:102400'],
            'image_file'    => ['nullable', 'file', 'max:102400'],
            'video_file'    => ['nullable', 'file', 'max:102400'],
            'thumbnail'     => ['nullable', 'image', 'max:10240'],
        ]);

        $upload = $this->resolveMediaFile($request, $data['type']);

        if ($data['type'] !== $media->type) {
            $media->file_path = null;
        }

        if ($data['type'] === 'image' && !$upload && !$media->file_path) {
            return back()->withInput()->with('error', 'An image file is required.');
        }
        if ($data['type'] === 'video' && !$upload && empty($data['external_url']) && !$media->file_path) {
            return back()->withInput()->with('error', 'Provide a video file or a hosted video URL.');
        }

        $oldPaths = [$media->file_path, $media->thumbnail_path];

        $media->type = $data['type'];
        $media->title = $data['title'];
        $media->description = $data['description'] ?? null;
        $media->category = $data['category'] ?? null;
        $media->external_url = $data['external_url'] ?? null;
        $media->display_order = (int) ($data['display_order'] ?? 0);
        $media->is_published = (bool) ($data['is_published'] ?? false);
        $media->published_at = $media->is_published ? ($media->published_at ?? now()) : null;

        if ($upload) {
            $media->file_path = $this->storePublicUpload($upload, 'website-media');
        }
        if ($request->hasFile('thumbnail')) {
            $media->thumbnail_path = $this->storePublicUpload($request->file('thumbnail'), 'website-media/thumbnails');
        }

        $media->save();

        foreach ($oldPaths as $path) {
            if ($path && !in_array($path, [$media->file_path, $media->thumbnail_path], true)) {
                $this->deletePublicFile($path);
            }
        }

        \App\Support\AuditTrail::log('website_media_update', $media, ['type' => $media->type, 'title' => $media->title]);

        return back()->with('success', 'Gallery item updated successfully.');
    }

    public function destroyMedia(WebsiteMediaItem $media)
    {
        $id = $media->id;
        foreach ([$media->file_path, $media->thumbnail_path] as $path) {
            if ($path) $this->deletePublicFile($path);
        }
        $media->delete();

        \App\Support\AuditTrail::log('website_media_delete', null, ['media_id' => $id]);
        return back()->with('success', 'Gallery item deleted successfully.');
    }
}
