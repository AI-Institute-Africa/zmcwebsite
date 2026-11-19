<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    protected $fillable = [
        'title',
        'vacancy_reference',
        'department',
        'job_grade',
        'employment_type',
        'contract_duration',
        'salary_range',
        'salary_display',
        'reports_to',
        'supervises',
        'body',
        'qualifications_required',
        'experience_required',
        'skills_competencies',
        'duty_station',
        'application_requirements',
        'submission_method',
        'submission_email',
        'submission_address',
        'closing_at',
        'contact_name',
        'contact_title',
        'contact_email',
        'contact_phone',
        'preferred_applicant_categories',
        'shortlisting_criteria',
        'interview_dates',
        'expected_start_date',
        'vacancy_status',
        'number_of_positions',
        'language_requirements',
        'target_portal',
        'is_published',
        'published_at',
        'created_by',
        'image_path',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'closing_at' => 'datetime',
        'expected_start_date' => 'date',
        'salary_display' => 'boolean',
        'application_requirements' => 'array',
        'preferred_applicant_categories' => 'array',
    ];
}
