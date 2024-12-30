<?php

use App\Http\Controllers\DuplicateChecker\DuplicateCheckerController;
use Illuminate\Routing\Route;
use App\Services\RedisService;
use App\Services\PatronDataTransformer;
use App\Services\AccuracyDataService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendWelcomeEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->transformer = Mockery::mock(PatronDataTransformer::class);
    $this->redisService = Mockery::mock(RedisService::class);
    $this->accuracyDataService = Mockery::mock(AccuracyDataService::class);
    
    $this->controller = new DuplicateCheckerController(
        $this->transformer,
        $this->redisService,
        $this->accuracyDataService
    );

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
// write a test that checks if the DuplicateCheckerController exists
it('checks if the DuplicateCheckerController exists', function () {
    $this->assertTrue(class_exists(DuplicateCheckerController::class));
});

// write a test that checks if the DuplicateCheckerController has a method called store
it('checks if the DuplicateCheckerController has a method called store', function () {
    $this->assertTrue(method_exists(DuplicateCheckerController::class, 'store'));
});

it('can get a bearer token using /oauth/token endpoint', function () {
    // Prepare your credentials
    $clientId = env('CLIENT_ID'); 
    $clientSecret = env('CLIENT_SECRET');
    $grantType = 'client_credentials';

    // Make the POST request to /oauth/token
    $response = $this->postJson('/oauth/token', [
        'grant_type' => $grantType,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]);

    // Assert that the response is successful
    $response->assertStatus(200);

    // Extract the token from the response
    $this->token = $response->json('access_token');

    // Assert that the token is returned and is not null
    expect($this->token)->not()->toBeNull();
});

// write a test that checks if the endpoint /duplicates exists
it('checks if the endpoint /duplicates exists', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token, 
      ])->postJson('/api/duplicates',[]);

    $response->assertStatus(422);
});

it('detects duplicate records', function () {
    $existingData = [
        [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'dateofbirth' => '1990-01-01',
            'address' => '123 Main St'
        ]
    ];
    
    $this->redisService
        ->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn(json_encode($existingData));

    $request = Request::create('/store', 'POST', [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'dateofbirth' => '1990-01-01',
        'address' => '123 Main St',
        'postalcode' => '12345',
        'city' => 'Test City',
        'barcode' => '123456789'
    ]);

    $response = $this->controller->store($request);
    
    expect($response->status())->toBe(409)
        ->and(json_decode($response->content(), true)['message'])
        ->toContain('Duplicate record found');
});

it('successfully stores new record', function () {
    // Fake the filesystem
    Storage::fake('local');

    // Create an empty json file that your controller expects
    Storage::put('test_duplicates.json', '[]');
     
    Mail::fake();
    $faker = Faker\Factory::create();

    $this->redisService
        ->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn('[]');

    $this->transformer
        ->shouldReceive('transform')
        ->andReturn(['transformed' => 'data']);

    Http::fake([
        config('cre.base_url_dev') . config('cre.endpoint') => Http::response(['sessionToken' => 'fake-token'], 200),
        config('cre.base_url_dev') . config('cre.patron_endpoint') => Http::response(['success' => true], 200),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token, 
        ])->postJson('/api/duplicates',[
            'firstname' => $faker->firstName,
            'lastname' => $faker->lastName,
            'middlename' => $faker->firstName,
            'email' => $faker->unique()->safeEmail,
            'phone' => $faker->numerify('##########'),
            'dateofbirth' => $faker->date('Y-m-d', '-18 years'), 
            'address' => $faker->streetAddress,
            'postalcode' => $faker->postcode,
            'city' => $faker->city,
            'province' => $faker->state,
            'barcode' => $faker->unique()->numerify('#########')
        ]);
    
    expect($response->status())->toBe(201);
    Mail::assertSent(SendWelcomeEmail::class);
    // Optional: Assert that the file was written to
    Storage::disk('local')->assertExists('test_duplicates.json');
});

it('handles ILS API failure', function () {
    // Mock Redis empty data
    $this->redisService->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn('[]');

    // Mock transformer
    $this->transformer->shouldReceive('transform')
        ->andReturn(['transformed' => 'data']);

    // Mock HTTP calls to fail
    Http::fake([
        config('cre.base_url_dev') . '*' => Http::response(['error' => 'API Error'], 500),
    ]);

    $request = new Request([
        'firstname' => 'Jane',
        'lastname' => 'Smith',
        'email' => 'jane@example.com',
        'phone' => '0987654321',
        'dateofbirth' => '1995-01-01',
        'address' => '456 Other St',
        'postalcode' => '54321',
        'city' => 'Test City',
        'barcode' => '987654321'
    ]);

    $response = $this->controller->store($request);
    
    expect($response->status())->toBe(500);
   
});

it('handles Redis data properly in lpass method', function () {
    $faker = Faker\Factory::create();
    $this->redisService->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn(json_encode([]));

    // Mock transformer
    $this->transformer->shouldReceive('transform')
        ->andReturn(['transformed' => 'data']);

    // Mock HTTP calls
    Http::fake([
        config('cre.base_url_dev') . config('cre.endpoint') => Http::response(['sessionToken' => 'fake-token'], 200),
        config('cre.base_url_dev') . config('cre.patron_endpoint') => Http::response(['success' => true], 200),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token, 
        ])->postJson('/api/lpass',[
            'firstname' => $faker->firstName,
            'lastname' => $faker->lastName,
            'middlename' => $faker->firstName,
            'email' => $faker->unique()->safeEmail,
            'phone' => $faker->numerify('##########'),
            'dateofbirth' => $faker->date('Y-m-d', '-18 years'), 
            'address' => $faker->streetAddress,
            'postalcode' => $faker->postcode,
            'city' => $faker->city,
            'province' => $faker->state,
            'barcode' => $faker->unique()->numerify('#########')
        ]);
    
    expect($response->status())->toBe(200);
    expect(json_decode($response->content(), true)['message'])
        ->toBe('New record created in Redis');
});