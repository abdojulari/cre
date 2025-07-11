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

        // Get filter parameters from request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $year = $request->input('year');
        $month = $request->input('month');
        $profile = $request->input('profile');
        $filterType = $request->input('filter_type', 'all'); // all, date_range, year, month_year, monthly

        // Filter data based on parameters
        $filteredData = $this->filterDataByDateAndProfile($data, $startDate, $endDate, $year, $month, $profile, $filterType);
      
        // Initialize statistics arrays
        $groupedData = [];
        $profileStats = [];
        $libraryProfileStats = [];
        $totalRecords = count($filteredData);
        
        // Process each record
        foreach ($filteredData as $entry) {
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

        foreach ($filteredData as $entry) {
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
                ],
                'filters_applied' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'year' => $year,
                    'month' => $month,
                    'profile' => $profile,
                    'filter_type' => $filterType
                ]
            ]
        ];

        return response()->json($statistics);
    }

    /**
     * Filter data based on date and profile parameters
     */
    private function filterDataByDateAndProfile($data, $startDate, $endDate, $year, $month, $profile, $filterType)
    {
        return array_filter($data, function ($entry) use ($startDate, $endDate, $year, $month, $profile, $filterType) {
            // Filter by profile if specified
            if ($profile && isset($entry['profile']) && $entry['profile'] !== $profile) {
                return false;
            }

            // If no date filtering is requested, return all records
            if ($filterType === 'all' && !$startDate && !$endDate && !$year && !$month) {
                return true;
            }

            // Check if entry has createdAt field
            if (!isset($entry['createdAt'])) {
                return false;
            }

            try {
                $entryDate = new \DateTime($entry['createdAt']);
            } catch (\Exception $e) {
                return false; // Skip invalid dates
            }

            // Apply different filter types
            switch ($filterType) {
                case 'date_range':
                    return $this->filterByDateRange($entryDate, $startDate, $endDate);
                
                case 'year':
                    return $this->filterByYear($entryDate, $year);
                
                case 'month_year':
                    return $this->filterByMonthYear($entryDate, $year, $month);
                
                case 'monthly':
                    return $this->filterByMonthly($entryDate, $startDate, $endDate);
                
                default:
                    // Default behavior: apply all filters if provided
                    if ($startDate && $endDate && !$this->filterByDateRange($entryDate, $startDate, $endDate)) {
                        return false;
                    }
                    if ($year && !$this->filterByYear($entryDate, $year)) {
                        return false;
                    }
                    if ($month && $year && !$this->filterByMonthYear($entryDate, $year, $month)) {
                        return false;
                    }
                    return true;
            }
        });
    }

    /**
     * Filter by date range (YYYY-MM-DD format)
     */
    private function filterByDateRange($entryDate, $startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return true;
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->setTime(23, 59, 59); // Include the entire end date

            return $entryDate >= $start && $entryDate <= $end;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Filter by year only
     */
    private function filterByYear($entryDate, $year)
    {
        if (!$year) {
            return true;
        }

        return $entryDate->format('Y') === $year;
    }

    /**
     * Filter by month and year
     */
    private function filterByMonthYear($entryDate, $year, $month)
    {
        if (!$year || !$month) {
            return true;
        }

        return $entryDate->format('Y-m') === $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Filter by monthly periods within a date range
     */
    private function filterByMonthly($entryDate, $startDate, $endDate)
    {
        if (!$startDate || !$endDate) {
            return true;
        }

        try {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $end->setTime(23, 59, 59);

            return $entryDate >= $start && $entryDate <= $end;
        } catch (\Exception $e) {
            return false;
        }
    }

}
