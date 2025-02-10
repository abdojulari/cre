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

}
