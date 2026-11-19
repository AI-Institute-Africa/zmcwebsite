<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_log_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_role')->nullable()->index();
            $table->string('action', 120)->index();
            $table->string('action_type')->nullable()->index();
            $table->string('module', 80)->nullable()->index();
            $table->string('model_type')->nullable()->index();
            $table->unsignedBigInteger('model_id')->nullable()->index();
            $table->string('record_number', 120)->nullable()->index();
            $table->string('office_branch', 120)->nullable()->index();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_system_generated')->default(false)->index();
            $table->unsignedBigInteger('application_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->unsignedSmallInteger('archive_year')->index();

            $table->index(['created_at', 'actor_user_id']);
            $table->index(['model_type', 'model_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_archives');
    }
};
