<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module', 80)->nullable()->index();
            }
            if (!Schema::hasColumn('audit_logs', 'record_number')) {
                $table->string('record_number', 120)->nullable()->index();
            }
            if (!Schema::hasColumn('audit_logs', 'office_branch')) {
                $table->string('office_branch', 120)->nullable()->index();
            }
            if (!Schema::hasColumn('audit_logs', 'old_value')) {
                $table->json('old_value')->nullable();
            }
            if (!Schema::hasColumn('audit_logs', 'new_value')) {
                $table->json('new_value')->nullable();
            }
            if (!Schema::hasColumn('audit_logs', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            if (!Schema::hasColumn('audit_logs', 'is_system_generated')) {
                $table->boolean('is_system_generated')->default(false)->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $columns = [
                'module',
                'record_number',
                'office_branch',
                'old_value',
                'new_value',
                'rejection_reason',
                'is_system_generated',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
