<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BarUpdated;
use App\Models\Order;
use App\Models\Position;
use App\Services\Trade\OrderService;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessOrdersOnBarUpdate
{
    public function __construct(private readonly OrderService $service) {}

    public function handle(BarUpdated $event): void
    {
        $pairId = $event->pairId;
        $price  = (string) $event->close;

        if (bccomp($price, '0', 10) <= 0) return;

        $this->matchPendingOrders($pairId, $price);
        $this->checkTakeProfitStopLoss($pairId, $price);
    }

    // ── Order matching (limit / stop / market) ───────────────────────────────

    private function matchPendingOrders(int $pairId, string $price): void
    {
        $orders = Order::query()
            ->where('pair_id', $pairId)
            ->whereIn('status', ['open', 'queued'])
            ->orderBy('id')
            ->limit(200)
            ->get();

        foreach ($orders as $order) {
            $shouldFill = match ($order->type) {
                'market' => true,
                'limit'  => $this->limitShouldFill($order->side, $price, (string) $order->price),
                'stop'   => $this->stopShouldFill($order->side, $price, (string) $order->stop_price),
                default  => false,
            };

            if (!$shouldFill) continue;

            try {
                $this->service->fillOrder($order, $price);
            } catch (Throwable $e) {
                Log::warning('ProcessOrdersOnBarUpdate: fillOrder failed', [
                    'order_id' => $order->id,
                    'price'    => $price,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    private function limitShouldFill(string $side, string $price, string $orderPrice): bool
    {
        if (bccomp($orderPrice, '0', 10) <= 0) return false;
        return $side === 'buy'
            ? bccomp($price, $orderPrice, 10) <= 0  // buy limit: fills when price ≤ order price
            : bccomp($price, $orderPrice, 10) >= 0; // sell limit: fills when price ≥ order price
    }

    private function stopShouldFill(string $side, string $price, string $stopPrice): bool
    {
        if (bccomp($stopPrice, '0', 10) <= 0) return false;
        return $side === 'buy'
            ? bccomp($price, $stopPrice, 10) >= 0  // buy stop: fills when price ≥ stop price
            : bccomp($price, $stopPrice, 10) <= 0; // sell stop: fills when price ≤ stop price
    }

    // ── Take Profit / Stop Loss ───────────────────────────────────────────────

    private function checkTakeProfitStopLoss(int $pairId, string $price): void
    {
        $positions = Position::query()
            ->where('pair_id', $pairId)
            ->where('status', 'open')
            ->whereRaw('(take_profit IS NOT NULL OR stop_loss IS NOT NULL)')
            ->get();

        foreach ($positions as $position) {
            $reason = $this->shouldClose($position, $price);
            if (!$reason) continue;

            try {
                $this->service->closePosition($position, $price);
                Log::info('ProcessOrdersOnBarUpdate: position auto-closed', [
                    'position_id' => $position->id,
                    'reason'      => $reason,
                    'price'       => $price,
                ]);
            } catch (Throwable $e) {
                Log::warning('ProcessOrdersOnBarUpdate: closePosition failed', [
                    'position_id' => $position->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }

    private function shouldClose(Position $position, string $price): ?string
    {
        $tp = $position->take_profit ? (string) $position->take_profit : null;
        $sl = $position->stop_loss  ? (string) $position->stop_loss  : null;

        if ($position->side === 'buy') {
            // Long: TP выше входа, SL ниже входа
            if ($tp && bccomp($price, $tp, 10) >= 0) return 'take_profit';
            if ($sl && bccomp($price, $sl, 10) <= 0) return 'stop_loss';
        } else {
            // Short: TP ниже входа, SL выше входа
            if ($tp && bccomp($price, $tp, 10) <= 0) return 'take_profit';
            if ($sl && bccomp($price, $sl, 10) >= 0) return 'stop_loss';
        }

        return null;
    }
}
