<?php

declare(strict_types=1);

namespace Tests\Unit\NewYorkTimes\Service;

use App\NewYorkTimes\Service\Dto\BestsellerBookFiltersDto;
use App\NewYorkTimes\Service\Dto\NytHttpClientInformationDto;
use App\NewYorkTimes\Service\NytHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpStatusCode;

class NytHttpClientTest extends TestCase
{
    private NytHttpClient $nytHttpClient;
    private MockInterface $mockHttpClient;
    private NytHttpClientInformationDto $informationDto;
    private string $defaultApiKey = 'test-api-key-123';
    private string $baseUrl = 'https://api.nytimes.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpClient = Mockery::mock(ClientInterface::class);

        $this->informationDto = new NytHttpClientInformationDto(
            nytBaseUrl: $this->baseUrl,
            nytVersion: 'v3',
            nytApiKey: $this->defaultApiKey
        );

        $this->nytHttpClient = new NytHttpClient(
            informationDto: $this->informationDto,
            httpClient: $this->mockHttpClient
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetBestsellersWithErrorResponse(): void
    {
        $filters = new BestsellerBookFiltersDto(author: 'Test Author');

        $errorResponse = new Response(
            status: 401,
            headers: ['Content-Type' => 'application/json'],
            body: json_encode(['fault' => ['faultstring' => 'Invalid API Key']])
        );

        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->andReturn($errorResponse);

        $result = $this->nytHttpClient->getBestSellers($filters);

        $this->assertFalse($result->success);
        $this->assertEquals(HttpStatusCode::HTTP_INTERNAL_SERVER_ERROR, $result->statusCode);
        $this->assertEquals('NYT API request failed', $result->message);
        $this->assertArrayHasKey('response', $result->errors);
        $this->assertArrayHasKey('serviceStatusCode', $result->meta);
        $this->assertEquals(401, $result->meta['serviceStatusCode']);
    }

    public function testGetBestsellersWithException(): void
    {
        $filters = new BestsellerBookFiltersDto(author: 'Test Author');

        $mockRequest = new Request('GET', 'test-url');
        $mockException = new RequestException(
            'Connection timed out',
            $mockRequest
        );

        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->andThrow($mockException);

        $result = $this->nytHttpClient->getBestSellers($filters);

        $this->assertFalse($result->success);
        $this->assertEquals('Connection timed out', $result->message);
    }

    public function testGetBestsellersWithEmptyResults(): void
    {
        $filters = new BestsellerBookFiltersDto(
            author: 'Non Existent Author',
            title: 'Non Existent Book'
        );

        $responseData = [
            'status' => 'OK',
            'copyright' => '© 2023 The New York Times',
            'num_results' => 0,
            'results' => []
        ];

        $mockResponse = new Response(
            status: 200,
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($responseData)
        );

        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->nytHttpClient->getBestSellers($filters);

        $this->assertTrue($result->success);
        $this->assertEquals(HttpStatusCode::HTTP_OK, $result->statusCode);
        $this->assertCount(0, $result->data);
    }

    public function testGetBestsellersWithFilteredArray(): void
    {
        $filters = new BestsellerBookFiltersDto(
            author: 'Test Author',
            title: 'Test Book',
            isbn: ['9781234567890'],
            offset: 20,
        );

        $responseData = [
            'status' => 'OK',
            'results' => [['title' => 'Test Book']]
        ];
        $mockResponse = new Response(
            status: 200,
            headers: ['Content-Type' => 'application/json'],
            body: json_encode($responseData)
        );

        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->nytHttpClient->getBestSellers($filters);

        $this->assertTrue($result->success);
    }

    public function testGetBestsellersWithInvalidJsonResponse(): void
    {
        $filters = new BestsellerBookFiltersDto(author: 'Test Author');

        $mockResponse = new Response(
            status: 200,
            headers: ['Content-Type' => 'application/json'],
            body: '{invalid_json:'
        );

        $this->mockHttpClient
            ->shouldReceive('get')
            ->once()
            ->andReturn($mockResponse);

        $result = $this->nytHttpClient->getBestSellers($filters);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Response deserialization to JSOn failed', $result->message);
    }
}
