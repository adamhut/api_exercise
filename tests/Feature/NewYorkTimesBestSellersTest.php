<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Set up mock configs
    Config::set('services.new_york_times.url', 'https://api.nytimes.com');
    Config::set('services.new_york_times.key', 'test-api-key');
});

test('successfully fetches best sellers with all valid parameters', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'OK',
            'results' => [
                ['title' => 'Test Book']
            ]
        ], Response::HTTP_OK)
    ]);

    $response = $this->getJson('/api/1/nyt/best-sellers?' . http_build_query([
        'isbn' => ['0593836324', '0593836325'],
        'author' => 'John Doe',
        'title' => 'Test Book',
        'offset' => 20
    ]));

    $response->assertStatus(Response::HTTP_OK);

    Http::assertSent(function ($request) {
        return $request['isbn'] === '0593836324;0593836325' &&
            $request['author'] === 'John Doe' &&
            $request['title'] === 'Test Book' &&
            $request['offset'] === '20';
    });
});

// ISBN Validation Tests

test('validates single ISBN format - 10 digits', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'OK',
            'results' => [
                ['title' => 'Test Book']
            ]
        ], Response::HTTP_OK)
    ]);
    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=1234567890');
    $response->assertStatus(Response::HTTP_OK);
});

test('validates single ISBN format - 13 digits', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'OK',
            'results' => [
                ['title' => 'Test Book']
            ]
        ], Response::HTTP_OK)
    ]);
    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=1234567890123');
    $response->assertStatus(Response::HTTP_OK);
});

test('rejects invalid ISBN format', function ($isbn) {
    $response = $this->getJson("/api/1/nyt/best-sellers?isbn[]=$isbn");
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['isbn.0']);
})->with([
    '123456789', // Too short
    '12345678901234', // Too long
    'abcdefghij', // Non-numeric
    '123-456-789', // Contains hyphens
]);

test('handles multiple valid ISBNs', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'OK',
            'results' => [
                ['title' => 'Test Book']
            ]
        ], Response::HTTP_OK)
    ]);

    $response = $this->getJson('/api/1/nyt/best-sellers?' . http_build_query([
        'isbn' => ['1234567890', '1234567890123']
    ]));

    $response->assertStatus(Response::HTTP_OK);

    Http::assertSent(function ($request) {
        return $request['isbn'] === '1234567890;1234567890123';
    });
});

// Author Validation Tests

test('validates author parameter', function ($author) {
    Http::fake(['*' => Http::response(['status' => 'OK'], Response::HTTP_OK)]);

    $response = $this->getJson("/api/1/nyt/best-sellers?author=$author");

    $response->assertStatus(Response::HTTP_OK);

    Http::assertSent(function ($request) use ($author) {
        return $request['author'] === $author;
    });
})->with([
    'John Doe',
    'J.K. Rowling',
    'Stephen King',
    str_repeat('a', 255) // Max length
]);

test('rejects invalid author parameter', function () {
    $response = $this->getJson('/api/1/nyt/best-sellers?author=' . str_repeat('a', 256));

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['author']);
});

test('validates title parameter', function ($title) {
    Http::fake(['*' => Http::response(['status' => 'OK'], Response::HTTP_OK)]);

    $response = $this->getJson("/api/1/nyt/best-sellers?title=$title");

    $response->assertStatus(Response::HTTP_OK);

    Http::assertSent(function ($request) use ($title) {
        return $request['title'] === $title;
    });
})->with([
    'The Great Gatsby',
    'To Kill a Mockingbird',
    '1984',
    str_repeat('a', 255) // Max length
]);

test('rejects invalid title parameter', function () {
    $response = $this->getJson('/api/1/nyt/best-sellers?title=' . str_repeat('a', 256));

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['title']);
});

// Offset Validation Tests

test('validates offset parameter', function ($offset) {
    Http::fake(['*' => Http::response(['status' => 'OK'], Response::HTTP_OK)]);

    $response = $this->getJson("/api/1/nyt/best-sellers?offset=$offset");

    $response->assertStatus(Response::HTTP_OK);

    Http::assertSent(function ($request) use ($offset) {
        return (int)$request['offset'] === $offset;
    });
})->with([
    0,
    20,
    40,
    60,
    100
]);

test('rejects invalid offset values', function ($offset) {

    $response = $this->getJson("/api/1/nyt/best-sellers?offset=$offset");

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['offset']);
})->with([
    -20, // Negative number
    15, // Not multiple of 20
    'abc', // Non-numeric
    1.5, // Decimal
]);

// Optional Parameters Tests

test('all parameters are optional', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], Response::HTTP_OK)]);

    $response = $this->getJson('/api/1/nyt/best-sellers');

    $response->assertStatus(Response::HTTP_OK);
});

// Combination Tests

test('handles multiple parameters with some invalid', function () {
    $response = $this->getJson('/api/1/nyt/best-sellers?' . http_build_query([
        'isbn' => ['1234567890', 'invalid-isbn'],
        'author' => str_repeat('a', 256),
        'title' => 'Valid Title',
        'offset' => 15
    ]));

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['isbn.1', 'author', 'offset']);
});

// Error Handling and Logging Tests

test('logs validation errors', function () {

    $response = $this->getJson('/api/1/nyt/best-sellers?' . http_build_query([
        'isbn' => ['invalid-isbn'],
        'offset' => 'invalid'
    ]));

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

test('handles empty array parameters', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], Response::HTTP_OK)]);

    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=');

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonValidationErrors(['isbn.0']);
});



test('handles large number of ISBN values', function () {
    $isbns = array_fill(0, 100, '1234567890');

    Http::fake(['*' => Http::response(['status' => 'OK'], Response::HTTP_OK)]);

    $response = $this->getJson('/api/1/nyt/best-sellers?' . http_build_query([
        'isbn' => $isbns
    ]));

    $response->assertStatus(Response::HTTP_OK);
});

// Special Characters Handling

test('handles special characters in parameters', function ($param) {
    Http::fake(['*' => Http::response(['status' => 'OK'], Response::HTTP_OK)]);

    $response = $this->getJson('/api/1/nyt/best-sellers?' . http_build_query([
        'title' => $param
    ]));

    $response->assertStatus(Response::HTTP_OK);
})->with([
    'Title & More',
    'Title: Subtitle',
    'Title (Series #1)',
    'Title\'s Apostrophe'
]);


test('handles rate limit exceeded error', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Too Many Requests'], 429)
    ]);

    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=0593836324');

    $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS)
        ->assertJson(['error' => 'Rate limit exceeded. Please try again later.']);

});

test('handles not found error', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Not Found'], 404)
    ]);

    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=0593836324');

    $response->assertStatus(Response::HTTP_NOT_FOUND)
        ->assertJson(['error' => 'No results found.']);
});

test('handles generic error', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Server Error'], 500)
    ]);

    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=0593836324');

    $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
        ->assertJson(['error' => 'Failed to fetch data']);
});

test('handles exception during request', function () {
    Http::fake(function () {
        throw new \Exception('Network error');
    });

    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=0593836324');

    $response->assertStatus(Response::HTTP_BAD_REQUEST)
        ->assertJson(['error' => 'something went wrong']);

});

test('validates request parameters', function () {
    // Test with invalid ISBN format
    $response = $this->getJson('/api/1/nyt/best-sellers?isbn[]=invalid-isbn');

    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

test('successfully handles additional parameters', function () {
    Http::fake([
        '*' => Http::response([
            'status' => 'OK',
            'results' => []
        ], Response::HTTP_OK)
    ]);

    $response = $this->getJson('/api/1/nyt/best-sellers?author=John Doe&title=Test Book&offset=20');

    $response->assertStatus(Response::HTTP_OK);

    Http::assertSent(function ($request) {
        return $request['author'] === 'John Doe' &&
            $request['title'] === 'Test Book' &&
            $request['offset'] === '20';
    });
});

test('logs error response details', function () {
    Http::fake([
        '*' => Http::response(['error' => 'API Error'], 500)
    ]);

    Log::spy();

    $this->getJson('/api/1/nyt/best-sellers?isbn[]=0593836324');

    Log::shouldHaveReceived('error')->with('NYT API Error', \Mockery::any());
});