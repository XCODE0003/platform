<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Currency;

class UpdateRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currency exchange rates every 10 seconds (loop)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting rates updater (press Ctrl+C to stop)');
        $this->updateRatesLoop();
        return self::SUCCESS;
    }

    /**
     * Run infinite loop to update rates every 10 seconds.
     */
    private function updateRatesLoop(): void
    {
        while (true) {
            $start = microtime(true);
            try {
                $updated = $this->updateAllCurrencies();
                $this->line('Updated rates for ' . $updated . ' currencies at ' . now()->toDateTimeString());
            } catch (\Throwable $e) {
                $this->error('Rates update failed: ' . $e->getMessage());
            }

            // Sleep remaining time to align roughly to 10s interval
            $elapsed = microtime(true) - $start;
            $sleepSeconds = (int) max(0, 10 - $elapsed);
            sleep($sleepSeconds);
        }
    }

    /**
     * Update exchange_rate for all known currencies.
     */
    private function updateAllCurrencies(): int
    {
        $count = 0;
        $currencies = Currency::query()->get();
        foreach ($currencies as $currency) {
            $code = strtoupper((string) $currency->code);
            if ($code === '') {
                continue;
            }

            // We approximate USD with USDT for Binance spot pairs
            $symbol = $code . 'USDT';
            $price = $this->fetchBinancePrice($symbol);
            if ($price === null) {
                // Skip if pair is not supported or request failed
                continue;
            }

            $currency->exchange_rate = (string) $price; // store as string to match schema
            $currency->save();
            $count++;
        }
        return $count;
    }

    /**
     * Fetch latest price from Binance public API for a symbol like BTCUSDT.
     */
    private function fetchBinancePrice(string $symbol): ?float
    {
        $url = 'https://api.binance.com/api/v3/ticker/price?symbol=' . urlencode($symbol);
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
            'https' => [
                'timeout' => 5,
            ],
        ]);

        $json = @file_get_contents($url, false, $context);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['price'])) {
            return null;
        }
        return (float) $data['price'];
    }
}
