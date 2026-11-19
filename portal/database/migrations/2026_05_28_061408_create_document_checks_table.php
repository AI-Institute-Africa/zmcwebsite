<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('application_documents')->nullOnDelete();
            
            // Check type: virus_scan, mime_verify, file_health, duplicate_check
            $table->string('check_type');
            
            // Result status: pass, fail, warning
            $table->enum('status', ['pass', 'fail', 'warning']);
            
            // Detailed results stored as JSON
            $table->json('details')->nullable();
            
            // Error message if check failed
            $table->text('error_message')->nullable();
            
            // Confidence score (0-100) if applicable
            $table->unsignedTinyInteger('confidence_score')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['application_id', 'check_type']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_checks');
    }
};
