<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PreciousMetalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 1. Determine Unit (Default GRAM)
        $requestedUnit = strtoupper($request->input('unit', 'GRAM'));
        $isOz = $requestedUnit === 'OZ';

        // 2. Determine Purity Factor (Default 1.0 / 24K / Fine)
        $purityInput = strtoupper($request->input('purity', ''));

        // Map of standard commercial purities to decimal factor
        $purityMap = [
            // Gold Karats
            '24K' => 1.0,
            '22K' => 0.9167,
            '18K' => 0.750,
            '14K' => 0.5833,
            '10K' => 0.4167,

            // Millesimal Fineness (Silver, Platinum, etc.)
            '999'      => 0.999,
            'FINE'     => 0.999,
            '958'      => 0.958,
            'BRITANNIA'=> 0.958,
            '950'      => 0.950,
            '925'      => 0.925,
            'STERLING' => 0.925,
            '900'      => 0.900,
            'COIN'     => 0.900,
            '850'      => 0.850,
            '500'      => 0.500,
        ];

        $purityFactor = $purityMap[$purityInput] ?? 1.0;

        // 3. Conversion Factors
        $gramFactor = $this->metal->conversion_factor_to_gram ?? 31.1034768;

        // Helper: (Value * Purity) / UnitFactor
        $format = function($val) use ($isOz, $gramFactor, $purityFactor) {
            $val = (float)$val;

            // Apply Purity first (e.g. 14k is 58% the price of 24k)
            $val = $val * $purityFactor;

            // Apply Unit conversion
            if (!$isOz) {
                $val = $val / $gramFactor;
            }
            return round($val, 2);
        };

        return [
            'metal' => $this->metal->name,
            'currency' => 'USD',
            'unit' => $isOz ? 'OZ' : 'GRAM',
            'purity' => $purityInput ?: 'STANDARD', // Inform client of used purity
            'bid' => $format($this->bid),
            'ask' => $format($this->ask),
            'change' => $format($this->change_val),
            'change_percent' => round((float)$this->change_percent, 2),
            'low' => $format($this->low),
            'high' => $format($this->high),
            'date' => $this->market_time->format('Y-m-d'),
            'time' => $this->market_time->format('H:i'),
            'timestamp' => $this->market_time->toIso8601String(),
        ];
    }
}
