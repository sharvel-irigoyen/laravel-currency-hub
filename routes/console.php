<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use App\Jobs\ScrapeCurrencyJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ScrapeCurrencyJob)->dailyAt('08:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
