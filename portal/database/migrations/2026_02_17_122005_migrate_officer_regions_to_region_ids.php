<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if region_id column already exists
        $hasRegionId = Schema::hasColumn('officer_regions', 'region_id');
        
        if (!$hasRegionId) {
            // Add region_id column
            Schema::table('officer_regions', function (Blueprint $table) {
                $table->unsignedBigInteger('region_id')->nullable()->after('user_id');
            });
        }
        
        // Drop the old unique index if it exists
        try {
            Schema::table('officer_regions', function (Blueprint $table) {
                $table->dropUnique('officer_regions_user_id_region_unique');
            });
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }
        
        // Drop the old 'region' column if it exists
        if (Schema::hasColumn('officer_regions', 'region')) {
            Schema::table('officer_regions', function (Blueprint $table) {
                $table->dropColumn('region');
            });
        }
        
        // Check if foreign key already exists before adding. information_schema
        // is MySQL-specific; SQLite test databases do not have it.
        $foreignKeyExists = [];
        if (DB::getDriverName() === 'mysql') {
            $foreignKeyExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'officer_regions' 
                AND CONSTRAINT_NAME = 'officer_regions_region_id_foreign'
            ");
        }
        
        if (empty($foreignKeyExists) && Schema::hasTable('regions')) {
            Schema::table('officer_regions', function (Blueprint $table) {
                try {
                    $table->foreign('region_id')
                          ->references('id')
                          ->on('regions')
                          ->onDelete('cascade');
                } catch (\Exception $e) {
                    // Foreign key might already exist
                }
            });
        }
        
        // Add new unique index if it doesn't exist
        try {
            Schema::table('officer_regions', function (Blueprint $table) {
                $table->unique(['user_id', 'region_id'], 'officer_regions_user_region_id_unique');
            });
        } catch (\Exception $e) {
            // Index might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('officer_regions', function (Blueprint $table) {
            // Drop new unique index
            try {
                $table->dropUnique('officer_regions_user_region_id_unique');
            } catch (\Exception $e) {
                // Index doesn't exist
            }
            
            // Drop foreign key
            try {
                $table->dropForeign(['region_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist
            }
            
            // Drop region_id column
            if (Schema::hasColumn('officer_regions', 'region_id')) {
                $table->dropColumn('region_id');
            }
            
            // Add back region column
            if (!Schema::hasColumn('officer_regions', 'region')) {
                $table->enum('region', ['harare', 'bulawayo', 'mutare', 'masvingo'])->after('user_id');
            }
            
            // Add back old unique index
            try {
                $table->unique(['user_id', 'region']);
            } catch (\Exception $e) {
                // Index already exists
            }
        });
    }
};
