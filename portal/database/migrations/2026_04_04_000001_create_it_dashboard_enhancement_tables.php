<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('warning');
            $table->string('status')->default('open');
            $table->string('module')->nullable();
            $table->string('source')->default('auto');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'severity']);
            $table->index('module');
        });

        Schema::create('module_health_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('module_name')->unique();
            $table->string('display_name');
            $table->string('category')->default('core');
            $table->string('status')->default('healthy');
            $table->string('response_state')->nullable();
            $table->integer('affected_users')->default(0);
            $table->text('probable_cause')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('service_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('status')->default('healthy');
            $table->integer('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['service_name', 'checked_at']);
        });

        Schema::create('temporary_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('base_role');
            $table->string('temporary_role');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->string('status')->default('active');
            $table->boolean('auto_expiry')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('region_id')->nullable();
            $table->string('location_type')->default('regional_office');
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->index(['location_type', 'status']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('printer');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('status')->default('online');
            $table->timestamp('last_heartbeat')->nullable();
            $table->integer('queue_size')->default(0);
            $table->integer('failed_jobs')->default(0);
            $table->timestamp('last_successful_job')->nullable();
            $table->string('connection_status')->default('connected');
            $table->text('diagnostics_notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
        });

        Schema::create('helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->string('category')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('resolution_summary')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('change_type');
            $table->string('module')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['module', 'created_at']);
        });

        if (!Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('department')->nullable()->after('designation');
                $table->string('supervisor_id')->nullable()->after('department');
                $table->string('officer_availability')->default('available')->after('account_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('change_logs');
        Schema::dropIfExists('helpdesk_tickets');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('temporary_role_assignments');
        Schema::dropIfExists('service_heartbeats');
        Schema::dropIfExists('module_health_statuses');
        Schema::dropIfExists('system_incidents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['department', 'supervisor_id', 'officer_availability']);
        });
    }
};
