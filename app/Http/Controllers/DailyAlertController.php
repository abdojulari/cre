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
}
