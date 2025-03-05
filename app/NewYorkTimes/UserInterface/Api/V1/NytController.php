<?php

declare(strict_types=1);

namespace App\NewYorkTimes\UserInterface\Api\V1;

use App\NewYorkTimes\Service\BestsellerResourceService;
use App\NewYorkTimes\Service\Dto\BestsellerBookFiltersDto;
use App\NewYorkTimes\UserInterface\Api\V1\Request\BestSellersRequest;
use App\Utils\Http\Controller;
use App\Utils\Http\HttpResult;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

class NytController extends Controller
{
    public function __construct(
        private readonly BestsellerResourceService $bestsellerResourceService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getBestSellers(BestSellersRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $httpResult = $this->bestsellerResourceService->getBestSellers(new BestsellerBookFiltersDto(...$filters),);

        return $this->mapToResponseAndLog($httpResult);
    }

    private function mapToResponseAndLog(HttpResult $httpResult): JsonResponse
    {
        if (!$httpResult->success) {
            $this->logger->error('NYT API request failed', [
                'message' => $httpResult->message,
                'errors' => $httpResult->errors,
                'serviceStatusCode' => $httpResult->meta['serviceStatusCode'] ?? 'undefined',
                'statusCode' => $httpResult->statusCode ?? 'undefined',
            ]);
        }

        return $httpResult->toResponse();
    }
}