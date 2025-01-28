<?php

namespace App\Http\Controllers\BarcodeGenerator;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RedisService;

class BarcodeGeneratorController extends Controller
{
    protected $redisService;

    public function __construct(RedisService $redisService)
    {
        $this->redisService = $redisService;
    }

    public function create()
    {
        // Define the constant prefix for the barcode
        $constantPrefix = config('cre.barcode_prefix'); // '212219' create an environment variable for this
        $startBarcode = config('cre.start_barcode');
        // Get the last generated barcode from Redis
        $lastBarcode = $this->redisService->get('barcode');
        if ($lastBarcode) {
            // Extract the numeric part and increment it by 1
            $lastNumericPart = (int)substr($lastBarcode, strlen($constantPrefix));
            $newNumericPart = $lastNumericPart + 1;
        } else {
            // Initialize the first numeric part (starting value)
            $newNumericPart = $startBarcode; // Starting value for the 9-digit number
        }

        // Ensure the new numeric part is 9 digits long
        $newNumericPartPadded = str_pad($newNumericPart, 8, '0', STR_PAD_LEFT);

        // Generate the new barcode
        $newBarcode = $constantPrefix . $newNumericPartPadded;
        // Save the new barcode to Redis
        $this->redisService->set('barcode', $newBarcode);
        // Return the new barcode to the user
        return response()->json(['barcode' => $newBarcode]);
    }
}
