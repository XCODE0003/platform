<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataProvider;

class DataProvidersSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'binance', 'name' => 'Binance', 'asset_classes' => ['crypto'], 'base_url' => 'https://api.binance.com', 'enabled' => true],
            ['code' => 'twelvedata', 'name' => 'TwelveData', 'asset_classes' => ['stock','forex','metal','fiat'], 'base_url' => 'https://api.twelvedata.com', 'enabled' => true],
            ['code' => 'finnhub', 'name' => 'Finnhub', 'asset_classes' => ['stock','forex','metal'], 'base_url' => 'https://finnhub.io', 'enabled' => true],
            ['code' => 'exchangeratehost', 'name' => 'ExchangeRate Host', 'asset_classes' => ['forex','fiat'], 'base_url' => 'https://api.exchangerate.host', 'enabled' => true],
        ];

        foreach ($items as $item) {
            DataProvider::updateOrCreate(['code' => $item['code']], [
                'name' => $item['name'],
                'asset_classes' => $item['asset_classes'],
                'base_url' => $item['base_url'],
                'enabled' => $item['enabled'],
            ]);
        }
    }
}


