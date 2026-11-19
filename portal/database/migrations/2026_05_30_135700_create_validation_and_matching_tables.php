<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Document extraction data - OCR extracted values from documents
        Schema::create('document_extraction_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('application_documents')->cascadeOnDelete();
            
            // Document classification
            $table->string('document_type'); // national_id, passport, certificate, employment_letter, etc.
            
            // Extracted fields (normalized)
            $table->string('extracted_first_name')->nullable();
            $table->string('extracted_surname')->nullable();
            $table->string('extracted_other_names')->nullable();
            $table->string('extracted_full_name')->nullable();
            
            // For documents with ID/passport numbers
            $table->string('extracted_id_number')->nullable();
            $table->string('extracted_passport_number')->nullable();
            $table->date('extracted_date_of_birth')->nullable();
            $table->string('extracted_nationality')->nullable();
            $table->date('extracted_document_expiry')->nullable();
            
            // Organization-related
            $table->string('extracted_organization_name')->nullable();
            $table->string('extracted_position')->nullable();
            $table->date('extracted_letter_date')->nullable();
            
            // OCR metrics
            $table->unsignedTinyInteger('ocr_confidence')->default(0); // 0-100 confidence score
            $table->json('ocr_warnings')->nullable(); // Quality issues, unclear text
            
            // Tampering detection
            $table->json('tampering_flags')->nullable(); // Unusual fonts, alterations, etc.
            $table->boolean('appears_altered')->default(false);
            $table->boolean('unreadable')->default(false);
            
            // Full extracted text (for manual review)
            $table->longText('full_extracted_text')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->index(['application_id', 'document_type']);
            $table->index(['extracted_id_number']);
            $table->index(['extracted_passport_number']);
            $table->index('ocr_confidence');
        });

        // Name matching results
        Schema::create('name_matching_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Source: application form
            $table->string('entered_first_name')->nullable();
            $table->string('entered_surname')->nullable();
            $table->string('entered_other_names')->nullable();
            $table->string('entered_full_name')->nullable();
            
            // Source: primary ID document (usually National ID)
            $table->string('primary_id_first_name')->nullable();
            $table->string('primary_id_surname')->nullable();
            $table->string('primary_id_other_names')->nullable();
            $table->string('primary_id_full_name')->nullable();
            
            // Source: certificate (if applicable)
            $table->string('certificate_first_name')->nullable();
            $table->string('certificate_surname')->nullable();
            $table->string('certificate_full_name')->nullable();
            
            // Matching results
            $table->enum('entry_vs_primary_id_match', ['match', 'mismatch', 'partial', 'unverified']);
            $table->unsignedTinyInteger('entry_vs_primary_id_score')->default(0); // 0-100
            $table->text('entry_vs_primary_id_reason')->nullable();
            
            $table->enum('entry_vs_certificate_match', ['match', 'mismatch', 'partial', 'unverified', 'no_certificate']);
            $table->unsignedTinyInteger('entry_vs_certificate_score')->default(0);
            $table->text('entry_vs_certificate_reason')->nullable();
            
            // Overall decision
            $table->enum('validation_status', ['pass', 'soft_warning', 'manual_review', 'hard_stop']);
            $table->text('validation_message')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('validation_status');
        });

        // ID validation results
        Schema::create('id_validation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            
            // Zimbabwe National ID
            $table->string('entered_id_number')->nullable();
            $table->string('uploaded_id_number')->nullable();
            $table->enum('id_number_match', ['match', 'mismatch', 'unverified']);
            
            // Passport
            $table->string('entered_passport_number')->nullable();
            $table->string('uploaded_passport_number')->nullable();
            $table->enum('passport_number_match', ['match', 'mismatch', 'unverified']);
            
            // Passport expiry
            $table->date('uploaded_passport_expiry')->nullable();
            $table->boolean('passport_expired')->default(false);
            $table->text('passport_expiry_message')->nullable();
            
            // Accreditation validity vs document validity
            $table->date('accreditation_start_date')->nullable();
            $table->date('accreditation_end_date')->nullable();
            $table->boolean('accreditation_exceeds_passport_validity')->default(false);
            
            // Document quality checks
            $table->boolean('id_appears_altered')->default(false);
            $table->boolean('passport_appears_altered')->default(false);
            $table->boolean('id_unreadable')->default(false);
            $table->boolean('passport_unreadable')->default(false);
            
            // Duplicate ID checks
            $table->boolean('id_linked_to_another_applicant')->default(false);
            $table->text('duplicate_id_details')->nullable();
            
            // Overall validation
            $table->enum('validation_status', ['pass', 'soft_warning', 'manual_review', 'hard_stop']);
            $table->text('validation_message')->nullable();
            
            $table->timestamps();
            $table->index('application_id');
            $table->index('entered_id_number');
            $table->index('entered_passport_number');
            $table->index('validation_status');
        });

        // Document hash tracking - detect duplicates
        Schema::create('document_hash_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('application_documents')->cascadeOnDelete();
            
            // Hashing
            $table->string('file_hash')->unique(); // SHA-256 or similar
            $table->string('perceptual_hash')->nullable(); // For image similarity
            $table->string('ocr_hash')->nullable(); // Hash of extracted text
            
            // File properties
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('file_name');
            
            // Document classification
            $table->string('document_type');
            
            // Usage tracking
            $table->unsignedInteger('times_uploaded')->default(1);
            $table->json('upload_history')->nullable(); // { application_id, uploaded_at, by_user }
            
            // Duplicate detection
            $table->boolean('is_duplicate')->default(false);
            $table->unsignedBigInteger('duplicate_of_document_id')->nullable();
            $table->json('other_uses')->nullable(); // Array of { application_id, applicant_name, status }
            
            $table->timestamps();
            $table->index('file_hash');
            $table->index('perceptual_hash');
            $table->index('is_duplicate');
            $table->index('created_at');
        });

        // Document tampering flags
        Schema::create('document_tampering_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('application_documents')->cascadeOnDelete();
            
            // Tampering indicators
            $table->json('detected_flags'); // Array of flags detected
            
            // Specific checks
            $table->boolean('unusual_fonts_detected')->default(false);
            $table->boolean('inconsistent_alignment')->default(false);
            $table->boolean('edited_images_detected')->default(false);
            $table->boolean('missing_seal')->default(false);
            $table->boolean('missing_signature')->default(false);
            $table->boolean('missing_stamp')->default(false);
            $table->boolean('blurred_id')->default(false);
            $table->boolean('cropped_document')->default(false);
            $table->boolean('name_area_altered')->default(false);
            $table->boolean('number_area_altered')->default(false);
            $table->boolean('text_overlaid')->default(false);
            $table->boolean('background_inconsistent')->default(false);
            
            // OCR confidence indicators
            $table->unsignedTinyInteger('ocr_confidence')->default(0);
            $table->boolean('low_ocr_confidence')->default(false);
            
            // Recommendation
            $table->enum('recommendation', ['pass', 'manual_review', 'reject']);
            $table->text('reason')->nullable();
            
            // Officer review
            $table->string('reviewed_by')->nullable(); // User ID
            $table->text('officer_notes')->nullable();
            $table->string('officer_decision')->nullable(); // approve, reject, request_new
            
            $table->timestamps();
            $table->index('document_id');
            $table->index('recommendation');
            $table->index('low_ocr_confidence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_extraction_data');
        Schema::dropIfExists('name_matching_results');
        Schema::dropIfExists('id_validation_results');
        Schema::dropIfExists('document_hash_tracking');
        Schema::dropIfExists('document_tampering_flags');
    }
};
