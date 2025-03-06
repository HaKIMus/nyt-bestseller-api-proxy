<?php

declare(strict_types=1);

namespace App\NewYorkTimes\Service;

use App\NewYorkTimes\Service\Dto\BestsellerBookFiltersDto;
use App\Utils\Http\HttpResult;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class BestsellerResourceService
{
    private const CACHE_KEY = 'nyt_bestsellers_';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly int $cacheTtl,
        private readonly NytHttpClient $nytHttpClient,
    ) {
    }

    public function getBestsellers(BestsellerBookFiltersDto $filters): HttpResult
    {
        $cacheKey = self::CACHE_KEY . md5(serialize($filters));

        $this->cache->remember($cacheKey, $this->cacheTtl, function () use ($filters) {
            return $this->nytHttpClient->getBestsellers($filters);
        });

        /** @var HttpResult $cacheResult */
        $cacheResult = $this->cache->get($cacheKey); // I'm aware I trust a little bit too much here that it's going to return an HttpResult

        if ($cacheResult->success !== true) {
            $this->cache->forget($cacheKey);
        }

        return $cacheResult;
    }
}