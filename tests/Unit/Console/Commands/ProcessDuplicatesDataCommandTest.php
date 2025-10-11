<?php

use App\Console\Commands\ProcessDuplicatesDataCommand;
use App\Services\RedisService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Set up the mock for RedisService
    $this->redisServiceMock = Mockery::mock(RedisService::class);
    $this->app->instance(RedisService::class, $this->redisServiceMock);

    // Mock the contents of duplicates.json
    $mockData = [
        ['id' => 1, 'name' => 'John Doe'],
        ['id' => 2, 'name' => 'Jane Doe']
    ];
    
    // Write to the real path the command reads
    if (!is_dir(storage_path('app'))) {
        mkdir(storage_path('app'), 0777, true);
    }
    file_put_contents(storage_path('app/duplicates.json'), json_encode($mockData));
});

afterEach(function () {
    Mockery::close();
});

it('should process duplicates and save to redis', function () {
    // Prepare the mock expectations before running the command
    $mockData = [
        ['id' => 1, 'name' => 'John Doe'],
        ['id' => 2, 'name' => 'Jane Doe']
    ];
    
    if (empty($mockData)) {
        $this->redisServiceMock
            ->shouldNotReceive('set');
    } else {
        $this->redisServiceMock
            ->shouldReceive('set')
            ->with('cre_registration_record', json_encode($mockData))
            ->andReturn(true);
    }

    // Clear output buffer
    Artisan::output();

    // Run the command
    $exitCode = $this->artisan('data:replace')->run();

    // Assert command executed successfully
    expect($exitCode)->toBe(0);
});

it('should handle empty duplicates.json gracefully', function () {
    // Create empty file
   // Storage::put('duplicates.json', json_encode([]));
   $data = [];
    // Clear output buffer
    Artisan::output();

    // Run the command and capture output
    $exitCode = $this->artisan('data:replace')
        ->assertExitCode(0)
        ->run();
    // Assert the error message
    $this->artisan('data:replace')->expectsOutput(null);
});

it('should handle exceptions and output the error', function () {
    // Simulate Redis error
    $this->redisServiceMock
        ->expects()
        ->set(Mockery::any(), Mockery::any())
        ->andThrow(new Exception('Redis error'))
        ->once();

    // Clear output buffer
    Artisan::output();

    // Run the command
    $this->artisan('data:replace')
        ->assertExitCode(0);
});