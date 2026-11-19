<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('website_page_sections')) {
            DB::table('website_page_sections')
                ->where('page_key', 'secretariat')
                ->where('name', 'Principal Director')
                ->update(['name' => 'Chief Director', 'updated_at' => $now]);

            DB::table('website_page_sections')
                ->where('page_key', 'secretariat')
                ->where('name', 'Managers, Registrar & Chief Accountant')
                ->update(['name' => 'Managers & Regional Managers', 'updated_at' => $now]);
        }

        if (!Schema::hasTable('website_page_people')) {
            return;
        }

        // Ensure the editable title is available to the PR Officer.
        $chiefDirectorTitle = DB::table('website_page_titles')
            ->whereRaw('LOWER(name) = ?', ['chief director'])
            ->value('id');
        if (!$chiefDirectorTitle) {
            $chiefDirectorTitle = DB::table('website_page_titles')->insertGetId([
                'page_key' => null,
                'name' => 'Chief Director',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $regionalManagerTitle = DB::table('website_page_titles')
            ->whereRaw('LOWER(name) = ?', ['regional manager'])
            ->value('id');
        if (!$regionalManagerTitle) {
            $regionalManagerTitle = DB::table('website_page_titles')->insertGetId([
                'page_key' => 'secretariat',
                'name' => 'Regional Manager',
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $chiefDirector = DB::table('website_page_people')
            ->where('page_key', 'secretariat')
            ->where(function ($query) {
                $query->whereRaw('LOWER(position) IN (?, ?)', ['principal director', 'chief director'])
                    ->orWhere('name', 'Academy Bvumayi Chinamhora');
            })
            ->orderBy('id')
            ->first();

        if ($chiefDirector) {
            DB::table('website_page_people')->where('id', $chiefDirector->id)->update([
                'position' => 'Chief Director',
                'title_id' => $chiefDirectorTitle,
                'tier' => 2,
                'is_published' => true,
                'updated_at' => $now,
            ]);
        }

        // Publish all existing Secretariat management posts. Profiles with no
        // portrait receive the managed placeholder until a PR Officer replaces it.
        DB::table('website_page_people')
            ->where('page_key', 'secretariat')
            ->update(['is_published' => true, 'updated_at' => $now]);

        DB::table('website_page_people')
            ->where('page_key', 'secretariat')
            ->where('department', 'Finance, Administration and HR')
            ->update(['department' => 'Finance, Administration & HR', 'tier' => 2, 'updated_at' => $now]);

        DB::table('website_page_people')
            ->where('page_key', 'secretariat')
            ->where('department', 'Media Development and Governance')
            ->update(['tier' => 2, 'updated_at' => $now]);

        $managersSection = DB::table('website_page_sections')
            ->where('page_key', 'secretariat')
            ->where(function ($query) {
                $query->where('name', 'Managers & Regional Managers')
                    ->orWhere('name', 'Managers, Registrar & Chief Accountant');
            })
            ->value('id');

        if ($managersSection) {
            DB::table('website_page_people')
                ->where('page_key', 'secretariat')
                ->where('department', 'Legal Services')
                ->update(['section_id' => $managersSection, 'tier' => 3, 'is_published' => true, 'updated_at' => $now]);
        }

        if (Schema::hasColumn('website_page_people', 'image_path')) {
            DB::table('website_page_people')
                ->where('page_key', 'secretariat')
                ->whereNull('image_path')
                ->update(['image_path' => 'website/people/zmc-placeholder.png', 'updated_at' => $now]);
        }

        // Four editable slots for regional managers. The PR Officer can update
        // the name, region/department and portrait from Website People.
        for ($i = 1; $i <= 4; $i++) {
            $region = 'Region ' . $i;
            $exists = DB::table('website_page_people')
                ->where('page_key', 'secretariat')
                ->where('position', 'Regional Manager')
                ->where('department', $region)
                ->exists();

            if (!$exists) {
                DB::table('website_page_people')->insert([
                    'page_key' => 'secretariat',
                    'section_id' => $managersSection,
                    'title_id' => $regionalManagerTitle,
                    'tier' => 3,
                    'name' => 'Regional Manager ' . $i,
                    'position' => 'Regional Manager',
                    'department' => $region,
                    'biography' => null,
                    'image_path' => 'website/people/zmc-placeholder.png',
                    'sort_order' => 90 + $i,
                    'is_published' => true,
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep profile/content changes reversible without deleting user data.
        DB::table('website_page_people')
            ->where('page_key', 'secretariat')
            ->where('position', 'Regional Manager')
            ->whereIn('department', ['Region 1', 'Region 2', 'Region 3', 'Region 4'])
            ->delete();
    }
};
