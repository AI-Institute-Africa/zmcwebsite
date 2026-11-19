<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $fillable = [
        'title',
        'notice_category',
        'reference_number',
        'body',
        'summary',
        'target_portal',
        'target_audience',
        'issuing_authority',
        'signed_by',
        'urgency_level',
        'effective_date',
        'expires_at',
        'language',
        'attachment_label',
        'related_legal_reference',
        'contact_name',
        'contact_phone',
        'contact_email',
        'is_published',
        'published_at',
        'stick_to_top',
        'show_on_homepage',
        'send_email_alert',
        'created_by',
        'image_path',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'effective_date' => 'date',
        'expires_at' => 'date',
        'stick_to_top' => 'boolean',
        'show_on_homepage' => 'boolean',
        'send_email_alert' => 'boolean',
    ];
}
