<?php

return [
    'query_cache' => [
        'enabled' => env('QUERY_CACHE_ENABLED', true),
        'dashboard_ttl' => (int) env('DASHBOARD_CACHE_TTL', 120),
        'bill_stats_ttl' => (int) env('BILL_STATS_CACHE_TTL', 120),
        'app_settings_ttl' => (int) env('APP_SETTINGS_CACHE_TTL', 3600),
    ],
];
