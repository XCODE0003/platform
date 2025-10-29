<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MarketData\UpdateRates as UpdateRatesService;

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
    public function __construct(private readonly UpdateRatesService $service)
    {
        parent::__construct();
    }

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
                $updated = $this->service->updateAllCurrencies();
                $this->line('Updated rates for ' . $updated . ' currencies at ' . now()->toDateTimeString());
            } catch (\Throwable $e) {
                $this->error('Rates update failed: ' . $e->getMessage());
            }

            $elapsed = microtime(true) - $start;
            $sleepSeconds = (int) max(0, 10 - $elapsed);
            sleep($sleepSeconds);
        }
    }
}
