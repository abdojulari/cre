<?php

namespace App\Http\Controllers\DuplicateChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\RedisService;
use App\Services\PatronDataTransformer;
use App\Services\AccuracyDataService;
use Illuminate\Support\Facades\Http;
use App\Mail\SendWelcomeEmail;
use Illuminate\Support\Facades\Mail;

class DuplicateCheckerController extends Controller
{
    protected $transformer;
    protected $redisService;
    protected $accuracyDataService;

    public function __construct(PatronDataTransformer $transformer, RedisService $redisService, AccuracyDataService $accuracyDataService)
    {
        $this->transformer = $transformer;
        $this->redisService = $redisService;
        $this->accuracyDataService = $accuracyDataService;
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
        $duplicate = $this->retrieveDuplicateUsingCache($data, $redis);
        if ($duplicate) {
            return response()->json([
                'message' => 'Duplicate record found with fuzzy logic.',
                'duplicate' => $duplicate
            ], 409);
        }
    
        $transformedData = $this->transformer->transform($data);
       
        // Wrap the ILS post call in try-catch block to handle errors
        try {
            $response = $this->postToILS($transformedData);
            
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
            return response()->json(['message' => 'Error posting to ILS API'], 500);
        }

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

    // private function isDuplicate($record1, $record2) {
    //     // Age calculation to determine if the incoming record is a minor
    //     $dob1 = new \DateTime($record1['dateofbirth']);
    //     $dob2 = new \DateTime($record2['dateofbirth']);
    //     $age1 = $dob1->diff(new \DateTime())->y;
    //     $age2 = $dob2->diff(new \DateTime())->y;

    //     $isMinor1 = $age1 < 18;
    //     $isMinor2 = $age2 < 18;
    //     // Define the weights for each field
    //     $fieldWeights = [
    //         'firstname' => 1.0,
    //         'lastname' => 1.0,
    //         'phone' => 3.0,  // Phone should be more strictly matched
    //         'email' => 3.0,  // Email should be more strictly matched
    //         'dateofbirth' => 1.0,  // Higher tolerance for DOB (since it's standardized)
    //         'address' => 3.0,  // Address is somewhat flexible
    //     ];
    
    //     // If the incoming record is a minor, reduce the strictness for phone and email matching
    //     if ($isMinor1 && $isMinor2) {
    //         // Minor record: Allow same phone/email, reduce their weight
    //         Log::info('Minor record');
    //         if (
    //             isset($record1['lastname']) && isset($record2['lastname']) 
    //             && strtolower($record1['lastname']) === strtolower($record2['lastname'])
    //             && strtolower($record1['phone']) === strtolower($record2['phone'])
    //             && strtolower($record1['email']) === strtolower($record2['email'])
    //             ) {
    //                 $fieldWeights['phone'] = 1.0;
    //                 $fieldWeights['email'] = 1.0;
    //                 $fieldWeights['address'] = 1.0;

    //         }
           
    //     }  // If one record is a minor, allow relaxed matching only if the last names match
    //     elseif ($isMinor1 && !$isMinor2) {
    //         if (
    //             isset($record1['lastname']) && isset($record2['lastname']) 
    //             && strtolower($record1['lastname']) === strtolower($record2['lastname'])) {
    //             // If last names match, allow relaxed matching for phone/email
    //             $fieldWeights['phone'] = 1.0;
    //             $fieldWeights['email'] = 1.0;
    //             $fieldWeights['address'] = 1.0;
    //         } else {
    //             // If last names don't match, enforce strict matching for phone/email
    //             $fieldWeights['phone'] = 3.0;
    //             $fieldWeights['email'] = 3.0;
    //         }
    //     }
    //     elseif (!$isMinor1 && $isMinor2) {
    //         if (isset($record1['lastname']) && isset($record2['lastname']) && strtolower($record1['lastname']) === strtolower($record2['lastname'])) {
    //             // If last names match, allow relaxed matching for phone/email
    //             $fieldWeights['phone'] = 1.0;
    //             $fieldWeights['email'] = 1.0;
    //             $fieldWeights['address'] = 1.0;
    //         } else {
    //             // If last names don't match, enforce strict matching for phone/email
    //             $fieldWeights['phone'] = 3.0;
    //             $fieldWeights['email'] = 3.0;
    //         }
    //     }

    //     // If both records are adults, phone and email must be unique
    //     if (!$isMinor1 && !$isMinor2) {
    //         // Adults cannot have the same phone/email, enforce strict matching
    //         $fieldWeights['phone'] = 3.0;  // Strict phone matching
    //         $fieldWeights['email'] = 3.0;  // Strict email matching
    //     }
    //     $totalSimilarityScore = 0;
    //     $totalWeight = 0;
    
    //     // Compare each field with the appropriate weight
    //     foreach ($fieldWeights as $field => $weight) {
    //         // Use similarity function for each field
    //         $similarity = $this->similarity($record1[$field] ?? '', $record2[$field] ?? '', $weight);
            
    //         // Accumulate the weighted similarity score
    //         $totalSimilarityScore += $similarity;
    //         $totalWeight += $weight;
    //     }
    
    //     // Normalize the total score by the total weight
    //     $averageSimilarity = $totalSimilarityScore / $totalWeight;
    //     Log::info('Average similarity: ' . $averageSimilarity);
    //     return $averageSimilarity > 75;  // Adjust threshold as needed
    // }
    private function isDuplicate($record1, $record2) {
        // Age calculation
        $dob1 = new \DateTime($record1['dateofbirth']);
        $dob2 = new \DateTime($record2['dateofbirth']);
        $age1 = $dob1->diff(new \DateTime())->y;
        $age2 = $dob2->diff(new \DateTime())->y;
    
        $isMinor1 = $age1 < 18;
        $isMinor2 = $age2 < 18;
    
        // If they share the same lastname
        if (strtolower($record1['lastname']) === strtolower($record2['lastname'])) {
            // Case 1: Both are minors with same lastname
            if ($isMinor1 && $isMinor2) {
                // Only compare firstname and DOB
                $fieldWeights = [
                    'firstname' => 1.0,
                    'dateofbirth' => 1.0
                ];
            }
            // Case 2: One is minor, one is adult (potential parent-child)
            else if ($isMinor1 xor $isMinor2) {
                // Allow shared contact details for parent-child
                return false;  // Not a duplicate
            }
            // Case 3: Both are adults with same lastname
            else {
                $fieldWeights = [
                    'firstname' => 1.0,
                    'lastname' => 1.0,
                    'phone' => 3.0,
                    'email' => 3.0,
                    'dateofbirth' => 1.0,
                    'address' => 3.0
                ];
            }
        }
        // Different lastnames
        else {
            // For different lastnames, require unique contact details
            if (
                strtolower($record1['email']) === strtolower($record2['email']) ||
                strtolower($record1['phone']) === strtolower($record2['phone'])
            ) {
                return true; // Consider it a duplicate if contact details match
            }
            
            $fieldWeights = [
                'firstname' => 1.0,
                'lastname' => 1.0,
                'dateofbirth' => 1.0,
                'address' => 1.0
            ];
        }
    
        $totalSimilarityScore = 0;
        $totalWeight = 0;
    
        foreach ($fieldWeights as $field => $weight) {
            if (!isset($record1[$field]) || !isset($record2[$field])) {
                continue;
            }
            $similarity = $this->similarity($record1[$field], $record2[$field], $weight);
            $totalSimilarityScore += $similarity;
            $totalWeight += $weight;
        }
    
        $averageSimilarity = $totalWeight > 0 ? $totalSimilarityScore / $totalWeight : 0;
        
        return $averageSimilarity > 75;
    }

    private function similarity($str1, $str2, $weight = 1) {
        // Calculate Levenshtein distance
        $levenshtein = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        
        // If maxLen is 0, return 100 similarity (i.e., same string)
        if ($maxLen == 0) {
            return 100 * $weight;
        }
    
        // Calculate normalized similarity score
        $normalizedSimilarity = (1 - $levenshtein / $maxLen) * 100;
        
        // Apply the weight to the similarity score
        return $normalizedSimilarity * $weight;
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

    // update request to ILS API
    private function updateToILS($data) {
        $sessionToken = $this->getSessionToken();
        $url = config('cre.base_url_dev') . config('cre.patron_endpoint') .'/key/'.$data['barcode'];
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'Content-Type' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'sd-originating-app-id' => config('cre.apps_id'),
                'x-sirs-clientID' => config('cre.symws_client_id'),
                'x-sirs-sessionToken' => $sessionToken,
                'SD-Prompt-Return' => 'USER_PRIVILEGE_OVRCD/Y'
            ])->put($url, $data);
                    if ($response->successful()) {
                        return $response->json();
                    }
        } catch (\Exception $e) {
            Log::error('Error updating to ILS API: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating to ILS API'], 500);
        }
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

    public function lpass(Request $request) {
        // Validate incoming request data
        $data = $request->validate([
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'middlename' => 'nullable|string',
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
            'category6' => 'nullable|string'
        ]);
    
        // Add timestamps
        $data['createdAt'] = now()->toDateTimeString();
        $data['modifiedAt'] = now()->toDateTimeString();
    
        try {
            // Get Redis data and handle the case where it might be null
            $redisData = $this->redisService->get('cre_registration_record');

            // Initialize the array of records
            $dataFromRedis = [];
            
            // If Redis data exists and is already an array, use it directly
            if ($redisData !== null) {
                if (is_array($redisData)) {
                    $dataFromRedis = $redisData;
                } elseif (is_string($redisData)) {
                    $dataFromRedis = json_decode($redisData, true) ?? [];
                }
            }
    
            // Initialize flags for checking existence
            $recordExists = false;
            $existingData = null;
    
            // Loop through the existing records and check for matching barcode and dateofbirth
            foreach ($dataFromRedis as $record) {
                if ($record['barcode'] === $data['barcode'] && $record['dateofbirth'] === $data['dateofbirth']) {
                    $recordExists = true;
                    $existingData = $record;
                    break;
                }
            }
    
            if ($recordExists) {
                // If record exists, compare it with the new data
                if ($this->dataHasChanged($existingData, $data)) {
                    // Update the record in the existing data array
                    foreach ($dataFromRedis as &$record) {
                        if ($record['barcode'] === $data['barcode'] && $record['dateofbirth'] === $data['dateofbirth']) {
                            $record = $data;
                            break;
                        }
                    }
    
                    // Always encode as JSON before saving to Redis
                    $this->redisService->set('cre_registration_record', json_encode($dataFromRedis));
                    $transformedData = $this->transformer->transform($dataFromRedis);
                    Log::info('Updated record in Redis:', ['updatedData' => $data]);
                    return response()->json(['message' => 'Record updated in Redis']);
                } else {
                    Log::info('No changes detected for record:', ['data' => $data]);
                    return response()->json(['message' => 'No changes detected. Record unchanged']);
                }
            } else {
                // If no matching record, insert the new record
                $dataFromRedis[] = $data;

                $transformedData = $this->transformer->transform($data);
                $response = $this->postToILS($transformedData);
            
                if (!$response) {
                    // If postToILS returns null or a failure response, we handle it
                    return response()->json(['message' => 'Error posting to ILS API'], 500);
                }
                // Always encode as JSON before saving to Redis
                $this->redisService->set('cre_registration_record', json_encode($dataFromRedis));
                Log::info('New record added to Redis:', ['newData' => $data]);
                return response()->json(['message' => 'New record created in Redis']);
            }
        } catch (\Exception $e) {
            Log::error('Redis operation failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to process request'], 500);
        }
    }
    
    private function dataHasChanged($existingData, $newData) {
        $fieldsToCompare = [
            'firstname', 'lastname', 'email', 'phone', 'address', 
            'postalcode', 'city', 'careof', 
            'category1', 'category2', 'category3', 'category4', 'category5', 'category6'
        ];
    
        foreach ($fieldsToCompare as $field) {
            if (($existingData[$field] ?? null) !== ($newData[$field] ?? null)) {
                return true;
            }
        }
        
        return false;
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
            $isDuplicate = $this->isDuplicate($record1, $record2);
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
