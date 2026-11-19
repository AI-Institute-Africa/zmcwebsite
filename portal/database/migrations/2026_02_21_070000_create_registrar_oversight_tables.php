<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->index();
            $table->string('document_type'); // card, certificate
            $table->unsignedBigInteger('printed_by');
            $table->string('reason')->default('first_print'); // first_print, reprint
            $table->text('reprint_reason')->nullable();
            $table->string('printer_name')->nullable();
            $table->string('workstation')->nullable();
            $table->timestamp('printed_at');
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
            $table->foreign('printed_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id')->index();
            $table->string('document_type'); // card, certificate
            $table->unsignedBigInteger('created_by');
            $table->json('data_snapshot');
            $table->string('version_hash')->unique();
            $table->text('edit_reason')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'print_count')) {
                $table->integer('print_count')->default(0)->after('issued_at');
            }
            if (!Schema::hasColumn('applications', 'reprint_approved_by')) {
                $table->unsignedBigInteger('reprint_approved_by')->nullable();
            }
            if (!Schema::hasColumn('applications', 'reprint_approved_at')) {
                $table->timestamp('reprint_approved_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['print_count', 'reprint_approved_by', 'reprint_approved_at']);
        });
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('print_logs');
    }
};
