<?php

namespace App\Providers;

use App\NewYorkTimes\Service\BestsellerResourceService;
use App\NewYorkTimes\Service\Dto\NytHttpClientInformationDto;
use App\NewYorkTimes\Service\NytHttpClient;
use App\Utils\Http\RateLimitedGuzzleClientFactory;
use App\Utils\Http\RateLimiterStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RateLimiterStore::class, function ($app) {
            return new RateLimiterStore(cache: $app->make(CacheRepository::class));
        });

        $this->app->singleton(RateLimitedGuzzleClientFactory::class, function ($app) {
            return new RateLimitedGuzzleClientFactory(
                maxAttemptsPerMinute: config('nyt.max_requests_per_minute'),
                rateLimiterStore: $app->make(RateLimiterStore::class),
            );
        });

        $this->app->singleton(NytHttpClientInformationDto::class, function ($app) {
            return new NytHttpClientInformationDto(
                nytBaseUrl: config('nyt.base_url'),
                nytVersion: config('nyt.version'),
                nytApiKey: config('nyt.api_key'),
            );
        });

        $this->app->singleton(BestsellerResourceService::class, function ($app) {
            return new BestsellerResourceService(
                cache: $app->make(CacheRepository::class),
                cacheTtl: config('nyt.cache_ttl'),
                nytHttpClient: $app->make(NytHttpClient::class),
                rateLimiter: $app->make(RateLimiter::class),
                maxAttemptsPerMinute: config('nyt.max_requests_per_minute'),
            );
        });

        $this->app->singleton(NytHttpClient::class, function ($app) {
            $informationDto = $app->make(NytHttpClientInformationDto::class);
            $clientFactory = $app->make(RateLimitedGuzzleClientFactory::class);
            $guzzleClient = $clientFactory->createClient(config: [
                'base_uri' => $informationDto->nytBaseUrl,
            ]);

            return new NytHttpClient(
                informationDto: $app->make(NytHttpClientInformationDto::class),
                httpClient: $guzzleClient,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('nyt.max_requests_per_minute'))->by($this->getNytApiKey($request))->response(function (Request $request, array $headers) {
                    return response(
                        'Too Many Attempts. Please wait 12 seconds.',
                        Response::HTTP_TOO_MANY_REQUESTS,
                        $headers
                    );
                });
        });
    }

    private function getNytApiKey(Request $request): string
    {
        $apiKey = $request->query('clientApiKey');

        if (empty($apiKey)) {
            $apiKey = config('nyt.api_key');
        }

        return $apiKey;
    }
}
