<?php

declare(strict_types=1);

namespace Tests\Feature\NewYorkTimes\Service;

use App\NewYorkTimes\Service\BestsellerResourceService;
use App\NewYorkTimes\Service\Dto\BestsellerBookFiltersDto;
use App\NewYorkTimes\Service\NytHttpClient;
use App\Utils\Http\HttpResult;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BestsellerResourceServiceTest extends TestCase
{
    private BestsellerResourceService $service;
    private MockInterface $mockNytHttpClient;
    private int $cacheTtl = 60;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->mockNytHttpClient = Mockery::mock(NytHttpClient::class);

        $cache = $this->app->make(CacheRepository::class);

        $this->service = new BestsellerResourceService(
            cache: $cache,
            cacheTtl: $this->cacheTtl,
            nytHttpClient: $this->mockNytHttpClient
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetBestsellersUsesCache(): void
    {
        $filters = new BestsellerBookFiltersDto(
            author: 'Stephen King',
            title: 'The Shining'
        );

        $expectedResult = HttpResult::success(data: [['title' => 'The Shining', 'author' => 'Stephen King']]);

        $this->mockNytHttpClient
            ->shouldReceive('getBestsellers')
            ->once()
            ->withArgs(function(BestsellerBookFiltersDto $arg) {
                return $arg->author === 'Stephen King' &&
                    $arg->title === 'The Shining';
            })
            ->andReturn($expectedResult);

        $result1 = $this->service->getBestsellers($filters);
        $result2 = $this->service->getBestsellers($filters);

        $this->assertEquals($expectedResult, $result1);
        $this->assertEquals($expectedResult, $result2);
    }

    public function testDifferentFiltersCausesDifferentCacheKeys(): void
    {
        $filters1 = new BestsellerBookFiltersDto(author: 'Stephen King');
        $filters2 = new BestsellerBookFiltersDto(author: 'J.K. Rowling');

        $result1 = HttpResult::success(data: [['title' => 'IT', 'author' => 'Stephen King']]);

        $result2 = HttpResult::success(
            data: [['title' => 'Harry Potter', 'author' => 'J.K. Rowling']]);

        $this->mockNytHttpClient
            ->shouldReceive('getBestsellers')
            ->once()
            ->withArgs(function(BestsellerBookFiltersDto $arg) {
                return $arg->author === 'Stephen King';
            })
            ->andReturn($result1);

        $this->mockNytHttpClient
            ->shouldReceive('getBestsellers')
            ->once()
            ->withArgs(function(BestsellerBookFiltersDto $arg) {
                return $arg->author === 'J.K. Rowling';
            })
            ->andReturn($result2);

        $response1 = $this->service->getBestsellers($filters1);
        $response2 = $this->service->getBestsellers($filters2);

        $this->assertEquals($result1, $response1);
        $this->assertEquals($result2, $response2);
    }

    public function testCacheKeyGenerationWithComplexFilters(): void
    {
        $complexFilters = new BestsellerBookFiltersDto(
            author: 'George R.R. Martin',
            title: 'A Game of Thrones',
            isbn: ['9780553593716'],
        );

        $simpleFilters = new BestsellerBookFiltersDto(author: 'George R.R. Martin');

        $expected1 = HttpResult::success(data: [['title' => 'A Game of Thrones', 'author' => 'George R.R. Martin']]);

        $expected2 = HttpResult::success(
            data: [
                ['title' => 'A Game of Thrones', 'author' => 'George R.R. Martin'],
                ['title' => 'A Clash of Kings', 'author' => 'George R.R. Martin']
            ],
        );

        $this->mockNytHttpClient
            ->shouldReceive('getBestsellers')
            ->once()
            ->withArgs(function(BestsellerBookFiltersDto $arg) {
                return $arg->author === 'George R.R. Martin' &&
                    $arg->title === 'A Game of Thrones' &&
                    is_array($arg->isbn) &&
                    in_array('9780553593716', $arg->isbn);
            })
            ->andReturn($expected1);

        $this->mockNytHttpClient
            ->shouldReceive('getBestsellers')
            ->once()
            ->withArgs(function(BestsellerBookFiltersDto $arg) {
                return $arg->author === 'George R.R. Martin' &&
                    $arg->title === null;
            })
            ->andReturn($expected2);

        $response1 = $this->service->getBestsellers($complexFilters);
        $response2 = $this->service->getBestsellers($simpleFilters);

        $this->assertEquals($expected1, $response1);
        $this->assertEquals($expected2, $response2);

        $response3 = $this->service->getBestsellers($complexFilters);
        $this->assertEquals($expected1, $response3);
    }

    public function testCacheExpirationTriggersNewRequest(): void
    {
        if (!method_exists(Cache::driver(), 'put')) {
            $this->markTestSkipped('Cache driver does not support TTL testing');
        }

        $filters = new BestsellerBookFiltersDto(author: 'Ernest Hemingway');
        $result1 = HttpResult::success(data: [['title' => 'The Old Man and the Sea', 'rank' => 5]]);
        $result2 = HttpResult::success(data: [['title' => 'The Old Man and the Sea', 'rank' => 3]]);

        $this->mockNytHttpClient
            ->shouldReceive('getBestsellers')
            ->once()
            ->withArgs(function(BestsellerBookFiltersDto $arg) {
                return $arg->author === 'Ernest Hemingway';
            })
            ->andReturn($result1);

        $response1 = $this->service->getBestsellers($filters);
        $this->assertEquals($result1, $response1);

        if (method_exists($this, 'travel')) {
            $this->travel($this->cacheTtl + 10)->seconds();

            $this->mockNytHttpClient
                ->shouldReceive('getBestsellers')
                ->once()
                ->withArgs(function(BestsellerBookFiltersDto $arg) {
                    return $arg->author === 'Ernest Hemingway';
                })
                ->andReturn($result2);

            $response2 = $this->service->getBestsellers($filters);
            $this->assertEquals($result2, $response2);
            $this->assertNotEquals($response1, $response2);
        } else {
            $this->markTestSkipped('Time travel testing not supported in this Laravel version');
        }
    }
}
