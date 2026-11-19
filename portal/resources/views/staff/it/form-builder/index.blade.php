@extends('layouts.portal')

@section('title', 'Application Form Editor')

@section('content')
@php
    $maxFormSteps = \App\Models\CustomFormField::stepCounts()[$formType] ?? 1;
    $selectedStepLabel = $stepLabels[$previewStep] ?? 'Selected step';
    $selectedStepFieldCount = $fields->where('step_number', $previewStep)->count();
@endphp
<div class="container-fluid px-4" id="application-form-editor">

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-line" style="font-size:18px;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
            <i class="ri-error-warning-line" style="font-size:18px;"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger mb-3" role="alert">
            <div class="fw-semibold">Please correct the form settings:</div>
            <ul class="mb-0 mt-1">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <header class="editor-hero">
        <div class="editor-hero-copy">
            <span class="editor-eyebrow"><i class="ri-settings-3-line"></i> IT Admin · Configuration</span>
            <h1>Application Form Editor</h1>
            <p>Review the applicant experience, add fields to the right step, and publish when you’re ready.</p>
        </div>
        <div class="editor-hero-actions">
            <button class="btn editor-primary-button" data-bs-toggle="modal" data-bs-target="#addFieldModal" data-add-field-step="{{ $previewStep }}">
                <i class="ri-add-line"></i> Add field
            </button>
            <a href="{{ route('staff.it.dashboard') }}" class="btn editor-back-button">
                <i class="ri-arrow-left-line"></i> Back to IT Admin
            </a>
        </div>
    </header>

    <section class="editor-form-picker" aria-label="Choose an application form">
        <div class="editor-picker-label"><i class="ri-layout-grid-line"></i><span>Application form</span></div>
        <nav class="editor-form-tabs">
            @foreach($formTypes as $key => $label)
                <a href="{{ route('staff.it.form-builder', ['form_type' => $key]) }}"
                   class="editor-form-tab {{ $formType === $key ? 'is-active' : '' }}" @if($formType === $key) aria-current="page" @endif>
                    <span>{{ $label }}</span>
                    @if($formType === $key)<i class="ri-check-line" aria-hidden="true"></i>@endif
                </a>
            @endforeach
        </nav>
    </section>

    <div class="editor-workspace">
        <section class="editor-panel editor-preview-panel" id="applicantFormPreviewCard">
            <div class="editor-panel-heading">
                <div class="editor-panel-title">
                    <span class="editor-heading-icon"><i class="ri-eye-line"></i></span>
                    <div>
                        <h2>Applicant form preview</h2>
                        <p>Explore the real form and select the step where your field belongs.</p>
                    </div>
                </div>
                <div class="editor-preview-controls">
                    <label for="formPreviewStep">Preview step</label>
                    <select class="form-select" id="formPreviewStep">
                        @foreach($stepLabels as $stepNumber => $stepLabel)
                            <option value="{{ $stepNumber }}" @selected($previewStep === $stepNumber)>{{ $stepNumber }} · {{ $stepLabel }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn editor-primary-button" id="addFieldAtPreviewStep" data-step="{{ $previewStep }}">
                        <i class="ri-add-line"></i><span>Add to step</span>
                    </button>
                </div>
            </div>
            <div class="editor-preview-canvas">
                <div class="form-preview-frame-wrap">
                    <iframe
                        id="applicantFormPreview"
                        title="{{ $formTypes[$formType] ?? $formType }} applicant form preview"
                        src="{{ route('staff.it.form-builder.preview', ['form_type' => $formType, 'step' => $previewStep]) }}"
                        loading="lazy"></iframe>
                </div>
                <div class="editor-preview-caption"><i class="ri-information-line"></i><span>This is a read-only preview. Extra fields appear at the end of the selected step and stay in draft until published.</span></div>
            </div>
        </section>

        <aside class="editor-sidebar">
            <section class="editor-panel editor-step-summary">
                <div class="editor-side-label"><i class="ri-focus-3-line"></i> Current selection</div>
                <div class="editor-step-number">{{ $previewStep }}</div>
                <h2>{{ $selectedStepLabel }}</h2>
                <p>{{ $selectedStepFieldCount }} additional {{ \Illuminate\Support\Str::plural('field', $selectedStepFieldCount) }} currently assigned to this step.</p>
                <button type="button" class="btn editor-secondary-button" data-bs-toggle="modal" data-bs-target="#addFieldModal" data-add-field-step="{{ $previewStep }}">
                    <i class="ri-add-line"></i> Add a field here
                </button>
            </section>

            <section class="editor-panel editor-publish-panel">
                <div class="editor-publish-topline">
                    <div class="editor-side-label"><i class="ri-git-branch-line"></i> Live version</div>
                    <span class="editor-version-tag">v{{ $state->version }}</span>
                </div>
                <p class="editor-publish-meta">
                    @if($state->published_at)
                        Published {{ $state->published_at->format('d M Y, H:i') }}{{ $state->publisher ? ' · ' . $state->publisher->name : '' }}
                    @else
                        No version has been published yet.
                    @endif
                </p>
                <div class="editor-draft-state {{ $hasUnpublishedChanges ? 'is-pending' : 'is-current' }}">
                    <span class="editor-state-dot"></span>
                    {{ $hasUnpublishedChanges ? 'Draft has unpublished changes' : 'Draft matches the live form' }}
                </div>
                <form action="{{ route('staff.it.form-builder.publish') }}" method="POST" class="editor-publish-form">
                    @csrf
                    <input type="hidden" name="form_type" value="{{ $formType }}">
                    <label for="publishSummary">Release note <span>(optional)</span></label>
                    <input type="text" id="publishSummary" name="change_summary" maxlength="500" class="form-control" placeholder="What changed in this version?" aria-label="Publish note">
                    <button type="submit" class="btn editor-publish-button" @disabled(!$hasUnpublishedChanges) onclick="return confirm('Publish these form changes for new applications? Existing applications and drafts will keep their current data.')">
                        <i class="ri-send-plane-line"></i> Publish changes
                    </button>
                </form>
            </section>

            <section class="editor-safety-note">
                <span class="editor-safety-icon"><i class="ri-shield-check-line"></i></span>
                <div>
                    <h3>Existing records stay safe</h3>
                    <p>Edits affect new applications after publishing. Saved drafts and submissions keep their data; core identity, payment, document, and workflow fields remain protected.</p>
                </div>
            </section>
        </aside>
    </div>

    <section class="editor-panel editor-fields-panel">
        <div class="editor-section-heading">
            <div class="editor-panel-title">
                <span class="editor-heading-icon"><i class="ri-list-settings-line"></i></span>
                <div>
                    <h2>Additional fields</h2>
                    <p>Manage configurable fields for {{ $formTypes[$formType] ?? $formType }}.</p>
                </div>
            </div>
            <span class="editor-count-badge">{{ $fields->count() }} {{ \Illuminate\Support\Str::plural('field', $fields->count()) }}</span>
        </div>
        <div class="editor-table-body">
            @if($fields->isEmpty())
                <div class="editor-empty-state">
                    <span><i class="ri-input-method-line"></i></span>
                    <h3>No additional fields yet</h3>
                    <p>The standard form appears in the preview. Add an optional field to {{ $selectedStepLabel }} when you need one.</p>
                    <button class="btn editor-secondary-button" data-bs-toggle="modal" data-bs-target="#addFieldModal" data-add-field-step="{{ $previewStep }}">
                        <i class="ri-add-line"></i> Add to {{ $selectedStepLabel }}
                    </button>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table editor-fields-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Label</th>
                                <th>Field Name</th>
                                <th>Type</th>
                                <th>Step</th>
                                <th>Required</th>
                                <th>Status</th>
                                <th style="width:160px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sortableFields">
                            @foreach($fields as $field)
                            <tr data-id="{{ $field->id }}">
                                <td class="text-muted">{{ $field->sort_order }}</td>
                                <td class="fw-semibold">{{ $field->field_label }}</td>
                                <td><code style="font-size:11px;">{{ $field->field_name }}</code></td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $fieldTypes[$field->field_type] ?? $field->field_type }}</span>
                                </td>
                                <td>Step {{ $field->step_number }}</td>
                                <td>
                                    @if($field->is_required)
                                        <span class="badge bg-danger">Required</span>
                                    @else
                                        <span class="badge bg-secondary">Optional</span>
                                    @endif
                                </td>
                                <td>
                                    @if($field->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#editFieldModal{{ $field->id }}" title="Edit">
                                            <i class="ri-pencil-line"></i>
                                        </button>
                                        <form action="{{ route('staff.it.form-builder.toggle', $field->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-{{ $field->is_active ? 'warning' : 'success' }}" title="{{ $field->is_active ? 'Disable' : 'Enable' }}">
                                                <i class="ri-{{ $field->is_active ? 'eye-off-line' : 'eye-line' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('staff.it.form-builder.destroy', $field->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this field from future forms? Existing submissions and drafts will be retained.')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger" title="Remove from live form">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="editor-panel editor-history-panel">
        <div class="editor-section-heading">
            <div class="editor-panel-title">
                <span class="editor-heading-icon"><i class="ri-history-line"></i></span>
                <div><h2>Published versions</h2><p>Review or restore a previous form version to the draft.</p></div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table editor-history-table align-middle mb-0">
                <thead><tr><th>Version</th><th>Published</th><th>By</th><th>Release note</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @forelse($revisions as $revision)
                    <tr>
                        <td class="fw-semibold">{{ $revision->version }} @if($revision->version === $state->version)<span class="badge bg-success ms-1">Current</span>@endif</td>
                        <td>{{ $revision->published_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $revision->publisher?->name ?? 'System' }}</td>
                        <td>{{ $revision->change_summary ?: '—' }}</td>
                        <td class="text-end">
                            @if($revision->version !== $state->version)
                                <form action="{{ route('staff.it.form-builder.restore', $revision) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore version {{ $revision->version }} to the draft? The live form will not change until you publish.')">
                                    @csrf
                                    <input type="hidden" name="form_type" value="{{ $formType }}">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="ri-history-line me-1"></i>Restore to draft</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No published versions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="modal fade editor-modal" id="addFieldModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.it.form-builder.store') }}" method="POST">
                @csrf
                <input type="hidden" name="form_type" value="{{ $formType }}">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Custom Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Field Label <span class="text-danger">*</span></label>
                        <input type="text" name="field_label" class="form-control" required placeholder="e.g. Driver's Licence Number">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Field Type <span class="text-danger">*</span></label>
                            <select name="field_type" class="form-select" required onchange="toggleOptionsField(this, 'add')">
                                @foreach($fieldTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                        <label class="form-label fw-semibold">Add to step <span class="text-danger">*</span></label>
                            <select name="step_number" id="addFieldStep" class="form-select" required>
                                @foreach($stepLabels as $stepNumber => $stepLabel)
                                    <option value="{{ $stepNumber }}" @selected($previewStep === $stepNumber)>Step {{ $stepNumber }} · {{ $stepLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Position</label>
                            <input type="number" name="sort_order" id="addFieldSortOrder" class="form-control" value="{{ ((int) $fields->where('step_number', $previewStep)->max('sort_order')) + 1 }}" min="0" max="10000">
                        </div>
                    </div>
                    <div class="mb-3" id="addOptionsField" style="display:none;">
                        <label class="form-label fw-semibold">Options (one per line)</label>
                        <textarea name="options" class="form-control" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Placeholder</label>
                        <input type="text" name="placeholder" class="form-control" placeholder="e.g. Enter your driver's licence number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Help Text</label>
                        <input type="text" name="help_text" class="form-control" placeholder="e.g. If applicable, enter your professional body membership">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_required" value="1" class="form-check-input" id="addRequired">
                        <label for="addRequired" class="form-check-label">Required field</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">
                        <i class="ri-add-line me-1"></i> Add Field
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($fields as $field)
<div class="modal fade editor-modal" id="editFieldModal{{ $field->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.it.form-builder.update', $field->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Field: {{ $field->field_label }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Field Label <span class="text-danger">*</span></label>
                        <input type="text" name="field_label" class="form-control" value="{{ $field->field_label }}" required>
                    </div>
                    <div class="alert alert-info py-2 small">
                        Renaming the label keeps this field's saved data key (<code>{{ $field->field_name }}</code>) unchanged, so existing drafts and applications remain intact.
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Field Type <span class="text-danger">*</span></label>
                            <select name="field_type" class="form-select" required onchange="toggleOptionsField(this, 'edit{{ $field->id }}')">
                                @foreach($fieldTypes as $key => $label)
                                    <option value="{{ $key }}" {{ $field->field_type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Step Number <span class="text-danger">*</span></label>
                            <input type="number" name="step_number" class="form-control" value="{{ $field->step_number }}" min="1" max="{{ $maxFormSteps }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Position</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $field->sort_order }}" min="0" max="10000">
                        </div>
                    </div>
                    <div class="mb-3" id="edit{{ $field->id }}OptionsField" style="{{ in_array($field->field_type, ['select', 'radio']) ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold">Options (one per line)</label>
                        <textarea name="options" class="form-control" rows="4">{{ is_array($field->options) ? implode("\n", $field->options) : '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Placeholder</label>
                        <input type="text" name="placeholder" class="form-control" value="{{ $field->placeholder }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Help Text</label>
                        <input type="text" name="help_text" class="form-control" value="{{ $field->help_text }}">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_required" value="1" class="form-check-input" id="editRequired{{ $field->id }}" {{ $field->is_required ? 'checked' : '' }}>
                        <label for="editRequired{{ $field->id }}" class="form-check-label">Required field</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editActive{{ $field->id }}" {{ $field->is_active ? 'checked' : '' }}>
                        <label for="editActive{{ $field->id }}" class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark">
                        <i class="ri-save-line me-1"></i> Update Field
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<script>
function toggleOptionsField(selectEl, prefix) {
    var val = selectEl.value;
    var optDiv = document.getElementById(prefix + 'OptionsField');
    if (optDiv) {
        optDiv.style.display = (val === 'select' || val === 'radio') ? '' : 'none';
    }
}

const previewStepSelect = document.getElementById('formPreviewStep');
const previewFrame = document.getElementById('applicantFormPreview');
const addFieldStepSelect = document.getElementById('addFieldStep');
const addFieldSortOrder = document.getElementById('addFieldSortOrder');

function setAddFieldStep(step) {
    const selectedStep = Number(step) || 1;
    if (addFieldStepSelect) addFieldStepSelect.value = String(selectedStep);
    if (addFieldSortOrder) {
        const stepOrders = @json($fields->groupBy('step_number')->map(fn ($stepFields) => (int) $stepFields->max('sort_order')));
        addFieldSortOrder.value = String((stepOrders[selectedStep] || 0) + 1);
    }
}

previewStepSelect?.addEventListener('change', () => {
    const url = new URL(previewFrame.src);
    url.searchParams.set('step', previewStepSelect.value);
    previewFrame.src = url.toString();
    document.getElementById('addFieldAtPreviewStep')?.setAttribute('data-step', previewStepSelect.value);
    setAddFieldStep(previewStepSelect.value);
});

document.querySelectorAll('[data-add-field-step]').forEach(button => {
    button.addEventListener('click', () => setAddFieldStep(button.dataset.addFieldStep || previewStepSelect?.value));
});

document.getElementById('addFieldAtPreviewStep')?.addEventListener('click', () => {
    setAddFieldStep(previewStepSelect?.value);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addFieldModal')).show();
});

window.addEventListener('message', event => {
    if (event.origin !== window.location.origin || event.source !== previewFrame?.contentWindow) return;
    if (event.data?.type !== 'form-builder-preview-step') return;
    previewStepSelect.value = String(event.data.step);
    document.getElementById('addFieldAtPreviewStep')?.setAttribute('data-step', String(event.data.step));
    setAddFieldStep(event.data.step);
});
</script>
<style>
    #application-form-editor {
        --editor-ink: #17202b;
        --editor-muted: #6b7788;
        --editor-border: #e4e9ef;
        --editor-gold: #f2c94c;
        --editor-gold-deep: #d5ad2e;
        --editor-canvas: #f6f8fa;
        max-width: 1680px;
        margin: 0 auto;
        padding-top: 10px;
        padding-bottom: 42px;
        color: var(--editor-ink);
    }

    #application-form-editor .editor-hero {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 22px;
        padding: 27px 30px;
        border: 1px solid #232e3a;
        border-radius: 20px;
        color: #fff;
        background: radial-gradient(ellipse at 92% 0%, rgba(242, 201, 76, .19), transparent 38%), linear-gradient(120deg, #121920, #202b36);
        box-shadow: 0 14px 34px rgba(15, 23, 42, .13);
    }

    #application-form-editor .editor-hero-copy { max-width: 740px; }
    #application-form-editor .editor-eyebrow,
    #application-form-editor .editor-side-label,
    #application-form-editor .editor-picker-label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
    #application-form-editor .editor-eyebrow {
        margin-bottom: 11px;
        color: #f5d86e;
    }
    #application-form-editor .editor-hero h1 {
        margin: 0;
        color: #fff;
        font-size: clamp(23px, 2.4vw, 31px);
        font-weight: 800;
        letter-spacing: -.035em;
    }
    #application-form-editor .editor-hero p {
        margin: 8px 0 0;
        color: rgba(255,255,255,.68);
        font-size: 13px;
        line-height: 1.6;
    }
    #application-form-editor .editor-hero-actions {
        display: flex;
        align-items: center;
        gap: 9px;
        flex: 0 0 auto;
    }
    #application-form-editor .editor-primary-button,
    #application-form-editor .editor-back-button,
    #application-form-editor .editor-secondary-button,
    #application-form-editor .editor-publish-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 800;
        transition: transform .16s ease, background-color .16s ease, border-color .16s ease, box-shadow .16s ease;
    }
    #application-form-editor .editor-primary-button {
        padding: 0 15px;
        border: 1px solid var(--editor-gold);
        background: var(--editor-gold);
        color: #1c2430;
        box-shadow: 0 5px 14px rgba(242, 201, 76, .18);
    }
    #application-form-editor .editor-primary-button:hover:not(:disabled) {
        transform: translateY(-1px);
        border-color: #ffdc63;
        background: #ffdc63;
        color: #151c24;
    }
    #application-form-editor .editor-back-button {
        padding: 0 13px;
        border: 1px solid rgba(255,255,255,.2);
        color: rgba(255,255,255,.82);
    }
    #application-form-editor .editor-back-button:hover {
        border-color: rgba(255,255,255,.45);
        background: rgba(255,255,255,.07);
        color: #fff;
    }

    #application-form-editor .editor-form-picker,
    #application-form-editor .editor-panel {
        border: 1px solid var(--editor-border);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 5px 18px rgba(15, 23, 42, .035);
    }
    #application-form-editor .editor-form-picker {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px 22px;
        margin-bottom: 18px;
        padding: 14px 18px;
    }
    #application-form-editor .editor-picker-label {
        color: #627083;
        letter-spacing: .06em;
        white-space: nowrap;
    }
    #application-form-editor .editor-picker-label i { color: #a27d13; font-size: 16px; }
    #application-form-editor .editor-form-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }
    #application-form-editor .editor-form-tab {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 0 12px;
        border: 1px solid transparent;
        border-radius: 9px;
        color: #647184;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        transition: color .15s ease, background-color .15s ease, border-color .15s ease;
    }
    #application-form-editor .editor-form-tab:hover { border-color: #e8ecf1; background: #f8fafb; color: #263241; }
    #application-form-editor .editor-form-tab.is-active {
        border-color: #ead98e;
        background: #fff9e5;
        color: #29281f;
    }
    #application-form-editor .editor-form-tab.is-active i { color: #937211; font-size: 15px; }

    #application-form-editor .editor-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1.65fr) minmax(285px, .75fr);
        align-items: start;
        gap: 17px;
        margin-bottom: 18px;
    }
    #application-form-editor .editor-preview-panel { min-width: 0; overflow: hidden; }
    #application-form-editor .editor-panel-heading,
    #application-form-editor .editor-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 18px 20px;
        border-bottom: 1px solid #edf0f3;
    }
    #application-form-editor .editor-panel-title {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
    }
    #application-form-editor .editor-heading-icon {
        display: grid;
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        place-items: center;
        border: 1px solid #eee2b8;
        border-radius: 11px;
        background: #fff9e8;
        color: #856913;
        font-size: 17px;
    }
    #application-form-editor .editor-panel-title h2,
    #application-form-editor .editor-step-summary h2 {
        margin: 0;
        color: #263241;
        font-size: 14px;
        font-weight: 800;
        letter-spacing: -.01em;
    }
    #application-form-editor .editor-panel-title p {
        margin: 3px 0 0;
        color: var(--editor-muted);
        font-size: 11px;
        line-height: 1.45;
    }
    #application-form-editor .editor-preview-controls {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }
    #application-form-editor .editor-preview-controls label,
    #application-form-editor .editor-publish-form label {
        margin: 0;
        color: #5e6c7e;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }
    #application-form-editor .editor-preview-controls .form-select {
        width: min(235px, 24vw);
        min-height: 38px;
        border-color: #dfe5eb;
        border-radius: 9px;
        color: #334155;
        font-size: 11px;
        font-weight: 650;
    }
    #application-form-editor .editor-preview-controls .editor-primary-button { min-height: 38px; white-space: nowrap; }
    #application-form-editor .editor-preview-canvas { padding: 14px; background: #f3f5f7; }
    #application-form-editor .form-preview-frame-wrap {
        height: min(680px, 67vh);
        min-height: 490px;
        overflow: hidden;
        border: 1px solid #dfe4ea;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .045);
    }
    #application-form-editor .form-preview-frame-wrap iframe { display: block; width: 100%; height: 100%; border: 0; background: #fff; }
    #application-form-editor .editor-preview-caption {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        padding: 10px 3px 0;
        color: #697688;
        font-size: 10px;
        line-height: 1.5;
    }
    #application-form-editor .editor-preview-caption i { flex: 0 0 auto; color: #8f7118; font-size: 14px; }

    #application-form-editor .editor-sidebar { display: grid; gap: 13px; }
    #application-form-editor .editor-step-summary,
    #application-form-editor .editor-publish-panel { padding: 17px; }
    #application-form-editor .editor-side-label { color: #748094; letter-spacing: .075em; }
    #application-form-editor .editor-side-label i { color: #97751a; font-size: 15px; }
    #application-form-editor .editor-step-number {
        display: grid;
        width: 35px;
        height: 35px;
        margin: 16px 0 10px;
        place-items: center;
        border-radius: 11px;
        background: #fff4cb;
        color: #6d5713;
        font-size: 15px;
        font-weight: 850;
    }
    #application-form-editor .editor-step-summary h2 { font-size: 17px; }
    #application-form-editor .editor-step-summary p,
    #application-form-editor .editor-publish-meta {
        margin: 5px 0 14px;
        color: #748094;
        font-size: 11px;
        line-height: 1.5;
    }
    #application-form-editor .editor-secondary-button {
        width: 100%;
        min-height: 37px;
        border: 1px solid #e2e7ed;
        background: #fff;
        color: #344154;
    }
    #application-form-editor .editor-secondary-button:hover {
        border-color: #d9bd5a;
        background: #fffaf0;
        color: #352d17;
    }
    #application-form-editor .editor-publish-topline { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    #application-form-editor .editor-version-tag {
        padding: 5px 8px;
        border: 1px solid #e7ebf0;
        border-radius: 7px;
        background: #f7f8fa;
        color: #536174;
        font-size: 10px;
        font-weight: 800;
    }
    #application-form-editor .editor-publish-meta { margin: 10px 0; }
    #application-form-editor .editor-draft-state {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 9px 10px;
        border: 1px solid;
        border-radius: 9px;
        font-size: 10px;
        font-weight: 750;
        line-height: 1.4;
    }
    #application-form-editor .editor-draft-state.is-pending { border-color: #f0dda0; background: #fff9e7; color: #735b14; }
    #application-form-editor .editor-draft-state.is-current { border-color: #cfe8d9; background: #f1faf4; color: #27734a; }
    #application-form-editor .editor-state-dot { width: 7px; height: 7px; flex: 0 0 7px; border-radius: 50%; background: currentColor; }
    #application-form-editor .editor-publish-form { display: grid; gap: 8px; margin-top: 15px; }
    #application-form-editor .editor-publish-form label span { color: #8b95a3; font-weight: 500; }
    #application-form-editor .editor-publish-form .form-control {
        min-height: 38px;
        border-color: #dfe5eb;
        border-radius: 9px;
        font-size: 11px;
    }
    #application-form-editor .editor-publish-button { width: 100%; min-height: 39px; border: 0; background: #1c2631; color: #fff; }
    #application-form-editor .editor-publish-button:hover:not(:disabled) { transform: translateY(-1px); background: #2d3a48; color: #fff; }
    #application-form-editor .editor-publish-button:disabled { background: #e8ebef; color: #98a1ad; }
    #application-form-editor .editor-safety-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 14px;
        border: 1px solid #e4eaf0;
        border-radius: 14px;
        background: #f9fbfc;
    }
    #application-form-editor .editor-safety-icon {
        display: grid;
        flex: 0 0 30px;
        width: 30px;
        height: 30px;
        place-items: center;
        border-radius: 9px;
        background: #eaf6ef;
        color: #328456;
    }
    #application-form-editor .editor-safety-note h3 { margin: 1px 0 4px; color: #344154; font-size: 11px; font-weight: 800; }
    #application-form-editor .editor-safety-note p { margin: 0; color: #748094; font-size: 10px; line-height: 1.55; }

    #application-form-editor .editor-fields-panel,
    #application-form-editor .editor-history-panel { overflow: hidden; margin-bottom: 17px; }
    #application-form-editor .editor-section-heading { padding: 16px 20px; }
    #application-form-editor .editor-count-badge {
        padding: 6px 9px;
        border: 1px solid #e8dfbd;
        border-radius: 99px;
        background: #fff9e8;
        color: #705b1a;
        font-size: 10px;
        font-weight: 800;
        white-space: nowrap;
    }
    #application-form-editor .editor-table-body { min-height: 105px; }
    #application-form-editor .editor-empty-state { display: grid; justify-items: center; padding: 34px 18px; text-align: center; }
    #application-form-editor .editor-empty-state > span { display: grid; width: 44px; height: 44px; margin-bottom: 10px; place-items: center; border-radius: 13px; background: #f3f5f7; color: #8b96a5; font-size: 21px; }
    #application-form-editor .editor-empty-state h3 { margin: 0; color: #344154; font-size: 13px; font-weight: 800; }
    #application-form-editor .editor-empty-state p { max-width: 470px; margin: 5px 0 14px; color: #788496; font-size: 11px; line-height: 1.55; }
    #application-form-editor .editor-fields-table,
    #application-form-editor .editor-history-table { color: #3c4858; font-size: 11px; }
    #application-form-editor .editor-fields-table thead th,
    #application-form-editor .editor-history-table thead th {
        padding: 10px 13px;
        border-bottom: 1px solid #e8edf2;
        background: #f7f9fb;
        color: #758195;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    #application-form-editor .editor-fields-table tbody td,
    #application-form-editor .editor-history-table tbody td { padding: 11px 13px; border-color: #edf0f3; vertical-align: middle; }
    #application-form-editor .editor-fields-table tbody tr:hover,
    #application-form-editor .editor-history-table tbody tr:hover { background: #fcfcfa; }
    #application-form-editor .editor-fields-table code { padding: 3px 5px; border-radius: 5px; background: #f2f4f7; color: #596779; font-size: 10px !important; }
    #application-form-editor .editor-fields-table .badge,
    #application-form-editor .editor-history-table .badge { font-size: 9px; font-weight: 750; }
    #application-form-editor .editor-fields-table .btn { width: 30px; height: 30px; display: inline-grid; place-items: center; padding: 0; border-radius: 8px !important; }
    #application-form-editor .editor-history-panel > .table-responsive { padding: 0 4px 4px; }
    #application-form-editor .editor-history-table td:nth-child(4) { min-width: 180px; }

    #application-form-editor .alert { border-radius: 12px; font-size: 12px; }
    .editor-modal .modal-content { overflow: hidden; border: 1px solid #e3e8ee; border-radius: 16px; box-shadow: 0 22px 65px rgba(15,23,42,.22); }
    .editor-modal .modal-header { padding: 17px 20px; border-bottom-color: #edf0f3; background: #fbfcfd; }
    .editor-modal .modal-title { color: #263241; font-size: 15px; }
    .editor-modal .modal-body { padding: 20px; }
    .editor-modal .modal-footer { padding: 13px 20px; border-top-color: #edf0f3; background: #fbfcfd; }

    @media (max-width: 1180px) {
        #application-form-editor .editor-workspace { grid-template-columns: minmax(0, 1.35fr) minmax(270px, .8fr); }
        #application-form-editor .editor-panel-heading { align-items: flex-start; flex-direction: column; }
        #application-form-editor .editor-preview-controls { width: 100%; justify-content: flex-start; }
        #application-form-editor .editor-preview-controls .form-select { width: auto; flex: 1 1 220px; }
    }
    @media (max-width: 900px) {
        #application-form-editor .editor-workspace { grid-template-columns: 1fr; }
        #application-form-editor .editor-sidebar { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: stretch; }
        #application-form-editor .editor-safety-note { grid-column: 1 / -1; }
    }
    @media (max-width: 620px) {
        #application-form-editor { padding-right: 8px; padding-left: 8px; }
        #application-form-editor .editor-hero { align-items: stretch; flex-direction: column; padding: 21px 18px; border-radius: 16px; }
        #application-form-editor .editor-hero-actions { flex-wrap: wrap; }
        #application-form-editor .editor-hero-actions .btn { flex: 1 1 auto; }
        #application-form-editor .editor-form-picker { align-items: flex-start; flex-direction: column; gap: 9px; padding: 13px; }
        #application-form-editor .editor-form-tabs { width: 100%; }
        #application-form-editor .editor-form-tab { flex: 1 1 auto; justify-content: center; padding: 0 8px; }
        #application-form-editor .editor-panel-heading,
        #application-form-editor .editor-section-heading { padding: 14px; }
        #application-form-editor .editor-preview-controls { align-items: stretch; flex-direction: column; }
        #application-form-editor .editor-preview-controls .form-select { width: 100%; }
        #application-form-editor .editor-preview-controls .editor-primary-button { width: 100%; }
        #application-form-editor .editor-preview-canvas { padding: 8px; }
        #application-form-editor .form-preview-frame-wrap { height: 65vh; min-height: 410px; border-radius: 9px; }
        #application-form-editor .editor-sidebar { grid-template-columns: 1fr; }
        #application-form-editor .editor-safety-note { grid-column: auto; }
        #application-form-editor .editor-fields-table { min-width: 760px; }
        #application-form-editor .editor-history-table { min-width: 680px; }
        #application-form-editor .editor-section-heading { align-items: flex-start; }
    }
</style>
@endsection
