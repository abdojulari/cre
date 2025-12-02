<?php

use App\Services\DuplicateCheckerService;
use Illuminate\Support\Collection;
use Tests\TestCase;

uses(TestCase::class);

// Setup: Before Each Test
beforeEach(function () {
    $this->service = new DuplicateCheckerService();
});

// Test for 'retrieveDuplicateUsingCache' method
it('can retrieve a duplicate from the cache', function () {
    $redis = collect([
        ['dateofbirth' => '2005-01-01', 'lastname' => 'Doe', 'firstname' => 'John', 'address' => '123 Main St', 'source' => 'test'],
        ['dateofbirth' => '2005-01-01', 'lastname' => 'Doe', 'firstname' => 'John', 'address' => '123 Main St', 'source' => 'test'],
    ]);
    
    $data = ['dateofbirth' => '2005-01-01', 'lastname' => 'Doe', 'firstname' => 'John', 'address' => '123 Main St', 'source' => 'test'];

    $duplicate = $this->service->retrieveDuplicateUsingCache($data, $redis);

    expect($duplicate)->toBe($redis[0]);
});

// Test for 'normalizeAddress' method
it('normalizes addresses correctly', function () {
    $address = '123 Main Street, Ave NW';
    $normalized = $this->service->normalizeAddress($address);

    expect($normalized)->toBe('123 main st ave nw');
});

// Test for 'isDuplicate' method
it('detects duplicates based on certain fields', function () {
    $record1 = [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'phone' => '1234567890',
        'email' => 'john@example.com',
        'dateofbirth' => '2000-01-01',
        'address' => '123 Main St',
        'source' => 'test',
    ];

    $record2 = [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'phone' => '1234567890',
        'email' => 'john@example.com',
        'dateofbirth' => '2000-01-01',
        'address' => '123 Main St',
        'source' => 'test',
    ];

    $isDuplicate = $this->service->isDuplicate($record1, $record2);

    expect($isDuplicate)->toBeTrue();
});

it('returns false for non-duplicates', function () {
    $record1 = [
        'firstname' => 'Jane',
        'lastname' => 'Doe',
        'phone' => '1234567890',
        'email' => 'jane@example.com',
        'dateofbirth' => '1995-01-01',
        'address' => '123 Main St',
        'source' => 'test',
    ];

    $record2 = [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'phone' => '1234567890',
        'email' => 'john@example.com',
        'dateofbirth' => '1990-01-01',
        'address' => '456 Oak St',
        'source' => 'test',
    ];

    $isDuplicate = $this->service->isDuplicate($record1, $record2);

    expect($isDuplicate)->toBeFalse();
});

// Test for 'similarity' method
it('calculates the similarity between two strings', function () {
    $str1 = 'Hello World';
    $str2 = 'Hello World!';

    $similarity = $this->service->similarity($str1, $str2);

    expect($similarity)->toBeGreaterThan(0); // Ensure similarity score is calculated
});

// Test for 'checkCareofRegistrationLimit' method with mocking RedisService
it('checks careof registration limit correctly', function () {
    $redisService = Mockery::mock('App\Services\RedisService');
    $redisService->shouldReceive('get')->once()->andReturn(json_encode([
        ['careof' => 'John Doe', 'phone' => '1234567890', 'email' => 'john@example.com'],
        // Add more records here as needed
    ]));

    $careofName = 'John Doe';
    $phone = '1234567890';
    $email = 'john@example.com';

    $canRegister = $this->service->checkCareofRegistrationLimit($redisService, $careofName, $phone, $email);

    expect($canRegister)->toBeTrue(); // or false if the limit is exceeded
});
