<?php

return [
    'timezone' => [
        'local_envs' => explode(',', (string) env('LOCAL_ENVS', 'local,development,dev')),
        'ttl' => (int) env('TIMEZONE_CACHE_TTL', 86400),
        'retry_after' => (int) env('TIMEZONE_RETRY_AFTER', 300),
    ],
];
