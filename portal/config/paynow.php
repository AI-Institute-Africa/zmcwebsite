<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayNow Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your PayNow integration details here.
    | Get these from: https://developers.paynow.co.zw/docs/paynow/integration_generation/
    |
    */

    'enabled' => env('PAYNOW_INTEGRATION', false),
    'default_currency' => env('PAYNOW_DEFAULT_CURRENCY', 'USD'),
    'merchant_email' => env('PAYNOW_MERCHANT_EMAIL', env('MAIL_FROM_ADDRESS')),

    'currencies' => [
        'USD' => [
            'integration_id' => env('PAYNOW_USD_INTEGRATION_ID', env('PAYNOW_INTEGRATION_ID', '')),
            'integration_key' => env('PAYNOW_USD_INTEGRATION_KEY', env('PAYNOW_INTEGRATION_KEY', '')),
        ],
        'ZWG' => [
            'integration_id' => env('PAYNOW_ZWG_INTEGRATION_ID', ''),
            'integration_key' => env('PAYNOW_ZWG_INTEGRATION_KEY', ''),
        ],
    ],

    'integration_id' => env('PAYNOW_USD_INTEGRATION_ID', env('PAYNOW_INTEGRATION_ID', '')),
    'integration_key' => env('PAYNOW_USD_INTEGRATION_KEY', env('PAYNOW_INTEGRATION_KEY', '')),
    
    // URLs for payment callback
    'return_url' => env('PAYNOW_RETURN_URL', env('APP_URL') . '/paynow/return'),
    'result_url' => env('PAYNOW_RESULT_URL', env('APP_URL') . '/paynow/callback'),
    
    // Default payment method (paynow for web redirect, mobile for express checkout)
    'default_method' => env('PAYNOW_METHOD', 'paynow'),
    
    // Mobile payment options (ecocash, onemoney)
    'mobile_methods' => ['ecocash', 'onemoney'],
];
