<?php

declare(strict_types=1);

return [
    'title' => ['title', 'book_title', 'display_title'],
    'author' => ['author', 'book_author', 'writer', 'display_author'],
    'isbn' => ['isbns', 'isbn', 'primary_isbn13', 'primary_isbn10'],
    'publisher' => ['publisher', 'book_publisher'],
    'description' => ['description', 'summary', 'book_description'],
    'rank' => [
        'rank',
        'bestseller_rank',
        'list_rank',
        [
            'path' => 'ranks_history.0.rank',
            'fallback' => null,
            'transform' => null,
        ],
    ],

    'rank_last_week' => [
        'rank_last_week',
        [
            'path' => 'ranks_history.0.rank_last_week',
            'fallback' => 0,
        ],
    ],

    'weeks_on_list' => [
        'weeks_on_list',
        [
            'path' => 'ranks_history.0.weeks_on_list',
            'fallback' => 0,
        ],
    ],

    'ranks_history' => [
        'ranks_history',
        [
            'path' => 'ranks_history',
            'fallback' => 0,
        ],
    ],

    'reviews' => [
        'reviews',
        [
            'path' => 'reviews',
            'fallback' => 0,
        ],
    ],
];
