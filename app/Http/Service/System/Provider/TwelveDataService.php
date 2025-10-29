<?php

namespace App\Http\Service\System\Provider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class TwelveDataService
{
    protected string $base = 'https://api.twelvedata.com';
    public function __construct(protected string $apiKey) {}

    public function fetch(string $symbol, string $interval, ?Carbon $from = null, ?Carbon $to = null, int $outputSize = 5000): array
    {
        $params = [
            'symbol'     => $symbol,
            'interval'   => $this->toTwelveInterval($interval),
            'outputsize' => $outputSize,
            'apikey'     => $this->apiKey,
            'timezone'   => 'UTC',
            'order'      => 'asc',
        ];

        if ($from) {
            $params['start_date'] = $from->clone()->utc()->toDateTimeString();
        }
        if ($to) {
            $params['end_date'] = $to->clone()->utc()->toDateTimeString();
        }

        $resp = Http::retry(3, 500)->get($this->base.'/time_series', $params)->throw()->json();

        $values = $resp['values'] ?? [];

        return collect($values)->map(function ($v) {
            return [
                'time'   => Carbon::parse($v['datetime'], 'UTC')->getTimestampMs(),
                'open'   => $v['open'],
                'high'   => $v['high'],
                'low'    => $v['low'],
                'close'  => $v['close'],
                'volume' => $v['volume'] ?? 0,
            ];
        })->all();
    }

    protected function toTwelveInterval(string $interval): string
    {
        return match ($interval) {
            '1m' => '1min',
            '5m' => '5min',
            '15m' => '15min',
            '1h' => '1h',
            '4h' => '4h',
            '1d' => '1day',
            default => $interval,
        };
    }
}
