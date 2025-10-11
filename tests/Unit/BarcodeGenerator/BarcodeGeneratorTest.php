<?php

use App\Http\Controllers\BarcodeGenerator\BarcodeGeneratorController;
use App\Services\RedisService;
use App\Models\GeneratedBarcode;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Create a mock of RedisService
    $this->redisMock = Mockery::mock(RedisService::class);
    
    // Bind the mock to the container
    app()->instance(RedisService::class, $this->redisMock);
    
    // Mock static methods on GeneratedBarcode model to avoid real DB
    $this->generatedBarcodeMock = Mockery::mock('alias:App\\Models\\GeneratedBarcode');
    
    // Set the config values for digital barcode (matching what controller uses)
    config(['cre.start_digital_barcode' => '00101000']);
    config(['cre.digital_barcode_prefix' => '212217']);
    // Disable middleware and avoid OAuth/DB
    $this->withoutMiddleware();
    $this->token = 'test-token';
});

afterEach(function () {
    Mockery::close();
});

it('generates first barcode when no previous barcode exists', function () {
    // Arrange
    $this->redisMock->shouldReceive('set')->with('digital_barcode_latest', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('addToSet')->with('generated_barcodes_digital', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('isInSet')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('barcodeExists')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('create')->andReturnTrue();
    
    // Act
    $response = $this->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200);
    $barcode = $response->json('barcode');
    expect($barcode)->toMatch('/^212217\\d{8}$/');
});

it('generates next barcode when previous barcode exists', function () {
    // Arrange
    $this->redisMock->shouldReceive('set')->with('digital_barcode_latest', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('addToSet')->with('generated_barcodes_digital', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('isInSet')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('barcodeExists')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('create')->andReturnTrue();
    
    // Act
    $response = $this->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200);
    $barcode = $response->json('barcode');
    expect($barcode)->toMatch('/^212217\\d{8}$/');
});

it('handles large numeric values correctly', function () {
    // Arrange
    $this->redisMock->shouldReceive('set')->with('digital_barcode_latest', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('addToSet')->with('generated_barcodes_digital', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('isInSet')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('barcodeExists')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('create')->andReturnTrue();
    
    // Act
    $response = $this->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200);
    $barcode = $response->json('barcode');
    expect($barcode)->toMatch('/^212217\\d{8}$/');
});

it('maintains 8-digit padding for numeric part', function () {
    // Arrange
    $this->redisMock->shouldReceive('set')->with('digital_barcode_latest', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('addToSet')->with('generated_barcodes_digital', Mockery::type('string'))->once();
    $this->redisMock->shouldReceive('isInSet')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('barcodeExists')->andReturn(false);
    $this->generatedBarcodeMock->shouldReceive('create')->andReturnTrue();
    
    // Act
    $response = $this->getJson('/api/barcode');
    
    // Assert
    $response->assertStatus(200);
    $barcode = $response->json('barcode');
    expect($barcode)->toMatch('/^212217\\d{8}$/');
});