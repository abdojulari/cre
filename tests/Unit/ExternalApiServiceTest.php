<?php

use App\Services\ExternalApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->service = new ExternalApiService();
    
    // Mock config values
    config([
        'cre.ils_base_url' => 'https://example.com/',
        'cre.endpoint' => 'auth',
        'cre.symws_user' => 'test_user',
        'cre.symws_pass' => 'test_pass',
        'cre.symws_client_id' => 'test_client_id',
        'cre.apps_id' => 'test_app_id',
        'cre.patron_endpoint' => 'patron',
        'cre.barcode_url' => 'barcode/',
        'cre.user_auth' => 'user/auth'
    ]);
});

it('gets session token successfully', function () {
    Http::fake([
        'https://example.com/auth' => Http::response(['sessionToken' => 'test_token'], 200)
    ]);

    $token = $this->service->getSessionToken();

    expect($token)->toBe('test_token');
});

it('returns null when session token request fails', function () {
    Http::fake([
        'https://example.com/auth' => Http::response(null, 500)
    ]);

    $token = $this->service->getSessionToken();

    expect($token)->toBeNull();
});

it('posts data to ILS successfully', function () {
    Http::fake([
        'https://example.com/auth' => Http::response(['sessionToken' => 'test_token'], 200),
        'https://example.com/patron' => Http::response(['success' => true], 200)
    ]);

    $data = ['test' => 'data'];
    $response = $this->service->postToILS($data);

    expect($response)->toBe(['success' => true]);
});

it('retrieves ILS data successfully', function () {
    Http::fake([
        'https://example.com/auth' => Http::response(['sessionToken' => 'test_token'], 200),
        'https://example.com/barcode/*' => Http::response(['data' => 'test_data'], 200)
    ]);

    $data = ['barcode' => '12345'];
    $response = $this->service->retrieveILSData($data);

    expect($response)->toBe(['data' => 'test_data']);
});


it('updates ILS data successfully', function () {
    Http::fake([
        'https://example.com/auth' => Http::response(['sessionToken' => 'test_token'], 200),
        'https://example.com/barcode/*' => Http::response(['@key' => 'test_key'], 200),
        'https://example.com/patron/key/*' => Http::response(['success' => true], 200)
    ]);

    $data = ['barcode' => '12345', 'test' => 'data'];
    $response = $this->service->updateToILS($data);

    expect($response)->toBe(['success' => true]);
});

it('authenticates user successfully', function () {
    $patronData = [
        'lastName' => 'Doe',
        'firstName' => 'John',
        'middleName' => 'Smith',
        'birthDate' => '1990-01-01',
        'profile' => ['@key' => 'test_profile'],
        'address1' => [
            ['code' => ['@key' => 'CARE/OF'], 'data' => 'Care of Test'],
            ['code' => ['@key' => 'PHONE'], 'data' => '1234567890'],
            ['code' => ['@key' => 'STREET'], 'data' => '123 Test St'],
            ['code' => ['@key' => 'CITY/STATE'], 'data' => 'TestCity, TS'],
            ['code' => ['@key' => 'POSTALCODE'], 'data' => '12345'],
            ['code' => ['@key' => 'EMAIL'], 'data' => 'test@example.com']
        ]
    ];

    Http::fake([
        'https://example.com/auth' => Http::response(['sessionToken' => 'test_token'], 200),
        'https://example.com/user/auth' => Http::response(['patronKey' => 'test_key'], 200),
        'https://example.com/user/patron/key/*' => Http::response($patronData, 200)
    ]);

    $data = ['barcode' => '12345', 'password' => 'test_pass'];
    $response = $this->service->userAuth($data);
    
    expect($response)
        ->toBeObject()
        ->firstName->toBe('John')
        ->lastName->toBe('Doe')
        ->middleName->toBe('Smith')
        ->address->toBeObject()
        ->address->careOf->toBe('Care of Test')
        ->address->phone->toBe('1234567890')
        ->address->street->toBe('123 Test St')
        ->address->city->toBe('TestCity')
        ->address->province->toBe('TS')
        ->address->postalCode->toBe('12345')
        ->address->email->toBe('test@example.com');
});