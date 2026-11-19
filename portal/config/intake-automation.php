<?php

return [
    /*
     * Enable or disable intake automation system
     */
    'enabled' => env('INTAKE_AUTOMATION_ENABLED', true),

    /*
     * Confidence score thresholds for decision-making
     */
    'confidence_thresholds' => [
        // Score >= auto_pass: Application auto-advanced (very high confidence)
        'auto_pass' => env('INTAKE_AUTOMATION_CONFIDENCE_AUTO_PASS', 90),

        // Score >= manual_review_min: Application flagged for human review
        'manual_review_min' => env('INTAKE_AUTOMATION_CONFIDENCE_MANUAL_REVIEW_MIN', 60),

        // Score < manual_review_min: Application flagged for correction
    ],

    /*
     * Document-specific requirements
     */
    'documents' => [
        // Passport photo minimum dimensions (pixels)
        'passport_photo_min_dimensions' => env('INTAKE_AUTOMATION_PASSPORT_PHOTO_MIN_DIMENSIONS', 600),

        // Clearance letter maximum age (months)
        'clearance_letter_max_age_months' => env('INTAKE_AUTOMATION_CLEARANCE_LETTER_MAX_AGE_MONTHS', 6),

        // Maximum file size for uploads (bytes, 50MB default)
        'max_file_size' => env('INTAKE_AUTOMATION_MAX_FILE_SIZE', 52428800),
    ],

    /*
     * OCR and text extraction configuration
     */
    'ocr' => [
        // OCR provider: 'tesseract', 'google_vision', 'aws_textract', 'azure'
        'provider' => env('INTAKE_AUTOMATION_OCR_PROVIDER', 'tesseract'),

        // Google Cloud Vision API key (if provider is google_vision)
        'google_vision_api_key' => env('GOOGLE_CLOUD_VISION_API_KEY'),

        // AWS Textract configuration (if provider is aws_textract)
        'aws_textract_region' => env('AWS_TEXTRACT_REGION', 'us-east-1'),

        // Azure Document Intelligence (if provider is azure)
        'azure_endpoint' => env('AZURE_DOCUMENT_INTELLIGENCE_ENDPOINT'),
        'azure_key' => env('AZURE_DOCUMENT_INTELLIGENCE_KEY'),
    ],

    /*
     * Facial recognition and photo quality checks
     */
    'face_detection' => [
        // Enable face detection in passport photos
        'enabled' => env('INTAKE_AUTOMATION_ENABLE_FACE_DETECTION', false),

        // Face detection provider: 'google_vision', 'aws_rekognition', 'azure'
        'provider' => env('INTAKE_AUTOMATION_FACE_DETECTION_PROVIDER', 'google_vision'),

        // Enable face comparison between ID/passport and photo
        'enable_face_comparison' => env('INTAKE_AUTOMATION_ENABLE_FACE_COMPARISON', false),

        // Minimum confidence for face match (0-100)
        'face_match_confidence_threshold' => env('INTAKE_AUTOMATION_FACE_MATCH_CONFIDENCE_THRESHOLD', 85),
    ],

    /*
     * Malware scanning configuration
     */
    'security' => [
        // Enable malware scanning (requires ClamAV or similar)
        'enable_malware_scanning' => env('INTAKE_AUTOMATION_ENABLE_MALWARE_SCANNING', false),

        // Malware scanner: 'clamav', 'virustotal', etc.
        'malware_scanner' => env('INTAKE_AUTOMATION_MALWARE_SCANNER', 'clamav'),

        // ClamAV socket path (if using local ClamAV)
        'clamav_socket' => env('INTAKE_AUTOMATION_CLAMAV_SOCKET', 'tcp://localhost:3310'),
    ],

    /*
     * Payment verification settings
     */
    'payment' => [
        // Payment verification mode: 'gateway_only', 'proof_only', 'both'
        'verification_mode' => env('INTAKE_AUTOMATION_PAYMENT_VERIFICATION_MODE', 'both'),

        // Payment gateway webhook source (for verification)
        'gateway_webhook_source' => env('INTAKE_AUTOMATION_PAYMENT_GATEWAY_SOURCE', 'paynow'),
    ],

    /*
     * Application status names (customize based on your workflow)
     */
    'application_statuses' => [
        'intake_auto_passed' => 'Intake Auto-Passed',
        'flagged_for_review' => 'Flagged for Review',
        'correction_requested' => 'Correction Requested',
        'blocked_for_review' => 'Blocked for Review',
        'officer_review' => 'Officer Review',
    ],

    /*
     * Categories that can be auto-assigned
     */
    'categories' => [
        'local_employed_practitioner' => 'Local Employed Practitioner',
        'local_freelancer_practitioner' => 'Local Freelancer Practitioner',
        'local_student_practitioner' => 'Local Student Practitioner',
        'local_aspiring_practitioner' => 'Local Aspiring Practitioner',
        'international_employed_practitioner' => 'International Employed Practitioner',
        'international_freelancer_practitioner' => 'International Freelancer Practitioner',
        'international_temporary_practitioner' => 'International Temporary Practitioner',
    ],
];
