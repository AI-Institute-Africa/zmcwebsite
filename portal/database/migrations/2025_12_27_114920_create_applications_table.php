<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('applications', function (Blueprint $table) {
      $table->id();

      $table->string('reference')->unique();

      $table->foreignId('applicant_user_id')->constrained('users')->cascadeOnDelete();

      // accreditation | registration
      $table->enum('application_type', ['accreditation', 'registration']);

      // new | renewal | replacement
      $table->enum('request_type', ['new', 'renewal', 'replacement']);

      // Only for accreditation (local/foreign journalist)
      $table->enum('journalist_scope', ['local', 'foreign'])->nullable();

      // region selected by applicant for collection
      $table->enum('collection_region', ['harare', 'bulawayo', 'mutare', 'masvingo']);

      // workflow statuses
      $table->enum('status', [
        'submitted',
        'needs_correction',
        'rejected',
        'approved_pending_payment',
        'paid',
        'returned_from_payments',
        'returned_from_registrar',
      ])->default('submitted');

      // Assignment
      $table->foreignId('assigned_officer_id')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamp('assigned_at')->nullable();

      // Decisions
      $table->timestamp('approved_at')->nullable();
      $table->timestamp('rejected_at')->nullable();
      $table->text('decision_notes')->nullable();

      // Payment / waiver fields (reviewed by Accounts)
      $table->string('paynow_reference')->nullable();
      $table->string('waiver_path')->nullable();
      $table->enum('payment_status', ['none','requested','uploaded_waiver','paid','rejected'])
            ->default('none');

      $table->timestamps();

      $table->index(['status', 'assigned_officer_id']);
      $table->index(['collection_region', 'status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('applications');
  }
};
