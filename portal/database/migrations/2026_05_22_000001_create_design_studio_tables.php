<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Design Studio Templates ──────────────────────────────────────────
        if (!Schema::hasTable('design_studio_templates')) {
            Schema::create('design_studio_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['card', 'certificate']);
                $table->enum('template_for', [
                    'accreditation_local', 'accreditation_foreign', 'registration',
                ]);
                $table->string('year', 4);
                $table->string('category_code')->nullable();   // JE, JF, MC, MA, etc.
                $table->string('application_type')->nullable(); // accreditation | registration
                $table->string('background_file')->nullable();
                $table->decimal('width', 8, 2)->nullable();
                $table->decimal('height', 8, 2)->nullable();
                $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
                $table->json('layout_config')->nullable();     // Fabric.js canvas JSON
                $table->enum('status', [
                    'draft',
                    'pending_approval',
                    'approved',
                    'rejected',
                    'archived',
                ])->default('draft');
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users');
                $table->foreign('submitted_by')->references('id')->on('users');
                $table->foreign('approved_by')->references('id')->on('users');
                $table->foreign('rejected_by')->references('id')->on('users');
                $table->foreign('archived_by')->references('id')->on('users');

                $table->index(['type', 'status']);
                $table->index(['year', 'status']);
                $table->index('created_by');
            });
        }

        // ── Template Fields ──────────────────────────────────────────────────
        if (!Schema::hasTable('design_studio_template_fields')) {
            Schema::create('design_studio_template_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')
                      ->constrained('design_studio_templates')
                      ->cascadeOnDelete();
                $table->string('field_key');       // e.g. full_name, photo, qr_code
                $table->string('field_label');
                $table->enum('field_type', [
                    'text', 'image', 'photo', 'qr', 'signature', 'stamp', 'date',
                ]);
                $table->decimal('x_position', 8, 2)->default(0);
                $table->decimal('y_position', 8, 2)->default(0);
                $table->decimal('width', 8, 2)->default(100);
                $table->decimal('height', 8, 2)->default(30);
                $table->string('font_family')->default('Arial');
                $table->unsignedSmallInteger('font_size')->default(12);
                $table->string('font_weight')->default('normal');
                $table->string('text_align')->default('left');
                $table->string('color')->default('#000000');
                $table->string('background_color')->nullable();
                $table->smallInteger('rotation')->default(0);
                $table->unsignedSmallInteger('z_index')->default(1);
                $table->boolean('is_required')->default(false);
                $table->timestamps();

                $table->index('template_id');
            });
        }

        // ── Template Versions ────────────────────────────────────────────────
        if (!Schema::hasTable('design_studio_template_versions')) {
            Schema::create('design_studio_template_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')
                      ->constrained('design_studio_templates')
                      ->cascadeOnDelete();
                $table->unsignedSmallInteger('version_number')->default(1);
                $table->json('snapshot_json');     // full layout_config snapshot
                $table->unsignedBigInteger('changed_by');
                $table->text('change_reason')->nullable();
                $table->timestamps();

                $table->foreign('changed_by')->references('id')->on('users');
                $table->index('template_id');
            });
        }

        // ── Design Audit Logs ────────────────────────────────────────────────
        if (!Schema::hasTable('design_studio_audit_logs')) {
            Schema::create('design_studio_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->string('entity_type');     // DesignStudioTemplate, etc.
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['entity_type', 'entity_id']);
                $table->index('user_id');
                $table->index('action');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('design_studio_audit_logs');
        Schema::dropIfExists('design_studio_template_versions');
        Schema::dropIfExists('design_studio_template_fields');
        Schema::dropIfExists('design_studio_templates');
    }
};
