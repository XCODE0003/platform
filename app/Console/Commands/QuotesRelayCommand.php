<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\BarUpdated;
use Illuminate\Console\Command;
use Throwable;
use Illuminate\Support\Facades\Log;

class QuotesRelayCommand extends Command
{
    protected $signature = 'quotes:relay {--pair_id=} {--provider=binance} {--symbol=} {--resolution=1} {--ttl=600}';
    protected $description = 'Relay external quotes into Reverb broadcasting';

    public function handle(): int
    {
        $pairId = (int) ($this->option('pair_id') ?? 0);
        $provider = (string) ($this->option('provider') ?? 'binance');
        $symbol = (string) ($this->option('symbol') ?? 'btcusdt');
        $resolution = (string) ($this->option('resolution') ?? '1');
        if ($pairId <= 0) {
            $this->error('pair_id is required');
            return self::FAILURE;
        }

        if ($provider === 'binance') {
            $interval = $this->mapResolution($resolution);
            $url = 'wss://stream.binance.com:9443/ws/' . strtolower($symbol) . '@kline_' . $interval;
            $ttl = (int) ($this->option('ttl') ?? 600);
            $this->relayBinance($url, $pairId, $resolution, max(1, $ttl));
            return self::SUCCESS;
        }

        if ($provider === 'twelvedata') {
            $apiKey = (string) env('TWELVEDATA_API_KEY', 'demo');
            $wsUrl = 'wss://ws.twelvedata.com/v1/quotes/price?apikey=' . urlencode($apiKey);
            $ttl = (int) ($this->option('ttl') ?? 600);
            $this->relayTwelveData($wsUrl, $pairId, $symbol, $resolution, max(1, $ttl));
            return self::SUCCESS;
        }



        $this->error('Provider not implemented: ' . $provider);
        return self::FAILURE;
    }

    private function mapResolution(string $r): string
    {
        return [
            '1' => '1m', '3' => '3m', '5' => '5m', '15' => '15m', '30' => '30m',
            '60' => '1h', '120' => '2h', '240' => '4h', '1D' => '1d',
        ][$r] ?? '1m';
    }

    private function relayBinance(string $wsUrl, int $pairId, string $resolution, int $ttlSeconds): void
    {
        $this->info("[pair={$pairId} res={$resolution}] Connecting WS: {$wsUrl}");
        $client = $this->makeWsClient($wsUrl);
        Log::info('quotes: WS connected', ['pair_id' => $pairId, 'resolution' => $resolution, 'url' => $wsUrl]);
        $count = 0;
        $deadline = time() + $ttlSeconds;
        $verbose = (bool) env('QUOTES_LOG_VERBOSE', false);
        while (true) {
            try {
                if (time() >= $deadline) {
                    try { $client->close(); } catch (\Throwable $e) {}
                    $this->info("[pair={$pairId} res={$resolution}] TTL reached ({$ttlSeconds}s) — closing connection");
                    Log::info('quotes: WS ttl reached', ['pair_id' => $pairId, 'resolution' => $resolution, 'ttl' => $ttlSeconds]);
                    return;
                }
                $msg = $client->receive();
                if (!$msg) { usleep(100_000); continue; }
                $count++;
                if (!$msg) { usleep(100_000); continue; }
                $data = json_decode($msg, true);
                $k = $data['k'] ?? null;
                if (!$k) continue;
                if ($verbose) {
                    Log::debug('quotes: ws raw', ['pair_id' => $pairId, 'resolution' => $resolution, 'payload' => $data]);
                }
                try {
                    event(new BarUpdated($pairId, $resolution, (int)$k['t'], (float)$k['o'], (float)$k['h'], (float)$k['l'], (float)$k['c'], (float)$k['v'], (bool)$k['x']));
                } catch (\Throwable $e) {
                    Log::warning('quotes: broadcast error', ['pair_id' => $pairId, 'resolution' => $resolution, 'error' => $e->getMessage()]);
                }
                if ($count === 1 || $count % 60 === 0) {
                    $line = sprintf('[pair=%d res=%s] bar t=%d close=%s vol=%s closed=%s (#%d)', $pairId, $resolution, (int) $k['t'], (string) $k['c'], (string) $k['v'], $k['x'] ? '1' : '0', $count);
                    $this->info($line);
                    Log::info('quotes: dispatched bar', [
                        'pair_id' => $pairId,
                        'resolution' => $resolution,
                        'time' => (int) $k['t'],
                        'close' => (float) $k['c'],
                        'volume' => (float) $k['v'],
                        'closed' => (bool) $k['x'],
                        'count' => $count,
                    ]);
                }
            } catch (Throwable $e) {
                $this->warn('[pair='.$pairId.' res='.$resolution.'] WS error: ' . $e->getMessage() . ' — reconnecting...');
                Log::warning('quotes: ws error', ['pair_id' => $pairId, 'resolution' => $resolution, 'error' => $e->getMessage()]);
                try { $client->close(); } catch (Throwable $e2) {}
                sleep(1);
                $client = $this->makeWsClient($wsUrl);
                $this->info('[pair='.$pairId.' res='.$resolution.'] WS reconnected');
                Log::info('quotes: ws reconnected', ['pair_id' => $pairId, 'resolution' => $resolution]);
            }
        }
    }

    private function makeWsClient(string $wsUrl): \WebSocket\Client
    {
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
        return new \WebSocket\Client($wsUrl, [
            'timeout' => 120,
            'context' => $ctx,
        ]);
    }

    private function mapResolutionMs(string $r): int
    {
        return [
            '1' => 60_000,
            '3' => 180_000,
            '5' => 300_000,
            '15' => 900_000,
            '30' => 1_800_000,
            '60' => 3_600_000,
            '120' => 7_200_000,
            '240' => 14_400_000,
            '1D' => 86_400_000,
        ][$r] ?? 60_000;
    }

    private function relayTwelveData(string $wsUrl, int $pairId, string $symbol, string $resolution, int $ttlSeconds): void
    {
        $this->info("[pair={$pairId} res={$resolution}] Connecting TwelveData WS: {$wsUrl} symbol={$symbol}");
        $client = $this->makeWsClient($wsUrl);
        Log::info('quotes: TD ws connected', ['pair_id' => $pairId, 'resolution' => $resolution, 'url' => $wsUrl, 'symbol' => $symbol]);

        // Subscribe
        $subMsg = json_encode([
            'action' => 'subscribe',
            'params' => [ 'symbols' => $symbol ],
        ]);
        try { $client->send($subMsg); } catch (\Throwable $e) {
            $this->error('Failed to subscribe to TwelveData: ' . $e->getMessage());
        }

        $intervalMs = $this->mapResolutionMs($resolution);
        $deadline = time() + $ttlSeconds;
        $verbose = (bool) env('QUOTES_LOG_VERBOSE', false);
        $count = 0;

        // Aggregator state
        $barStartMs = null;
        $o = $h = $l = $c = null;
        $vol = 0.0;

        while (true) {
            try {
                if (time() >= $deadline) {
                    try { $client->close(); } catch (\Throwable $e) {}
                    $this->info("[pair={$pairId} res={$resolution}] TTL reached ({$ttlSeconds}s) — closing TD connection");
                    Log::info('quotes: TD ws ttl reached', ['pair_id' => $pairId, 'resolution' => $resolution, 'ttl' => $ttlSeconds]);
                    return;
                }

                $msg = $client->receive();
                if (!$msg) { usleep(100_000); continue; }
                $count++;
                $data = json_decode($msg, true);
                if (!is_array($data)) { continue; }

                $event = $data['event'] ?? '';
                if ($event === 'subscribe-status') {
                    $this->info('[TD] subscribe-status: ' . ($data['status'] ?? ''));
                    if ($verbose) { Log::debug('quotes: TD subscribe', $data); }
                    continue;
                }
                if ($event !== 'price') { continue; }

                // Expected structure: event, symbol, timestamp (unix sec), price, day_volume
                $tickSymbol = (string) ($data['symbol'] ?? '');
                if ($tickSymbol !== $symbol) { continue; }
                $tsSec = (int) ($data['timestamp'] ?? time());
                $tsMs = $tsSec * 1000;
                $price = (float) ($data['price'] ?? 0);
                if ($price <= 0) { continue; }

                if ($barStartMs === null) {
                    $barStartMs = intdiv($tsMs, $intervalMs) * $intervalMs;
                    $o = $h = $l = $c = $price;
                    $vol = 0.0;
                }

                // If tick moved to a new bar interval, close previous bar
                if ($tsMs >= $barStartMs + $intervalMs) {
                    try {
                        event(new BarUpdated($pairId, $resolution, (int)$barStartMs, (float)$o, (float)$h, (float)$l, (float)$c, (float)$vol, true));
                    } catch (\Throwable $e) {
                        Log::warning('quotes: TD broadcast close error', ['pair_id' => $pairId, 'error' => $e->getMessage()]);
                    }
                    // Start new bar at this tick's slot
                    $barStartMs = intdiv($tsMs, $intervalMs) * $intervalMs;
                    $o = $h = $l = $c = $price;
                    $vol = 0.0;
                } else {
                    // Update current bar
                    $h = max($h, $price);
                    $l = min($l, $price);
                    $c = $price;
                }

                if ($verbose && ($count % 30 === 0)) {
                    Log::debug('quotes: TD tick', ['pair_id' => $pairId, 't' => $tsMs, 'price' => $price]);
                }

                // Emit in-progress bar update
                try {
                    event(new BarUpdated($pairId, $resolution, (int)$barStartMs, (float)$o, (float)$h, (float)$l, (float)$c, (float)$vol, false));
                } catch (\Throwable $e) {
                    Log::warning('quotes: TD broadcast open error', ['pair_id' => $pairId, 'error' => $e->getMessage()]);
                }

                if ($count === 1 || $count % 60 === 0) {
                    $line = sprintf('[pair=%d res=%s] TD price t=%d p=%s (#%d)', $pairId, $resolution, $tsSec, (string) $price, $count);
                    $this->info($line);
                }
            } catch (Throwable $e) {
                $this->warn('[pair='.$pairId.' res='.$resolution.'] TD WS error: ' . $e->getMessage() . ' — reconnecting...');
                Log::warning('quotes: TD ws error', ['pair_id' => $pairId, 'resolution' => $resolution, 'error' => $e->getMessage()]);
                try { $client->close(); } catch (Throwable $e2) {}
                sleep(1);
                $client = $this->makeWsClient($wsUrl);
                // Re-subscribe after reconnect
                try { $client->send($subMsg); } catch (\Throwable $e) {}
                $this->info('[pair='.$pairId.' res='.$resolution.'] TD WS reconnected');
                Log::info('quotes: TD ws reconnected', ['pair_id' => $pairId, 'resolution' => $resolution]);
            }
        }
    }
}


