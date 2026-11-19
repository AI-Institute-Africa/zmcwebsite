<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compliance_violations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->index();
            $table->string('category')->nullable()->index();
            $table->enum('status', ['open','under_investigation','closed'])->default('open')->index();
            $table->text('summary')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('compliance_cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->index();
            $table->enum('status', ['open','assigned','closed'])->default('open')->index();
            $table->text('summary')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('unaccredited_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->index();
            $table->string('subject_name')->nullable();
            $table->string('entity_name')->nullable();
            $table->enum('status', ['open','reviewing','closed'])->default('open')->index();
            $table->text('notes')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('compliance_evidence_files', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type')->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_evidence_files');
        Schema::dropIfExists('unaccredited_reports');
        Schema::dropIfExists('compliance_cases');
        Schema::dropIfExists('compliance_violations');
    }
};
