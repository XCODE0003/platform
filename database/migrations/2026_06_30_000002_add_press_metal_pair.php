<?php

use App\Models\Currency;
use App\Models\GroupPair;
use App\Models\Pair;
use App\Models\PairSource;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the Press Metal Aluminium Holdings (Bursa Malaysia 8869.KL) stock pair.
 *
 * The deploy pipeline runs `php artisan migrate --force` but does not run
 * seeders, so this idempotent migration applies the new pair to production.
 * It mirrors the entry added to YFinancePairsSeeder (the source of truth).
 */
return new class extends Migration
{
    private const CODE   = 'PMETAL';
    private const NAME   = 'Press Metal Aluminium Holdings';
    private const SYMBOL = '8869.KL';

    public function up(): void
    {
        $stockGroup = GroupPair::firstOrCreate(['name' => 'Stocks'], ['is_active' => true]);

        $usd = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => 'USD', 'icon' => 'USD', 'network' => '',
             'exchange_rate' => '1', 'status' => 'active', 'is_deposit' => false,
             'min_deposit_amount' => '0', 'address_regex' => '']
        );

        $cur = Currency::firstOrCreate(
            ['code' => self::CODE],
            ['name' => self::NAME, 'symbol' => self::CODE, 'icon' => self::CODE, 'network' => '',
             'exchange_rate' => '1', 'status' => 'active', 'is_deposit' => false,
             'min_deposit_amount' => '0', 'address_regex' => '']
        );

        $pair = Pair::firstOrCreate(
            ['currency_id_in' => $cur->id, 'currency_id_out' => $usd->id],
            ['group_id' => $stockGroup->id, 'is_active' => true,
             'asset_class' => 'stock', 'default_source' => 'yfinance']
        );

        PairSource::updateOrCreate(
            ['pair_id' => $pair->id, 'provider' => 'yfinance'],
            ['provider_symbol' => self::SYMBOL, 'priority' => 1, 'status' => 'valid', 'validated_at' => now()]
        );
    }

    public function down(): void
    {
        $cur = Currency::where('code', self::CODE)->first();
        if (! $cur) {
            return;
        }

        $pairs = Pair::where('currency_id_in', $cur->id)->get();
        foreach ($pairs as $pair) {
            PairSource::where('pair_id', $pair->id)->delete();
            $pair->delete();
        }
        $cur->delete();
    }
};
