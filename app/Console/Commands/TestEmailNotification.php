<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ScrapingFailedNotification;

class TestEmailNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email : The recipient email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test ScrapingFailedNotification to the specified email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $errorMessage = "Simulated Error: Connection timeout while trying to reach cuantoestaeldolar.pe at " . now();

        $this->info("Sending test notification to: {$email}...");

        try {
            Notification::route('mail', $email)
                ->notify(new ScrapingFailedNotification($errorMessage));

            $this->info("✅ Notification sent successfully (queued or sent depending on config)!");
            $this->comment("Check your inbox (or Mailtrap/Log depending on MAIL_MAILER).");
        } catch (\Exception $e) {
            $this->error("❌ Failed to send notification: " . $e->getMessage());
        }
    }
}
