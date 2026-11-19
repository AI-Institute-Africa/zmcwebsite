<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('audit_flags')) return;
        Schema::table('audit_flags', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_flags', 'assigned_unit')) $table->string('assigned_unit', 120)->nullable()->index();
            if (!Schema::hasColumn('audit_flags', 'status')) $table->string('status', 40)->default('open')->index();
            if (!Schema::hasColumn('audit_flags', 'response_notes')) $table->text('response_notes')->nullable();
            if (!Schema::hasColumn('audit_flags', 'corrective_action')) $table->text('corrective_action')->nullable();
            if (!Schema::hasColumn('audit_flags', 'due_at')) $table->timestamp('due_at')->nullable();
            if (!Schema::hasColumn('audit_flags', 'attachment_path')) $table->string('attachment_path', 500)->nullable();
            if (!Schema::hasColumn('audit_flags', 'attachment_original_name')) $table->string('attachment_original_name', 255)->nullable();
            if (!Schema::hasColumn('audit_flags', 'resolved_by')) $table->unsignedBigInteger('resolved_by')->nullable();
            if (!Schema::hasColumn('audit_flags', 'resolved_at')) $table->timestamp('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_flags')) return;
        Schema::table('audit_flags', function (Blueprint $table) {
            $columns = ['assigned_unit','status','response_notes','corrective_action','due_at','attachment_path','attachment_original_name','resolved_by','resolved_at'];
            $table->dropColumn(array_values(array_filter($columns, fn ($column) => Schema::hasColumn('audit_flags', $column))));
        });
    }
};
