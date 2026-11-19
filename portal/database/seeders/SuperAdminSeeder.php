<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Idempotent super-admin bootstrap.
     *
     * IMPORTANT: This seeder runs on every production deploy (build.sh).
     * It must NEVER overwrite an existing user's password or profile data,
     * otherwise every redeploy would reset the live super-admin password
     * and undo any profile/role changes made through the UI.
     *
     * Behaviour:
     *   - If the super-admin user does not exist -> create it only when
     *     SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD are configured.
     *   - If the super-admin user already exists  -> leave password,
     *     name, account_status, account_type untouched. Only ensure the
     *     `super_admin` role is attached.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $email = trim((string) config('bootstrap.super_admin.email', ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->warn('SUPER_ADMIN_EMAIL is not configured; skipping super-admin bootstrap.');
            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $password = (string) config('bootstrap.super_admin.password', '');
            if ($password === '') {
                $this->command?->warn('SUPER_ADMIN_PASSWORD is not configured; no super-admin account was created.');
                return;
            }

            $user = User::create([
                'email'          => $email,
                'name'           => config('bootstrap.super_admin.name', 'System Administrator'),
                'password'       => Hash::make($password),
                'account_status' => 'active',
                'account_type'   => 'staff',
                'email_verified_at' => now(),
            ]);
        }

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }
    }
}
