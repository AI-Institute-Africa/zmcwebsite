<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFormField extends Model
{
    protected $fillable = [
        'form_type', 'field_name', 'field_label', 'field_type',
        'options', 'step_number', 'sort_order', 'is_required',
        'placeholder', 'help_text', 'validation_rules', 'is_active', 'created_by',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForForm($query, string $formType)
    {
        return $query->where('form_type', $formType);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('step_number')->orderBy('sort_order');
    }

    public static function formTypes(): array
    {
        return [
            'accreditation' => 'Media Practitioner Portal (Accreditation)',
            'ap1' => 'Media House Registration Portal (AP1)',
            'ap2' => 'Media House Portal (AP2 – News Agency)',
            'ap4' => 'Media House Portal (AP4 – Foreign Representative)',
        ];
    }

    public static function fieldTypes(): array
    {
        return [
            'text' => 'Text Input',
            'textarea' => 'Text Area',
            'number' => 'Number',
            'email' => 'Email',
            'date' => 'Date',
            'select' => 'Dropdown Select',
            'checkbox' => 'Checkbox',
            'radio' => 'Radio Buttons',
            'file' => 'File Upload',
            'tel' => 'Phone Number',
            'url' => 'URL',
        ];
    }

    public static function stepCounts(): array
    {
        return [
            'accreditation' => 5,
            'ap1' => 6,
            'ap2' => 5,
            'ap4' => 5,
        ];
    }

    public static function stepLabels(): array
    {
        return [
            'accreditation' => [
                1 => 'Applicant Type',
                2 => 'Personal Details',
                3 => 'Employment',
                4 => 'Past Work',
                5 => 'Uploads & Declaration',
            ],
            'ap1' => [
                1 => 'Contact & Organization',
                2 => 'Directors & Shareholding',
                3 => 'Management',
                4 => 'Questions',
                5 => 'Uploads',
                6 => 'Declaration',
            ],
            'ap2' => [
                1 => 'Contact Details',
                2 => 'Agency Details',
                3 => 'Directors',
                4 => 'Uploads',
                5 => 'Declaration',
            ],
            'ap4' => [
                1 => 'Contact Details',
                2 => 'Organisation',
                3 => 'Rep Office & Staff',
                4 => 'Uploads',
                5 => 'Declaration',
            ],
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
