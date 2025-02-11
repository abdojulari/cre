<?php 

// replace redis with duplicates.json at 00:45
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use App\Services\RedisService;
use Illuminate\Support\Facades\Log;

class ProcessDuplicatesDataCommand extends Command
{
    // The name and signature of the console command
    protected $signature = 'data:replace';

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
            //  read duplicates.json
            $path = storage_path('app/duplicates.json');
            $data = json_decode(file_get_contents($path), true);
            if (empty($data)) {
                $this->error('No new data found in the file.');
                return;
            }
            $this->redisService->set('cre_registration_record', json_encode($data));
            Log::channel('slack')->alert('Successfully saved on Redis!', [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

}