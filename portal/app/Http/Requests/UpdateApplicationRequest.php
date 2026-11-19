<?php

namespace App\Http\Requests;

use App\Rules\FileSignature;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $applicationId = $this->route('application')?->id ?? $this->route('id');
        $documentTypes = ['pdf', 'jpg', 'jpeg', 'png'];
        $imageTypes = ['jpg', 'jpeg', 'png'];
        $pastWorkTypes = ['pdf', 'jpg', 'jpeg', 'png', 'mp4', 'mp3', 'doc', 'docx', 'xls', 'xlsx'];

        return [
            // Basic info
            'applicant_type' => ['sometimes', 'in:local,international'],
            'employment_status' => ['sometimes', 'in:employed,freelancer,student,unemployed'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', "unique:applications,email,{$applicationId}"],
            'phone' => ['sometimes', 'string', 'max:20'],

            // ID documents - conditional on applicant type
            'national_id' => [
                'required_if:applicant_type,local',
                'nullable',
                'file',
                'extensions:' . implode(',', $documentTypes),
                new FileSignature($documentTypes),
                'max:10240',
            ],
            'passport' => [
                'required_if:applicant_type,international',
                'nullable',
                'file',
                'extensions:' . implode(',', $documentTypes),
                new FileSignature($documentTypes),
                'max:10240',
            ],
            'passport_photo' => [
                'nullable',
                'file',
                'extensions:' . implode(',', $imageTypes),
                new FileSignature($imageTypes),
                'max:10240',
                'dimensions:min_width=600,min_height=600',
            ],

            // Clearance letter
            'clearance_letter' => [
                'required_if:applicant_type,international',
                'nullable',
                'file',
                'extensions:' . implode(',', $documentTypes),
                new FileSignature($documentTypes),
                'max:10240',
            ],

            // Qualification documents
            'graduation_certificate' => [
                'nullable',
                'file',
                'extensions:' . implode(',', $documentTypes),
                new FileSignature($documentTypes),
                'max:10240',
            ],

            // Employment letter
            'employment_letter' => [
                'required_if:employment_status,employed',
                'nullable',
                'file',
                'extensions:' . implode(',', $documentTypes),
                new FileSignature($documentTypes),
                'max:10240',
            ],

            // Payment proof
            'payment_proof' => [
                'nullable',
                'file',
                'extensions:' . implode(',', $documentTypes),
                new FileSignature($documentTypes),
                'max:10240',
            ],

            // Past work
            'past_work_file' => [
                'nullable',
                'file',
                'extensions:' . implode(',', $pastWorkTypes),
                new FileSignature($pastWorkTypes),
                'max:52428800',
            ],
            'past_work_link' => [
                'nullable',
                'url',
                'max:2000',
            ],

            // Payment gateway reference
            'payment_gateway_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'applicant_type.in' => 'Applicant type must be local or international.',
            'employment_status.in' => 'Employment status must be valid.',
            'national_id.required_if' => 'National ID is required for local applicants.',
            'passport.required_if' => 'Passport is required for international applicants.',
            'clearance_letter.required_if' => 'Clearance letter is required for international applicants.',
            'passport_photo.dimensions' => 'Passport photo must be at least 600x600 pixels.',
            'employment_letter.required_if' => 'Employment letter is required if you are employed.',
            'past_work_file.extensions' => 'Past work file must be a valid document or media file.',
            'past_work_link.url' => 'Past work link must be a valid URL.',
        ];
    }
}
