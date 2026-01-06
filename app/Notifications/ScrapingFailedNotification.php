<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class ScrapingFailedNotification extends Notification
{
    use Queueable;

    public $errorMessage;

    public function __construct($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    public function via($notifiable): array
    {
        // Returns enabled channels
        return ['mail', 'slack', 'telegram']; // Telegram requires a driver
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('⚠️ Scraping Failed Alert')
                    ->line('The currency scraping job has failed after 3 attempts.')
                    ->line('Error: ' . $this->errorMessage)
                    ->action('Check Logs', url('/'))
                    ->line('Please investigate immediately.');
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
                    ->error()
                    ->content('⚠️ Scraping Failed: ' . $this->errorMessage);
    }

    // toTelegram would require 'laravel-notification-channels/telegram' package
    public function toTelegram($notifiable)
    {
        // Placeholder for Telegram logic if package were installed
        // return TelegramMessage::create()->content(...);
        return "Scraping Failed: " . $this->errorMessage;
    }
}
