<?php

declare(strict_types=1);

namespace App\NewYorkTimes\Service\Dto;

readonly class NytHttpClientInformationDto
{
    public function __construct(
        public string $nytBaseUrl,
        public string $nytVersion,
        public string $nytApiKey,
    ) {}

    public function buildUrl(string $uri, string $suffix = ''): string
    {
        return sprintf("%s/%s/%s", $uri, $this->nytVersion, $suffix);
    }
}