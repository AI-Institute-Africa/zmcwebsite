<?php

namespace Database\Seeders;

use App\Models\Notice;
use App\Models\Tender;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Optional, unpublished review samples. Never runs from DatabaseSeeder and is
 * blocked in production so fictional content cannot appear on the live site.
 */
class WebsiteReviewDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Website review samples cannot be seeded in production.');
        }

        Notice::updateOrCreate(
            ['title' => 'DEMO ONLY — Website review sample notice'],
            [
                'notice_category' => 'General',
                'reference_number' => 'DEMO-NOTICE-REVIEW',
                'summary' => 'Fictional draft content for reviewing the notice management workflow.',
                'body' => 'DEMO ONLY. Replace or delete this fictional sample before publishing. It is not an official Zimbabwe Media Commission announcement.',
                'is_published' => false,
                'published_at' => null,
            ]
        );

        Tender::updateOrCreate(
            ['tender_reference' => 'DEMO-TENDER-REVIEW'],
            [
                'title' => 'DEMO ONLY — Website review sample tender',
                'issuing_organisation' => 'DEMO ONLY — fictional issuer',
                'summary' => 'Fictional draft content for reviewing tender fields and the e-GP destination field.',
                'description' => 'DEMO ONLY. This is not a real procurement opportunity. Replace or delete this record before publication.',
                'bidding_documents_link' => null,
                'tender_status' => 'Draft',
                'is_published' => false,
                'published_at' => null,
            ]
        );
    }
}
