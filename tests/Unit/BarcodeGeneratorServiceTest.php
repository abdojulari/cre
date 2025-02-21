<?php

use App\Services\BarcodeGeneratorService;
use App\Services\RedisService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Mock RedisService
    $this->redisService = Mockery::mock(RedisService::class);

    // Set config values directly
    config(['cre.barcode_prefix' => '212219']);
    config(['cre.start_barcode' => 10000000]);


    // Instantiate BarcodeGeneratorService
    $this->barcodeGeneratorService = new BarcodeGeneratorService($this->redisService);
});

it('generates a new barcode by incrementing the last one in Redis', function () {
    // Simulate that the last barcode is '21221910000001'
    $this->redisService->shouldReceive('get')
        ->with('barcode')
        ->andReturn('21221910000001');

    // Expect Redis set call
    $this->redisService->shouldReceive('set')
        ->with('barcode', '21221910000002')
        ->once();

    // Call the method
    $response = $this->barcodeGeneratorService->generate();

    // Assert the response structure and value
    expect($response->getData(true))->toHaveKey('barcode', '21221910000002');
});

it('generates a new barcode when no last barcode is present in Redis', function () {
    // Simulate that no last barcode exists
    $this->redisService->shouldReceive('get')
        ->with('barcode')
        ->andReturn(null);

    // Expect Redis set call
    $this->redisService->shouldReceive('set')
        ->with('barcode', '21221910000000')
        ->once();

    // Call the method
    $response = $this->barcodeGeneratorService->generate();

    // Assert the response structure and value
    expect($response->getData(true))->toHaveKey('barcode', '21221910000000');
});