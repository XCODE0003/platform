<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunBinanceSyncLoop extends Command
{
    protected $signature = 'markets:binance-loop
                            {--intervals=1m,1h,1d}
                            {--sleep=60 : Пауза между итерациями (сек)}
                            {--throttle-ms=150 : Пауза между парами, мс}
                            {--only-active=1}';

    protected $description = 'Запускает бесконечный цикл синхронизации всех пар Binance без cron';

    public function handle(): int
    {
        ini_set('memory_limit', '-1');

        while (true) {
            try {
                // Вызов вашей команды массового синка (из ответа выше)
                $exit = $this->call('markets:sync-binance', [
                    '--intervals'    => $this->option('intervals'),
                    '--lookback'     => 200,
                    '--throttle-ms'  => (int)$this->option('throttle-ms'),
                    '--only-active'  => (int)$this->option('only-active'),
                ]);

                if ($exit !== 0) {
                    $this->warn("markets:sync-binance завершилась с кодом {$exit}");
                }
            } catch (\Throwable $e) {
                Log::error('Binance loop error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $this->error('Ошибка: '.$e->getMessage());
                // Небольшая пауза, чтобы не крутить ошибки слишком быстро
                sleep(5);
            }

            $sleep = (int)$this->option('sleep');
            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        return self::SUCCESS;
    }
}
