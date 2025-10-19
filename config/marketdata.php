<?php

return [
    'providers' => [
        'binance' => [
            'class' => App\Services\MarketData\Providers\BinanceSource::class,
            'supports' => ['crypto'],
        ],
        'twelvedata' => [
            'class' => App\Services\MarketData\Providers\TwelveDataSource::class,
            'supports' => ['stock','forex','metal','fiat'],
        ],
        'finnhub' => [
            'class' => App\Services\MarketData\Providers\FinnhubSource::class,
            'supports' => ['stock','forex','metal'],
        ],
        'exchangeratehost' => [
            'class' => App\Services\MarketData\Providers\ExchangeRateHostSource::class,
            'supports' => ['forex','fiat'],
            'base_url' => env('EXCHANGERATE_BASE_URL', 'https://api.exchangerate.host'),
        ],
    ],
];


