@php
    $formData = is_string($application->form_data) ? json_decode($application->form_data, true) : ($application->form_data ?? []);
    $customFieldValues = $formData['custom_fields'] ?? [];
    $appType = $application->application_type ?? 'accreditation';
    $formType = strtolower($application->form_type ?? $appType);
    $allCustomFields = \App\Models\CustomFormField::forForm($formType)->ordered()->get();
@endphp

@if($allCustomFields->isNotEmpty() && !empty($customFieldValues))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="fw-bold m-0" style="font-size:14px;">
            <i class="ri-file-edit-line me-2 text-muted"></i>Additional Information (Custom Fields)
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($allCustomFields as $cf)
                @if(isset($customFieldValues[$cf->field_name]))
                <div class="col-md-6">
                    <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">{{ $cf->field_label }}</div>
                    <div class="fw-semibold" style="font-size:13px;">
                        @if($cf->field_type === 'checkbox')
                            {{ $customFieldValues[$cf->field_name] ? 'Yes' : 'No' }}
                        @else
                            {{ $customFieldValues[$cf->field_name] ?: '-' }}
                        @endif
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endif
