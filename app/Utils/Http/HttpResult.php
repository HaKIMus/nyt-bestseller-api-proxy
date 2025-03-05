<?php

declare(strict_types=1);

namespace App\Utils\Http;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatusCode;
use Throwable;

/**
 * Immutable class representing an HTTP result that can be either a success or failure.
 */
final readonly class HttpResult
{
    /**
     * @param string|null $message Optional message describing the result
     * @param array<string, mixed> $errors Validation or other errors (for failure responses)
     * @param array<string, mixed> $meta Additional metadata
     */
    private function __construct(
        public bool $success,
        public mixed $data = null,
        public ?string $message = null,
        public array $errors = [],
        public int $statusCode = 200,
        public array $meta = [],
    ) {
    }

    /**
     * @param string|null $message
     * @param array<string, mixed> $meta
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200,
        array $meta = [],
    ): self {
        return new self(
            success: true,
            data: $data,
            message: $message,
            statusCode: $statusCode,
            meta: $meta,
        );
    }

    /**
     * @param array<string, mixed> $errors
     * @param array<string, mixed> $meta
     */
    public static function failure(
        string $message,
        array $errors = [],
        int $statusCode = 400,
        mixed $data = null,
        array $meta = [],
    ): self {
        return new self(
            success: false,
            data: $data,
            message: $message,
            errors: $errors,
            statusCode: $statusCode,
            meta: $meta,
        );
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function fromException(
        Throwable $exception,
        ?string $message = null,
        ?int $statusCode = null,
        array $meta = [],
    ): self {
        return self::failure(
            message: $message ?? $exception->getMessage(),
            errors: ['exception' => get_class($exception)],
            statusCode: $statusCode ?? ($exception->getCode() ?: 500),
            meta: $meta,
        );
    }

    public function toResponse(): JsonResponse
    {
        $response = [];
        $statusCode = $this->statusCode;

        if ($this->success && empty($this->data)) {
            $statusCode = HttpStatusCode::HTTP_NOT_FOUND;
        }

        if ($this->success && $this->data !== null) {
            $response = $this->data;
        }

        if (!$this->success && !empty($this->errors)) {
            $response = $this->errors;
        }

        return new JsonResponse($response, $statusCode);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}