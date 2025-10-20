<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Pair;
use App\Http\Service\System\Provider\BinanceKlinesService;
use App\Repositories\BarsRepository;

class SyncAllBinanceBars extends Command
{
    protected $signature = 'markets:sync-binance
                            {--intervals=1m,5m,1h,1d : Список интервалов через запятую}
                            {--lookback=200 : Сколько баров перекрывать назад}
                            {--limit=1000 : Лимит запросов к API (max 1000 на запрос)}
                            {--only-active=1 : Брать только активные пары}
                            {--throttle-ms=200 : Пауза между парами (мс)}
                            ';

    protected $description = 'Синхронизирует недостающие/новые бары для всех пар Binance (с lookback перекрытием)';

    public function handle(BinanceKlinesService $svc, BarsRepository $repo): int
    {
        $intervals = array_map('trim', explode(',', (string) $this->option('intervals')));
        $lookback = (int) $this->option('lookback');
        $limit = (int) $this->option('limit');
        $onlyActive = (int) $this->option('only-active') === 1;
        $throttleMs = (int) $this->option('throttle-ms');

        $pairsQuery = Pair::query()->where('default_source', 'binance');
        if ($onlyActive) {
            $pairsQuery->where('is_active', 1);
        }


        $pairs = DB::table('pairs')
            ->join('currencies as base', 'base.id', '=', 'pairs.currency_id_in')
            ->join('currencies as quote', 'quote.id', '=', 'pairs.currency_id_out')
            ->join('pair_sources as ps', 'ps.pair_id', '=', 'pairs.id')
            ->where('pairs.is_active', 1)
            ->where('ps.provider', 'binance')
            ->select([
                'pairs.id as id',
                DB::raw("CONCAT(base.code, quote.code) AS symbol"),
            ])
            ->orderBy('pairs.id')
            ->get();

        if ($pairs->isEmpty()) {
            $this->warn('Нет пар для обновления.');
            return self::SUCCESS;
        }

        foreach ($pairs as $pair) {
            $symbol = $pair->symbol ?? null;
            $pairId = $pair->id;

            if (!$symbol) {
                $this->warn("Пара id={$pairId} пропущена: не задан symbol");
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
                    : now()->subDays(7)->utc();
                $to = now()->utc();

                // Binance позволяет задавать startTime/endTime и limit<=1000
                // Разобьем на батчи, пока не дойдем до "to" или пока не вернут пусто
                $cursor = $from->clone();
                $total = 0;

                while ($cursor < $to) {
                    // Для надежности возьмем окно по времени, но основной стопер — limit
                    $batchTo = $cursor->clone()->addDays(7);
                    if ($batchTo > $to) {
                        $batchTo = $to;
                    }

                    $bars = $svc->fetch($symbol, $interval, $cursor, $batchTo, $limit);
                    if (!empty($bars)) {
                        $repo->upsertBars($pairId, $interval, $bars);
                        $total += count($bars);
                    }

                    // Если данных нет, двигаем курсор; иначе все равно двигаем к batchTo
                    $cursor = $batchTo;

                    // Троттлинг между батчами по одной паре (бережем лимиты)
                    usleep(100_000); // 100ms
                }

                $this->info("Pair {$symbol} [{$interval}] upserted ~{$total} bars");
            }

            // Троттлинг между парами
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
