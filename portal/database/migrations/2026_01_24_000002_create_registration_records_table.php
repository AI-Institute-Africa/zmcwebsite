<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registration_records', function (Blueprint $table) {
            $table->id();

            // Link to a contact user (media house portal owner) + original application
            $table->foreignId('contact_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();

            $table->string('entity_name')->nullable()->index();
            $table->string('registration_no')->nullable()->index();
            $table->string('record_number')->nullable()->index();

            $table->enum('status', ['active','expired','suspended','revoked'])->default('active')->index();

            $table->date('issued_at')->nullable()->index();
            $table->date('expires_at')->nullable()->index();

            $table->string('qr_token')->nullable()->unique();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_records');
    }
};
