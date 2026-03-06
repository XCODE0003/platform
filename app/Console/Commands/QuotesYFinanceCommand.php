<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\BarUpdated;
use App\Services\SpreadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Polls yfinance microservice for all active yfinance pair subscriptions
 * and broadcasts bar updates via Reverb.
 *
 * yfinance has no WebSocket — we poll on a configurable interval.
 * For 1-min bars: poll every ~15s (enough to show live-ish movement).
 * For 1D bars:    poll every 60s (price changes slowly).
 */
class QuotesYFinanceCommand extends Command
{
    protected $signature = 'quotes:yfinance
                            {--poll=15 : Seconds between each poll cycle}
                            {--sync=30 : Seconds between re-reading active subscriptions from DB}';

    protected $description = 'Poll yfinance microservice and broadcast bar updates for all active subscriptions';

    /** pair+resolution → current aggregated bar state */
    private array $bars = [];
    private SpreadService $spread;

    public function handle(): int
    {
        $this->spread = app(SpreadService::class);
        $pollSec = max(5, (int) $this->option('poll'));
        $syncSec = max(10, (int) $this->option('sync'));

        $this->info("quotes:yfinance started (poll={$pollSec}s sync={$syncSec}s)");
        Log::info('quotes:yfinance started', ['poll' => $pollSec, 'sync' => $syncSec]);

        $targets  = [];   // [pair_id, resolution, provider_symbol, interval_ms]
        $nextSync = 0;

        while (true) {
            // Re-read active subscriptions from DB
            if (time() >= $nextSync) {
                $targets  = $this->loadTargets();
                $nextSync = time() + $syncSec;
                $this->info('Active yfinance subscriptions: ' . count($targets));
            }

            if (empty($targets)) {
                sleep($pollSec);
                continue;
            }

            // Group by symbol to avoid duplicate fetches
            $bySymbol = [];
            foreach ($targets as $t) {
                $bySymbol[$t['provider_symbol']][] = $t;
            }

            foreach ($bySymbol as $symbol => $group) {
                try {
                    $this->pollSymbol($symbol, $group);
                } catch (Throwable $e) {
                    Log::warning('quotes:yfinance poll error', ['symbol' => $symbol, 'error' => $e->getMessage()]);
                }
            }

            sleep($pollSec);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function pollSymbol(string $symbol, array $group): void
    {
        // Determine the finest resolution in this group (smallest interval → most ticks)
        $minInterval = PHP_INT_MAX;
        foreach ($group as $t) {
            $minInterval = min($minInterval, $t['interval_ms']);
        }

        // Fetch last 2 bars at the finest resolution needed
        $resolution = $this->msToResolution($minInterval);
        $from = time() - ($minInterval / 1000) * 5; // last 5 bars worth
        $to   = time() + 86400;

        $resp = Http::timeout(10)
            ->withOptions(['verify' => false])
            ->get(rtrim(env('YFINANCE_BASE_URL', 'http://127.0.0.1:8001'), '/') . '/bars', [
                'symbol'     => $symbol,
                'resolution' => $resolution,
                'from_ts'    => $from,
                'to_ts'      => $to,
            ]);

        if (!$resp->ok()) return;

        $bars = $resp->json('bars', []);
        if (empty($bars)) return;

        // Use last bar as current live bar
        $latest = end($bars);

        $tsMs   = (int) $latest['time'];
        $open   = (float) $latest['open'];
        $high   = (float) $latest['high'];
        $low    = (float) $latest['low'];
        $close  = (float) $latest['close'];
        $volume = (float) $latest['volume'];

        // Update exchange_rate for the base currency so portfolio value is accurate
        $updatedCurrencies = [];
        foreach ($group as $t) {
            $cid = (int) ($t['currency_id_in'] ?? 0);
            if ($cid && !in_array($cid, $updatedCurrencies, true) && $close > 0) {
                DB::table('currencies')->where('id', $cid)->update(['exchange_rate' => $close]);
                $updatedCurrencies[] = $cid;
            }
        }

        foreach ($group as $t) {
            $pairId     = (int) $t['pair_id'];
            $res        = (string) $t['resolution'];
            $intervalMs = (int) $t['interval_ms'];

            // Snap timestamp to bar boundary for this resolution
            $barStart = intdiv($tsMs, $intervalMs) * $intervalMs;
            $key      = "{$pairId}:{$res}";

            $prev = $this->bars[$key] ?? null;

            // Detect bar close: new bar started
            if ($prev && $prev['start'] !== $barStart) {
                [$bo, $bh, $bl, $bc] = $this->spread->applyToBar($pairId, $prev['o'], $prev['h'], $prev['l'], $prev['c']);
                try {
                    event(new BarUpdated($pairId, $res, $prev['start'], $bo, $bh, $bl, $bc, $prev['vol'], true));
                } catch (Throwable $e) {
                    Log::warning('quotes:yfinance close error', ['pair_id' => $pairId, 'error' => $e->getMessage()]);
                }
                $this->bars[$key] = ['start' => $barStart, 'o' => $open, 'h' => $high, 'l' => $low, 'c' => $close, 'vol' => $volume];
            } elseif (!$prev) {
                $this->bars[$key] = ['start' => $barStart, 'o' => $open, 'h' => $high, 'l' => $low, 'c' => $close, 'vol' => $volume];
            } else {
                $this->bars[$key]['h']   = max($prev['h'], $high);
                $this->bars[$key]['l']   = min($prev['l'], $low);
                $this->bars[$key]['c']   = $close;
                $this->bars[$key]['vol'] = $volume;
            }

            $agg = $this->bars[$key];

            // Broadcast live (open) bar with spread applied
            [$bo, $bh, $bl, $bc] = $this->spread->applyToBar($pairId, $agg['o'], $agg['h'], $agg['l'], $agg['c']);
            try {
                event(new BarUpdated($pairId, $res, $agg['start'], $bo, $bh, $bl, $bc, $agg['vol'], false));
            } catch (Throwable $e) {
                Log::warning('quotes:yfinance broadcast error', ['pair_id' => $pairId, 'error' => $e->getMessage()]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function loadTargets(): array
    {
        $rows = DB::table('quote_subscriptions as qs')
            ->join('pair_sources as ps', function ($j) {
                $j->on('ps.pair_id', '=', 'qs.pair_id')->where('ps.provider', '=', 'yfinance');
            })
            ->join('pairs as p', 'p.id', '=', 'qs.pair_id')
            ->where('qs.expires_at', '>=', now())
            ->select('qs.pair_id', 'qs.resolution', 'ps.provider_symbol', 'p.currency_id_in')
            ->get();

        $targets = [];
        foreach ($rows as $row) {
            $res = (string) $row->resolution;
            $targets[] = [
                'pair_id'         => (int) $row->pair_id,
                'resolution'      => $res,
                'provider_symbol' => (string) $row->provider_symbol,
                'interval_ms'     => $this->resolutionToMs($res),
                'currency_id_in'  => (int) $row->currency_id_in,
            ];
        }

        return $targets;
    }

    private function resolutionToMs(string $r): int
    {
        return [
            '1'    => 60_000,
            '5'    => 300_000,
            '15'   => 900_000,
            '30'   => 1_800_000,
            '60'   => 3_600_000,
            '1D'   => 86_400_000,
            'D'    => 86_400_000,
            '1440' => 86_400_000,
        ][$r] ?? 60_000;
    }

    private function msToResolution(int $ms): string
    {
        return match (true) {
            $ms <= 60_000    => '1',
            $ms <= 300_000   => '5',
            $ms <= 3_600_000 => '60',
            default          => '1D',
        };
    }
}
