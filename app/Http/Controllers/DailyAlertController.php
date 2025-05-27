<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RedisService;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;

class DailyAlertController extends Controller
{
    protected $redisService;

    public function __construct(RedisService $redisService)
    {
        $this->redisService = $redisService;
    }

    public function duplicateDetectionAlert()
    {
        $data = $this->redisService->get('cre_registration_record');

        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        $duplicates = [];
        $seen = [];

        foreach ($data as $entry) {
            $key = $entry['firstname'] . $entry['lastname'] . $entry['dateofbirth'] . $entry['phone'] . $entry['email'] . ($entry['careof'] ?? '');

            if (isset($seen[$key])) {
                $duplicates[] = $entry;
            } else {
                $seen[$key] = true;
            }
        }

        try {
            if (!empty($duplicates)) {
                Notification::route('slack', config('services.slack.notifications.webhook_url'))
                    ->notify(new DuplicateDetectionNotification($duplicates));
                
                return response()->json([
                    'message' => 'Duplicate Entry(ies) detected',
                    'duplicates' => $duplicates
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Slack notification failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Duplicate entries detected but notification failed',
                'duplicates' => $duplicates,
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json(['message' => 'No duplicates found']);
    }

    public function barcodeLookup(Request $request)
    {
        $barcode = $request->input('barcode');
        $data = $this->redisService->get('cre_registration_record');

        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        $result = array_filter($data, function ($entry) use ($barcode) {
            return isset($entry['barcode']) && $entry['barcode'] === $barcode;
        });
        Log::info('Barcode lookup result:', ['result' => $result]);
        return response()->json(['result' => $result]);
    }

    public function listBarcodes()
    {
        // Get the data from Redis
        $data = $this->redisService->get('cre_registration_record');

        // Check if the data is in JSON format, and decode it
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        // Format the data into the desired string format
        $formattedData = array_map(function ($entry) {
            // Ensure each entry has the necessary fields before formatting
            if (isset($entry['barcode'], $entry['firstname'], $entry['lastname'], $entry['phone'], $entry['email'], $entry['dateofbirth'])) {
                return $entry['barcode'] . ' - ' . $entry['firstname'] . ',' . $entry['lastname'] . ',' . $entry['phone'] . ',' . $entry['email'] . ',' . $entry['dateofbirth'];
            }
            return null;
        }, $data);

        // Remove any null values from the formatted data (if any)
        $formattedData = array_filter($formattedData, function ($item) {
            return $item !== null;
        });

        // Return the formatted data as JSON
        return response()->json(['barcodes' => array_values($formattedData)]);
    }

    // read redis data and group by library and return the count of records
    public function groupByLibrary()
    {
        $data = $this->redisService->get('cre_registration_record');
        
        if (!is_array($data)) { 
            $data = json_decode($data, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        $groupedData = [];
        foreach ($data as $entry) {
            $library = $entry['library'];
            if (!isset($groupedData[$library])) {
                $groupedData[$library] = 0;
            }
            $groupedData[$library]++;
        }

        return response()->json(['groupedData' => $groupedData]);
        
        
    }

    public function getLibraryStatistics(Request $request)
    {
        $data = $this->redisService->get('cre_registration_record');
        
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'Invalid JSON data'], 400);
        }

        // Initialize statistics arrays
        $groupedData = [];
        $profileStats = [];
        $libraryProfileStats = [];
        $totalRecords = count($data);
        
        // Process each record
        foreach ($data as $entry) {
            $library = $entry['library'] ?? 'Unknown';
            $profile = $entry['profile'] ?? 'Unknown';
            
            // Initialize library counts
            if (!isset($groupedData[$library])) {
                $groupedData[$library] = [
                    'total' => 0,
                    'profiles' => [
                        'EPL_SELF' => 0,
                        'EPL_SELFJ' => 0,
                        'Unknown' => 0
                    ]
                ];
            }
            
            // Initialize profile counts
            if (!isset($profileStats[$profile])) {
                $profileStats[$profile] = 0;
            }
            
            // Update counts
            $groupedData[$library]['total']++;
            $profileStats[$profile]++;
            
            // Update library-specific profile counts
            if (in_array($profile, ['EPL_SELF', 'EPL_SELFJ'])) {
                $groupedData[$library]['profiles'][$profile]++;
            } else {
                $groupedData[$library]['profiles']['Unknown']++;
            }
        }

        // Calculate additional statistics
        $totalLibraries = count($groupedData);
        $totalProfiles = count($profileStats);
        
        // Get date range statistics
        $dateStats = [
            'earliest_record' => null,
            'latest_record' => null,
            'records_by_month' => []
        ];

        foreach ($data as $entry) {
            if (isset($entry['createdAt'])) {
                $date = new \DateTime($entry['createdAt']);
                $monthYear = $date->format('Y-m');
                
                if (!isset($dateStats['records_by_month'][$monthYear])) {
                    $dateStats['records_by_month'][$monthYear] = 0;
                }
                $dateStats['records_by_month'][$monthYear]++;
                
                // Update earliest and latest dates
                if ($dateStats['earliest_record'] === null || $date < new \DateTime($dateStats['earliest_record'])) {
                    $dateStats['earliest_record'] = $entry['createdAt'];
                }
                if ($dateStats['latest_record'] === null || $date > new \DateTime($dateStats['latest_record'])) {
                    $dateStats['latest_record'] = $entry['createdAt'];
                }
            }
        }

        // Sort libraries by total count
        uasort($groupedData, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        // Sort profile stats by count
        arsort($profileStats);

        // Sort monthly stats by date
        ksort($dateStats['records_by_month']);

        $statistics = [
            'statistics' => [
                'by_library' => $groupedData,
                'by_profile' => $profileStats,
                'date_statistics' => $dateStats,
                'summary' => [
                    'total_records' => $totalRecords,
                    'total_libraries' => $totalLibraries,
                    'total_profiles' => $totalProfiles,
                    'profile_distribution' => [
                        'EPL_SELF' => $profileStats['EPL_SELF'] ?? 0,
                        'EPL_SELFJ' => $profileStats['EPL_SELFJ'] ?? 0,
                        'Other' => array_sum($profileStats) - (($profileStats['EPL_SELF'] ?? 0) + ($profileStats['EPL_SELFJ'] ?? 0))
                    ]
                ]
            ]
        ];

        return response()->json($statistics);
    }

}
