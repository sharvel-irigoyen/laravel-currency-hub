<?php

namespace App\Spiders;

use App\Spiders\Processors\SavePreciousMetals;
use Generator;
use RoachPHP\Http\Request;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;

class KitcoSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.kitco.com/price/precious-metals'
    ];

    public array $itemProcessors = [
        SavePreciousMetals::class,
    ];

    public function parse(Response $response): Generator
    {
        // ... (Market status logic remains same, but let's just keep the core parsing part here to handle the "0 tables" issue)

        // 1. Check Market Status
        // The "MARKET IS OPEN" text is sometimes missing or changed in the DOM.
        // We default to TRUE (try to scrape) unless we explicitly see "CLOSED".
        $statusNode = $response->filterXPath("//*[contains(text(), 'MARKET IS')]");
        $isMarketOpen = true; // Default to TRUE to ensure we scrape if layout changes

        if ($statusNode->count() > 0) {
            $text = strtoupper($statusNode->text());
            if (str_contains($text, 'CLOSED')) {
                 $isMarketOpen = false;
                 // Double check "Will OPEN in" to confirm it's actually closed
                 $willOpenNode = $response->filterXPath("//*[contains(text(), 'Will OPEN in')]");
                 if ($willOpenNode->count() > 0) {
                     Log::info("KitcoSpider: Market explicitly CLOSED (found 'Will OPEN in').");
                     $isMarketOpen = false;
                 }
            }
        } else {
             // If we can't find the status, we assume it's valid to scrape so we don't miss data.
             Log::warning("KitcoSpider: Could not find 'MARKET IS' status text. Assuming market is OPEN/Scrapeable.");
        }

        // Logic for "scrape if DB empty"
        if (!$isMarketOpen) {
            $hasData = \App\Models\PreciousMetalPrice::exists();
            if ($hasData) {
                 Log::info("KitcoSpider: Market is closed and data exists. Skipping.");
                 return yield from [];
            }
            Log::info("KitcoSpider: Market is closed but NO data exists. Force scraping.");
        }

        // 5. JSON Extraction Strategy (Robust)
        // Parse raw HTML body to find the keys, avoiding DOM traversal issues (script content empty)
        $html = $response->getBody();
        $json = null;

        // Find standard Next.js data using string functions to avoid regex limits on huge JSON
        $search = '<script id="__NEXT_DATA__" type="application/json">';
        $start = strpos($html, $search);

        if ($start !== false) {
            $start += strlen($search);
            $end = strpos($html, '</script>', $start);

            if ($end !== false) {
                $jsonStr = substr($html, $start, $end - $start);

                try {
                    $decoded = json_decode($jsonStr, true);
                    if ($decoded && isset($decoded['props'])) {
                        $json = $decoded;
                    }
                } catch (\Exception $e) {
                    Log::error("JSON string decode failed: " . $e->getMessage());
                }
            }
        }

        if ($json) {
            try {
                // We need to find the "queries" or "state" where the data resides.
                // Based on grep: props -> pageProps -> dehydratedState -> queries -> [ ... state -> data ... ]

                $queries = $json['props']['pageProps']['dehydratedState']['queries'] ?? [];

                $processedMetals = [];

                // Helper to extract from a result object
                $extract = function($metalName, $data) use (&$processedMetals) {
                    $metalKey = strtoupper($metalName);
                    if (isset($processedMetals[$metalKey])) return;

                    $result = $data['results'][0] ?? null;
                    if (!$result) return;

                    $bid = $result['bid'] ?? 0;
                    $ask = $result['ask'] ?? $bid; // Fallback to bid if ask is missing (common in some widgets)
                    $change = $result['change'] ?? 0;
                    $changePct = $result['changePercentage'] ?? 0;
                    $low = $result['low'] ?? 0;
                    $high = $result['high'] ?? 0;

                    // Time
                    $marketTime = now()->format('Y-m-d H:i:s');
                    if (isset($result['originalTime'])) {
                        // Format from JSON: "2026-01-09T17:00:00Z"
                        // Issue: The JSON says 'Z' (UTC), but the website displays this same time as EST (e.g. 17:00).
                        // If we parse as UTC and convert to local (Lima/EST), 17:00 becomes 12:00.
                        // We need to treat the string as "Value to Display" rather than "Absolute UTC time".
                        // Strategy: Remove 'Z' and parse without timezone shift, or assign App timezone directly.

                        try {
                            // Take "2026-01-09T17:00:00" part only
                            $cleanTime = substr($result['originalTime'], 0, 19);
                            $marketTime = \Illuminate\Support\Carbon::parse($cleanTime)->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {}
                    }

                    if ($bid > 0) {
                        // Resolve Metal ID
                        $metalModel = \App\Models\PreciousMetal::firstOrCreate(
                            ['name' => $metalKey],
                            ['symbol' => substr($metalKey, 0, 2)] // Simple fallback for symbol
                        );

                         yield $this->item([
                             'metal_id' => $metalModel->id,
                             // 'currency' => 'USD', // Removed from DB
                             // 'unit' => 'OZ',
                             'bid' => (float)$bid,
                             'ask' => (float)$ask,
                             'change_val' => (float)$change,
                             'change_percent' => (float)$changePct,
                             'low' => (float)$low,
                             'high' => (float)$high,
                             'market_time' => $marketTime,
                         ]);
                         $processedMetals[$metalKey] = true;
                    }
                };

                foreach ($queries as $query) {
                    $data = $query['state']['data'] ?? [];

                    // Check for Rhodium structure: "rhodium": { ... }
                    if (isset($data['rhodium'])) {
                        yield from $extract('Rhodium', $data['rhodium']);
                    }

                    // Check for detailed data (lowercase keys: gold, silver, platinum, palladium)
                    // These contain full Ask, Low, High, etc.
                    foreach (['gold', 'silver', 'platinum', 'palladium'] as $m) {
                        if (isset($data[$m])) {
                             yield from $extract(ucfirst($m), $data[$m]);
                        }
                    }

                    // Fallback: Check for Widget structure (Capitalized keys) if separate
                    foreach (['Gold', 'Silver', 'Platinum', 'Palladium'] as $m) {
                        if (isset($data[$m])) {
                             yield from $extract($m, $data[$m]);
                        }
                    }
                }

                // If we found metals, we are done.
                if (!empty($processedMetals)) {
                    return;
                }

                // CRITICAL: No metals found despite JSON parsing. This is a scraping failure.
                throw new \Exception("JSON found but no metals matched in queries. Structure might have changed.");

            } catch (\Exception $e) {
                // Re-throw to trigger Job Failure & Notification
                throw new \Exception("JSON Parsing Error: " . $e->getMessage());
            }
        } else {
             // CRITICAL: No data script found.
             throw new \Exception("No __NEXT_DATA__ script found on Kitco page.");
        }

        // Fallback or exit?
        // If we reached here, something went wrong.
    }

    protected function initialRequests(): array
    {
        return [
            new Request(
                'GET',
                $this->startUrls[0],
                [$this, 'parse'],
                [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    ],
                ]
            ),
        ];
    }

    private function parseMoney(string $value): float
    {
        return (float) str_replace(',', '', $value);
    }
}
