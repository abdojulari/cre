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

        if (!is_array($data) || $data === null) {
            return response()->json(['error' => 'No data found in Redis'], 404);
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

        if (!is_array($data) || $data === null) {
            return response()->json(['error' => 'No data found in Redis'], 404);
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

        // Check if the data is valid
        if (!is_array($data) || $data === null) {
            return response()->json(['error' => 'No data found in Redis'], 404);
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
        
        if (!is_array($data) || $data === null) {
            return response()->json(['error' => 'No data found in Redis'], 404);
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
        
        if (!is_array($data) || $data === null) {
            return response()->json(['error' => 'No data found in Redis'], 404);
        }

        // Get filter parameters from request - ONLY if explicitly provided
        $startDate = $request->has('start_date') ? $request->input('start_date') : null;
        $endDate = $request->has('end_date') ? $request->input('end_date') : null;
        $year = $request->has('year') ? $request->input('year') : null;
        $month = $request->has('month') ? $request->input('month') : null;
        $profile = $request->has('profile') ? $request->input('profile') : null;
        $library = $request->has('library') ? $request->input('library') : null;
        $filterType = $request->input('filter_type', 'all');

        Log::info('Library Statistics Request:', [
            'total_records' => count($data),
            'request_params' => $request->all(),
            'parsed_filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'year' => $year,
                'month' => $month,
                'profile' => $profile,
                'library' => $library,
                'filter_type' => $filterType
            ]
        ]);

        // Filter data based on parameters
        $filteredData = $this->filterDataByDateAndProfile($data, $startDate, $endDate, $year, $month, $profile, $library, $filterType);
      
        Log::info('Filtering complete:', [
            'original_count' => count($data),
            'filtered_count' => count($filteredData)
        ]);

        $totalRecords = count($filteredData);
        
        // Dashboard-style breakdown for ALL requests (filtered or not)
        $libraryStats = [];
        $adultCount = 0;
        $childCount = 0;
        
        // Define profile categories
        $adultProfiles = [
            'EPL_ACCESS', 'EPL_ADULT', 'EPL_ADU01', 'EPL_ADU05', 'EPL_ADU10',
            'EPL_CORP', 'EPL_NOVIDG', 'EPL_ONLIN', 'EPL_SELF', 'EPL_VISITR', 'EPL_TRESID'
        ];
        
        $childProfiles = [
            'EPL_JNOVG', 'EPL_JONLIN', 'EPL_JUV', 'EPL_JUV01', 'EPL_JUV05',
            'EPL_JUV10', 'EPL_JUVIND', 'EPL_SELFJ'
        ];
        
        // Process each record
        foreach ($filteredData as $entry) {
            $library = $entry['library'] ?? 'Unknown';
            $profile = $entry['profile'] ?? 'Unknown';
            
            // Count by library
            if (!isset($libraryStats[$library])) {
                $libraryStats[$library] = 0;
            }
            $libraryStats[$library]++;
            
            // Count adult vs child registrations
            if (in_array($profile, $adultProfiles)) {
                $adultCount++;
            } elseif (in_array($profile, $childProfiles)) {
                $childCount++;
            }
        }
        
        // Sort libraries by total count (descending)
        arsort($libraryStats);
        
        // Get top 3 libraries
        $top3Libraries = array_slice($libraryStats, 0, 3, true);

        // Dashboard statistics response
        $dashboard = [
            'dashboard' => [
                'total_registrations' => $totalRecords,
                'by_branch' => count($libraryStats),
                'adult_registrations' => $adultCount,
                'child_registrations' => $childCount,
                'registrations_by_branch' => $libraryStats,
                'top_3_branches' => $top3Libraries,
                'filters_applied' => array_filter([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'year' => $year,
                    'month' => $month,
                    'library' => $library,
                    'profile' => $profile,
                    'filter_type' => $filterType
                ], function($value) { return $value !== null; })
            ]
        ];

        return response()->json($dashboard);
    }

    /**
     * Filter data based on date and profile parameters
     */
    private function filterDataByDateAndProfile($data, $startDate, $endDate, $year, $month, $profile, $library, $filterType)
    {
        return array_filter($data, function ($entry) use ($startDate, $endDate, $year, $month, $profile, $library, $filterType) {
            // Filter by profile ONLY if explicitly provided
            if ($profile !== null && isset($entry['profile']) && $entry['profile'] !== $profile) {
                return false;
            }

            // Filter by library ONLY if explicitly provided
            if ($library !== null && isset($entry['library']) && $entry['library'] !== $library) {
                return false;
            }

            // If no filtering is requested, return all records
            if ($filterType === 'all' && $startDate === null && $endDate === null && $year === null && $month === null) {
                return true;
            }

            // For date-based filtering, check if entry has createdAt field
            if (($year !== null || $month !== null || $startDate !== null || $endDate !== null)) {
                if (!isset($entry['createdAt'])) {
                    return false; // Skip records without date when date filtering is requested
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
                    case 'month':
                        return $this->filterByMonthYear($entryDate, $year, $month);
                    
                    case 'monthly':
                        return $this->filterByMonthly($entryDate, $startDate, $endDate);
                    
                    default:
                        // Default behavior: apply all filters if provided
                        if ($startDate !== null && $endDate !== null && !$this->filterByDateRange($entryDate, $startDate, $endDate)) {
                            return false;
                        }
                        if ($year !== null && !$this->filterByYear($entryDate, $year)) {
                            return false;
                        }
                        if ($month !== null && $year !== null && !$this->filterByMonthYear($entryDate, $year, $month)) {
                            return false;
                        }
                        return true;
                }
            }

            return true; // Include record if no date filtering was requested
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
