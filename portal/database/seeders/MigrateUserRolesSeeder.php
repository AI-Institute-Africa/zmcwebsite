<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class MigrateUserRolesSeeder extends Seeder
{
    public function run(): void
    {
        $allowed = [
            'super_admin',
            'accreditation_officer',
            'registrar',
            'accounts_payments',
            'production',
        ];

        User::whereNotNull('role')->each(function ($user) use ($allowed) {
            $role = trim((string) $user->role);

            // Skip applicant/public or unknown values
            if (!in_array($role, $allowed, true)) {
                return;
            }

            $user->syncRoles([$role]); // safer than assignRole for re-runs
        });
    }
}
