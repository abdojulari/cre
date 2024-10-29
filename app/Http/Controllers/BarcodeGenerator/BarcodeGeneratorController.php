<?php

namespace App\Http\Controllers\BarcodeGenerator;

use Illuminate\Http\Request;
use Predis\Client;
use App\Http\Controllers\Controller;

class BarcodeGeneratorController extends Controller
{
    public function create()
    {
        // Initialize Redis client
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
            'database' => '0',
        ]);

        // Define the constant prefix for the barcode
        $constantPrefix = '21221';

        // Get the last generated barcode from Redis
        $lastBarcode = $redis->get('barcode');

        if ($lastBarcode) {
            // Extract the numeric part and increment it by 1
            $lastNumericPart = (int)substr($lastBarcode, strlen($constantPrefix));
            $newNumericPart = $lastNumericPart + 1;
        } else {
            // Initialize the first numeric part (starting value)
            $newNumericPart = 30376355; // Starting value for the 9-digit number
        }

        // Ensure the new numeric part is 9 digits long
        $newNumericPartPadded = str_pad($newNumericPart, 9, '0', STR_PAD_LEFT);

        // Generate the new barcode
        $newBarcode = $constantPrefix . $newNumericPartPadded;

        // Save the new barcode to Redis
        $redis->set('barcode', $newBarcode);

        // Return the new barcode to the user
        return response()->json(['barcode' => $newBarcode]);
    }
}
