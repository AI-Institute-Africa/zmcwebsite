<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('generated_documents')) {
            Schema::create('generated_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('application_id');
                $table->unsignedBigInteger('applicant_id')->nullable();
                $table->unsignedBigInteger('media_house_id')->nullable();
                $table->unsignedBigInteger('template_id');
                $table->unsignedBigInteger('template_version_id')->nullable();
                $table->enum('document_type', [
                    'accreditation_card',
                    'registration_certificate',
                    'licence',
                    'renewal_certificate',
                    'replacement_card',
                ]);
                $table->string('document_number')->unique();
                $table->string('qr_code_value')->nullable();
                $table->string('generated_file')->nullable();
                $table->string('generated_preview')->nullable();
                $table->unsignedBigInteger('generated_by');
                $table->timestamp('generated_at')->useCurrent();
                $table->enum('status', [
                    'generated', 'printed', 'reprinted', 'cancelled', 'revoked',
                ])->default('generated');
                $table->text('cancellation_reason')->nullable();
                $table->timestamps();

                $table->foreign('application_id')->references('id')->on('applications');
                $table->foreign('applicant_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('template_id')->references('id')->on('design_studio_templates');
                $table->foreign('generated_by')->references('id')->on('users');

                $table->index('application_id');
                $table->index('document_number');
                $table->index(['document_type', 'status']);
                $table->index('generated_by');
            });
        }

        // Upgrade print_logs to link to generated_documents
        if (Schema::hasTable('print_logs')) {
            Schema::table('print_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('print_logs', 'generated_document_id')) {
                    $table->unsignedBigInteger('generated_document_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('print_logs', 'print_status')) {
                    $table->enum('print_status', ['pending','printed','failed','cancelled'])
                          ->default('pending')->after('printer_name');
                }
                if (!Schema::hasColumn('print_logs', 'print_attempts')) {
                    $table->unsignedSmallInteger('print_attempts')->default(1)->after('print_status');
                }
                if (!Schema::hasColumn('print_logs', 'failure_reason')) {
                    $table->text('failure_reason')->nullable()->after('print_attempts');
                }
                if (!Schema::hasColumn('print_logs', 'reprint_reason')) {
                    $table->text('reprint_reason')->nullable()->after('failure_reason');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
