<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accreditation_records', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('accreditation_records', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'surname')) {
                $table->string('surname')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'other_names')) {
                $table->string('other_names')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'organisation')) {
                $table->string('organisation')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'category')) {
                $table->string('category')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'id_number')) {
                $table->string('id_number')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'passport_number')) {
                $table->string('passport_number')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'photo_path')) {
                $table->string('photo_path')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'marital_status')) {
                $table->string('marital_status')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'sex')) {
                $table->string('sex')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'birth_place')) {
                $table->string('birth_place')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'nationality')) {
                $table->string('nationality')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'physical_address')) {
                $table->string('physical_address')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'town')) {
                $table->string('town')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'province')) {
                $table->string('province')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'cell')) {
                $table->string('cell')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'telephone')) {
                $table->string('telephone')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'medium')) {
                $table->string('medium')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'designation')) {
                $table->string('designation')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'accreditation_year')) {
                $table->integer('accreditation_year')->nullable();
            }
            if (!Schema::hasColumn('accreditation_records', 'accreditation_number')) {
                $table->string('accreditation_number')->nullable();
            }

            // Add indexes with short names
            if (!Schema::hasColumn('accreditation_records', 'accreditation_year')) {
                $table->index('accreditation_year', 'acc_year_index');
            }
            if (!Schema::hasColumn('accreditation_records', 'accreditation_number')) {
                $table->index('accreditation_number', 'acc_number_index');
            }
            
            // Add composite index with short name
            try {
                $table->index(['accreditation_year', 'accreditation_number'], 'acc_year_number_index');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            // Add id_number index with short name
            try {
                $table->index('id_number', 'acc_id_number_index');
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('accreditation_records', function (Blueprint $table) {
            // Drop indexes first (short names)
            try {
                $table->dropIndex('acc_year_index');
            } catch (\Exception $e) {}
            try {
                $table->dropIndex('acc_number_index');
            } catch (\Exception $e) {}
            try {
                $table->dropIndex('acc_year_number_index');
            } catch (\Exception $e) {}
            try {
                $table->dropIndex('acc_id_number_index');
            } catch (\Exception $e) {}
            
            // Drop columns
            $columns = [
                'first_name', 'surname', 'other_names', 'organisation', 'category',
                'id_number', 'passport_number', 'photo_path', 'marital_status', 'sex',
                'date_of_birth', 'birth_place', 'nationality', 'physical_address',
                'town', 'province', 'phone', 'cell', 'telephone', 'email',
                'medium', 'designation', 'accreditation_year', 'accreditation_number'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('accreditation_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
