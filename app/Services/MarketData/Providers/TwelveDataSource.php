<?php

namespace App\Services\MarketData\Providers;

use App\Services\MarketData\MarketDataSource;
use Illuminate\Support\Facades\Http;

class TwelveDataSource implements MarketDataSource
{
    public function supports(string $assetClass): bool
    {
        return in_array($assetClass, ['stock','forex','metal','fiat'], true);
    }

    public function search(string $query): array
    {
        return [];
    }

    public function validate(string $symbol): bool
    {
        $url = $this->baseUrl().'/price';
        $resp = $this->client()->get($url, [
            'symbol' => $symbol,
        ]);
        if (!$resp->ok()) return false;
        $data = $resp->json();
        return isset($data['price']) || isset($data['value']);
    }

    public function getBars(string $symbol, string $resolution, int $from, int $to): array
    {
        $interval = $this->mapResolution($resolution);
        // TwelveData time_series supports start_date/end_date for historical
        $url = $this->baseUrl().'/time_series';
        $params = [
            'symbol' => $symbol,
            'interval' => $interval,
            'start_date' => gmdate('Y-m-d', $from),
            'end_date' => gmdate('Y-m-d', $to),
            'outputsize' => 5000,
            'order' => 'asc',
        ];
        $resp = $this->client()->get($url, $params);
        if (!$resp->ok()) return [];
        $data = $resp->json();
        $values = $data['values'] ?? [];
        if (!is_array($values)) return [];
        $bars = [];
        foreach ($values as $row) {
            $dt = $row['datetime'] ?? null;
            if (!$dt) continue;
            $ts = strtotime($dt.' UTC');
            if ($ts === false) continue;
            $bars[] = [
                'time' => $ts * 1000,
                'open' => isset($row['open']) ? (float)$row['open'] : (float)($row['close'] ?? 0),
                'high' => isset($row['high']) ? (float)$row['high'] : (float)($row['close'] ?? 0),
                'low' => isset($row['low']) ? (float)$row['low'] : (float)($row['close'] ?? 0),
                'close' => isset($row['close']) ? (float)$row['close'] : 0.0,
                'volume' => isset($row['volume']) ? (float)$row['volume'] : 0.0,
            ];
        }
        usort($bars, fn($a,$b)=>$a['time']<=>$b['time']);
        return $bars;
    }

    private function mapResolution(string $res): string
    {
        $map = [
            '1' => '1min',
            '3' => '3min',
            '5' => '5min',
            '15' => '15min',
            '30' => '30min',
            '60' => '1h',
            '120' => '2h',
            '240' => '4h',
            '1D' => '1day',
            'D' => '1day',
            '1440' => '1day',
        ];
        return $map[$res] ?? '1day';
    }

    private function client()
    {
        $http = Http::timeout(15);
        $disableVerify = app()->environment('local') || env('TWELVEDATA_VERIFY_SSL', 'true') === 'false';
        if ($disableVerify) {
            $http = $http->withOptions(['verify' => false, 'proxy' => null]);
        }
        $apiKey = env('TWELVEDATA_API_KEY', 'demo');
        // Prefer header auth
        return $http->withHeaders(['Authorization' => 'apikey '.$apiKey]);
    }

    private function baseUrl(): string
    {
        return rtrim(config('marketdata.providers.twelvedata.base_url') ?? 'https://api.twelvedata.com', '/');
    }
}


