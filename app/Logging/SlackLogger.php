<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\NullHandler;
use Monolog\Formatter\LineFormatter;

class SlackLogger
{
    public function __invoke(array $config)
    {
        try {
            $webhookUrl = config('logging.channels.slack.url');
            
            // Return NullHandler for testing environment or empty webhook
            if (empty($webhookUrl) || app()->environment('testing')) {
                return new Logger('slack', [new NullHandler()]);
            }

            $handler = new SlackWebhookHandler(
                $webhookUrl,
                'cre',              
                'Laravel Logger',    
                true,               
                ':warning:',        
                false,              
                true,               
                Logger::DEBUG       
            );

            // Add some debug logging
            error_log("Initializing Slack logger with webhook: " . substr($webhookUrl, 0, 20) . '...');

            // Use LineFormatter for simple message formatting
            $formatter = new LineFormatter(
                "Laravel Application: %message% %context% %extra%",
                null,
                true,
                true
            );

            $handler->setFormatter($formatter);
            return new Logger('slack', [$handler]);

        } catch (\Exception $e) {
            // Log the error to Laravel's default log
            error_log('SlackLogger Error: ' . $e->getMessage());
            // Return NullHandler instead of throwing exception
            return new Logger('slack', [new NullHandler()]);
        }
    }
}