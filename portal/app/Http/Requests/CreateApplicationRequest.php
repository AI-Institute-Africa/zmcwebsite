<?php

namespace App\Http\Requests;

use App\Support\FileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CreateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic info
            'applicant_type' => ['required', 'in:local,international'],
            'employment_status' => ['required', 'in:employed,freelancer,student,unemployed'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:applications,email'],
            'phone' => ['required', 'string', 'max:20'],

            // ID documents - conditional on applicant type
            'national_id' => [
                'required_if:applicant_type,local',
                'nullable',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'max:10240',
            ],
            'passport' => [
                'required_if:applicant_type,international',
                'nullable',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'max:10240',
            ],
            'passport_photo' => [
                'required',
                'file',
                'extensions:jpg,jpeg,png',
                'max:10240',
                'dimensions:min_width=600,min_height=600',
            ],

            // Clearance letter - conditional on international applicants
            'clearance_letter' => [
                'required_if:applicant_type,international',
                'nullable',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'max:10240',
            ],

            // Qualification documents
            'graduation_certificate' => [
                'required',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'max:10240',
            ],

            // Employment letter - conditional on employed applicants
            'employment_letter' => [
                'required_if:employment_status,employed',
                'nullable',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'max:10240',
            ],

            // Payment proof
            'payment_proof' => [
                'nullable',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'max:10240',
            ],

            // Past work - either file or link required (validated via custom rule)
            'past_work_file' => [
                'nullable',
                'file',
                'extensions:pdf,jpg,jpeg,png,mp4,mp3,doc,docx,xls,xlsx',
                'max:52428800', // 50MB for media files
            ],
            'past_work_link' => [
                'nullable',
                'url',
                'max:2000',
            ],

            // Payment gateway reference (optional - for webhook verification)
            'payment_gateway_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'applicant_type.required' => 'Applicant type is required.',
            'applicant_type.in' => 'Applicant type must be local or international.',
            'employment_status.required' => 'Employment status is required.',
            'national_id.required_if' => 'National ID is required for local applicants.',
            'passport.required_if' => 'Passport is required for international applicants.',
            'clearance_letter.required_if' => 'Clearance letter is required for international applicants.',
            'passport_photo.required' => 'Passport photo is required.',
            'passport_photo.dimensions' => 'Passport photo must be at least 600x600 pixels.',
            'graduation_certificate.required' => 'Graduation certificate is required.',
            'employment_letter.required_if' => 'Employment letter is required if you are employed.',
            'past_work_file.extensions' => 'Past work file must be a valid document or media file.',
            'past_work_link.url' => 'Past work link must be a valid URL.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $fileRules = [
                'national_id' => ['pdf', 'jpg', 'jpeg', 'png'],
                'passport' => ['pdf', 'jpg', 'jpeg', 'png'],
                'passport_photo' => ['jpg', 'jpeg', 'png'],
                'clearance_letter' => ['pdf', 'jpg', 'jpeg', 'png'],
                'graduation_certificate' => ['pdf', 'jpg', 'jpeg', 'png'],
                'employment_letter' => ['pdf', 'jpg', 'jpeg', 'png'],
                'payment_proof' => ['pdf', 'jpg', 'jpeg', 'png'],
                'past_work_file' => ['pdf', 'jpg', 'jpeg', 'png', 'mp4', 'mp3', 'doc', 'docx', 'xls', 'xlsx'],
            ];

            foreach ($fileRules as $field => $extensions) {
                if ($this->hasFile($field) && !FileType::uploadedFileLooksValid($this->file($field), $extensions)) {
                    $validator->errors()->add($field, 'The uploaded file type does not match its extension.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // Ensure past work file/link validation: at least one must be provided
        // This will be done in the controller after this request is validated
    }
}
