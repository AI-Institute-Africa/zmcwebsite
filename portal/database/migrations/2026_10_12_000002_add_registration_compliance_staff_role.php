<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $exists = DB::table('roles')
            ->where('name', 'registration_compliance_officer')
            ->where('guard_name', 'web')
            ->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'registration_compliance_officer',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('model_has_roles')) {
            return;
        }

        $roleId = DB::table('roles')
            ->where('name', 'registration_compliance_officer')
            ->where('guard_name', 'web')
            ->value('id');

        if (!$roleId || DB::table('model_has_roles')->where('role_id', $roleId)->exists()) {
            return;
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
        }

        DB::table('roles')->where('id', $roleId)->delete();
    }
};
