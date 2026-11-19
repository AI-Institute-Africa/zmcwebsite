<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZMC Fee Structure
    |--------------------------------------------------------------------------
    |
    | Define all fees charged by Zimbabwe Media Commission
    |
    */

    'accreditation' => [
        'new' => (float) env('FEE_ACCREDITATION_NEW', 20.00),
        'renewal' => (float) env('FEE_ACCREDITATION_RENEWAL', 20.00),
        'foreign_new' => (float) env('FEE_ACCREDITATION_FOREIGN_NEW', 20.00),
        'foreign_renewal' => (float) env('FEE_ACCREDITATION_FOREIGN_RENEWAL', 20.00),
    ],

    'media_house' => [
        'application_fee' => (float) env('FEE_MEDIA_HOUSE_APPLICATION', 30.00),
        'levy_rate' => (float) env('FEE_ANNUAL_LEVY_RATE', 0.005), // decimal fraction (0.005 = 0.5%)
        
        // Registration fees (paid after approval, varies by media house type)
        'registration_fee' => [
            'MC' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_MC', 100.00),
            'MA' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_MA', 150.00),
            'MV' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_MV', 150.00),
            'MN' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_MN', 200.00),
            'DG' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_DG', 150.00),
            'MP' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_MP', 150.00),
            'MS' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_MS', 200.00),
            'MF' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_MF', 200.00),
            'default' => (float) env('FEE_MEDIA_HOUSE_REGISTRATION_DEFAULT', 150.00),
        ],
        
        // Annual levy (separate from registration)
        'annual_levy' => [
            'MC' => (float) env('FEE_ANNUAL_LEVY_MC', 50.00),
            'MA' => (float) env('FEE_ANNUAL_LEVY_MA', 75.00),
            'MV' => (float) env('FEE_ANNUAL_LEVY_MV', 75.00),
            'MN' => (float) env('FEE_ANNUAL_LEVY_MN', 100.00),
            'DG' => (float) env('FEE_ANNUAL_LEVY_DG', 75.00),
            'MP' => (float) env('FEE_ANNUAL_LEVY_MP', 75.00),
            'MS' => (float) env('FEE_ANNUAL_LEVY_MS', 100.00),
            'MF' => (float) env('FEE_ANNUAL_LEVY_MF', 100.00),
            'default' => (float) env('FEE_ANNUAL_LEVY_DEFAULT', 75.00),
        ],
        
        // Renewal fees
        'renewal_fee' => (float) env('FEE_MEDIA_HOUSE_RENEWAL', 100.00),
        'replacement_fee' => (float) env('FEE_MEDIA_HOUSE_REPLACEMENT', 50.00),
    ],

    'foia' => [
        'appeal_fee' => (float) env('FEE_FOIA_APPEAL', 10.00),
    ],
];
