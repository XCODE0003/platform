<?php

declare(strict_types=1);

namespace App\Services\Trade;

use App\Models\Bill;
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
    private const SCALE = 10;
    private const HIGH_SCALE = 20;

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

            // amount (base qty) is the canonical user-provided quantity.
            // `total` is ALWAYS derived from amount * effective_price on the server.
            // Never trust client-supplied total — it can be inconsistent with amount.
            $amount = (string) ($data['amount'] ?? '0');
            if (!$this->isPositive($amount)) {
                throw new InvalidArgumentException('Amount must be positive');
            }

            $effectivePrice = $this->effectiveReservePrice($type, $data);
            $total = $effectivePrice !== null
                ? bcmul($amount, $effectivePrice, self::SCALE)
                : null;

            // For market orders, caller MUST supply a resolved server-side price
            // so we can reserve funds. Leave it to the controller/service to
            // resolve via MarketPriceResolver before calling createOrder.
            if ($type === 'market' && $total === null) {
                throw new InvalidArgumentException('Market orders require a resolved price');
            }

            // Reload bill with row lock to avoid TOCTOU balance races.
            $lockedBill = Bill::query()->whereKey($bill->id)->lockForUpdate()->firstOrFail();

            if ($total !== null && bccomp((string) $lockedBill->balance, $total, self::SCALE) < 0) {
                throw new InvalidArgumentException('Insufficient balance');
            }
            if ($total !== null) {
                $lockedBill->balance = bcsub((string) $lockedBill->balance, $total, self::SCALE);
                $lockedBill->save();
            }

            $status = $type === 'market' ? 'open' : 'queued';
            $order = new Order([
                'user_id'     => $userId,
                'pair_id'     => $pair->id,
                'bill_id'     => $lockedBill->id,
                'side'        => $side,
                'type'        => $type,
                'tif'         => $data['tif'] ?? null,
                'post_only'   => (bool) ($data['post_only'] ?? false),
                'price'       => $data['price'] ?? null,
                'stop_price'  => $data['stop_price'] ?? null,
                'amount'      => $amount,
                'total'       => $total,
                'stops_mode'  => $data['stops_mode'] ?? 'none',
                'take_profit' => $data['take_profit'] ?? null,
                'stop_loss'   => $data['stop_loss'] ?? null,
                'status'      => $status,
            ]);
            $order->save();

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
                $bill->balance = bcadd((string) $bill->balance, (string) $order->total, self::SCALE);
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
            if (!$this->isPositive($price)) {
                throw new InvalidArgumentException('Invalid fill price');
            }

            $order->pair()->lockForUpdate()->first();
            $bill = $order->bill()->lockForUpdate()->first();

            $amount = (string) $order->amount;
            if (!$this->isPositive($amount)) {
                throw new InvalidArgumentException('Invalid order amount');
            }

            // Canonical entry total is always amount * fillPrice. Any mismatch
            // between previously reserved total (from createOrder) and the fill
            // total is reconciled against the bill here so the bookkeeping stays
            // consistent with the position's entry_total invariant.
            $reservedTotal = $order->total !== null ? (string) $order->total : '0';
            $fillTotal = bcmul($amount, $price, self::SCALE);

            // If fill total > reserved, take the delta from the bill. If <, refund.
            $diff = bcsub($fillTotal, $reservedTotal, self::SCALE);
            $cmp = bccomp($diff, '0', self::SCALE);
            if ($cmp > 0) {
                if (bccomp((string) $bill->balance, $diff, self::SCALE) < 0) {
                    throw new InvalidArgumentException('Insufficient balance to complete fill');
                }
                $bill->balance = bcsub((string) $bill->balance, $diff, self::SCALE);
                $bill->save();
            } elseif ($cmp < 0) {
                // negate
                $refund = bcsub('0', $diff, self::SCALE);
                $bill->balance = bcadd((string) $bill->balance, $refund, self::SCALE);
                $bill->save();
            }

            // Each filled order creates its own independent position.
            // Invariant: entry_total = entry_price * quantity.
            $position = new Position([
                'user_id'     => $order->user_id,
                'pair_id'     => $order->pair_id,
                'bill_id'     => $order->bill_id,
                'side'        => $order->side,
                'entry_price' => $price,
                'quantity'    => $amount,
                'entry_total' => $fillTotal,
                'take_profit' => $order->take_profit,
                'stop_loss'   => $order->stop_loss,
                'status'      => 'open',
            ]);
            $position->save();

            $order->price  = $price;
            $order->total  = $fillTotal;
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
            if (!$this->isPositive($rate)) {
                return;
            }

            $swapAmount = bcmul((string) $position->entry_total, $rate, self::SCALE);

            $bill = $position->bill()->lockForUpdate()->first();
            $bill->balance = bcsub((string) $bill->balance, $swapAmount, self::SCALE);
            $bill->save();

            $position->swap = bcadd((string) $position->swap, $swapAmount, self::SCALE);
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
            if (!$this->isPositive($price)) {
                throw new InvalidArgumentException('Invalid close price');
            }

            $totalQty = (string) $position->quantity;
            if (!$this->isPositive($totalQty)) {
                throw new InvalidArgumentException('Invalid position quantity');
            }

            // Clamp requested close quantity to available quantity.
            if ($closeQty !== null && $this->isPositive($closeQty)) {
                $qty = bccomp($closeQty, $totalQty, self::SCALE) >= 0 ? $totalQty : $closeQty;
            } else {
                $qty = $totalQty;
            }

            $isPartial = bccomp($qty, $totalQty, self::SCALE) < 0;

            $bill = $position->bill()->lockForUpdate()->first();

            // PnL for closed portion
            $priceDiff = ($position->side === 'buy')
                ? bcsub($price, (string) $position->entry_price, self::SCALE)
                : bcsub((string) $position->entry_price, $price, self::SCALE);
            $realizedPnl = bcmul($priceDiff, $qty, self::SCALE);

            // Entry cost proportional to closed qty — derived from the stored
            // entry_total to preserve the original reservation even if rounding
            // had diverged from entry_price * quantity over time.
            $closedEntryTotal = bcdiv(
                bcmul((string) $position->entry_total, $qty, self::HIGH_SCALE),
                $totalQty,
                self::SCALE
            );

            // Return entry cost + PnL to bill
            $returnAmount = bcadd($closedEntryTotal, $realizedPnl, self::SCALE);
            $bill->balance = bcadd((string) $bill->balance, $returnAmount, self::SCALE);
            $bill->save();

            if ($isPartial) {
                $remainingQty = bcsub($totalQty, $qty, self::SCALE);
                // Preserve per-unit entry cost: remainder keeps proportional share.
                $remainingEntryTotal = bcsub((string) $position->entry_total, $closedEntryTotal, self::SCALE);
                $position->quantity    = $remainingQty;
                $position->entry_total = $remainingEntryTotal;
                $position->save();

                $snapshot = new Position($position->toArray());
                $snapshot->quantity     = $qty;
                $snapshot->entry_total  = $closedEntryTotal;
                $snapshot->close_price  = $price;
                $snapshot->close_total  = bcmul($price, $qty, self::SCALE);
                $snapshot->realized_pnl = $realizedPnl;
                $snapshot->status       = 'partial';

                return $snapshot;
            }

            // Full close
            $position->status       = 'closed';
            $position->closed_at    = now();
            $position->close_reason = $reason;
            $position->close_price  = $price;
            $position->close_total  = bcmul($price, $qty, self::SCALE);
            $position->realized_pnl = $realizedPnl;
            $position->save();

            return $position;
        });
    }

    private function isPositive(string $value): bool
    {
        return is_numeric($value) && bccomp($value, '0', self::SCALE) > 0;
    }

    /**
     * The price used to reserve quote funds at creation time.
     * Returns null for order types that are not reservable without a price.
     */
    private function effectiveReservePrice(string $type, array $data): ?string
    {
        $raw = match ($type) {
            'market', 'limit' => $data['price'] ?? null,
            'stop'            => $data['stop_price'] ?? null,
            default           => null,
        };
        if ($raw === null || $raw === '' || !is_numeric($raw)) {
            return null;
        }
        $s = (string) $raw;
        return $this->isPositive($s) ? $s : null;
    }
}
