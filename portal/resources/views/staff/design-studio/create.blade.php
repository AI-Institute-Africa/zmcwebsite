@extends('layouts.portal')
@section('title', 'New Template — Design Studio')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:var(--font-primary);color:var(--zmc-text-dark);">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:var(--font-size-2xl);color:#1e293b;">
        <i class="ri-add-circle-line me-2" style="color:var(--zmc-accent)"></i>New Template
      </h1>
      <div class="text-muted mt-1">Define the template details. You will design the layout on the next screen.</div>
    </div>
    <a href="{{ route('staff.design-studio.index') }}" class="btn btn-white border shadow-sm btn-sm px-3">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>

  @if($errors->any())
    <div class="alert alert-danger mb-3">
      <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-lg-7">
      <div class="zmc-card">
        <h2 class="fw-bold mb-4"><i class="ri-file-settings-line me-2" style="color:var(--zmc-accent-dark)"></i>Template Details</h2>

        <form method="POST" action="{{ route('staff.design-studio.store') }}" enctype="multipart/form-data">
          @csrf

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-bold text-muted">Template Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control zmc-input" value="{{ old('name') }}"
                     placeholder="e.g. {{ now()->year }} Accreditation Card — Local" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Template Type <span class="text-danger">*</span></label>
              <select name="type" class="form-select zmc-input" required id="typeSelect">
                <option value="">— Select —</option>
                <option value="card"        {{ old('type') === 'card'        ? 'selected' : '' }}>Accreditation Card</option>
                <option value="certificate" {{ old('type') === 'certificate' ? 'selected' : '' }}>Certificate / Licence</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Template For <span class="text-danger">*</span></label>
              <select name="template_for" class="form-select zmc-input" required>
                <option value="">— Select —</option>
                <option value="accreditation_local" {{ old('template_for') === 'accreditation_local' ? 'selected' : '' }}>Accreditation (Local)</option>
                <option value="accreditation_foreign" {{ old('template_for') === 'accreditation_foreign' ? 'selected' : '' }}>Accreditation (Foreign)</option>
                <option value="registration"  {{ old('template_for') === 'registration'  ? 'selected' : '' }}>Registration</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-bold text-muted">Year <span class="text-danger">*</span></label>
              <input type="text" name="year" class="form-control zmc-input" value="{{ old('year', now()->year) }}"
                     maxlength="4" placeholder="{{ now()->year }}" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-bold text-muted">Mode <span class="text-danger">*</span></label>
              <select name="design_mode" class="form-select zmc-input" id="designModeSelect" required>
                <option value="scratch">Design from scratch</option>
                <option value="upload">Upload existing design</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-bold text-muted">Orientation</label>
              <select name="orientation" class="form-select zmc-input">
                <option value="portrait"  {{ old('orientation', 'portrait') === 'portrait'  ? 'selected' : '' }}>Portrait</option>
                <option value="landscape" {{ old('orientation') === 'landscape' ? 'selected' : '' }}>Landscape</option>
              </select>
            </div>

            <div class="col-md-6 dimension-field">
              <label class="form-label small fw-bold text-muted">Width (mm)</label>
              <input type="number" name="width" class="form-control zmc-input" value="{{ old('width') }}"
                     placeholder="e.g. 85.6 for card, 210 for A4" step="0.1">
            </div>

            <div class="col-md-6 dimension-field">
              <label class="form-label small fw-bold text-muted">Height (mm)</label>
              <input type="number" name="height" class="form-control zmc-input" value="{{ old('height') }}"
                     placeholder="e.g. 54 for card, 297 for A4" step="0.1">
            </div>

            <div class="col-12">
              <label class="form-label small fw-bold text-muted">Background Image</label>
              <input type="file" name="background" class="form-control zmc-input" accept="image/jpeg,image/png">
              <div class="form-text">JPG or PNG, max 8 MB. Upload your card/certificate background design.</div>
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-dark fw-bold px-4">
              <i class="ri-arrow-right-line me-1"></i> Create &amp; Open Designer
            </button>
            <a href="{{ route('staff.design-studio.index') }}" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>

    <div class="col-12 col-lg-5">
      <div class="zmc-card border-0" style="background:#f8fafc;">
        <h3 class="fw-bold mb-3"><i class="ri-information-line me-2" style="color:var(--zmc-accent)"></i>Guidelines</h3>
        <ul class="small text-muted mb-0" style="line-height:1.8;">
          <li>Card templates are for accreditation cards (CR80 / ID-1 size: 85.6 × 54 mm).</li>
          <li>Certificate templates are for A4 registration certificates (210 × 297 mm).</li>
          <li>Upload a background image to use as the card/certificate base design.</li>
          <li>After creating, you will be taken to the designer to place fields.</li>
          <li>Place the fields in the designer, then use <strong>Save &amp; Use in Production</strong>.</li>
          <li>Saving makes this design active for the selected production type.</li>
          <li>Previous active designs are archived automatically for audit history.</li>
        </ul>
      </div>
    </div>
  </div>

</div>

<script>
const designModeSelect = document.getElementById('designModeSelect');
const dimensionFields = Array.from(document.querySelectorAll('.dimension-field'));

function syncDesignModeFields() {
  const isUploadedDesign = designModeSelect?.value === 'upload';
  dimensionFields.forEach((field) => {
    field.classList.toggle('d-none', isUploadedDesign);
    field.querySelectorAll('input').forEach((input) => {
      input.disabled = isUploadedDesign;
    });
  });
}

designModeSelect?.addEventListener('change', syncDesignModeFields);
syncDesignModeFields();

// Auto-set sensible defaults when type changes
document.getElementById('typeSelect')?.addEventListener('change', function () {
  const w = document.querySelector('[name=width]');
  const h = document.querySelector('[name=height]');
  if (this.value === 'card') {
    w.placeholder = '85.6 (CR80 card width)';
    h.placeholder = '54 (CR80 card height)';
  } else {
    w.placeholder = '210 (A4 width)';
    h.placeholder = '297 (A4 height)';
  }
});
</script>
@endsection
