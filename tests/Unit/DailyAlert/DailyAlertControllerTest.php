<?php

use App\Http\Controllers\DailyAlertController;
use App\Services\RedisService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DuplicateDetectionNotification;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Mock RedisService
    $this->redisService = Mockery::mock(RedisService::class);
    
    // Instantiate the controller with the mocked Redis service
    $this->controller = new DailyAlertController($this->redisService);
    // Bind mock into the container so route-resolved controller uses it
    app()->instance(RedisService::class, $this->redisService);
    $this->withoutMiddleware();
});

it('can detect no duplicates and return a message', function () {
    // Mock Redis data with no duplicates
    $data = [
        ['firstname' => 'John', 'lastname' => 'Doe', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'email' => 'john.doe@example.com']
    ];

    // Mock Redis service to return this data
    $this->redisService->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn($data);

    // Call the method
    $response = $this->controller->duplicateDetectionAlert();

    // Get the response data
    $responseData = json_decode($response->getContent(), true);

    // Assert response content
    expect($responseData)->toHaveKey('message');
    expect($responseData['message'])->toBe('No duplicates found');
});

it('can lookup a barcode and return results', function () {
    // Mock Redis data
    $data = [
        ['barcode' => '12345', 'firstname' => 'John', 'lastname' => 'Doe', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'email' => 'john.doe@example.com']
    ];

    // Mock Redis service to return this data
    $this->redisService->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn($data);

    // Register the route for testing
    $this->app['router']->post('/barcode/lookup', [DailyAlertController::class, 'barcodeLookup']);

    // Call the method
    $response = $this->postJson('/barcode/lookup', ['barcode' => '12345']);

    // Assert response content
    expect($response->status())->toBe(200);
    expect($response->json())->toHaveKey('result');
});

it('can list barcodes and return formatted data', function () {
    // Mock Redis data
    $data = [
        ['barcode' => '12345', 'firstname' => 'John', 'lastname' => 'Doe', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'email' => 'john.doe@example.com']
    ];

    // Mock Redis service to return this data
    $this->redisService->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn($data);

    // Call the method
    $response = $this->controller->listBarcodes();

    // Get the response data
    $responseData = json_decode($response->getContent(), true);

    // Assert response content
    expect($responseData)->toHaveKey('barcodes');
});