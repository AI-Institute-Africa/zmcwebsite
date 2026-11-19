<?php

return [
    'devices' => [
        'heartbeat_timeout_minutes' => (int) env('DEVICE_HEARTBEAT_TIMEOUT_MINUTES', 3),
    ],
    'vps' => [
        'enabled' => filter_var(env('SYSTEM_MONITOR_VPS_ENABLED', true), FILTER_VALIDATE_BOOL),
        'disk_path' => env('SYSTEM_MONITOR_VPS_DISK_PATH', '/'),
        'cpu_warning_percent' => (int) env('SYSTEM_MONITOR_CPU_WARNING_PERCENT', 85),
        'cpu_critical_percent' => (int) env('SYSTEM_MONITOR_CPU_CRITICAL_PERCENT', 95),
        'memory_warning_percent' => (int) env('SYSTEM_MONITOR_MEMORY_WARNING_PERCENT', 85),
        'memory_critical_percent' => (int) env('SYSTEM_MONITOR_MEMORY_CRITICAL_PERCENT', 95),
        'disk_warning_percent' => (int) env('SYSTEM_MONITOR_DISK_WARNING_PERCENT', 80),
        'disk_critical_percent' => (int) env('SYSTEM_MONITOR_DISK_CRITICAL_PERCENT', 90),
        'services' => [
            'nginx' => ['label' => 'Nginx', 'matches' => ['nginx']],
            'php_fpm' => ['label' => 'PHP-FPM', 'matches' => ['php-fpm']],
            'mysql' => ['label' => 'MySQL', 'matches' => ['mysqld']],
            'mongodb' => ['label' => 'MongoDB', 'matches' => ['mongod']],
            'caddy' => ['label' => 'Caddy', 'matches' => ['caddy']],
            'website_node' => ['label' => 'Website Node.js', 'matches' => ['comm:node']],
            'training_api' => ['label' => 'Training Hub API', 'matches' => ['uvicorn', 'zmc-backend']],
        ],
    ],
];
