<?php

return [
    // Controlled testing override; production defaults to regional scoping.
    'region_scope_enabled' => env('ZMC_REGION_SCOPE_ENABLED', true),

    /*
     |--------------------------------------------------------------------------
     | Master Settings (All Portals & Dashboards)
     |--------------------------------------------------------------------------
     | Stored in system_configs key: master_settings.
     | These defaults are merged with saved values at runtime.
     */
    'master_settings' => [
        'general' => [
            'system_name' => 'Zimbabwe Media Commission Portal',
            'commission_short_name' => 'ZMC',
            'logo_light_path' => null,
            'logo_dark_path' => null,
            'favicon_path' => null,
            'footer_text' => '© ' . date('Y') . ' Zimbabwe Media Commission',
            'default_language' => 'en',
            'time_zone' => 'Africa/Harare',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'currency_symbol' => '$',
            'currency_iso' => 'USD',
            'financial_year_start' => '01-01',
            'financial_year_end' => '12-31',
            'maintenance_mode' => false,
            'public_portal_enabled' => true,
            'staff_portals_enabled' => true,
        ],

        'accounts_roles' => [
            'force_password_reset_first_login' => true,
            'password_policy' => [
                'min_length' => 8,
                'complexity' => true,
                'expiry_days' => 0,
                'history' => 3,
            ],
            'session_timeout_minutes' => 60,
            'max_login_attempts' => 5,
            'account_lockout_minutes' => 15,
            'delegated_authority_limits' => [
                'waiver_auto_approve_threshold' => 0,
            ],
        ],

        'profile_self_service' => [
            'editable_fields' => [
                'name' => true,
                'phone' => true,
                'address' => true,
            ],
            'change_username' => 'allowed', // allowed|approval_required|disabled
            'change_email_requires_verification' => true,
            'active_sessions_visible' => true,
            'logout_all_devices' => true,
            'public_account_deactivation_request' => true,
        ],

        'auth_security' => [
            'email_verification_on_signup' => 'mandatory', // mandatory|optional|disabled
            'otp_on_login' => false,
            'otp_methods' => ['email'], // email,sms
            'otp_length' => 6,
            'otp_expiry_minutes' => 10,
            'otp_resend_limit' => 3,
            'two_factor' => [
                'enabled_globally' => false,
                'enforce_by_role' => [],
                'allowed_methods' => ['email'],
                'trusted_devices_days' => 30,
            ],
            'ip_logging' => true,
            'ip_whitelist' => [],
            'new_device_alerts' => false,
        ],

        'portal_specific' => [
            'public' => [
                'registration_window' => ['open' => null, 'close' => null],
                'accreditation_window' => ['open' => null, 'close' => null],
                'public_notices_visible' => true,
                'captcha_enabled' => false,
                'allowed_upload_formats' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_upload_size_mb' => 10,
            ],
            'staff' => [
                'default_dashboard_by_role' => [],
                'staff_announcements_visible' => true,
                'feature_toggles_by_role' => [],
            ],
        ],

        'accreditation_registration' => [
            'accreditation_categories' => ['Local Journalist', 'Foreign Journalist', 'Student Journalist'],
            'registration_categories' => ['Mass Media Service'],
            'validity_period_months' => [
                'accreditation' => 12,
                'registration' => 12,
            ],
            'renewal_rules' => [
                'grace_period_days' => 30,
                'reaccreditation_conditions' => '',
            ],
            'required_documents' => [
                'default' => ['National ID/Passport', 'Passport Photo'],
            ],
            'document_expiry_rules' => [
                'enforce' => false,
            ],
            'status_progression_rules' => [
                'use_strict_transitions' => true,
            ],
        ],

        'payments_finance' => [
            'rounding' => 'none', // none|nearest_cent|nearest_dollar
            'paynow_enabled' => true,
            'paynow_callback_url' => null,
            'paynow_timeout_minutes' => 30,
            'payment_proof' => [
                'required' => true,
                'formats' => ['pdf', 'jpg', 'jpeg', 'png'],
                'max_size_mb' => 10,
            ],
            'waivers' => [
                'enabled' => true,
                'types' => ['full', 'partial'],
                'partial_max_percent' => 100,
                'eligible_categories' => [],
                'approval_authority_levels' => ['registrar', 'director'],
                'auto_approval_threshold' => 0,
                'mask_settlement_account' => true,
            ],
        ],

        'payment_proofs' => [
            'submission_enabled' => true,
            'formats' => ['pdf', 'jpg', 'jpeg', 'png'],
            'max_size_mb' => 10,
            'review_sla_hours' => 48,
            'rejection_reason_templates' => [
                'Unclear proof',
                'Wrong amount',
                'Reference mismatch',
            ],
            'resubmission_allowed' => true,
            'auto_link_to_paynow' => true,
        ],

        'waivers' => [
            'eligibility_criteria' => '',
            'supporting_documents_required' => true,
            'partial_percentage_limit' => 100,
            'approval_levels' => ['registrar', 'director'],
            'audit_flagging_rules' => '',
            'expiry_days' => 0,
            'appeal_enabled' => true,
        ],

        'workflow_approvals' => [
            'multi_step' => false,
            'escalation_timelines_hours' => [],
            'auto_status_transitions' => true,
            'override_permissions_roles' => ['super_admin'],
            'sla_timers_by_role' => [],
        ],

        'applications_status' => [
            'definitions' => [],
            'transition_rules' => [],
            'auto_reminders' => false,
            'expiry_days' => 0,
        ],

        'notifications_alerts' => [
            'email_enabled' => true,
            'sms_enabled' => false,
            'in_app_enabled' => true,
            'sender_name' => 'ZMC',
            'sender_email' => 'no-reply@example.com',
            'reminder_schedules' => [],
        ],

        'content_management' => [
            'news_categories' => ['General'],
            'notice_categories' => ['General'],
            'event_categories' => ['General'],
            'approval_workflow' => 'none', // none|single|multi
            'publish_scheduling' => true,
            'featured_rules' => '',
            'media_upload_max_mb' => 10,
            'archive_days' => 0,
        ],

        'website_content' => [
            'media_hub_url' => env('TRAINING_HUB_URL', 'https://traininghub.zmc.org.zw'),
        ],

        'reports_analytics' => [
            'access_by_role' => [],
            'default_period_days' => 30,
            'export_formats' => ['csv', 'excel'],
            'data_retention_days' => 0,
            'kpi_config' => [],
            'dashboard_widgets' => [],
        ],

        'audit_compliance' => [
            'audit_logging_enabled' => true,
            'scope' => 'all',
            'retention_days' => 365,
            'user_action_tracking' => true,
            'ip_tracking' => true,
            'evidence_attachment_rules' => '',
            'compliance_tagging' => ['payments', 'waivers', 'approvals'],
        ],

        'documents_files' => [
            'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
            'max_size_mb' => 10,
            'virus_scanning' => false,
            'storage' => 'local',
            'secure_downloads' => true,
            'auto_expiry_days' => 0,
        ],

        'integrations' => [
            'paynow' => ['enabled' => true, 'credentials_set' => false],
            'sms' => ['provider' => '', 'credentials_set' => false],
            'email' => ['driver' => 'smtp', 'credentials_set' => false],
            'api_keys' => [],
            'webhook_retry' => ['attempts' => 3],
        ],

        'monitoring_maintenance' => [
            'system_version' => 'v2.8.3.2',
            'modules' => [
                'notices' => true,
                'news' => true,
                'events' => true,
                'complaints' => true,
                'payments' => true,
            ],
            'feature_flags' => [],
            'jobs_monitoring' => true,
            'cache_control' => true,
            'error_monitoring' => true,
            'backup_frequency' => 'daily',
            'disaster_recovery_mode' => false,
        ],

        'help_support_legal' => [
            'helpdesk_contacts' => '',
            'support_ticket_categories' => ['General'],
            'manuals_guides' => '',
            'faqs' => '',
            'privacy_policy' => '',
            'terms_conditions' => '',
            'legal_disclaimers' => '',
            'cookie_policy' => '',
        ],

        'menu_ui' => [
            'sidebar_by_role' => [],
            'menu_order' => [],
            'hidden_modules' => [],
            'theme' => 'light',
            'accessibility' => [
                'high_contrast' => false,
                'font_size' => 'normal',
            ],
        ],
    ],
    'workflow' => [
        // SLA in hours per key stage (editable in Super Admin console)
        'sla_hours' => [
            'submitted' => 48,
            'officer_review' => 72,
            'accounts_review' => 48,
            'registrar_review' => 72,
            'production_queue' => 72,
        ],

        // Escalations (editable) - kept simple as role names
        'escalations' => [
            'submitted' => ['after_hours' => 72, 'notify_roles' => 'it_admin,super_admin'],
            'officer_review' => ['after_hours' => 96, 'notify_roles' => 'registrar,super_admin'],
            'accounts_review' => ['after_hours' => 72, 'notify_roles' => 'super_admin'],
            'registrar_review' => ['after_hours' => 96, 'notify_roles' => 'super_admin,director'],
            'production_queue' => ['after_hours' => 96, 'notify_roles' => 'super_admin'],
        ],
    ],

    'fees' => [
        'fees' => [
            ['name' => 'Accreditation – Local Journalist', 'amount' => 0, 'currency' => 'USD', 'active' => true],
            ['name' => 'Accreditation – Foreign Journalist', 'amount' => 0, 'currency' => 'USD', 'active' => true],
            ['name' => 'Registration – Mass Media Service', 'amount' => 0, 'currency' => 'USD', 'active' => true],
            ['name' => 'Replacement – Card', 'amount' => 0, 'currency' => 'USD', 'active' => true],
            ['name' => 'Replacement – Certificate', 'amount' => 0, 'currency' => 'USD', 'active' => true],
        ],
        'payment_channels' => [
            ['name' => 'PayNow', 'active' => true],
            ['name' => 'Bank Transfer', 'active' => true],
            ['name' => 'Cash (Office)', 'active' => false],
        ],
        'waiver_rules' => '',
        'tax' => [
            'vat_percent' => 0,
        ],
    ],

    'templates' => [
        'items' => [
            'card' => ['label' => 'Accreditation Card Template', 'active_version' => null, 'versions' => []],
            'certificate' => ['label' => 'Certificate Template', 'active_version' => null, 'versions' => []],
            'letters' => ['label' => 'Letters Pack', 'active_version' => null, 'versions' => []],
            'email' => ['label' => 'Email Templates', 'active_version' => null, 'versions' => []],
            'sms' => ['label' => 'SMS Templates', 'active_version' => null, 'versions' => []],
        ],
    ],

    'content_control' => [
        'modules' => [
            'notices' => true,
            'news' => true,
            'events' => true,
        ],
        'categories' => ['General'],
        'moderation_rules' => '',
    ],

    'regions_offices' => [
        'offices' => [
            ['name' => 'Head Office (Harare)', 'code' => 'HQ', 'region' => 'Harare', 'schedule' => '', 'assigned_user_ids' => []],
            ['name' => 'Bulawayo Regional Office', 'code' => 'BYO', 'region' => 'Bulawayo', 'schedule' => '', 'assigned_user_ids' => []],
        ],
    ],

    'system_settings' => [
        'branding' => [
            'logo_path' => null,
            'seal_path' => null,
        ],
        'security' => [
            'password_min' => 8,
            'mfa_enabled' => false,
        ],
        'backups' => [
            'schedule' => 'daily',
        ],
        'integrations' => [
            'paynow' => ['enabled' => true],
            'email' => ['driver' => 'smtp'],
            'sms' => ['provider' => ''],
        ],
    ],
];
