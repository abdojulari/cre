<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RedisService;
use App\Models\GeneratedBarcode;
use Illuminate\Support\Facades\DB;

class MigrateBarcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'barcode:migrate-existing {--dry-run : Show what would be migrated without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing barcodes from Redis and populate Redis sets for conflict checking';

    protected $redisService;

    public function __construct(RedisService $redisService)
    {
        parent::__construct();
        $this->redisService = $redisService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Starting barcode migration process...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Migrate existing barcodes from Redis single keys
        $this->migrateSingleBarcodes($isDryRun);
        
        // Populate Redis sets from existing database records
        $this->populateRedisSets($isDryRun);
        
        // Migrate barcodes from registration records if they exist
        $this->migrateRegistrationBarcodes($isDryRun);
        
        $this->info('Barcode migration completed!');
    }
    
    /**
     * Migrate existing single barcode entries from Redis
     */
    private function migrateSingleBarcodes(bool $isDryRun)
    {
        $this->info('Checking for existing barcode entries in Redis...');
        
        // Check for digital and physical barcodes
        $digitalBarcode = $this->redisService->get('digital_barcode');
        $physicalBarcode = $this->redisService->get('barcode');
        
        $migrated = 0;
        
        if ($digitalBarcode) {
            $this->line("Found digital barcode: {$digitalBarcode}");
            if (!$isDryRun) {
                $this->migrateSingleBarcode($digitalBarcode, 'digital');
                $migrated++;
            }
        }
        
        if ($physicalBarcode) {
            $this->line("Found physical barcode: {$physicalBarcode}");
            if (!$isDryRun) {
                $this->migrateSingleBarcode($physicalBarcode, 'physical');
                $migrated++;
            }
        }
        
        if ($migrated > 0) {
            $this->info("Migrated {$migrated} single barcode entries");
        } else {
            $this->info('No single barcode entries found to migrate');
        }
    }
    
    /**
     * Migrate a single barcode to the database
     */
    private function migrateSingleBarcode(string $barcode, string $type)
    {
        $prefix = $type === 'digital' 
            ? config('cre.digital_barcode_prefix') 
            : config('cre.barcode_prefix');
            
        $numericPart = substr($barcode, strlen($prefix));
        
        // Check if already exists
        if (!GeneratedBarcode::barcodeExists($barcode)) {
            GeneratedBarcode::create([
                'barcode' => $barcode,
                'type' => $type,
                'prefix' => $prefix,
                'numeric_part' => $numericPart,
                'generated_at' => now()
            ]);
            
            // Add to Redis set
            $this->redisService->addToSet("generated_barcodes_{$type}", $barcode);
            
            $this->line("✓ Migrated {$type} barcode: {$barcode}");
        } else {
            $this->line("• Barcode already exists: {$barcode}");
        }
    }
    
    /**
     * Populate Redis sets from existing database records
     */
    private function populateRedisSets(bool $isDryRun)
    {
        $this->info('Populating Redis sets from database records...');
        
        $barcodes = GeneratedBarcode::all();
        $added = 0;
        
        foreach ($barcodes as $barcode) {
            $setKey = "generated_barcodes_{$barcode->type}";
            
            if (!$isDryRun) {
                $this->redisService->addToSet($setKey, $barcode->barcode);
                $added++;
            } else {
                $this->line("Would add to Redis set {$setKey}: {$barcode->barcode}");
            }
        }
        
        if ($added > 0) {
            $this->info("Added {$added} barcodes to Redis sets");
        } else if (!$isDryRun) {
            $this->info('No database records found to populate Redis sets');
        }
    }
    
    /**
     * Migrate barcodes from registration records
     */
    private function migrateRegistrationBarcodes(bool $isDryRun)
    {
        $this->info('Checking for barcodes in registration records...');
        
        $registrationData = $this->redisService->get('cre_registration_record');
        
        if (!is_array($registrationData)) {
            $this->info('No registration records found');
            return;
        }
        
        $migrated = 0;
        
        foreach ($registrationData as $record) {
            if (isset($record['barcode'])) {
                $barcode = $record['barcode'];
                
                // Determine type based on prefix
                $digitalPrefix = config('cre.digital_barcode_prefix');
                $physicalPrefix = config('cre.barcode_prefix');
                
                $type = 'digital';
                $prefix = $digitalPrefix;
                
                if (str_starts_with($barcode, $physicalPrefix)) {
                    $type = 'physical';
                    $prefix = $physicalPrefix;
                }
                
                if (!$isDryRun) {
                    if (!GeneratedBarcode::barcodeExists($barcode)) {
                        $numericPart = substr($barcode, strlen($prefix));
                        
                        GeneratedBarcode::create([
                            'barcode' => $barcode,
                            'type' => $type,
                            'prefix' => $prefix,
                            'numeric_part' => $numericPart,
                            'generated_at' => now()
                        ]);
                        
                        $this->redisService->addToSet("generated_barcodes_{$type}", $barcode);
                        $migrated++;
                        
                        $this->line("✓ Migrated registration barcode: {$barcode}");
                    }
                } else {
                    $this->line("Would migrate registration barcode: {$barcode} ({$type})");
                }
            }
        }
        
        if ($migrated > 0) {
            $this->info("Migrated {$migrated} registration barcodes");
        } else if (!$isDryRun) {
            $this->info('No new registration barcodes found to migrate');
        }
    }
}
