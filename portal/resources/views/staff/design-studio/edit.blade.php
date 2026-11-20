@extends('layouts.portal')
@section('title', 'Designer — ' . $template->name)

@section('content')
@php
  $isCard  = $template->type === 'card';
  $bgUrl   = $template->backgroundUrl();
  // Canvas at 96dpi screen equivalent: card=1012×638 (CR80@300dpi scaled), cert=794×1123 (A4@96dpi)
  $baseW = $isCard ? 1012 : 794;
  $baseH = $isCard ? 638  : 1123;
  
  if ($template->orientation === 'landscape') {
      $canvasW = max($baseW, $baseH);
      $canvasH = min($baseW, $baseH);
  } else {
      $canvasW = min($baseW, $baseH);
      $canvasH = max($baseW, $baseH);
  }

  $displayW = $canvasW / 2; // 50% display scale
  $displayH = $canvasH / 2;
  $fields  = $isCard ? $cardFields : $certFields;
@endphp

{{-- Full-screen designer — no portal wrapper padding --}}
<style>
  body { overflow: hidden; }
  .ds-studio { display:flex; flex-direction:column; height:100vh; font-family:var(--font-primary); }
  /* Top bar */
  .ds-topbar { height:52px; background:#1e293b; color:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 12px; flex-shrink:0; gap:8px; z-index:200; }
  .ds-topbar .ds-title { font-size:13px; font-weight:700; color:#f1f5f9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:260px; }
  .ds-topbar .ds-meta  { font-size:11px; color:#94a3b8; }
  /* Main area */
  .ds-main { display:flex; flex:1; overflow:hidden; position:relative; }
  /* Left panel */
  .ds-left { width:220px; flex-shrink:0; background:#f8fafc; border-right:1px solid #e2e8f0; display:flex; flex-direction:column; overflow:hidden; }
  .ds-left-tabs { display:flex; border-bottom:1px solid #e2e8f0; }
  .ds-left-tab { flex:1; padding:8px 4px; font-size:10px; font-weight:700; text-align:center; cursor:pointer; color:#64748b; border-bottom:2px solid transparent; text-transform:uppercase; letter-spacing:.04em; }
  .ds-left-tab.active { color:#1e293b; border-bottom-color:#f59e0b; }
  .ds-left-panel { flex:1; overflow-y:auto; padding:10px; display:none; }
  .ds-left-panel.active { display:block; }
  /* Centre */
  .ds-centre { flex:1; background:#64748b; overflow:auto; display:flex; align-items:flex-start; justify-content:center; padding:24px; }
  .ds-canvas-wrap { position:relative; box-shadow:0 8px 40px rgba(0,0,0,.4); }
  .ds-safe-margin { position:absolute; border:1px dashed rgba(255,100,100,.6); pointer-events:none; z-index:10; }
  .ds-bleed-margin { position:absolute; border:1px dashed rgba(100,100,255,.4); pointer-events:none; z-index:10; }
  /* Right panel */
  .ds-right { width:240px; flex-shrink:0; background:#fff; border-left:1px solid #e2e8f0; overflow-y:auto; padding:12px; transition:width .2s ease, padding .2s ease, border .2s ease; }
  .ds-right.is-collapsed { width:0; padding-left:0; padding-right:0; border-left:0; overflow:hidden; }
  #showPropertiesBtn { display:none; position:absolute; top:12px; right:12px; z-index:50; box-shadow:0 4px 12px rgba(15,23,42,.2); }
  .ds-main.properties-collapsed #showPropertiesBtn { display:inline-flex; }
  /* Bottom bar */
  .ds-bottombar { height:28px; background:#0f172a; color:#94a3b8; font-size:10px; display:flex; align-items:center; padding:0 12px; gap:16px; flex-shrink:0; }
  /* Field chips */
  .ds-chip { padding:6px 8px; border:1px solid #e2e8f0; border-radius:6px; background:#fff; cursor:grab; font-size:11px; display:flex; align-items:center; gap:6px; margin-bottom:6px; transition:background .15s; user-select:none; }
  .ds-chip:hover { background:#f1f5f9; border-color:#94a3b8; }
  .ds-chip i { font-size:13px; color:#64748b; }
  /* Element buttons */
  .ds-elem-btn { width:100%; padding:6px 8px; border:1px solid #e2e8f0; border-radius:6px; background:#fff; cursor:pointer; font-size:11px; display:flex; align-items:center; gap:6px; margin-bottom:6px; text-align:left; }
  .ds-elem-btn:hover { background:#f1f5f9; }
  /* Layer item */
  .ds-layer { padding:5px 8px; border:1px solid #e2e8f0; border-radius:4px; background:#fff; font-size:11px; display:flex; align-items:center; gap:6px; margin-bottom:4px; cursor:pointer; }
  .ds-layer:hover { background:#f1f5f9; }
  .ds-layer.selected { background:#fef3c7; border-color:#f59e0b; }
  /* Prop inputs */
  .ds-prop-label { font-size:10px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin-bottom:3px; }
  /* Btn styles */
  .ds-btn { padding:5px 10px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:4px; }
  .ds-btn-dark { background:#1e293b; color:#fff; }
  .ds-btn-dark:hover { background:#334155; }
  .ds-btn-warn { background:#f59e0b; color:#000; }
  .ds-btn-warn:hover { background:#d97706; }
  .ds-btn-outline { background:transparent; color:#94a3b8; border:1px solid #475569; }
  .ds-btn-outline:hover { background:#1e293b; color:#fff; }
  .ds-btn-success { background:#16a34a; color:#fff; }
  .ds-btn-success:hover { background:#15803d; }
  .ds-btn-danger { background:#dc2626; color:#fff; }
  .ds-control-group { margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0; }
  .ds-control-note { font-size:10px; color:#64748b; line-height:1.4; }
  .ds-palette-grid { display:grid; grid-template-columns:repeat(5, minmax(0, 1fr)); gap:6px; }
  .ds-palette-swatch {
    width:100%;
    aspect-ratio:1/1;
    border-radius:8px;
    border:1px solid rgba(15, 23, 42, 0.12);
    cursor:pointer;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,0.5);
    transition:transform .12s ease, box-shadow .12s ease;
  }
  .ds-palette-swatch:hover {
    transform:translateY(-1px);
    box-shadow:0 4px 10px rgba(15, 23, 42, 0.12);
  }
  .ds-palette-label { font-size:10px; color:#64748b; margin-top:4px; }
  /* Autosave indicator */
  #autosaveStatus { font-size:10px; color:#94a3b8; }
  #autosaveStatus.saving { color:#f59e0b; }
  #autosaveStatus.saved  { color:#4ade80; }
</style>

<div class="ds-studio">

  {{-- TOP BAR --}}
  <div class="ds-topbar">
    <div class="d-flex align-items-center gap-2" style="min-width:0;">
      <a href="{{ route('staff.design-studio.index') }}" class="ds-btn ds-btn-outline" id="exitBtn" title="Exit">
        <i class="ri-arrow-left-line"></i>
      </a>
      <div style="min-width:0;">
        <div class="ds-title">{{ $template->name }}</div>
        <div class="ds-meta">{{ ucfirst($template->type) }} · {{ $template->year }} ·
          <span class="badge {{ $template->statusBadgeClass() }}" style="font-size:9px;">{{ $template->statusLabel() }}</span>
        </div>
      </div>
    </div>
    <div class="d-flex align-items-center gap-1 flex-wrap">
      <button class="ds-btn ds-btn-outline" onclick="undoAction()" title="Undo (Ctrl+Z)"><i class="ri-arrow-go-back-line"></i></button>
      <button class="ds-btn ds-btn-outline" onclick="redoAction()" title="Redo (Ctrl+Y)"><i class="ri-arrow-go-forward-line"></i></button>
      <div style="width:1px;height:24px;background:#475569;margin:0 4px;"></div>
      <button class="ds-btn ds-btn-outline" onclick="zoomOut()" title="Zoom Out"><i class="ri-zoom-out-line"></i></button>
      <span id="zoomLabel" style="font-size:11px;color:#94a3b8;min-width:36px;text-align:center;">50%</span>
      <button class="ds-btn ds-btn-outline" onclick="zoomIn()" title="Zoom In"><i class="ri-zoom-in-line"></i></button>
      <button class="ds-btn ds-btn-outline" onclick="zoomFit()" title="Fit to screen"><i class="ri-fullscreen-line"></i></button>
      <div style="width:1px;height:24px;background:#475569;margin:0 4px;"></div>
      <button class="ds-btn ds-btn-outline" onclick="previewSampleData()" title="Preview with sample data"><i class="ri-eye-line"></i> Preview</button>
      <button class="ds-btn ds-btn-outline" onclick="openPreviewDataEditor()" title="Preview with actual data"><i class="ri-file-list-3-line"></i> Preview Data</button>
      <button class="ds-btn ds-btn-success" onclick="saveDraft()" title="Save and activate for production (Ctrl+S)"><i class="ri-save-line"></i> Save &amp; Use in Production</button>
    </div>
    <div id="autosaveStatus">All changes saved</div>
  </div>

  {{-- REJECTION BANNER --}}
  @if($template->status === 'rejected')
  <div style="background:#fef2f2;border-bottom:1px solid #fecaca;padding:8px 16px;font-size:12px;color:#991b1b;flex-shrink:0;">
    <strong><i class="ri-close-circle-line me-1"></i>Rejected</strong> by {{ $template->rejecter?->name ?? 'Super Admin' }}
    on {{ $template->rejected_at?->format('d M Y') }}:
    <em>{{ $template->rejection_reason }}</em>
    — Revise and save to activate the updated design.
  </div>
  @endif

  {{-- MAIN AREA --}}
  <div class="ds-main">

    {{-- LEFT PANEL --}}
    <div class="ds-left">
      <div class="ds-left-tabs">
        <div class="ds-left-tab active" onclick="switchTab('elements')" id="tab-elements">Elements</div>
        <div class="ds-left-tab" onclick="switchTab('fields')" id="tab-fields">Fields</div>
        <div class="ds-left-tab" onclick="switchTab('layers')" id="tab-layers">Layers</div>
      </div>

      {{-- ELEMENTS TAB --}}
      <div class="ds-left-panel active" id="panel-elements">
        <div class="ds-prop-label mb-2">Text</div>
        <button class="ds-elem-btn" onclick="addElement('text')"><i class="ri-text"></i> Text Box</button>
        <button class="ds-elem-btn" onclick="addElement('heading')"><i class="ri-heading"></i> Heading</button>
        <div class="ds-prop-label mb-2 mt-3">Shapes</div>
        <button class="ds-elem-btn" onclick="addElement('rect')"><i class="ri-rectangle-line"></i> Rectangle</button>
        <button class="ds-elem-btn" onclick="addElement('rounded_rect')"><i class="ri-shape-fill"></i> Rounded Rect</button>
        <button class="ds-elem-btn" onclick="addElement('circle')"><i class="ri-circle-line"></i> Circle</button>
        <button class="ds-elem-btn" onclick="addElement('ellipse')"><i class="ri-shape-line"></i> Ellipse</button>
        <button class="ds-elem-btn" onclick="addElement('triangle')"><i class="ri-triangle-line"></i> Triangle</button>
        <button class="ds-elem-btn" onclick="addElement('diamond')"><i class="ri-diamond-line"></i> Diamond</button>
        <button class="ds-elem-btn" onclick="addElement('star')"><i class="ri-star-line"></i> Star</button>
        <button class="ds-elem-btn" onclick="addElement('hexagon')"><i class="ri-shapes-line"></i> Hexagon</button>
        <button class="ds-elem-btn" onclick="addElement('pentagon')"><i class="ri-pentagon-line"></i> Pentagon</button>
        <button class="ds-elem-btn" onclick="addElement('arrow')"><i class="ri-arrow-right-line"></i> Arrow</button>
        <button class="ds-elem-btn" onclick="addElement('heart')"><i class="ri-heart-3-line"></i> Heart</button>
        <button class="ds-elem-btn" onclick="addElement('line')"><i class="ri-separator"></i> Line</button>
        <div class="ds-prop-label mb-2 mt-3">Media</div>
        <button class="ds-elem-btn" onclick="triggerPictureUpload()"><i class="ri-image-line"></i> Insert Picture</button>
        <button class="ds-elem-btn" onclick="addElement('logo')"><i class="ri-shield-star-line"></i> ZMC Logo</button>
        <div class="ds-prop-label mb-2 mt-3">Security</div>
        <button class="ds-elem-btn" onclick="addElement('watermark')"><i class="ri-water-flash-line"></i> Watermark Text</button>
        <button class="ds-elem-btn" onclick="addElement('border')"><i class="ri-border-all-line"></i> Border Box</button>
        <div class="ds-control-group">
          <div class="ds-prop-label mb-2">Canvas Background</div>
          <input type="color" id="bgColorPicker" class="form-control form-control-sm form-control-color mb-2" value="#ffffff" oninput="setCanvasBackground(this.value)">
          <div id="bgPaletteGrid" class="ds-palette-grid mb-2"></div>
          <button type="button" id="bgPaletteToggle" class="ds-btn ds-btn-outline w-100 mb-2" onclick="togglePaletteExpansion('bgPaletteGrid', 'bgPaletteToggle')">More colors</button>
          <button class="ds-elem-btn" onclick="resetCanvasBackground()"><i class="ri-paint-brush-line"></i> Reset Background</button>
          <div class="ds-control-note mt-2">Choose any color from the full palette to set the base canvas background.</div>
        </div>
        <div class="ds-control-group">
          <div class="ds-prop-label mb-2">Background Watermarks</div>
          <input type="text" id="bgWatermarkText" class="form-control form-control-sm mb-2" value="ZMC OFFICIAL">
          <button class="ds-elem-btn" onclick="applyBackgroundWatermarks()"><i class="ri-water-flash-line"></i> Apply Tiled Watermarks</button>
          <button class="ds-elem-btn" onclick="clearBackgroundWatermarks()"><i class="ri-close-line"></i> Clear Watermarks</button>
          <div class="ds-control-note mt-2">Creates repeated low-opacity watermark text across the full background.</div>
        </div>
        <div class="ds-control-group">
          <div class="ds-prop-label mb-2">Upload Background</div>
          <label class="ds-elem-btn" style="cursor:pointer;">
            <i class="ri-upload-2-line"></i> Upload BG Image
            <input type="file" id="bgUploadInput" accept="image/*" style="display:none;" onchange="uploadBackground(this)">
          </label>
          <div class="mt-2 d-flex gap-2 align-items-center">
            <div style="flex:1">
              <select id="bgModeSelect" class="form-select form-select-sm" onchange="setBgMode(this.value)">
                <option value="contain" selected>Original aspect (show full image)</option>
                <option value="cover">Cover (may crop edges)</option>
                <option value="stretch">Stretch (fill, ignore aspect)</option>
                <option value="custom">Custom (position + size)</option>
              </select>
            </div>
            <button class="ds-btn ds-btn-outline" type="button" onclick="toggleBgCustomControls()" title="Show custom options">⚙</button>
          </div>
          <div id="bgCustomControls" style="display:none;margin-top:8px;">
            <div class="row g-1">
              <div class="col-6"><label class="form-label" style="font-size:10px">Width %</label><input id="bgCustomW" type="number" min="1" max="200" value="50" class="form-control form-control-sm" oninput="setBgCustom()"></div>
              <div class="col-6"><label class="form-label" style="font-size:10px">Height %</label><input id="bgCustomH" type="number" min="1" max="200" value="50" class="form-control form-control-sm" oninput="setBgCustom()"></div>
            </div>
            <div class="row g-1 mt-1">
              <div class="col-6"><label class="form-label" style="font-size:10px">Left %</label><input id="bgCustomLeft" type="number" min="0" max="100" value="25" class="form-control form-control-sm" oninput="setBgCustom()"></div>
              <div class="col-6"><label class="form-label" style="font-size:10px">Top %</label><input id="bgCustomTop" type="number" min="0" max="100" value="25" class="form-control form-control-sm" oninput="setBgCustom()"></div>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button class="ds-elem-btn" onclick="addZimbabweFlagBackground()"><i class="ri-flag-2-line"></i> Add Zimbabwe Flag</button>
            <button class="ds-elem-btn" onclick="removeZimbabweFlagBackground()"><i class="ri-close-line"></i> Remove</button>
            <button class="ds-elem-btn" type="button" onclick="toggleZimFlagEdit()" id="zimFlagEditBtn"><i class="ri-edit-line"></i> Edit</button>
          </div>
          <div class="mt-2 d-flex gap-2 align-items-center">
            <button class="ds-btn ds-btn-outline" type="button" onclick="toggleZimFlagControls()" title="Show flag size and position controls">⚙</button>
            <span class="text-muted" style="font-size:0.84rem;">Adjust flag size and placement</span>
          </div>
          <div id="zimFlagCustomControls" style="display:none;margin-top:8px;">
            <div class="row g-1">
              <div class="col-6"><label class="form-label" style="font-size:10px">Width %</label><input id="zimFlagWidth" type="number" min="1" max="200" value="80" class="form-control form-control-sm" oninput="setZimbabweFlagCustom()"></div>
              <div class="col-6"><label class="form-label" style="font-size:10px">Height %</label><input id="zimFlagHeight" type="number" min="1" max="200" value="50" class="form-control form-control-sm" oninput="setZimbabweFlagCustom()"></div>
            </div>
            <div class="row g-1 mt-1">
              <div class="col-6"><label class="form-label" style="font-size:10px">Left %</label><input id="zimFlagLeft" type="number" min="0" max="100" value="10" class="form-control form-control-sm" oninput="setZimbabweFlagCustom()"></div>
              <div class="col-6"><label class="form-label" style="font-size:10px">Top %</label><input id="zimFlagTop" type="number" min="0" max="100" value="20" class="form-control form-control-sm" oninput="setZimbabweFlagCustom()"></div>
            </div>
          </div>
          <div id="bgImageStatus" class="ds-control-note mt-2">No background image selected.</div>
        </div>
        <div class="ds-control-group">
          <div class="ds-prop-label mb-2">Insert Picture</div>
          <label class="ds-elem-btn" style="cursor:pointer;">
            <i class="ri-image-add-line"></i> Insert Picture
            <input type="file" id="pictureUploadInput" accept="image/*" style="display:none;" onchange="insertPictureFromFile(this)">
          </label>
          <div class="ds-control-note mt-2">Add photos, logos, or branded graphics directly onto the canvas.</div>
        </div>
      </div>

      {{-- DYNAMIC FIELDS TAB --}}
      <div class="ds-left-panel" id="panel-fields">
        <div class="small text-muted mb-2" style="font-size:10px;">Click or drag to add a dynamic field to the canvas.</div>
        @foreach($fields as $field)
        <div class="ds-chip"
             draggable="true"
             data-key="{{ $field['key'] }}"
             data-label="{{ $field['label'] }}"
             data-ftype="{{ $field['type'] }}"
             onclick="addDynamicField('{{ $field['key'] }}','{{ addslashes($field['label']) }}','{{ $field['type'] }}')">
          <i class="ri-{{ $field['type'] === 'photo' ? 'user-3' : ($field['type'] === 'qr' ? 'qr-code' : ($field['type'] === 'image' ? 'image-2' : ($field['type'] === 'signature' ? 'pen-nib' : ($field['type'] === 'stamp' ? 'seal' : ($field['type'] === 'date' ? 'calendar-line' : 'text'))))) }}-line"></i>
          <span>{{ $field['label'] }}</span>
        </div>
        @endforeach
      </div>

      {{-- LAYERS TAB --}}
      <div class="ds-left-panel" id="panel-layers">
        <div class="small text-muted mb-2" style="font-size:10px;">Click a layer to select it. Drag to reorder.</div>
        <div id="layersList"></div>
        <div class="mt-2 d-flex gap-1">
          <button class="ds-btn ds-btn-outline w-100" onclick="bringForward()" title="Bring Forward" style="font-size:10px;"><i class="ri-bring-forward"></i></button>
          <button class="ds-btn ds-btn-outline w-100" onclick="sendBackward()" title="Send Backward" style="font-size:10px;"><i class="ri-send-backward"></i></button>
          <button class="ds-btn ds-btn-outline w-100" onclick="bringToFront()" title="Front" style="font-size:10px;"><i class="ri-bring-to-front"></i></button>
          <button class="ds-btn ds-btn-outline w-100" onclick="sendToBack()" title="Back" style="font-size:10px;"><i class="ri-send-to-back"></i></button>
        </div>
      </div>
    </div>

    {{-- CENTRE CANVAS --}}
    <div class="ds-centre" id="canvasCentre">
      <div class="ds-canvas-wrap" id="canvasWrap" style="width:{{ $displayW }}px;height:{{ $displayH }}px;">
        {{-- Safe margin guide --}}
        <div class="ds-safe-margin" style="top:{{ $isCard ? 10 : 20 }}px;left:{{ $isCard ? 10 : 20 }}px;right:{{ $isCard ? 10 : 20 }}px;bottom:{{ $isCard ? 10 : 20 }}px;"></div>
        <canvas id="designCanvas"></canvas>
      </div>
    </div>

    {{-- RIGHT PROPERTIES PANEL --}}
    <button type="button" id="showPropertiesBtn" class="ds-btn ds-btn-dark" onclick="togglePropertiesPanel()" title="Show properties">
      <i class="ri-sidebar-unfold-line"></i> Properties
    </button>
    <div class="ds-right" id="propertiesPanel">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="ds-prop-label mb-0">Properties</div>
        <button type="button" class="ds-btn ds-btn-outline" onclick="togglePropertiesPanel()" title="Hide properties" aria-label="Hide properties">
          <i class="ri-sidebar-fold-line"></i>
        </button>
      </div>
      <div id="nothingSelected" class="text-muted" style="font-size:11px;">Select an object on the canvas to edit its properties.</div>
      <div id="propPanel" style="display:none;">
        <div class="mb-2">
          <div class="ds-prop-label">Label / Text</div>
          <input type="text" id="p_text" class="form-control form-control-sm" oninput="applyProp('text')">
        </div>
        <div class="row g-1 mb-2">
          <div class="col-6"><div class="ds-prop-label">X</div><input type="number" id="p_x" class="form-control form-control-sm" oninput="applyProp('x')"></div>
          <div class="col-6"><div class="ds-prop-label">Y</div><input type="number" id="p_y" class="form-control form-control-sm" oninput="applyProp('y')"></div>
        </div>
        <div class="row g-1 mb-2">
          <div class="col-6"><div class="ds-prop-label">Width</div><input type="number" id="p_w" class="form-control form-control-sm" oninput="applyProp('w')"></div>
          <div class="col-6"><div class="ds-prop-label">Height</div><input type="number" id="p_h" class="form-control form-control-sm" oninput="applyProp('h')"></div>
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Font Size</div>
          <input type="number" id="p_fontSize" class="form-control form-control-sm" oninput="applyProp('fontSize')">
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Font Weight</div>
          <select id="p_fontWeight" class="form-select form-select-sm" onchange="applyProp('fontWeight')">
            <option value="normal">Normal</option>
            <option value="bold">Bold</option>
          </select>
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Font Style</div>
          <select id="p_fontStyle" class="form-select form-select-sm" onchange="applyProp('fontStyle')">
            <option value="normal">Normal</option>
            <option value="italic">Italic</option>
          </select>
        </div>
        <div class="d-flex gap-3 mb-2" style="font-size:11px;">
          <label class="d-flex align-items-center gap-1"><input type="checkbox" id="p_underline" onchange="applyProp('underline')"> Underline</label>
          <label class="d-flex align-items-center gap-1"><input type="checkbox" id="p_linethrough" onchange="applyProp('linethrough')"> Strike</label>
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Font Family</div>
          <select id="p_fontFamily" class="form-select form-select-sm" onchange="applyProp('fontFamily')">
            <option value="Arial">Arial</option>
            <option value="Georgia">Georgia</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Courier New">Courier New</option>
            <option value="Verdana">Verdana</option>
          </select>
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Text Align</div>
          <div class="d-flex gap-1">
            <button class="ds-btn ds-btn-outline flex-fill" onclick="setTextAlign('left')"><i class="ri-align-left"></i></button>
            <button class="ds-btn ds-btn-outline flex-fill" onclick="setTextAlign('center')"><i class="ri-align-center"></i></button>
            <button class="ds-btn ds-btn-outline flex-fill" onclick="setTextAlign('right')"><i class="ri-align-right"></i></button>
          </div>
        </div>
        <div class="row g-1 mb-2">
          <div class="col-6">
            <div class="ds-prop-label">Letter Spacing</div>
            <input type="number" id="p_charSpacing" class="form-control form-control-sm" min="-200" max="1000" step="1" oninput="applyProp('charSpacing')">
          </div>
          <div class="col-6">
            <div class="ds-prop-label">Line Height</div>
            <input type="number" id="p_lineHeight" class="form-control form-control-sm" min="0.5" max="4" step="0.05" oninput="applyProp('lineHeight')">
          </div>
        </div>
        <div class="row g-1 mb-2">
          <div class="col-6">
            <div class="ds-prop-label">Fill Color</div>
            <input type="color" id="p_fill" class="form-control form-control-sm form-control-color" oninput="applyProp('fill')">
          </div>
          <div class="col-6">
            <div class="ds-prop-label">Stroke</div>
            <input type="color" id="p_stroke" class="form-control form-control-sm form-control-color" oninput="applyProp('stroke')">
          </div>
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Gradient Fill</div>
          <div class="d-flex gap-1 mb-1">
            <input type="color" id="p_fill_grad_start" class="form-control form-control-sm form-control-color flex-fill" value="#0ea5e9">
            <input type="color" id="p_fill_grad_end" class="form-control form-control-sm form-control-color flex-fill" value="#8b5cf6">
          </div>
          <div class="d-flex gap-1">
            <button type="button" class="ds-btn ds-btn-outline flex-fill" onclick="applyGradientToSelection('fill')" style="font-size:10px;">Apply Fill Gradient</button>
            <button type="button" class="ds-btn ds-btn-outline flex-fill" onclick="applyGradientToSelection('stroke')" style="font-size:10px;">Apply Stroke Gradient</button>
          </div>
          <div class="ds-control-note mt-2">Create a smooth linear gradient for the selected object using the two colors above.</div>
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Palette</div>
          <div id="selectedPaletteGrid" class="ds-palette-grid mb-2"></div>
          <button type="button" id="selectedPaletteToggle" class="ds-btn ds-btn-outline w-100 mb-2" onclick="togglePaletteExpansion('selectedPaletteGrid', 'selectedPaletteToggle')">More colors</button>
          <div class="d-flex gap-1">
            <button type="button" class="ds-btn ds-btn-outline flex-fill" onclick="applyPaletteToSelection('fill', document.getElementById('p_fill').value)" style="font-size:10px;">Apply Fill</button>
            <button type="button" class="ds-btn ds-btn-outline flex-fill" onclick="applyPaletteToSelection('stroke', document.getElementById('p_stroke').value)" style="font-size:10px;">Apply Stroke</button>
          </div>
          <div class="ds-control-note mt-2">Use the palette to quickly color the selected object’s fill or stroke.</div>
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Stroke Width</div>
          <input type="number" id="p_strokeWidth" class="form-control form-control-sm" min="0" max="20" oninput="applyProp('strokeWidth')">
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Opacity (%)</div>
          <input type="range" id="p_opacity" class="form-range" min="0" max="100" oninput="applyProp('opacity')">
        </div>
        <div class="mb-2">
          <div class="ds-prop-label">Rotation (°)</div>
          <input type="number" id="p_rotation" class="form-control form-control-sm" oninput="applyProp('rotation')">
        </div>
        <hr>
        <div class="ds-prop-label mb-2">Align on Canvas</div>
        <div class="row g-1 mb-2">
          <div class="col-4"><button class="ds-btn ds-btn-outline w-100" onclick="alignObj('left')" style="font-size:10px;"><i class="ri-align-left"></i></button></div>
          <div class="col-4"><button class="ds-btn ds-btn-outline w-100" onclick="alignObj('center')" style="font-size:10px;"><i class="ri-align-center"></i></button></div>
          <div class="col-4"><button class="ds-btn ds-btn-outline w-100" onclick="alignObj('right')" style="font-size:10px;"><i class="ri-align-right"></i></button></div>
          <div class="col-4"><button class="ds-btn ds-btn-outline w-100" onclick="alignObj('top')" style="font-size:10px;"><i class="ri-align-top"></i></button></div>
          <div class="col-4"><button class="ds-btn ds-btn-outline w-100" onclick="alignObj('middle')" style="font-size:10px;"><i class="ri-align-vertically"></i></button></div>
          <div class="col-4"><button class="ds-btn ds-btn-outline w-100" onclick="alignObj('bottom')" style="font-size:10px;"><i class="ri-align-bottom"></i></button></div>
        </div>
        <hr>
        <button class="ds-btn ds-btn-danger w-100" onclick="deleteSelected()" style="font-size:11px;"><i class="ri-delete-bin-line"></i> Delete Selected</button>
      </div>
    </div>

  </div>{{-- /ds-main --}}

  {{-- BOTTOM BAR --}}
  <div class="ds-bottombar">
    <span><strong style="color:#f1f5f9;">{{ $template->name }}</strong></span>
    <span>{{ ucfirst($template->type) }} · {{ $template->year }}</span>
    <span>{{ $canvasW }}×{{ $canvasH }}px</span>
    <span id="zoomStatus">Zoom: 50%</span>
    <span id="objCount">Objects: 0</span>
    <span id="lastSaved">—</span>
  </div>

</div>{{-- /ds-studio --}}

{{-- Hidden save form --}}
<form id="saveForm" method="POST" action="{{ route('staff.design-studio.update', $template->id) }}" style="display:none;">
  @csrf @method('PUT')
  <input type="hidden" name="name"          value="{{ $template->name }}">
  <input type="hidden" name="template_for"  value="{{ $template->template_for }}">
  <input type="hidden" name="year"          value="{{ $template->year }}">
  <input type="hidden" name="orientation"   value="{{ $template->orientation }}">
  <input type="hidden" name="layout_config" id="layoutConfigInput">
  <input type="hidden" name="save_version"  value="0">
</form>
<form id="bgUploadForm" method="POST" action="{{ route('staff.design-studio.update', $template->id) }}"
      enctype="multipart/form-data" style="display:none;">
  @csrf @method('PUT')
  <input type="hidden" name="name"         value="{{ $template->name }}">
  <input type="hidden" name="template_for" value="{{ $template->template_for }}">
  <input type="hidden" name="year"         value="{{ $template->year }}">
  <input type="file"   name="background"   id="bgFileInput">
</form>

{{-- Preview Modal --}}
<div class="modal fade" id="dsPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fw-bold" id="dsPreviewModalTitle">Preview (Sample Data)</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="background:#0f172a;">
        <div class="d-flex justify-content-center">
          <canvas id="previewCanvas" style="max-width:100%;border-radius:6px;background:#fff;"></canvas>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="dsPreviewDataModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fw-bold">Preview Data Editor</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="previewDataJson" class="form-label">Actual preview values</label>
          <textarea id="previewDataJson" class="form-control" rows="12"></textarea>
          <div class="form-text">Edit or paste the actual dynamic field values to render the card/certificate preview with real data. Leave fields empty to use fallback sample text.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetPreviewData()">Reset to sample values</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="savePreviewData()">Preview Actual Data</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<script>
(function () {
'use strict';

const CANVAS_W  = {{ $canvasW }};
const CANVAS_H  = {{ $canvasH }};
const IS_CARD   = {{ $isCard ? 'true' : 'false' }};
const BG_URL    = @json($bgUrl);
const SAVED     = @json($template->layout_config ?? null);
const LOGO_URL  = '{{ asset('zmc_logo.png') }}';
const UPDATE_URL = @json(route('staff.design-studio.update', $template->id));
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());

// Background mode: 'cover' | 'contain' | 'stretch' | 'custom'
// Uploaded artwork is shown in full by default. This preserves its aspect
// ratio and avoids stretching or cropping supplied card backgrounds.
window.BG_MODE = 'contain';
window.BG_CUSTOM = { widthPct: 50, heightPct: 50, leftPct: 25, topPct: 25 };
window.BG_IMAGE_OBJ = null; // Reference to the fabric background image object on canvas

window.setBgMode = function (mode) {
  window.BG_MODE = String(mode || 'contain');
  // show/hide custom controls
  const el = document.getElementById('bgCustomControls');
  if (el) el.style.display = (window.BG_MODE === 'custom') ? 'block' : 'none';
  // Reapply sizing to the existing background image if it exists
  if (window.BG_IMAGE_OBJ) {
    const img = window.BG_IMAGE_OBJ;
    const naturalW = img._element?.naturalWidth || img.width || CANVAS_W;
    const naturalH = img._element?.naturalHeight || img.height || CANVAS_H;
    const scaleX = CANVAS_W / naturalW;
    const scaleY = CANVAS_H / naturalH;
    let finalScaleX = 1, finalScaleY = 1, left = 0, top = 0;

    if (window.BG_MODE === 'contain') {
      const s = Math.min(scaleX, scaleY);
      finalScaleX = finalScaleY = s;
      const w = naturalW * s; const h = naturalH * s;
      left = Math.round((CANVAS_W - w) / 2);
      top = Math.round((CANVAS_H - h) / 2);
    } else if (window.BG_MODE === 'stretch') {
      finalScaleX = scaleX;
      finalScaleY = scaleY;
      left = 0; top = 0;
    } else if (window.BG_MODE === 'custom') {
      const c = window.BG_CUSTOM || { widthPct:50, heightPct:50, leftPct:25, topPct:25 };
      finalScaleX = (CANVAS_W * (c.widthPct/100)) / naturalW;
      finalScaleY = (CANVAS_H * (c.heightPct/100)) / naturalH;
      left = Math.round(CANVAS_W * (c.leftPct/100));
      top = Math.round(CANVAS_H * (c.topPct/100));
    } else {
      const s = Math.max(scaleX, scaleY);
      finalScaleX = finalScaleY = s;
      left = 0; top = 0;
    }
    
    img.set({ scaleX: finalScaleX, scaleY: finalScaleY, left: left, top: top });
    canvas.requestRenderAll();
    markDirty();
  }
};

window.setBgCustom = function () {
  const w = parseFloat(document.getElementById('bgCustomW')?.value || 50);
  const h = parseFloat(document.getElementById('bgCustomH')?.value || 50);
  const l = parseFloat(document.getElementById('bgCustomLeft')?.value || 25);
  const t = parseFloat(document.getElementById('bgCustomTop')?.value || 25);
  window.BG_CUSTOM = { widthPct: w, heightPct: h, leftPct: l, topPct: t };
  // If currently in custom mode and background exists, reapply
  if (window.BG_MODE === 'custom' && window.BG_IMAGE_OBJ) {
    window.setBgMode('custom');
  }
};

window.toggleBgCustomControls = function () {
  const el = document.getElementById('bgCustomControls');
  if (!el) return;
  el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
};

window.ZIM_FLAG_OBJ = null;
window.ZIM_FLAG_SETTINGS = {
  widthPct: 80,
  heightPct: 50,
  leftPct: 10,
  topPct: 20,
};

window.toggleZimFlagControls = function () {
  const el = document.getElementById('zimFlagCustomControls');
  if (!el) return;
  el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
};

window.setZimbabweFlagCustom = function () {
  const w = parseFloat(document.getElementById('zimFlagWidth')?.value || 80);
  const h = parseFloat(document.getElementById('zimFlagHeight')?.value || 50);
  const l = parseFloat(document.getElementById('zimFlagLeft')?.value || 10);
  const t = parseFloat(document.getElementById('zimFlagTop')?.value || 20);
  window.ZIM_FLAG_SETTINGS = { widthPct: w, heightPct: h, leftPct: l, topPct: t };
  // If the flag is currently being edited interactively, update the existing object
  if (window.ZIM_FLAG_EDITING && window.ZIM_FLAG_OBJ && canvas.contains(window.ZIM_FLAG_OBJ)) {
    const settings = window.ZIM_FLAG_SETTINGS;
    const width = Math.round((CANVAS_W * Math.max(1, Math.min(200, settings.widthPct))) / 100);
    const height = Math.round((CANVAS_H * Math.max(1, Math.min(200, settings.heightPct))) / 100);
    const left = Math.round((CANVAS_W * Math.max(0, Math.min(100, settings.leftPct))) / 100);
    const top = Math.round((CANVAS_H * Math.max(0, Math.min(100, settings.topPct))) / 100);
    // Use group's original size to compute scale factors
    const obj = window.ZIM_FLAG_OBJ;
    const sx = width / (obj.width || 1);
    const sy = height / (obj.height || 1);
    obj.set({ left, top, scaleX: sx, scaleY: sy });
    obj.setCoords();
    canvas.requestRenderAll();
    refreshLayers();
    markDirty();
    return;
  }

  // Otherwise rebuild the flag (non-editing mode)
  if (window.ZIM_FLAG_OBJ && canvas.contains(window.ZIM_FLAG_OBJ)) {
    const updatedFlag = buildZimbabweFlagObject();
    canvas.remove(window.ZIM_FLAG_OBJ);
    canvas.add(updatedFlag);
    window.ZIM_FLAG_OBJ = updatedFlag;
    canvas.sendToBack(updatedFlag);
    canvas.requestRenderAll();
    refreshLayers();
    markDirty();
  }
};

window.ZIM_FLAG_EDITING = false;

window.toggleZimFlagEdit = function () {
  if (!window.ZIM_FLAG_OBJ || !canvas.contains(window.ZIM_FLAG_OBJ)) {
    if (bgImageStatusEl) bgImageStatusEl.textContent = 'Add the flag first before editing.';
    return;
  }
  if (!window.ZIM_FLAG_EDITING) {
    window.enableZimFlagEditing();
  } else {
    window.disableZimFlagEditing();
  }
};

window.enableZimFlagEditing = function () {
  const obj = window.ZIM_FLAG_OBJ;
  if (!obj) return;
  window.ZIM_FLAG_EDITING = true;
  obj.selectable = true;
  obj.evented = true;
  obj.hasControls = true;
  obj.lockRotation = true;
  obj.lockScalingFlip = false;
  canvas.setActiveObject(obj);
  canvas.requestRenderAll();
  if (bgImageStatusEl) bgImageStatusEl.textContent = 'Flag editing enabled: drag or use handles to resize.';
};

window.disableZimFlagEditing = function () {
  const obj = window.ZIM_FLAG_OBJ;
  if (!obj) return;
  // Capture final size/position into settings
  const scaledW = obj.getScaledWidth();
  const scaledH = obj.getScaledHeight();
  const left = obj.left;
  const top = obj.top;
  window.ZIM_FLAG_SETTINGS = {
    widthPct: Math.round((scaledW / CANVAS_W) * 100),
    heightPct: Math.round((scaledH / CANVAS_H) * 100),
    leftPct: Math.round((left / CANVAS_W) * 100),
    topPct: Math.round((top / CANVAS_H) * 100),
  };
  // Update the input fields to reflect
  document.getElementById('zimFlagWidth').value = window.ZIM_FLAG_SETTINGS.widthPct;
  document.getElementById('zimFlagHeight').value = window.ZIM_FLAG_SETTINGS.heightPct;
  document.getElementById('zimFlagLeft').value = window.ZIM_FLAG_SETTINGS.leftPct;
  document.getElementById('zimFlagTop').value = window.ZIM_FLAG_SETTINGS.topPct;

  obj.selectable = false;
  obj.evented = false;
  obj.hasControls = false;
  canvas.discardActiveObject();
  canvas.sendToBack(obj);
  canvas.requestRenderAll();
  refreshLayers();
  markDirty();
  window.ZIM_FLAG_EDITING = false;
  if (bgImageStatusEl) bgImageStatusEl.textContent = 'Flag editing saved.';
};

function buildZimbabweFlagObject() {
  const settings = window.ZIM_FLAG_SETTINGS || { widthPct: 80, heightPct: 50, leftPct: 10, topPct: 20 };
  const width = Math.round((CANVAS_W * Math.max(1, Math.min(200, settings.widthPct))) / 100);
  const height = Math.round((CANVAS_H * Math.max(1, Math.min(200, settings.heightPct))) / 100);
  const left = Math.round((CANVAS_W * Math.max(0, Math.min(100, settings.leftPct))) / 100);
  const top = Math.round((CANVAS_H * Math.max(0, Math.min(100, settings.topPct))) / 100);

  const green = new fabric.Rect({
    width,
    height,
    left: 0,
    top: 0,
    fill: '#1f7a3b',
    originX: 'left',
    originY: 'top',
    selectable: false,
    evented: false,
  });

  const whiteStripe = new fabric.Rect({
    width,
    height: Math.max(18, height / 6),
    left: 0,
    top: Math.round((height - (Math.max(18, height / 6))) / 2),
    fill: '#ffffff',
    originX: 'left',
    originY: 'top',
    selectable: false,
    evented: false,
  });

  const redChevron = new fabric.Polygon([
    { x: 0, y: 0 },
    { x: 92, y: 0 },
    { x: 0, y: height },
  ], {
    left: 0,
    top: 0,
    fill: '#d62828',
    originX: 'left',
    originY: 'top',
    selectable: false,
    evented: false,
  });

  const star = new fabric.Text('✶', {
    left: 30,
    top: 52,
    fontSize: 60,
    fill: '#ffffff',
    fontFamily: 'Arial',
    selectable: false,
    evented: false,
  });

  return new fabric.Group([green, whiteStripe, redChevron, star], {
    left,
    top,
    selectable: false,
    evented: false,
    opacity: 0.92,
    elemType: 'zimbabwe_flag',
    zmcFlagManual: true,
  });
}

window.addZimbabweFlagBackground = function () {
  if (window.ZIM_FLAG_OBJ && canvas.contains(window.ZIM_FLAG_OBJ)) {
    canvas.remove(window.ZIM_FLAG_OBJ);
  }
  const flag = buildZimbabweFlagObject();
  canvas.add(flag);
  window.ZIM_FLAG_OBJ = flag;
  canvas.sendToBack(flag);
  // Keep an uploaded background at the very bottom while the explicit flag
  // remains above it and below the card fields/text.
  if (window.BG_IMAGE_OBJ && canvas.contains(window.BG_IMAGE_OBJ)) {
    canvas.sendToBack(window.BG_IMAGE_OBJ);
  }
  canvas.requestRenderAll();
  refreshLayers();
  markDirty();
  if (bgImageStatusEl) bgImageStatusEl.textContent = 'Zimbabwe flag added as background.';
};

window.removeZimbabweFlagBackground = function () {
  if (window.ZIM_FLAG_OBJ && canvas.contains(window.ZIM_FLAG_OBJ)) {
    canvas.remove(window.ZIM_FLAG_OBJ);
  }
  window.ZIM_FLAG_OBJ = null;
  canvas.requestRenderAll();
  refreshLayers();
  markDirty();
  if (bgImageStatusEl) bgImageStatusEl.textContent = 'Zimbabwe flag removed.';
};

function ensureZimbabweFlagForFields() {
  // Flags are explicit design elements. Do not insert a large flag
  // automatically over an uploaded card background.
}

const SAMPLE_CARD = {
  applicant_photo: null,
  full_name: 'Tawanda Moyo',
  first_name: 'Tawanda',
  surname: 'Moyo',
  national_id: '63-123456 T 00',
  passport_number: 'PP1234567',
  accreditation_number: '2305JF00126',
  accreditation_category_code: 'JF',
  accreditation_category_name: 'Local Journalist Freelancing Locally',
  application_type: 'Local Journalist / Media Practitioner',
  media_organisation: 'Sample Media House',
  designation: 'Journalist',
  issue_date: @json(now()->format('d M Y')),
  expiry_date: @json(now()->endOfYear()->format('d M Y')),
  collection_office: 'Harare',
  issuing_office: 'ZMC HQ',
  registrar_name: 'J. Mutasa',
  registrar_signature: '___________',
  verification_qr: 'QR',
};

const SAMPLE_CERT = {
  media_house_name: 'Sample Media Services (Pvt) Ltd',
  registration_number: '2305MN00126',
  licence_number: 'LIC-' + new Date().getFullYear() + '-00045',
  registration_type: 'Local Mass Media Service AP1',
  mass_media_category_code: 'MN',
  mass_media_category_name: 'National Media Service Publishing Newspaper',
  physical_address: '123 Main Street, Harare',
  postal_address: 'P.O. Box 123, Harare',
  issue_date: @json(now()->format('d M Y')),
  expiry_date: @json(now()->endOfYear()->format('d M Y')),
  registration_year: @json((string) now()->year),
  registrar_name: 'J. Mutasa',
  registrar_signature: '___________',
  commission_stamp: 'STAMP',
  verification_qr: 'QR',
  certificate_title: 'CERTIFICATE OF REGISTRATION',
  official_wording: 'Issued in terms of the Zimbabwe Media Commission Act.',
  licence_conditions: 'Subject to the provisions of the Zimbabwe Media Commission Act.',
};
const SAMPLE = IS_CARD ? SAMPLE_CARD : SAMPLE_CERT;
const PREVIEW_DATA = { ...SAMPLE };
let activePreviewData = { ...PREVIEW_DATA };

const DESIGN_COLOR_PALETTE = [
  '#ffffff', '#f8fafc', '#f1f5f9', '#e2e8f0', '#cbd5e1', '#94a3b8', '#64748b', '#475569', '#334155', '#0f172a', '#111827', '#000000',
  '#fee2e2', '#fecaca', '#fca5a5', '#f87171', '#ef4444', '#dc2626', '#b91c1c', '#991b1b',
  '#ffedd5', '#fed7aa', '#fdba74', '#fb923c', '#f97316', '#ea580c', '#c2410c', '#9a3412',
  '#fef3c7', '#fde68a', '#fcd34d', '#fbbf24', '#f59e0b', '#d97706', '#b45309', '#92400e',
  '#fef9c3', '#fef08a', '#fde047', '#facc15', '#eab308', '#ca8a04', '#a16207', '#854d0e',
  '#dcfce7', '#bbf7d0', '#86efac', '#4ade80', '#22c55e', '#16a34a', '#15803d', '#166534',
  '#ccfbf1', '#99f6e4', '#5eead4', '#2dd4bf', '#14b8a6', '#0f766e', '#115e59', '#134e4a',
  '#cffafe', '#a5f3fc', '#67e8f9', '#22d3ee', '#06b6d4', '#0891b2', '#155e75', '#164e63',
  '#e0f2fe', '#bae6fd', '#7dd3fc', '#38bdf8', '#0ea5e9', '#0284c7', '#0369a1', '#075985',
  '#dbeafe', '#bfdbfe', '#93c5fd', '#60a5fa', '#3b82f6', '#2563eb', '#1d4ed8', '#1e40af',
  '#e0e7ff', '#c7d2fe', '#a5b4fc', '#818cf8', '#6366f1', '#4f46e5', '#4338ca', '#3730a3',
  '#ede9fe', '#ddd6fe', '#c4b5fd', '#a78bfa', '#8b5cf6', '#7c3aed', '#6d28d6', '#5b21b6',
  '#fce7f3', '#fbcfe8', '#f9a8d4', '#f472b6', '#ec4899', '#db2777', '#be185d', '#9d174d',
  '#ffe4e6', '#fecdd3', '#fda4af', '#fb7185', '#f43f5e', '#e11d48', '#be123c', '#9f1239'
];

const autosaveEl = document.getElementById('autosaveStatus');
const zoomLabelEl = document.getElementById('zoomLabel');
const zoomStatusEl = document.getElementById('zoomStatus');
const objCountEl = document.getElementById('objCount');
const lastSavedEl = document.getElementById('lastSaved');
const bgImageStatusEl = document.getElementById('bgImageStatus');

function fmtTime(d) {
  try { return d.toLocaleString(); } catch (e) { return ''; }
}

const canvas = new fabric.Canvas('designCanvas', {
  width: CANVAS_W,
  height: CANVAS_H,
  backgroundColor: '#ffffff',
  preserveObjectStacking: true,
  selection: true,
});

// Keep flag inputs in sync while editing
canvas.on('object:modified', function (e) {
  const obj = e.target;
  if (!obj) return;
  if (obj.elemType === 'zimbabwe_flag' || obj === window.ZIM_FLAG_OBJ) {
    // update inputs
    const scaledW = obj.getScaledWidth();
    const scaledH = obj.getScaledHeight();
    const left = obj.left;
    const top = obj.top;
    document.getElementById('zimFlagWidth').value = Math.round((scaledW / CANVAS_W) * 100);
    document.getElementById('zimFlagHeight').value = Math.round((scaledH / CANVAS_H) * 100);
    document.getElementById('zimFlagLeft').value = Math.round((left / CANVAS_W) * 100);
    document.getElementById('zimFlagTop').value = Math.round((top / CANVAS_H) * 100);
    // store settings
    window.ZIM_FLAG_SETTINGS = {
      widthPct: parseFloat(document.getElementById('zimFlagWidth').value),
      heightPct: parseFloat(document.getElementById('zimFlagHeight').value),
      leftPct: parseFloat(document.getElementById('zimFlagLeft').value),
      topPct: parseFloat(document.getElementById('zimFlagTop').value),
    };
    markDirty();
  }
});

canvas.on('object:moving', function (e) {
  const obj = e.target;
  if (!obj) return;
  if ((obj.elemType === 'zimbabwe_flag' || obj === window.ZIM_FLAG_OBJ) && window.ZIM_FLAG_EDITING) {
    document.getElementById('zimFlagLeft').value = Math.round((obj.left / CANVAS_W) * 100);
    document.getElementById('zimFlagTop').value = Math.round((obj.top / CANVAS_H) * 100);
  }
});

canvas.on('object:scaling', function (e) {
  const obj = e.target;
  if (!obj) return;
  if ((obj.elemType === 'zimbabwe_flag' || obj === window.ZIM_FLAG_OBJ) && window.ZIM_FLAG_EDITING) {
    document.getElementById('zimFlagWidth').value = Math.round((obj.getScaledWidth() / CANVAS_W) * 100);
    document.getElementById('zimFlagHeight').value = Math.round((obj.getScaledHeight() / CANVAS_H) * 100);
  }
});

const bgColorPicker = document.getElementById('bgColorPicker');
const PALETTE_DEFAULT_COUNT = 15;
const paletteState = {
  bgPaletteGrid: false,
  selectedPaletteGrid: false,
};
const paletteCallbacks = {
  bgPaletteGrid: (color) => setCanvasBackground(color),
  selectedPaletteGrid: (color) => applyPaletteToSelection('fill', color),
};

function renderPaletteGrid(containerId, onSelectColor, expanded = false) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = '';
  const colors = expanded ? DESIGN_COLOR_PALETTE : DESIGN_COLOR_PALETTE.slice(0, PALETTE_DEFAULT_COUNT);
  colors.forEach((color) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'ds-palette-swatch';
    button.title = color;
    button.style.background = color;
    button.onclick = () => onSelectColor(color);
    container.appendChild(button);
  });
}

function renderPaletteWithToggle(containerId, toggleId) {
  const expanded = Boolean(paletteState[containerId]);
  renderPaletteGrid(containerId, paletteCallbacks[containerId], expanded);
  const toggle = document.getElementById(toggleId);
  if (!toggle) return;

  if (expanded) {
    toggle.textContent = 'Show less colors';
  } else {
    toggle.textContent = 'More colors';
  }
}

window.togglePaletteExpansion = function(containerId, toggleId) {
  paletteState[containerId] = !paletteState[containerId];
  renderPaletteWithToggle(containerId, toggleId);
};

function renderDesignPalettes() {
  renderPaletteWithToggle('bgPaletteGrid', 'bgPaletteToggle');
  renderPaletteWithToggle('selectedPaletteGrid', 'selectedPaletteToggle');
}

let zoom = 0.5;
function applyZoom(z) {
  zoom = Math.max(0.1, Math.min(3, z));
  canvas.setZoom(zoom);
  canvas.setWidth(CANVAS_W * zoom);
  canvas.setHeight(CANVAS_H * zoom);
  document.getElementById('canvasWrap').style.width = (CANVAS_W * zoom) + 'px';
  document.getElementById('canvasWrap').style.height = (CANVAS_H * zoom) + 'px';
  const p = Math.round(zoom * 100);
  zoomLabelEl.textContent = p + '%';
  zoomStatusEl.textContent = 'Zoom: ' + p + '%';
  updateGuides();
  canvas.requestRenderAll();
}

function updateGuides() {
  const safe = document.querySelector('.ds-safe-margin');
  if (!safe) return;
  const m = IS_CARD ? 10 : 20;
  safe.style.top = (m * zoom) + 'px';
  safe.style.left = (m * zoom) + 'px';
  safe.style.right = (m * zoom) + 'px';
  safe.style.bottom = (m * zoom) + 'px';
}

window.zoomFit = function () {
  const centre = document.getElementById('canvasCentre');
  const pad = 60;
  const maxW = Math.max(200, centre.clientWidth - pad);
  applyZoom(maxW / CANVAS_W);
};
window.zoomIn = () => applyZoom(zoom + 0.1);
window.zoomOut = () => applyZoom(zoom - 0.1);
applyZoom(0.5);
renderDesignPalettes();

function loadBackground(url) {
  // The uploaded file is persisted separately as background_file. Remove any
  // deserialised copy first so backgrounds are not duplicated after reload.
  canvas.getObjects().filter((obj) => obj.elemType === 'background_image').forEach((obj) => canvas.remove(obj));
  window.BG_IMAGE_OBJ = null;

  if (!url) {
    if (bgImageStatusEl) bgImageStatusEl.textContent = 'No background image selected.';
    canvas.requestRenderAll();
    refreshLayers();
    return;
  }

  if (bgImageStatusEl) bgImageStatusEl.textContent = 'Loading background...';

  try {
    const src = String(url);
    const isBlob = src.startsWith('blob:');
    const isData = src.startsWith('data:');

    const imgEl = new Image();
    // Only set crossOrigin for remote URLs (not blob/data)
    if (!isBlob && !isData) imgEl.crossOrigin = 'anonymous';

    imgEl.onload = function () {
      try {
        const fabricImg = new fabric.Image(imgEl, { left: 0, top: 0, selectable: false, evented: false, originX: 'left', originY: 'top' });
        const naturalW = imgEl.naturalWidth || fabricImg.width || CANVAS_W;
        const naturalH = imgEl.naturalHeight || fabricImg.height || CANVAS_H;
        const scaleX = CANVAS_W / naturalW;
        const scaleY = CANVAS_H / naturalH;
        let finalScaleX = 1, finalScaleY = 1, left = 0, top = 0;

        // Apply sizing mode
        if (window.BG_MODE === 'contain') {
          const s = Math.min(scaleX, scaleY);
          finalScaleX = finalScaleY = s;
          const w = naturalW * s; const h = naturalH * s;
          left = Math.round((CANVAS_W - w) / 2);
          top = Math.round((CANVAS_H - h) / 2);
        } else if (window.BG_MODE === 'stretch') {
          finalScaleX = scaleX;
          finalScaleY = scaleY;
          left = 0; top = 0;
        } else if (window.BG_MODE === 'custom') {
          const c = window.BG_CUSTOM || { widthPct:50, heightPct:50, leftPct:25, topPct:25 };
          finalScaleX = (CANVAS_W * (c.widthPct/100)) / naturalW;
          finalScaleY = (CANVAS_H * (c.heightPct/100)) / naturalH;
          left = Math.round(CANVAS_W * (c.leftPct/100));
          top = Math.round(CANVAS_H * (c.topPct/100));
        } else {
          // default: original aspect ratio, fully visible inside the canvas
          const s = Math.min(scaleX, scaleY);
          finalScaleX = finalScaleY = s;
          const w = naturalW * s; const h = naturalH * s;
          left = Math.round((CANVAS_W - w) / 2);
          top = Math.round((CANVAS_H - h) / 2);
        }

        fabricImg.set({ scaleX: finalScaleX, scaleY: finalScaleY, left: left, top: top, selectable: true, evented: true, elemType: 'background_image' });
        
        // Remove old background image if it exists
        if (window.BG_IMAGE_OBJ && canvas.contains(window.BG_IMAGE_OBJ)) {
          canvas.remove(window.BG_IMAGE_OBJ);
        }
        
        // Add as regular canvas object so it can be dragged/resized
        canvas.add(fabricImg);
        window.BG_IMAGE_OBJ = fabricImg;
        canvas.sendToBack(fabricImg);
        canvas.requestRenderAll();
        refreshLayers();
        if (bgImageStatusEl) bgImageStatusEl.textContent = 'Background image loaded (drag to move, resize to scale).';
        } catch (err) {
          console.error('Failed to create fabric image from element:', err);
          if (bgImageStatusEl) bgImageStatusEl.textContent = 'Failed to set background image (see console).';
        }
      };
      imgEl.onerror = function (evt) {
        console.error('Background image failed to load for src:', src, evt);
        if (bgImageStatusEl) bgImageStatusEl.textContent = 'Failed to load background image (see console).';
      };
    imgEl.src = src;
  } catch (e) {
    if (bgImageStatusEl) bgImageStatusEl.textContent = 'Failed to load background image.';
  }
}

function setCanvasBackground(color) {
  const normalized = color || '#ffffff';
  canvas.backgroundColor = normalized;
  if (bgColorPicker) bgColorPicker.value = normalized;
  canvas.requestRenderAll();
  markDirty();
}

window.setCanvasBackground = setCanvasBackground;

window.resetCanvasBackground = function () {
  setCanvasBackground('#ffffff');
};

window.applyPaletteToSelection = function (target, color) {
  const objs = (canvas.getActiveObjects && canvas.getActiveObjects()) || (canvas.getActiveObject() ? [canvas.getActiveObject()] : []);
  if (!objs || objs.length === 0) return;
  if (!color) return;

  const isStroke = target === 'stroke';

  objs.forEach((o) => {
    const applyTo = (it) => {
      if (!it) return;
      // Lines usually only have stroke
      if (isStroke || it.type === 'line') {
        if (typeof it.set === 'function') it.set('stroke', color); else it.stroke = color;
        const sEl = document.getElementById('p_stroke'); if (sEl) sEl.value = color;
      } else {
        if (typeof it.set === 'function') it.set('fill', color); else it.fill = color;
        const fEl = document.getElementById('p_fill'); if (fEl) fEl.value = color;
      }
      if (typeof it.setCoords === 'function') it.setCoords();
    };

    if (o.type === 'activeSelection' || o.type === 'group') {
      o.forEachObject((child) => applyTo(child));
    } else {
      applyTo(o);
    }
  });

  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();
};

function buildLinearGradientObject(startColor, endColor, width, height) {
  try {
    return new fabric.Gradient({
      type: 'linear',
      coords: {
        x1: 0,
        y1: 0,
        x2: width || 1,
        y2: height || 1,
      },
      colorStops: [
        { offset: 0, color: startColor },
        { offset: 1, color: endColor },
      ],
    });
  } catch (error) {
    return null;
  }
}

window.applyGradientToSelection = function (target) {
  const objs = (canvas.getActiveObjects && canvas.getActiveObjects()) || (canvas.getActiveObject() ? [canvas.getActiveObject()] : []);
  if (!objs || objs.length === 0) return;

  const startColor = (document.getElementById('p_fill_grad_start') || { value: '#ffffff' }).value;
  const endColor = (document.getElementById('p_fill_grad_end') || { value: '#000000' }).value;

  objs.forEach((obj) => {
    if (!obj) return;
    const width = (obj.getScaledWidth && obj.getScaledWidth()) || (obj.width || 1);
    const height = (obj.getScaledHeight && obj.getScaledHeight()) || (obj.height || 1);

    const gradient = buildLinearGradientObject(startColor, endColor, width, height);
    if (!gradient) return;

    if (target === 'stroke' || obj.type === 'line') {
      if (typeof obj.set === 'function') obj.set('stroke', gradient); else obj.stroke = gradient;
      const sEl = document.getElementById('p_stroke'); if (sEl) sEl.value = startColor;
    } else {
      if (typeof obj.set === 'function') obj.set('fill', gradient); else obj.fill = gradient;
      const fEl = document.getElementById('p_fill'); if (fEl) fEl.value = startColor;
    }

    if (typeof obj.setCoords === 'function') obj.setCoords();
  });

  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();
};

function triggerPictureUpload() {
  document.getElementById('pictureUploadInput').click();
}
window.triggerPictureUpload = triggerPictureUpload;

function insertPictureFromFile(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  const reader = new FileReader();
  reader.onload = function (evt) {
    fabric.Image.fromURL(evt.target.result, function (img) {
      const maxW = Math.min(CANVAS_W * 0.45, 300);
      const scale = maxW / img.width;
      img.set({
        left: CANVAS_W / 2 - (img.width * scale) / 2,
        top: CANVAS_H / 2 - (img.height * scale) / 2,
        scaleX: scale,
        scaleY: scale,
        elemType: 'picture',
        fieldKey: 'inserted_image',
        fieldType: 'image',
        fieldLabel: file.name.replace(/\.[^.]+$/, ''),
      });
      canvas.add(img);
      canvas.setActiveObject(img);
      canvas.requestRenderAll();
      refreshLayers();
      updateObjCount();
      markDirty();
    }, { crossOrigin: 'anonymous' });
  };
  reader.readAsDataURL(file);
  input.value = '';
}
window.insertPictureFromFile = insertPictureFromFile;

function createShapeForType(type, cx, cy) {
  const shared = { fill: 'rgba(200,200,200,0.3)', stroke: '#94a3b8', strokeWidth: 1 };

  switch (type) {
    case 'rect':
      return new fabric.Rect({ left: cx - 60, top: cy - 30, width: 120, height: 60, rx: 0, ry: 0, ...shared, elemType: 'rect' });
    case 'rounded_rect':
      return new fabric.Rect({ left: cx - 60, top: cy - 30, width: 120, height: 60, rx: 14, ry: 14, ...shared, elemType: 'rounded_rect' });
    case 'circle':
      return new fabric.Circle({ left: cx - 40, top: cy - 40, radius: 40, ...shared, elemType: 'circle' });
    case 'ellipse':
      return new fabric.Ellipse({ rx: 60, ry: 36, left: cx - 60, top: cy - 36, ...shared, elemType: 'ellipse' });
    case 'triangle':
      return new fabric.Triangle({ left: cx - 45, top: cy - 40, width: 90, height: 80, ...shared, elemType: 'triangle' });
    case 'diamond':
      return new fabric.Polygon([
        { x: 0, y: 45 },
        { x: 45, y: 0 },
        { x: 90, y: 45 },
        { x: 45, y: 90 },
      ], { left: cx - 45, top: cy - 45, ...shared, elemType: 'diamond' });
    case 'hexagon':
      return new fabric.Polygon([
        { x: 46, y: 0 },
        { x: 92, y: 26 },
        { x: 92, y: 74 },
        { x: 46, y: 100 },
        { x: 0, y: 74 },
        { x: 0, y: 26 },
      ], { left: cx - 46, top: cy - 50, ...shared, elemType: 'hexagon' });
    case 'pentagon':
      return new fabric.Polygon([
        { x: 50, y: 0 },
        { x: 100, y: 35 },
        { x: 82, y: 100 },
        { x: 18, y: 100 },
        { x: 0, y: 35 },
      ], { left: cx - 50, top: cy - 50, ...shared, elemType: 'pentagon' });
    case 'arrow':
      return new fabric.Polygon([
        { x: 0, y: 24 },
        { x: 48, y: 24 },
        { x: 48, y: 0 },
        { x: 96, y: 50 },
        { x: 48, y: 100 },
        { x: 48, y: 76 },
        { x: 0, y: 76 },
      ], { left: cx - 48, top: cy - 50, ...shared, elemType: 'arrow' });
    case 'heart':
      return new fabric.Path('M 50 20 C 65 5, 90 5, 100 20 C 110 35, 100 55, 80 70 C 60 85, 50 100, 50 100 C 50 100, 40 85, 20 70 C 0 55, -10 35, 0 20 C 10 5, 35 5, 50 20 Z', {
        left: cx - 50,
        top: cy - 45,
        scaleX: 1,
        scaleY: 1,
        ...shared,
        elemType: 'heart',
      });
    case 'star':
      return new fabric.Polygon([
        { x: 0, y: -48 },
        { x: 14, y: -14 },
        { x: 48, y: -14 },
        { x: 20, y: 8 },
        { x: 28, y: 44 },
        { x: 0, y: 24 },
        { x: -28, y: 44 },
        { x: -20, y: 8 },
        { x: -48, y: -14 },
        { x: -14, y: -14 },
      ], { left: cx - 7, top: cy - 8, ...shared, elemType: 'star' });
    case 'line':
      return new fabric.Line([cx - 80, cy, cx + 80, cy], { stroke: '#1e293b', strokeWidth: 2, elemType: 'line' });
    default:
      return null;
  }
}

function clearBackgroundWatermarks() {
  const backgrounds = canvas.getObjects().filter((obj) => obj.elemType === 'background_watermark');
  backgrounds.forEach((obj) => canvas.remove(obj));
  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();
}
window.clearBackgroundWatermarks = clearBackgroundWatermarks;

function parseColorToRgb(color) {
  const input = String(color || '#ffffff').trim();
  const hex = input.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
  if (hex) {
    const value = hex[1];
    const expanded = value.length === 3
      ? value.split('').map(ch => ch + ch).join('')
      : value;
    return {
      r: parseInt(expanded.slice(0, 2), 16),
      g: parseInt(expanded.slice(2, 4), 16),
      b: parseInt(expanded.slice(4, 6), 16),
    };
  }

  const rgb = input.match(/rgba?\(([^)]+)\)/i);
  if (rgb) {
    const parts = rgb[1].split(',').map((part) => parseFloat(part.trim()));
    if (parts.length >= 3) {
      return { r: parts[0], g: parts[1], b: parts[2] };
    }
  }

  return { r: 255, g: 255, b: 255 };
}

function getWatermarkFillColor() {
  const { r, g, b } = parseColorToRgb(canvas.backgroundColor || '#ffffff');
  const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
  return luminance > 0.6 ? 'rgba(15,23,42,0.28)' : 'rgba(255,255,255,0.22)';
}

function applyBackgroundWatermarks() {
  const text = document.getElementById('bgWatermarkText').value.trim() || 'ZMC OFFICIAL';
  clearBackgroundWatermarks();
  const cols = 5;
  const rows = 4;
  const gapX = 180;
  const gapY = 140;
  const startX = 40;
  const startY = 40;

  for (let row = 0; row < rows; row++) {
    for (let col = 0; col < cols; col++) {
      const x = startX + (col * gapX) + (row % 2 === 0 ? 20 : 0);
      const y = startY + (row * gapY);
      if (x > CANVAS_W - 80 || y > CANVAS_H - 50) continue;
      const obj = new fabric.Text(text, {
        left: x,
        top: y,
        fontSize: 28,
        fontFamily: 'Arial',
        fontWeight: 'bold',
        fill: getWatermarkFillColor(),
        angle: -28,
        selectable: false,
        evented: false,
        opacity: 0.45,
        elemType: 'background_watermark',
      });
      canvas.add(obj);
      canvas.sendToBack(obj);
    }
  }

  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();
}
window.applyBackgroundWatermarks = applyBackgroundWatermarks;

loadBackground(BG_URL);

let history = [], histIdx = -1, suppressSnapshot = false;
let dirty = false;
let autosaveTimer = null;

function snapshot() {
  if (suppressSnapshot) return;
  const json = canvas.toJSON(['fieldKey', 'fieldType', 'fieldLabel', 'elemType', 'zmcFlagManual']);
  history = history.slice(0, histIdx + 1);
  history.push(JSON.stringify(json));
  if (history.length > 80) history.shift();
  histIdx = history.length - 1;
  markDirty();
}

function markDirty() {
  dirty = true;
  autosaveEl.textContent = 'Unsaved changes';
  autosaveEl.classList.add('saving');
  autosaveEl.classList.remove('saved');
  scheduleAutosave();
}

function markSaved() {
  dirty = false;
  autosaveEl.textContent = 'All changes saved';
  autosaveEl.classList.remove('saving');
  autosaveEl.classList.add('saved');
  lastSavedEl.textContent = 'Saved: ' + fmtTime(new Date());
}

function scheduleAutosave() {
  if (autosaveTimer) return;
  autosaveTimer = setTimeout(async () => {
    autosaveTimer = null;
    if (dirty) await saveDraft(true);
  }, 20000);
}

canvas.on('object:added', snapshot);
canvas.on('object:modified', snapshot);
canvas.on('object:removed', snapshot);
canvas.on('selection:created', syncPropsFromSelection);
canvas.on('selection:updated', syncPropsFromSelection);
canvas.on('selection:cleared', syncPropsFromSelection);

window.undoAction = function () {
  if (histIdx <= 0) return;
  histIdx--;
  suppressSnapshot = true;
  canvas.loadFromJSON(JSON.parse(history[histIdx]), function () {
    suppressSnapshot = false;
    canvas.requestRenderAll();
    refreshLayers();
    updateObjCount();
    syncPropsFromSelection();
    markDirty();
  });
};
window.redoAction = function () {
  if (histIdx >= history.length - 1) return;
  histIdx++;
  suppressSnapshot = true;
  canvas.loadFromJSON(JSON.parse(history[histIdx]), function () {
    suppressSnapshot = false;
    canvas.requestRenderAll();
    refreshLayers();
    updateObjCount();
    syncPropsFromSelection();
    markDirty();
  });
};

if (SAVED && SAVED.objects && SAVED.objects.length) {
  suppressSnapshot = true;
  canvas.loadFromJSON(SAVED, function () {
    suppressSnapshot = false;
    if (SAVED.backgroundColor) {
      canvas.backgroundColor = SAVED.backgroundColor;
    }
    if (bgColorPicker) bgColorPicker.value = canvas.backgroundColor || '#ffffff';
    loadBackground(BG_URL);
    // Remove oversized flags inserted by older versions. Explicitly added
    // flags carry zmcFlagManual and remain available for editing.
    canvas.getObjects()
      .filter((obj) => obj.elemType === 'zimbabwe_flag' && obj.zmcFlagManual !== true)
      .forEach((obj) => canvas.remove(obj));
    window.ZIM_FLAG_OBJ = canvas.getObjects().find((obj) => obj.elemType === 'zimbabwe_flag') || null;
    canvas.requestRenderAll();
    refreshLayers();
    updateObjCount();
    history = [JSON.stringify(canvas.toJSON(['fieldKey','fieldType','fieldLabel','elemType','zmcFlagManual']))];
    histIdx = 0;
    markSaved();
  });
} else {
  history = [JSON.stringify(canvas.toJSON(['fieldKey','fieldType','fieldLabel','elemType','zmcFlagManual']))];
  histIdx = 0;
  if (bgColorPicker) bgColorPicker.value = canvas.backgroundColor || '#ffffff';
  markSaved();
}

window.switchTab = function (tab) {
  ['elements','fields','layers'].forEach(t => {
    document.getElementById('tab-' + t)?.classList.toggle('active', t === tab);
    document.getElementById('panel-' + t)?.classList.toggle('active', t === tab);
  });
};

window.addElement = function (type) {
  let obj;
  const cx = CANVAS_W / 2, cy = CANVAS_H / 2;
  switch (type) {
    case 'text':
      obj = new fabric.IText('Text here', { left: cx - 60, top: cy - 12, fontSize: 18, fill: '#1e293b', fontFamily: 'Arial', elemType: 'text' });
      break;
    case 'heading':
      obj = new fabric.IText('HEADING', { left: cx - 80, top: cy - 16, fontSize: 28, fontWeight: 'bold', fill: '#1e293b', fontFamily: 'Arial', elemType: 'heading' });
      break;
    case 'rect':
    case 'rounded_rect':
    case 'circle':
    case 'ellipse':
    case 'triangle':
    case 'diamond':
    case 'hexagon':
    case 'pentagon':
    case 'arrow':
    case 'heart':
    case 'star':
      obj = createShapeForType(type, cx, cy);
      break;
    case 'line':
      obj = new fabric.Line([cx - 80, cy, cx + 80, cy], { stroke: '#1e293b', strokeWidth: 2, elemType: 'line' });
      break;
    case 'border':
      obj = new fabric.Rect({ left: 20, top: 20, width: CANVAS_W - 40, height: CANVAS_H - 40, fill: 'transparent', stroke: '#c9a86c', strokeWidth: 4, rx: 8, ry: 8, elemType: 'border' });
      break;
    case 'watermark':
      obj = new fabric.Text(document.getElementById('bgWatermarkText').value.trim() || 'ZMC OFFICIAL', { left: cx - 110, top: cy - 20, fontSize: 36, fill: 'rgba(0,0,0,0.08)', fontFamily: 'Arial', fontWeight: 'bold', angle: -30, selectable: true, elemType: 'watermark' });
      break;
    case 'logo':
      fabric.Image.fromURL(LOGO_URL, function (img) {
        img.set({ left: cx - 40, top: cy - 40, scaleX: 80 / img.width, scaleY: 80 / img.height, elemType: 'logo', fieldKey: 'zmc_logo', fieldType: 'image', fieldLabel: 'ZMC Logo' });
        canvas.add(img); canvas.setActiveObject(img); canvas.requestRenderAll(); refreshLayers(); updateObjCount();
      }, { crossOrigin: 'anonymous' });
      return;
    case 'image':
      triggerPictureUpload();
      return;
    default: return;
  }
  if (!obj) return;
  canvas.add(obj);
  canvas.setActiveObject(obj);
  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();
};

function placeholderGroup(label, fieldKey, fieldType, w, h) {
  const rect = new fabric.Rect({ left: 0, top: 0, width: w, height: h, fill: 'rgba(226,232,240,0.7)', stroke: '#94a3b8', strokeWidth: 1, rx: 4, ry: 4 });
  const txt  = new fabric.Text(label, { left: w/2, top: h/2, originX: 'center', originY: 'center', fontSize: 12, fill: '#334155' });
  return new fabric.Group([rect, txt], { left: 80, top: 80, elemType: 'dynamic', fieldKey, fieldType, fieldLabel: label });
}

window.addDynamicField = function (key, label, ftype, at) {
  const left = at?.x ?? (CANVAS_W / 2 - 80);
  const top  = at?.y ?? (CANVAS_H / 2 - 16);

  let obj;
  if (ftype === 'text' || ftype === 'date') {
    obj = new fabric.IText(label, { left, top, fontSize: 18, fill: '#0f172a', fontFamily: 'Arial', elemType: 'dynamic_text', fieldKey: key, fieldType: ftype, fieldLabel: label });
  } else if (ftype === 'qr') {
    obj = placeholderGroup('QR', key, ftype, 140, 140); obj.set({ left, top });
  } else if (ftype === 'photo') {
    obj = placeholderGroup('PHOTO', key, ftype, 220, 260); obj.set({ left, top });
  } else if (ftype === 'signature') {
    obj = placeholderGroup('SIGNATURE', key, ftype, 220, 70); obj.set({ left, top });
  } else if (ftype === 'stamp') {
    obj = placeholderGroup('STAMP', key, ftype, 160, 160); obj.set({ left, top });
  } else if (ftype === 'image') {
    obj = placeholderGroup('IMAGE', key, ftype, 160, 120); obj.set({ left, top });
  } else {
    obj = new fabric.IText(label, { left, top, fontSize: 18, fill: '#0f172a', fontFamily: 'Arial', elemType: 'dynamic_text', fieldKey: key, fieldType: ftype, fieldLabel: label });
  }
  canvas.add(obj);
  canvas.setActiveObject(obj);
  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();

};

document.querySelectorAll('.ds-chip').forEach(chip => {
  chip.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', JSON.stringify({ key: chip.dataset.key, label: chip.dataset.label, ftype: chip.dataset.ftype }));
  });
});

const wrap = document.getElementById('canvasWrap');
wrap.addEventListener('dragover', (e) => e.preventDefault());
wrap.addEventListener('drop', (e) => {
  e.preventDefault();
  try {
    const data = JSON.parse(e.dataTransfer.getData('text/plain'));
    const rect = wrap.getBoundingClientRect();
    const x = (e.clientX - rect.left) / zoom;
    const y = (e.clientY - rect.top) / zoom;
    window.addDynamicField(data.key, data.label, data.ftype, { x, y });
  } catch (err) {}
});

function layerLabel(o) {
  if (o.fieldKey && o.fieldLabel) return o.fieldLabel;
  if (o.elemType) return String(o.elemType).toUpperCase();
  return (o.type || 'object').toUpperCase();
}

function refreshLayers() {
  const list = document.getElementById('layersList');
  if (!list) return;
  list.innerHTML = '';
  const objs = canvas.getObjects().slice().reverse();
  const active = canvas.getActiveObject();
  objs.forEach((o) => {
    const div = document.createElement('div');
    div.className = 'ds-layer' + (active === o ? ' selected' : '');
    div.innerHTML = '<i class="ri-stack-line"></i><span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + layerLabel(o) + '</span>';
    div.onclick = () => { canvas.setActiveObject(o); canvas.requestRenderAll(); refreshLayers(); syncPropsFromSelection(); };
    list.appendChild(div);
  });
}

window.bringForward = () => { const o = canvas.getActiveObject(); if (!o) return; canvas.bringForward(o); canvas.requestRenderAll(); refreshLayers(); markDirty(); };
window.sendBackward = () => { const o = canvas.getActiveObject(); if (!o) return; canvas.sendBackwards(o); canvas.requestRenderAll(); refreshLayers(); markDirty(); };
window.bringToFront = () => { const o = canvas.getActiveObject(); if (!o) return; canvas.bringToFront(o); canvas.requestRenderAll(); refreshLayers(); markDirty(); };
window.sendToBack = () => { const o = canvas.getActiveObject(); if (!o) return; canvas.sendToBack(o); canvas.requestRenderAll(); refreshLayers(); markDirty(); };

function setPropPanelVisible(on) {
  document.getElementById('propPanel').style.display = on ? 'block' : 'none';
  document.getElementById('nothingSelected').style.display = on ? 'none' : 'block';
}

function syncPropsFromSelection() {
  const obj = canvas.getActiveObject();
  if (!obj) { setPropPanelVisible(false); refreshLayers(); return; }
  setPropPanelVisible(true);

  const isText = obj.type === 'i-text' || obj.type === 'text' || obj.type === 'textbox';
  document.getElementById('p_text').value = isText ? (obj.text || '') : (obj.fieldLabel || obj.elemType || obj.type || '');
  document.getElementById('p_x').value = Math.round(obj.left || 0);
  document.getElementById('p_y').value = Math.round(obj.top || 0);

  const w = obj.getScaledWidth ? obj.getScaledWidth() : (obj.width || 0);
  const h = obj.getScaledHeight ? obj.getScaledHeight() : (obj.height || 0);
  document.getElementById('p_w').value = Math.round(w);
  document.getElementById('p_h').value = Math.round(h);

  document.getElementById('p_fontSize').value = Math.round(obj.fontSize || 18);
  document.getElementById('p_fontWeight').value = obj.fontWeight || 'normal';
  document.getElementById('p_fontStyle').value = obj.fontStyle || 'normal';
  document.getElementById('p_underline').checked = !!obj.underline;
  document.getElementById('p_linethrough').checked = !!obj.linethrough;
  document.getElementById('p_fontFamily').value = obj.fontFamily || 'Arial';
  document.getElementById('p_charSpacing').value = Math.round(obj.charSpacing || 0);
  document.getElementById('p_lineHeight').value = obj.lineHeight || 1.16;
  document.getElementById('p_fill').value = typeof obj.fill === 'string' ? (obj.fill || '#000000') : '#0ea5e9';
  document.getElementById('p_stroke').value = typeof obj.stroke === 'string' ? (obj.stroke || '#000000') : '#000000';
  document.getElementById('p_strokeWidth').value = obj.strokeWidth || 0;
  document.getElementById('p_opacity').value = Math.round((obj.opacity ?? 1) * 100);
  document.getElementById('p_rotation').value = Math.round(obj.angle || 0);
  refreshLayers();
}

function setSize(obj, desiredW, desiredH) {
  if (!obj.width || !obj.height) return;
  obj.scaleX = desiredW / obj.width;
  obj.scaleY = desiredH / obj.height;
}

window.applyProp = function (prop) {
  const obj = canvas.getActiveObject();
  if (!obj) return;

  if (prop === 'text') {
    if (obj.type === 'i-text' || obj.type === 'text' || obj.type === 'textbox') {
      obj.text = document.getElementById('p_text').value;
    } else {
      obj.fieldLabel = document.getElementById('p_text').value;
    }
  }
  if (prop === 'x') obj.left = parseFloat(document.getElementById('p_x').value || 0);
  if (prop === 'y') obj.top = parseFloat(document.getElementById('p_y').value || 0);
  if (prop === 'w' || prop === 'h') {
    const w = parseFloat(document.getElementById('p_w').value || obj.getScaledWidth());
    const h = parseFloat(document.getElementById('p_h').value || obj.getScaledHeight());
    setSize(obj, w, h);
  }
  if (prop === 'fontSize') obj.fontSize = parseInt(document.getElementById('p_fontSize').value || 18, 10);
  if (prop === 'fontWeight') obj.fontWeight = document.getElementById('p_fontWeight').value;
  if (prop === 'fontStyle') obj.fontStyle = document.getElementById('p_fontStyle').value;
  if (prop === 'underline') obj.underline = document.getElementById('p_underline').checked;
  if (prop === 'linethrough') obj.linethrough = document.getElementById('p_linethrough').checked;
  if (prop === 'fontFamily') obj.fontFamily = document.getElementById('p_fontFamily').value;
  if (prop === 'charSpacing') obj.charSpacing = parseInt(document.getElementById('p_charSpacing').value || 0, 10);
  if (prop === 'lineHeight') obj.lineHeight = parseFloat(document.getElementById('p_lineHeight').value || 1.16);
  if (prop === 'fill') obj.fill = document.getElementById('p_fill').value;
  if (prop === 'stroke') obj.stroke = document.getElementById('p_stroke').value;
  if (prop === 'strokeWidth') obj.strokeWidth = parseInt(document.getElementById('p_strokeWidth').value || 0, 10);
  if (prop === 'opacity') obj.opacity = (parseInt(document.getElementById('p_opacity').value || 100, 10) / 100);
  if (prop === 'rotation') obj.angle = parseFloat(document.getElementById('p_rotation').value || 0);

  obj.setCoords();
  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();
};

window.setTextAlign = function (align) {
  const obj = canvas.getActiveObject();
  if (!obj) return;
  if (obj.textAlign !== undefined) obj.textAlign = align;
  canvas.requestRenderAll();
  markDirty();
};

window.alignObj = function (pos) {
  const obj = canvas.getActiveObject();
  if (!obj) return;
  if (pos === 'left') obj.left = 0;
  if (pos === 'center') obj.left = (CANVAS_W - obj.getScaledWidth()) / 2;
  if (pos === 'right') obj.left = CANVAS_W - obj.getScaledWidth();
  if (pos === 'top') obj.top = 0;
  if (pos === 'middle') obj.top = (CANVAS_H - obj.getScaledHeight()) / 2;
  if (pos === 'bottom') obj.top = CANVAS_H - obj.getScaledHeight();
  obj.setCoords();
  canvas.requestRenderAll();
  syncPropsFromSelection();
  markDirty();
};

window.deleteSelected = function () {
  const active = canvas.getActiveObject();
  if (!active) return;
  if (active.type === 'activeSelection') {
    active.forEachObject(o => canvas.remove(o));
    canvas.discardActiveObject();
  } else {
    canvas.remove(active);
  }
  canvas.requestRenderAll();
  refreshLayers();
  updateObjCount();
  markDirty();
};

function updateObjCount() {
  objCountEl.textContent = 'Objects: ' + canvas.getObjects().length;
}

window.uploadBackground = function (input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];

  // Preview immediately using object URL
  try {
    const url = URL.createObjectURL(file);
    if (bgImageStatusEl) bgImageStatusEl.textContent = 'Previewing background...';
    loadBackground(url);
    // release the object URL after a short delay (fabric will have loaded it)
    // keep object URL available a little longer to ensure fabric finishes loading
    setTimeout(() => { try { URL.revokeObjectURL(url); } catch (e) {} }, 60000);
  } catch (err) {
    // fall back to FileReader
    const reader = new FileReader();
    reader.onload = (evt) => {
      if (bgImageStatusEl) bgImageStatusEl.textContent = 'Previewing background...';
      loadBackground(evt.target.result);
    };
    reader.readAsDataURL(file);
  }

  // Keep the selected file in the same save request as the canvas JSON.
  document.getElementById('bgFileInput').files = input.files;
  if (bgImageStatusEl) bgImageStatusEl.textContent = 'Background ready — click Save & Use in Production.';
  markDirty();
};

let clipboard = null;
function copySelected() {
  const obj = canvas.getActiveObject();
  if (!obj) return;
  obj.clone(function (cloned) { clipboard = cloned; });
}
function pasteClipboard() {
  if (!clipboard) return;
  clipboard.clone(function (clonedObj) {
    canvas.discardActiveObject();
    clonedObj.set({ left: (clonedObj.left || 0) + 10, top: (clonedObj.top || 0) + 10, evented: true });
    if (clonedObj.type === 'activeSelection') {
      clonedObj.canvas = canvas;
      clonedObj.forEachObject(function (obj) { canvas.add(obj); });
      clonedObj.setCoords();
    } else {
      canvas.add(clonedObj);
    }
    canvas.setActiveObject(clonedObj);
    canvas.requestRenderAll();
    refreshLayers();
    updateObjCount();
    markDirty();
  });
}

function serializeLayout() {
  const layout = canvas.toJSON(['fieldKey', 'fieldType', 'fieldLabel', 'elemType', 'zmcFlagManual']);
  // The uploaded background is persisted as background_file. Do not keep a
  // blob/remote URL in Fabric JSON, otherwise it becomes stale or duplicated.
  layout.objects = (layout.objects || []).filter((obj) => obj.elemType !== 'background_image');
  layout.backgroundColor = canvas.backgroundColor || '#ffffff';
  return layout;
}

let saveInProgress = false;
async function saveDraft(isAutosave) {
  if (saveInProgress) return;
  saveInProgress = true;
  autosaveEl.textContent = isAutosave ? 'Auto-saving…' : 'Saving…';
  autosaveEl.classList.add('saving');
  const fd = new FormData();
  fd.append('_token', CSRF_TOKEN);
  fd.append('_method', 'PUT');
  fd.append('name', @json($template->name));
  fd.append('template_for', @json($template->template_for));
  fd.append('year', @json($template->year));
  fd.append('orientation', @json($template->orientation));
  const applicationType = @json($template->application_type);
  const templateWidth = @json($template->width);
  const templateHeight = @json($template->height);
  if (applicationType !== null && applicationType !== undefined) fd.append('application_type', applicationType);
  if (templateWidth !== null && templateWidth !== undefined) fd.append('width', templateWidth);
  if (templateHeight !== null && templateHeight !== undefined) fd.append('height', templateHeight);
  fd.append('layout_config', JSON.stringify(serializeLayout()));
  fd.append('save_version', '0');

  const backgroundInput = document.getElementById('bgFileInput');
  if (backgroundInput?.files?.[0]) {
    fd.append('background', backgroundInput.files[0], backgroundInput.files[0].name);
  }

  try {
    const res = await fetch(UPDATE_URL, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) throw new Error('save failed');
    markSaved();
    // The file is now stored on the template; avoid re-uploading it on every
    // autosave while retaining it when a save fails so the user can retry.
    if (backgroundInput?.files?.[0]) {
      backgroundInput.value = '';
      const uploadInput = document.getElementById('bgUploadInput');
      if (uploadInput) uploadInput.value = '';
    }
    if (!isAutosave) autosaveEl.textContent = 'Saved & active in production';
  } catch (error) {
    console.error('Design Studio save failed:', error);
    autosaveEl.textContent = 'Save failed — please try again';
    autosaveEl.classList.remove('saving');
  } finally {
    saveInProgress = false;
  }
}
window.saveDraft = () => saveDraft(false);

function previewWithData(data, title) {
  const modalEl = document.getElementById('dsPreviewModal');
  const modal = new bootstrap.Modal(modalEl);
  const titleEl = document.getElementById('dsPreviewModalTitle');
  if (titleEl) titleEl.textContent = title;

  const previewCanvasEl = document.getElementById('previewCanvas');
  previewCanvasEl.width = CANVAS_W;
  previewCanvasEl.height = CANVAS_H;

  const c = new fabric.StaticCanvas('previewCanvas', { width: CANVAS_W, height: CANVAS_H, backgroundColor: '#fff' });
  const renderObjects = () => {
    c.loadFromJSON(serializeLayout(), function () {
      c.getObjects().forEach(o => {
        if (o.fieldKey) {
          const displayText = (data[o.fieldKey] ?? o.fieldLabel ?? '');
          if (o.type === 'i-text' || o.type === 'text' || o.type === 'textbox') {
            o.text = displayText;
          } else if (o.type === 'group') {
            (o._objects || []).forEach(child => {
              if (child.type === 'text' && o.fieldType !== 'qr') {
                child.text = displayText;
              }
            });
          }
        }
      });
      c.renderAll();
    });
  };

  if (BG_URL) {
    fabric.Image.fromURL(BG_URL, function (img) {
      img.set({ left: 0, top: 0, selectable: false });
      const naturalW = img.width || CANVAS_W;
      const naturalH = img.height || CANVAS_H;
      const scale = Math.min(CANVAS_W / naturalW, CANVAS_H / naturalH);
      img.scale(scale);
      img.set({
        left: (CANVAS_W - naturalW * scale) / 2,
        top: (CANVAS_H - naturalH * scale) / 2,
      });
      c.setBackgroundImage(img, function () { renderObjects(); });
    }, { crossOrigin: 'anonymous' });
  } else {
    renderObjects();
  }

  modal.show();
}

window.previewSampleData = function () {
  previewWithData(SAMPLE, 'Preview (Sample Data)');
};

window.openPreviewDataEditor = function () {
  const textarea = document.getElementById('previewDataJson');
  if (!textarea) return;
  textarea.value = JSON.stringify(activePreviewData, null, 2);
  const modal = new bootstrap.Modal(document.getElementById('dsPreviewDataModal'));
  modal.show();
};

window.savePreviewData = function () {
  const textarea = document.getElementById('previewDataJson');
  if (!textarea) return;
  try {
    const parsed = JSON.parse(textarea.value);
    if (typeof parsed === 'object' && parsed !== null) {
      activePreviewData = parsed;
      const modalEl = document.getElementById('dsPreviewDataModal');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();
      previewWithData(activePreviewData, 'Preview (Actual Data)');
    } else {
      alert('Preview data must be a JSON object.');
    }
  } catch (error) {
    alert('Invalid JSON: ' + error.message);
  }
};

window.resetPreviewData = function () {
  activePreviewData = { ...PREVIEW_DATA };
  const textarea = document.getElementById('previewDataJson');
  if (textarea) textarea.value = JSON.stringify(activePreviewData, null, 2);
};

window.togglePropertiesPanel = function () {
  const panel = document.getElementById('propertiesPanel');
  const main = document.querySelector('.ds-main');
  if (!panel || !main) return;
  const collapsed = panel.classList.toggle('is-collapsed');
  main.classList.toggle('properties-collapsed', collapsed);
  sessionStorage.setItem('zmc_design_properties_collapsed', collapsed ? '1' : '0');
  setTimeout(() => {
    if (typeof c !== 'undefined' && c) {
      c.calcOffset();
      c.requestRenderAll();
    }
  }, 220);
};

if (sessionStorage.getItem('zmc_design_properties_collapsed') === '1') {
  window.togglePropertiesPanel();
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Delete' || e.key === 'Backspace') {
    if (document.activeElement && ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) return;
    window.deleteSelected();
  }
  if (e.ctrlKey && (e.key === 's' || e.key === 'S')) { e.preventDefault(); window.saveDraft(); }
  if (e.ctrlKey && (e.key === 'z' || e.key === 'Z')) { e.preventDefault(); window.undoAction(); }
  if (e.ctrlKey && (e.key === 'y' || e.key === 'Y')) { e.preventDefault(); window.redoAction(); }
  if (e.ctrlKey && (e.key === 'c' || e.key === 'C')) { e.preventDefault(); copySelected(); }
  if (e.ctrlKey && (e.key === 'v' || e.key === 'V')) { e.preventDefault(); pasteClipboard(); }
});

refreshLayers();
updateObjCount();
updateGuides();

})();
</script>

@endsection
