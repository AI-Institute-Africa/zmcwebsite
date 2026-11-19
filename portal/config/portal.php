<?php

return [
    'limits' => [
        'reason_min_length' => (int) env('PORTAL_REASON_MIN_LENGTH', 10),
        'reason_max_length' => (int) env('PORTAL_REASON_MAX_LENGTH', 2000),
        'long_text_max_length' => (int) env('PORTAL_LONG_TEXT_MAX_LENGTH', 2000),
    ],
];
