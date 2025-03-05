<?php

declare(strict_types=1);

namespace App\Utils\Http;

use App\NewYorkTimes\Service\Exception\RateLimitedException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Spatie\GuzzleRateLimiterMiddleware\Deferrer;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

class RateLimitedGuzzleClientFactory
{
    public function __construct(
        private readonly int $maxAttemptsPerMinute,
        private RateLimiterStore $rateLimiterStore,
    ) {
    }

    public function createClient(array $config = []): ClientInterface
    {
        $stack = HandlerStack::create();

        $rateLimiterMiddleware = RateLimiterMiddleware::perMinute(
            limit: $this->maxAttemptsPerMinute,
            store: $this->rateLimiterStore,
            deferrer: new class implements Deferrer
            {
                public function getCurrentTime(): int
                {
                    return (int) round(microtime(true) * 60 * 1000);
                }

                public function sleep(int $milliseconds)
                {
                    throw new RateLimitedException('Rate limit exceeded');
                }
            }
        );

        $stack->push($rateLimiterMiddleware);

        $defaultConfig = [
            'handler' => $stack,
            'timeout' => (60 / $this->maxAttemptsPerMinute),
        ];

        $mergedConfig = array_merge($defaultConfig, $config);

        return new Client($mergedConfig);
    }
}
