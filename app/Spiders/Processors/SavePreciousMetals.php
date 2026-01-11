<?php

namespace App\Spiders\Processors;

use App\Models\PreciousMetalPrice;
use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\ItemPipeline\Processors\ItemProcessorInterface;
use Illuminate\Support\Facades\Log;

class SavePreciousMetals implements ItemProcessorInterface
{
    public function configure(array $options): void
    {
        // No configuration needed
    }

    public function processItem(ItemInterface $item): ItemInterface
    {
        try {
            PreciousMetalPrice::create([
                'metal_id' => $item->get('metal_id'),
                // 'currency' => $item->get('currency'),
                // 'unit' => $item->get('unit'),
                'bid' => $item->get('bid'),
                'ask' => $item->get('ask'),
                'change_val' => $item->get('change_val'),
                'change_percent' => $item->get('change_percent'),
                'low' => $item->get('low'),
                'high' => $item->get('high'),
                'market_time' => $item->get('market_time'),
            ]);

            Log::info("Saved Precious Metal Price: " . $item->get('metal'));
        } catch (\Exception $e) {
            Log::error("Failed to save precious metal price: " . $e->getMessage());
        }

        return $item;
    }
}
