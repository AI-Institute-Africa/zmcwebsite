<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paynow' => [
        'enabled' => env('PAYNOW_INTEGRATION', false),

        // USD Configuration
        'usd' => [
            'integration_id' => env('PAYNOW_USD_INTEGRATION_ID', env('PAYNOW_INTEGRATION_ID')),
            'integration_key' => env('PAYNOW_USD_INTEGRATION_KEY', env('PAYNOW_INTEGRATION_KEY')),
        ],

        // ZWG Configuration
        'zwg' => [
            'integration_id' => env('PAYNOW_ZWG_INTEGRATION_ID'),
            'integration_key' => env('PAYNOW_ZWG_INTEGRATION_KEY'),
        ],

        // Common settings
        'return_url' => env('PAYNOW_RETURN_URL', env('APP_URL') . '/paynow/return'),
        'result_url' => env('PAYNOW_RESULT_URL', env('APP_URL') . '/paynow/callback'),
        'default_currency' => env('PAYNOW_DEFAULT_CURRENCY', 'USD'),
        'merchant_email' => env('PAYNOW_MERCHANT_EMAIL', env('MAIL_FROM_ADDRESS')),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'sms_from' => env('TWILIO_SMS_FROM'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'endpoint' => env('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
        'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'endpoint' => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
        'model' => env('OPENAI_VISION_MODEL', 'gpt-4o-mini'),
    ],

    'sendpulse' => [
        'base_url' => env('SENDPULSE_BASE_URL', 'https://api.sendpulse.com'),
    ],

    'media_hub' => [
        'url' => env('TRAINING_HUB_URL', 'https://traininghub.zmc.org.zw'),
        'video_hosts' => array_values(array_filter(array_map('trim', explode(',', env('MEDIA_VIDEO_ALLOWED_HOSTS', 'youtube.com,www.youtube.com,youtu.be,vimeo.com,www.vimeo.com,player.vimeo.com'))))),
    ],

    'qr' => [
        'secret_key' => env('QR_SECRET_KEY', env('APP_KEY')),
    ],

];
