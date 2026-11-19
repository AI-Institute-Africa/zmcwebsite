<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('application_documents', function (Blueprint $table) {
      $table->id();
      $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();

      // e.g. national_id, passport_photo, editorial_charter, cashflow_3y, balance_sheet, dummy_magazine etc.
      $table->string('doc_type');
      $table->string('file_path');

      $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
      $table->text('review_notes')->nullable();

      $table->timestamps();

      $table->index(['application_id','doc_type']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('application_documents');
  }
};
