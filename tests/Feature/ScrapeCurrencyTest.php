<?php

use App\Jobs\ScrapeCurrencyJob;
use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scrapes currency data and stores it in database', function () {
    // Execute the job synchronously
    (new ScrapeCurrencyJob)->handle();

    // Verify record was created
    expect(ExchangeRate::count())->toBeGreaterThan(0);

    // Get the latest record
    $rate = ExchangeRate::latest()->first();

    // Validate structure and values
    expect($rate->source)->toBe('cuantoestaeldolar.pe')
        ->and($rate->buy)->toBeNumeric()
        ->and($rate->buy)->toBeGreaterThan(1.0)
        ->and($rate->sell)->toBeNumeric()
        ->and($rate->sell)->toBeGreaterThan(1.0);
});
