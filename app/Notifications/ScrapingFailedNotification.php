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
    public $source;
    public $sourceUrl;

    public function __construct($errorMessage, $source = 'Target Website', $sourceUrl = '#')
    {
        $this->errorMessage = $errorMessage;
        $this->source = $source;
        $this->sourceUrl = $sourceUrl;
    }

    public function via($notifiable): array
    {
        // Returns enabled channels
        return ['mail'];
        // return ['mail', 'slack', 'telegram'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("⚠️ Scraping Failed Alert: {$this->source}")
                    ->view('emails.scraping-failed', [
                        'errorMessage' => $this->errorMessage,
                        'source' => $this->source,
                        'sourceUrl' => $this->sourceUrl,
                    ]);
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
