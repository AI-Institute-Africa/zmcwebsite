@extends('layouts.portal')

@section('title', 'Downloads')
@section('page_title', 'DOWNLOADS')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Downloads</h4>
      <div class="text-muted mt-1" style="font-size:13px;">
        <i class="ri-information-line me-1"></i>
        Download your uploaded documents and any generated files associated with your applications.
      </div>
    </div>
    <a class="btn btn-secondary" href="{{ url()->previous() }}"><i class="ri-arrow-left-line me-1"></i>Back</a>
  </div>

  @if($docs->count() === 0)
    <div class="zmc-card text-center py-5">
      <i class="ri-folder-2-line" style="font-size:48px; opacity:0.3;"></i>
      <h5 class="mt-3 mb-2">No downloadable files yet</h5>
      <p class="text-muted mb-0">Your uploaded documents and generated files will appear here.</p>
    </div>
  @else
    <div class="zmc-card">
      <h6 class="fw-bold mb-3"><i class="ri-download-2-line me-2" style="color:var(--zmc-accent)"></i>Your Files</h6>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>Application</th>
              <th>Type</th>
              <th>File</th>
              <th>Uploaded</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($docs as $doc)
              <tr>
                <td><span class="badge bg-light text-dark">{{ $doc->application->reference }}</span></td>
                <td>{{ $doc->document_type }}</td>
                <td class="text-truncate" style="max-width:280px;">{{ $doc->original_name ?: basename($doc->file_path) }}</td>
                <td class="text-muted">{{ $doc->created_at?->format('d M Y H:i') }}</td>
                <td class="text-end">
                  <a class="btn btn-sm btn-primary" href="{{ route(str_starts_with(Route::currentRouteName(),'mediahouse.') ? 'mediahouse.downloads.file' : 'accreditation.downloads.file', $doc) }}">
                    <i class="ri-download-2-line me-1"></i>Download
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection
