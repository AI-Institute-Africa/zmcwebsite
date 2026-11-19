<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (!Schema::hasColumn('notices', 'summary')) {
                $table->text('summary')->nullable()->after('body');
            }
            if (!Schema::hasColumn('notices', 'target_audience')) {
                $table->string('target_audience')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('notices', 'issuing_authority')) {
                $table->string('issuing_authority')->nullable()->after('target_audience');
            }
            if (!Schema::hasColumn('notices', 'signed_by')) {
                $table->string('signed_by')->nullable()->after('issuing_authority');
            }
            if (!Schema::hasColumn('notices', 'urgency_level')) {
                $table->string('urgency_level')->default('Normal')->after('signed_by');
            }
            if (!Schema::hasColumn('notices', 'stick_to_top')) {
                $table->boolean('stick_to_top')->default(false)->after('urgency_level');
            }
            if (!Schema::hasColumn('notices', 'show_on_homepage')) {
                $table->boolean('show_on_homepage')->default(false)->after('stick_to_top');
            }
            if (!Schema::hasColumn('notices', 'send_email_alert')) {
                $table->boolean('send_email_alert')->default(false)->after('show_on_homepage');
            }
            if (!Schema::hasColumn('notices', 'language')) {
                $table->string('language')->nullable()->after('send_email_alert');
            }
            if (!Schema::hasColumn('notices', 'attachment_label')) {
                $table->string('attachment_label')->nullable()->after('language');
            }
            if (!Schema::hasColumn('notices', 'related_legal_reference')) {
                $table->string('related_legal_reference')->nullable()->after('attachment_label');
            }
            if (!Schema::hasColumn('notices', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('related_legal_reference');
            }
            if (!Schema::hasColumn('notices', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('notices', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn([
                'summary',
                'target_audience',
                'issuing_authority',
                'signed_by',
                'urgency_level',
                'stick_to_top',
                'show_on_homepage',
                'send_email_alert',
                'language',
                'attachment_label',
                'related_legal_reference',
                'contact_name',
                'contact_phone',
                'contact_email',
            ]);
        });
    }
};
