<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['created_at', 'actor_user_id'], 'audit_logs_created_actor_idx');
                $table->index(['action', 'created_at'], 'audit_logs_action_created_idx');
                $table->index(['model_type', 'model_id'], 'audit_logs_model_pair_idx');
            });

            if (Schema::hasColumn('audit_logs', 'user_role')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index('user_role', 'audit_logs_user_role_idx');
                });
            }

            if (Schema::hasColumn('audit_logs', 'application_id')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index('application_id', 'audit_logs_application_idx');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_created_actor_idx');
                $table->dropIndex('audit_logs_action_created_idx');
                $table->dropIndex('audit_logs_model_pair_idx');
            });

            if (Schema::hasColumn('audit_logs', 'user_role')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('audit_logs_user_role_idx');
                });
            }

            if (Schema::hasColumn('audit_logs', 'application_id')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropIndex('audit_logs_application_idx');
                });
            }
        }
    }
};
