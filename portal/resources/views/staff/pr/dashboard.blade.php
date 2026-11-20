@extends('layouts.portal')
@section('title','Public Relations Dashboard')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">
    @php
        $activeTab = $activeTab ?? request('tab');
        $pageLabels = ['notices' => 'Notices', 'events' => 'Events', 'news' => 'News', 'tenders' => 'Tenders', 'vacancies' => 'Vacancies', 'downloads' => 'Reports & Documents'];
        $pageTitle = $activeTab ? ($pageLabels[$activeTab] ?? ucfirst($activeTab)) : 'Public Relations Dashboard';
    @endphp

    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-checkbox-circle-fill fs-5"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-error-warning-fill fs-5"></i> {{ session('error') }}
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border border-slate-100">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">@if($activeTab)<a href="{{ route('staff.pr.dashboard') }}" class="btn btn-sm btn-light border rounded-pill"><i class="ri-arrow-left-line"></i> Back to Dashboard</a>@endif</div>
            <h2 class="fw-bold m-0" style="letter-spacing:-0.02em; color:#0f172a;">{{ $pageTitle }}</h2>
            <p class="text-muted m-0 small mt-1">{{ $activeTab ? "Manage " . $pageTitle . " for the ZMC Portal" : "Overview of Public Relations content and activity" }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('staff.pr.website-people.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                <i class="ri-team-line me-1"></i> Commissioners &amp; Secretariat
            </a>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                <i class="ri-user-line me-1"></i>{{ Auth::user()->name }}
            </span>
        </div>
    </div>

    @php
        $totalNotices = $notices->total();
        $totalEvents  = $events->total();
        $totalNews    = $news->total();
        $totalTenders = $tenders->total();
        $totalVacancies = $vacancies->total();
        $staticDocumentCount = 21;
        $publishedDownloadCount = \App\Models\WebsiteDownload::where('is_published', true)->count();
        $totalDownloads = $staticDocumentCount + $publishedDownloadCount;
        $publishedNotices = \App\Models\Notice::where('is_published', true)->count();
        $publishedEvents  = \App\Models\Event::where('is_published', true)->count();
        $publishedNews    = \App\Models\News::where('is_published', true)->count();
        $publishedTenders = \App\Models\Tender::where('is_published', true)->count();
        $publishedVacancies = \App\Models\Vacancy::where('is_published', true)->count();
        $publishedDownloads = $staticDocumentCount + $publishedDownloadCount;
        $upcomingEvents   = \App\Models\Event::where('starts_at', '>=', now())->where('is_published', true)->count();
        $openTenders = \App\Models\Tender::where('is_published', true)->where(function($q){ $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()); })->count();
        $openVacancies = \App\Models\Vacancy::where('is_published', true)->where(function($q){ $q->whereNull('closing_at')->orWhere('closing_at', '>=', now()); })->count();
        $totalSubscribers = \App\Models\Subscriber::count();
        $draftNotices = \App\Models\Notice::where('is_published', false)->count();
        $draftNews = \App\Models\News::where('is_published', false)->count();
        $expiringNotices = \App\Models\Notice::whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(14)])->count();
    @endphp
    @if(!$activeTab)
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#dbeafe;">
                    <i class="ri-megaphone-line" style="font-size:22px;color:#2563eb;"></i>
                </div>
                <div>
                    <div class="acct-kpi-label text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Notices</div>
                    <div class="acct-kpi-value fw-bold" style="font-size:22px;color:#0f172a;">{{ $totalNotices }}</div>
                    <div class="small text-muted">{{ $publishedNotices }} published</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#dcfce7;">
                    <i class="ri-calendar-event-line" style="font-size:22px;color:#16a34a;"></i>
                </div>
                <div>
                    <div class="acct-kpi-label text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Events</div>
                    <div class="acct-kpi-value fw-bold" style="font-size:22px;color:#0f172a;">{{ $totalEvents }}</div>
                    <div class="small text-muted">{{ $upcomingEvents }} upcoming</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#fef3c7;">
                    <i class="ri-newspaper-line" style="font-size:22px;color:#d97706;"></i>
                </div>
                <div>
                    <div class="acct-kpi-label text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">News</div>
                    <div class="acct-kpi-value fw-bold" style="font-size:22px;color:#0f172a;">{{ $totalNews }}</div>
                    <div class="small text-muted">{{ $publishedNews }} published</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#ede9fe;">
                    <i class="ri-bar-chart-grouped-line" style="font-size:22px;color:#7c3aed;"></i>
                </div>
                <div>
                    <div class="acct-kpi-label text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Total Content</div>
                    <div class="acct-kpi-value fw-bold" style="font-size:22px;color:#0f172a;">{{ $totalNotices + $totalEvents + $totalNews + $totalTenders + $totalVacancies }}</div>
                    <div class="small text-muted">across all sections</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="bg-white rounded-4 shadow-sm border p-3 h-100 d-flex align-items-center gap-3" style="cursor:pointer;" onclick="window.location.href='{{ route('staff.pr.subscribers') }}'">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#fce7f3;">
                    <i class="ri-user-heart-line" style="font-size:22px;color:#db2777;"></i>
                </div>
                <div>
                    <div class="acct-kpi-label text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Subscribers</div>
                    <div class="acct-kpi-value fw-bold" style="font-size:22px;color:#0f172a;">{{ $totalSubscribers }}</div>
                    <div class="small text-muted">mailing list</div>
                </div>
            </div>
        </div>
    </div>

    @endif

    <nav class="zmc-tabs" role="tablist" aria-label="Public Relations sections">
        @foreach([
            [null, 'Overview', 'staff.pr.dashboard', 'ri-dashboard-3-line'],
            ['notices', 'Notices', 'staff.pr.notices', 'ri-megaphone-line'],
            ['events', 'Events', 'staff.pr.events', 'ri-calendar-event-line'],
            ['news', 'News', 'staff.pr.news', 'ri-newspaper-line'],
            ['tenders', 'Tenders', 'staff.pr.tenders', 'ri-file-text-line'],
            ['vacancies', 'Vacancies', 'staff.pr.vacancies', 'ri-briefcase-line'],
            ['downloads', 'Reports & Documents', 'staff.pr.documents', 'ri-folder-2-line'],
        ] as [$tabKey, $tabLabel, $tabRoute, $tabIcon])
            <a href="{{ route($tabRoute) }}" role="tab" aria-selected="{{ ($activeTab ?: null) === $tabKey ? 'true' : 'false' }}"><i class="{{ $tabIcon }}"></i> {{ $tabLabel }}</a>
        @endforeach
    </nav>

    @if($activeTab)
    @php
        $workspace = [
            'notices' => ['Notices workspace', 'Manage public notices, alerts, categories, publication and expiry dates.', $totalNotices, $publishedNotices, 'Published', 'ri-megaphone-line'],
            'events' => ['Events workspace', 'Plan and publish upcoming ZMC events, venues and event information.', $totalEvents, $upcomingEvents, 'Upcoming', 'ri-calendar-event-line'],
            'news' => ['News workspace', 'Prepare news stories and press statements for publication.', $totalNews, $publishedNews, 'Published', 'ri-newspaper-line'],
            'tenders' => ['Tenders workspace', 'Manage procurement notices, closing dates and tender documents.', $totalTenders, $publishedTenders, 'Published', 'ri-file-text-line'],
            'vacancies' => ['Vacancies workspace', 'Manage recruitment notices, closing dates and open positions.', $totalVacancies, $openVacancies, 'Open', 'ri-briefcase-line'],
            'downloads' => ['Reports & Documents workspace', 'Organise public reports, documents, categories and downloads.', $totalDownloads, $publishedDownloads, 'Published', 'ri-folder-2-line'],
        ][$activeTab] ?? null;
    @endphp
    @if($workspace)
    <form method="GET" action="{{ url()->current() }}" class="bg-white rounded-4 shadow-sm border p-3 mb-3"><div class="row g-2 align-items-center"><div class="col-md-7"><div class="input-group"><span class="input-group-text bg-light border-end-0"><i class="ri-search-line"></i></span><input name="q" value="{{ request('q') }}" class="form-control border-start-0" placeholder="Search {{ strtolower($pageTitle) }}..."></div></div><div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option><option value="published" @selected(request('status') === 'published')>Published</option><option value="draft" @selected(request('status') === 'draft')>Drafts</option></select></div><div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="ri-search-line me-1"></i>Search</button>@if(request('q') || request('status'))<a href="{{ url()->current() }}" class="btn btn-light border" title="Clear filters"><i class="ri-close-line"></i></a>@endif</div></div></form>
    <div class="row g-3 mb-4">
        <div class="col-lg-8"><div class="bg-white rounded-4 shadow-sm border p-4 h-100"><div class="d-flex align-items-start gap-3"><div class="rounded-3 bg-primary-subtle p-3"><i class="{{ $workspace[5] }} fs-3 text-primary"></i></div><div><h2 class="h5 fw-bold mb-1">{{ $workspace[0] }}</h2><p class="text-muted small mb-0">{{ $workspace[1] }}</p></div></div></div></div>
        <div class="col-6 col-lg-2"><div class="bg-white rounded-4 shadow-sm border p-3 h-100"><div class="small text-muted text-uppercase">Total</div><div class="fs-3 fw-bold">{{ $workspace[2] }}</div><div class="small text-muted">records</div></div></div>
        <div class="col-6 col-lg-2"><div class="bg-white rounded-4 shadow-sm border p-3 h-100"><div class="small text-muted text-uppercase">{{ $workspace[4] }}</div><div class="fs-3 fw-bold text-success">{{ $workspace[3] }}</div><div class="small text-muted">in this section</div></div></div>
    </div>
    @endif
    @endif

    @if(!$activeTab)
    <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-header bg-white border-0 p-3"><h3 class="fw-bold mb-0"><i class="ri-history-line me-2 text-primary"></i>Latest 5 PR activities</h3><span class="small text-muted">Uploads, edits and removals</span></div><div class="list-group list-group-flush">@forelse($history as $entry)<div class="list-group-item px-3 py-3 d-flex align-items-center gap-3"><i class="ri-history-line text-primary"></i><div class="flex-grow-1"><div class="fw-semibold small">{{ ucwords(str_replace("_", " ", $entry->action)) }}</div><div class="text-muted small">{{ $entry->meta["title"] ?? ($entry->meta["email"] ?? "Content record") }} · {{ optional($entry->actor)->name ?? "System" }}</div></div><time class="text-muted small">{{ optional($entry->created_at)->diffForHumans() }}</time></div>@empty<div class="p-4 text-muted small">No PR activity has been recorded yet.</div>@endforelse</div></div>
    <div class="row g-3 mb-4">
        <div class="col-lg-8"><div class="bg-white rounded-4 shadow-sm border p-4 h-100"><div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="fw-bold mb-1">Needs attention</h4><p class="small text-muted mb-0">Items that may need action from the PR Officer.</p></div><i class="ri-focus-3-line fs-3 text-warning"></i></div><div class="row g-2"><div class="col-md-4"><a href="{{ route('staff.pr.notices') }}" class="text-decoration-none"><div class="p-3 rounded-3 bg-light"><div class="small text-muted">Notice drafts</div><div class="fs-4 fw-bold text-dark">{{ $draftNotices }}</div></div></a></div><div class="col-md-4"><a href="{{ route('staff.pr.news') }}" class="text-decoration-none"><div class="p-3 rounded-3 bg-light"><div class="small text-muted">News drafts</div><div class="fs-4 fw-bold text-dark">{{ $draftNews }}</div></div></a></div><div class="col-md-4"><a href="{{ route('staff.pr.notices') }}" class="text-decoration-none"><div class="p-3 rounded-3 bg-light"><div class="small text-muted">Expiring in 14 days</div><div class="fs-4 fw-bold text-dark">{{ $expiringNotices }}</div></div></a></div></div></div></div>
        <div class="col-lg-4"><div class="bg-white rounded-4 shadow-sm border p-4 h-100"><h5 class="fw-bold mb-3">Quick actions</h5><div class="d-grid gap-2"><a href="{{ route('staff.pr.notices') }}" class="btn btn-primary text-start"><i class="ri-add-line me-2"></i>Manage Notices</a><a href="{{ route('staff.pr.events') }}" class="btn btn-outline-success text-start"><i class="ri-calendar-event-line me-2"></i>Manage Events</a><a href="{{ route('staff.pr.news') }}" class="btn btn-outline-warning text-start"><i class="ri-newspaper-line me-2"></i>Manage News</a></div></div></div>
    </div>
    @endif

    <div class="tab-content border-0" id="prDashContent">

         <div class="tab-pane fade {{ $activeTab==='notices' ? 'show active' : '' }}" id="panel-notices" role="tabpanel" style="padding-top:0 !important; margin-top:0 !important;">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0"><i class="ri-megaphone-line me-2" style="color:#2563eb;"></i>Notices</h6>
                    <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createNotice">
                        <i class="ri-add-line me-1"></i>New Notice
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Title</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Portal</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Status</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Published</th>
                                <th class="fw-semibold text-uppercase text-end pe-4" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notices as $n)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($n->image_path)
                                        <img src="{{ asset('storage/' . $n->image_path) }}" alt="" class="rounded" style="width:36px;height:36px;object-fit:cover;">
                                        @else
                                        <div class="d-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#dbeafe;">
                                            <i class="ri-megaphone-line" style="color:#2563eb;"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold" style="color:#334155;">{{ Str::limit($n->title, 50) }}</div>
                                            @if($n->attachment_path)
                                            <div class="text-muted" style="font-size:11px;"><i class="ri-attachment-line me-1"></i>{{ $n->attachment_original_name ?? 'Attachment' }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php $portalMap = ['both'=>['Both Portals','bg-primary-subtle text-primary'],'journalist'=>['Practitioners','bg-info-subtle text-info'],'mediahouse'=>['Media Houses','bg-violet-subtle text-violet']]; @endphp
                                    <span class="badge {{ $portalMap[$n->target_portal][1] ?? 'bg-light text-dark' }} border rounded-pill px-2">{{ $portalMap[$n->target_portal][0] ?? ucfirst($n->target_portal) }}</span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 {{ $n->is_published ? 'bg-success-subtle text-success border-success-subtle' : 'bg-secondary-subtle text-secondary' }}">
                                        <i class="ri-{{ $n->is_published ? 'checkbox-circle' : 'draft' }}-line me-1"></i>{{ $n->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size:12px;">{{ $n->published_at ? $n->published_at->format('d M Y H:i') : '—' }}</td>
                                <td class="text-end pe-4">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editNotice{{ $n->id }}" title="Edit"><i class="ri-edit-line"></i></button>
                                        <form method="POST" action="{{ route('staff.pr.notices.destroy',$n) }}" class="d-inline">@csrf @method('DELETE')
                                            <button class="btn btn-sm zmc-icon-btn btn-outline-danger" onclick="return confirm('Delete this notice?')" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">
                                <i class="ri-megaphone-line d-block mx-auto mb-2" style="font-size:32px;color:#cbd5e1;"></i>
                                No notices found. Click <strong>New Notice</strong> to create one.
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($notices->hasPages())
                <div class="p-3 border-top">{{ $notices->appends(['tab'=>'notices'])->links() }}</div>
                @endif
            </div>
        </div>

         <div class="tab-pane fade {{ $activeTab==='events' ? 'show active' : '' }}" id="panel-events" role="tabpanel" style="padding-top:0 !important; margin-top:0 !important;">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0"><i class="ri-calendar-event-line me-2" style="color:#16a34a;"></i>Events</h6>
                    <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createEvent">
                        <i class="ri-add-line me-1"></i>New Event
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Event</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Date & Location</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Portal</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Status</th>
                                <th class="fw-semibold text-uppercase text-end pe-4" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($events as $e)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($e->image_path)
                                        <img src="{{ asset('storage/' . $e->image_path) }}" alt="" class="rounded" style="width:36px;height:36px;object-fit:cover;">
                                        @else
                                        <div class="d-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#dcfce7;">
                                            <i class="ri-calendar-event-line" style="color:#16a34a;"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold" style="color:#334155;">{{ Str::limit($e->title, 50) }}</div>
                                            @if($e->attachment_path)
                                            <div class="text-muted" style="font-size:11px;"><i class="ri-attachment-line me-1"></i>{{ $e->attachment_original_name ?? 'Attachment' }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:12px;color:#334155;">
                                        <i class="ri-time-line me-1 text-muted"></i>{{ optional($e->starts_at)->format('d M Y, H:i') ?? '—' }}
                                    </div>
                                    @if($e->location)
                                    <div class="text-muted" style="font-size:11px;"><i class="ri-map-pin-line me-1"></i>{{ $e->location }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php $portalMap = ['both'=>['Both Portals','bg-primary-subtle text-primary'],'journalist'=>['Practitioners','bg-info-subtle text-info'],'mediahouse'=>['Media Houses','bg-violet-subtle text-violet']]; @endphp
                                    <span class="badge {{ $portalMap[$e->target_portal][1] ?? 'bg-light text-dark' }} border rounded-pill px-2">{{ $portalMap[$e->target_portal][0] ?? ucfirst($e->target_portal) }}</span>
                                </td>
                                <td>
                                    @if($e->is_published && $e->starts_at && $e->starts_at->isFuture())
                                    <span class="badge rounded-pill px-3 bg-info-subtle text-info"><i class="ri-time-line me-1"></i>Upcoming</span>
                                    @elseif($e->is_published)
                                    <span class="badge rounded-pill px-3 bg-success-subtle text-success border-success-subtle"><i class="ri-checkbox-circle-line me-1"></i>Published</span>
                                    @else
                                    <span class="badge rounded-pill px-3 bg-secondary-subtle text-secondary"><i class="ri-draft-line me-1"></i>Draft</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEvent{{ $e->id }}" title="Edit"><i class="ri-edit-line"></i></button>
                                        <form method="POST" action="{{ route('staff.pr.events.destroy',$e) }}" class="d-inline">@csrf @method('DELETE')
                                            <button class="btn btn-sm zmc-icon-btn btn-outline-danger" onclick="return confirm('Delete this event?')" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">
                                <i class="ri-calendar-event-line d-block mx-auto mb-2" style="font-size:32px;color:#cbd5e1;"></i>
                                No events found. Click <strong>New Event</strong> to create one.
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($events->hasPages())
                <div class="p-3 border-top">{{ $events->appends(['tab'=>'events'])->links() }}</div>
                @endif
            </div>
        </div>

         <div class="tab-pane fade {{ $activeTab==='news' ? 'show active' : '' }}" id="panel-news" role="tabpanel" style="padding-top:0 !important; margin-top:0 !important;">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0"><i class="ri-newspaper-line me-2" style="color:#d97706;"></i>News / Press Statements</h6>
                    <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createNews">
                        <i class="ri-add-line me-1"></i>New Article
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Article</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Slug</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Status</th>
                                <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Published</th>
                                <th class="fw-semibold text-uppercase text-end pe-4" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($news as $n)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($n->image_path)
                                        <img src="{{ asset('storage/' . $n->image_path) }}" alt="" class="rounded" style="width:36px;height:36px;object-fit:cover;">
                                        @else
                                        <div class="d-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#fef3c7;">
                                            <i class="ri-newspaper-line" style="color:#d97706;"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <div class="fw-bold" style="color:#334155;">{{ Str::limit($n->title, 55) }}</div>
                                            @if($n->attachment_path)
                                            <div class="text-muted" style="font-size:11px;"><i class="ri-attachment-line me-1"></i>{{ $n->attachment_original_name ?? 'Attachment' }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><code class="text-muted" style="font-size:11px;">{{ Str::limit($n->slug, 30) }}</code></td>
                                <td>
                                    <span class="badge rounded-pill px-3 {{ $n->is_published ? 'bg-success-subtle text-success border-success-subtle' : 'bg-secondary-subtle text-secondary' }}">
                                        <i class="ri-{{ $n->is_published ? 'checkbox-circle' : 'draft' }}-line me-1"></i>{{ $n->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size:12px;">{{ $n->published_at ? $n->published_at->format('d M Y, H:i') : '—' }}</td>
                                <td class="text-end pe-4">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editNews{{ $n->id }}" title="Edit"><i class="ri-edit-line"></i></button>
                                        <form method="POST" action="{{ route('staff.pr.news.destroy',$n) }}" class="d-inline">@csrf @method('DELETE')
                                            <button class="btn btn-sm zmc-icon-btn btn-outline-danger" onclick="return confirm('Delete this news item?')" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">
                                <i class="ri-newspaper-line d-block mx-auto mb-2" style="font-size:32px;color:#cbd5e1;"></i>
                                No news items found. Click <strong>New Article</strong> to create one.
                            </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($news->hasPages())
                <div class="p-3 border-top">{{ $news->appends(['tab'=>'news'])->links() }}</div>
                @endif
            </div>
        </div>

         <div class="tab-pane fade {{ $activeTab==='tenders' ? 'show active' : '' }}" id="panel-tenders" role="tabpanel" style="padding-top:0 !important; margin-top:0 !important;">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0"><i class="ri-file-text-line me-2" style="color:#0891b2;"></i>Tenders</h6>
            <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createTender">
                <i class="ri-add-line me-1"></i>New Tender
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Tender</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Reference / Quantity</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Closing Date</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Status</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Attachment</th>
                        <th class="fw-semibold text-uppercase text-end pe-4" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenders as $t)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold" style="color:#0f172a;">{{ Str::limit($t->title, 50) }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ Str::limit(strip_tags($t->description), 60) }}</div>
                        </td>
                        <td><div class="fw-semibold">{{ $t->tender_reference ?: '—' }}</div><div class="text-muted" style="font-size:11px;">Qty: {{ $t->quantity ?: '—' }}</div></td>
                        <td>
                            @if($t->closing_at)
                                <span class="{{ $t->closing_at->isPast() ? 'text-danger' : 'text-dark' }}">{{ $t->closing_at->format('d M Y') }}</span>
                                @if($t->closing_at->isPast()) <span class="badge bg-danger bg-opacity-10 text-danger ms-1" style="font-size:10px;">Closed</span> @endif
                            @else
                                <span class="text-muted">Open ended</span>
                            @endif
                        </td>
                        <td>
                            @if($t->is_published)
                                <span class="badge bg-success bg-opacity-10 text-success px-2 py-1 rounded-pill" style="font-size:11px;">Published</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-2 py-1 rounded-pill" style="font-size:11px;">Draft</span>
                            @endif
                        </td>
                        <td>
                            @if($t->attachment_path)
                                <a href="{{ asset('storage/'.$t->attachment_path) }}" target="_blank" class="text-primary"><i class="ri-attachment-line me-1"></i>{{ Str::limit($t->attachment_original_name ?? 'Download', 20) }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex gap-1 justify-content-end">
                                <button class="btn btn-sm zmc-icon-btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#editTender{{ $t->id }}" title="Edit"><i class="ri-edit-line"></i></button>
                                <form action="{{ route('staff.pr.tenders.destroy', $t) }}" method="POST" onsubmit="return confirm('Delete this tender?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm zmc-icon-btn btn-outline-danger" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5"><i class="ri-file-text-line fs-2 d-block mb-2 opacity-25"></i>No tenders yet. Click "New Tender" to create one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenders->hasPages())<div class="p-3 border-top">{{ $tenders->appends(['tab'=>'tenders'])->links() }}</div>@endif
            </div>
        </div>

         <div class="tab-pane fade {{ $activeTab==='vacancies' ? 'show active' : '' }}" id="panel-vacancies" role="tabpanel" style="padding-top:0 !important; margin-top:0 !important;">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0"><i class="ri-briefcase-line me-2" style="color:#dc2626;"></i>Vacancies</h6>
            <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createVacancy">
                <i class="ri-add-line me-1"></i>New Vacancy
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Position</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Closing Date</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Status</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Attachment</th>
                        <th class="fw-semibold text-uppercase text-end pe-4" style="font-size:11px;letter-spacing:.05em;color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vacancies as $v)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                @if($v->image_path)
                                    <img src="{{ asset('storage/'.$v->image_path) }}" alt="" class="rounded" style="width:36px;height:36px;object-fit:cover;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#fef2f2;">
                                        <i class="ri-briefcase-line" style="color:#dc2626;"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold" style="color:#334155;">{{ Str::limit($v->title, 50) }}</div>
                                    @if($v->attachment_path)
                                    <div class="text-muted" style="font-size:11px;"><i class="ri-attachment-line me-1"></i>{{ $v->attachment_original_name ?? 'Attachment' }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($v->closing_at)
                                <span class="{{ $v->closing_at->isPast() ? 'text-danger' : 'text-dark' }}">{{ $v->closing_at->format('d M Y') }}</span>
                                @if($v->closing_at->isPast()) <span class="badge bg-danger bg-opacity-10 text-danger ms-1" style="font-size:10px;">Closed</span> @endif
                            @else
                                <span class="text-muted">Open ended</span>
                            @endif
                        </td>
                        <td>
                            @if($v->is_published)
                                <span class="badge rounded-pill px-3 bg-success-subtle text-success border-success-subtle" style="font-size:11px;"><i class="ri-checkbox-circle-line me-1"></i>Published</span>
                            @else
                                <span class="badge rounded-pill px-3 bg-secondary-subtle text-secondary" style="font-size:11px;"><i class="ri-draft-line me-1"></i>Draft</span>
                            @endif
                        </td>
                        <td>
                            @if($v->attachment_path)
                                <a href="{{ asset('storage/'.$v->attachment_path) }}" target="_blank" class="text-primary"><i class="ri-attachment-line me-1"></i>{{ Str::limit($v->attachment_original_name ?? 'Download', 20) }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex gap-1 justify-content-end">
                                <button class="btn btn-sm zmc-icon-btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#editVacancy{{ $v->id }}" title="Edit"><i class="ri-edit-line"></i></button>
                                <form action="{{ route('staff.pr.vacancies.destroy', $v) }}" method="POST" onsubmit="return confirm('Delete this vacancy?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm zmc-icon-btn btn-outline-danger" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-5"><i class="ri-briefcase-line fs-2 d-block mb-2 opacity-25"></i>No vacancies yet. Click "New Vacancy" to create one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vacancies->hasPages())<div class="p-3 border-top">{{ $vacancies->appends(['tab'=>'vacancies'])->links() }}</div>@endif
            </div>
        </div>

         <div class="tab-pane fade {{ $activeTab==='downloads' ? 'show active' : '' }}" id="panel-downloads" role="tabpanel" style="padding-top:0 !important; margin-top:0 !important;">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white p-4 border-0 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold m-0"><i class="ri-download-cloud-2-line me-2" style="color:#2563eb;"></i>Reports & Documents</h6>
                <p class="text-muted small mb-0 mt-1">Available documents for download on the ZMC public website</p>
            </div>
            <button class="btn btn-dark btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createDownload">
                <i class="ri-add-line me-1"></i>New Document
            </button>
        </div>
        
        <div class="p-4">
            <div class="row g-3">
                @php
                    $categoriesData = [
                        'registration_accreditation_forms' => [
                            'title' => 'Registration & Accreditation Forms',
                            'icon' => 'ri-file-edit-line',
                            'bg' => '#eff6ff',
                            'border' => '#2563eb',
                            'iconBg' => '#2563eb',
                            'titleColor' => '#1e40af',
                            'itemColor' => '#2563eb'
                        ],
                        'foia_forms' => [
                            'title' => 'Freedom of Information Act Forms',
                            'icon' => 'ri-scales-3-line',
                            'bg' => '#fee2e2',
                            'border' => '#dc2626',
                            'iconBg' => '#dc2626',
                            'titleColor' => '#991b1b',
                            'itemColor' => '#dc2626'
                        ],
                        'organizational_documents' => [
                            'title' => 'Organizational Documents',
                            'icon' => 'ri-team-line',
                            'bg' => '#dbeafe',
                            'border' => '#3b82f6',
                            'iconBg' => '#3b82f6',
                            'titleColor' => '#1e40af',
                            'itemColor' => '#3b82f6'
                        ],
                        'policies_guidelines' => [
                            'title' => 'Policies & Guidelines',
                            'icon' => 'ri-book-open-line',
                            'bg' => '#f3e8ff',
                            'border' => '#9333ea',
                            'iconBg' => '#9333ea',
                            'titleColor' => '#6b21a8',
                            'itemColor' => '#9333ea'
                        ],
                        'legislation' => [
                            'title' => 'Legislation',
                            'icon' => 'ri-shield-check-line',
                            'bg' => '#fef3c7',
                            'border' => '#ca8a04',
                            'iconBg' => '#ca8a04',
                            'titleColor' => '#854d0e',
                            'itemColor' => '#ca8a04'
                        ],
                        'statutory_instruments' => [
                            'title' => 'Statutory Instruments',
                            'icon' => 'ri-file-list-3-line',
                            'bg' => '#fed7aa',
                            'border' => '#ea580c',
                            'iconBg' => '#ea580c',
                            'titleColor' => '#9a3412',
                            'itemColor' => '#ea580c'
                        ]
                    ];
                    
                    // Get uploaded documents grouped by category
                    $uploadedDocs = isset($downloads) ? $downloads->groupBy('category') : collect();
                    
                    // Static documents for each category
                    $staticDocs = [
                        'registration_accreditation_forms' => [
                            ['title' => 'Registration of a Mass Media Service - Form AP1', 'url' => '/documents/registration-mass-media-service-AP1.doc', 'type' => 'DOC', 'size' => '38 KB'],
                            ['title' => 'Registration of a News Agency - Form AP2', 'url' => '/documents/registration-news-agency-AP2.doc', 'type' => 'DOC', 'size' => '37 KB'],
                            ['title' => 'Accreditation of a Journalist (Local) - Form AP3', 'url' => '/documents/accreditation-journalist-local-AP3.doc', 'type' => 'DOC', 'size' => '63 KB'],
                            ['title' => 'Accreditation of a Journalist (Foreign) - Form AP3', 'url' => '/documents/accreditation-journalist-foreign-AP3.doc', 'type' => 'DOC', 'size' => '48 KB'],
                            ['title' => 'Foreign Representative Office Permission - Form AP4', 'url' => '/documents/registration-foreign-representative-office-AP4.doc', 'type' => 'DOC', 'size' => '35 KB'],
                            ['title' => 'Renewal of Registration / Accreditation - Form AP5', 'url' => '/documents/renewal-registration-accreditation-AP5.doc', 'type' => 'DOC', 'size' => '35 KB'],
                            ['title' => 'Mass Media Levy - Form AP6', 'url' => '/documents/mass-media-levy-AP6.doc', 'type' => 'DOC', 'size' => '35 KB'],
                        ],
                        'foia_forms' => [
                            ['title' => 'Freedom of Information Request Form', 'url' => '/documents/si-229-of-2021-foia-regulations.pdf#page=15', 'type' => 'PDF', 'size' => '538 KB'],
                            ['title' => 'Form of Acknowledgement by Information Officer', 'url' => '/documents/si-229-of-2021-foia-regulations.pdf#page=19', 'type' => 'PDF', 'size' => '538 KB'],
                            ['title' => 'FOIA Appeal Form', 'url' => '/documents/si-229-of-2021-foia-regulations.pdf#page=19', 'type' => 'PDF', 'size' => '538 KB'],
                            ['title' => 'Form of Subpoena', 'url' => '/documents/si-229-of-2021-foia-regulations.pdf#page=20', 'type' => 'PDF', 'size' => '538 KB'],
                        ],
                        'organizational_documents' => [
                            ['title' => 'ZMC Service Charter', 'url' => '/documents/zmc-service-charter.pdf', 'type' => 'PDF', 'size' => '4.8 MB'],
                            ['title' => 'ZMC Organogram', 'url' => '/documents/zmc-organogram.pdf', 'type' => 'PDF', 'size' => '1.9 MB'],
                        ],
                        'policies_guidelines' => [
                            ['title' => 'Summary of the Freedom of Information Act', 'url' => '/documents/summary-of-freedom-of-information-act.pdf', 'type' => 'PDF', 'size' => '214 KB'],
                        ],
                        'legislation' => [
                            ['title' => 'Zimbabwe Media Commission Act [Chapter 10:35]', 'url' => '/documents/zmc-act-chapter-10-35.pdf', 'type' => 'PDF', 'size' => '623 KB'],
                            ['title' => 'Freedom of Information Act [Chapter 10:33]', 'url' => '/documents/freedom-of-information-act-2020.pdf', 'type' => 'PDF', 'size' => '1.7 MB'],
                            ['title' => 'Constitution of Zimbabwe (2023)', 'url' => '/documents/constitution-of-zimbabwe-2023.pdf', 'type' => 'PDF', 'size' => '1.3 MB'],
                        ],
                        'statutory_instruments' => [
                            ['title' => 'SI 65 of 2022 - Fee Schedule', 'url' => '/documents/si-65-of-2022-fee-schedule.pdf', 'type' => 'PDF', 'size' => '112 KB'],
                            ['title' => 'SI 229 of 2021 - Freedom of Information (General) Regulations', 'url' => '/documents/si-229-of-2021-foia-regulations.pdf', 'type' => 'PDF', 'size' => '538 KB'],
                            ['title' => 'SI 169C of 2002 - AIPPA Regulations', 'url' => '/documents/si-169c-of-2002.pdf', 'type' => 'PDF', 'size' => '8.9 MB'],
                            ['title' => 'SI 10 of 2004 - AIPPA Amendment Regulations', 'url' => '/documents/si-10-of-2004.pdf', 'type' => 'PDF', 'size' => '1.9 MB'],
                        ],
                    ];
                @endphp
                
                @foreach($categoriesData as $categoryKey => $categoryInfo)
                <div class="col-12 col-md-6">
                    <div class="card border rounded-4 h-100">
                        <div class="card-header border-0 p-3" style="background:{{ $categoryInfo['bg'] }}; border-bottom:3px solid {{ $categoryInfo['border'] }}!important;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center rounded-3" style="width:40px;height:40px;background:{{ $categoryInfo['iconBg'] }};">
                                    <i class="{{ $categoryInfo['icon'] }} text-white fs-5"></i>
                                </div>
                                <h6 class="fw-bold m-0" style="color:{{ $categoryInfo['titleColor'] }};">{{ $categoryInfo['title'] }}</h6>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="list-group list-group-flush small">
                                
                                {{-- Uploaded Documents for this category --}}
                                @if($uploadedDocs->has($categoryKey))
                                    @foreach($uploadedDocs[$categoryKey] as $doc)
                                        @if($doc->is_published)
                                        <div class="list-group-item border rounded-3 mb-2" style="background:#f0fdf4;">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="ri-file-text-line mt-1" style="color:{{ $categoryInfo['itemColor'] }};"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="fw-semibold text-truncate">{{ $doc->title }}</div>
                                                    <div class="text-muted" style="font-size:11px;">
                                                        {{ strtoupper(pathinfo($doc->file_original_name ?? '', PATHINFO_EXTENSION)) }}
                                                        @if($doc->file_size_bytes)
                                                        | {{ number_format($doc->file_size_bytes / 1024, 0) }} KB
                                                        @endif
                                                        <span class="badge bg-success-subtle text-success ms-1" style="font-size:10px;">Uploaded</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    @if($doc->file_path)
                                                    <a href="{{ asset('storage/' . $doc->file_path) }}" class="btn btn-sm btn-link p-0" target="_blank" title="Download">
                                                        <i class="ri-download-line" style="color:{{ $categoryInfo['itemColor'] }};"></i>
                                                    </a>
                                                    @endif
                                                    <button class="btn btn-sm btn-link p-0" data-bs-toggle="modal" data-bs-target="#editDownload{{ $doc->id }}" title="Edit">
                                                        <i class="ri-edit-line" style="color:{{ $categoryInfo['itemColor'] }};"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('staff.pr.downloads.destroy', $doc) }}" class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-sm btn-link p-0" onclick="return confirm('Delete this document?')" title="Delete" type="submit">
                                                            <i class="ri-delete-bin-line text-danger"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @endforeach
                                @endif
                                
                                {{-- Static Documents --}}
                                @if(isset($staticDocs[$categoryKey]))
                                    @foreach($staticDocs[$categoryKey] as $staticDoc)
                                    <a href="{{ $staticDoc['url'] }}" class="list-group-item list-group-item-action border rounded-3 mb-2" target="_blank">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ri-file-{{ strtolower($staticDoc['type']) === 'pdf' ? 'pdf' : 'text' }}-line" style="color:{{ $categoryInfo['itemColor'] }};"></i>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-semibold text-truncate">{{ $staticDoc['title'] }}</div>
                                                <div class="text-muted" style="font-size:11px;">{{ $staticDoc['type'] }} | {{ $staticDoc['size'] }}</div>
                                            </div>
                                            <i class="ri-download-line" style="color:{{ $categoryInfo['itemColor'] }};"></i>
                                        </div>
                                    </a>
                                    @endforeach
                                @endif
                                
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </div>
</div>

    </div>{{-- /tab-content --}}

<div class="modal fade" id="createNotice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" enctype="multipart/form-data" action="{{ route('staff.pr.notices.store') }}">
                @csrf
            <div class="modal-header bg-white border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-megaphone-line me-2" style="color:#2563eb;"></i>Create Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Notice Type <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" name="notice_category" required>
                            <option value="">Select type...</option>
                            <option value="Public Notice">Public Notice</option>
                            <option value="Legal Notice">Legal Notice</option>
                            <option value="Tender Notice">Tender Notice</option>
                            <option value="Hearing Notice">Hearing Notice</option>
                            <option value="Vacancy Announcement">Vacancy Announcement</option>
                            <option value="Regulatory Directive">Regulatory Directive</option>
                            <option value="Press Notice">Press Notice</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Reference Number</label><input class="form-control rounded-3" name="reference_number" placeholder="e.g. ZMC/NOT/{{ now()->year }}/001"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label><input class="form-control rounded-3" name="title" required></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Body <span class="text-danger">*</span></label><textarea class="form-control rounded-3" rows="5" name="body" required></textarea></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Summary / Key Points</label><textarea class="form-control rounded-3" rows="3" name="summary" placeholder="Brief bullets or summary for quick reading..."></textarea></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Effective Date</label><input class="form-control rounded-3" type="date" name="effective_date"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Expiry Date</label><input class="form-control rounded-3" type="date" name="expires_at"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Urgency Level</label>
                        <select class="form-select rounded-3" name="urgency_level">
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Issuing Authority</label>
                        <select class="form-select rounded-3" name="issuing_authority">
                            <option value="Zimbabwe Media Commission">Zimbabwe Media Commission</option>
                            <option value="Complaints Committee">Complaints Committee</option>
                            <option value="Accreditation Committee">Accreditation Committee</option>
                            <option value="Office of the Chairperson">Office of the Chairperson</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Signed By</label><input class="form-control rounded-3" name="signed_by" placeholder="e.g. Nyaradzo Makombe Hazangwi – Acting Secretary"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Target Audience</label>
                        <select class="form-select rounded-3" name="target_audience">
                            <option value="">Select audience...</option>
                            <option value="General Public">General Public</option>
                            <option value="Media Houses">Media Houses</option>
                            <option value="Broadcasters">Broadcasters</option>
                            <option value="Journalists">Journalists</option>
                            <option value="Applicants">Applicants</option>
                            <option value="Licensed Stations">Licensed Stations</option>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Language</label>
                        <select class="form-select rounded-3" name="language">
                            <option value="English">English</option>
                            <option value="Shona">Shona</option>
                            <option value="Ndebele">Ndebele</option>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold small">Related Legal Reference</label><input class="form-control rounded-3" name="related_legal_reference" placeholder="e.g. Section 249 of the Constitution of Zimbabwe / Media Practitioners Bill"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Attachment Label</label><input class="form-control rounded-3" name="attachment_label" placeholder="e.g. Download Notice PDF"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Contact Name</label><input class="form-control rounded-3" name="contact_name" placeholder="Inquiries contact name"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Contact Phone</label><input class="form-control rounded-3" name="contact_phone" placeholder="+263 ..."></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Contact Email</label><input class="form-control rounded-3" type="email" name="contact_email" placeholder="info@example.com"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Image (optional)</label><input class="form-control rounded-3" type="file" name="image" accept="image/*"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Attachment (optional)</label><input class="form-control rounded-3" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Target Portal</label>
                        <select class="form-select rounded-3" name="target_portal" required>
                            <option value="both">Both Portals</option><option value="journalist">Media Practitioner Portal</option><option value="mediahouse">Media House Portal</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" checked id="n_pub"><label class="form-check-label small" for="n_pub">Publish immediately</label></div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-3">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="stick_to_top" value="1" id="n_top"><label class="form-check-label small" for="n_top">Stick to Top</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_on_homepage" value="1" id="n_home"><label class="form-check-label small" for="n_home">Show on Homepage</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="send_email_alert" value="1" id="n_email"><label class="form-check-label small" for="n_email">Send Email Alert</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Save Notice</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createEvent" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" enctype="multipart/form-data" action="{{ route('staff.pr.events.store') }}">
                @csrf
            <div class="modal-header bg-white border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-calendar-event-line me-2" style="color:#16a34a;"></i>Create Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold small">Title</label><input class="form-control rounded-3" name="title" required></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Description</label><textarea class="form-control rounded-3" rows="4" name="description"></textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Location</label><input class="form-control rounded-3" name="location" placeholder="e.g. Harare International Conference Centre"></div>
                    <div class="col-12 col-md-3"><label class="form-label fw-semibold small">Starts</label><input class="form-control rounded-3" type="datetime-local" name="starts_at"></div>
                    <div class="col-12 col-md-3"><label class="form-label fw-semibold small">Ends</label><input class="form-control rounded-3" type="datetime-local" name="ends_at"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Image (optional)</label><input class="form-control rounded-3" type="file" name="image" accept="image/*"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Attachment (optional)</label><input class="form-control rounded-3" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Target Portal</label>
                        <select class="form-select rounded-3" name="target_portal" required>
                            <option value="both">Both Portals</option><option value="journalist">Media Practitioner Portal</option><option value="mediahouse">Media House Portal</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" checked id="e_pub"><label class="form-check-label small" for="e_pub">Publish immediately</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Save Event</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createNews" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" enctype="multipart/form-data" action="{{ route('staff.pr.news.store') }}">
                @csrf
            <div class="modal-header bg-white border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-newspaper-line me-2" style="color:#d97706;"></i>Create News Article</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label><input class="form-control rounded-3" name="title" required></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Slug / URL Alias</label><input class="form-control rounded-3" name="slug" placeholder="Auto-generated from title" maxlength="200"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Excerpt / Summary</label><input class="form-control rounded-3" name="excerpt" maxlength="160" placeholder="Short preview (max 160 characters)"></div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'news_body_new', 'name' => 'body', 'label' => 'Article Body', 'required' => true, 'value' => ''])</div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">News Category <span class="text-danger">*</span></label>
                        <select class="form-select rounded-3" name="category" required>
                            <option value="">Select category...</option>
                            <option value="Press Release">Press Release</option>
                            <option value="Media Advisory">Media Advisory</option>
                            <option value="Commission Update">Commission Update</option>
                            <option value="Legal Notice">Legal Notice</option>
                            <option value="Speech">Speech</option>
                            <option value="Report">Report</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Language</label>
                        <select class="form-select rounded-3" name="language">
                            <option value="English">English</option>
                            <option value="Shona">Shona</option>
                            <option value="Ndebele">Ndebele</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Status</label>
                        <select class="form-select rounded-3" name="status">
                            <option value="Draft">Draft</option>
                            <option value="Pending Review">Pending Review</option>
                            <option value="Published">Published</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Publication Date &amp; Time</label>
                        <input class="form-control rounded-3" type="datetime-local" name="published_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold small">Tags / Keywords</label><input class="form-control rounded-3" name="tags" placeholder="e.g. ZMC, media reforms, accreditation"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Author Name</label><input class="form-control rounded-3" name="author_name" placeholder="Writer or ZMC official"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Source / Reference</label><input class="form-control rounded-3" name="source_reference" placeholder="e.g. ZMC Statement, Parliament of Zimbabwe"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Meta Description</label><textarea class="form-control rounded-3" rows="3" name="meta_description" placeholder="SEO description"></textarea></div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Article Images (up to 10)</label>
                        <input class="form-control rounded-3" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Image Alt Text</label>
                        <input class="form-control rounded-3" name="image_alt_text" placeholder="Accessibility / SEO text for image">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Attachment (optional)</label>
                        <input class="form-control rounded-3" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" checked id="news_pub"><label class="form-check-label small" for="news_pub">Publish immediately</label></div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-3">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="featured" value="1" id="news_featured"><label class="form-check-label small" for="news_featured">Featured News</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_comments" value="1" checked id="news_comments"><label class="form-check-label small" for="news_comments">Allow Comments</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_subscribers" value="1" id="news_notify"><label class="form-check-label small" for="news_notify">Notify Subscribers</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-outline-primary rounded-pill px-4" type="button" data-article-preview><i class="ri-eye-line me-1"></i>Preview</button><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Publish Article</button></div>
            </form>
        </div>
    </div>
</div>

@foreach($notices as $n)
<div class="modal fade" id="editNotice{{ $n->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" enctype="multipart/form-data" action="{{ route('staff.pr.notices.update',$n) }}">
                @csrf @method('PUT')
            <div class="modal-header bg-white border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-edit-line me-2" style="color:#2563eb;"></i>Edit Notice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Notice Type</label>
                        <select class="form-select rounded-3" name="notice_category">
                            <option value="">Select type...</option>
                            @foreach(['Public Notice','Legal Notice','Tender Notice','Hearing Notice','Vacancy Announcement','Regulatory Directive','Press Notice'] as $cat)
                                <option value="{{ $cat }}" @selected($n->notice_category === $cat)>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Reference Number</label><input class="form-control rounded-3" name="reference_number" value="{{ $n->reference_number }}" placeholder="e.g. ZMC/NOT/{{ now()->year }}/001"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Title</label><input class="form-control rounded-3" name="title" value="{{ $n->title }}" required></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Body</label><textarea class="form-control rounded-3" rows="5" name="body" required>{{ $n->body }}</textarea></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Summary / Key Points</label><textarea class="form-control rounded-3" rows="3" name="summary" placeholder="Brief bullets or summary...">{{ $n->summary }}</textarea></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Effective Date</label><input class="form-control rounded-3" type="date" name="effective_date" value="{{ optional($n->effective_date)->format('Y-m-d') }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Expiry Date</label><input class="form-control rounded-3" type="date" name="expires_at" value="{{ optional($n->expires_at)->format('Y-m-d') }}"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold small">Urgency Level</label>
                        <select class="form-select rounded-3" name="urgency_level">
                            @foreach(['Normal','High','Urgent'] as $lvl)
                                <option value="{{ $lvl }}" @selected($n->urgency_level === $lvl)>{{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Issuing Authority</label>
                        <select class="form-select rounded-3" name="issuing_authority">
                            @foreach(['Zimbabwe Media Commission','Complaints Committee','Accreditation Committee','Office of the Chairperson'] as $auth)
                                <option value="{{ $auth }}" @selected($n->issuing_authority === $auth)>{{ $auth }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Signed By</label><input class="form-control rounded-3" name="signed_by" value="{{ $n->signed_by }}" placeholder="e.g. Nyaradzo Makombe Hazangwi – Acting Secretary"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Target Audience</label>
                        <select class="form-select rounded-3" name="target_audience">
                            <option value="">Select audience...</option>
                            @foreach(['General Public','Media Houses','Broadcasters','Journalists','Applicants','Licensed Stations'] as $aud)
                                <option value="{{ $aud }}" @selected($n->target_audience === $aud)>{{ $aud }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Language</label>
                        <select class="form-select rounded-3" name="language">
                            @foreach(['English','Shona','Ndebele'] as $lang)
                                <option value="{{ $lang }}" @selected($n->language === $lang)>{{ $lang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold small">Related Legal Reference</label><input class="form-control rounded-3" name="related_legal_reference" value="{{ $n->related_legal_reference }}" placeholder="e.g. Section 249 of the Constitution of Zimbabwe / Media Practitioners Bill"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Attachment Label</label><input class="form-control rounded-3" name="attachment_label" value="{{ $n->attachment_label }}" placeholder="e.g. Download Notice PDF"></div>
                    <div class="col-12 col-md-4"><label class="form-label fw-semibold small">Contact Name</label><input class="form-control rounded-3" name="contact_name" value="{{ $n->contact_name }}" placeholder="Inquiries contact name"></div>
                    <div class="col-12 col-md-4"><label class="form-label fw-semibold small">Contact Phone</label><input class="form-control rounded-3" name="contact_phone" value="{{ $n->contact_phone }}" placeholder="+263 ..."></div>
                    <div class="col-12 col-md-4"><label class="form-label fw-semibold small">Contact Email</label><input class="form-control rounded-3" type="email" name="contact_email" value="{{ $n->contact_email }}" placeholder="info@example.com"></div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Image</label>
                        <input class="form-control rounded-3" type="file" name="image" accept="image/*">
                        @if($n->image_path)<div class="small text-muted mt-1"><i class="ri-image-line me-1"></i><a href="{{ asset('storage/'.$n->image_path) }}" target="_blank">View current</a></div>@endif
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Attachment</label>
                        <input class="form-control rounded-3" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*">
                        @if($n->attachment_path)<div class="small text-muted mt-1"><i class="ri-attachment-line me-1"></i><a href="{{ asset('storage/'.$n->attachment_path) }}" target="_blank">{{ $n->attachment_original_name ?? 'Current file' }}</a></div>@endif
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Target Portal</label>
                        <select class="form-select rounded-3" name="target_portal" required>
                            <option value="both" @selected($n->target_portal==='both')>Both Portals</option>
                            <option value="journalist" @selected($n->target_portal==='journalist')>Media Practitioner Portal</option>
                            <option value="mediahouse" @selected($n->target_portal==='mediahouse')>Media House Portal</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" @checked($n->is_published) id="n_pub{{ $n->id }}"><label class="form-check-label small" for="n_pub{{ $n->id }}">Published</label></div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-3">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="stick_to_top" value="1" @checked($n->stick_to_top) id="n_top{{ $n->id }}"><label class="form-check-label small" for="n_top{{ $n->id }}">Stick to Top</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_on_homepage" value="1" @checked($n->show_on_homepage) id="n_home{{ $n->id }}"><label class="form-check-label small" for="n_home{{ $n->id }}">Show on Homepage</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="send_email_alert" value="1" @checked($n->send_email_alert) id="n_email{{ $n->id }}"><label class="form-check-label small" for="n_email{{ $n->id }}">Send Email Alert</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Update</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($events as $e)
<div class="modal fade" id="editEvent{{ $e->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" enctype="multipart/form-data" action="{{ route('staff.pr.events.update',$e) }}">
                @csrf @method('PUT')
            <div class="modal-header bg-white border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-edit-line me-2" style="color:#16a34a;"></i>Edit Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold small">Title</label><input class="form-control rounded-3" name="title" value="{{ $e->title }}" required></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Description</label><textarea class="form-control rounded-3" rows="4" name="description">{{ $e->description }}</textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Location</label><input class="form-control rounded-3" name="location" value="{{ $e->location }}"></div>
                    <div class="col-12 col-md-3"><label class="form-label fw-semibold small">Starts</label><input class="form-control rounded-3" type="datetime-local" name="starts_at" value="{{ optional($e->starts_at)->format('Y-m-d\TH:i') }}"></div>
                    <div class="col-12 col-md-3"><label class="form-label fw-semibold small">Ends</label><input class="form-control rounded-3" type="datetime-local" name="ends_at" value="{{ optional($e->ends_at)->format('Y-m-d\TH:i') }}"></div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Image</label>
                        <input class="form-control rounded-3" type="file" name="image" accept="image/*">
                        @if($e->image_path)<div class="small text-muted mt-1"><i class="ri-image-line me-1"></i><a href="{{ asset('storage/'.$e->image_path) }}" target="_blank">View current</a></div>@endif
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Attachment</label>
                        <input class="form-control rounded-3" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*">
                        @if($e->attachment_path)<div class="small text-muted mt-1"><i class="ri-attachment-line me-1"></i><a href="{{ asset('storage/'.$e->attachment_path) }}" target="_blank">{{ $e->attachment_original_name ?? 'Download' }}</a></div>@endif
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Target Portal</label>
                        <select class="form-select rounded-3" name="target_portal" required>
                            <option value="both" @selected($e->target_portal==='both')>Both Portals</option>
                            <option value="journalist" @selected($e->target_portal==='journalist')>Media Practitioner Portal</option>
                            <option value="mediahouse" @selected($e->target_portal==='mediahouse')>Media House Portal</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" @checked($e->is_published) id="e_pub{{ $e->id }}"><label class="form-check-label small" for="e_pub{{ $e->id }}">Published</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Update</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($news as $n)
<div class="modal fade" id="editNews{{ $n->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <form method="POST" enctype="multipart/form-data" action="{{ route('staff.pr.news.update',$n) }}">
                @csrf @method('PUT')
            <div class="modal-header bg-white border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold"><i class="ri-edit-line me-2" style="color:#d97706;"></i>Edit News Article</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label><input class="form-control rounded-3" name="title" value="{{ $n->title }}" required></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Slug / URL Alias</label><input class="form-control rounded-3" name="slug" value="{{ $n->slug }}" maxlength="200"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Excerpt / Summary</label><input class="form-control rounded-3" name="excerpt" value="{{ $n->excerpt }}" maxlength="160" placeholder="Short preview (max 160 characters)"></div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'news_body_' . $n->id, 'name' => 'body', 'label' => 'Article Body', 'required' => true, 'value' => $n->body])</div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">News Category</label>
                        <select class="form-select rounded-3" name="category">
                            <option value="">Select category...</option>
                            @foreach(['Press Release','Media Advisory','Commission Update','Legal Notice','Speech','Report'] as $cat)
                                <option value="{{ $cat }}" @selected($n->category === $cat || ($n->category === 'Statement' && $cat === 'Press Release'))>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Language</label>
                        <select class="form-select rounded-3" name="language">
                            @foreach(['English','Shona','Ndebele'] as $lang)
                                <option value="{{ $lang }}" @selected($n->language === $lang)>{{ $lang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Status</label>
                        <select class="form-select rounded-3" name="status">
                            @foreach(['Draft','Pending Review','Published','Archived'] as $st)
                                <option value="{{ $st }}" @selected($n->status === $st)>{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Publication Date &amp; Time</label>
                        <input class="form-control rounded-3" type="datetime-local" name="published_at" value="{{ optional(\Carbon\Carbon::parse($n->published_at ?? now()))->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-12"><label class="form-label fw-semibold small">Tags / Keywords</label><input class="form-control rounded-3" name="tags" value="{{ $n->tags }}" placeholder="e.g. ZMC, media reforms, accreditation"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Author Name</label><input class="form-control rounded-3" name="author_name" value="{{ $n->author_name }}" placeholder="Writer or ZMC official"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Source / Reference</label><input class="form-control rounded-3" name="source_reference" value="{{ $n->source_reference }}" placeholder="e.g. ZMC Statement, Parliament of Zimbabwe"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Meta Description</label><textarea class="form-control rounded-3" rows="3" name="meta_description" placeholder="SEO description">{{ $n->meta_description }}</textarea></div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Add Images (up to 10 total)</label>
                        <input class="form-control rounded-3" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                        @php $newsImages = array_values(array_unique(array_filter(array_merge((array) $n->image_paths, [$n->image_path])))); @endphp
                        @if($newsImages)
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach($newsImages as $imagePath)
                                    <label class="border rounded p-1 small text-center" style="width:96px;">
                                        <img class="pr-existing-image" src="{{ asset('storage/' . $imagePath) }}" alt="" style="width:82px;height:56px;object-fit:cover;">
                                        <span class="d-block"><input type="checkbox" name="remove_image_paths[]" value="{{ $imagePath }}"> Remove</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Image Alt Text</label>
                        <input class="form-control rounded-3" name="image_alt_text" value="{{ $n->image_alt_text }}" placeholder="Accessibility / SEO text for image">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Replace Attachment</label>
                        <input class="form-control rounded-3" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*">
                        @if($n->attachment_path)<div class="small text-muted mt-1"><i class="ri-attachment-line me-1"></i><a href="{{ asset('storage/'.$n->attachment_path) }}" target="_blank">{{ $n->attachment_original_name ?? 'Current file' }}</a></div>@endif
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" @checked($n->is_published) id="news_pub{{ $n->id }}"><label class="form-check-label small" for="news_pub{{ $n->id }}">Published</label></div>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-3">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="featured" value="1" @checked($n->featured) id="news_featured{{ $n->id }}"><label class="form-check-label small" for="news_featured{{ $n->id }}">Featured News</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_comments" value="1" @checked($n->allow_comments) id="news_comments{{ $n->id }}"><label class="form-check-label small" for="news_comments{{ $n->id }}">Allow Comments</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="notify_subscribers" value="1" @checked($n->notify_subscribers) id="news_notify{{ $n->id }}"><label class="form-check-label small" for="news_notify{{ $n->id }}">Notify Subscribers</label></div>
                    </div>
                </div>
            </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Attachment</label>
                        <input class="form-control rounded-3" type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/*">
                        @if($n->attachment_path)<div class="small text-muted mt-1"><i class="ri-attachment-line me-1"></i><a href="{{ asset('storage/'.$n->attachment_path) }}" target="_blank">{{ $n->attachment_original_name ?? 'Download' }}</a></div>@endif
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" @checked($n->is_published) id="news_pub{{ $n->id }}"><label class="form-check-label small" for="news_pub{{ $n->id }}">Published</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-outline-primary rounded-pill px-4" type="button" data-article-preview><i class="ri-eye-line me-1"></i>Preview</button><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Update</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<div class="modal fade" id="prArticlePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header"><h5 class="modal-title fw-bold">Article Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close preview"></button></div>
            <div class="modal-body p-0"><iframe id="prArticlePreviewFrame" title="News article preview" sandbox="" referrerpolicy="no-referrer" style="display:block;width:100%;height:70vh;border:0;background:#fff;"></iframe></div>
        </div>
    </div>
</div>

{{-- ===================== CREATE TENDER MODAL ===================== --}}
<div class="modal fade" id="createTender" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content rounded-4 border-0 shadow">
        <form action="{{ route('staff.pr.tenders.store') }}" method="POST" enctype="multipart/form-data">@csrf
            <div class="modal-header bg-white border-0 p-4 pb-2"><h5 class="modal-title fw-bold"><i class="ri-file-text-line me-2"></i>New Tender</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Reference Number <span class="text-danger">*</span></label><input type="text" name="tender_reference" class="form-control rounded-3" required placeholder="e.g. ZMC/ONB/{{ now()->year }}/001"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Quantity <span class="text-danger">*</span></label><input type="text" name="quantity" class="form-control rounded-3" required maxlength="120" placeholder="e.g. 20 laptops, 1 service contract"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Procurement Method</label>
                        <select class="form-select rounded-3" name="procurement_method">
                            <option value="">Select method...</option>
                            <option value="Open Bidding">Open Bidding</option>
                            <option value="National Competitive Bidding (NCB)">National Competitive Bidding (NCB)</option>
                            <option value="International Competitive Bidding (ICB)">International Competitive Bidding (ICB)</option>
                            <option value="Request for Quotations (RFQ)">Request for Quotations (RFQ)</option>
                            <option value="Direct Procurement">Direct Procurement</option>
                            <option value="Framework Agreement">Framework Agreement</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Tender Category</label>
                        <select class="form-select rounded-3" name="tender_category">
                            <option value="">Select category...</option>
                            <option value="Goods">Goods</option>
                            <option value="Services">Services</option>
                            <option value="Works">Works</option>
                            <option value="Consultancy Services">Consultancy Services</option>
                            <option value="ICT Equipment">ICT Equipment</option>
                            <option value="Office Supplies">Office Supplies</option>
                            <option value="Printing Services">Printing Services</option>
                            <option value="Security Services">Security Services</option>
                            <option value="Cleaning Services">Cleaning Services</option>
                            <option value="Vehicle Supply">Vehicle Supply</option>
                            <option value="Construction">Construction</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Tender Status</label>
                        <select class="form-select rounded-3" name="tender_status">
                            <option value="Open">Open</option>
                            <option value="Closing Soon">Closing Soon</option>
                            <option value="Closed">Closed</option>
                            <option value="Awarded">Awarded</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Postponed">Postponed</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control rounded-3" required maxlength="200" placeholder="e.g. Supply of Office Equipment">
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Issuing Organisation</label><input type="text" name="issuing_organisation" class="form-control rounded-3" maxlength="190"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Summary</label><textarea name="summary" class="form-control rounded-3" rows="2" maxlength="5000"></textarea></div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control rounded-3" rows="5" required placeholder="Full tender details, requirements, submission instructions..."></textarea>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contract Value Estimate</label><input type="text" name="contract_value_estimate" class="form-control rounded-3" placeholder="e.g. ZWL 5,000,000 or USD 50,000"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Bid Security Amount</label><input type="text" name="bid_security_amount" class="form-control rounded-3" placeholder="e.g. ZWL 100,000"></div>
                    <div class="col-12 d-flex flex-wrap gap-3">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="bid_security_required" value="1" id="t_bid_sec"><label class="form-check-label small" for="t_bid_sec">Bid Security Required</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pre_bid_meeting_mandatory" value="1" id="t_bid_mand"><label class="form-check-label small" for="t_bid_mand">Pre-bid Meeting Mandatory</label></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Preferred Bidder Categories</label>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_bidder_categories[]" value="Registered with PRAZ"> <label class="form-check-label small">Registered with PRAZ</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_bidder_categories[]" value="Youth-Owned"> <label class="form-check-label small">Youth-Owned</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_bidder_categories[]" value="Women-Owned"> <label class="form-check-label small">Women-Owned</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_bidder_categories[]" value="Persons with Disabilities"> <label class="form-check-label small">Persons with Disabilities</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_bidder_categories[]" value="Locally Based"> <label class="form-check-label small">Locally Based</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_bidder_categories[]" value="International"> <label class="form-check-label small">International</label></div>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Closing Date <span class="text-danger">*</span></label><input type="date" name="closing_at" class="form-control rounded-3" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Opening Date &amp; Time</label><input type="datetime-local" name="opening_at" class="form-control rounded-3"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Opening Venue / Link</label><input type="text" name="opening_venue" class="form-control rounded-3" placeholder="e.g. ZMC Boardroom, 2nd Floor, ZB Life Towers, Harare"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Submission Address / Method</label><textarea name="submission_method" class="form-control rounded-3" rows="3" placeholder="Physical drop-off, email, online portal..."></textarea></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Submission Address</label><input type="text" name="submission_address" class="form-control rounded-3" placeholder="Tender Box, Zimbabwe Media Commission, 1st Floor, ZB Life Towers..."></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Name</label><input type="text" name="contact_name" class="form-control rounded-3" placeholder="Tawanda Ncube"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Title</label><input type="text" name="contact_title" class="form-control rounded-3" placeholder="Procurement Officer"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Email</label><input type="email" name="contact_email" class="form-control rounded-3" placeholder="procurement@example.com"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Phone</label><input type="text" name="contact_phone" class="form-control rounded-3" placeholder="+263 242 123456"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Questions Deadline</label><input type="date" name="questions_deadline" class="form-control rounded-3"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">PRAZ EGP Tender Page <span class="text-danger">*</span></label><input type="url" name="bidding_documents_link" class="form-control rounded-3" required placeholder="https://egp.praz.org.zw/..."></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Evaluation Criteria</label><textarea name="evaluation_criteria" class="form-control rounded-3" rows="3" placeholder="e.g. 60% Technical, 40% Financial. Minimum technical score: 70%"></textarea></div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Tender Image</label>
                        <input type="file" name="image" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Attachment (PDF/DOC)</label>
                        <input type="file" name="attachment" class="form-control rounded-3" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Language</label>
                        <select class="form-select rounded-3" name="language">
                            <option value="English">English</option>
                            <option value="English & Shona">English & Shona</option>
                            <option value="English & Ndebele">English & Ndebele</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" checked id="tender_pub_new"><label class="form-check-label small" for="tender_pub_new">Publish immediately</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Create Tender</button></div>
        </form>
    </div></div>
</div>

{{-- ===================== CREATE VACANCY MODAL ===================== --}}
<div class="modal fade" id="createVacancy" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content rounded-4 border-0 shadow">
        <form action="{{ route('staff.pr.vacancies.store') }}" method="POST" enctype="multipart/form-data">@csrf
            <div class="modal-header bg-white border-0 p-4 pb-2"><h5 class="modal-title fw-bold"><i class="ri-briefcase-line me-2"></i>New Vacancy</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Position Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control rounded-3" required maxlength="200" placeholder="e.g. Senior Communications Officer"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Vacancy Reference</label><input type="text" name="vacancy_reference" class="form-control rounded-3" placeholder="e.g. ZMC/HR/{{ now()->year }}/001"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Department / Division</label>
                        <select class="form-select rounded-3" name="department">
                            <option value="">Select department...</option>
                            <option value="Human Resources">Human Resources</option>
                            <option value="Finance">Finance</option>
                            <option value="Legal">Legal</option>
                            <option value="Accreditation">Accreditation</option>
                            <option value="Complaints">Complaints</option>
                            <option value="Monitoring & Research">Monitoring & Research</option>
                            <option value="Corporate Affairs">Corporate Affairs</option>
                            <option value="ICT">ICT</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Job Grade / Level</label>
                        <select class="form-select rounded-3" name="job_grade">
                            <option value="">Select grade...</option>
                            <option value="Executive">Executive</option>
                            <option value="Senior Management">Senior Management</option>
                            <option value="Middle Management">Middle Management</option>
                            <option value="Professional">Professional</option>
                            <option value="Technical">Technical</option>
                            <option value="Administrative">Administrative</option>
                            <option value="Intern">Intern</option>
                            <option value="Trainee">Trainee</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Employment Type</label>
                        <select class="form-select rounded-3" name="employment_type">
                            <option value="">Select type...</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Fixed-term Contract">Fixed-term Contract</option>
                            <option value="Temporary">Temporary</option>
                            <option value="Internship">Internship</option>
                            <option value="Secondment">Secondment</option>
                            <option value="Consultancy">Consultancy</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Contract Duration</label><input type="text" name="contract_duration" class="form-control rounded-3" placeholder="e.g. 12 months, 2 years"></div>
                    <div class="col-12"><div class="alert alert-light border small mb-0">Vacancy status is calculated from the closing date.</div></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Number of Positions</label><input type="number" name="number_of_positions" class="form-control rounded-3" value="1" min="1"></div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_body_new', 'name' => 'body', 'label' => 'Description', 'required' => true, 'value' => ''])</div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_requirements_new', 'name' => 'qualifications_required', 'label' => 'Requirements', 'value' => ''])</div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_experience_new', 'name' => 'experience_required', 'label' => 'Relevant Experience', 'value' => ''])</div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_skills_new', 'name' => 'skills_competencies', 'label' => 'Skills', 'value' => ''])</div>
                    <div class="col-12"><label class="form-label fw-semibold small">Duty Station / Location</label><input type="text" name="duty_station" class="form-control rounded-3" placeholder="e.g. Harare (with possible travel to Bulawayo, Mutare, Masvingo)"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">How to Apply: Email Address <span class="text-danger">*</span></label><input type="email" name="submission_email" class="form-control rounded-3" placeholder="hr@example.com" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Closing Date</label><input type="date" name="closing_at" class="form-control rounded-3"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Preferred Applicant Categories</label><br>
                        @foreach(['Persons with Disabilities','Women','Youth (under 35)','Ex-combatants','Rural candidates','No preference'] as $cat)
                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_applicant_categories[]" value="{{ $cat }}"> <label class="form-check-label small">{{ $cat }}</label></div>
                        @endforeach
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Image</label>
                        <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Attachment (PDF/DOC)</label>
                        <input type="file" name="attachment" class="form-control rounded-3" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" checked id="vac_pub_new"><label class="form-check-label small" for="vac_pub_new">Publish immediately</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Create Vacancy</button></div>
        </form>
    </div></div>
</div>

@foreach($tenders as $t)
<div class="modal fade" id="editTender{{ $t->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content rounded-4 border-0 shadow">
        <form action="{{ route('staff.pr.tenders.update', $t) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="modal-header bg-white border-0 p-4 pb-2"><h5 class="modal-title fw-bold">Edit Tender #{{ $t->id }}</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Reference Number <span class="text-danger">*</span></label><input type="text" name="tender_reference" class="form-control rounded-3" value="{{ $t->tender_reference }}" required placeholder="e.g. ZMC/ONB/{{ now()->year }}/001"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Quantity <span class="text-danger">*</span></label><input type="text" name="quantity" class="form-control rounded-3" value="{{ $t->quantity }}" required maxlength="120" placeholder="e.g. 20 laptops, 1 service contract"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Procurement Method</label>
                        <select class="form-select rounded-3" name="procurement_method">
                            @foreach(['Open Bidding','National Competitive Bidding (NCB)','International Competitive Bidding (ICB)','Request for Quotations (RFQ)','Direct Procurement','Framework Agreement'] as $m)
                                <option value="{{ $m }}" @selected($t->procurement_method === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Tender Category</label>
                        <select class="form-select rounded-3" name="tender_category">
                            @foreach(['Goods','Services','Works','Consultancy Services','ICT Equipment','Office Supplies','Printing Services','Security Services','Cleaning Services','Vehicle Supply','Construction'] as $c)
                                <option value="{{ $c }}" @selected($t->tender_category === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Tender Status</label>
                        <select class="form-select rounded-3" name="tender_status">
                            @foreach(['Open','Closing Soon','Closed','Awarded','Cancelled','Postponed'] as $s)
                                <option value="{{ $s }}" @selected($t->tender_status === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Title</label>
                        <input type="text" name="title" class="form-control rounded-3" value="{{ $t->title }}" required maxlength="200">
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Issuing Organisation</label><input type="text" name="issuing_organisation" class="form-control rounded-3" value="{{ $t->issuing_organisation }}" maxlength="190"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Summary</label><textarea name="summary" class="form-control rounded-3" rows="2" maxlength="5000">{{ $t->summary }}</textarea></div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="5" required>{{ $t->description }}</textarea>
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contract Value Estimate</label><input type="text" name="contract_value_estimate" class="form-control rounded-3" value="{{ $t->contract_value_estimate }}" placeholder="e.g. ZWL 5,000,000 or USD 50,000"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Bid Security Amount</label><input type="text" name="bid_security_amount" class="form-control rounded-3" value="{{ $t->bid_security_amount }}" placeholder="e.g. ZWL 100,000"></div>
                    <div class="col-12 d-flex flex-wrap gap-3">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="bid_security_required" value="1" @checked($t->bid_security_required) id="t_bid_sec{{ $t->id }}"><label class="form-check-label small" for="t_bid_sec{{ $t->id }}">Bid Security Required</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pre_bid_meeting_mandatory" value="1" @checked($t->pre_bid_meeting_mandatory) id="t_bid_mand{{ $t->id }}"><label class="form-check-label small" for="t_bid_mand{{ $t->id }}">Pre-bid Meeting Mandatory</label></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold small">Preferred Bidder Categories</label><br>
                        @foreach(['Registered with PRAZ','Youth-Owned','Women-Owned','Persons with Disabilities','Locally Based','International'] as $cat)
                            @php $selected = is_array($t->preferred_bidder_categories) ? in_array($cat, $t->preferred_bidder_categories) : false; @endphp
                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_bidder_categories[]" value="{{ $cat }}" @checked($selected)> <label class="form-check-label small">{{ $cat }}</label></div>
                        @endforeach
                    </div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Closing Date <span class="text-danger">*</span></label><input type="date" name="closing_at" class="form-control rounded-3" value="{{ $t->closing_at?->format('Y-m-d') }}" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Opening Date &amp; Time</label><input type="datetime-local" name="opening_at" class="form-control rounded-3" value="{{ optional($t->opening_at)->format('Y-m-d\TH:i') }}"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Opening Venue / Link</label><input type="text" name="opening_venue" class="form-control rounded-3" value="{{ $t->opening_venue }}" placeholder="e.g. ZMC Boardroom, 2nd Floor, ZB Life Towers, Harare"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Submission Address / Method</label><textarea name="submission_method" class="form-control rounded-3" rows="3">{{ $t->submission_method }}</textarea></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Submission Address</label><input type="text" name="submission_address" class="form-control rounded-3" value="{{ $t->submission_address }}" placeholder="Tender Box, Zimbabwe Media Commission, 1st Floor, ZB Life Towers..."></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Name</label><input type="text" name="contact_name" class="form-control rounded-3" value="{{ $t->contact_name }}" placeholder="Tawanda Ncube"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Title</label><input type="text" name="contact_title" class="form-control rounded-3" value="{{ $t->contact_title }}" placeholder="Procurement Officer"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Email</label><input type="email" name="contact_email" class="form-control rounded-3" value="{{ $t->contact_email }}" placeholder="procurement@example.com"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Contact Phone</label><input type="text" name="contact_phone" class="form-control rounded-3" value="{{ $t->contact_phone }}" placeholder="+263 242 123456"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">Questions Deadline</label><input type="date" name="questions_deadline" class="form-control rounded-3" value="{{ $t->questions_deadline?->format('Y-m-d') }}"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold small">PRAZ EGP Tender Page <span class="text-danger">*</span></label><input type="url" name="bidding_documents_link" class="form-control rounded-3" value="{{ $t->bidding_documents_link }}" required placeholder="https://egp.praz.org.zw/..."></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Evaluation Criteria</label><textarea name="evaluation_criteria" class="form-control rounded-3" rows="3">{{ $t->evaluation_criteria }}</textarea></div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Image</label>
                        <input type="file" name="image" class="form-control rounded-3" accept="image/jpeg,image/png,image/webp">
                        @if($t->image_path)<div class="small text-muted mt-1"><i class="ri-image-line me-1"></i><a href="{{ asset('storage/'.$t->image_path) }}" target="_blank">Current image</a></div>@endif
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Attachment</label>
                        <input type="file" name="attachment" class="form-control rounded-3" accept=".pdf,.doc,.docx,.xls,.xlsx">
                        @if($t->attachment_path)<div class="small text-muted mt-1"><i class="ri-attachment-line me-1"></i><a href="{{ asset('storage/'.$t->attachment_path) }}" target="_blank">Current file</a></div>@endif
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Language</label>
                        <select class="form-select rounded-3" name="language">
                            @foreach(['English','English & Shona','English & Ndebele'] as $lang)
                                <option value="{{ $lang }}" @selected($t->language === $lang)>{{ $lang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" @checked($t->is_published) id="tender_pub{{ $t->id }}"><label class="form-check-label small" for="tender_pub{{ $t->id }}">Published</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Update</button></div>
        </form>
    </div></div>
</div>
@endforeach

@foreach($vacancies as $v)
<div class="modal fade" id="editVacancy{{ $v->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content rounded-4 border-0 shadow">
        <form action="{{ route('staff.pr.vacancies.update', $v) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
            <div class="modal-header bg-white border-0 p-4 pb-2"><h5 class="modal-title fw-bold">Edit Vacancy #{{ $v->id }}</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Position Title</label><input type="text" name="title" class="form-control rounded-3" value="{{ $v->title }}" required maxlength="200"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Vacancy Reference</label><input type="text" name="vacancy_reference" class="form-control rounded-3" value="{{ $v->vacancy_reference }}" placeholder="e.g. ZMC/HR/{{ now()->year }}/001"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Department / Division</label>
                        <select class="form-select rounded-3" name="department">
                            @foreach(['Human Resources','Finance','Legal','Accreditation','Complaints','Monitoring & Research','Corporate Affairs','ICT'] as $d)
                                <option value="{{ $d }}" @selected($v->department === $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Job Grade / Level</label>
                        <select class="form-select rounded-3" name="job_grade">
                            @foreach(['Executive','Senior Management','Middle Management','Professional','Technical','Administrative','Intern','Trainee'] as $g)
                                <option value="{{ $g }}" @selected($v->job_grade === $g)>{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Employment Type</label>
                        <select class="form-select rounded-3" name="employment_type">
                            @foreach(['Full-time','Part-time','Fixed-term Contract','Temporary','Internship','Secondment','Consultancy'] as $et)
                                <option value="{{ $et }}" @selected($v->employment_type === $et)>{{ $et }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Contract Duration</label><input type="text" name="contract_duration" class="form-control rounded-3" value="{{ $v->contract_duration }}" placeholder="e.g. 12 months, 2 years"></div>
                    <div class="col-12"><div class="alert alert-light border small mb-0">Current status: <strong>{{ !$v->closing_at ? 'Open' : ($v->closing_at->copy()->startOfDay()->lt(now()->startOfDay()) ? 'Closed' : ($v->closing_at->copy()->startOfDay()->lte(now()->addDays(7)->startOfDay()) ? 'Closing Soon' : 'Open')) }}</strong>. Status follows the closing date.</div></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Number of Positions</label><input type="number" name="number_of_positions" class="form-control rounded-3" value="{{ $v->number_of_positions ?? 1 }}" min="1"></div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_body_' . $v->id, 'name' => 'body', 'label' => 'Description', 'required' => true, 'value' => $v->body])</div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_requirements_' . $v->id, 'name' => 'qualifications_required', 'label' => 'Requirements', 'value' => $v->qualifications_required])</div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_experience_' . $v->id, 'name' => 'experience_required', 'label' => 'Relevant Experience', 'value' => $v->experience_required])</div>
                    <div class="col-12">@include('staff.pr.partials.rich-text-editor', ['id' => 'vacancy_skills_' . $v->id, 'name' => 'skills_competencies', 'label' => 'Skills', 'value' => $v->skills_competencies])</div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Duty Station / Location</label><input type="text" name="duty_station" class="form-control rounded-3" value="{{ $v->duty_station }}" placeholder="e.g. Harare (with possible travel to Bulawayo, Mutare, Masvingo)"></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">How to Apply: Email Address <span class="text-danger">*</span></label><input type="email" name="submission_email" class="form-control rounded-3" value="{{ $v->submission_email }}" placeholder="hr@example.com" required></div>
                    <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Closing Date</label><input type="date" name="closing_at" class="form-control rounded-3" value="{{ $v->closing_at?->format('Y-m-d') }}"></div>
                    <div class="col-12"><label class="form-label fw-semibold small">Preferred Applicant Categories</label><br>
                        @foreach(['Persons with Disabilities','Women','Youth (under 35)','Ex-combatants','Rural candidates','No preference'] as $cat)
                            @php $selected = is_array($v->preferred_applicant_categories) ? in_array($cat, $v->preferred_applicant_categories) : false; @endphp
                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="preferred_applicant_categories[]" value="{{ $cat }}" @checked($selected)> <label class="form-check-label small">{{ $cat }}</label></div>
                        @endforeach
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Image</label>
                        <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                        @if($v->image_path)<div class="small text-muted mt-1"><i class="ri-image-line me-1"></i><a href="{{ asset('storage/'.$v->image_path) }}" target="_blank">View current</a></div>@endif
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small">Replace Attachment</label>
                        <input type="file" name="attachment" class="form-control rounded-3" accept=".pdf,.doc,.docx,.xls,.xlsx">
                        @if($v->attachment_path)<div class="small text-muted mt-1"><i class="ri-attachment-line me-1"></i><a href="{{ asset('storage/'.$v->attachment_path) }}" target="_blank">Current file</a></div>@endif
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" @checked($v->is_published) id="vac_pub{{ $v->id }}"><label class="form-check-label small" for="vac_pub{{ $v->id }}">Published</label></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-0 p-4 pt-2"><button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Update</button></div>
        </form>
    </div></div>
</div>
@endforeach

{{-- ===================== CREATE DOWNLOAD MODAL ===================== --}}
<div class="modal fade" id="createDownload" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('staff.pr.downloads.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-white border-0 p-4 pb-2">
                    <h5 class="modal-title fw-bold"><i class="ri-download-cloud-2-line me-2"></i>Upload Document</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-3" required maxlength="200">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="3"></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Category <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="category" required>
                                <option value="">Select category...</option>
                                <option value="registration_accreditation_forms">Registration & Accreditation Forms</option>
                                <option value="foia_forms">Freedom of Information Act Forms</option>
                                <option value="organizational_documents">Organizational Documents</option>
                                <option value="policies_guidelines">Policies & Guidelines</option>
                                <option value="legislation">Legislation</option>
                                <option value="statutory_instruments">Statutory Instruments</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control rounded-3" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_published" value="1" checked id="d_pub_new">
                                <label class="form-check-label small" for="d_pub_new">Publish immediately</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 p-4 pt-2">
                    <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button>
                    <button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================== EDIT DOWNLOAD MODALS ===================== --}}
@if(isset($downloads))
@foreach($downloads as $d)
<div class="modal fade" id="editDownload{{ $d->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('staff.pr.downloads.update', $d) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header bg-white border-0 p-4 pb-2">
                    <h5 class="modal-title fw-bold"><i class="ri-download-cloud-2-line me-2" style="color:#2563eb;"></i>Edit Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-semibold small">Title</label><input class="form-control rounded-3" name="title" value="{{ $d->title }}" required></div>
                        <div class="col-12"><label class="form-label fw-semibold small">Description</label><textarea class="form-control rounded-3" rows="3" name="description">{{ $d->description }}</textarea></div>
                        <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Category</label>
                            <select class="form-select rounded-3" name="category" required>
                                <option value="registration_accreditation_forms" {{ $d->category == 'registration_accreditation_forms' ? 'selected' : '' }}>Registration & Accreditation Forms</option>
                                <option value="foia_forms" {{ $d->category == 'foia_forms' ? 'selected' : '' }}>Freedom of Information Act Forms</option>
                                <option value="organizational_documents" {{ $d->category == 'organizational_documents' ? 'selected' : '' }}>Organizational Documents</option>
                                <option value="policies_guidelines" {{ $d->category == 'policies_guidelines' ? 'selected' : '' }}>Policies & Guidelines</option>
                                <option value="legislation" {{ $d->category == 'legislation' ? 'selected' : '' }}>Legislation</option>
                                <option value="statutory_instruments" {{ $d->category == 'statutory_instruments' ? 'selected' : '' }}>Statutory Instruments</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6"><label class="form-label fw-semibold small">Replace File (optional)</label><input class="form-control rounded-3" type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
                            @if($d->file_path) <div class="small text-muted mt-1">Current: <a href="{{ asset('storage/'.$d->file_path) }}" target="_blank">{{ $d->file_original_name }}</a></div> @endif
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_published" value="1" {{ $d->is_published ? 'checked' : '' }} id="d_pub_edit_{{ $d->id }}"><label class="form-check-label small" for="d_pub_edit_{{ $d->id }}">Publish to website</label></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 p-4 pt-2">
                    <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancel</button>
                    <button class="btn btn-dark rounded-pill px-4 fw-bold" type="submit"><i class="ri-save-line me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif


@push('scripts')
<style>
.pr-rich-content { min-height: 150px; background: #fff; }
.pr-rich-content:focus { border-color: #94a3b8; box-shadow: 0 0 0 .2rem rgba(148,163,184,.18); outline: 0; }
.pr-rich-content:empty:before { content: 'Write the content here…'; color: #94a3b8; }
</style>
<script>
document.querySelectorAll('#prDashTabs button[data-bs-toggle="pill"]').forEach(function(btn){
    btn.addEventListener('shown.bs.tab', function(e){
        var tab = e.target.getAttribute('data-bs-target').replace('#panel-','');
        var url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    });
});

document.querySelectorAll('[data-pr-rich-editor]').forEach(function(wrapper) {
    const editor = wrapper.querySelector('[data-editor-content]');
    const output = wrapper.querySelector('[data-editor-output]');
    if (!editor || !output) return;

    editor.innerHTML = output.value;
    let savedRange = null;
    const saveRange = () => {
        const selection = window.getSelection();
        if (selection && selection.rangeCount && editor.contains(selection.anchorNode)) {
            savedRange = selection.getRangeAt(0).cloneRange();
        }
    };
    const sync = () => { output.value = editor.innerHTML; };
    ['keyup', 'mouseup', 'focus'].forEach(eventName => editor.addEventListener(eventName, saveRange));
    editor.addEventListener('input', sync);
    editor.addEventListener('paste', function(event) {
        event.preventDefault();
        document.execCommand('insertText', false, event.clipboardData?.getData('text/plain') || '');
        sync();
    });

    wrapper.querySelectorAll('[data-editor-command]').forEach(button => {
        button.addEventListener('mousedown', event => event.preventDefault());
        button.addEventListener('click', function() {
            editor.focus();
            if (savedRange) {
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(savedRange);
            }
            document.execCommand(this.dataset.editorCommand, false, null);
            saveRange();
            sync();
        });
    });

    wrapper.querySelectorAll('[data-editor-select]').forEach(select => {
        select.addEventListener('change', function() {
            editor.focus();
            if (savedRange) {
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(savedRange);
            }
            document.execCommand(this.dataset.editorSelect, false, this.value);
            saveRange();
            sync();
        });
    });

    wrapper.querySelector('[data-editor-color]')?.addEventListener('input', function() {
        editor.focus();
        if (savedRange) {
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(savedRange);
        }
        document.execCommand('foreColor', false, this.value);
        saveRange();
        sync();
    });

    editor.closest('form')?.addEventListener('submit', sync);
});

document.querySelectorAll('[data-article-preview]').forEach(function(button) {
    button.addEventListener('click', function() {
        const form = button.closest('form');
        if (!form) return;
        const get = name => form.querySelector(`[name="${name}"]`)?.value || '';
        const editor = form.querySelector('[data-editor-content]');
        const body = editor?.innerHTML || form.querySelector('[name="body"]')?.value || '';
        const title = get('title');
        const excerpt = get('excerpt');
        const category = get('category');
        const esc = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
        const imageFiles = Array.from(form.querySelector('[name="images[]"]')?.files || []);
        const existingImages = Array.from(form.querySelectorAll('.pr-existing-image')).map(image => image.src);
        const imageMarkup = [...existingImages, ...imageFiles.map(file => URL.createObjectURL(file))]
            .map(src => `<img src="${esc(src)}" alt="" style="max-width:100%;height:auto;border-radius:12px;margin:8px 8px 8px 0">`).join('');
        const markup = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font:16px/1.7 Arial,sans-serif;color:#1e293b;max-width:850px;margin:40px auto;padding:0 24px}h1{font-size:2rem;line-height:1.2;color:#0f172a}.meta{color:#64748b;font-size:.9rem;border-bottom:1px solid #e2e8f0;padding-bottom:16px;margin-bottom:24px}.excerpt{font-size:1.15rem;color:#475569}.article-body img{max-width:100%}</style></head><body><div class="meta">${esc(category)}${category && (form.querySelector('[name="published_at"]')?.value) ? ' · ' : ''}${esc(form.querySelector('[name="published_at"]')?.value || '')}</div><h1>${esc(title || 'Untitled article')}</h1>${excerpt ? `<p class="excerpt">${esc(excerpt)}</p>` : ''}<div>${imageMarkup}</div><article class="article-body">${body}</article></body></html>`;
        const frame = document.getElementById('prArticlePreviewFrame');
        if (frame) frame.srcdoc = markup;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('prArticlePreviewModal')).show();
    });
});
</script>
@endpush
@endsection
