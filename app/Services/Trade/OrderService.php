<?php

declare(strict_types=1);

namespace App\Services\Trade;

use App\Models\Bill;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Position;
use App\Models\Pair;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use function bccomp;
use function bcmul;
use function bcsub;
use function bcadd;
use function bcdiv;

class OrderService
{
    public function createOrder(int $userId, Bill $bill, Pair $pair, array $data): Order
    {
        return DB::transaction(function () use ($userId, $bill, $pair, $data): Order {
            $side = $data['side'];
            $type = $data['type'];

            // Validate price requirements
            if ($type === 'limit' && empty($data['price'])) {
                throw new InvalidArgumentException('Price is required for limit orders');
            }
            if ($type === 'stop' && empty($data['stop_price'])) {
                throw new InvalidArgumentException('Stop price is required for stop orders');
            }

            // Reserve funds (simple hold in bill.balance for now -> move to pending in future)
            $amount = (string) ($data['amount'] ?? '0');
            $total = (string) ($data['total'] ?? '0');

            // USD-only model: both buy and sell hold quote (USD) total from the same bill
            if (bccomp($total, '0', 10) <= 0 && bccomp($amount, '0', 10) > 0) {
                $effectivePrice = null;
                if ($type === 'market' && !empty($data['price'])) {
                    $effectivePrice = (string) $data['price'];
                } elseif ($type === 'limit' && !empty($data['price'])) {
                    $effectivePrice = (string) $data['price'];
                } elseif ($type === 'stop' && !empty($data['stop_price'])) {
                    $effectivePrice = (string) $data['stop_price'];
                }
                if ($effectivePrice !== null) {
                    $total = bcmul($amount, $effectivePrice, 10);
                }
            }

            if (bccomp((string) $bill->balance, $total, 10) < 0) {
                throw new InvalidArgumentException('Insufficient balance');
            }
            // Lock USD funds
            $bill->balance = bcsub((string) $bill->balance, $total, 10);
            $bill->save();

            $status = $type === 'market' ? 'open' : 'queued';
            $order = new Order([
                'user_id' => $userId,
                'pair_id' => $pair->id,
                'bill_id' => $bill->id,
                'side' => $side,
                'type' => $type,
                'tif' => $data['tif'] ?? null,
                'post_only' => (bool) ($data['post_only'] ?? false),
                'price' => $data['price'] ?? null,
                'stop_price' => $data['stop_price'] ?? null,
                'amount' => $amount,
                'total' => $total ?: null,
                'stops_mode' => $data['stops_mode'] ?? 'none',
                'take_profit' => $data['take_profit'] ?? null,
                'stop_loss' => $data['stop_loss'] ?? null,
                'status' => $status,
            ]);
            $order->save();

            // Position is created in fillOrder() once the order is actually matched.
            // Do NOT create a position here to avoid double-counting when fillOrder merges.

            return $order;
        });
    }

    public function cancelOrder(Order $order): void
    {
        if (in_array($order->status, ['filled', 'cancelled', 'rejected'], true)) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $bill = $order->bill()->lockForUpdate()->first();

            // Return the locked funds — no PnL for a pending order that was never executed
            if ($order->total !== null) {
                $bill->balance = bcadd((string) $bill->balance, (string) $order->total, 10);
            }
            $bill->save();

            $order->status = 'cancelled';
            $order->save();
        });
    }

    public function fillOrder(Order $order, string $fillPrice): Order
    {
        return DB::transaction(function () use ($order, $fillPrice): Order {
            if (in_array($order->status, ['filled', 'cancelled', 'rejected'], true)) {
                return $order;
            }

            $price = (string) $fillPrice;
            if (bccomp($price, '0', 10) <= 0) {
                throw new InvalidArgumentException('Invalid fill price');
            }

            $pair = $order->pair()->lockForUpdate()->first();
            $bill = $order->bill()->lockForUpdate()->first();

            // compute totals
            $amount = (string) $order->amount; // base qty
            $total = $order->total ? (string) $order->total : bcmul($amount, $price, 10); // quote amount

            // Each filled order creates its own independent position
            $position = new Position([
                'user_id'      => $order->user_id,
                'pair_id'      => $order->pair_id,
                'bill_id'      => $order->bill_id,
                'side'         => $order->side,
                'entry_price'  => $price,
                'quantity'     => $amount,
                'entry_total'  => $total,
                'take_profit'  => $order->take_profit,
                'stop_loss'    => $order->stop_loss,
                'status'       => 'open',
            ]);
            $position->save();

            // update order as filled with final price/total
            $order->price = $order->price ?: $price;
            $order->total = $order->total ?: $total;
            $order->status = 'filled';
            $order->save();

            return $order;
        });
    }

    public function applySwap(Position $position): void
    {
        DB::transaction(function () use ($position): void {
            $pair = $position->pair()->first();
            $rate = (string) ($pair?->swap_rate ?? '0.0003');
            if (bccomp($rate, '0', 10) <= 0) {
                return;
            }

            $swapAmount = bcmul((string) $position->entry_total, $rate, 10);

            $bill = $position->bill()->lockForUpdate()->first();
            $bill->balance = bcsub((string) $bill->balance, $swapAmount, 10);
            $bill->save();

            $position->swap = bcadd((string) $position->swap, $swapAmount, 10);
            $position->save();
        });
    }

    public function closePosition(Position $position, string $closePrice, ?string $closeQty = null, string $reason = 'manual'): Position
    {
        return DB::transaction(function () use ($position, $closePrice, $closeQty, $reason): Position {
            if ($position->status === 'closed') {
                return $position;
            }

            $price = (string) $closePrice;
            if (bccomp($price, '0', 10) <= 0) {
                throw new InvalidArgumentException('Invalid close price');
            }

            $totalQty = (string) $position->quantity;

            // Clamp requested close quantity to available quantity
            if ($closeQty !== null && bccomp($closeQty, '0', 10) > 0) {
                $qty = bccomp($closeQty, $totalQty, 10) >= 0 ? $totalQty : $closeQty;
            } else {
                $qty = $totalQty; // full close
            }

            $isPartial = bccomp($qty, $totalQty, 10) < 0;

            $bill = $position->bill()->lockForUpdate()->first();

            // PnL for closed portion
            $priceDiff = ($position->side === 'buy')
                ? bcsub($price, (string) $position->entry_price, 10)
                : bcsub((string) $position->entry_price, $price, 10);
            $realizedPnl = bcmul($priceDiff, $qty, 10);

            // Entry cost proportional to closed qty
            $closedEntryTotal = bcmul((string) $position->entry_price, $qty, 10);

            // Return entry cost + PnL to bill
            $returnAmount = bcadd($closedEntryTotal, $realizedPnl, 10);
            $bill->balance = bcadd((string) $bill->balance, $returnAmount, 10);
            $bill->save();

            if ($isPartial) {
                // Reduce open position — keep it open with remaining quantity
                $remainingQty = bcsub($totalQty, $qty, 10);
                $position->quantity    = $remainingQty;
                $position->entry_total = bcmul((string) $position->entry_price, $remainingQty, 10);
                $position->save();

                // Return a synthetic snapshot of what was closed (not persisted as separate row)
                $snapshot = new Position($position->toArray());
                $snapshot->quantity    = $qty;
                $snapshot->entry_total = $closedEntryTotal;
                $snapshot->close_price = $price;
                $snapshot->close_total = bcmul($price, $qty, 10);
                $snapshot->realized_pnl = $realizedPnl;
                $snapshot->status = 'partial';

                return $snapshot;
            }

            // Full close
            $position->status        = 'closed';
            $position->closed_at     = now();
            $position->close_reason  = $reason;
            $position->close_price   = $price;
            $position->close_total   = bcmul($price, $qty, 10);
            $position->realized_pnl  = $realizedPnl;
            $position->save();

            return $position;
        });
    }
}


