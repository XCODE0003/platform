<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\Pair;
use App\Models\GroupPair;
use App\Models\PairSource;

class NetAppPairsSeeder extends Seeder
{
    public function run(): void
    {
        $stockGroup = GroupPair::firstOrCreate(['name' => 'Stocks'], ['is_active' => true]);

        $usd = $this->currency('USD', 'US Dollar');

        // ── NetApp ────────────────────────────────────────────────────────────
        // [code, name, yfinance_symbol]
        $stocks = [
            ['NTAP', 'NetApp, Inc.', 'NTAP'],
        ];

        foreach ($stocks as [$code, $name, $yfSym]) {
            $cur  = $this->currency($code, $name);
            $pair = Pair::firstOrCreate(
                ['currency_id_in' => $cur->id, 'currency_id_out' => $usd->id],
                ['group_id' => $stockGroup->id, 'is_active' => true, 'asset_class' => 'stock', 'default_source' => 'yfinance']
            );
            PairSource::updateOrCreate(
                ['pair_id' => $pair->id, 'provider' => 'yfinance'],
                ['provider_symbol' => $yfSym, 'priority' => 1, 'status' => 'valid', 'validated_at' => now()]
            );
        }

        $this->command->info('NetApp (NTAP) pair seeded successfully.');
    }

    private function currency(string $code, string $name): Currency
    {
        return Currency::firstOrCreate(
            ['code' => $code],
            [
                'name'               => $name,
                'symbol'             => $code,
                'icon'               => $code,
                'network'            => '',
                'exchange_rate'      => '1',
                'status'             => 'active',
                'is_deposit'         => false,
                'min_deposit_amount' => '0',
                'address_regex'      => '',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]
        );
    }
}
