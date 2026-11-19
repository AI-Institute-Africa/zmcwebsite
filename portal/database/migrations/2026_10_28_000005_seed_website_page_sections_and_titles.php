<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SECTIONS = [
        'commissioners' => [
            1 => 'Chairperson',
            2 => 'Vice Chairperson',
            3 => 'Commissioners',
        ],
        'secretariat' => [
            1 => 'Executive Secretary',
            2 => 'Principal Director',
            3 => 'Directors',
            4 => 'Managers, Registrar & Chief Accountant',
            5 => 'Officers & Support Staff',
        ],
    ];

    private const DEFAULT_TITLES = [
        'Chairperson',
        'Vice Chairperson',
        'Commissioner',
        'Executive Secretary',
        'Principal Director',
        'Deputy Director',
        'Director',
        'Manager',
        'Registrar',
        'Chief Accountant',
        'Officer',
        'Support Staff',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::SECTIONS as $pageKey => $sections) {
            foreach ($sections as $order => $name) {
                $exists = DB::table('website_page_sections')
                    ->where('page_key', $pageKey)
                    ->where('name', $name)
                    ->exists();

                if (!$exists) {
                    DB::table('website_page_sections')->insert([
                        'page_key' => $pageKey,
                        'name' => $name,
                        'sort_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Point existing profiles at the section that matches their tier.
        $people = DB::table('website_page_people')->select('id', 'page_key', 'tier')->get();
        foreach ($people as $person) {
            $sectionId = DB::table('website_page_sections')
                ->where('page_key', $person->page_key)
                ->where('sort_order', (int) ($person->tier ?: 3))
                ->value('id');

            if ($sectionId) {
                DB::table('website_page_people')->where('id', $person->id)->update(['section_id' => $sectionId]);
            }
        }

        // Build a reusable title list from the defaults plus every position
        // already captured on a profile.
        $names = self::DEFAULT_TITLES;
        foreach (DB::table('website_page_people')->whereNotNull('position')->pluck('position') as $position) {
            $position = trim((string) $position);
            if ($position !== '') {
                $names[] = $position;
            }
        }

        $seen = [];
        $order = 0;
        foreach ($names as $name) {
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $exists = DB::table('website_page_titles')
                ->whereNull('page_key')
                ->whereRaw('LOWER(name) = ?', [$key])
                ->exists();

            if (!$exists) {
                DB::table('website_page_titles')->insert([
                    'page_key' => null,
                    'name' => $name,
                    'sort_order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $order++;
        }

        // Match existing free-text positions to the new title records.
        foreach ($people as $person) {
            $position = DB::table('website_page_people')->where('id', $person->id)->value('position');
            if (!$position) {
                continue;
            }

            $titleId = DB::table('website_page_titles')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($position))])
                ->value('id');

            if ($titleId) {
                DB::table('website_page_people')->where('id', $person->id)->update(['title_id' => $titleId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('website_page_people', 'title_id')) {
            DB::table('website_page_people')->update(['title_id' => null]);
        }
        if (Schema::hasColumn('website_page_people', 'section_id')) {
            DB::table('website_page_people')->update(['section_id' => null]);
        }

        DB::table('website_page_titles')->delete();
        DB::table('website_page_sections')->delete();
    }
};
