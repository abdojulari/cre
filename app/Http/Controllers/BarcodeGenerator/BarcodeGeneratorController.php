<?php

namespace App\Http\Controllers\BarcodeGenerator;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RedisService;
use App\Models\GeneratedBarcode;
use Illuminate\Support\Facades\Log;

class BarcodeGeneratorController extends Controller
{
    protected $redisService;

    public function __construct(RedisService $redisService)
    {
        $this->redisService = $redisService;
    }

    public function create()
    {
        try {
            // Define the constant prefix for the barcode
            $constantPrefix = config('cre.digital_barcode_prefix'); // '212219' create an environment variable for this
            
            // Generate a random barcode using the new system
            $newBarcode = $this->generateRandomBarcodeWithConflictCheck($constantPrefix, 'digital');
            
            // Store in Redis for backward compatibility and fast access
            $this->redisService->set('digital_barcode_latest', $newBarcode);
            
            // Also add to Redis set for fast conflict checking
            $this->redisService->addToSet('generated_barcodes_digital', $newBarcode);
            
            Log::info("Successfully generated new digital barcode", [
                'barcode' => $newBarcode,
                'prefix' => $constantPrefix
            ]);
            
            // Return the new barcode to the user
            return response()->json([
                'barcode' => $newBarcode,
                'status' => 'success',
                'message' => 'Barcode generated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to generate barcode", [
                'error' => $e->getMessage(),
                'prefix' => $constantPrefix ?? 'unknown'
            ]);
            
            return response()->json([
                'error' => 'Failed to generate barcode',
                'message' => 'Please try again later'
            ], 500);
        }
    }
    
    /**
     * Generate a random barcode with conflict checking
     */
    private function generateRandomBarcodeWithConflictCheck(string $prefix, string $type = 'digital', int $maxAttempts = 1000): string
    {
        $redisSetKey = "generated_barcodes_{$type}";
        $attempts = 0;
        
        do {
            // Generate random 8-digit numeric part
            $numericPart = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
            $barcode = $prefix . $numericPart;
            
            $attempts++;
            
            if ($attempts >= $maxAttempts) {
                Log::error("Failed to generate unique barcode after {$maxAttempts} attempts", [
                    'prefix' => $prefix,
                    'type' => $type
                ]);
                throw new \Exception("Unable to generate unique barcode after {$maxAttempts} attempts");
            }
            
            // Check both database and Redis for conflicts
            $existsInDb = GeneratedBarcode::barcodeExists($barcode);
            $existsInRedis = $this->redisService->isInSet($redisSetKey, $barcode);
            
        } while ($existsInDb || $existsInRedis);
        
        // Store in database
        GeneratedBarcode::create([
            'barcode' => $barcode,
            'type' => $type,
            'prefix' => $prefix,
            'numeric_part' => $numericPart,
            'generated_at' => now()
        ]);
        
        return $barcode;
    }
    
    /**
     * Get barcode generation statistics
     */
    public function getStatistics()
    {
        try {
            $stats = GeneratedBarcode::getStatistics();
            
            // Add Redis stats for digital barcodes
            $stats['redis_digital_count'] = $this->redisService->getSetCount('generated_barcodes_digital');
            $stats['redis_physical_count'] = $this->redisService->getSetCount('generated_barcodes_physical');
            
            return response()->json([
                'statistics' => $stats,
                'status' => 'success'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to get barcode statistics", ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Failed to retrieve statistics',
                'message' => 'Please try again later'
            ], 500);
        }
    }
}
