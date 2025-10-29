<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class QuotesRelayAllCommand extends Command
{
    protected $signature = 'quotes:relay:all {--resolutions=1,3,5,15,30,60,120,240,1D} {--ttl=600} {--no-restart}';
    protected $description = 'Launch quotes:relay for all pairs using their default source';

    public function handle(): int
    {
        $resArg = (string) ($this->option('resolutions') ?? '1');
        $resolutions = array_filter(array_map('trim', explode(',', $resArg)));

        // Fetch one preferred source row per pair (default_source first)
        $rows = DB::table('pairs as p')
            ->leftJoin('pair_sources as ps', 'ps.pair_id', '=', 'p.id')
            ->select('p.id as pair_id', 'p.default_source', 'ps.provider', 'ps.provider_symbol')
            ->orderByRaw('(ps.provider = p.default_source) desc')
            ->orderBy('ps.priority')
            ->get()
            ->groupBy('pair_id')
            ->map->first()
            ->values();

        if ($rows->isEmpty()) {
            $this->warn('No pairs/sources found');
            return self::SUCCESS;
        }

        $procs = [];
        $ttl = (int) ($this->option('ttl') ?? 600);
        $noRestart = (bool) $this->option('no-restart');

        foreach ($rows as $row) {
            $pairId = (int) $row->pair_id;
            $provider = (string) ($row->provider ?? $row->default_source ?? 'binance');
            $symbol = (string) ($row->provider_symbol ?? 'btcusdt');
            foreach ($resolutions as $resolution) {
                $cmd = [PHP_BINARY, base_path('artisan'), 'quotes:relay', '--pair_id=' . $pairId, '--provider=' . $provider, '--symbol=' . $symbol, '--resolution=' . $resolution, '--ttl=' . $ttl];
                $process = new Process($cmd, base_path());
                $process->start();
                $key = $pairId . ':' . $resolution;
                $procs[$key] = $process;
                $this->info("Started pair {$pairId} {$resolution} ({$provider} {$symbol}) pid=" . $process->getPid());
                usleep(80_000);
            }
        }

        $this->info('All relays started. Press Ctrl+C to stop.');

        // Simple monitor loop
        if ($noRestart) {
            $this->info('No-restart mode: will not respawn child processes. Exiting monitor.');
            return self::SUCCESS;
        }

        while (true) {
            foreach ($procs as $key => $proc) {
                if (!$proc->isRunning()) {
                    $this->warn("Relay {$key} stopped. Restarting...");
                    $cmd = $proc->getCommandLine();
                    $restart = Process::fromShellCommandline($cmd, base_path());
                    $restart->start();
                    $procs[$key] = $restart;
                }
            }
            sleep(5);
        }

        // unreachable
        // return self::SUCCESS;
    }
}


