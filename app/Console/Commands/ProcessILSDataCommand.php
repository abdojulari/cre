<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use App\Services\RedisService;

class ProcessILSDataCommand extends Command
{
    // The name and signature of the console command
    protected $signature = 'data:process';

    // The console command description
    protected $description = 'Run a command, process the output, and save it to Redis';

    protected $redisService;

    public function __construct(RedisService $redisService)
    {
        parent::__construct();
        $this->redisService = $redisService;
    }

    // Execute the console command
    public function handle()
    {
        try {
            // Run the command
            $command = "ssh edpltest.sirsidynix.net -l sirsi 2>/dev/null \'/software/EDPL/cronscripts/ILS_Registration_Engine/get_new_and_changed_ils_users_test.sh\'";
            $process = Process::run($command);
            if (!$process->successful()) {
                throw new \Exception('Command failed: ' . $process->errorOutput());
            }

            $output = $process->output();

            // Convert output to JSON
            $data = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode JSON output: ' . json_last_error_msg());
            }

            // Get the existing data from Redis
            $existingData = $this->redisService->get('cre_registration_record');
            
            // Check if the existing data is a string (in case it's a JSON string)
            if (is_string($existingData)) {
                $existingData = json_decode($existingData, true) ?? [];  // Decode JSON if it's a string
            } elseif (!is_array($existingData)) {
                $existingData = [];  // Ensure it's an empty array if it's neither a string nor an array
            }
            // Loop through the $data and $existingData, use barcode as the key to find matching data, if found, update with $data value
            foreach ($data as $newRecord) {
                $updated = false;
                foreach ($existingData as &$existingRecord) {
                    // Compare by barcode
                    if (isset($existingRecord['barcode']) && isset($newRecord['barcode']) && $existingRecord['barcode'] === $newRecord['barcode']) {
                        // Update the existing record with the new data
                        $existingRecord = array_merge($existingRecord, $newRecord);
                        $updated = true;
                        break;  // Once updated, no need to check further in the existing data
                    }
                }
                // If no existing record was updated, add the new record
                if (!$updated) {
                    $existingData[] = $newRecord;
                }
            }

            // After processing the main $data, merge with the new data file
            $path = storage_path('app/new-ils-user.json');
            $newData = json_decode(file_get_contents($path), true) ?? [];
            if (empty($newData)) {
                $this->error('No new data found in the file.');
                return;
            }

            // Loop through the new data file and process similarly
            foreach ($newData as $newRecord) {
                $updated = false;
                foreach ($existingData as &$existingRecord) {
                    // Compare by barcode
                    if (isset($existingRecord['barcode']) && isset($newRecord['barcode']) && $existingRecord['barcode'] === $newRecord['barcode']) {
                        // Update the existing record with the new data
                        $existingRecord = array_merge($existingRecord, $newRecord);
                        $updated = true;
                        break;  // Once updated, no need to check further in the existing data
                    }
                }
                // If no existing record was updated, add the new record
                if (!$updated) {
                    $existingData[] = $newRecord;
                }
            }

            // Save the final merged data to Redis
            $this->redisService->set('cre_registration_record', json_encode($existingData));

            $this->info('Data merged and saved to Redis successfully!');

            // Clear the new data file after processing
            file_put_contents($path, '');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
