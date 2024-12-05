<?php

namespace App\Http\Controllers\DuplicateChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\RedisService;
use App\Services\PatronDataTransformer;
use Illuminate\Support\Facades\Http;
use App\Mail\SendWelcomeEmail;
use Illuminate\Support\Facades\Mail;


class DuplicateCheckerController extends Controller
{
    protected $transformer;
    protected $redisService;

    public function __construct(PatronDataTransformer $transformer, RedisService $redisService)
    {
        $this->transformer = $transformer;
        $this->redisService = $redisService;
    }
  
    public function store(Request $request) {
        $data = $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
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
    
        // Check if the record already exists
        $path = storage_path('app/duplicates.json');
        $duplicates = json_decode(file_get_contents($path), true) ?? [];
        $redis = $this->redisService->get('cre_registration_record') ?? [];
        // Fuzzy logic check
        $duplicate = $this->retrieveDuplicateUsingCache($data, $redis);
        if ($duplicate) {
            return response()->json([
                'message' => 'Duplicate record found with fuzzy logic.',
                'duplicate' => $duplicate
            ], 409);
        }
    
        // Add the record to the duplicates file 
        //TODO: replace this with the ils
        $duplicates[] = $data;
        file_put_contents($path, json_encode($duplicates));
        // Transform and send the data to the API
        $transformedData = $this->transformer->transform($data);
        $response = $this->postToILS($transformedData);
        
        // Update Redis cache after adding new record  
        $this->redisService->set('cre_registration_record', $duplicates);
        // Send welcome email
        $this->sendWelcomeEmail($data);
        Log::info('patron', $transformedData);
        return response()->json(['message' => 'Record added successfully.','data' => $transformedData ], 201);
    }
     
    private function retrieveDuplicateUsingCache($data, $redis) {
        // Retrieve duplicates from Redis using Predis
        foreach ($redis as $duplicate) {
            if ($this->isDuplicate($duplicate, $data)) {
                return $duplicate; // Return the duplicate record details
            }
        }
        return null;
    }

    private function isDuplicate($record1, $record2) {
        // Define a similarity threshold
        $threshold = 80;

        // Compare multiple fields
        $similarities = [
            $this->similarity($record1['firstname'], $record2['firstname']),
            $this->similarity($record1['lastname'], $record2['lastname']),
            $this->similarity($record1['email'], $record2['email']),
            $this->similarity($record1['phone'], $record2['phone']),
            $this->similarity($record1['address'], $record2['address']),
            $this->similarity($record1['dateofbirth'], $record2['dateofbirth']),
            $this->similarity($record1['postalcode'], $record2['postalcode']),
            $this->similarity($record1['city'], $record2['city']),
        ];

        // Check if the average similarity exceeds the threshold
        $averageSimilarity = array_sum($similarities) / count($similarities);
    
        return $averageSimilarity > $threshold;
    }

    private function similarity($str1, $str2) {
        // Use Levenshtein distance or any other string similarity metric
        // The Levenshtein distance is a string metric for measuring the difference between two sequences
        $levenshtein = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        // Calculate similarity percentage
        if ($maxLen == 0) {
            return 100;
        }

        return (1 - $levenshtein / $maxLen) * 100;
    }

    private function getSessionToken() {
        $url = config('cre.base_url_dev') . config('cre.endpoint');
        $body = [
            'login' => config('cre.symws_user'),
            'password' => config('cre.symws_pass')
        ];
      
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-sirs-clientID' => config('cre.symws_client_id'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post($url, $body);
        if ($response->successful()) {
            return $response->json()['sessionToken'];
        }
        return null;
    }

    private function postToILS($data) {
        //Call the ILS API endpoint to save the data
        $sessionToken = $this->getSessionToken();
        $url = config('cre.base_url_dev') . config('cre.patron_endpoint');
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.sirsidynix.roa.resource.v2+json',
            'Content-Type' => 'application/vnd.sirsidynix.roa.resource.v2+json',
            'sd-originating-app-id' => config('cre.apps_id'),
            'x-sirs-clientID' => config('cre.symws_client_id'),
            'x-sirs-sessionToken' => $sessionToken,
            'SD-Prompt-Return' => 'USER_PRIVILEGE_OVRCD/Y'
        ])->post($url, $data);
        if ($response->successful()) {
            Log::info('ILS API response', $response->json());
            return $response->json();
        }
        Log::info('FAILURE', $response->json());
        return response()->json(['message' => 'Error posting to ILS API'], 500);
    }
    // methid to send welcome email used in the store method
    private function sendWelcomeEmail($data) {
        $currentDate = new \DateTime();

        // Add 3 months to the current date
        $currentDate->modify('+3 months');

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
        }
    }


    // method to update the record
    public function lpass(Request $request) {
        // Validate incoming request data
        $data = $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'dateofbirth' => 'required|date',
            'email' => 'required|email',
            'phone' => 'required|string',
            'address' => 'required|string',
            'postalcode' => 'required|string',
            'city' => 'required|string',
            'barcode' => 'required|string',
            'careof' => 'nullable|string',
            'category1' => 'nullable|string',
            'category2' => 'nullable|string',
            'category3' => 'nullable|string',
            'category4' => 'nullable|string',
            'category5' => 'nullable|string',
            'category6' => 'nullable|string',
            'createdAt' => 'required|date',
            'modifiedAt' => 'required|date'
        ]);
    
        // Connect to Redis
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => 'localhost',  // Update with the actual Redis server host
            'port'   => 6379,
            'database' => 0
        ]);
    
        // Fetch all records from Redis
        $dataFromRedis = json_decode($redis->get('cre_registration_record'), true) ?? [];
    
        // Initialize flags for checking existence
        $recordExists = false;
        $existingData = null;
    
        // Loop through the existing records and check for matching barcode and dateofbirth
        foreach ($dataFromRedis as $record) {
            if ($record['barcode'] === $data['barcode'] && $record['dateofbirth'] === $data['dateofbirth']) {
                // Record found, so we can check if it needs to be updated
                $recordExists = true;
                $existingData = $record;
                break;  // No need to check further once we've found the record
            }
        }
    
        if ($recordExists) {
            // If record exists, compare it with the new data
            if ($this->dataHasChanged($existingData, $data)) {
                // Update the record in the existing data array
                foreach ($dataFromRedis as &$record) {
                    if ($record['barcode'] === $data['barcode'] && $record['dateofbirth'] === $data['dateofbirth']) {
                        $record = $data; // Update the record with new data
                        break;
                    }
                }
    
                // Save the updated data back to Redis
                $redis->set('cre_registration_record', json_encode($dataFromRedis));
                // TODO: then call the ILS API endpoint to UPDATE the data to the database
                return response()->json(['message' => 'Record updated in Redis']);
            } else {
                // No change detected
                return response()->json(['message' => 'No changes detected. Record unchanged']);
            }
        } else {
            // If no matching record, insert the new record
            $dataFromRedis[] = $data; // Append the new record to the array
            // Save the updated data back to Redis
            $redis->set('cre_registration_record', json_encode($dataFromRedis));

            //TODO: then call the ILS API endpoint to save the data to the database
            return response()->json(['message' => 'New record created in Redis']);
        }
    }
    
    // Helper function to compare existing and new data
    private function dataHasChanged($existingData, $newData) {
        // Compare the relevant fields between existing and new data
        return $existingData['firstname'] !== $newData['firstname'] ||
               $existingData['lastname'] !== $newData['lastname'] ||
               $existingData['email'] !== $newData['email'] ||
               $existingData['phone'] !== $newData['phone'] ||
               $existingData['address'] !== $newData['address'] ||
               $existingData['postalcode'] !== $newData['postalcode'] ||
               $existingData['city'] !== $newData['city'] ||
               $existingData['careof'] !== $newData['careof'] ||
               $existingData['category1'] !== $newData['category1'] ||
               $existingData['category2'] !== $newData['category2'] ||
               $existingData['category3'] !== $newData['category3'] ||
               $existingData['category4'] !== $newData['category4'] ||
               $existingData['category5'] !== $newData['category5'] ||
               $existingData['category6'] !== $newData['category6'] ||
               $existingData['createdAt'] !== $newData['createdAt'] ||
               $existingData['modifiedAt'] !== $newData['modifiedAt'];
    }

    // update request to ILS API
    private function updateToILS($data) {
        try {
            $response = Http::withHeaders([])
                    ->put($url, $data);
                    if ($response->successful()) {
                        return $response->json();
                    }
        } catch (\Exception $e) {
            Log::error('Error updating to ILS API: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating to ILS API'], 500);
        }
    }
                
}
