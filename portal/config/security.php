<?php

return [
    'global_rate_limit' => [
        'enabled' => (bool) env('PORTAL_GLOBAL_RATE_LIMIT_ENABLED', true),
        'requests_per_minute' => max(1, (int) env('PORTAL_GLOBAL_RATE_LIMIT_PER_MINUTE', 300)),
    ],

    'ip_blocking' => [
        'enabled' => (bool) env('PORTAL_IP_BLOCKING_ENABLED', true),
        'cache_seconds' => max(1, (int) env('PORTAL_IP_BLOCK_CACHE_SECONDS', 30)),
        'excluded_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PORTAL_IP_BLOCK_EXCLUDED_IPS', '127.0.0.1,::1'))
        ))),
    ],

    'tls_audit' => [
        'hosts' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'PORTAL_TLS_AUDIT_HOSTS',
                'zmc.org.zw,www.zmc.org.zw,portal.zmc.org.zw,training.zmc.org.zw,traininghub.zmc.org.zw'
            ))
        ))),
        'timeout_seconds' => max(2, (int) env('PORTAL_TLS_AUDIT_TIMEOUT', 4)),
    ],
];
