<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auditor_user_id')->index();
            $table->string('entity_type', 80)->index(); // e.g. application, payment_proof, waiver
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('severity', 30)->default('medium')->index(); // low|medium|high
            $table->text('reason');
            $table->timestamps();

            $table->foreign('auditor_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_flags');
    }
};
