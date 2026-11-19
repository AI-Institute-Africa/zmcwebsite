<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->string('user_role')->nullable()->index();

            $table->string('action')->index();
            $table->string('entity_type')->index();
            $table->unsignedBigInteger('entity_id')->index();

            $table->string('from_status')->nullable()->index();
            $table->string('to_status')->nullable()->index();

            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
