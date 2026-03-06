<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Position;
use App\Services\MarketData\UpdateRates;
use App\Services\Trade\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use function bccomp;

/**
 * Fallback safety check: runs every minute to catch any TP/SL conditions
 * that the BarUpdated listener may have missed (e.g. during downtime).
 */
class CheckPositionsTpSlCommand extends Command
{
    protected $signature   = 'trade:check-positions';
    protected $description = 'Check all open positions for TP/SL conditions and auto-close if triggered';

    public function __construct(private readonly OrderService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $positions = Position::query()
            ->with('pair.currencyIn')
            ->where('status', 'open')
            ->whereRaw('(take_profit IS NOT NULL OR stop_loss IS NOT NULL)')
            ->get();

        if ($positions->isEmpty()) return self::SUCCESS;

        $priceCache = [];
        $rates      = new UpdateRates();

        foreach ($positions as $position) {
            $pair = $position->pair;
            if (!$pair) continue;

            // Fetch market price (cached per pair)
            if (!isset($priceCache[$pair->id])) {
                try {
                    $symbol = $pair->currencyIn->symbol ?? '';
                    $price  = $pair->default_source === 'binance'
                        ? $rates->fetchBinancePrice($symbol . 'USDT')
                        : $rates->fetchTwelveDataPrice($symbol);

                    $priceCache[$pair->id] = $price > 0 ? (string) $price : null;
                } catch (Throwable $e) {
                    Log::warning('trade:check-positions price fetch failed', ['pair_id' => $pair->id, 'error' => $e->getMessage()]);
                    $priceCache[$pair->id] = null;
                }
            }

            $price = $priceCache[$pair->id];
            if (!$price) continue;

            $reason = $this->shouldClose($position, $price);
            if (!$reason) continue;

            try {
                $this->service->closePosition($position, $price);
                $this->line("Closed position #{$position->id} ({$reason}) at {$price}");
                Log::info('trade:check-positions auto-closed', [
                    'position_id' => $position->id,
                    'reason'      => $reason,
                    'price'       => $price,
                ]);
            } catch (Throwable $e) {
                Log::warning('trade:check-positions closePosition failed', [
                    'position_id' => $position->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function shouldClose(Position $position, string $price): ?string
    {
        $tp = $position->take_profit ? (string) $position->take_profit : null;
        $sl = $position->stop_loss  ? (string) $position->stop_loss  : null;

        if ($position->side === 'buy') {
            if ($tp && bccomp($price, $tp, 10) >= 0) return 'take_profit';
            if ($sl && bccomp($price, $sl, 10) <= 0) return 'stop_loss';
        } else {
            if ($tp && bccomp($price, $tp, 10) <= 0) return 'take_profit';
            if ($sl && bccomp($price, $sl, 10) >= 0) return 'stop_loss';
        }

        return null;
    }
}
