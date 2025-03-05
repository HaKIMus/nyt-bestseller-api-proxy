<?php

declare(strict_types=1);

namespace App\NewYorkTimes\Service\Exception;

class RateLimitedException extends \RuntimeException
{
    public function __construct(string $message = 'Action failed deu to exceeded rate limits', public readonly int $nextAvailableTimeInSeconds = 0)
    {
        parent::__construct($message);
    }
}