<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;  // Changed this import

class DuplicateDetectionNotification extends Notification
{
    use Queueable;

    protected $duplicates;

    public function __construct($duplicates)
    {
        $this->duplicates = $duplicates;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toSlack($notifiable)
    {
        $message = (new SlackMessage)
            ->error()
            ->content('🚨 Duplicate Entries Detected');

        // Add attachments for each duplicate
        foreach ($this->duplicates as $index => $duplicate) {
            $message->attachment(function ($attachment) use ($index, $duplicate) {
                $attachment
                    ->title("Duplicate #" . ($index + 1))
                    ->content(sprintf(
                        "*Name:* %s %s\n*DOB:* %s\n*Phone:* %s\n*Email:* %s",
                        $duplicate['firstname'],
                        $duplicate['lastname'],
                        $duplicate['dateofbirth'],
                        $duplicate['phone'],
                        $duplicate['email']
                    ))
                    ->color('#FF0000');
            });
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}