@extends('layouts.portal')

@php($pageLabel = $selectedPage ? ($selectedPage === 'commissioners' ? 'Commissioners' : 'Secretariat') : 'Commissioners & Secretariat')
@section('title', $pageLabel)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $pageLabel }}</h1>
            <p class="text-muted mb-0">Create page section headings and job titles, then manage profiles, photos and display order for the public website.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('staff.pr.dashboard') }}"><i class="ri-arrow-left-line me-1"></i> PR Dashboard</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="alert alert-info">
        Add section headings such as <strong>Deputy Director</strong> or <strong>Managers</strong>, and add reusable job titles used by the profile forms.
        Drag profiles between sections or within a section, then choose <strong>Save arrangement</strong>.
    </div>

    {{-- Reusable job titles --}}
    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h2 class="h5 mb-0 fw-bold">Reusable job titles</h2>
            <small class="text-muted">These appear in the “Title” dropdown when adding a profile. “All pages” titles are available on both pages.</small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('staff.pr.website-people.titles.store') }}" class="row g-2 align-items-end mb-3">
                @csrf
                <div class="col-md-4"><label class="form-label small">Title name</label><input class="form-control" name="name" maxlength="160" placeholder="e.g. Deputy Director" required></div>
                <div class="col-md-3"><label class="form-label small">Available on</label>
                    <select class="form-select" name="page_key">
                        <option value="">All pages</option>
                        @foreach($pages as $key => $title)
                            <option value="{{ $key }}">{{ $title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small">Order</label><input class="form-control" type="number" name="sort_order" min="0" max="255" value="0"></div>
                <div class="col-md-3"><button class="btn btn-primary w-100" type="submit"><i class="ri-add-line me-1"></i>Add title</button></div>
            </form>

            <div class="d-flex flex-wrap gap-2">
                @forelse($titles as $title)
                    <span class="badge text-bg-light border d-inline-flex align-items-center gap-2 py-2 px-3">
                        <span>{{ $title->name }}</span>
                        <small class="text-muted">{{ $title->page_key ? ($pages[$title->page_key] ?? $title->page_key) : 'All pages' }}</small>
                        <form method="POST" action="{{ route('staff.pr.website-people.titles.destroy', $title) }}" class="d-inline" onsubmit="return confirm('Remove this title? Profiles keep their saved text.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger p-0" type="submit" aria-label="Remove title {{ $title->name }}" title="Remove title"><i class="ri-close-line"></i></button>
                        </form>
                    </span>
                @empty
                    <span class="text-muted small">No titles yet. Add one above.</span>
                @endforelse
            </div>
        </div>
    </section>

    <div class="row g-4">
        @foreach($pages as $pageKey => $pageTitle)
            @php($pagePeople = $peopleByPage->get($pageKey, collect()))
            @php($pageSections = $sectionsByPage->get($pageKey, collect()))
            @php($pageTitles = $titles->whereNull('page_key')->merge($titles->where('page_key', $pageKey)))
            <div class="col-12">
                <section class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                        <div>
                            <h2 class="h5 mb-0 fw-bold">{{ $pageTitle }}</h2>
                            <small class="text-muted">{{ $pagePeople->count() }} profiles · {{ $pageSections->count() }} sections</small>
                        </div>
                        <a href="{{ route('api.v1.website-pages.show', $pageKey) }}" target="_blank" rel="noopener" class="small">View public API</a>
                    </div>

                    {{-- Section headings --}}
                    <div class="card-body border-bottom">
                        <h3 class="h6 fw-bold mb-3">Page sections</h3>
                        <div class="row g-2 mb-3">
                            @forelse($pageSections as $section)
                                <div class="col-12 col-xl-6">
                                    <div class="d-flex gap-2 align-items-center border rounded-3 p-2 bg-light-subtle">
                                        <form method="POST" action="{{ route('staff.pr.website-people.sections.update', $section) }}" class="row g-2 align-items-center flex-grow-1">
                                            @csrf @method('PUT')
                                            <div class="col-7"><input class="form-control form-control-sm" name="name" maxlength="160" value="{{ $section->name }}" aria-label="Section name" required></div>
                                            <div class="col-3"><input class="form-control form-control-sm" type="number" name="sort_order" min="0" max="255" value="{{ $section->sort_order }}" aria-label="Section order"></div>
                                            <div class="col-2"><button class="btn btn-sm btn-outline-primary w-100" type="submit" aria-label="Save section"><i class="ri-save-line"></i></button></div>
                                        </form>
                                        <form method="POST" action="{{ route('staff.pr.website-people.sections.destroy', $section) }}" onsubmit="return confirm('Remove the {{ $section->name }} section? Profiles will become ungrouped.')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Remove section {{ $section->name }}" title="Remove section"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted small">No sections yet. Add one below.</div>
                            @endforelse
                        </div>
                        <form method="POST" action="{{ route('staff.pr.website-people.sections.store') }}" class="row g-2 align-items-end">
                            @csrf
                            <input type="hidden" name="page_key" value="{{ $pageKey }}">
                            <div class="col-md-6"><label class="form-label small">New section heading</label><input class="form-control" name="name" maxlength="160" placeholder="e.g. Deputy Director" required></div>
                            <div class="col-md-2"><label class="form-label small">Order</label><input class="form-control" type="number" name="sort_order" min="0" max="255" placeholder="Auto"></div>
                            <div class="col-md-4"><button class="btn btn-outline-primary w-100" type="submit"><i class="ri-add-line me-1"></i>Add section</button></div>
                        </form>
                    </div>

                    {{-- Add profile --}}
                    <div class="card-body border-bottom">
                        <form method="POST" action="{{ route('staff.pr.website-people.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                            @csrf
                            <input type="hidden" name="page_key" value="{{ $pageKey }}">
                            <div class="col-md-3"><label class="form-label">Name</label><input class="form-control" name="name" maxlength="160" required></div>
                            <div class="col-md-3"><label class="form-label">Title</label>
                                <select class="form-select" name="title_id">
                                    <option value="">— Select title —</option>
                                    @foreach($pageTitles as $title)
                                        <option value="{{ $title->id }}">{{ $title->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label">Section</label>
                                <select class="form-select" name="section_id">
                                    <option value="">— Ungrouped —</option>
                                    @foreach($pageSections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3"><label class="form-label">Portrait photo</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>
                            <div class="col-md-6"><label class="form-label">Department / committee</label><input class="form-control" name="department" maxlength="160"></div>
                            <div class="col-md-6"><label class="form-label">Biography</label><textarea class="form-control" name="biography" rows="2" maxlength="5000"></textarea></div>
                            <div class="col-md-2"><label class="form-label">Display order</label><input class="form-control" type="number" name="sort_order" min="0" max="10000" value="0"></div>
                            <div class="col-md-2">
                                <input type="hidden" name="is_published" value="0">
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_published" value="1" id="publish-{{ $pageKey }}"><label class="form-check-label" for="publish-{{ $pageKey }}">Publish now</label></div>
                            </div>
                            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="ri-add-line me-1"></i>Add profile</button></div>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('staff.pr.website-people.reorder') }}" class="tier-order-form">
                        @csrf
                        <input type="hidden" name="page_key" value="{{ $pageKey }}">
                        <div class="tier-inputs"></div>
                        <div class="card-body border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="small text-muted"><i class="ri-drag-move-2-line me-1"></i>Drag a profile card to another section or a new position.</div>
                            <button class="btn btn-outline-primary save-tier-order" type="submit"><i class="ri-save-3-line me-1"></i>Save arrangement</button>
                        </div>
                    </form>

                    <div class="card-body">
                        <div class="row g-4 tier-board" data-tier-board="{{ $pageKey }}">
                            @foreach($pageSections as $section)
                                <div class="col-12">
                                    <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h3 class="h6 mb-0 fw-bold">{{ $section->name }}</h3>
                                            <span class="badge text-bg-light">{{ $pagePeople->where('section_id', $section->id)->count() }}</span>
                                        </div>
                                        <div class="tier-list row g-3" data-section="{{ $section->id }}">
                                            @forelse($pagePeople->where('section_id', $section->id) as $person)
                                                @include('staff.pr.partials.website-person-card', ['person' => $person, 'pageKey' => $pageKey, 'pageTitles' => $pageTitles, 'pageSections' => $pageSections])
                                            @empty
                                                <div class="col-12 text-center text-muted py-4">No profiles in this section yet.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @php($ungrouped = $pagePeople->whereNull('section_id'))
                            <div class="col-12">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h3 class="h6 mb-0 fw-bold">Ungrouped profiles</h3>
                                        <span class="badge text-bg-light">{{ $ungrouped->count() }}</span>
                                    </div>
                                    <div class="tier-list row g-3" data-section="">
                                        @forelse($ungrouped as $person)
                                            @include('staff.pr.partials.website-person-card', ['person' => $person, 'pageKey' => $pageKey, 'pageTitles' => $pageTitles, 'pageSections' => $pageSections])
                                        @empty
                                            <div class="col-12 text-center text-muted py-4">Drag profiles here to remove them from a section.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-tier-board]').forEach(function (board) {
    var dragged = null;
    var orderForm = board.closest('section').querySelector('.tier-order-form');
    var inputContainer = orderForm.querySelector('.tier-inputs');

    function refreshInputs() {
        inputContainer.innerHTML = '';
        var index = 0;
        board.querySelectorAll('.tier-list').forEach(function (list) {
            list.querySelectorAll('.person-drag-card').forEach(function (card) {
                var sectionId = list.dataset.section || '';
                [['id', card.dataset.personId], ['section_id', sectionId], ['sort_order', index++]].forEach(function (entry) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'items[' + (index - 1) + '][' + entry[0] + ']';
                    input.value = entry[1];
                    inputContainer.appendChild(input);
                });
            });
        });
    }

    board.querySelectorAll('.person-drag-card').forEach(function (card) {
        card.addEventListener('dragstart', function () { dragged = card; card.classList.add('opacity-50'); });
        card.addEventListener('dragend', function () { card.classList.remove('opacity-50'); dragged = null; refreshInputs(); });
    });
    board.querySelectorAll('.tier-list').forEach(function (list) {
        list.addEventListener('dragover', function (event) { event.preventDefault(); });
        list.addEventListener('drop', function (event) {
            event.preventDefault();
            if (dragged) list.appendChild(dragged);
            refreshInputs();
        });
    });
    refreshInputs();
});
</script>
@endpush
