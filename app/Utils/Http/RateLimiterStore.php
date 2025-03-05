<?php

declare(strict_types=1);

namespace App\Utils\Http;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Spatie\GuzzleRateLimiterMiddleware\Store;

class RateLimiterStore implements Store
{
    public function __construct(private readonly CacheRepository $cache)
    {

    }
    public function get(): array
    {
        return $this->cache->get('api-rate-limiter', []);
    }

    public function push(int $timestamp, int $limit): void
    {
        $this->cache->put('api-rate-limiter', array_merge($this->get(), [$timestamp]));
    }
}