@extends('layouts.portal')

@section('title', 'Templates & Documents')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">Templates & Documents</h4>
      <div class="text-muted" style="font-size:13px;">Upload templates and manage active versions (basic version control).</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.templates.index') }}">Catalogue</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Upload new version</div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.templates.config') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-2">
              <label class="form-label small text-muted">Template type</label>
              <select class="form-select form-select-sm" name="type" required>
                @foreach(($cfg['items'] ?? []) as $key => $item)
                  <option value="{{ $key }}">{{ $item['label'] ?? strtoupper($key) }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label small text-muted">Label (optional)</label>
              <input class="form-control form-control-sm" name="label" placeholder="e.g. 2026 Card Template" />
            </div>
            <div class="mb-3">
              <label class="form-label small text-muted">File</label>
              <input class="form-control form-control-sm" type="file" name="file" required />
              <div class="small text-muted mt-1">Max 5MB.</div>
            </div>
            <button class="btn btn-primary btn-sm">Upload & Activate</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Current templates</div>
        <div class="card-body">
          <div class="accordion" id="tplAcc">
            @foreach(($cfg['items'] ?? []) as $key => $item)
              @php($active = $item['active_version'] ?? null)
              <div class="accordion-item">
                <h2 class="accordion-header" id="h{{ $key }}">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#c{{ $key }}">
                    {{ $item['label'] ?? strtoupper($key) }}
                    <span class="badge bg-light text-dark ms-2">Active: {{ $active ?: '—' }}</span>
                  </button>
                </h2>
                <div id="c{{ $key }}" class="accordion-collapse collapse" data-bs-parent="#tplAcc">
                  <div class="accordion-body">
                    @if(empty($item['versions']))
                      <div class="text-muted">No versions uploaded.</div>
                    @else
                      <div class="table-responsive">
                        <table class="table table-sm">
                          <thead class="table-light"><tr><th>Version</th><th>Label</th><th>Uploaded</th><th>Path</th></tr></thead>
                          <tbody>
                            @foreach(array_reverse($item['versions']) as $v)
                              <tr>
                                <td class="text-nowrap"><code>{{ $v['version'] ?? '' }}</code></td>
                                <td>{{ $v['label'] ?? '' }}</td>
                                <td class="text-nowrap">{{ $v['uploaded_at'] ?? '' }}</td>
                                <td style="font-size:12px;">{{ $v['path'] ?? '' }}</td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
