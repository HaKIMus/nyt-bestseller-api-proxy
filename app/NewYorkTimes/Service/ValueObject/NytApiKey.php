<?php

declare(strict_types=1);

namespace App\NewYorkTimes\Service\ValueObject;

use Webmozart\Assert\Assert;

class NytApiKey
{
    public function __construct(public string $apiKey)
    {
        Assert::stringNotEmpty($apiKey, 'NYT API key cannot be empty');
    }
}