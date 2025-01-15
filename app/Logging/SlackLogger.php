<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Illuminate\Support\Facades\Http;

class SlackLogger extends AbstractProcessingHandler
{
    protected $url;

    public function __construct($url, $level = Logger::DEBUG, $bubble = true)
    {
        $this->url = $url;
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $payload = [
            'username' => 'Laravel Log',
            'icon_emoji' => ':boom:',
            'text' => $record['message'],
        ];

        // Add more details, such as stack trace, context, etc.
        if (!empty($record['context'])) {
            $payload['attachments'] = [
                [
                    'text' => json_encode($record['context'], JSON_PRETTY_PRINT),
                    'color' => 'danger',
                ],
            ];
        }

        Http::post($this->url, $payload);
    }
}
