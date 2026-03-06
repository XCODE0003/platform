<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\MarketData\UpdateRates;
use App\Services\Trade\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use function bccomp;

/**
 * Checks all queued limit/stop orders and fills them when price condition is met.
 *
 * Limit BUY  → fill when currentPrice <= limitPrice   (market dropped to your price)
 * Limit SELL → fill when currentPrice >= limitPrice   (market rose to your price)
 * Stop  BUY  → fill when currentPrice >= stopPrice    (breakout above stop)
 * Stop  SELL → fill when currentPrice <= stopPrice    (breakout below stop)
 */
class CheckLimitOrdersCommand extends Command
{
    protected $signature   = 'trade:check-limit-orders';
    protected $description = 'Fill queued limit/stop orders when current price meets the condition';

    public function __construct(private readonly OrderService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $orders = Order::query()
            ->whereIn('status', ['queued', 'open'])
            ->whereIn('type', ['limit', 'stop'])
            ->with(['pair.currencyIn'])
            ->get();

        if ($orders->isEmpty()) {
            return self::SUCCESS;
        }

        $priceCache = [];
        $rates      = new UpdateRates();

        foreach ($orders as $order) {
            $pair = $order->pair;
            if (!$pair) {
                continue;
            }

            // Fetch market price (cached per pair)
            if (!isset($priceCache[$pair->id])) {
                try {
                    $symbol = $pair->currencyIn->symbol ?? '';
                    $price  = $pair->default_source === 'binance'
                        ? $rates->fetchBinancePrice($symbol . 'USDT')
                        : $rates->fetchTwelveDataPrice($symbol);

                    $priceCache[$pair->id] = $price > 0 ? (string) $price : null;
                } catch (Throwable $e) {
                    Log::warning('trade:check-limit-orders price fetch failed', [
                        'pair_id' => $pair->id,
                        'error'   => $e->getMessage(),
                    ]);
                    $priceCache[$pair->id] = null;
                }
            }

            $currentPrice = $priceCache[$pair->id];
            if (!$currentPrice) {
                continue;
            }

            if (!$this->shouldFill($order, $currentPrice)) {
                continue;
            }

            try {
                $this->service->fillOrder($order, $currentPrice);
                $this->line("Filled order #{$order->id} ({$order->type} {$order->side}) at {$currentPrice}");
                Log::info('trade:check-limit-orders filled', [
                    'order_id' => $order->id,
                    'type'     => $order->type,
                    'side'     => $order->side,
                    'price'    => $currentPrice,
                ]);
            } catch (Throwable $e) {
                Log::warning('trade:check-limit-orders fillOrder failed', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function shouldFill(Order $order, string $currentPrice): bool
    {
        if ($order->type === 'limit') {
            $limitPrice = (string) $order->price;
            if (!$limitPrice || $limitPrice === '0') {
                return false;
            }
            // Buy limit: fill when market <= limit (price dropped to your buy price)
            if ($order->side === 'buy') {
                return bccomp($currentPrice, $limitPrice, 10) <= 0;
            }
            // Sell limit: fill when market >= limit (price rose to your sell price)
            return bccomp($currentPrice, $limitPrice, 10) >= 0;
        }

        if ($order->type === 'stop') {
            $stopPrice = (string) $order->stop_price;
            if (!$stopPrice || $stopPrice === '0') {
                return false;
            }
            // Stop buy: fill when market >= stop (breakout upward)
            if ($order->side === 'buy') {
                return bccomp($currentPrice, $stopPrice, 10) >= 0;
            }
            // Stop sell: fill when market <= stop (breakout downward)
            return bccomp($currentPrice, $stopPrice, 10) <= 0;
        }

        return false;
    }
}
