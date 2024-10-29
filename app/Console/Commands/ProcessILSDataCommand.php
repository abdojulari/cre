<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Predis\Client;

class ProcessILSDataCommand extends Command
{
    // The name and signature of the console command
    protected $signature = 'data:process';

    // The console command description
    protected $description = 'Run a command, process the output, and save it to Redis';

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

            $jsonData = json_encode($data);
            if ($jsonData === false) {
                throw new \Exception('Failed to encode JSON: ' . json_last_error_msg());
            }

            // Save JSON to Redis
            $redisKey = 'duplicates_data';
            $redis = new Client([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
                'database'   => '0'
            ]);
            $redis->set($redisKey, $jsonData);
    
            $this->info('Data saved to Redis successfully!');

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
