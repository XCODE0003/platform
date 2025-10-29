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
use App\Services\MarketData\UpdateRates;

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

            // Create position immediately for all order types
            $entryPrice = $data['price'] ?? $data['stop_price'] ?? null;
            if ($entryPrice !== null) {
                $position = new Position([
                    'user_id' => $userId,
                    'pair_id' => $pair->id,
                    'bill_id' => $bill->id,
                    'side' => $side,
                    'entry_price' => (string) $entryPrice,
                    'quantity' => $amount,
                    'entry_total' => $total,
                    'take_profit' => $data['take_profit'] ?? null,
                    'stop_loss' => $data['stop_loss'] ?? null,
                    'status' => 'open',
                ]);
                $position->save();


            }

            return $order;
        });
    }

    public function cancelOrder(Order $order): void
    {
        if (in_array($order->status, ['filled', 'cancelled', 'rejected'], true)) {
            return;
        }


        DB::transaction(function () use ($order): void {
            // Refund reserved funds to bill
            $bill = $order->bill()->lockForUpdate()->first();
            $pair = $order->pair;
            if($pair->default_source === 'binance') {
                $marketPrice = (new UpdateRates())->fetchBinancePrice($pair->currencyIn->symbol . 'USDT');
            } else {
                $marketPrice = (new UpdateRates())->fetchTwelveDataPrice($pair->currencyIn->symbol );

            }


            // Базовая цена ордера для расчета (limit или stop)
            $anchorPrice = $order->price ?? $order->stop_price;

            // Кол-во в базовой валюте
            $qty = (string) $order->amount;

            // per-unit diff с правильным направлением
            $perUnit = '0';
            if ($anchorPrice !== null) {
                $perUnit = $order->side === 'buy'
                    ? bcsub((string) $marketPrice, (string) $anchorPrice, 10)
                    : bcsub((string) $anchorPrice, (string) $marketPrice, 10);
            }

            // Полный PnL = diff * qty
            $pnl = bcmul($perUnit, $qty, 10);

            if ($order->total !== null) {
                $bill->balance = bcadd((string) $bill->balance, (string) $order->total, 10);
            }
            $bill->balance = bcadd((string) $bill->balance, (string) $pnl, 10);
            $bill->save();


            $position = Position::query()->where('user_id', $order->user_id)->where('pair_id', $order->pair_id)->where('bill_id', $order->bill_id)->where('status', 'open')->first();
            if ($position) {
                $position->take_profit = $pnl;
                $position->status = 'closed';
                $position->save();
            }
            $order->take_profit = $pnl;
            $order->status = 'filled'; // если хотите именно 'cancelled', замените, но тогда решите где хранить PnL
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

            // Create/merge position on fill (USD-only model)
            $position = Position::query()
                ->where('user_id', $order->user_id)
                ->where('pair_id', $order->pair_id)
                ->where('bill_id', $order->bill_id)
                ->where('status', 'open')
                ->first();

            if (!$position) {
                $position = new Position([
                    'user_id' => $order->user_id,
                    'pair_id' => $order->pair_id,
                    'bill_id' => $order->bill_id,
                    'side' => $order->side,
                    'entry_price' => $price,
                    'quantity' => $amount,
                    'entry_total' => $total,
                    'take_profit' => $order->take_profit,
                    'stop_loss' => $order->stop_loss,
                    'status' => 'open',
                ]);
                $position->save();
            } else {
                // simple average price merge for same side; if different side, reduce/flip later
                if ($position->side === $order->side) {
                    $newQty = bcadd((string) $position->quantity, $amount, 10);
                    $newCost = bcadd((string) $position->entry_total, $total, 10);
                    $position->quantity = $newQty;
                    $position->entry_total = $newCost;
                    $position->entry_price = bcdiv($newCost, $newQty, 10);
                    $position->save();
                } else {
                    // opposite side -> reduce position (partial close)
                    $reduceQty = min((float) $position->quantity, (float) $amount);
                    $reduceQtyStr = (string) $reduceQty;
                    // realized pnl = (closePrice - entryPrice) * qty for long; reversed for short
                    $priceDiff = ($position->side === 'buy')
                        ? bcsub($price, (string) $position->entry_price, 10)
                        : bcsub((string) $position->entry_price, $price, 10);
                    $realized = bcmul($priceDiff, $reduceQtyStr, 10);
                    $position->quantity = bcsub((string) $position->quantity, $reduceQtyStr, 10);
                    $position->entry_total = bcmul((string) $position->entry_price, (string) $position->quantity, 10);
                    if (bccomp((string) $position->quantity, '0', 10) <= 0) {
                        $position->status = 'closed';
                        $position->close_price = $price;
                        $position->close_total = bcmul($price, $reduceQtyStr, 10);
                        $position->realized_pnl = $realized;
                    }
                    $position->save();
                }
            }

            // update order as filled with final price/total
            $order->price = $order->price ?: $price;
            $order->total = $order->total ?: $total;
            $order->status = 'filled';
            $order->save();

            return $order;
        });
    }

    public function closePosition(Position $position, string $closePrice): Position
    {
        return DB::transaction(function () use ($position, $closePrice): Position {
            if ($position->status === 'closed') {
                return $position;
            }

            $price = (string) $closePrice;
            if (bccomp($price, '0', 10) <= 0) {
                throw new InvalidArgumentException('Invalid close price');
            }

            $bill = $position->bill()->lockForUpdate()->first();
            $qty = (string) $position->quantity;
            $entryTotal = (string) $position->entry_total;

            // Calculate PnL: for buy (long) -> (close - entry) * qty; for sell (short) -> (entry - close) * qty
            $priceDiff = ($position->side === 'buy')
                ? bcsub($price, (string) $position->entry_price, 10)
                : bcsub((string) $position->entry_price, $price, 10);
            $realizedPnl = bcmul($priceDiff, $qty, 10);

            // Refund entry total + PnL to bill
            $returnAmount = bcadd($entryTotal, $realizedPnl, 10);
            $bill->balance = bcadd((string) $bill->balance, $returnAmount, 10);
            $bill->save();

            // Update position
            $position->status = 'closed';
            $position->close_price = $price;
            $position->close_total = bcmul($price, $qty, 10);
            $position->realized_pnl = $realizedPnl;
            $position->save();

            return $position;
        });
    }
}


