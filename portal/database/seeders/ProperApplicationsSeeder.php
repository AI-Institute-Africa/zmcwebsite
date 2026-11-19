<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProperApplicationsSeeder extends Seeder
{
    private string $seedPasswordHash;

    public function run(): void
    {
        $seedPassword = (string) config('bootstrap.test_user_password', '');
        if ($seedPassword === '') {
            throw new \RuntimeException('Set SEED_TEST_USER_PASSWORD in a protected environment before running this test-data seeder.');
        }
        $this->seedPasswordHash = Hash::make($seedPassword);

        $this->createLocalAccreditation();
        $this->createForeignAccreditation();
        $this->createLocalMediaHouse();
        $this->createForeignMediaHouse();
    }

    private function createLocalAccreditation(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'local.journalist@example.com'],
            [
                'name' => 'Local Journalist',
                'password' => $this->seedPasswordHash,
                'account_status' => 'active',
                'account_type' => 'public',
            ]
        );

        $ref = 'ZMC-AP3-' . date('Y') . '-PRO-0001';
        $app = Application::updateOrCreate(
            ['reference' => $ref],
            [
                'applicant_user_id' => $user->id,
                'application_type'  => 'accreditation',
                'request_type'      => 'new',
                'journalist_scope'  => 'local',
                'collection_region' => 'harare',
                'status'            => Application::SUBMITTED,
                'is_draft'          => false,
                'submitted_at'      => now(),
                'form_data'         => [
                    'title' => 'Mr',
                    'first_name' => 'Local',
                    'surname' => 'Journalist',
                    'dob' => '1990-01-01',
                    'birth_place' => 'Harare',
                    'gender' => 'male',
                    'marital_status' => 'single',
                    'nationality' => 'Zimbabwean',
                    'address' => '123 Main St, Harare',
                    'phone_country_code' => '+263',
                    'phone' => '771234567',
                    'email' => 'local.journalist@example.com',
                    'employment_type' => 'employed',
                    'medium_type' => 'print',
                    'designation' => 'Reporter',
                    'national_reg_no' => '63-123456A78',
                    'declaration_date' => date('Y-m-d'),
                    'referee_name_1' => 'Ref One', 'referee_address_1' => 'Add 1', 'referee_phone_1' => '0771111111',
                    'referee_name_2' => 'Ref Two', 'referee_address_2' => 'Add 2', 'referee_phone_2' => '0772222222',
                    'referee_name_3' => 'Ref Three', 'referee_address_3' => 'Add 3', 'referee_phone_3' => '0773333333',
                ]
            ]
        );

        $this->attachDoc($app, 'passport_photo', 'mock_photo.jpg');
        $this->attachDoc($app, 'id_scan', 'mock_id.pdf');
        $this->attachDoc($app, 'employment_letter', 'mock_employment.pdf');
        $this->attachPayment($app);
    }

    private function createForeignAccreditation(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'foreign.journalist@example.com'],
            [
                'name' => 'Foreign Journalist',
                'password' => $this->seedPasswordHash,
                'account_status' => 'active',
                'account_type' => 'public',
            ]
        );

        $ref = 'ZMC-AP3-' . date('Y') . '-PRO-0002';
        $app = Application::updateOrCreate(
            ['reference' => $ref],
            [
                'applicant_user_id' => $user->id,
                'application_type'  => 'accreditation',
                'request_type'      => 'new',
                'journalist_scope'  => 'foreign',
                'collection_region' => 'harare',
                'status'            => Application::OFFICER_REVIEW,
                'is_draft'          => false,
                'submitted_at'      => now(),
                'form_data'         => [
                    'title' => 'Ms',
                    'first_name' => 'Foreign',
                    'surname' => 'Journalist',
                    'dob' => '1985-05-15',
                    'birth_place' => 'London',
                    'gender' => 'female',
                    'marital_status' => 'married',
                    'nationality' => 'British',
                    'address' => '456 London Rd, London',
                    'phone_country_code' => '+44',
                    'phone' => '2071234567',
                    'email' => 'foreign.journalist@example.com',
                    'employment_type' => 'employed',
                    'medium_type' => 'broadcast',
                    'designation' => 'Correspondent',
                    'passport_no' => 'UK1234567',
                    'passport_expiry' => '2030-12-31',
                    'passport_issued_at' => 'London',
                    'first_time_in_zim' => 'no',
                    'address_in_zimbabwe' => 'Meikles Hotel, Harare',
                    'arrived_on' => date('Y-m-d'),
                    'arrival_mode' => 'air',
                    'port_of_entry' => 'RG Mugabe Int Airport',
                    'departing_on' => date('Y-m-d', strtotime('+7 days')),
                    'special_assignment' => 'Reporting on elections',
                    'declaration_date' => date('Y-m-d'),
                    'referee_name_1' => 'Ref One', 'referee_address_1' => 'Add 1', 'referee_phone_1' => '0771111111',
                    'referee_name_2' => 'Ref Two', 'referee_address_2' => 'Add 2', 'referee_phone_2' => '0772222222',
                    'referee_name_3' => 'Ref Three', 'referee_address_3' => 'Add 3', 'referee_phone_3' => '0773333333',
                ]
            ]
        );

        $this->attachDoc($app, 'passport_photo', 'mock_photo.jpg');
        $this->attachDoc($app, 'passport_biodata_page', 'mock_id.pdf');
        $this->attachDoc($app, 'clearance_letter', 'mock_clearance.pdf');
        $this->attachPayment($app);
    }

    private function createLocalMediaHouse(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'local.mediahouse@example.com'],
            [
                'name' => 'Local Media House Rep',
                'password' => $this->seedPasswordHash,
                'account_status' => 'active',
                'account_type' => 'public',
            ]
        );

        $ref = 'ZMC-AP1-' . date('Y') . '-PRO-0001';
        $app = Application::updateOrCreate(
            ['reference' => $ref],
            [
                'applicant_user_id' => $user->id,
                'application_type'  => 'registration',
                'request_type'      => 'new',
                'journalist_scope'  => 'local',
                'form_type'         => 'AP1',
                'collection_region' => 'harare',
                'status'            => Application::SUBMITTED_WITH_APP_FEE,
                'is_draft'          => false,
                'submitted_at'      => now(),
                'form_data'         => [
                    'registration_scope' => 'local',
                    'form_type' => 'AP1',
                    'org_name' => 'Local News Network',
                    'org_head_office' => '789 Samora Machel Ave, Harare',
                    'mass_media_activity' => 'Newspaper Publishing',
                    'website' => 'https://localnews.co.zw',
                    'contact_name' => 'Media Manager',
                    'contact_phone' => '+263771111111',
                    'contact_email' => 'local.mediahouse@example.com',
                    'directors' => [
                        [
                            'name' => 'John',
                            'surname' => 'Chamberlain',
                            'gender' => 'male',
                            'shareholding' => '60%',
                        ],
                        [
                            'name' => 'Sarah',
                            'surname' => 'Kombayi',
                            'gender' => 'female',
                            'shareholding' => '40%',
                        ],
                    ],
                ]
            ]
        );

        $this->attachDoc($app, 'company_registration', 'mock_company_reg.pdf');
        $this->attachDoc($app, 'id_scans_of_directors', 'mock_id.pdf');
        $this->attachPayment($app, 'application_fee');

        \App\Models\LevyRecord::updateOrCreate(
            ['user_id' => $user->id],
            [
                'reference' => \App\Models\LevyRecord::generateReference(),
                'entity_name' => 'Local News Network',
                'financial_year' => date('Y'),
                'gross_annual_turnover' => 240000.00,
                'levy_amount' => 1200.00,
                'payment_status' => 'paid',
                'payment_date' => now(),
            ]
        );
    }

    private function createForeignMediaHouse(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'foreign.mediahouse@example.com'],
            [
                'name' => 'Foreign Media House Rep',
                'password' => $this->seedPasswordHash,
                'account_status' => 'active',
                'account_type' => 'public',
            ]
        );

        $ref = 'ZMC-AP4-' . date('Y') . '-PRO-0001';
        $app = Application::updateOrCreate(
            ['reference' => $ref],
            [
                'applicant_user_id' => $user->id,
                'application_type'  => 'registration',
                'request_type'      => 'new',
                'journalist_scope'  => 'foreign',
                'form_type'         => 'AP4',
                'collection_region' => 'harare',
                'status'            => Application::VERIFIED_BY_OFFICER,
                'is_draft'          => false,
                'submitted_at'      => now(),
                'form_data'         => [
                    'registration_scope' => 'foreign',
                    'form_type' => 'AP4',
                    'rep_office_name' => 'Global News Bureau',
                    'rep_office_address' => 'Meikles Hotel Suite 5, Harare',
                    'rep_office_activities' => 'Foreign News Bureau',
                    'website' => 'https://globalnews.com',
                    'contact_name' => 'Bureau Chief',
                    'contact_phone' => '+263772222222',
                    'contact_email' => 'foreign.mediahouse@example.com',
                ]
            ]
        );

        $this->attachDoc($app, 'foreign_registration_certificate', 'mock_company_reg.pdf');
        $this->attachDoc($app, 'clearance_letter', 'mock_clearance.pdf');
        $this->attachPayment($app, 'application_fee');
    }

    private function attachDoc(Application $app, string $type, string $mockFile): void
    {
        $path = 'documents/' . $app->id . '/' . $mockFile;
        Storage::disk('public')->put($path, Storage::disk('public')->get('seed_assets/' . $mockFile));

        ApplicationDocument::updateOrCreate(
            ['application_id' => $app->id, 'doc_type' => $type],
            [
                'file_path' => $path,
                'original_name' => $mockFile,
                'owner_id' => $app->applicant_user_id,
                'mime' => 'application/pdf',
                'size' => 1024,
                'status' => 'uploaded',
                'is_current' => true,
                'version' => 1,
            ]
        );
    }

    private function attachPayment(Application $app, ?string $stage = null): void
    {
        $mockFile = 'mock_payment.pdf';
        $path = 'payment_proofs/' . $mockFile;
        Storage::disk('public')->put($path, Storage::disk('public')->get('seed_assets/' . $mockFile));

        $app->update([
            'payment_proof_path' => $path,
            'payment_proof_uploaded_at' => now(),
            'proof_status' => 'submitted',
            'payment_status' => 'pending',
            'payment_submission_method' => 'proof_upload',
            'proof_amount_paid' => 50.00,
            'proof_payment_date' => date('Y-m-d'),
            'proof_bank_name' => 'Mock Bank',
            'payment_stage' => $stage,
        ]);
    }
}
