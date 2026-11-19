<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModuleHealthStatus;

class ModuleHealthSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['module_name' => 'applicant_portal', 'display_name' => 'Applicant Portal', 'category' => 'portal'],
            ['module_name' => 'staff_dashboard', 'display_name' => 'Staff Dashboard', 'category' => 'portal'],
            ['module_name' => 'authentication', 'display_name' => 'Authentication Service', 'category' => 'core'],
            ['module_name' => 'file_upload', 'display_name' => 'File Upload Service', 'category' => 'core'],
            ['module_name' => 'reporting', 'display_name' => 'Reporting Service', 'category' => 'core'],
            ['module_name' => 'print_service', 'display_name' => 'Print Service', 'category' => 'production'],
            ['module_name' => 'card_generation', 'display_name' => 'Card Generation', 'category' => 'production'],
            ['module_name' => 'certificate_generation', 'display_name' => 'Certificate Generation', 'category' => 'production'],
            ['module_name' => 'proof_of_payment', 'display_name' => 'Proof of Payment Module', 'category' => 'payment'],
            ['module_name' => 'receipt_generation', 'display_name' => 'Receipt Generation', 'category' => 'payment'],
            ['module_name' => 'paynow_gateway', 'display_name' => 'Paynow Gateway', 'category' => 'payment'],
            ['module_name' => 'payment_initiation', 'display_name' => 'Payment Initiation', 'category' => 'payment'],
            ['module_name' => 'payment_callback', 'display_name' => 'Payment Callback/Webhook', 'category' => 'payment'],
            ['module_name' => 'payment_verification', 'display_name' => 'Payment Verification', 'category' => 'payment'],
            ['module_name' => 'payment_sync', 'display_name' => 'Payment Sync/Reconciliation', 'category' => 'payment'],
            ['module_name' => 'qr_generation', 'display_name' => 'QR Code Generation', 'category' => 'production'],
            ['module_name' => 'notification', 'display_name' => 'Notification Service', 'category' => 'core'],
            ['module_name' => 'search_filter', 'display_name' => 'Search & Filter', 'category' => 'core'],
            ['module_name' => 'draft_save', 'display_name' => 'Draft Save Module', 'category' => 'core'],
            ['module_name' => 'database', 'display_name' => 'Database', 'category' => 'infrastructure'],
        ];

        foreach ($modules as $module) {
            ModuleHealthStatus::updateOrCreate(
                ['module_name' => $module['module_name']],
                array_merge($module, [
                    'status' => 'healthy',
                    'last_checked_at' => now(),
                    'status_changed_at' => now(),
                ])
            );
        }
    }
}
