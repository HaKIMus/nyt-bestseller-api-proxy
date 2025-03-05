<?php

declare(strict_types=1);

namespace Tests\Feature\NewYorkTimes\UserInterface\Api\V1;

use App\NewYorkTimes\Service\BestsellerResourceService;
use App\Utils\Http\HttpResult;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Symfony\Component\HttpFoundation\Response as HttpStatusCode;
use Tests\TestCase;

class NytControllerTest extends TestCase
{
    private BestsellerResourceService $mockedService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockedService = Mockery::mock(BestsellerResourceService::class);
        $this->app->instance(BestsellerResourceService::class, $this->mockedService);

        Cache::flush();
    }

    public function testGetBestSellersSuccess(): void
    {
        $mockResponseData = [
            [
                'title' => 'Test Book 1',
                'author' => 'Test Author 1',
                'publisher' => 'Test Publisher',
                'rank' => 1,
                'bestsellers_date' => '2023-01-01',
            ],
            [
                'title' => 'Test Book 2',
                'author' => 'Test Author 2',
                'publisher' => 'Test Publisher',
                'rank' => 2,
                'bestsellers_date' => '2023-01-01',
            ]
        ];

        $this->mockedService
            ->shouldReceive('getBestsellers')
            ->once()
            ->andReturn(
                HttpResult::success(
                    data: collect($mockResponseData),
                    statusCode: HttpStatusCode::HTTP_OK,
                    meta: [
                        'headers' => [],
                        'url' => [],
                        'serviceStatusCode' => 200,
                    ]
                )
            );

        $response = $this->getJson('/api/v1/bestsellers');

        $response->assertStatus(200)
            ->assertJsonPath('0.title', 'Test Book 1')
            ->assertJsonPath('1.title', 'Test Book 2')
            ->assertJsonStructure([
                '*' => [
                    'title',
                    'author',
                    'publisher',
                    'rank',
                    'bestsellers_date'
                ],
            ]);
    }

    public function testGetBestSellersWithFilters(): void
    {
        $mockResponseData = [
            [
                'title' => 'Filtered Book',
                'author' => 'Filtered Author',
                'publisher' => 'Test Publisher',
                'rank' => 1,
                'bestsellers_date' => '2023-01-01',
            ]
        ];

        $this->mockedService
            ->shouldReceive('getBestsellers')
            ->once()
            ->andReturn(
                HttpResult::success(
                    data: collect($mockResponseData),
                    statusCode: HttpStatusCode::HTTP_OK,
                    meta: [
                        'headers' => [],
                        'url' => [],
                        'serviceStatusCode' => 200,
                    ]
                )
            );

        $response = $this->getJson('/api/v1/bestsellers?author=Filtered+Author&title=Filtered+Book');

        $response->assertStatus(200)
            ->assertJsonPath('0.title', 'Filtered Book')
            ->assertJsonPath('0.author', 'Filtered Author');
    }

    public function testGetBestSellersEmptyResults(): void
    {
        $this->mockedService
            ->shouldReceive('getBestsellers')
            ->once()
            ->andReturn(
                HttpResult::success(
                    data: collect([]),
                    statusCode: HttpStatusCode::HTTP_OK,
                    meta: [
                        'headers' => [],
                        'url' => [],
                        'serviceStatusCode' => 200,
                    ]
                )
            );

        $response = $this->getJson('/api/v1/bestsellers');

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function testGetBestSellersApiError(): void
    {
        $this->mockedService
            ->shouldReceive('getBestsellers')
            ->once()
            ->andReturn(
                HttpResult::failure(
                    message: 'NYT API request failed',
                    errors: ['NYT API request failed'],
                    statusCode: HttpStatusCode::HTTP_INTERNAL_SERVER_ERROR,
                    meta: ['serviceStatusCode' => 500]
                )
            );

        $response = $this->getJson('/api/v1/bestsellers');

        $response->assertStatus(500)
            ->assertJson([
                '0' => 'NYT API request failed'
            ]);
    }

    public function testGetBestSellersInvalidApiKey(): void
    {
        $this->mockedService
            ->shouldReceive('getBestsellers')
            ->once()
            ->andReturn(
                HttpResult::failure(
                    message: 'NYT API request failed',
                    errors: ['Invalid API key'],
                    statusCode: HttpStatusCode::HTTP_UNAUTHORIZED,
                    meta: ['serviceStatusCode' => 401]
                )
            );

        $response = $this->getJson('/api/v1/bestsellers?client_api_key=invalid_key');

        $response->assertStatus(401);

        $this->assertJson($response->getContent(), 'Invalid API key');
    }

    public function testGetBestSellersCaching(): void
    {
        $mockResponseData = [
            [
                'title' => 'Cached Book',
                'author' => 'Cached Author',
                'publisher' => 'Test Publisher',
                'rank' => 1,
                'bestsellers_date' => '2023-01-01',
            ]
        ];

        $this->mockedService
            ->shouldReceive('getBestsellers')
            ->andReturn(
                HttpResult::success(
                    data: collect($mockResponseData),
                    statusCode: HttpStatusCode::HTTP_OK,
                    meta: [
                        'headers' => [],
                        'url' => [],
                        'serviceStatusCode' => 200,
                    ]
                )
            );

        $this->getJson('/api/v1/bestsellers');

        $response = $this->getJson('/api/v1/bestsellers');

        $response->assertStatus(200)
            ->assertJsonPath('0.title', 'Cached Book')
            ->assertJsonPath('0.author', 'Cached Author');
    }
}
