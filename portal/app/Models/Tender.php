<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'tender_reference',
        'issuing_organisation',
        'summary',
        'quantity',
        'procurement_method',
        'tender_category',
        'description',
        'contract_value_estimate',
        'bid_security_required',
        'bid_security_amount',
        'preferred_bidder_categories',
        'site_visit_scheduled',
        'site_visit_date',
        'site_visit_time',
        'site_visit_notes',
        'pre_bid_meeting_scheduled',
        'pre_bid_meeting_date',
        'pre_bid_meeting_time',
        'pre_bid_meeting_venue',
        'pre_bid_meeting_mandatory',
        'submission_method',
        'submission_address',
        'submission_email',
        'closing_at',
        'opening_at',
        'opening_venue',
        'contact_name',
        'contact_title',
        'contact_email',
        'contact_phone',
        'questions_deadline',
        'bidding_documents_link',
        'evaluation_criteria',
        'tender_status',
        'awarded_to',
        'award_amount',
        'contract_number',
        'language',
        'is_published',
        'published_at',
        'created_by',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'closing_at' => 'datetime',
        'opening_at' => 'datetime',
        'site_visit_date' => 'date',
        'pre_bid_meeting_date' => 'date',
        'questions_deadline' => 'date',
        'bid_security_required' => 'boolean',
        'site_visit_scheduled' => 'boolean',
        'pre_bid_meeting_scheduled' => 'boolean',
        'pre_bid_meeting_mandatory' => 'boolean',
    ];
}
