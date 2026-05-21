<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Rename COFEE → COFFEE ─────────────────────────────────────
        DB::table('currencies')->where('code', 'COFEE')->update([
            'code'   => 'COFFEE',
            'name'   => 'Coffee',
            'symbol' => 'COFFEE',
            'icon'   => 'COFFEE',
        ]);

        // ─── Rename COTTO → COTTON ─────────────────────────────────────
        DB::table('currencies')->where('code', 'COTTO')->update([
            'code'   => 'COTTON',
            'name'   => 'Cotton',
            'symbol' => 'COTTON',
            'icon'   => 'COTTON',
        ]);

        // ─── LUMOIL: LBS=F (delisted Mar 2023) → LBR=F (active) ─────────
        $lumoilPairId = $this->pairIdForCurrencyCode('LUMOIL');
        if ($lumoilPairId) {
            DB::table('pair_sources')
                ->where('pair_id', $lumoilPairId)
                ->where('provider', 'yfinance')
                ->update([
                    'provider_symbol' => 'LBR=F',
                    'status'          => 'valid',
                    'validated_at'    => now(),
                ]);
        }

        // ─── COAL: KOL ETF (delisted Dec 2020) → BTU (Peabody Energy) ──
        $coalPairId = $this->pairIdForCurrencyCode('COAL');
        if ($coalPairId) {
            DB::table('pair_sources')
                ->where('pair_id', $coalPairId)
                ->where('provider', 'yfinance')
                ->update([
                    'provider_symbol' => 'BTU',
                    'status'          => 'valid',
                    'validated_at'    => now(),
                ]);
        }
        DB::table('currencies')->where('code', 'COAL')->update([
            'name' => 'Coal (Peabody)',
        ]);

        // ─── Clear any stale per-pair subscriptions so quotes:yfinance
        //     reloads with the new provider symbols on its next sync. ──
        if ($lumoilPairId || $coalPairId) {
            DB::table('quote_subscriptions')
                ->whereIn('pair_id', array_filter([$lumoilPairId, $coalPairId]))
                ->delete();
        }
    }

    public function down(): void
    {
        DB::table('currencies')->where('code', 'COFFEE')->update([
            'code'   => 'COFEE',
            'symbol' => 'COFEE',
            'icon'   => 'COFEE',
        ]);
        DB::table('currencies')->where('code', 'COTTON')->update([
            'code'   => 'COTTO',
            'symbol' => 'COTTO',
            'icon'   => 'COTTO',
        ]);

        $lumoilPairId = $this->pairIdForCurrencyCode('LUMOIL');
        if ($lumoilPairId) {
            DB::table('pair_sources')
                ->where('pair_id', $lumoilPairId)
                ->where('provider', 'yfinance')
                ->update(['provider_symbol' => 'LBS=F']);
        }

        $coalPairId = $this->pairIdForCurrencyCode('COAL');
        if ($coalPairId) {
            DB::table('pair_sources')
                ->where('pair_id', $coalPairId)
                ->where('provider', 'yfinance')
                ->update(['provider_symbol' => 'KOL']);
        }
        DB::table('currencies')->where('code', 'COAL')->update([
            'name' => 'Coal (ETF: KOL)',
        ]);
    }

    private function pairIdForCurrencyCode(string $code): ?int
    {
        $currencyId = DB::table('currencies')->where('code', $code)->value('id');
        if (!$currencyId) {
            return null;
        }
        $pairId = DB::table('pairs')->where('currency_id_in', $currencyId)->value('id');
        return $pairId ? (int) $pairId : null;
    }
};
