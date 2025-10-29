<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;

final class UpdateRates
{
    public function __construct(
        private int $httpTimeoutSeconds = 5,
    ) {
    }

    public function updateAllCurrencies(): int
    {
        $updatedCount = 0;
        $currencies = Currency::query()->get();
        foreach ($currencies as $currency) {
            $code = strtoupper((string) $currency->code);
            if ($code === '') {
                continue;
            }

            // We approximate USD with USDT for Binance spot pairs
            $symbol = $code . 'USDT';
            $price = $this->fetchBinancePrice($symbol);
            if ($price === null) {
                // Skip if pair is not supported or request failed
                continue;
            }

            $currency->exchange_rate = (string) $price; // store as string to match schema
            $currency->save();
            $updatedCount++;
        }

        return $updatedCount;
    }

    public function fetchBinancePrice(string $symbol): ?float
    {
        $response = Http::timeout($this->httpTimeoutSeconds)
            ->acceptJson()
            ->get('https://api.binance.com/api/v3/ticker/price', [
                'symbol' => $symbol,
            ]);

        if (!$response->ok()) {
            return null;
        }
        $price = $response->json('price');
        if (!is_numeric($price)) {
            return null;
        }

        return (float) $price;
    }

    public function fetchTwelveDataPrice(string $symbol): ?float
    {
        $base = rtrim(config('marketdata.providers.twelvedata.base_url') ?? 'https://api.twelvedata.com', '/');
        $apiKey = (string) env('TWELVEDATA_API_KEY', '');

        $params = [
            'symbol' => $this->normalizeTwelveSymbol($symbol),
        ];
        if ($apiKey !== '') {
            $params['apikey'] = $apiKey;
        }

        $price = $this->tdPriceRequest($base, $params);
        if ($price !== null) {
            return $price;
        }

        // Fallback: if symbol with slash failed (e.g., AAPL/USD), try base-only (AAPL)
        if (isset($params['symbol']) && str_contains($params['symbol'], '/')) {
            $baseOnly = explode('/', $params['symbol'])[0] ?? '';
            if ($baseOnly !== '') {
                $params2 = $params;
                $params2['symbol'] = $baseOnly;
                $price2 = $this->tdPriceRequest($base, $params2);
                if ($price2 !== null) {
                    return $price2;
                }
            }
        }

        return null;
    }

    private function normalizeTwelveSymbol(string $symbol): string
    {
        $s = strtoupper(trim($symbol));
        if ($s === '') {
            return $s;
        }
        if (str_contains($s, '/')) {
            return $s;
        }
        $quotes = ['USDT', 'USD', 'EUR', 'BTC', 'ETH'];
        foreach ($quotes as $q) {
            $qlen = strlen($q);
            if (strlen($s) > $qlen && substr($s, -$qlen) === $q) {
                $base = substr($s, 0, -$qlen);
                if ($base !== '') {
                    $mappedQuote = $q === 'USDT' ? 'USD' : $q;
                    return $base . '/' . $mappedQuote;
                }
            }
        }
        if (strlen($s) >= 6) {
            return substr($s, 0, 3) . '/' . substr($s, 3);
        }
        return $s;
    }

    private function tdPriceRequest(string $baseUrl, array $params): ?float
    {
        $response = Http::timeout($this->httpTimeoutSeconds)
            ->acceptJson()
            ->get($baseUrl . '/price', $params);

        if (!$response->ok()) {
            return null;
        }
        $price = $response->json('price');
        if (!is_numeric($price)) {
            $price = $response->json('value');
        }
        if (!is_numeric($price)) {
            return null;
        }
        return (float) $price;
    }
}


