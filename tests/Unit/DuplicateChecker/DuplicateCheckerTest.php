<?php

use App\Http\Controllers\DuplicateChecker\DuplicateCheckerController;
use Illuminate\Routing\Route;
use App\Services\RedisService;
use App\Services\PatronDataTransformer;
use App\Services\AccuracyDataService;
use Illuminate\Support\Facades\Redis;
use App\Services\ExternalApiService;
use App\Services\DuplicateCheckerService;
use App\Services\BarcodeGeneratorService;
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
    $this->externalApiService = Mockery::mock(ExternalApiService::class);
    $this->duplicateCheckerService = Mockery::mock(DuplicateCheckerService::class);
    $this->barcodeGeneratorService = Mockery::mock(BarcodeGeneratorService::class);
    
    $this->controller = new DuplicateCheckerController(
        $this->transformer,
        $this->redisService,
        $this->accuracyDataService,
        $this->externalApiService,
        $this->duplicateCheckerService,
        $this->barcodeGeneratorService
    );

    // Obtain the token once before the tests run
    $clientId = env('CLIENT_ID');
    $clientSecret = env('CLIENT_SECRET');
   
    $response = $this->postJson('/oauth/token', [
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret
    ]);
 
    $this->token = $response->json('access_token');
      
    $auth = $this->postJson('/api/login',[
        'email' => 'cre_test@example.com',
        'password' => 'sample_test2025@january'
    ]);
 
   // dd($auth->json('sanctum_token'));

   $this->authToken = $auth->json('sanctum_token');
});

afterEach(function () {
    Mockery::close();
});

it('checks if the DuplicateCheckerController exists', function () {
    $this->assertTrue(class_exists(DuplicateCheckerController::class));
});

it('checks if the DuplicateCheckerController has a method called store', function () {
    $this->assertTrue(method_exists(DuplicateCheckerController::class, 'store'));
});

it('can get a bearer token using /oauth/token endpoint', function () {
    $clientId = env('CLIENT_ID'); 
    $clientSecret = env('CLIENT_SECRET');
    $grantType = 'client_credentials';

    $response = $this->postJson('/oauth/token', [
        'grant_type' => $grantType,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]);

    $response->assertStatus(200);
    $this->token = $response->json('access_token');
    expect($this->token)->not()->toBeNull();
});

it('checks if the endpoint /duplicates exists', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token,
        'X-Sanctum-Token' => $this->authToken
      ])->postJson('/api/duplicates',[]);

    $response->assertStatus(422);
});

it('successfully stores new record', function () {
    Storage::fake('local');
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

    $this->duplicateCheckerService
        ->shouldReceive('retrieveDuplicateUsingCache')
        ->andReturn(false);

    $this->barcodeGeneratorService
        ->shouldReceive('generate')
        ->andReturn(response()->json(['barcode' => '123456789']));

    Http::fake([
        config('cre.ils_base_url') . config('cre.endpoint') => Http::response(['sessionToken' => 'fake-token'], 200),
        config('cre.ils_base_url') . config('cre.patron_endpoint') => Http::response(['success' => true], 200),
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token,
        'X-Sanctum-Token' => $this->authToken, 
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
            'barcode' => $faker->unique()->numerify('#########'),
            'profile' => 'EPL_SELF'
        ]);

    expect($response->status())->toBe(201);
    Mail::assertSent(SendWelcomeEmail::class);
    Storage::disk('local')->assertExists('test_duplicates.json');
});

it('handles ILS API failure', function () {
    $this->redisService->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn('[]');

    $this->transformer->shouldReceive('transform')
        ->andReturn(['transformed' => 'data']);

    $this->duplicateCheckerService
        ->shouldReceive('retrieveDuplicateUsingCache')
        ->andReturn(false);

    $this->barcodeGeneratorService
        ->shouldReceive('generate')
        ->andReturn(response()->json(['barcode' => '123456789']));

    Http::fake([
        config('cre.ils_base_url') . '*' => Http::response(['error' => 'API Error'], 500),
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

it('detects duplicate record', function () {
    Storage::fake('local');
    Storage::put('test_duplicates.json', '[]');
    Mail::fake();
    $faker = Faker\Factory::create();

    $this->redisService
        ->shouldReceive('get')
        ->with('cre_registration_record')
        ->andReturn('[]');

    $this->duplicateCheckerService
        ->shouldReceive('retrieveDuplicateUsingCache')
        ->andReturn(true);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token,
        'X-Sanctum-Token' => $this->authToken 
        ])->postJson('/api/duplicates',[
            'firstname' => 'Hunkend',
            'lastname' => 'Doacche',
            'dateofbirth' => '2089-05-14',
            'email' => 'hunkend@example.com',
            'phone' => '7806455102',
            'address' => '123 Stubborn Goat St',
            'postalcode' => '980931',
            'city' => 'Edmonton',
            'barcode' => '22211099393'
        ]);
    
    expect($response->status())->toBe(409)
        ->and(json_decode($response->content(), true)['message'])
        ->toContain('Duplicate record found');
    Storage::disk('local')->assertExists('test_duplicates.json');
});

it('handles invalid input gracefully', function () {
    //dd($this->authToken);
    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$this->token,
        'X-Sanctum-Token' => 'Bearer '.$this->authToken
    ])->postJson('/api/duplicates', [
        'firstname' => '',
        'lastname' => '',
        'dateofbirth' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
        'postalcode' => '',
        'city' => '',
        'barcode' => ''
    ]);

    $response->assertStatus(422); 
    expect($response->json('errors'))->not()->toBeEmpty();
});

it('can evaluate duplicates accuracy', function () {
    // Arrange: Prepare test data
    $testData = [
        [
            ['firstname' => 'John', 'lastname' => 'Doe'],
            ['firstname' => 'John', 'lastname' => 'Doe'],
            'expected_duplicate' => true
        ]
    ];

    // Mock the service methods that are used inside the controller
    $this->accuracyDataService
        ->shouldReceive('generateTestData')
        ->andReturn($testData);

    $this->duplicateCheckerService
        ->shouldReceive('isDuplicate')
        ->andReturn(true); // Simulate that a duplicate is detected

    // Act: Call the evaluateDuplicates method on the controller
    $response = $this->controller->evaluateDuplicates();

    // Assert: Check the response from the controller
    $responseData = $response->getData(); // Get the response data

    // Verify the response content and keys
    expect($responseData)->toBeObject() // Ensure the response is an object
        ->and($responseData->confusion_matrix)->not()->toBeNull() // Check that 'confusion_matrix' key exists
        ->and($responseData->accuracy)->not()->toBeNull(); // Check that 'accuracy' key exists
});


it('can collect statistics data using setDataForStatistics method', function () {
    // Arrange: Prepare the data that will be passed to the method
    $statsData = [
        'utm_source' => 'test',
        'utm_medium' => 'email',
        'utm_campaign' => 'winter2024',
        'event_category' => 'registration',
        'postal_code' => 'T6G2R3'
    ];

    // Mock Redis service responses
    $this->redisService
        ->shouldReceive('get')
        ->with('statistics_data')
        ->andReturn('[]'); // Simulate that Redis contains no data initially

    $this->redisService
        ->shouldReceive('set')
        ->with('statistics_data', Mockery::any()) // Verify that 'set' is called with some data
        ->andReturn(true); // Simulate that Redis set was successful

    // Mock other dependencies if necessary (like the transformer or external services)
    $this->transformer
        ->shouldReceive('transform')
        ->andReturn(['transformed' => 'data']); // Simulate a successful transformation

    // Mock the log behavior (if necessary)
    Log::shouldReceive('info')
        ->once()
        ->with('Data for statistics:', Mockery::any()); // Log call verification

    // Act: Create a controller instance with mocked dependencies
    $controller = new DuplicateCheckerController(
        $this->transformer,
        $this->redisService,
        $this->accuracyDataService,
        $this->externalApiService,
        $this->duplicateCheckerService,
        $this->barcodeGeneratorService
    );

    // Simulate the request to pass in the data
    $request = new Request($statsData); // Simulate the request with data

    // Call the method directly on the controller
    $response = $controller->setDataForStatistics($request);

    // Assert: Check the response from the method
    $responseData = $response->getData(); // This will give the response data

    // Verify the response
    expect($responseData)->toBeObject() // Check that the response data is an object
        ->and($responseData->message)->toBe('Data received for statistics'); // Check the message key in the response

    // Verify Redis 'set' method was called
    $this->redisService->shouldHaveReceived('set')
        ->with('statistics_data', Mockery::any()); // Ensure that 'set' was called with appropriate data
});

it('can export statistics data', function () {
    // Mock the service methods that are used inside the controller
    $this->redisService
    ->shouldReceive('get')
    ->with('statistics_data')
    ->andReturn('[]');

    $data = json_encode([
        'utm_source' => 'test',
        'utm_medium' => 'email',
        'utm_campaign' => 'winter2024',
        'event_category' => 'registration',
        'postal_code' => 'T6G 2R3'
    ]);
    $response = $this->getJson('/api/export-statistics');
    $response->assertStatus(200);

});
