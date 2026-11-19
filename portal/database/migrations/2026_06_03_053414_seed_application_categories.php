<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            // ACCREDITATION
            ['portal_type' => 'accreditation', 'code' => 'JT', 'name' => 'Foreign media practitioner', 'category_order' => 1],
            
            // Trained Journalists
            ['portal_type' => 'accreditation', 'code' => 'JE', 'name' => 'Local media practitioner employed on full-time basis (Trained)', 'category_order' => 2],
            ['portal_type' => 'accreditation', 'code' => 'JF', 'name' => 'Local journalist free-lancing locally (Trained)', 'category_order' => 3],
            ['portal_type' => 'accreditation', 'code' => 'JS', 'name' => 'Local journalist stringing for foreign media service (Trained)', 'category_order' => 4],
            ['portal_type' => 'accreditation', 'code' => 'JO', 'name' => 'Local journalist running an office for foreign media service (Trained)', 'category_order' => 5],
            ['portal_type' => 'accreditation', 'code' => 'JM', 'name' => 'Local journalist reporting both locally and abroad (Trained)', 'category_order' => 6],
            
            // Passionate Media Creators
            ['portal_type' => 'accreditation', 'code' => 'JP', 'name' => 'Local media practitioner in content creation, photography, PR, digital media (Passionate)', 'category_order' => 7],
            ['portal_type' => 'accreditation', 'code' => 'JD', 'name' => 'Local media practitioner in digital social media (Passionate)', 'category_order' => 8],

            // REGISTRATION
            ['portal_type' => 'registration', 'code' => 'MF', 'name' => 'Foreign representative media organizations', 'category_order' => 1],
            ['portal_type' => 'registration', 'code' => 'MC', 'name' => 'Community Media', 'category_order' => 2],
            ['portal_type' => 'registration', 'code' => 'MA', 'name' => 'Advertising agency as media service', 'category_order' => 3],
            ['portal_type' => 'registration', 'code' => 'MV', 'name' => 'Media service in film and video production', 'category_order' => 4],
            ['portal_type' => 'registration', 'code' => 'MN', 'name' => 'National media service publishing newspaper', 'category_order' => 5],
            ['portal_type' => 'registration', 'code' => 'DG', 'name' => 'Internet base media service', 'category_order' => 6],
            ['portal_type' => 'registration', 'code' => 'MP', 'name' => 'Production house as media service', 'category_order' => 7],
            ['portal_type' => 'registration', 'code' => 'MS', 'name' => 'Media service fitting multiple categories', 'category_order' => 8],
        ];

        foreach ($categories as $cat) {
            DB::table('application_categories')->updateOrInsert(
                ['portal_type' => $cat['portal_type'], 'code' => $cat['code']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['name'],
                    'is_active' => true,
                    'category_order' => $cat['category_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('application_categories')->whereIn('code', [
            'JT', 'JE', 'JF', 'JS', 'JO', 'JM', 'JP', 'JD',
            'MF', 'MC', 'MA', 'MV', 'MN', 'DG', 'MP', 'MS'
        ])->delete();
    }
};
