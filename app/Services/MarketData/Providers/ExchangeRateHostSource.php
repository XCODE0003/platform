<?php

namespace App\Services\MarketData\Providers;

use App\Services\MarketData\MarketDataSource;
use Illuminate\Support\Facades\Http;

class ExchangeRateHostSource implements MarketDataSource
{
    public function supports(string $assetClass): bool
    {
        return in_array($assetClass, ['forex', 'fiat'], true);
    }

    public function search(string $query): array
    {
        return [];
    }

    public function validate(string $symbol): bool
    {
        // Basic format validation for 6-letter FX like EURUSD or with slash
        $s = strtoupper($symbol);
        if (strpos($s, '/') !== false) {
            [$from, $to] = explode('/', $s, 2);
            return strlen($from) === 3 && strlen($to) === 3;
        }
        return strlen($s) === 6;
    }

    public function getBars(string $symbol, string $resolution, int $from, int $to): array
    {
        // Поддерживаем D1 (интрадей можно добавить ресемплингом)
        if (!in_array($resolution, ['1D', 'D', '1440'], true)) {
            $resolution = '1D';
        }

        [$base, $quote] = $this->splitSymbol($symbol);
        $start = gmdate('Y-m-d', $from);
        $end   = gmdate('Y-m-d', $to);

        $http = Http::timeout(15);
        $disableVerify = app()->environment('local') || env('EXCHANGERATE_VERIFY_SSL', 'true') === 'false';
        if ($disableVerify) {
            $http = $http->withOptions(['verify' => false, 'proxy' => null]);
        }

        // Попытка 1: exchangerate.host /timeseries (rates)
        $urlTimeseries = $this->buildTimeseriesUrl($base, $quote, $start, $end);
        $resp = $http->get($urlTimeseries);
        if ($resp->ok()) {
            $data = $resp->json();
            if (!empty($data['rates'])) {
                return $this->parseRatesTimeseries($data['rates'], $base, $quote);
            }
        }

        // Попытка 2: apilayer-стиль /timeframe (quotes)
        $urlTimeframe = $this->buildTimeframeUrl($base, $quote, $start, $end);
        $resp2 = $http->get($urlTimeframe);
        if ($resp2->ok()) {
            $data2 = $resp2->json();
            if (!empty($data2['quotes'])) {
                return $this->parseQuotesTimeframe($data2['quotes'], $base, $quote, $data2['source'] ?? 'USD');
            }
        }

        // Попытка 3: historical по дням (quotes) — крайний случай
        try {
            $bars = [];
            $cur = new DateTimeImmutable($start, new DateTimeZone('UTC'));
            $toD = new DateTimeImmutable($end, new DateTimeZone('UTC'));
            while ($cur <= $toD) {
                $urlHist = $this->buildHistoricalUrl($base, $quote, $cur->format('Y-m-d'));
                $r = $http->get($urlHist);
                if ($r->ok()) {
                    $d = $r->json();
                    if (!empty($d['quotes'])) {
                        $bars = array_merge($bars, $this->parseQuotesTimeframe([$cur->format('Y-m-d') => $d['quotes']], $base, $quote, $d['source'] ?? 'USD'));
                    }
                }
                $cur = $cur->modify('+1 day');
            }
            usort($bars, fn($a,$b) => $a['time'] <=> $b['time']);
            return $bars;
        } catch (\Throwable) {
            return [];
        }
    }

    private function parseRatesTimeseries(array $rates, string $base, string $quote): array
    {
        $out = [];
        foreach ($rates as $date => $obj) {
            $rate = $obj[$quote] ?? ($obj[strtoupper($base.$quote)] ?? null);
            if ($rate === null) continue;
            $ts = strtotime($date.' 00:00:00 UTC') * 1000;
            $p = (float)$rate;
            $out[] = ['time'=>$ts,'open'=>$p,'high'=>$p,'low'=>$p,'close'=>$p,'volume'=>0.0];
        }
        usort($out, fn($a,$b)=>$a['time']<=>$b['time']);
        return $out;
    }

    private function parseQuotesTimeframe(array $quotes, string $base, string $quote, string $source): array
    {
        $key = strtoupper(($source ?: 'USD').$quote);
        $out = [];
        foreach ($quotes as $date => $obj) {
            // Если source != base — нужен кросс (базово: BASE/QUOTE = (BASE/SOURCE) / (QUOTE/SOURCE)), упростим до ключа SOURCE+QUOTE
            $rate = $obj[$key] ?? null;
            if ($rate === null) continue;
            $ts = strtotime($date.' 00:00:00 UTC') * 1000;
            $p = (float)$rate;
            $out[] = ['time'=>$ts,'open'=>$p,'high'=>$p,'low'=>$p,'close'=>$p,'volume'=>0.0];
        }
        usort($out, fn($a,$b)=>$a['time']<=>$b['time']);
        return $out;
    }

    private function buildTimeframeUrl(string $base, string $quote, string $start, string $end): string
    {
        $baseUrl = config('marketdata.providers.exchangeratehost.base_url') ?? 'https://api.exchangerate.host';
        $u = rtrim($baseUrl, '/').'/timeframe';
        $q = [
            'start_date' => $start,
            'end_date'   => $end,
            // для apilayer-модели source по умолчанию USD; можно выставить source=$base и затем приводить ключи
            'source'     => 'USD',
            'currencies' => $quote,
        ];
        $query = http_build_query($q);
        if ($key = env('EXCHANGERATE_ACCESS_KEY')) $query .= '&access_key='.urlencode($key);
        return $u.'?'.$query;
    }

    private function buildHistoricalUrl(string $base, string $quote, string $date): string
    {
        $baseUrl = config('marketdata.providers.exchangeratehost.base_url') ?? 'https://api.exchangerate.host';
        $u = rtrim($baseUrl, '/').'/historical';
        $q = ['date'=>$date, 'source'=>'USD', 'currencies'=>$quote];
        $query = http_build_query($q);
        if ($key = env('EXCHANGERATE_ACCESS_KEY')) $query .= '&access_key='.urlencode($key);
        return $u.'?'.$query;
    }

    private function splitSymbol(string $symbol): array
    {
        $s = strtoupper($symbol);
        if (strpos($s, '/') !== false) {
            [$from, $to] = explode('/', $s, 2);
            return [substr($from, 0, 3), substr($to, 0, 3)];
        }
        if (strlen($s) >= 6) {
            return [substr($s, 0, 3), substr($s, 3, 3)];
        }
        return [$s, 'USD'];
    }

    private function buildTimeseriesUrl(string $base, string $quote, string $start, string $end): string
    {
        $baseUrl = config('marketdata.providers.exchangeratehost.base_url') ?? 'https://api.exchangerate.host';
        // exchangerate.host uses /timeseries; some docs call it /timeframe
        $u = rtrim($baseUrl, '/') . '/timeseries';
        $query = http_build_query([
            'start_date' => $start,
            'end_date' => $end,
            'base' => $base,
            'symbols' => $quote,
        ]);
        $access = env('EXCHANGERATE_ACCESS_KEY');
        if ($access) {
            $query .= '&access_key=' . urlencode($access);
        }
        return $u . '?' . $query;
    }
}


