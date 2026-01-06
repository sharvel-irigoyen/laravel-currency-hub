<?php

namespace App\Spiders;

use App\Models\ExchangeRate;
use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class CurrencySpider extends BasicSpider
{
    public array $startUrls = [
        'https://cuantoestaeldolar.pe/'
    ];

    public array $clientOptions = [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
    ];

    public function parse(Response $response): Generator
    {
        $types = [
            'Paralelo' => \App\Enums\ExchangeRateType::PARALLEL,
            'sunat' => \App\Enums\ExchangeRateType::SUNAT,
        ];

        foreach ($types as $searchText => $enumType) {
            // Protect against mixed containers (tabs)
            $forbidden = $searchText === 'sunat' ? 'Paralelo' : 'sunat';
            try {
                $rates = $this->extractRates($response, $searchText, $forbidden);

                if ($rates) {
                    ExchangeRate::create([
                        'source' => 'cuantoestaeldolar.pe',
                        'type' => $enumType,
                        'buy' => $rates['buy'],
                        'sell' => $rates['sell'],
                    ]);
                    Log::info("Scraped {$searchText}: Buy {$rates['buy']}, Sell {$rates['sell']}");

                    yield $this->item([
                        'type' => $enumType->value,
                        'buy' => $rates['buy'],
                        'sell' => $rates['sell'],
                    ]);
                } else {
                    Log::warning("Failed to extract rates for {$searchText}");
                }
            } catch (\Exception $e) {
                Log::error("Error scraping {$searchText}: " . $e->getMessage());
            }
        }
    }

    private function extractRates(Response $response, string $headerText, string $forbiddenText = ''): ?array
    {
        // Find ALL headers matching text to handle Tabs vs Rows
        $headers = $response->filterXPath("//*[contains(text(), '{$headerText}')]");

        foreach ($headers as $node) {
            $container = new Crawler($node);

            // Traverse up (max 5 levels)
            for ($i = 0; $i < 5; $i++) {
                if (!$container->count()) {
                    break;
                }

                $text = $container->text();

                // If container has forbidden text (e.g. searching for Sunat but found container with Paralelo),
                // it implies we hit a shared container/header/tab-bar. Stop this branch.
                if ($forbiddenText && str_contains($text, $forbiddenText)) {
                    // Log::debug("DEBUG [{$headerText}] Skipping level {$i} due to forbidden text '{$forbiddenText}'");
                    break;
                }

                // Extract numbers (X.XXX)
                preg_match_all('/(\d+\.\d{3})/', $text, $matches);

                if (isset($matches[1]) && count($matches[1]) >= 2) {
                    // Filter out small numbers
                    $rates = array_filter($matches[1], function($val) {
                        return floatval($val) > 1.0;
                    });
                    $rates = array_values($rates);

                if (count($rates) >= 2) {
                    $result = [
                        'buy' => $rates[0],
                        'sell' => $rates[1],
                    ];
                    Log::info("DEBUG: Found rates for {$headerText}: " . json_encode($result) . ". Container: " . substr(str_replace("\n", "", $text), 0, 150));
                    return $result;
                }
                }

                // Go up one level
                try {
                    $node = $container->getNode(0);
                    if ($node && $node->parentNode) {
                        $container = new Crawler($node->parentNode);
                    } else {
                        break;
                    }
                } catch (\Exception $e) {
                    break;
                }
            }
        }

        return null;
    }
}
