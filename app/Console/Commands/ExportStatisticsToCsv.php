<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\RedisService;
use Illuminate\Support\Facades\File;
use App\Mail\SendWelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ExportStatisticsToCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-statistics-to-csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export statistics data from Redis to CSV';
    // The RedisService to interact with Redis
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
        // Get existing data from Redis
        $existingData = $this->redisService->get('statistics_data') ?? [];

        // If data is a string, decode it into an array
        if (is_string($existingData)) {
            $existingData = json_decode($existingData, true) ?? [];
        }

        // Ensure that existingData is an array
        if (!is_array($existingData)) {
            $this->error('No valid data found in Redis.');
            return;
        }

        // Prepare the CSV file path
        $csvFilePath = storage_path('app/statistics_data.csv');

        // Open the file in write mode
        $csvFile = fopen($csvFilePath, 'w');

        // Define the column headers for the CSV
        $headers = [
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
            'event_category', 'event_label', 'screen_name', 'registration_type',
            'postal_code', 'step', 'date', 'time'
        ];

        // Write the headers to the CSV file
        fputcsv($csvFile, $headers);

        // Write each row of data to the CSV file
        foreach ($existingData as $data) {
            $row = [
                $data['utm_source'] ?? '',
                $data['utm_medium'] ?? '',
                $data['utm_campaign'] ?? '',
                $data['utm_term'] ?? '',
                $data['utm_content'] ?? '',
                $data['event_category'] ?? '',
                $data['event_label'] ?? '',
                $data['screen_name'] ?? '',
                $data['registration_type'] ?? '',
                $data['postal_code'] ?? '',
                $data['step'] ?? '',
                $data['date'] ?? '',
                $data['time'] ?? ''
            ];

            // Write the row to the CSV
            fputcsv($csvFile, $row);
        }

        // Close the file after writing
        fclose($csvFile);

        // Upload the file to public disk and get the URL
        $csvFileName = 'statistics_data.csv';
        $path = Storage::disk('public')->putFileAs('exports', new \Illuminate\Http\File($csvFilePath), $csvFileName);
        $fileUrl = Storage::disk('public')->url('exports/' . $csvFileName);

        // Send the file URL to Slack
        Log::channel('slack')->info('New statistics export available', [
            'file' => [
                'title' => 'Statistics Data Export',
                'url' => $fileUrl,  // Include the URL in Slack message
            ]
        ]);

        // Send email with CSV attachment
        $email = config('cre.email');
        if (!$email) {
            $this->warn('No email address configured for export. Skipping email sending.');
        } else {
            Mail::raw('Please find attached the latest statistics export.', function($message) use ($csvFilePath, $email) {
                $message->to($email)
                        ->subject('Statistics Data Export')
                        ->attach($csvFilePath, [
                            'as' => 'statistics_data.csv',
                            'mime' => 'text/csv',
                        ]);
            });
            $this->info('Export email sent to: ' . $email);
        }

        // Clear the Redis data after successful export
        $this->redisService->del('statistics_data');

        // Output success message
        $this->info('Statistics data has been successfully exported to CSV.');
    }

}
