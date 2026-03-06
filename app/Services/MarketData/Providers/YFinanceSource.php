<?php

namespace App\Services\MarketData\Providers;

use App\Services\MarketData\MarketDataSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YFinanceSource implements MarketDataSource
{
    public function supports(string $assetClass): bool
    {
        return in_array($assetClass, ['stock', 'forex', 'metal', 'fiat', 'index'], true);
    }

    public function search(string $query): array
    {
        try {
            $resp = $this->client()->get($this->baseUrl() . '/search', ['q' => $query]);
            if (!$resp->ok()) return [];
            return $resp->json('results', []);
        } catch (\Throwable $e) {
            Log::warning('yfinance search error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function validate(string $symbol): bool
    {
        try {
            $resp = $this->client()->get($this->baseUrl() . '/validate', ['symbol' => $symbol]);
            if (!$resp->ok()) return false;
            return (bool) $resp->json('valid', false);
        } catch (\Throwable $e) {
            Log::warning('yfinance validate error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getBars(string $symbol, string $resolution, int $from, int $to): array
    {
        try {
            $resp = $this->client()->get($this->baseUrl() . '/bars', [
                'symbol'     => $symbol,
                'resolution' => $resolution,
                'from_ts'    => $from,
                'to_ts'      => $to,
            ]);

            if (!$resp->ok()) {
                Log::warning('yfinance getBars non-200', [
                    'symbol' => $symbol,
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                return [];
            }

            return $resp->json('bars', []);
        } catch (\Throwable $e) {
            Log::warning('yfinance getBars error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return [];
        }
    }

    private function client()
    {
        return Http::timeout(20)->withOptions(['verify' => false]);
    }

    private function baseUrl(): string
    {
        return rtrim(env('YFINANCE_BASE_URL', 'http://127.0.0.1:8001'), '/');
    }
}
