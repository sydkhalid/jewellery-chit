<?php

return [
    'cache' => [
        'dashboard_ttl' => (int) env('DASHBOARD_CACHE_TTL', 300),
        'settings_ttl' => (int) env('SETTINGS_CACHE_TTL', 3600),
        'gold_rate_ttl' => (int) env('GOLD_RATE_CACHE_TTL', 300),
    ],
];
