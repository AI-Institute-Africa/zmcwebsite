<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('website_page_people')
            ->where('page_key', 'secretariat')
            ->where(function ($query) {
                $query->where('name', 'like', 'Manager — %')
                    ->orWhere('name', 'like', 'Director — %')
                    ->orWhere('name', 'like', 'Registrar — %')
                    ->orWhere('name', 'like', 'Acting Chief Accountant — %')
                    ->orWhere('name', 'like', 'Regional Manager %');
            })
            ->where(function ($query) {
                $query->whereNull('biography')->orWhere('biography', '');
            })
            ->update([
                'biography' => 'Legacy website profile. Update the person name and details before publishing.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('website_page_people')
            ->where('page_key', 'secretariat')
            ->where('biography', 'Legacy website profile. Update the person name and details before publishing.')
            ->update(['biography' => null, 'updated_at' => now()]);
    }
};
