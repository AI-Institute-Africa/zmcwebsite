<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const RETIRED_ROLES = [
        'principal_director',
        'executive_secretary',
        'chief_accountant',
    ];

    public function up(): void
    {
        Role::query()
            ->whereIn('name', self::RETIRED_ROLES)
            ->each(fn (Role $role) => $role->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (self::RETIRED_ROLES as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
