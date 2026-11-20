@extends('layouts.portal')
@section('title', 'Template Approval Queue — Design Studio')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:var(--font-primary);color:var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:var(--font-size-2xl);color:#1e293b;">
        <i class="ri-shield-check-line me-2" style="color:var(--zmc-accent)"></i>Template Approval Queue
      </h1>
      <div class="text-muted mt-1">Review and approve or reject templates submitted by designers.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.design-studio.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
        <i class="ri-arrow-left-line me-1"></i> Design Studio
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
  @endif

  {{-- Filter --}}
  <div class="zmc-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-bold text-muted">Type</label>
        <select name="type" class="form-select form-select-sm">
          <option value="">All Types</option>
          <option value="card"        {{ request('type') === 'card'        ? 'selected' : '' }}>Card</option>
          <option value="certificate" {{ request('type') === 'certificate' ? 'selected' : '' }}>Certificate</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2 align-items-end">
        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-filter-line me-1"></i>Filter</button>
        <a href="{{ route('staff.design-studio.approval.queue') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
      </div>
    </form>
  </div>

  @forelse($templates as $tpl)
  <div class="zmc-card mb-3 border-0 shadow-sm">
    <div class="row g-3 align-items-start">

      {{-- Preview thumbnail --}}
      <div class="col-12 col-md-3">
        @if($tpl->backgroundUrl())
          <img src="{{ $tpl->backgroundUrl() }}" class="img-fluid rounded border" alt="Background"
               style="max-height:140px;object-fit:cover;width:100%;">
        @else
          <div class="rounded border d-flex align-items-center justify-content-center text-muted"
               style="height:120px;background:#f8fafc;font-size:12px;">
            <i class="ri-image-line me-1"></i> No background
          </div>
        @endif
        @if($tpl->layout_config)
          <div class="mt-2">
            <canvas id="thumb_{{ $tpl->id }}" width="200" height="{{ $tpl->type === 'card' ? 126 : 283 }}"
                    style="width:100%;border:1px solid #e2e8f0;border-radius:4px;"></canvas>
          </div>
        @endif
      </div>

      {{-- Details --}}
      <div class="col-12 col-md-5">
        <div class="fw-bold" style="font-size:15px;">{{ $tpl->name }}</div>
        <div class="mt-1 d-flex flex-wrap gap-1">
          <span class="badge bg-light text-dark border">{{ ucfirst($tpl->type) }}</span>
          <span class="badge bg-light text-dark border">{{ $tpl->templateForLabel() }}</span>
          <span class="badge bg-light text-dark border">{{ $tpl->year }}</span>
          @if($tpl->category_code)
            <span class="badge bg-light text-dark border">{{ $tpl->category_code }}</span>
          @endif
        </div>
        <div class="mt-2 small text-muted">
          <i class="ri-user-line me-1"></i>
          <strong>Designed by:</strong> {{ $tpl->creator?->name ?? '—' }}
          @if($tpl->creator)
            <span class="ms-1">({{ implode(', ', $tpl->creator->getRoleNames()->map(fn($r) => ucwords(str_replace('_',' ',$r)))->toArray()) }})</span>
          @endif
        </div>
        <div class="small text-muted">
          <i class="ri-calendar-line me-1"></i>
          <strong>Submitted:</strong> {{ $tpl->submitted_at?->format('d M Y H:i') ?? '—' }}
        </div>
        @if($tpl->layout_config)
          <div class="mt-2 small text-muted">
            <i class="ri-layout-line me-1"></i>
            {{ count($tpl->layout_config['objects'] ?? []) }} field(s) on canvas
          </div>
        @endif
        <div class="mt-3">
          <a href="{{ route('staff.design-studio.show', $tpl->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
            <i class="ri-eye-line me-1"></i> Full Preview
          </a>
        </div>
      </div>

      {{-- Actions --}}
      <div class="col-12 col-md-4">
        <div class="p-3 rounded border" style="background:#f8fafc;">
          <div class="fw-bold small mb-3">Decision</div>

          {{-- Approve --}}
          <form method="POST" action="{{ route('staff.design-studio.approval.approve', $tpl->id) }}" class="mb-2">
            @csrf
            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold"
                    onclick="return confirm('Approve this template? It will become active immediately.')">
              <i class="ri-checkbox-circle-line me-1"></i> Approve Template
            </button>
          </form>

          {{-- Reject --}}
          <button type="button" class="btn btn-danger btn-sm w-100 fw-bold"
                  data-bs-toggle="collapse" data-bs-target="#rejectForm_{{ $tpl->id }}">
            <i class="ri-close-circle-line me-1"></i> Reject with Reason
          </button>

          <div class="collapse mt-2" id="rejectForm_{{ $tpl->id }}">
            <form method="POST" action="{{ route('staff.design-studio.approval.reject', $tpl->id) }}">
              @csrf
              <label class="form-label small fw-bold text-muted">Rejection Reason <span class="text-danger">*</span></label>
              <textarea name="rejection_reason" class="form-control form-control-sm mb-2" rows="3" required
                        placeholder="Explain clearly what needs to be changed…"></textarea>
              <button type="submit" class="btn btn-danger btn-sm w-100">
                <i class="ri-send-plane-line me-1"></i> Send Rejection
              </button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
  @empty
  <div class="zmc-card text-center py-5">
    <i class="ri-shield-check-line" style="font-size:48px;color:#d1d5db;"></i>
    <div class="fw-bold mt-3">No templates pending approval</div>
    <div class="text-muted small mt-1">All submitted templates have been reviewed.</div>
  </div>
  @endforelse

  <div class="mt-3">{{ $templates->withQueryString()->links() }}</div>

</div>

{{-- Render canvas thumbnails --}}
@if($templates->count())
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
(function () {
  const templates = @json($templates->items()->map(fn($t) => [
    'id'     => $t->id,
    'type'   => $t->type,
    'layout' => $t->layout_config,
    'bg'     => $t->backgroundUrl(),
  ]));

  templates.forEach(function (tpl) {
    if (!tpl.layout) return;
    const el = document.getElementById('thumb_' + tpl.id);
    if (!el) return;
    const w = 200, h = tpl.type === 'card' ? 126 : 283;
    const c = new fabric.StaticCanvas(el, { width: w, height: h, backgroundColor: '#fff' });
    const scale = w / (tpl.type === 'card' ? 340 : 595);

    function load() {
      if (tpl.layout && tpl.layout.objects) {
        c.loadFromJSON(tpl.layout, function () {
          c.getObjects().forEach(o => {
            o.set({ left: o.left * scale, top: o.top * scale,
                    scaleX: (o.scaleX || 1) * scale, scaleY: (o.scaleY || 1) * scale });
          });
          c.renderAll();
        });
      }
    }

    if (tpl.bg) {
      fabric.Image.fromURL(tpl.bg, function (img) {
        img.set({ left: 0, top: 0, selectable: false });
        img.scaleToWidth(w);
        img.scaleToHeight(h);
        c.setBackgroundImage(img, function () { load(); });
      }, { crossOrigin: 'anonymous' });
    } else {
      load();
    }
  });
})();
</script>
@endif
@endsection
