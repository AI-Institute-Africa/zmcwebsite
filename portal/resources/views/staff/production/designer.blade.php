@extends('layouts.portal')
@section('title', isset($template) ? 'Edit Template' : 'Template Designer')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
        <i class="ri-palette-line me-2" style="color:var(--zmc-accent)"></i>
        {{ isset($template) ? 'Edit Template: ' . $template->name : 'Template Designer' }}
      </h1>
      <div class="text-muted mt-1" style="font-size:13px;">
        Design card/certificate templates by uploading a background and placing dynamic fields.
      </div>
    </div>
    <a href="{{ route('staff.production.templates') }}" class="btn btn-outline-secondary btn-sm px-3">
      <i class="ri-arrow-left-line me-1"></i> Back to Templates
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="zmc-card shadow-sm">
        <h2 class="fw-bold mb-3"><i class="ri-settings-3-line me-1"></i> Template Settings</h2>

        <form id="templateForm"
              method="POST"
              action="{{ isset($template) ? route('staff.production.templates.update', $template) : route('staff.production.templates.store') }}"
              enctype="multipart/form-data">
          @csrf
          @if(isset($template))
            @method('PUT')
          @endif

          <div class="mb-3">
            <label class="form-label fw-bold small">Template Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $template->name ?? '') }}" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-bold small">Type</label>
              <select name="type" class="form-select" required>
                <option value="card" {{ old('type', $template->type ?? '') === 'card' ? 'selected' : '' }}>Card</option>
                <option value="certificate" {{ old('type', $template->type ?? '') === 'certificate' ? 'selected' : '' }}>Certificate</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold small">Year</label>
              <input type="text" name="year" class="form-control" maxlength="4" value="{{ old('year', $template->year ?? date('Y')) }}" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold small">Background Image (JPG/PNG)</label>
            <input type="file" name="background" class="form-control" accept="image/jpeg,image/png" id="bgUpload">
            @if(isset($template) && $template->background_path)
              <div class="mt-1 small text-muted">
                <i class="ri-image-line"></i> Current: {{ basename($template->background_path) }}
              </div>
            @endif
          </div>

          <input type="hidden" name="layout_config" id="layoutConfigInput" value="{{ old('layout_config', json_encode($template->layout_config ?? [])) }}">

          <hr>

          <h3 class="fw-bold mb-3"><i class="ri-drag-move-2-line me-1"></i> Dynamic Fields</h3>
          <p class="small text-muted mb-2">Click a field to add it to the canvas. Drag fields to position them.</p>

          <div class="d-flex flex-wrap gap-2 mb-3" id="fieldPalette">
            <button type="button" class="btn btn-sm btn-outline-primary field-add-btn" data-field="name">
              <i class="ri-user-line me-1"></i> Name
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary field-add-btn" data-field="reg_number">
              <i class="ri-hashtag me-1"></i> Reg/Accred No.
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary field-add-btn" data-field="category">
              <i class="ri-price-tag-3-line me-1"></i> Category
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary field-add-btn" data-field="expiry_date">
              <i class="ri-calendar-line me-1"></i> Expiry Date
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary field-add-btn" data-field="qr_code">
              <i class="ri-qr-code-line me-1"></i> QR Code
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary field-add-btn" data-field="photo">
              <i class="ri-camera-line me-1"></i> Photo
            </button>
          </div>

          <div id="fieldProperties" class="d-none mb-3 p-3 border rounded bg-light">
            <h4 class="fw-bold small mb-2">Field Properties <span id="propFieldName" class="badge bg-primary"></span></h4>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label small">X (px)</label>
                <input type="number" class="form-control form-control-sm" id="propX" min="0">
              </div>
              <div class="col-6">
                <label class="form-label small">Y (px)</label>
                <input type="number" class="form-control form-control-sm" id="propY" min="0">
              </div>
              <div class="col-6">
                <label class="form-label small">Width (px)</label>
                <input type="number" class="form-control form-control-sm" id="propW" min="20">
              </div>
              <div class="col-6">
                <label class="form-label small">Height (px)</label>
                <input type="number" class="form-control form-control-sm" id="propH" min="15">
              </div>
              <div class="col-6">
                <label class="form-label small">Font Size</label>
                <input type="number" class="form-control form-control-sm" id="propFontSize" min="8" max="72" value="14">
              </div>
              <div class="col-6">
                <label class="form-label small">Color</label>
                <input type="color" class="form-control form-control-sm form-control-color" id="propColor" value="#000000">
              </div>
              <div class="col-6">
                <label class="form-label small">Font</label>
                <select class="form-select form-select-sm" id="propFontFamily">
                  <option value="Arial">Arial</option>
                  <option value="Georgia">Georgia</option>
                  <option value="Times New Roman">Times New Roman</option>
                  <option value="Verdana">Verdana</option>
                  <option value="Courier New">Courier New</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small">Weight</label>
                <select class="form-select form-select-sm" id="propFontWeight">
                  <option value="400">Normal</option>
                  <option value="600">Semi-bold</option>
                  <option value="700">Bold</option>
                  <option value="900">Extra-bold</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small">Style</label>
                <select class="form-select form-select-sm" id="propFontStyle">
                  <option value="normal">Normal</option>
                  <option value="italic">Italic</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small">Alignment</label>
                <select class="form-select form-select-sm" id="propTextAlign">
                  <option value="left">Left</option>
                  <option value="center">Center</option>
                  <option value="right">Right</option>
                </select>
              </div>
              <div class="col-12">
                <label class="d-flex align-items-center gap-2 small mt-1"><input type="checkbox" id="propUnderline"> Underline text</label>
              </div>
            </div>
            <div class="mt-2 d-flex gap-2">
              <button type="button" class="btn btn-sm btn-success" id="propApply">Apply</button>
              <button type="button" class="btn btn-sm zmc-icon-btn btn-outline-danger" id="propRemove">Remove Field</button>
            </div>
          </div>

          <hr>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary fw-bold flex-fill" onclick="prepareSubmit()">
              <i class="ri-save-line me-1"></i> {{ isset($template) ? 'Update' : 'Save' }} Template
            </button>
            <button type="button" class="btn btn-outline-info fw-bold" onclick="togglePreview()">
              <i class="ri-eye-line me-1"></i> Preview
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="zmc-card shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold m-0"><i class="ri-artboard-2-line me-1"></i> Canvas</h5>
          <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-dark border" id="canvasSize">850 × 540</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearCanvas()">
              <i class="ri-delete-bin-line me-1"></i> Clear All
            </button>
          </div>
        </div>

        <div id="canvasWrapper" style="position:relative; width:850px; max-width:100%; height:540px; border:2px dashed #cbd5e1; border-radius:8px; overflow:hidden; background:#f8fafc; margin:0 auto;">
          <div id="canvasBg" style="position:absolute; top:0; left:0; width:100%; height:100%; background-size:cover; background-position:center; background-repeat:no-repeat;"></div>
          <div id="canvasFields" style="position:absolute; top:0; left:0; width:100%; height:100%;"></div>
          <div id="canvasEmpty" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); text-align:center; color:#94a3b8;">
            <i class="ri-image-add-line" style="font-size:48px;"></i>
            <div class="mt-2">Upload a background image and add fields</div>
          </div>
        </div>
      </div>

      <div class="zmc-card shadow-sm mt-4 d-none" id="previewCard">
        <h6 class="fw-bold mb-3"><i class="ri-eye-line me-1"></i> Preview with Sample Data</h6>
        <div id="previewContainer" style="position:relative; width:850px; max-width:100%; height:540px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; margin:0 auto;">
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
const SAMPLE_DATA = {
  name: 'John M. Doe',
  reg_number: 'ZMC/ACC/' + new Date().getFullYear() + '/001234',
  category: 'Print Media',
  expiry_date: new Date(new Date().getFullYear(), 11, 31).toLocaleDateString(),
  qr_code: 'QR',
  photo: 'Photo'
};

const FIELD_LABELS = {
  name: 'Name',
  reg_number: 'Reg/Accred No.',
  category: 'Category',
  expiry_date: 'Expiry Date',
  qr_code: 'QR Code',
  photo: 'Photo'
};

const FIELD_DEFAULTS = {
  name:        { x: 250, y: 180, w: 300, h: 30, fontSize: 18, color: '#000000', fontFamily: 'Arial', fontWeight: 700, fontStyle: 'normal', textAlign: 'left', underline: false },
  reg_number:  { x: 250, y: 220, w: 300, h: 25, fontSize: 14, color: '#333333', fontFamily: 'Arial', fontWeight: 600, fontStyle: 'normal', textAlign: 'left', underline: false },
  category:    { x: 250, y: 260, w: 200, h: 25, fontSize: 14, color: '#333333', fontFamily: 'Arial', fontWeight: 600, fontStyle: 'normal', textAlign: 'left', underline: false },
  expiry_date: { x: 250, y: 300, w: 200, h: 25, fontSize: 14, color: '#333333', fontFamily: 'Arial', fontWeight: 600, fontStyle: 'normal', textAlign: 'left', underline: false },
  qr_code:     { x: 680, y: 350, w: 120, h: 120, fontSize: 12, color: '#000000', fontFamily: 'Arial', fontWeight: 400, fontStyle: 'normal', textAlign: 'center', underline: false },
  photo:       { x: 50,  y: 150, w: 150, h: 180, fontSize: 12, color: '#000000', fontFamily: 'Arial', fontWeight: 400, fontStyle: 'normal', textAlign: 'center', underline: false }
};

let fields = {};
let selectedField = null;
let dragState = null;

function initFromExisting() {
  try {
    const raw = document.getElementById('layoutConfigInput').value;
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed)) {
      parsed.forEach(f => {
        if (f.key) {
          fields[f.key] = { x: f.x||0, y: f.y||0, w: f.w||100, h: f.h||30, fontSize: f.fontSize||14, color: f.color||'#000000', fontFamily: f.fontFamily||'Arial', fontWeight: f.fontWeight||600, fontStyle: f.fontStyle||'normal', textAlign: f.textAlign||'left', underline: !!f.underline };
        }
      });
    }
  } catch(e) {}
  renderFields();
}

@if(isset($template) && $template->background_path)
  document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('canvasBg').style.backgroundImage = 'url({{ asset("storage/" . $template->background_path) }})';
    document.getElementById('canvasEmpty').style.display = 'none';
  });
@endif

document.getElementById('bgUpload').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(ev) {
    document.getElementById('canvasBg').style.backgroundImage = 'url(' + ev.target.result + ')';
    document.getElementById('canvasEmpty').style.display = 'none';
  };
  reader.readAsDataURL(file);
});

document.querySelectorAll('.field-add-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const key = this.dataset.field;
    if (fields[key]) {
      selectField(key);
      return;
    }
    const defaults = FIELD_DEFAULTS[key] || { x:100, y:100, w:150, h:30, fontSize:14, color:'#000000', fontFamily:'Arial', fontWeight:600, fontStyle:'normal', textAlign:'left', underline:false };
    fields[key] = { ...defaults };
    renderFields();
    selectField(key);
  });
});

function renderFields() {
  const container = document.getElementById('canvasFields');
  container.innerHTML = '';
  Object.keys(fields).forEach(key => {
    const f = fields[key];
    const el = document.createElement('div');
    el.className = 'designer-field' + (selectedField === key ? ' selected' : '');
    el.dataset.key = key;
    el.style.cssText = `position:absolute; left:${f.x}px; top:${f.y}px; width:${f.w}px; height:${f.h}px; border:2px solid ${selectedField===key?'#2563eb':'#64748b'}; background:rgba(255,255,255,0.85); cursor:move; display:flex; align-items:center; justify-content:${f.textAlign==='right'?'flex-end':(f.textAlign==='center'?'center':'flex-start')}; font-size:${f.fontSize}px; color:${f.color}; font-family:${f.fontFamily||'Arial'}; font-weight:${f.fontWeight||600}; font-style:${f.fontStyle||'normal'}; text-decoration:${f.underline?'underline':'none'}; border-radius:4px; user-select:none; z-index:10;`;

    const isSpecial = key === 'qr_code' || key === 'photo';
    if (isSpecial) {
      el.innerHTML = '<div style="text-align:center;font-size:11px;color:#64748b;"><i class="ri-' + (key==='qr_code'?'qr-code':'camera') + '-line" style="font-size:24px;display:block;"></i>' + FIELD_LABELS[key] + '</div>';
    } else {
      el.textContent = FIELD_LABELS[key];
    }

    el.addEventListener('mousedown', function(e) {
      e.preventDefault();
      selectField(key);
      dragState = { key, startX: e.clientX, startY: e.clientY, origX: f.x, origY: f.y };
    });

    container.appendChild(el);
  });
}

document.addEventListener('mousemove', function(e) {
  if (!dragState) return;
  const dx = e.clientX - dragState.startX;
  const dy = e.clientY - dragState.startY;
  fields[dragState.key].x = Math.max(0, dragState.origX + dx);
  fields[dragState.key].y = Math.max(0, dragState.origY + dy);
  renderFields();
  updateProperties();
});

document.addEventListener('mouseup', function() {
  dragState = null;
});

function selectField(key) {
  selectedField = key;
  renderFields();
  showProperties(key);
}

function showProperties(key) {
  const panel = document.getElementById('fieldProperties');
  const f = fields[key];
  if (!f) { panel.classList.add('d-none'); return; }
  panel.classList.remove('d-none');
  document.getElementById('propFieldName').textContent = FIELD_LABELS[key] || key;
  document.getElementById('propX').value = Math.round(f.x);
  document.getElementById('propY').value = Math.round(f.y);
  document.getElementById('propW').value = f.w;
  document.getElementById('propH').value = f.h;
  document.getElementById('propFontSize').value = f.fontSize;
  document.getElementById('propColor').value = f.color;
  document.getElementById('propFontFamily').value = f.fontFamily || 'Arial';
  document.getElementById('propFontWeight').value = String(f.fontWeight || 600);
  document.getElementById('propFontStyle').value = f.fontStyle || 'normal';
  document.getElementById('propTextAlign').value = f.textAlign || 'left';
  document.getElementById('propUnderline').checked = !!f.underline;
}

function updateProperties() {
  if (!selectedField || !fields[selectedField]) return;
  const f = fields[selectedField];
  document.getElementById('propX').value = Math.round(f.x);
  document.getElementById('propY').value = Math.round(f.y);
}

document.getElementById('propApply').addEventListener('click', function() {
  if (!selectedField || !fields[selectedField]) return;
  fields[selectedField].x = parseInt(document.getElementById('propX').value) || 0;
  fields[selectedField].y = parseInt(document.getElementById('propY').value) || 0;
  fields[selectedField].w = parseInt(document.getElementById('propW').value) || 100;
  fields[selectedField].h = parseInt(document.getElementById('propH').value) || 30;
  fields[selectedField].fontSize = parseInt(document.getElementById('propFontSize').value) || 14;
  fields[selectedField].color = document.getElementById('propColor').value || '#000000';
  fields[selectedField].fontFamily = document.getElementById('propFontFamily').value || 'Arial';
  fields[selectedField].fontWeight = parseInt(document.getElementById('propFontWeight').value, 10) || 600;
  fields[selectedField].fontStyle = document.getElementById('propFontStyle').value || 'normal';
  fields[selectedField].textAlign = document.getElementById('propTextAlign').value || 'left';
  fields[selectedField].underline = document.getElementById('propUnderline').checked;
  renderFields();
});

document.getElementById('propRemove').addEventListener('click', function() {
  if (!selectedField) return;
  delete fields[selectedField];
  selectedField = null;
  document.getElementById('fieldProperties').classList.add('d-none');
  renderFields();
});

function clearCanvas() {
  if (!confirm('Remove all fields from the canvas?')) return;
  fields = {};
  selectedField = null;
  document.getElementById('fieldProperties').classList.add('d-none');
  renderFields();
}

function prepareSubmit() {
  const config = Object.keys(fields).map(key => ({
    key,
    label: FIELD_LABELS[key] || key,
    ...fields[key]
  }));
  document.getElementById('layoutConfigInput').value = JSON.stringify(config);
}

function togglePreview() {
  const card = document.getElementById('previewCard');
  card.classList.toggle('d-none');
  if (!card.classList.contains('d-none')) {
    renderPreview();
  }
}

function renderPreview() {
  const container = document.getElementById('previewContainer');
  const bgStyle = document.getElementById('canvasBg').style.backgroundImage;
  let html = '<div style="position:absolute;top:0;left:0;width:100%;height:100%;background-size:cover;background-position:center;' + (bgStyle ? 'background-image:' + bgStyle + ';' : 'background:#f1f5f9;') + '"></div>';

  Object.keys(fields).forEach(key => {
    const f = fields[key];
    const val = SAMPLE_DATA[key] || key;
    const isSpecial = key === 'qr_code' || key === 'photo';

    if (key === 'qr_code') {
      html += `<div style="position:absolute;left:${f.x}px;top:${f.y}px;width:${f.w}px;height:${f.h}px;display:flex;align-items:center;justify-content:center;background:white;border:1px solid #ccc;border-radius:4px;"><i class="ri-qr-code-line" style="font-size:${Math.min(f.w,f.h)*0.7}px;color:#333;"></i></div>`;
    } else if (key === 'photo') {
      html += `<div style="position:absolute;left:${f.x}px;top:${f.y}px;width:${f.w}px;height:${f.h}px;display:flex;align-items:center;justify-content:center;background:#e2e8f0;border:2px solid #94a3b8;border-radius:4px;"><i class="ri-user-line" style="font-size:${Math.min(f.w,f.h)*0.5}px;color:#64748b;"></i></div>`;
    } else {
      const justify = f.textAlign === 'right' ? 'flex-end' : (f.textAlign === 'center' ? 'center' : 'flex-start');
      const decoration = f.underline ? 'underline' : 'none';
      html += `<div style="position:absolute;left:${f.x}px;top:${f.y}px;width:${f.w}px;height:${f.h}px;display:flex;align-items:center;justify-content:${justify};font-size:${f.fontSize}px;color:${f.color};font-family:${f.fontFamily || 'Arial'};font-weight:${f.fontWeight || 600};font-style:${f.fontStyle || 'normal'};text-decoration:${decoration};">${val}</div>`;
    }
  });

  container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
  initFromExisting();
});
</script>
@endpush
@endsection
