<?php

declare(strict_types=1);

namespace Tests\Unit\NewYorkTimes\UserInterface\Api\V1\Resource;

use App\NewYorkTimes\UserInterface\Api\V1\Resource\StableBestsellerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StableBestsellerResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mappings = require __DIR__ . '/../../../../../../../config/nyt_field_mapping.php'; // There is a better way to do it , but I'm short on time (PRs are welcome!)
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.title', ['title'])
            ->andReturn($mappings['title'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.author', ['author'])
            ->andReturn($mappings['author'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.isbn', ['isbn'])
            ->andReturn($mappings['isbn'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.publisher', ['publisher'])
            ->andReturn($mappings['publisher'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.description', ['description'])
            ->andReturn($mappings['description'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.rank', ['rank'])
            ->andReturn($mappings['rank'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.rank_last_week', ['rank_last_week'])
            ->andReturn($mappings['rank_last_week'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.weeks_on_list', ['weeks_on_list'])
            ->andReturn($mappings['weeks_on_list'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.ranks_history', ['ranks_history'])
            ->andReturn($mappings['ranks_history'])
            ->byDefault();
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.reviews', ['reviews'])
            ->andReturn($mappings['reviews'])
            ->byDefault();
    }

    public function testToArrayWithDirectFields(): void
    {
        $bookData = [
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'isbn' => '9780743273565',
            'publisher' => 'Scribner',
            'description' => 'A classic novel about the American Dream',
            'rank' => 1,
            'rank_last_week' => 2,
            'weeks_on_list' => 10,
        ];

        $resource = new StableBestsellerResource($bookData);
        $result = $resource->toArray(Request::create('/'));

        $this->assertEquals('The Great Gatsby', $result['title']);
        $this->assertEquals('F. Scott Fitzgerald', $result['author']);
        $this->assertEquals('9780743273565', $result['isbn']);
        $this->assertEquals('Scribner', $result['publisher']);
        $this->assertEquals('A classic novel about the American Dream', $result['description']);
        $this->assertEquals(1, $result['rank']);
        $this->assertEquals(2, $result['rank_last_week']);
        $this->assertEquals(10, $result['weeks_on_list']);
        $this->assertEmpty($result['ranks_history']);
        $this->assertEmpty($result['reviews']);
    }

    public function testToArrayWithAlternativeFieldNames(): void
    {
        $bookData = [
            'book_title' => 'The Great Gatsby',
            'book_author' => 'F. Scott Fitzgerald',
            'primary_isbn13' => '9780743273565',
            'book_publisher' => 'Scribner',
            'summary' => 'A classic novel about the American Dream',
            'bestseller_rank' => 1,
        ];

        $resource = new StableBestsellerResource($bookData);
        $result = $resource->toArray(Request::create('/'));

        $this->assertEquals('The Great Gatsby', $result['title']);
        $this->assertEquals('F. Scott Fitzgerald', $result['author']);
        $this->assertEquals('9780743273565', $result['isbn']);
        $this->assertEquals('Scribner', $result['publisher']);
        $this->assertEquals('A classic novel about the American Dream', $result['description']);
        $this->assertEquals(1, $result['rank']);
    }

    public function testToArrayWithNestedValues(): void
    {
        $bookData = [
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'ranks_history' => [
                [
                    'rank' => 3,
                    'rank_last_week' => 5,
                    'weeks_on_list' => 15
                ],
                [
                    'rank' => 4,
                    'rank_last_week' => 3,
                    'weeks_on_list' => 14
                ]
            ]
        ];

        $resource = new StableBestsellerResource($bookData);
        $result = $resource->toArray(Request::create('/'));

        $this->assertEquals('The Great Gatsby', $result['title']);
        $this->assertEquals('F. Scott Fitzgerald', $result['author']);
        $this->assertEquals(3, $result['rank']);
        $this->assertEquals(5, $result['rank_last_week']);
        $this->assertEquals(15, $result['weeks_on_list']);
        $this->assertEquals($bookData['ranks_history'], $result['ranks_history']);
    }

    public function testToArrayWithEmptyData(): void
    {
        $resource = new StableBestsellerResource([]);
        $result = $resource->toArray(Request::create('/'));

        $this->assertNull($result['title']);
        $this->assertNull($result['author']);
        $this->assertNull($result['isbn']);
        $this->assertNull($result['publisher']);
        $this->assertNull($result['description']);
        $this->assertNull($result['rank']);
        $this->assertEmpty($result['rank_last_week']);
        $this->assertEmpty($result['weeks_on_list']);
        $this->assertEmpty($result['ranks_history']);
        $this->assertEmpty($result['reviews']);
    }

    public function testToArrayWithFallbackValues(): void
    {
        Config::shouldReceive('get')
            ->with('nyt_field_mapping.rank_last_week', ['rank_last_week'])
            ->andReturn([
                'rank_last_week',
                [
                    'path' => 'ranks_history.0.rank_last_week',
                    'fallback' => 0,
                ],
            ]);

        Config::shouldReceive('get')
            ->with('nyt_field_mapping.weeks_on_list', ['weeks_on_list'])
            ->andReturn([
                'weeks_on_list',
                [
                    'path' => 'ranks_history.0.weeks_on_list',
                    'fallback' => 0,
                ],
            ]);

        $bookData = [
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
        ];

        $resource = new StableBestsellerResource($bookData);
        $result = $resource->toArray(Request::create('/'));

        $this->assertEquals('The Great Gatsby', $result['title']);
        $this->assertEquals('F. Scott Fitzgerald', $result['author']);
        $this->assertEquals(0, $result['rank_last_week']);
        $this->assertEquals(0, $result['weeks_on_list']);
    }
}
