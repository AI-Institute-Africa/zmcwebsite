<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteDownload extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'subcategory',
        'version',
        'language',
        'effective_date',
        'display_order',
        'featured',
        'file_path',
        'file_original_name',
        'file_mime',
        'file_size_bytes',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_published'  => 'boolean',
        'published_at'  => 'datetime',
        'effective_date' => 'date',
        'file_size_bytes' => 'integer',
        'display_order' => 'integer',
        'featured' => 'boolean',
    ];

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'accreditation_forms'   => 'Accreditation Forms',
            'registration_forms'    => 'Registration Forms',
            'foia_forms'            => 'FOIA Forms',
            'annual_reports'        => 'Annual Reports',
            'organizational_documents' => 'Organizational Documents',
            'policies_guidelines'   => 'Policies & Guidelines',
            'legislation'           => 'Legislation',
            'statutory_instruments' => 'Statutory Instruments',
            default                 => ucwords(str_replace('_', ' ', $this->category ?? 'other')),
        };
    }

    public function getFileSizeLabelAttribute(): string
    {
        $bytes = $this->file_size_bytes ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }
}
