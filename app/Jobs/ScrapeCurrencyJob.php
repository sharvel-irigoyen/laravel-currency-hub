<?php

namespace App\Jobs;

use App\Notifications\ScrapingFailedNotification;
use App\Spiders\CurrencySpider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use RoachPHP\Roach;
use Throwable;

class ScrapeCurrencyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Data needed for notifications if hardcoded, or config driven.
     */
    protected $notifyEmail = 'admin@example.com'; // Change via config

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Run the spider
        Roach::startSpider(CurrencySpider::class);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Send notification via Telegram, Slack, Gmail, WhatsApp
        // We presume there is a generic Admin user or a specific route

        // For demonstration, we use the Notification facade to a generic "Notifiable"
        // or a specific email set in config.

        // Create an anonymous notifiable or use a User model
        $recipient = \Illuminate\Support\Facades\Route::has('admin') ? \App\Models\User::first() : new \Illuminate\Notifications\AnonymousNotifiable;

        // Add routes
        $recipient->route('mail', config('mail.from.address'))
                  ->route('slack', config('services.slack.webhook_url'))
                  ->route('telegram', config('services.telegram.chat_id'));

        // WhatsApp is often custom channel, commonly via Twilio
        // $recipient->route(WhatsAppChannel::class, 'number');

        Notification::send($recipient, new ScrapingFailedNotification($exception->getMessage()));
    }
}
