<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('application_documents', 'verification_status')) {
                $table->string('verification_status')->nullable()->index();
            }
            if (!Schema::hasColumn('application_documents', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('application_documents', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->index();
            }
            if (!Schema::hasColumn('application_documents', 'verification_notes')) {
                $table->text('verification_notes')->nullable();
            }
            if (!Schema::hasColumn('application_documents', 'risk_score')) {
                $table->unsignedTinyInteger('risk_score')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            foreach (['verification_status','verified_by','verified_at','verification_notes','risk_score'] as $col) {
                if (Schema::hasColumn('application_documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
