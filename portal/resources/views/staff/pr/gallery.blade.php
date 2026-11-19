@extends('layouts.portal')

@section('title', 'Gallery')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <a href="{{ route('staff.pr.dashboard') }}" class="btn btn-sm btn-light border rounded-pill mb-3">
                <i class="ri-arrow-left-line me-1"></i> Back to Dashboard
            </a>
            <h1 class="h3 fw-bold mb-1">Gallery</h1>
            <p class="text-muted mb-0">Upload photos and videos to the public website. Group related photos by album or folder name.</p>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h2 class="h5 fw-bold mb-1">Upload to Gallery</h2>
            <p class="small text-muted mb-0">Select multiple photos to create one album. The album name groups them together on the website.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('staff.pr.gallery.store') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-2"><label class="form-label">Media type</label><select name="type" class="form-select" id="gallery-type"><option value="image">Pictures</option><option value="video">Video</option></select></div>
                <div class="col-md-4"><label class="form-label">Album / folder name</label><input name="album" class="form-control" placeholder="e.g. 2026 Media Awards"></div>
                <div class="col-md-6"><label class="form-label">Title</label><input name="title" required class="form-control" placeholder="Title shown on the website"></div>
                <div class="col-md-8"><label class="form-label">Files <span class="text-muted small">(select multiple for an album)</span></label><input name="files[]" type="file" class="form-control" accept="image/*" multiple id="gallery-files"></div>
                <div class="col-md-4"><label class="form-label">Video URL <span class="text-muted small">(optional)</span></label><input name="external_url" class="form-control" placeholder="YouTube or hosted video URL"></div>
                <div class="col-12"><label class="form-label">Description <span class="text-muted small">(optional)</span></label><textarea name="description" class="form-control" rows="2" placeholder="Short description for this album or video"></textarea></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary px-4" type="submit"><i class="ri-upload-2-line me-1"></i>Upload to Gallery</button></div>
            </form>
        </div>
    </section>

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 fw-bold mb-0">Recent uploads</h2><span class="text-muted small">{{ $media->total() }} items</span></div>
        <div class="row g-3">
            @forelse($media as $item)
                <div class="col-sm-6 col-lg-4 col-xl-3"><div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    @if($item->file_path)
                        @if($item->type === 'video')<video src="{{ asset('storage/'.$item->file_path) }}" class="w-100" style="height:160px;object-fit:cover" muted></video>
                        @else<img src="{{ asset('storage/'.$item->file_path) }}" class="card-img-top" style="height:160px;object-fit:cover" alt="{{ $item->title }}">@endif
                    @else<div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height:160px"><i class="ri-video-line fs-1"></i></div>@endif
                    <div class="card-body"><h3 class="h6 mb-1">{{ $item->title }}</h3><div class="small text-muted">{{ ucfirst($item->type) }}@if($item->category) · {{ $item->category }}@endif</div></div>
                </div></div>
            @empty
                <div class="col-12 text-center text-muted p-5 bg-white rounded-4 shadow-sm">No gallery items yet.</div>
            @endforelse
        </div>
        @if($media->hasPages())<div class="mt-4">{{ $media->links() }}</div>@endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    const galleryType = document.getElementById('gallery-type');
    const galleryFiles = document.getElementById('gallery-files');
    if (galleryType && galleryFiles) galleryType.addEventListener('change', function () { galleryFiles.multiple = this.value === 'image'; galleryFiles.accept = this.value === 'image' ? 'image/*' : 'video/*'; });
</script>
@endpush
