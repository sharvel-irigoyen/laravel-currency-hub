<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ExchangeRateResource;

class ExchangeRateController extends Controller
{
    /**
     * Get the latest exchange rate.
     */
    public function latest(Request $request): ExchangeRateResource|JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $type = $request->input('type', 'parallel');

        $rate = ExchangeRate::whereDate('created_at', $date)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$rate) {
            abort(404, 'No data available');
        }

        return new ExchangeRateResource($rate);
    }
}
