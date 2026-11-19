<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('print_logs')) {
            Schema::table('print_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('print_logs', 'print_type')) {
                    $table->string('print_type')->nullable();
                }
                if (!Schema::hasColumn('print_logs', 'reprint_count')) {
                    $table->integer('reprint_count')->default(0);
                }
            });
        }

        if (Schema::hasTable('audit_flags')) {
            Schema::table('audit_flags', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_flags', 'flag_type')) {
                    $table->string('flag_type')->nullable();
                }
                if (!Schema::hasColumn('audit_flags', 'resolved')) {
                    $table->boolean('resolved')->default(false);
                }
                if (!Schema::hasColumn('audit_flags', 'application_id')) {
                    $table->unsignedBigInteger('application_id')->nullable();
                }
                if (!Schema::hasColumn('audit_flags', 'details')) {
                    $table->text('details')->nullable();
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'action_type')) {
                    $table->string('action_type')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'user_role')) {
                    $table->string('user_role')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'application_id')) {
                    $table->unsignedBigInteger('application_id')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('print_logs')) {
            Schema::table('print_logs', function (Blueprint $table) {
                $table->dropColumn(array_filter(['print_type', 'reprint_count'], fn($c) => Schema::hasColumn('print_logs', $c)));
            });
        }
        if (Schema::hasTable('audit_flags')) {
            Schema::table('audit_flags', function (Blueprint $table) {
                $table->dropColumn(array_filter(['flag_type', 'resolved', 'application_id', 'details'], fn($c) => Schema::hasColumn('audit_flags', $c)));
            });
        }
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn(array_filter(['action_type', 'user_id', 'user_role', 'application_id'], fn($c) => Schema::hasColumn('audit_logs', $c)));
            });
        }
    }
};
