@php
    $isPreview = $customFormPreview ?? false;
    $customFields = isset($customFieldsOverride)
        ? collect($customFieldsOverride)
        : \App\Models\CustomFormField::active()
            ->forForm($customFormType ?? 'accreditation')
            ->ordered()
            ->get();
    $retiredFields = collect();
    if (!$isPreview && isset($draft) && $draft) {
        $retiredFields = \App\Models\CustomFormField::query()
            ->forForm($customFormType ?? 'accreditation')
            ->where('is_active', false)
            ->ordered()
            ->get();
    }
    $currentStep = $customFieldStep ?? null;
    if ($currentStep) {
        $customFields = $customFields->where('step_number', $currentStep);
    }
    $stepGroups = $customFields->groupBy('step_number');
    $stepPrefix = [
        'accreditation' => 'ap3',
        'ap1' => 'ap1',
        'ap2' => 'ap2',
        'ap4' => 'ap4',
    ][$customFormType ?? 'accreditation'] ?? '';
@endphp

@if($customFields->isNotEmpty())
    @foreach($stepGroups as $stepNumber => $fieldsForStep)
<div class="custom-fields-section mt-4 mb-3" data-custom-form-section data-step-prefix="{{ $stepPrefix }}" data-step-number="{{ $stepNumber }}" @if($isPreview) data-form-preview="true" @endif>
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="ri-file-edit-line text-muted"></i>
        <h1 class="fw-bold m-0" style="font-size:14px; color:#475569;">
            Additional Information
            @if($stepGroups->count() > 1)
                — Step {{ $stepNumber }}
            @endif
        </h1>
    </div>
    <div class="row g-3">
        @foreach($fieldsForStep as $cf)
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="custom-field-{{ $cf->id }}" style="font-size:13px;">
                    {{ $cf->field_label }}
                    @if($cf->is_required) <span class="text-danger">*</span> @endif
                </label>

                @if($cf->field_type === 'textarea')
                    <textarea name="custom_fields[{{ $cf->field_name }}]"
                        id="custom-field-{{ $cf->id }}"
                        class="form-control form-control-sm zmc-draft-field"
                        data-draft-key="{{ $cf->field_name }}"
                        placeholder="{{ $cf->placeholder }}"
                        rows="3"
                        {{ $cf->is_required ? 'required' : '' }}
                        {{ $isPreview ? 'disabled aria-disabled=true' : '' }}>{{ old('custom_fields.' . $cf->field_name) }}</textarea>

                @elseif($cf->field_type === 'select')
                    <select name="custom_fields[{{ $cf->field_name }}]"
                        id="custom-field-{{ $cf->id }}"
                        class="form-select form-select-sm zmc-draft-field"
                        data-draft-key="{{ $cf->field_name }}"
                        {{ $cf->is_required ? 'required' : '' }}
                        {{ $isPreview ? 'disabled aria-disabled=true' : '' }}>
                        <option value="">{{ $cf->placeholder ?: '-- Select --' }}</option>
                        @foreach($cf->options ?? [] as $opt)
                            <option value="{{ $opt }}" {{ old('custom_fields.' . $cf->field_name) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>

                @elseif($cf->field_type === 'radio')
                    @foreach($cf->options ?? [] as $opt)
                        <div class="form-check">
                            <input type="radio" name="custom_fields[{{ $cf->field_name }}]"
                                id="custom-field-{{ $cf->id }}-{{ $loop->index }}"
                                value="{{ $opt }}"
                                class="form-check-input zmc-draft-field"
                                data-draft-key="{{ $cf->field_name }}"
                                {{ $loop->first && $cf->is_required ? 'required' : '' }}
                                {{ $isPreview ? 'disabled aria-disabled=true' : '' }}
                                {{ old('custom_fields.' . $cf->field_name) === $opt ? 'checked' : '' }}>
                            <label for="custom-field-{{ $cf->id }}-{{ $loop->index }}" class="form-check-label" style="font-size:13px;">{{ $opt }}</label>
                        </div>
                    @endforeach

                @elseif($cf->field_type === 'checkbox')
                    <div class="form-check">
                        <input type="checkbox" name="custom_fields[{{ $cf->field_name }}]"
                            id="custom-field-{{ $cf->id }}"
                            value="1"
                            class="form-check-input zmc-draft-field"
                            data-draft-key="{{ $cf->field_name }}"
                            {{ $isPreview ? 'disabled aria-disabled=true' : '' }}
                            {{ old('custom_fields.' . $cf->field_name) ? 'checked' : '' }}>
                        <label for="custom-field-{{ $cf->id }}" class="form-check-label" style="font-size:13px;">{{ $cf->field_label }}</label>
                    </div>

                @elseif($cf->field_type === 'file')
                    <input type="file" name="custom_files[{{ $cf->field_name }}]"
                        id="custom-field-{{ $cf->id }}"
                        class="form-control form-control-sm"
                        {{ $cf->is_required ? 'required' : '' }}
                        {{ $isPreview ? 'disabled aria-disabled=true' : '' }}>

                @else
                    <input type="{{ $cf->field_type }}"
                        id="custom-field-{{ $cf->id }}"
                        name="custom_fields[{{ $cf->field_name }}]"
                        class="form-control form-control-sm zmc-draft-field"
                        data-draft-key="{{ $cf->field_name }}"
                        placeholder="{{ $cf->placeholder }}"
                        value="{{ old('custom_fields.' . $cf->field_name) }}"
                        {{ $cf->is_required ? 'required' : '' }}
                        {{ $isPreview ? 'disabled aria-disabled=true' : '' }}>
                @endif

                @if($cf->help_text)
                    <div class="form-text" style="font-size:11px;">{{ $cf->help_text }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
    @endforeach
@endif

{{-- Keep values for retired configurable fields when an applicant resumes a saved draft. --}}
@if($retiredFields->isNotEmpty())
    @foreach($retiredFields as $retiredField)
        @php
            $retiredValue = data_get($draft->form_data ?? [], 'custom_fields.' . $retiredField->field_name);
            if ($retiredValue === null) {
                $retiredValue = data_get($draft->form_data ?? [], 'custom_fields[' . $retiredField->field_name . ']');
            }
            if ($retiredValue === null && is_array($draft->form_data ?? [])) {
                $retiredValue = $draft->form_data['custom_fields[' . $retiredField->field_name . ']'] ?? null;
            }
        @endphp
        @if($retiredValue !== null)
            <input type="hidden" name="custom_fields[{{ $retiredField->field_name }}]" value="{{ is_scalar($retiredValue) ? $retiredValue : json_encode($retiredValue) }}">
        @endif
    @endforeach
@endif

@if(!$isPreview || ($formBuilderPreview ?? false))
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll(@json(($formBuilderPreview ?? false) ? '[data-custom-form-section]' : '[data-custom-form-section]:not([data-form-preview])')).forEach(section => {
        const prefix = section.dataset.stepPrefix;
        const targetStep = document.getElementById(`${prefix}-step-${section.dataset.stepNumber}`);
        if (targetStep && !targetStep.contains(section)) targetStep.appendChild(section);
    });
});
</script>
@endif
