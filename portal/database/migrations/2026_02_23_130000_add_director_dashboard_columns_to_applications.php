<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'waiver_approved_by')) {
                $table->unsignedBigInteger('waiver_approved_by')->nullable();
            }
            if (!Schema::hasColumn('applications', 'registrar_approved_at')) {
                $table->timestamp('registrar_approved_at')->nullable();
            }
            if (!Schema::hasColumn('applications', 'card_number')) {
                $table->string('card_number')->nullable();
            }
            if (!Schema::hasColumn('applications', 'certificate_number')) {
                $table->string('certificate_number')->nullable();
            }
            if (!Schema::hasColumn('applications', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            if (!Schema::hasColumn('applications', 'media_house_id')) {
                $table->unsignedBigInteger('media_house_id')->nullable();
            }
            if (!Schema::hasColumn('applications', 'applicant_id')) {
                $table->unsignedBigInteger('applicant_id')->nullable();
            }
            if (!Schema::hasColumn('applications', 'application_number')) {
                $table->string('application_number')->nullable();
            }
            if (!Schema::hasColumn('applications', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }
            if (!Schema::hasColumn('applications', 'reprint_count')) {
                $table->integer('reprint_count')->default(0);
            }
            if (!Schema::hasColumn('applications', 'edited_at')) {
                $table->timestamp('edited_at')->nullable();
            }
            if (!Schema::hasColumn('applications', 'field_name')) {
                $table->string('field_name')->nullable();
            }
            if (!Schema::hasColumn('applications', 'edit_count')) {
                $table->integer('edit_count')->default(0);
            }
            if (!Schema::hasColumn('applications', 'reassignment_count')) {
                $table->integer('reassignment_count')->default(0);
            }
            if (!Schema::hasColumn('applications', 'print_type')) {
                $table->string('print_type')->nullable();
            }
            if (!Schema::hasColumn('applications', 'service_type')) {
                $table->string('service_type')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $cols = ['waiver_approved_by','registrar_approved_at','card_number','certificate_number','payment_method','media_house_id','applicant_id','application_number','confirmed_at','reprint_count','edited_at','field_name','edit_count','reassignment_count','print_type','service_type'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
