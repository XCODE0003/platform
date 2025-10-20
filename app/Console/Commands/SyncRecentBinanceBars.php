<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Http\Service\System\Provider\BinanceKlinesService;
use App\Repositories\BarsRepository;
use Illuminate\Support\Facades\DB;

class SyncRecentBinanceBars extends Command
{
    protected $signature = 'markets:sync-recent-binance {symbol} {interval} {--pair-id=} {--lookback=100}';
    protected $description = 'Sync recent bars from Binance with lookback';

    public function handle(BinanceKlinesService $svc, BarsRepository $repo): int
    {
        $symbol = $this->argument('symbol');
        $interval = $this->argument('interval');
        $pairId = (int) ($this->option('pair-id') ?: 0);
        $lookback = (int) $this->option('lookback');

        // Берём последнюю известную дату и отматываем на N баров
        $last = DB::table('bars')
            ->where('pair_id', $pairId)
            ->where('interval', $interval)
            ->orderByDesc('time')
            ->value('time');

        $from = $last
            ? \Illuminate\Support\Carbon::createFromTimestampMsUTC((int) $last)->subMinutes($this->intervalToMinutes($interval) * $lookback)
            : now()->subDays(7)->utc();
        $to = now()->utc();

        $bars = $svc->fetch($symbol, $interval, $from, $to);
        $repo->upsertBars($pairId, $interval, $bars);

        $this->info("Upserted " . count($bars) . " recent bars for {$symbol} {$interval}");
        return self::SUCCESS;
    }

    protected function intervalToMinutes(string $interval): int
    {
        return match ($interval) {
            '1m' => 1,
            '5m' => 5,
            '15m' => 15,
            '30m' => 30,
            '1h' => 60,
            '4h' => 240,
            '1d' => 1440,
            default => 1,
        };
    }
}
