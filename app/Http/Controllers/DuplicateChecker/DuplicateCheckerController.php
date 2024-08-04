<?php

namespace App\Http\Controllers\DuplicateChecker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Predis\Client;
use Illuminate\Support\Facades\Log;


class DuplicateCheckerController extends Controller
{
    public function index(){
        $path = storage_path('app/duplicates.json');
        $duplicates = json_decode(file_get_contents($path), true) ?? [];
        return response()->json($duplicates);
    }

    public function show($id) {
        $path = storage_path('app/duplicates.json');
        $duplicates = json_decode(file_get_contents($path), true) ?? [];

        foreach ($duplicates as $duplicate) {
            if ($duplicate['id'] == $id) {
                return response()->json($duplicate);
            }
        }

        return response()->json(['message' => 'Record not found.'], 404);
    }

    public function update(Request $request, $id) {
        $data = $request->validate([
            'firstname' => 'sometimes|required|string',
            'lastname' => 'sometimes|required|string',
            'dateofbirth' => 'sometimes|required|date',
            'email' => 'sometimes|required|email',
            'phone' => 'sometimes|required|string',
            'address' => 'sometimes|required|string',
            'postalcode' => 'required|string',
            'city' => 'required|string',
            'barcode' => 'sometimes|required|string',
            'createdAt' => 'sometimes|required|date',
            'modifiedAt' => 'sometimes|required|date'
        ]);

        $path = storage_path('app/duplicates.json');
        $duplicates = json_decode(file_get_contents($path), true) ?? [];

        foreach ($duplicates as &$duplicate) {
            if ($duplicate['id'] == $id) {
                $duplicate = array_merge($duplicate, $data);
                file_put_contents($path, json_encode($duplicates));
                return response()->json(['message' => 'Record updated successfully.']);
            }
        }

        return response()->json(['message' => 'Record not found.'], 404);
    }

    public function destroy($id) {
        $path = storage_path('app/duplicates.json');
        $duplicates = json_decode(file_get_contents($path), true) ?? [];

        foreach ($duplicates as $key => $duplicate) {
            if ($duplicate['id'] == $id) {
                unset($duplicates[$key]);
                file_put_contents($path, json_encode(array_values($duplicates)));
                return response()->json(['message' => 'Record deleted successfully.']);
            }
        }

        return response()->json(['message' => 'Record not found.'], 404);
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
            'city' => 'required|string',
            'barcode' => 'required|string',
            'createdAt' => 'required|date',
            'modifiedAt' => 'required|date'
        ]);    
    
        // Check if the record already exists
        $path = storage_path('app\duplicates.json');
        $duplicates = json_decode(file_get_contents($path), true) ?? [];

        // Store duplicates in Redis using Predis
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
            'database'   => '0'
        ]);

        $redis->set('duplicates_data', json_encode($duplicates));
        Log::info('Stored duplicates data in Redis.', ['data' => $duplicates]);
        // Fuzzy logic check
        $duplicate = $this->retrieveDuplicateUsingCache($data, $redis);
        if ($duplicate) {
            return response()->json([
                'message' => 'Duplicate record found with fuzzy logic.',
                'duplicate' => $duplicate
            ], 409);
        }
    
        // Add the record to the duplicates file
        $duplicates[] = $data;
        file_put_contents($path, json_encode($duplicates));
    
        // Update Redis cache after adding new record
        $redis->set('duplicates_data', json_encode($duplicates));
    
        return response()->json(['message' => 'Record added successfully.'], 201);
    }
    
    private function retrieveDuplicateUsingCache($data, $redis) {
        // Retrieve duplicates from Redis using Predis
        $duplicates = json_decode($redis->get('duplicates_data'), true) ?? [];

        foreach ($duplicates as $duplicate) {
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

}
