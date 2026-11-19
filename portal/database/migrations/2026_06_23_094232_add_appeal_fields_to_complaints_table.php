<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->unique()->after('id');
            $table->foreignId('category_id')->nullable()->after('type')->constrained('appeal_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['reference_number', 'category_id']);
        });
    }
};
