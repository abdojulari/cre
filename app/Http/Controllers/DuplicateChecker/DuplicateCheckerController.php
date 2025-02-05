<?php

namespace App\Http\Controllers\DuplicateChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\RedisService;
use App\Services\PatronDataTransformer;
use App\Services\AccuracyDataService;
use App\Services\ExternalApiService;
use App\Services\DuplicateCheckerService;
use Illuminate\Support\Facades\Http;
use App\Mail\SendWelcomeEmail;
use Illuminate\Support\Facades\Mail;

class DuplicateCheckerController extends Controller
{
    protected $transformer;
    protected $redisService;
    protected $accuracyDataService;
    protected $externalApiService;
    protected $duplicateCheckerService;

    public function __construct(
        PatronDataTransformer $transformer, 
        RedisService $redisService, 
        AccuracyDataService $accuracyDataService,
        ExternalApiService $externalApiService,
        DuplicateCheckerService $duplicateCheckerService
    )
    {
        $this->transformer = $transformer;
        $this->redisService = $redisService;
        $this->accuracyDataService = $accuracyDataService;
        $this->externalApiService = $externalApiService;
        $this->duplicateCheckerService = $duplicateCheckerService;
    }
  
    public function store(Request $request) {
        $data = $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'middlename' => 'nullable|string',
            'dateofbirth' => 'required|date',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'postalcode' => 'required|string',
            'province' => 'nullable|string',
            'password' => 'nullable|string',
            'profile' => 'nullable|string',
            'city' => 'required|string',
            'barcode' => 'required|string',
            'library' => 'nullable|string',
            'careof' => 'nullable|string',
            'category1' => 'nullable|string',
            'category2' => 'nullable|string',
            'category3' => 'nullable|string',
            'category4' => 'nullable|string',
            'category5' => 'nullable|string',
            'category6' => 'nullable|string'
        ]);    
    
        $currentDate = new \DateTime();
        $currentDate->modify('+45 days');
        // Format the date if needed (e.g., 'Y-m-d' for '2024-03-01')
        $expiryDate = $currentDate->format('Y-m-d');
        $data['expirydate'] = $expiryDate;

        // Check if the record already exists
        $path = storage_path('app/duplicates.json');
        $duplicates = json_decode(file_get_contents($path), true) ?? [];
        // Get and decode Redis data if necessary
        $redisData = $this->redisService->get('cre_registration_record');
        // Check if $redisData is already an array, otherwise decode it if it's a string
        if (is_string($redisData)) {
            $redis = json_decode($redisData, true) ?? [];
        } elseif (is_array($redisData)) {
            $redis = $redisData;
        } else {
            $redis = [];
        }
        // Fuzzy logic check
        $duplicate = $this->duplicateCheckerService->retrieveDuplicateUsingCache($data, $redis);
        if ($duplicate) {
            Log::channel('slack')->alert('Duplicate record found with fuzzy logic', [
                'duplicate' => $duplicate,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            return response()->json([
                'message' => 'Duplicate record found with fuzzy logic.',
                'duplicate' => $duplicate
            ], 409);
        }
    
        $transformedData = $this->transformer->transform($data, 'OLR');
       
        // Wrap the ILS post call in try-catch block to handle errors
        try {
            $response = $this->externalApiService->postToILS($transformedData);
        
            if (!$response) {
                // If postToILS returns null or a failure response, we handle it
                return response()->json(['message' => 'Error posting to ILS API'], 500);
            }
            // If ILS API call was successful, proceed to update Redis
            $duplicates[] = $data;
            file_put_contents($path, json_encode($duplicates));

            // Update Redis cache after adding the new record
            $this->redisService->set('cre_registration_record', $duplicates);

            // Send welcome email
            $this->sendWelcomeEmail($data);

            Log::info('patron', $transformedData);
            return response()->json(['message' => 'Record added successfully.', 'data' => $transformedData], 201);
        } catch (\Exception $e) {
            // If there's an error with the ILS API call, handle the exception and prevent Redis write
            Log::error('Error posting to ILS API: ' . $e->getMessage());
            Log::channel('slack')->error('Error posting to ILS API', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
            return response()->json(['message' => 'Error posting to ILS API'], 500);
        }

    }

    public function lpass(Request $request) {
        // Validate incoming request data
        $data = $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'middlename' => 'nullable|string',
            'dateofbirth' => 'required|date',
            'email' => 'required|email',
            'phone' => 'required|string',
            'country' => 'required|string',
            'address' => 'required|string',
            'postalcode' => 'required|string',
            'province' => 'nullable|string',
            'password' => 'nullable|string',
            'profile' => 'nullable|string',
            'city' => 'required|string',
            'expirydate' => 'required|date',
            'barcode' => 'required|string',
            'library' => 'nullable|string',
            'careof' => 'nullable|string',
            'category1' => 'nullable|string',
            'category2' => 'nullable|string',
            'category3' => 'nullable|string',
            'category4' => 'nullable|string',
            'category5' => 'nullable|string',
            'category6' => 'nullable|string',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
            'branch' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
    
        // Add timestamps
        $data['createdAt'] = now()->toDateTimeString();
        $data['modifiedAt'] = now()->toDateTimeString();
        $data['key'] = $this->externalApiService->retrieveILSData($data)['@key'] ?? null;
    
        try {
            // Get Redis data and handle the case where it might be null
            $ilsData = $this->externalApiService->retrieveILSData($data);
            // Initialize transformed data as an empty array to handle null case
            $transformedData = [];
            // If ILS data exists, transform it
            if ($ilsData !== null) {
                $clean = json_encode($this->transformer->transformUserData($ilsData), JSON_PRETTY_PRINT);
                // Initialize the array of records
                $dataFromILS = [];
    
                // If Redis data exists and is already an array, use it directly
                if ($clean !== null) {
                    if (is_array($clean)) {
                        $dataFromILS = $clean;
                    } elseif (is_string($clean)) {
                        $dataFromILS = json_decode($clean, true) ?? [];
                    }
                }
    
                // Initialize flags for checking existence
                $recordExists = false;
                $existingData = null;
    
                // Check if the ILS data exists (not null)
                if ($ilsData !== null) {
                    // Loop through the existing records and check for matching barcode and dateofbirth
                    foreach ($dataFromILS as $record) {
                        if ($record['barcode'] === $data['barcode'] && $record['dateofbirth'] === $data['dateofbirth']) {
                            $recordExists = true;
                            $existingData = $record;
                            break;
                        }
                    }
    
                    if ($recordExists) {
                        // If record exists, compare it with the new data
                        if ($this->transformer->dataHasChanged($existingData, $data)) {
                            // Update the record in the existing data array
                            foreach ($dataFromILS as &$record) {
                                if ($record['barcode'] === $data['barcode'] && $record['dateofbirth'] === $data['dateofbirth']) {
                                    $record = $data;
                                    break;
                                }
                            }
    
                            try {
                                $transformedData = $this->transformer->transform($data, 'LPASS');
                                $response = $this->externalApiService->updateToILS($transformedData);
                                if (!$response) {
                                    return response()->json(['message' => 'Error updating ILS record'], 500);
                                }
                                // Always encode as JSON before saving
                                Log::info('Updated record in ILS:', ['updatedData' => $data]);
                                return response()->json(['message' => 'Record updated to ILS']);
                            } catch (\Exception $e) {
                                Log::error('Error updating ILS: ' . $e->getMessage());
                                Log::channel('slack')->error('Error updating ILS - LPASS', [
                                    'Erro Message' => $e->getMessage(),
                                    'ip' => request()->ip(),
                                    'trace' => $e->getTraceAsString(),
                                    'user_agent' => request()->userAgent()
                                ]);
                                return response()->json(['message' => 'Error updating ILS'], 500);
                            }
                        } else {
                            Log::info('No changes detected for record:', ['data' => $data]);
                            return response()->json(['message' => 'No changes detected. Record unchanged']);
                        }
                    }
                }
            }

            if ($ilsData === null) {
                // Transform and post new data when ILS data is null (i.e., it doesn't exist)
                $transformedData = $this->transformer->transform($data, 'LPASS');
                $response = $this->externalApiService->postToILS($transformedData);
    
                if (!$response) {
                    return response()->json(['message' => 'Error posting to ILS API'], 500);
                }
    
                Log::info('New record added to Redis:', ['newData' => $data]);
                return response()->json(['message' => 'New record created in Redis']);
            }
    
        } catch (\Exception $e) {
            Log::error('Redis operation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Log::channel('slack')->error('Redis operation failed - LPASS', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
                'trace' => $e->getTraceAsString(),
                'user_agent' => request()->userAgent()
            ]);
            return response()->json(['error' => 'Failed to process request'], 500);
        }
    }
    
    // method to send welcome email used in the store method
    private function sendWelcomeEmail($data) {
        $currentDate = new \DateTime();

        // Add 45 days to the current date
        $currentDate->modify('+45 days');

        // Format the date if needed (e.g., 'Y-m-d' for '2024-03-01')
        $expiryDate = $currentDate->format('Y-m-d');

        try {
            Mail::to($data['email'])->send(new SendWelcomeEmail(
                $data['firstname'],
                $data['lastname'],
                $data['barcode'],
                $expiryDate
            ));
        } catch (\Exception $e) {
            Log::error('Error sending welcome email: ' . $e->getMessage());
            Log::channel('slack')->error('Error sending welcome email', [
                'error' => $e->getMessage(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }
    
    // Confusion matrix for accuracy 
    public function evaluateDuplicates()
    {
        $testData =  $this->accuracyDataService->generateTestData(); //$this->generateTestData(); // Generate or fetch your test data
        $confusionMatrix = [
            'TP' => 0, // True Positives
            'FP' => 0, // False Positives
            'FN' => 0, // False Negatives
            'TN' => 0, // True Negatives
        ];

        foreach ($testData as $pair) {
            $record1 = $pair[0];
            $record2 = $pair[1];

            // Assume that the second item in the pair is the expected result for duplication
            $isDuplicate = $this->duplicateCheckerService->isDuplicate($record1, $record2);
            $expectedDuplicate = $pair['expected_duplicate']; // Should be either true or false
    
            // Compare the actual result with the expected result and increment the confusion matrix
            if ($isDuplicate && $expectedDuplicate) {
                $confusionMatrix['TP']++;
            } elseif (!$isDuplicate && !$expectedDuplicate) {
                $confusionMatrix['TN']++;
            } elseif ($isDuplicate && !$expectedDuplicate) {
                $confusionMatrix['FP']++;
            } elseif (!$isDuplicate && $expectedDuplicate) {
                $confusionMatrix['FN']++;
            }
        }

        // Calculate accuracy or other metrics
        $accuracy = (($confusionMatrix['TP'] + $confusionMatrix['TN']) / array_sum($confusionMatrix)) * 100;
        // Format the accuracy to 2 decimal places
        $accuracyFormatted = number_format($accuracy, 2);
        return response()->json([
            'confusion_matrix' => $confusionMatrix,
            'accuracy' => $accuracyFormatted. '%'
        ]);
    }
        
}
