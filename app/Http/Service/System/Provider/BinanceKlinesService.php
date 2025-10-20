<?php

namespace App\Http\Service\System\Provider;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class BinanceKlinesService
{
    protected string $base = 'https://api.binance.com';

    public function fetch(string $symbol, string $interval, ?Carbon $from = null, ?Carbon $to = null, int $limit = 1000): array
    {
        $params = [
            'symbol'   => strtoupper($symbol),
            'interval' => $interval,
            'limit'    => $limit,
        ];

        if ($from) {
            $params['startTime'] = $from->clone()->utc()->getTimestampMs();
        }
        if ($to) {
            $params['endTime'] = $to->clone()->utc()->getTimestampMs();
        }

        $resp = Http::retry(3, 500)->get($this->base.'/api/v3/klines', $params)->throw();
        $data = $resp->json();

        return collect($data)->map(function ($k) {
            return [
                'time'   => $k[0],
                'open'   => $k[1],
                'high'   => $k[2],
                'low'    => $k[3],
                'close'  => $k[4],
                'volume' => $k[5],
            ];
        })->all();
    }
}
