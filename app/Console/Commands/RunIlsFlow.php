<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalApiService;

class RunIlsFlow extends Command
{
    /**
     * The name and signature of the console command.
     * Accepts either a JSON string or a path to a JSON file via --json or --file
     */
    protected $signature = 'ils:run-flow {--json=} {--file=}';

    /**
     * The console command description.
     */
    protected $description = 'Run getSessionToken, retrieveILSData, and updateToILS with provided data';

    public function __construct(private ExternalApiService $externalApiService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $jsonOption = $this->option('json');
        $fileOption = $this->option('file');

        if (!$jsonOption && !$fileOption) {
            $this->error('Provide data with --json="{...}" or --file=/absolute/path/to.json');
            return self::FAILURE;
        }

        try {
            if ($fileOption) {
                if (!is_readable($fileOption)) {
                    $this->error('File not readable: ' . $fileOption);
                    return self::FAILURE;
                }
                $payload = json_decode(file_get_contents($fileOption), true);
            } else {
                $payload = json_decode($jsonOption, true);
            }
        } catch (\Throwable $e) {
            $this->error('Invalid JSON: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!is_array($payload)) {
            $this->error('Decoded payload is not an object/array.');
            return self::FAILURE;
        }

        // Persist payload for audit / re-runs
        try {
            $savePath = storage_path('app/patron.json');
            file_put_contents($savePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->line('Saved payload to: ' . $savePath);
        } catch (\Throwable $e) {
            $this->warn('Could not save payload: ' . $e->getMessage());
        }

        // 1) getSessionToken
        $this->info('Requesting session token...');
        $token = $this->externalApiService->getSessionToken();
        if (!$token) {
            $this->error('Failed to get session token');
            return self::FAILURE;
        }
        $this->info('Session token acquired');

        // 2) retrieveILSData
        $this->info('Retrieving ILS data by barcode (if provided)...');
        $ilsData = null;
        if (isset($payload['barcode']) && $payload['barcode']) {
            $ilsData = $this->externalApiService->retrieveILSData($payload);
            $this->line('ILS Lookup: ' . json_encode($ilsData));
        } else {
            $this->line('No barcode provided; skipping ILS lookup.');
        }

        // 3) updateToILS
        $this->info('Updating ILS...');
        \Log::info('ILS Update starting', [
            'command' => 'ils:run-flow',
            'endpoint' => config('cre.ils_base_url') . config('cre.patron_endpoint'),
            'method' => 'PUT',
            'barcode' => $payload['barcode'] ?? null,
            'key' => $payload['@key'] ?? ($payload['key'] ?? null),
        ]);
        $updateResponse = $this->externalApiService->updateToILS($payload);
        if ($updateResponse === null) {
            \Log::error('ILS Update failed', [
                'command' => 'ils:run-flow',
                'barcode' => $payload['barcode'] ?? null,
            ]);
            $this->error('Update failed. Check logs for details.');
            return self::FAILURE;
        }
        \Log::info('ILS Update response', [
            'command' => 'ils:run-flow',
            'response' => $updateResponse,
        ]);
        $this->line('Update Response: ' . json_encode($updateResponse));
        $this->info('Done.');
        return self::SUCCESS;
    }
}


