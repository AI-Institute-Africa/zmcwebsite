<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('category')->nullable()->after('body');
            $table->text('tags')->nullable()->after('category');
            $table->string('excerpt', 255)->nullable()->after('tags');
            $table->string('author_name')->nullable()->after('excerpt');
            $table->string('status')->default('draft')->after('author_name');
            $table->boolean('featured')->default(false)->after('status');
            $table->string('language')->nullable()->after('featured');
            $table->string('source_reference')->nullable()->after('language');
            $table->string('image_alt_text')->nullable()->after('source_reference');
            $table->boolean('allow_comments')->default(true)->after('image_alt_text');
            $table->boolean('notify_subscribers')->default(false)->after('allow_comments');
            $table->text('meta_description')->nullable()->after('notify_subscribers');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'tags',
                'excerpt',
                'author_name',
                'status',
                'featured',
                'language',
                'source_reference',
                'image_alt_text',
                'allow_comments',
                'notify_subscribers',
                'meta_description',
            ]);
        });
    }
};
