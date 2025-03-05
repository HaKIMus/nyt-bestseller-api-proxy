<?php

declare(strict_types=1);

namespace App\NewYorkTimes\Service\Dto;

final readonly class BestsellerBookFiltersDto
{
    /**
     * @param string[] $isbn
     */
    public function __construct(
        public ?string $author = null,
        public ?array $isbn = null,
        public ?string $title = null,
        public ?int $offset = null,
        public ?string $clientApiKey = null,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toFilteredArray(): array
    {
        $filters = [
            'author' => $this->author,
            'title' => $this->title,
            'offset' => $this->offset,
        ];

        if ($this->isbn !== null) {
            $filters['isbn'] = implode(';', $this->isbn);
        }

        return array_filter($filters, fn($value) => $value !== null);
    }
}