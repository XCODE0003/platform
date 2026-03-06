<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\BarUpdated;
use App\Services\SpreadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single persistent process that relays ALL TwelveData pairs over ONE WebSocket connection.
 * TwelveData allows only 1 concurrent WS per API key — separate processes cause "Empty read" crashes.
 */
class QuotesTwelveDataCommand extends Command
{
    protected $signature = 'quotes:twelvedata {--sync-interval=30}';
    protected $description = 'Consolidated TwelveData WS relay — one connection for all active pairs';

    private array $aggregators = []; // key = "pairId:resolution" → [barStartMs, o, h, l, c, vol, intervalMs]
    private array $symbolMap   = []; // symbol (uppercase) → [[pair_id, resolution, intervalMs], ...]
    private SpreadService $spread;

    public function handle(): int
    {
        $this->spread = app(SpreadService::class);
        $syncInterval = max(10, (int) $this->option('sync-interval'));
        $apiKey = (string) env('TWELVEDATA_API_KEY', 'demo');
        $verbose = (bool) env('QUOTES_LOG_VERBOSE', false);

        $this->info('quotes:twelvedata relay started');
        Log::info('quotes: TD consolidated relay started');

        $client        = null;
        $subscribedSet = []; // symbol → true
        $nextSync      = 0;

        while (true) {
            // ── Re-sync subscriptions from DB ─────────────────────────────
            if (time() >= $nextSync) {
                $newMap = $this->buildSymbolMap();

                $newSet = array_fill_keys(array_keys($newMap), true);
                $added  = array_keys(array_diff_key($newSet, $subscribedSet));
                $removed = array_keys(array_diff_key($subscribedSet, $newSet));

                if (!empty($removed) || ($client === null && !empty($newSet))) {
                    // Reconnect if symbols removed or not yet connected
                    if ($client) { try { $client->close(); } catch (Throwable $e) {} }
                    $client = null;
                    $subscribedSet = [];
                }

                $this->symbolMap = $newMap;

                if (!empty($newSet)) {
                    if ($client === null) {
                        $client = $this->connect($apiKey, array_keys($newSet));
                        $subscribedSet = $newSet;
                        $this->info('Connected. Subscribed to: ' . implode(', ', array_keys($newSet)));
                        Log::info('quotes: TD connected', ['symbols' => array_keys($newSet)]);
                    } elseif (!empty($added)) {
                        // Subscribe only the new symbols
                        $this->sendSubscribe($client, $added);
                        foreach ($added as $s) { $subscribedSet[$s] = true; }
                        $this->info('Subscribed new symbols: ' . implode(', ', $added));
                    }
                }

                $nextSync = time() + $syncInterval;
            }

            // ── Nothing to do ─────────────────────────────────────────────
            if ($client === null || empty($subscribedSet)) {
                sleep(5);
                continue;
            }

            // ── Read messages ──────────────────────────────────────────────
            try {
                $msg = $client->receive();
                if (!$msg) { usleep(100_000); continue; }

                $data  = json_decode($msg, true);
                if (!is_array($data)) continue;

                $event = $data['event'] ?? '';

                if ($event === 'subscribe-status') {
                    $status = $data['status'] ?? '';
                    $ok     = $data['success'] ?? [];
                    $fails  = $data['fails']   ?? [];
                    $this->info("subscribe-status: {$status} ok=" . count((array)$ok) . ' fails=' . count((array)$fails));
                    if ($verbose) Log::debug('quotes: TD subscribe-status', $data);
                    continue;
                }

                if ($event !== 'price') continue;

                $symbol = strtoupper((string)($data['symbol'] ?? ''));
                $price  = (float)($data['price'] ?? 0);
                if (!$symbol || $price <= 0.0) continue;

                $targets = $this->symbolMap[$symbol] ?? [];
                if (empty($targets)) continue;

                $tsSec = (int)($data['timestamp'] ?? time());
                $tsMs  = $tsSec * 1000;

                foreach ($targets as $target) {
                    $this->tick($target['pair_id'], $target['resolution'], $target['interval_ms'], $tsMs, $price);
                }

            } catch (Throwable $e) {
                $this->warn('WS error: ' . $e->getMessage() . ' — reconnecting...');
                Log::warning('quotes: TD ws error', ['error' => $e->getMessage()]);
                try { $client->close(); } catch (Throwable $e2) {}
                $client        = null;
                $subscribedSet = [];
                sleep(2);
                // Force re-sync on next iteration
                $nextSync = 0;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function tick(int $pairId, string $resolution, int $intervalMs, int $tsMs, float $price): void
    {
        $key = "{$pairId}:{$resolution}";

        if (!isset($this->aggregators[$key])) {
            $barStart = intdiv($tsMs, $intervalMs) * $intervalMs;
            $this->aggregators[$key] = ['start' => $barStart, 'o' => $price, 'h' => $price, 'l' => $price, 'c' => $price, 'vol' => 0.0];
        }

        $agg = &$this->aggregators[$key];

        // Close current bar if tick crossed into a new interval
        if ($tsMs >= $agg['start'] + $intervalMs) {
            [$bo, $bh, $bl, $bc] = $this->spread->applyToBar($pairId, $agg['o'], $agg['h'], $agg['l'], $agg['c']);
            try {
                event(new BarUpdated($pairId, $resolution, $agg['start'], $bo, $bh, $bl, $bc, $agg['vol'], true));
            } catch (Throwable $e) {
                Log::warning('quotes: TD bar close error', ['pair_id' => $pairId, 'error' => $e->getMessage()]);
            }
            $barStart   = intdiv($tsMs, $intervalMs) * $intervalMs;
            $agg = ['start' => $barStart, 'o' => $price, 'h' => $price, 'l' => $price, 'c' => $price, 'vol' => 0.0];
        } else {
            $agg['h'] = max($agg['h'], $price);
            $agg['l'] = min($agg['l'], $price);
            $agg['c'] = $price;
        }

        // Emit live (open) bar with spread applied
        [$bo, $bh, $bl, $bc] = $this->spread->applyToBar($pairId, $agg['o'], $agg['h'], $agg['l'], $agg['c']);
        try {
            event(new BarUpdated($pairId, $resolution, $agg['start'], $bo, $bh, $bl, $bc, $agg['vol'], false));
        } catch (Throwable $e) {
            Log::warning('quotes: TD bar open error', ['pair_id' => $pairId, 'error' => $e->getMessage()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /** Build symbol → targets map from active quote_subscriptions */
    private function buildSymbolMap(): array
    {
        $rows = DB::table('quote_subscriptions as qs')
            ->join('pair_sources as ps', function ($j) {
                $j->on('ps.pair_id', '=', 'qs.pair_id')->where('ps.provider', '=', 'twelvedata');
            })
            ->where('qs.expires_at', '>=', now())
            ->select('qs.pair_id', 'qs.resolution', 'ps.provider_symbol')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $symbol      = strtoupper(trim((string)($row->provider_symbol ?? '')));
            $resolution  = (string)$row->resolution;
            $intervalMs  = $this->resolutionToMs($resolution);
            if (!$symbol) continue;
            $map[$symbol][] = [
                'pair_id'     => (int)$row->pair_id,
                'resolution'  => $resolution,
                'interval_ms' => $intervalMs,
            ];
        }

        return $map;
    }

    private function resolutionToMs(string $r): int
    {
        return [
            '1' => 60_000, '3' => 180_000, '5' => 300_000, '15' => 900_000,
            '30' => 1_800_000, '60' => 3_600_000, '120' => 7_200_000,
            '240' => 14_400_000, '1D' => 86_400_000, 'D' => 86_400_000,
        ][$r] ?? 60_000;
    }

    private function connect(string $apiKey, array $symbols): \WebSocket\Client
    {
        $url = 'wss://ws.twelvedata.com/v1/quotes/price?apikey=' . urlencode($apiKey);
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $client = new \WebSocket\Client($url, ['timeout' => 30, 'context' => $ctx]);
        $this->sendSubscribe($client, $symbols);
        return $client;
    }

    private function sendSubscribe(\WebSocket\Client $client, array $symbols): void
    {
        $client->send(json_encode([
            'action' => 'subscribe',
            'params' => ['symbols' => implode(',', $symbols)],
        ]));
    }
}
