<?php

namespace Tests\Unit;

use App\Services\PatronDataTransformer;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->transformer = new PatronDataTransformer();
});

it('throws exception for invalid profile in OLR product', function () {
    $data = [
        'profile' => 'INVALID_PROFILE',
        'category1' => 'cat1',
        'category3' => 'cat3',
        'category4' => 'cat4',
        'category5' => 'cat5',
        'category6' => 'cat6',
    ];

    $product = 'OLR';

    expect(fn () => $this->transformer->transform($data, $product))->toThrow(\InvalidArgumentException::class, "Invalid profile value. Allowed values are: EPL_SELF, EPL_SELFJ");
});

it('correctly transforms valid data for OLR product', function () {
    $data = [
        'profile' => 'EPL_SELF',
        'category1' => 'cat1',
        'category3' => 'cat3',
        'category4' => 'cat4',
        'category5' => 'cat5',
        'category6' => 'cat6',
        'key' => '12345',
        'barcode' => '123456789',
        'lastname' => 'Doe',
        'firstname' => 'John',
        'middlename' => 'Michael',
        'library' => 'Library1',
        'password' => 'secret123',
        'expirydate' => '2025-12-31',
        'dateofbirth' => '1990-01-01',
        'city' => 'SampleCity',
        'province' => 'SampleProvince',
        'postalcode' => '12345',
        'phone' => '555-1234',
        'email' => 'john.doe@example.com',
        'careof' => 'CareOfName',
        'address' => '123 Sample St',
    ];

    $product = 'OLR';

    $result = $this->transformer->transform($data, $product);

    // Assert that transformed data contains the expected keys and values
    Assert::assertArrayHasKey('@resource', $result);
    Assert::assertArrayHasKey('@key', $result);
    Assert::assertEquals('/user/patron', $result['@resource']);
    Assert::assertEquals('123456789', $result['barcode']);
    Assert::assertEquals('Doe', $result['lastName']);
    Assert::assertEquals('John', $result['firstName']);
    Assert::assertEquals('Michael', $result['middleName']);
    Assert::assertArrayHasKey('category01', $result);
    Assert::assertArrayHasKey('category03', $result);
    Assert::assertArrayHasKey('category04', $result);
});

it('correctly transforms user data', function () {
    $input = [
        'firstName' => 'John',
        'lastName' => 'Doe',
        'middleName' => 'Michael',
        'birthDate' => '1990-01-01',
        'profile' => ['@key' => 'EPL_SELF'],
        'barcode' => '123456789',
    ];

    $result = $this->transformer->transformUserData($input);

    // Assert that transformed data is in the expected format
    Assert::assertCount(1, $result);
    Assert::assertArrayHasKey('firstname', $result[0]);
    Assert::assertEquals('John', $result[0]['firstname']);
    Assert::assertEquals('Doe', $result[0]['lastname']);
    Assert::assertEquals('Michael', $result[0]['middlename']);
    Assert::assertEquals('1990-01-01', $result[0]['dateofbirth']);
    Assert::assertEquals('EPL_SELF', $result[0]['profile']);
    Assert::assertEquals('123456789', $result[0]['barcode']);
});

it('correctly detects when user data has changed', function () {
    $existingData = [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '555-1234',
        'address' => '123 Sample St',
        'postalcode' => '12345',
        'city' => 'SampleCity',
    ];

    $newData = [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '555-1234',
        'address' => '123 Sample St',
        'postalcode' => '12345',
        'city' => 'NewCity', // City changed
    ];

    $hasChanged = $this->transformer->dataHasChanged($existingData, $newData);

    Assert::assertTrue($hasChanged);
});

it('correctly detects when user data has not changed', function () {
    $existingData = [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '555-1234',
        'address' => '123 Sample St',
        'postalcode' => '12345',
        'city' => 'SampleCity',
    ];

    $newData = [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '555-1234',
        'address' => '123 Sample St',
        'postalcode' => '12345',
        'city' => 'SampleCity', // No change
    ];

    $hasChanged = $this->transformer->dataHasChanged($existingData, $newData);

    Assert::assertFalse($hasChanged);
});
