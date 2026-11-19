<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accreditation_records', function (Blueprint $table) {
            $table->id();

            // Link to the applicant/user (journalist)
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();

            $table->string('certificate_no')->nullable()->index();
            $table->string('record_number')->nullable()->index();

            $table->enum('status', ['active','expired','suspended','revoked'])->default('active')->index();

            $table->date('issued_at')->nullable()->index();
            $table->date('expires_at')->nullable()->index();

            // For QR verification (public check endpoint can validate token)
            $table->string('qr_token')->nullable()->unique();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditation_records');
    }
};
