<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'accreditation_category_code')) {
                $table->string('accreditation_category_code', 10)->nullable()->index();
            }
            if (!Schema::hasColumn('applications', 'media_house_category_code')) {
                $table->string('media_house_category_code', 10)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // safe rollback omitted
    }
};
