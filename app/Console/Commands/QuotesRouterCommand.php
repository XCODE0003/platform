<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\QuoteSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class QuotesRouterCommand extends Command
{
    protected $signature = 'quotes:router {--ttl=600} {--sleep=1}';
    protected $description = 'Spawn/stop quotes relays on-demand based on quote_subscriptions table';

    public function handle(): int
    {
        $ttlDefault = (int) ($this->option('ttl') ?? 600);
        $sleep = max(1, (int) ($this->option('sleep') ?? 1));
        $procs = [];
        $this->info('Quotes router started (DB mode)');
        Log::info('quotes: router started');
        while (true) {
            // Clean expired
            QuoteSubscription::where('expires_at', '<', now())->delete();

            $subs = QuoteSubscription::where('expires_at', '>=', now())->get();
            $this->info('Active subscriptions: ' . $subs->count());
            $activeKeys = [];
            foreach ($subs as $sub) {
                $key = sprintf('relay:%d:%s', $sub->pair_id, $sub->resolution);
                $activeKeys[$key] = $sub;
            }

            // Start missing
            foreach ($activeKeys as $key => $sub) {
                if (isset($procs[$key]) && $procs[$key]->isRunning()) continue;
                $pairId = $sub->pair_id;
                $res = $sub->resolution;
                $childTtl = max(60, $ttlDefault);
                [$provider, $symbol] = $this->getBestSource((int)$pairId);
                // TwelveData and yfinance have their own dedicated commands
                if ($provider === 'twelvedata' || $provider === 'yfinance') continue;
                $this->info("Need to start relay {$key}");
                $cmd = [PHP_BINARY, base_path('artisan'), 'quotes:relay', '--pair_id='.$pairId, '--provider='.$provider, '--symbol='.$symbol, '--resolution='.$res, '--ttl='.$childTtl];
                $proc = new Process($cmd, base_path());
                $proc->start();
                $procs[$key] = $proc;
                $this->info("Started relay {$key} ({$provider} {$symbol}) pid=".$proc->getPid());
                Log::info('quotes: router started relay', ['key' => $key, 'provider' => $provider, 'symbol' => $symbol, 'ttl' => $childTtl]);
                usleep(100_000);
            }

            // Stop orphan processes (no longer in DB)
            foreach ($procs as $key => $proc) {
                if (!isset($activeKeys[$key])) {
                    $this->stopProcess($proc);
                    unset($procs[$key]);
                    $this->info("Stopped relay {$key}");
                    Log::info('quotes: router stopped relay', ['key' => $key]);
                }
            }

            sleep($sleep);
        }

        // return self::SUCCESS; // unreachable
    }

    private function stopProcess(Process $proc): void
    {
        try { $proc->stop(1, SIGTERM); } catch (\Throwable $e) {}
    }

    private function getBestSource(int $pairId): array
    {
        $row = DB::table('pairs as p')
            ->leftJoin('pair_sources as ps', 'ps.pair_id', '=', 'p.id')
            ->select('p.default_source', 'ps.provider', 'ps.provider_symbol')
            ->where('p.id', $pairId)
            ->orderByRaw('(ps.provider = p.default_source) desc')
            ->orderBy('ps.priority')
            ->first();
        $provider = $row->provider ?? $row->default_source ?? 'binance';
        $symbol = $row->provider_symbol ?? 'btcusdt';
        return [$provider, $symbol];
    }
}


