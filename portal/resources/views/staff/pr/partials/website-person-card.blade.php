<div class="col-12 col-xl-6 person-drag-card" draggable="true" data-person-id="{{ $person->id }}">
    <form method="POST" action="{{ route('staff.pr.website-people.update', $person) }}" enctype="multipart/form-data" class="border rounded-3 p-3 bg-white h-100">
        @csrf
        @method('PUT')
        @if($person->biography && str_contains(strtolower($person->biography), 'legacy website profile'))
            <div class="alert alert-warning small py-2 px-3 mb-3">
                <i class="ri-edit-line me-1"></i>{{ $person->biography }}
            </div>
        @endif
        <div class="d-flex gap-3 mb-3">
            @if($person->image_path)
                <img src="{{ asset('storage/' . $person->image_path) }}" alt="Current portrait of {{ $person->name }}" class="rounded object-fit-cover border" style="width:72px;height:88px">
            @else
                <div class="rounded border d-flex align-items-center justify-content-center text-muted" style="width:72px;height:88px"><i class="ri-user-line fs-4"></i></div>
            @endif
            <div class="flex-grow-1">
                <label class="form-label small">Name</label><input class="form-control form-control-sm mb-2" name="name" maxlength="160" required value="{{ $person->name }}">
                <label class="form-label small">Replace photo</label><input class="form-control form-control-sm" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                @if($person->image_path)
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove-image-{{ $person->id }}">
                        <label class="form-check-label small text-danger" for="remove-image-{{ $person->id }}">Remove current photo</label>
                    </div>
                @endif
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small">Title</label>
                <select class="form-select form-select-sm" name="title_id">
                    <option value="">— No title —</option>
                    @foreach($pageTitles as $title)
                        <option value="{{ $title->id }}" @selected($person->title_id === $title->id)>{{ $title->name }}</option>
                    @endforeach
                </select>
                @if($person->position && !$person->title_id)
                    <small class="text-muted">Current text: {{ $person->position }}</small>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label small">Section</label>
                <select class="form-select form-select-sm" name="section_id">
                    <option value="">— Ungrouped —</option>
                    @foreach($pageSections as $section)
                        <option value="{{ $section->id }}" @selected($person->section_id === $section->id)>{{ $section->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6"><label class="form-label small">Department / committee</label><input class="form-control form-control-sm" name="department" maxlength="160" value="{{ $person->department }}"></div>
            <div class="col-md-6"><label class="form-label small">Display order</label><input class="form-control form-control-sm" type="number" name="sort_order" min="0" max="10000" value="{{ $person->sort_order }}"></div>
            <div class="col-12"><label class="form-label small">Biography</label><textarea class="form-control form-control-sm" name="biography" rows="2" maxlength="5000">{{ $person->biography }}</textarea></div>
            <div class="col-md-5">
                <input type="hidden" name="page_key" value="{{ $pageKey }}">
                <input type="hidden" name="tier" value="{{ $person->tier }}">
                <input type="hidden" name="is_published" value="0">
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_published" value="1" id="published-{{ $person->id }}" @checked($person->is_published)><label class="form-check-label small" for="published-{{ $person->id }}">Published</label></div>
            </div>
            <div class="col-md-7 d-flex align-items-end gap-2">
                <button class="btn btn-primary btn-sm flex-grow-1" type="submit"><i class="ri-save-line me-1"></i>Save</button>
                <button class="btn btn-outline-danger btn-sm" type="submit" form="delete-person-{{ $person->id }}" aria-label="Remove {{ $person->name }}" title="Remove profile"><i class="ri-delete-bin-line"></i></button>
            </div>
        </div>
    </form>
    <form id="delete-person-{{ $person->id }}" method="POST" action="{{ route('staff.pr.website-people.destroy', $person) }}" class="d-none" onsubmit="return confirm('Remove this profile and its photo?')">@csrf @method('DELETE')</form>
</div>
