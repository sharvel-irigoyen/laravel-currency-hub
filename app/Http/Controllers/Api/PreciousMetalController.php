<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PreciousMetalController extends Controller
{
    /**
     * List all precious metal prices.
     *
     * Returns the latest available price for every supported metal.
     */
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $metals = \App\Models\PreciousMetal::all();
        $latestPrices = collect();

        foreach ($metals as $metal) {
            $price = \App\Models\PreciousMetalPrice::where('metal_id', $metal->id)
                ->orderBy('market_time', 'desc')
                ->with('metal')
                ->first();

            if ($price) {
                $latestPrices->push($price);
            }
        }

        return \App\Http\Resources\PreciousMetalResource::collection($latestPrices);
    }

    /**
     * Get a specific precious metal price.
     *
     * Retrieve the latest price or filtered historical prices for a specific precious metal.
     * Supports Purity (Fineness) calculation and Unit conversion.
     *
     * @urlParam metal string required The name of the metal (e.g., GOLD). Example: GOLD
     */
    public function show(\App\Http\Requests\ShowPreciousMetalRequest $request, \App\Enums\PreciousMetalType $metal): \App\Http\Resources\PreciousMetalResource
    {
        $metalName = $metal->value;
        $metalModel = \App\Models\PreciousMetal::where('name', $metalName)->first();

        if (!$metalModel) {
            abort(404, "Metal not supported: $metalName");
        }

        $query = \App\Models\PreciousMetalPrice::query()
            ->with('metal')
            ->where('metal_id', $metalModel->id);

        $validated = $request->validated();
        $dateParam = $validated['date'] ?? null;
        $timeParam = $validated['time'] ?? null;

        if ($dateParam) {
            $query->whereDate('market_time', $dateParam);

            if ($timeParam) {
                if (strpos($timeParam, ':') !== false) {
                    $target = "$dateParam $timeParam:00";
                    $query->orderByRaw("ABS(TIMESTAMPDIFF(SECOND, market_time, ?)) ASC", [$target]);
                    $record = $query->first();

                    if (!$record) abort(404, 'No price found for specified time.');
                    return new \App\Http\Resources\PreciousMetalResource($record);

                } else {
                    $query->whereRaw("HOUR(market_time) = ?", [$timeParam])
                          ->orderBy('market_time', 'desc');
                    $record = $query->first();

                    if (!$record) abort(404, 'No price found for specified hour.');
                    return new \App\Http\Resources\PreciousMetalResource($record);
                }
            } else {
                $query->orderBy('market_time', 'desc');
                $record = $query->first();

                if (!$record) abort(404, 'No price found for specified date.');
                return new \App\Http\Resources\PreciousMetalResource($record);
            }
        }

        $latest = $query->orderBy('market_time', 'desc')->first();

        if (!$latest) {
             abort(404, 'Price not found.');
        }

        return new \App\Http\Resources\PreciousMetalResource($latest);
    }
}
