<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('vacancy_reference')->nullable()->after('title');
            $table->string('department')->nullable()->after('vacancy_reference');
            $table->string('job_grade')->nullable()->after('department');
            $table->string('employment_type')->nullable()->after('job_grade');
            $table->string('contract_duration')->nullable()->after('employment_type');
            $table->string('salary_range')->nullable()->after('contract_duration');
            $table->boolean('salary_display')->default(false)->after('salary_range');
            $table->string('reports_to')->nullable()->after('salary_display');
            $table->string('supervises')->nullable()->after('reports_to');
            $table->text('qualifications_required')->nullable()->after('supervises');
            $table->text('experience_required')->nullable()->after('qualifications_required');
            $table->text('skills_competencies')->nullable()->after('experience_required');
            $table->string('duty_station')->nullable()->after('skills_competencies');
            $table->text('application_requirements')->nullable()->after('duty_station');
            $table->string('submission_method')->nullable()->after('application_requirements');
            $table->string('submission_email')->nullable()->after('submission_method');
            $table->string('submission_address')->nullable()->after('submission_email');
            $table->string('contact_name')->nullable()->after('submission_address');
            $table->string('contact_title')->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_title');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('preferred_applicant_categories')->nullable()->after('contact_phone');
            $table->text('shortlisting_criteria')->nullable()->after('preferred_applicant_categories');
            $table->string('interview_dates')->nullable()->after('shortlisting_criteria');
            $table->date('expected_start_date')->nullable()->after('interview_dates');
            $table->string('vacancy_status')->default('Open')->after('expected_start_date');
            $table->integer('number_of_positions')->default(1)->after('vacancy_status');
            $table->string('language_requirements')->nullable()->after('number_of_positions');
            $table->string('target_portal')->nullable()->after('language_requirements');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn([
                'vacancy_reference',
                'department',
                'job_grade',
                'employment_type',
                'contract_duration',
                'salary_range',
                'salary_display',
                'reports_to',
                'supervises',
                'qualifications_required',
                'experience_required',
                'skills_competencies',
                'duty_station',
                'application_requirements',
                'submission_method',
                'submission_email',
                'submission_address',
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
            ]);
        });
    }
};
