<?php

declare(strict_types=1);

namespace App\NewYorkTimes\Service;

use App\NewYorkTimes\Service\Dto\BestsellerBookFiltersDto;
use App\NewYorkTimes\Service\Dto\NytHttpClientInformationDto;
use App\NewYorkTimes\Service\ValueObject\NytApiKey;
use App\NewYorkTimes\UserInterface\Api\V1\Resource\StableBestsellerResource;
use App\Utils\Http\HttpResult;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RedirectMiddleware;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpStatusCode;

class NytHttpClient
{
    public function __construct(
        private readonly NytHttpClientInformationDto $informationDto,
        private readonly ClientInterface $httpClient,
    ) {
    }

    public function getBestSellers(BestsellerBookFiltersDto $filters): HttpResult
    {
        try {
            $clientApiKey = null;

            if ($filters->clientApiKey !== null) {
                $clientApiKey = (new NytApiKey($filters->clientApiKey))->apiKey;
            }

            $queryParams = [
                'api-key' => $clientApiKey ?? $this->informationDto->nytApiKey,
            ];

            $filteredArray = $filters->toFilteredArray();

            if (!empty($filteredArray)) {
                $queryParams = array_merge($queryParams, $filteredArray);
            }

            $response = $this->httpClient->get(
                uri: $this->informationDto->buildUrl('svc/books', 'lists/best-sellers/history.json'),
                options: ['query' => $queryParams]
            );

            return $this->handleResponse(
                $response,
                fn(array $results) => collect($results)->map(fn(array $item) => new StableBestsellerResource($item))
            );
        } catch (\Exception $exception) {
            return HttpResult::fromException(exception: $exception);
        };
    }

    /**
     * @return HttpResult It should not be possible for clients to send an invalid payload
     * that could result in NYT returning 400ish - that's why it's set to 500,
     * because it means we implemented the connection between NYT api and ours wrong.
     * @throws \JsonException
     */
    private function handleResponse(ResponseInterface $response, \Closure $transformRawData): HttpResult
    {
        if (!str_starts_with((string) $response->getStatusCode(), '2')) {
            return HttpResult::failure(
                message: 'NYT API request failed',
                errors: ['response' => $response->getBody()],
                statusCode: HttpStatusCode::HTTP_INTERNAL_SERVER_ERROR,
                meta: ['url' => $response->getHeader(RedirectMiddleware::HISTORY_HEADER), 'serviceStatusCode' => $response->getStatusCode()],
            );
        }

        try {
            $decodedResponse = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR)['results'];
        } catch (\JsonException $exception) {
            return HttpResult::fromException(message: 'Response deserialization to JSOn failed', exception: $exception);
        }

        return HttpResult::success(
            data: $transformRawData($decodedResponse),
            statusCode: HttpStatusCode::HTTP_OK,
            meta: [
                'headers' => $response->getHeaders(),
                'url' => $response->getHeader(RedirectMiddleware::HISTORY_HEADER),
                'serviceStatusCode' => $response->getStatusCode(),
            ],
        );
    }
}