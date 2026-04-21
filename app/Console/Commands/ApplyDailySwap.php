<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Position;
use App\Services\Trade\OrderService;
use Illuminate\Console\Command;

class ApplyDailySwap extends Command
{
    protected $signature = 'trade:apply-swap';
    protected $description = 'Charge overnight swap fee on all open positions';

    public function handle(OrderService $service): int
    {
        $positions = Position::query()->where('status', 'open')->get();
        $count = 0;

        foreach ($positions as $position) {
            $service->applySwap($position);
            $count++;
        }

        $this->info("Swap applied to {$count} open position(s).");
        return self::SUCCESS;
    }
}
