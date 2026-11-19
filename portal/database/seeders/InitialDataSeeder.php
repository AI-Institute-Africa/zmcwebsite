<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Region;
use App\Models\SystemConfig;
use App\Models\CardTemplate;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Essential system data — always seed, idempotent.
        $this->seedRegions();
        $this->seedSystemConfigs();
        $this->seedCardTemplates();

        // IMPORTANT: NO DEMO DATA IN PRODUCTION
        // Only seed essential staff users that are required for the system to function
        $this->seedEssentialStaffUsers();
    }

    private function seedEssentialStaffUsers(): void
    {
        $email = trim((string) config('bootstrap.system_admin.email', ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->warn('SYSTEM_ADMIN_EMAIL is not configured; skipping system-admin bootstrap.');
            return;
        }

        $staff = [
            [
                'name' => 'System Administrator',
                'email' => $email,
                'role' => 'super_admin',
                'designation' => 'System Administrator',
            ],
        ];

        foreach ($staff as $s) {
            $user = User::where('email', $s['email'])->first();
            if (! $user) {
                $password = (string) config('bootstrap.system_admin.password', '');
                if ($password === '') {
                    $this->command?->warn('SYSTEM_ADMIN_PASSWORD is not configured; no system-admin account was created.');
                    continue;
                }

                $user = User::create([
                    'email' => $s['email'],
                    'name' => $s['name'],
                    'password' => Hash::make($password),
                    'account_status' => 'active',
                    'account_type' => 'staff',
                    'designation' => $s['designation'],
                    'email_verified_at' => now(),
                ]);
            }

            if (!$user->hasRole($s['role'])) {
                $user->assignRole($s['role']);
            }
        }
    }

    private function seedRegions(): void
    {
        $regions = [
            ['name' => 'Harare', 'code' => 'HRE'],
            ['name' => 'Bulawayo', 'code' => 'BYO'],
        ];

        foreach ($regions as $region) {
            Region::firstOrCreate(
                ['code' => $region['code']],
                [
                    'name' => $region['name'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedSystemConfigs(): void
    {
        $configs = [
            ['key' => 'accreditation_fee_local_new', 'value' => '50'],
            ['key' => 'accreditation_fee_local_renewal', 'value' => '30'],
            ['key' => 'accreditation_fee_foreign_new', 'value' => '150'],
            ['key' => 'accreditation_fee_foreign_renewal', 'value' => '100'],
            ['key' => 'registration_fee_media_house_new', 'value' => '500'],
            ['key' => 'registration_fee_media_house_renewal', 'value' => '300'],
            ['key' => 'card_validity_years', 'value' => '1'],
            ['key' => 'application_processing_days', 'value' => '14'],
            ['key' => 'application_fee_percentage', 'value' => '50'],
            ['key' => 'max_file_size_mb', 'value' => '10'],
            ['key' => 'allowed_file_types', 'value' => 'pdf,jpg,jpeg,png'],
        ];

        foreach ($configs as $config) {
            SystemConfig::firstOrCreate(
                ['key' => $config['key']],
                ['value' => $config['value']]
            );
        }
    }

    private function seedCardTemplates(): void
    {
        // Find the system admin user or use ID 1 as fallback
        $adminEmail = trim((string) config('bootstrap.system_admin.email', ''));
        $adminUser = $adminEmail !== '' ? User::where('email', $adminEmail)->first() : null;
        $createdBy = $adminUser?->id ?? User::query()->value('id');
        if (! $createdBy) {
            $this->command?->warn('No user exists; skipping default card-template bootstrap.');
            return;
        }

        // Only create templates if they don't exist
        $templateYear = now()->year;
        CardTemplate::firstOrCreate(
            ['name' => "Standard Press Card {$templateYear}"],
            [
                'type' => 'card',
                'year' => $templateYear,
                'layout_config' => [
                    'fields' => [
                        ['name' => 'full_name', 'label' => 'Name', 'x' => 200, 'y' => 180, 'width' => 400, 'height' => 40, 'fontSize' => 22, 'color' => '#000000'],
                        ['name' => 'accreditation_number', 'label' => 'Accred No.', 'x' => 200, 'y' => 230, 'width' => 300, 'height' => 30, 'fontSize' => 16, 'color' => '#333333'],
                        ['name' => 'category', 'label' => 'Category', 'x' => 200, 'y' => 270, 'width' => 200, 'height' => 30, 'fontSize' => 14, 'color' => '#555555'],
                        ['name' => 'expiry_date', 'label' => 'Expiry', 'x' => 200, 'y' => 310, 'width' => 200, 'height' => 30, 'fontSize' => 14, 'color' => '#555555'],
                        ['name' => 'photo', 'label' => 'Photo', 'x' => 30, 'y' => 150, 'width' => 150, 'height' => 180],
                        ['name' => 'qr_code', 'label' => 'QR Code', 'x' => 680, 'y' => 350, 'width' => 120, 'height' => 120],
                    ],
                ],
                'is_active' => true,
                'created_by' => $createdBy,
            ]
        );

        CardTemplate::firstOrCreate(
            ['name' => "Standard Certificate {$templateYear}"],
            [
                'type' => 'certificate',
                'year' => $templateYear,
                'layout_config' => [
                    'fields' => [
                        ['name' => 'media_house_name', 'label' => 'Media House', 'x' => 150, 'y' => 200, 'width' => 550, 'height' => 50, 'fontSize' => 28, 'color' => '#1a3a1a'],
                        ['name' => 'registration_number', 'label' => 'Reg No.', 'x' => 150, 'y' => 260, 'width' => 400, 'height' => 30, 'fontSize' => 16, 'color' => '#333333'],
                        ['name' => 'category', 'label' => 'Category', 'x' => 150, 'y' => 300, 'width' => 300, 'height' => 30, 'fontSize' => 14, 'color' => '#555555'],
                        ['name' => 'expiry_date', 'label' => 'Valid Until', 'x' => 150, 'y' => 340, 'width' => 300, 'height' => 30, 'fontSize' => 14, 'color' => '#555555'],
                        ['name' => 'qr_code', 'label' => 'QR Code', 'x' => 680, 'y' => 400, 'width' => 100, 'height' => 100],
                    ],
                ],
                'is_active' => true,
                'created_by' => $createdBy,
            ]
        );
    }
}
