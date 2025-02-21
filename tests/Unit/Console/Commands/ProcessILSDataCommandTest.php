<?php

namespace Tests\Console\Commands;

use App\Console\Commands\ProcessILSDataCommand;
use App\Services\RedisService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;
use Mockery;

uses(TestCase::class);

beforeEach(function () {
    // Mock RedisService to avoid actual Redis operations
    $this->redisServiceMock = Mockery::mock(RedisService::class);
    $this->app->instance(RedisService::class, $this->redisServiceMock);
});

it('processes data and saves to Redis', function () {
    // Given: We have a path to the new data file
    $path = storage_path('app/new-ils-user.json');

    // Mock the content of the new data file
    $newData = [
        ['barcode' => '12345', 'name' => 'Test User'],
        ['barcode' => '67890', 'name' => 'Another User']
    ];
    File::put($path, json_encode($newData));

    // Mock the RedisService methods
    $existingData = [
        ['barcode' => '12345', 'name' => 'Old User']
    ];

    // Setup Redis service mock behavior for get() and set()
    $this->redisServiceMock
        ->shouldReceive('get')
        ->once()
        ->with('cre_registration_record')
        ->andReturn(json_encode($existingData));

    $this->redisServiceMock
        ->shouldReceive('set')
        ->once()
        ->with('cre_registration_record', json_encode([
            ['barcode' => '12345', 'name' => 'Test User'],
            ['barcode' => '67890', 'name' => 'Another User']
        ]));

    // When: The command is executed
    Artisan::call(ProcessILSDataCommand::class);

    // Then: Ensure the correct command output and Redis interactions
    $this->assertStringContainsString('Data merged and saved to Redis successfully!', Artisan::output());

    // Verify if the new data is saved in Redis correctly
    $this->redisServiceMock->shouldHaveReceived('set')->once();
    
    // Cleanup: Clear the new data file after processing
    File::put($path, '');
});

it('handles no new data gracefully', function () {
    // Given: Empty new data
    $path = storage_path('app/new-ils-user.json');
    File::put($path, json_encode([]));  // Empty new data

    // Mock RedisService for the existing data
    $existingData = [
        ['barcode' => '12345', 'name' => 'Old User']
    ];

    $this->redisServiceMock
        ->shouldReceive('get')
        ->once()
        ->with('cre_registration_record')
        ->andReturn(json_encode($existingData));

    // When: The command is executed
    Artisan::call(ProcessILSDataCommand::class);

    // Then: Ensure the correct command output
    $this->assertStringContainsString('No new data found in the file.', Artisan::output());

    // Verify no interaction with Redis (since no new data)
    $this->redisServiceMock->shouldNotHaveReceived('set');
});

it('handles exceptions gracefully', function () {
    // Given: An exception is thrown while getting existing data
    $this->redisServiceMock
        ->shouldReceive('get')
        ->once()
        ->with('cre_registration_record')
        ->andThrow(new \Exception('Redis error'));

    // When: The command is executed
    Artisan::call(ProcessILSDataCommand::class);

    // Then: Ensure the correct error message is output
    $this->assertStringContainsString('Error: Redis error', Artisan::output());
});
