<?php

use App\Http\Controllers\BarcodeGenerator\BarcodeGeneratorController;
use App\Services\RedisService;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Create a mock of RedisService
    $this->redisMock = Mockery::mock(RedisService::class);
    
    // Bind the mock to the container
    app()->instance(RedisService::class, $this->redisMock);
    
    // Set the config values for digital barcode (matching what controller uses)
    config(['cre.start_digital_barcode' => '00101000']);
    config(['cre.digital_barcode_prefix' => '212217']);

    // Obtain the token once before the tests run
    $clientId = env('CLIENT_ID');
    $clientSecret = env('CLIENT_SECRET');
    
    $response = $this->postJson('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ]);
    
    // Store the access token for reuse in the tests
    $this->token = $response->json('access_token');
});

afterEach(function () {
    Mockery::close();
});

it('generates first barcode when no previous barcode exists', function () {
    // Arrange
    $this->redisMock
        ->shouldReceive('get')
        ->with('digital_barcode')
        ->once()
        ->andReturnNull();
    
    $this->redisMock
        ->shouldReceive('set')
        ->with('digital_barcode', '21221700101000')
        ->once();
    
    // Act
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token, 
        ])->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'barcode' => '21221700101000'
        ]);
});

it('generates next barcode when previous barcode exists', function () {
    // Arrange
    $this->redisMock
        ->shouldReceive('get')
        ->with('digital_barcode')
        ->once()
        ->andReturn('21221700101000');
    
    $this->redisMock
        ->shouldReceive('set')
        ->with('digital_barcode', '21221700101001')
        ->once();
    
    // Act
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token, 
        ])->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'barcode' => '21221700101001'
        ]);
});

it('handles large numeric values correctly', function () {
    // Arrange
    $this->redisMock
        ->shouldReceive('get')
        ->with('digital_barcode')
        ->once()
        ->andReturn('21221799999998');
    
    $this->redisMock
        ->shouldReceive('set')
        ->with('digital_barcode', '21221799999999')
        ->once();
    
    // Act
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token, 
        ])->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'barcode' => '21221799999999'
        ]);
});

it('maintains 8-digit padding for numeric part', function () {
    // Arrange
    $this->redisMock
        ->shouldReceive('get')
        ->with('digital_barcode')
        ->once()
        ->andReturn('21221700000001');
    
    $this->redisMock
        ->shouldReceive('set')
        ->with('digital_barcode', '21221700000002')
        ->once();
    
    // Act
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token, 
        ])->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200)
        ->assertJson([
            'barcode' => '21221700000002'
        ]);
});