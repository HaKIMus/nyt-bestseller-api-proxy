<?php

declare(strict_types=1);

namespace Tests\Unit\NewYorkTimes\Service\Dto;

use App\NewYorkTimes\Service\Dto\BestsellerBookFiltersDto;
use Tests\TestCase;

class BestsellerBookFiltersDtoTest extends TestCase
{
    public function test_to_filtered_array_returns_empty_when_no_filters_provided(): void
    {
        $dto = new BestsellerBookFiltersDto();
        $expected = [];

        $this->assertSame($expected, $dto->toFilteredArray());
    }

    public function test_to_filtered_array_returns_filtered_array_when_filters_provided(): void
    {
        $author = 'John Doe';
        $isbn = ['1234567890', '0987654321'];
        $title = 'A Great Book';
        $offset = 10;
        $clientApiKey = 'secret-key';

        $dto = new BestsellerBookFiltersDto(
            author: $author,
            isbn: $isbn,
            title: $title,
            offset: $offset,
            clientApiKey: $clientApiKey
        );

        $expected = [
            'author' => $author,
            'title'  => $title,
            'offset' => $offset,
            'isbn'   => implode(';', $isbn),
        ];

        $this->assertSame($expected, $dto->toFilteredArray());
    }

    public function test_to_filtered_array_filters_null_values(): void
    {
        $author = 'Jane Doe';
        $isbn = ['1111111111'];

        $dto = new BestsellerBookFiltersDto(
            author: $author,
            isbn: $isbn
        );

        $expected = [
            'author' => $author,
            'isbn'   => implode(';', $isbn),
        ];

        $this->assertSame($expected, $dto->toFilteredArray());
    }
}
