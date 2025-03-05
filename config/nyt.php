<?php

declare(strict_types=1);

return [
    'api_key' => env('NYT_API_KEY'),
    'base_url' => env('NYT_API_URL'),
    'version' => env('NYT_API_VERSION'),
    'cache_ttl' => env('NYT_CACHE_TTL_MINUTES'),
    'max_requests_per_minute' => env('NYT_API_MAX_REQUESTS_PER_MINUTE'),
];