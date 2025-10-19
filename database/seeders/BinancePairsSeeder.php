<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Currency;
use App\Models\Pair;
use App\Models\GroupPair;
use App\Models\PairSource;

class BinancePairsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure crypto group exists
        $group = GroupPair::firstOrCreate(['name' => 'Cryptocurrencies'], ['is_active' => true]);

        // Common Binance spot USDT pairs
        $symbols = [
            'BTCUSDT','ETHUSDT','BNBUSDT','SOLUSDT','XRPUSDT','ADAUSDT','DOGEUSDT','TRXUSDT','TONUSDT','DOTUSDT',
            'MATICUSDT','LINKUSDT','LTCUSDT','BCHUSDT','ATOMUSDT','ETCUSDT','XLMUSDT','APTUSDT','OPUSDT','ARBUSDT',
            'AVAXUSDT','NEARUSDT','FILUSDT','SANDUSDT','AAVEUSDT','ICPUSDT','FTMUSDT','GALAUSDT','INJUSDT','RUNEUSDT',
            'SUIUSDT','SEIUSDT','TIAUSDT','WIFUSDT','SHIBUSDT','PEPEUSDT','JUPUSDT','PYTHUSDT','AEVOUSDT','ORDIUSDT'
        ];

        foreach ($symbols as $sym) {
            // Split into base/quote by USDT suffix
            if (!str_ends_with($sym, 'USDT')) continue;
            $base = substr($sym, 0, -4);
            $quote = 'USDT';

            $curIn = Currency::firstOrCreate(
                ['code' => $base],
                [
                    'name' => $base,
                    'symbol' => $base,
                    'icon' => $base,
                    'network' => $base,
                    'exchange_rate' => '1',
                    'status' => 'active',
                    'is_deposit' => false,
                    'min_deposit_amount' => '0',
                    'address_regex' => '',
                    'created_by' => 'system',
                    'updated_by' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $curOut = Currency::firstOrCreate(
                ['code' => $quote],
                [
                    'name' => $quote,
                    'symbol' => $quote,
                    'icon' => $quote,
                    'network' => $quote,
                    'exchange_rate' => '1',
                    'status' => 'active',
                    'is_deposit' => false,
                    'min_deposit_amount' => '0',
                    'address_regex' => '',
                    'created_by' => 'system',
                    'updated_by' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $pair = Pair::firstOrCreate([
                'currency_id_in' => $curIn->id,
                'currency_id_out' => $curOut->id,
                'group_id' => $group->id,
            ], [
                'is_active' => true,
                'asset_class' => 'crypto',
                'default_source' => 'binance',
            ]);

            PairSource::updateOrCreate([
                'pair_id' => $pair->id,
                'provider' => 'binance',
            ], [
                'provider_symbol' => $sym,
                'priority' => 1,
                'status' => 'valid',
                'validated_at' => now(),
            ]);
        }
    }
}


