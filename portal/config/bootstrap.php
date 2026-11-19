<?php

return [
    'super_admin' => [
        'email' => env('SUPER_ADMIN_EMAIL'),
        'name' => env('SUPER_ADMIN_NAME', 'System Administrator'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],
    'system_admin' => [
        'email' => env('SYSTEM_ADMIN_EMAIL'),
        'password' => env('SYSTEM_ADMIN_PASSWORD'),
    ],
    'pr_officer' => [
        'email' => env('PR_OFFICER_EMAIL'),
        'name' => env('PR_OFFICER_NAME'),
        'password' => env('PR_OFFICER_PASSWORD'),
    ],
    'test_user_password' => env('SEED_TEST_USER_PASSWORD'),
];
