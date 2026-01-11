<?php

namespace App\Jobs;

use App\Notifications\ScrapingFailedNotification;
use App\Spiders\KitcoSpider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use RoachPHP\Roach;
use Throwable;

class ScrapePreciousMetalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Run the spider
        Roach::startSpider(KitcoSpider::class);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Notification logic aligned with ScrapeCurrencyJob
        $recipient = \Illuminate\Support\Facades\Route::has('admin') ? \App\Models\User::first() : new \Illuminate\Notifications\AnonymousNotifiable;

        $recipient->route('mail', config('mail.from.address'))
                  ->route('slack', config('services.slack.webhook_url'))
                  ->route('telegram', config('services.telegram.chat_id'));

        Notification::send($recipient, new ScrapingFailedNotification(
            "Kitco Spider Failed: " . $exception->getMessage(),
            'Kitco',
            'https://www.kitco.com/price/precious-metals'
        ));
    }
}
