@extends('layouts.portal')
@section('title', $template->name . ' — Design Studio')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:var(--font-primary);color:var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:var(--font-size-2xl);color:#1e293b;">
        {{ $template->name }}
      </h4>
      <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
        <span class="badge {{ $template->statusBadgeClass() }} px-3 py-2">{{ $template->statusLabel() }}</span>
        <span class="badge bg-light text-dark border">{{ ucfirst($template->type) }}</span>
        <span class="badge bg-light text-dark border">{{ $template->year }}</span>
        @if($template->category_code)
          <span class="badge bg-light text-dark border">{{ $template->category_code }}</span>
        @endif
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @if($template->isEditable() && ($template->created_by === auth()->id() || auth()->user()->hasRole('super_admin')))
        <a href="{{ route('staff.design-studio.edit', $template->id) }}" class="btn btn-dark btn-sm fw-bold">
          <i class="ri-edit-2-line me-1"></i> Open Designer
        </a>
      @endif
      @if($template->isEditable() && ($template->created_by === auth()->id() || auth()->user()->hasRole('super_admin')))
        <form method="POST" action="{{ route('staff.design-studio.destroy', $template->id) }}" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm"
                  onclick="return confirm('Delete this template permanently? This action cannot be undone.')">
            <i class="ri-delete-bin-line me-1"></i> Delete
          </button>
        </form>
      @endif
      <form method="POST" action="{{ route('staff.design-studio.duplicate', $template->id) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm">
          <i class="ri-file-copy-line me-1"></i> Duplicate
        </button>
      </form>
      @if(auth()->user()->hasRole('super_admin'))
        @if($template->status === 'pending_approval')
          <a href="{{ route('staff.design-studio.approval.queue') }}" class="btn btn-outline-warning btn-sm">
            <i class="ri-shield-check-line me-1"></i> Review in Queue
          </a>
        @elseif($template->status === 'approved')
          <form method="POST" action="{{ route('staff.design-studio.approval.archive', $template->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm"
                    onclick="return confirm('Archive this template?')">
              <i class="ri-archive-line me-1"></i> Archive
            </button>
          </form>
        @elseif($template->status === 'archived')
          <form method="POST" action="{{ route('staff.design-studio.approval.restore', $template->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-success btn-sm">
              <i class="ri-restart-line me-1"></i> Restore
            </button>
          </form>
        @endif
      @endif
      <a href="{{ route('staff.design-studio.index') }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-arrow-left-line me-1"></i> Back
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
  @endif

  {{-- Rejection notice --}}
  @if($template->status === 'rejected')
  <div class="alert alert-danger mb-4">
    <strong><i class="ri-close-circle-line me-1"></i>Rejected</strong>
    by {{ $template->rejecter?->name ?? 'Super Admin' }}
    on {{ $template->rejected_at?->format('d M Y H:i') }}
    <div class="mt-2 p-2 bg-white rounded border small">{{ $template->rejection_reason }}</div>
    @if($template->created_by === auth()->id())
      <div class="mt-2">
        <a href="{{ route('staff.design-studio.edit', $template->id) }}" class="btn btn-sm btn-dark">
          <i class="ri-edit-2-line me-1"></i> Revise Template
        </a>
      </div>
    @endif
  </div>
  @endif

  <div class="row g-4">

    {{-- Details --}}
    <div class="col-12 col-lg-5">
      <div class="zmc-card mb-4">
        <h6 class="fw-bold mb-3"><i class="ri-file-info-line me-2" style="color:var(--zmc-accent-dark)"></i>Template Details</h6>
        <table class="table table-sm small mb-0">
          <tbody>
            <tr><td class="text-muted fw-bold" style="width:40%;">Type</td><td>{{ ucfirst($template->type) }}</td></tr>
            <tr><td class="text-muted fw-bold">For</td><td>{{ $template->templateForLabel() }}</td></tr>
            <tr><td class="text-muted fw-bold">Year</td><td>{{ $template->year }}</td></tr>
            <tr><td class="text-muted fw-bold">Category</td><td>{{ $template->category_code ?? '—' }}</td></tr>
            <tr><td class="text-muted fw-bold">Orientation</td><td>{{ ucfirst($template->orientation) }}</td></tr>
            <tr><td class="text-muted fw-bold">Size</td><td>{{ $template->width ? $template->width.'×'.$template->height.' mm' : '—' }}</td></tr>
            <tr><td class="text-muted fw-bold">Designed By</td><td>{{ $template->creator?->name ?? '—' }}</td></tr>
            <tr><td class="text-muted fw-bold">Created</td><td>{{ $template->created_at->format('d M Y H:i') }}</td></tr>
            <tr><td class="text-muted fw-bold">Last Updated</td><td>{{ $template->updated_at->format('d M Y H:i') }}</td></tr>
            @if($template->submitted_at)
            <tr><td class="text-muted fw-bold">Submitted</td><td>{{ $template->submitted_at->format('d M Y H:i') }}</td></tr>
            @endif
            @if($template->approved_at)
            <tr><td class="text-muted fw-bold">Approved By</td><td>{{ $template->approver?->name ?? '—' }}</td></tr>
            <tr><td class="text-muted fw-bold">Approved At</td><td>{{ $template->approved_at->format('d M Y H:i') }}</td></tr>
            @endif
          </tbody>
        </table>
      </div>

      {{-- Background preview --}}
      @if($template->backgroundUrl())
      <div class="zmc-card mb-4">
        <h6 class="fw-bold mb-3"><i class="ri-image-line me-2" style="color:var(--zmc-accent-dark)"></i>Background</h6>
        <img src="{{ $template->backgroundUrl() }}" class="img-fluid rounded border" alt="Background">
      </div>
      @endif
    </div>

    {{-- Canvas preview + versions --}}
    <div class="col-12 col-lg-7">

      {{-- Canvas preview --}}
      @if($template->layout_config)
      <div class="zmc-card mb-4">
        <h6 class="fw-bold mb-3"><i class="ri-eye-line me-2" style="color:var(--zmc-accent-dark)"></i>Canvas Preview</h6>
        @php
          $cw = $template->type === 'card' ? 340 : 595;
          $ch = $template->type === 'card' ? 215 : 842;
        @endphp
        <div style="background:#d1d5db;padding:12px;border-radius:8px;text-align:center;">
          <canvas id="previewCanvas" width="{{ $cw }}" height="{{ $ch }}"
                  style="max-width:100%;box-shadow:0 4px 16px rgba(0,0,0,.2);"></canvas>
        </div>
      </div>
      @endif

      {{-- Version history --}}
      @if($template->versions->count())
      <div class="zmc-card">
        <h6 class="fw-bold mb-3"><i class="ri-history-line me-2" style="color:var(--zmc-accent-dark)"></i>Version History</h6>
        <div class="table-responsive">
          <table class="table table-sm small mb-0">
            <thead class="bg-light">
              <tr>
                <th>Version</th>
                <th>Changed By</th>
                <th>Reason</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @foreach($template->versions as $v)
              <tr>
                <td class="fw-bold">v{{ $v->version_number }}</td>
                <td>{{ $v->changedBy?->name ?? '—' }}</td>
                <td>{{ $v->change_reason ?? '—' }}</td>
                <td>{{ $v->created_at->format('d M Y H:i') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif

    </div>
  </div>
</div>

@if($template->layout_config)
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
(function () {
  const canvas = new fabric.StaticCanvas('previewCanvas', {
    width: {{ $cw }}, height: {{ $ch }}, backgroundColor: '#ffffff',
  });
  const saved = @json($template->layout_config);
  const bgUrl = @json($template->backgroundUrl());

  function loadLayout() {
    if (saved && saved.objects) {
      canvas.loadFromJSON(saved, () => canvas.renderAll());
    }
  }

  if (bgUrl) {
    fabric.Image.fromURL(bgUrl, function (img) {
      img.set({ left: 0, top: 0, selectable: false });
      img.scaleToWidth({{ $cw }});
      img.scaleToHeight({{ $ch }});
      canvas.setBackgroundImage(img, function () { loadLayout(); });
    }, { crossOrigin: 'anonymous' });
  } else {
    loadLayout();
  }
})();
</script>
@endif
@endsection
