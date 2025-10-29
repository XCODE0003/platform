<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Service\System\Provider\TwelveDataService;
use App\Repositories\BarsRepository;

class SyncAllTwelveDataBars extends Command
{
    protected $signature = 'markets:sync-twelvedata
                            {--intervals=1m,5m,1h,1d : Список интервалов через запятую}
                            {--lookback=200 : Сколько баров перекрывать назад}
                            {--outputsize=5000 : Сколько баров запрашивать за один запрос}
                            {--only-active=1 : Брать только активные пары}
                            {--throttle-ms=200 : Пауза между парами (мс)}
                            {--window-days=7 : Размер окна по времени в днях}
                            ';

    protected $description = 'Синхронизирует недостающие/новые бары для всех пар через Twelve Data (с lookback перекрытием)';

    public function handle(BarsRepository $repo): int
    {
        $intervals = array_map('trim', explode(',', (string) $this->option('intervals')));
        $lookback = (int) $this->option('lookback');
        $outputSize = (int) $this->option('outputsize');
        $onlyActive = (int) $this->option('only-active') === 1;
        $throttleMs = (int) $this->option('throttle-ms');
        $windowDays = max(1, (int) $this->option('window-days'));

        $apiKey = (string) env('TWELVEDATA_API_KEY', 'demo');
        $svc = new TwelveDataService($apiKey);

        $pairs = DB::table('pairs')
            ->join('pair_sources as ps', 'ps.pair_id', '=', 'pairs.id')
            ->where('ps.provider', 'twelvedata')
            ->when($onlyActive, fn($q) => $q->where('pairs.is_active', 1))
            ->select([
                'pairs.id as id',
                'ps.provider_symbol as symbol',
            ])
            ->orderBy('pairs.id')
            ->get();

        if ($pairs->isEmpty()) {
            $this->warn('Нет пар для обновления (twelvedata).');
            return self::SUCCESS;
        }

        foreach ($pairs as $pair) {
            $symbol = $pair->symbol ?? null;
            $pairId = $pair->id;

            if (!$symbol) {
                $this->warn("Пара id={$pairId} пропущена: не задан provider_symbol для TwelveData");
                continue;
            }

            foreach ($intervals as $interval) {
                // Определяем старт для lookback-окна
                $lastTime = DB::table('bars')
                    ->where('pair_id', $pairId)
                    ->where('interval', $interval)
                    ->orderByDesc('time')
                    ->value('time');

                $from = $lastTime
                    ? $this->rewindByLookback(\Illuminate\Support\Carbon::createFromTimestampMsUTC((int) $lastTime), $interval, $lookback)
                    : now()->subDays($windowDays)->utc();
                $to = now()->utc();

                // Разбиваем на окна по windowDays
                $cursor = $from->clone();
                $total = 0;

                while ($cursor < $to) {
                    $batchTo = $cursor->clone()->addDays($windowDays);
                    if ($batchTo > $to) {
                        $batchTo = $to;
                    }

                    $bars = $svc->fetch($symbol, $interval, $cursor, $batchTo, $outputSize);
                    if (!empty($bars)) {
                        // TwelveDataService возвращает time в мс
                        $repo->upsertBars($pairId, $interval, $bars);
                        $total += count($bars);
                    }

                    $cursor = $batchTo;
                    usleep(100_000); // 100ms между батчами
                }

                $this->info("Pair {$symbol} [{$interval}] upserted ~{$total} bars (TD)");
            }

            if ($throttleMs > 0) {
                usleep($throttleMs * 1000);
            }
        }

        return self::SUCCESS;
    }

    protected function rewindByLookback(Carbon $last, string $interval, int $lookback): Carbon
    {
        $minutes = match ($interval) {
            '1m' => 1,
            '3m' => 3,
            '5m' => 5,
            '15m' => 15,
            '30m' => 30,
            '1h' => 60,
            '2h' => 120,
            '4h' => 240,
            '6h' => 360,
            '8h' => 480,
            '12h' => 720,
            '1d' => 1440,
            '3d' => 4320,
            '1w' => 10080,
            '1M' => 43200,
            default => 60,
        };

        return $last->clone()->subMinutes($minutes * max($lookback, 1));
    }
}


