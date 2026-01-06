<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExchangeRateController;

// Auth with Sanctum (Token)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/exchange-rate', [ExchangeRateController::class, 'latest']);
});

// For testing purposes, maybe a public route or just keep it secure as requested.
// "exponerlos via api con token" -> Secure.
