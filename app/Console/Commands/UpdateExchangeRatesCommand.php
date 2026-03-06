<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches the latest price for every currency that has a pair_source
 * (binance, yfinance, twelvedata) and updates currencies.exchange_rate.
 *
 * Runs on a schedule (every minute) so portfolio values stay accurate
 * even when nobody has the chart open.
 */
class UpdateExchangeRatesCommand extends Command
{
    protected $signature   = 'rates:update';
    protected $description = 'Update exchange_rate for all currencies backed by any price provider';

    public function handle(): int
    {
        $rows = DB::table('pair_sources as ps')
            ->join('pairs as p', 'p.id', '=', 'ps.pair_id')
            ->select('ps.provider', 'ps.provider_symbol', 'p.currency_id_in')
            ->distinct()
            ->get();

        if ($rows->isEmpty()) {
            return self::SUCCESS;
        }

        // Group by provider
        $byProvider = [];
        foreach ($rows as $row) {
            $cid    = (int) $row->currency_id_in;
            $symbol = (string) $row->provider_symbol;
            if ($cid && $symbol) {
                $byProvider[$row->provider][$symbol][] = $cid;
            }
        }

        foreach ($byProvider as $provider => $bySymbol) {
            match ($provider) {
                'binance'    => $this->updateBinance($bySymbol),
                'yfinance'   => $this->updateYfinance($bySymbol),
                'twelvedata' => $this->updateTwelvedata($bySymbol),
                default      => null,
            };
        }

        return self::SUCCESS;
    }

    // ─── Binance ──────────────────────────────────────────────────────────────

    private function updateBinance(array $bySymbol): void
    {
        $symbols = array_keys($bySymbol);

        try {
            // Batch request: up to 100 symbols at once
            $resp = Http::timeout(10)
                ->get('https://api.binance.com/api/v3/ticker/price', [
                    'symbols' => json_encode(array_map('strtoupper', $symbols)),
                ]);

            if (!$resp->ok()) {
                Log::warning('rates:update binance batch failed', ['status' => $resp->status()]);
                return;
            }

            foreach ($resp->json() as $item) {
                $symbol = strtoupper((string) ($item['symbol'] ?? ''));
                $price  = (float) ($item['price'] ?? 0);
                if ($price <= 0 || !isset($bySymbol[$symbol])) continue;

                foreach (array_unique($bySymbol[$symbol]) as $cid) {
                    DB::table('currencies')->where('id', $cid)->update(['exchange_rate' => $price]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('rates:update binance error', ['error' => $e->getMessage()]);
        }
    }

    // ─── yfinance ─────────────────────────────────────────────────────────────

    private function updateYfinance(array $bySymbol): void
    {
        $baseUrl = rtrim(env('YFINANCE_BASE_URL', 'http://127.0.0.1:8001'), '/');

        foreach ($bySymbol as $symbol => $currencyIds) {
            // Skip USD-base forex pairs (USDJPY=X, USDCHF=X, etc.)
            // Their price represents "foreign units per 1 USD", not "USD price in USD".
            // Updating these would corrupt the USD exchange_rate.
            if (stripos($symbol, 'USD') === 0) {
                continue;
            }

            try {
                $resp = Http::timeout(10)
                    ->withOptions(['verify' => false])
                    ->get("{$baseUrl}/bars", [
                        'symbol'     => $symbol,
                        'resolution' => '1D',
                        'from_ts'    => time() - 86400 * 7,
                        'to_ts'      => time() + 86400,
                    ]);

                if (!$resp->ok()) continue;

                $bars = $resp->json('bars', []);
                if (empty($bars)) continue;

                $close = (float) end($bars)['close'];
                if ($close <= 0) continue;

                foreach (array_unique($currencyIds) as $cid) {
                    DB::table('currencies')->where('id', $cid)->update(['exchange_rate' => $close]);
                }
            } catch (Throwable $e) {
                Log::warning('rates:update yfinance error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            }
        }
    }

    // ─── TwelveData ───────────────────────────────────────────────────────────

    private function updateTwelvedata(array $bySymbol): void
    {
        $apiKey  = (string) env('TWELVEDATA_API_KEY', '');
        if (!$apiKey || $apiKey === 'demo') return;

        $symbols = array_keys($bySymbol);

        try {
            $resp = Http::timeout(10)
                ->get('https://api.twelvedata.com/price', [
                    'symbol' => implode(',', $symbols),
                    'apikey' => $apiKey,
                ]);

            if (!$resp->ok()) return;

            $data = $resp->json();

            // Single symbol returns {"price":"123"}, multiple returns {"SYM":{"price":"123"},...}
            if (isset($data['price'])) {
                $data = [array_key_first($bySymbol) => $data];
            }

            foreach ($data as $symbol => $info) {
                $price = (float) ($info['price'] ?? 0);
                if ($price <= 0 || !isset($bySymbol[$symbol])) continue;

                foreach (array_unique($bySymbol[$symbol]) as $cid) {
                    DB::table('currencies')->where('id', $cid)->update(['exchange_rate' => $price]);
                }
            }
        } catch (Throwable $e) {
            Log::warning('rates:update twelvedata error', ['error' => $e->getMessage()]);
        }
    }
}
