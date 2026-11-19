<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->string('tender_reference')->nullable()->after('title');
            $table->string('procurement_method')->nullable()->after('tender_reference');
            $table->string('tender_category')->nullable()->after('procurement_method');
            $table->string('contract_value_estimate')->nullable()->after('tender_category');
            $table->boolean('bid_security_required')->default(false)->after('contract_value_estimate');
            $table->string('bid_security_amount')->nullable()->after('bid_security_required');
            $table->string('preferred_bidder_categories')->nullable()->after('bid_security_amount');
            $table->boolean('site_visit_scheduled')->default(false)->after('preferred_bidder_categories');
            $table->date('site_visit_date')->nullable()->after('site_visit_scheduled');
            $table->string('site_visit_time')->nullable()->after('site_visit_date');
            $table->text('site_visit_notes')->nullable()->after('site_visit_time');
            $table->boolean('pre_bid_meeting_scheduled')->default(false)->after('site_visit_notes');
            $table->date('pre_bid_meeting_date')->nullable()->after('pre_bid_meeting_scheduled');
            $table->string('pre_bid_meeting_time')->nullable()->after('pre_bid_meeting_date');
            $table->string('pre_bid_meeting_venue')->nullable()->after('pre_bid_meeting_time');
            $table->boolean('pre_bid_meeting_mandatory')->default(false)->after('pre_bid_meeting_venue');
            $table->text('submission_method')->nullable()->after('pre_bid_meeting_mandatory');
            $table->string('submission_address')->nullable()->after('submission_method');
            $table->string('submission_email')->nullable()->after('submission_address');
            $table->datetime('opening_at')->nullable()->after('submission_email');
            $table->string('opening_venue')->nullable()->after('opening_at');
            $table->string('contact_name')->nullable()->after('opening_venue');
            $table->string('contact_title')->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_title');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->date('questions_deadline')->nullable()->after('contact_phone');
            $table->string('bidding_documents_link')->nullable()->after('questions_deadline');
            $table->text('evaluation_criteria')->nullable()->after('bidding_documents_link');
            $table->string('tender_status')->default('Open')->after('evaluation_criteria');
            $table->string('awarded_to')->nullable()->after('tender_status');
            $table->string('award_amount')->nullable()->after('awarded_to');
            $table->string('contract_number')->nullable()->after('award_amount');
            $table->string('language')->nullable()->after('contract_number');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn([
                'tender_reference',
                'procurement_method',
                'tender_category',
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
            ]);
        });
    }
};
