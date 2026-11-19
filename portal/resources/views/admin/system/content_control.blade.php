@extends('layouts.portal')

@section('title', 'Content System Control')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">Content System Control</h4>
      <div class="text-muted" style="font-size:13px;">Enable/disable modules, manage categories, and set moderation rules.</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.content.index') }}">Content</a>
      <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.news.index') }}">News</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.content.control') }}">
    @csrf
    <div class="row g-3">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold">Module Access</div>
          <div class="card-body">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="modules[notices]" value="1" {{ !empty($cfg['modules']['notices']) ? 'checked' : '' }}>
              <label class="form-check-label">Notices</label>
            </div>
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="modules[events]" value="1" {{ !empty($cfg['modules']['events']) ? 'checked' : '' }}>
              <label class="form-check-label">Events</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="modules[news]" value="1" {{ !empty($cfg['modules']['news']) ? 'checked' : '' }}>
              <label class="form-check-label">News</label>
            </div>
            <div class="small text-muted mt-2">If a module is disabled, staff pages remain accessible, but publishing can be blocked in the relevant controllers (optional).</div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold">Category Management</div>
          <div class="card-body">
            <label class="form-label small text-muted">One category per line</label>
            <textarea class="form-control" rows="10" name="categories">{{ implode("\n", $cfg['categories'] ?? []) }}</textarea>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold">Moderation Rules</div>
          <div class="card-body">
            <textarea class="form-control" rows="10" name="moderation_rules" placeholder="Define moderation rules (e.g., prohibited content, review requirements, approval chains).">{{ $cfg['moderation_rules'] ?? '' }}</textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
      <button class="btn btn-primary">Save Content Settings</button>
    </div>
  </form>
</div>
@endsection
