<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\GroupPair;
use App\Models\Pair;
use App\Models\PairSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommoditiesPairsSeeder extends Seeder
{
    public function run(): void
    {
        $group = GroupPair::firstOrCreate(
            ['name' => 'Commodities'],
            ['is_active' => true]
        );

        $usd = $this->currency('USD', 'US Dollar');

        // [code, name, yfinance_symbol, pricescale_hint]
        $commodities = [
            // ── Energy ────────────────────────────────────────────────────────
            ['USOIL',  'WTI Crude Oil', 'CL=F'],
            ['UKOIL',  'Brent Oil',     'BZ=F'],
            ['NATGAS', 'Natural Gas',      'NG=F'],
            ['HTOIL',  'Heating Oil',      'HO=F'],
            ['GASOL',  'Gasoline (RBOB)',  'RB=F'],
            // ── Coal ──────────────────────────────────────────────────────────
            ['COAL',   'Coal (ETF: KOL)',  'KOL'],
            // ── Agricultural ──────────────────────────────────────────────────
            ['WHEAT',  'Wheat',            'ZW=F'],
            ['CORN',   'Corn',             'ZC=F'],
            ['SOYBN',  'Soybeans',         'ZS=F'],
            ['COFEE',  'Coffee',           'KC=F'],
            ['SUGAR',  'Sugar',            'SB=F'],
            ['COTTO',  'Cotton',           'CT=F'],
            // ── Other ─────────────────────────────────────────────────────────
            ['LUMOIL', 'Lumber',           'LBS=F'],
            ['COCOA',  'Cocoa',            'CC=F'],
            ['LIVCAT', 'Live Cattle',      'LE=F'],
        ];

        foreach ($commodities as [$code, $name, $yfSym]) {
            $cur = $this->currency($code, $name);

            $pair = Pair::firstOrCreate(
                ['currency_id_in' => $cur->id, 'currency_id_out' => $usd->id],
                [
                    'group_id'       => $group->id,
                    'is_active'      => true,
                    'asset_class'    => 'commodity',
                    'default_source' => 'yfinance',
                ]
            );

            // Keep group up to date if pair already existed
            if ($pair->group_id !== $group->id) {
                $pair->update(['group_id' => $group->id]);
            }

            PairSource::updateOrCreate(
                ['pair_id' => $pair->id, 'provider' => 'yfinance'],
                [
                    'provider_symbol' => $yfSym,
                    'priority'        => 1,
                    'status'          => 'valid',
                    'validated_at'    => now(),
                ]
            );
        }

        // Display labels for oil — currency `code` stays as USOIL/UKOIL
        // (FK target), but the human-facing symbol/name are friendlier.
        DB::table('currencies')->where('code', 'USOIL')->update([
            'name'   => 'WTI Crude Oil',
            'symbol' => 'WTI Crude Oil',
        ]);
        DB::table('currencies')->where('code', 'UKOIL')->update([
            'name'   => 'Brent Oil',
            'symbol' => 'Brent Oil',
        ]);

        $this->command->info('Commodities pairs seeded: ' . count($commodities) . ' instruments.');
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
