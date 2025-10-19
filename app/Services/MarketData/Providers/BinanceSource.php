<?php

namespace App\Services\MarketData\Providers;

use App\Services\MarketData\MarketDataSource;
use Illuminate\Support\Facades\Http;

class BinanceSource implements MarketDataSource
{
    public function supports(string $assetClass): bool
    {
        return $assetClass === 'crypto';
    }

    public function search(string $query): array
    {
        // Minimal stub: could call /exchangeInfo and filter symbols
        return [];
    }

    public function validate(string $symbol): bool
    {
        $url = 'https://api.binance.com/api/v3/exchangeInfo';
        $resp = $this->client()->get($url, ['symbol' => $symbol]);
        if (!$resp->ok()) return false;
        $data = $resp->json();
        return isset($data['symbols'][0]['symbol']) && $data['symbols'][0]['symbol'] === $symbol;
    }

    public function getBars(string $symbol, string $resolution, int $from, int $to): array
    {
        $interval = $this->mapResolution($resolution);
        $url = 'https://api.binance.com/api/v3/klines';
        $resp = $this->client()->get($url, [
            'symbol' => $symbol,
            'interval' => $interval,
            'startTime' => $from * 1000,
            'endTime' => $to * 1000,
            'limit' => 1000,
        ]);

        if (!$resp->ok()) return [];
        $rows = $resp->json();
        return array_map(function ($k) {
            return [
                'time' => $k[0],
                'open' => (float)$k[1],
                'high' => (float)$k[2],
                'low' => (float)$k[3],
                'close' => (float)$k[4],
                'volume' => (float)$k[5],
            ];
        }, $rows);
    }

    private function mapResolution(string $res): string
    {
        $map = [
            '1' => '1m','3' => '3m','5' => '5m','15' => '15m','30' => '30m',
            '60' => '1h','120' => '2h','240' => '4h','1D' => '1d'
        ];
        return $map[$res] ?? '1m';
    }

    private function client()
    {
        $http = Http::timeout(15);
        $disableVerify = app()->environment('local') || env('BINANCE_VERIFY_SSL', 'true') === 'false';
        if ($disableVerify) {
            $http = $http->withOptions([
                'verify' => false,
                'proxy' => null,
            ]);
        }
        return $http;
    }
}


