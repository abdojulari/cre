<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GeneratedBarcode extends Model
{
    protected $fillable = [
        'barcode',
        'type',
        'prefix',
        'numeric_part',
        'generated_at'
    ];

    protected $casts = [
        'generated_at' => 'datetime'
    ];

    /**
     * Check if a barcode already exists
     */
    public static function barcodeExists(string $barcode): bool
    {
        return self::where('barcode', $barcode)->exists();
    }

    /**
     * Generate a random numeric part ensuring no conflicts
     */
    public static function generateRandomNumericPart(string $prefix, string $type = 'digital', int $length = 8, int $maxAttempts = 1000): string
    {
        $attempts = 0;
        
        do {
            // Generate random numeric part
            $numericPart = str_pad(mt_rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
            $barcode = $prefix . $numericPart;
            
            $attempts++;
            
            if ($attempts >= $maxAttempts) {
                Log::error("Failed to generate unique barcode after {$maxAttempts} attempts", [
                    'prefix' => $prefix,
                    'type' => $type,
                    'length' => $length
                ]);
                throw new \Exception("Unable to generate unique barcode after {$maxAttempts} attempts");
            }
            
        } while (self::barcodeExists($barcode));
        
        return $numericPart;
    }

    /**
     * Create and store a new barcode
     */
    public static function createBarcode(string $prefix, string $type = 'digital', int $length = 8): string
    {
        $numericPart = self::generateRandomNumericPart($prefix, $type, $length);
        $barcode = $prefix . $numericPart;
        
        self::create([
            'barcode' => $barcode,
            'type' => $type,
            'prefix' => $prefix,
            'numeric_part' => $numericPart,
            'generated_at' => now()
        ]);
        
        Log::info("Generated new barcode", [
            'barcode' => $barcode,
            'type' => $type,
            'prefix' => $prefix
        ]);
        
        return $barcode;
    }

    /**
     * Get statistics about generated barcodes
     */
    public static function getStatistics(): array
    {
        return [
            'total_barcodes' => self::count(),
            'digital_barcodes' => self::where('type', 'digital')->count(),
            'physical_barcodes' => self::where('type', 'physical')->count(),
            'latest_barcode' => self::latest('generated_at')->first()?->barcode,
            'oldest_barcode' => self::oldest('generated_at')->first()?->barcode,
        ];
    }
}
