<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'buy' => (float) $this->buy,
            'sell' => (float) $this->sell,
            'updated_at' => $this->created_at->toIso8601String(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
