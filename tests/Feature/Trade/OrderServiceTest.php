<?php

declare(strict_types=1);

use App\Models\Bill;
use App\Models\Currency;
use App\Models\GroupPair;
use App\Models\Pair;
use App\Models\Position;
use App\Models\User;
use App\Services\Trade\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function assertNumericEqual(string $expected, $actual, string $message = ''): void
{
    $actualStr = (string) $actual;
    $cmp = bccomp($actualStr, $expected, 10);
    \PHPUnit\Framework\Assert::assertSame(
        0, $cmp,
        $message !== '' ? $message : "Expected numeric {$expected}, got {$actualStr}"
    );
}

/**
 * These tests verify the core trading math invariants that previously
 * allowed frontend-driven PnL manipulation. The service layer must:
 *   - reserve funds based on server-derived total (amount * price), never
 *     trust a frontend-supplied total,
 *   - maintain the invariant `entry_total = entry_price * quantity` on fill,
 *   - return the correct amount on close for both BUY and SELL (USD-only)
 *     positions, including partial closes,
 *   - handle cancellation by refunding exactly what was reserved.
 */

function makeFixtures(string $initialBalance = '10000'): array
{
    static $seq = 0;
    $seq++;
    $user = new User();
    $user->email    = "order-test-{$seq}@example.com";
    $user->password = bcrypt('secret');
    $user->save();

    $curIn  = Currency::query()->create([
        'name' => 'Bitcoin', 'symbol' => 'BTC', 'code' => 'BTC',
        'icon' => 'x', 'network' => 'n', 'exchange_rate' => '1', 'status' => 'active',
    ]);
    $curOut = Currency::query()->create([
        'name' => 'Tether', 'symbol' => 'USDT', 'code' => 'USDT',
        'icon' => 'x', 'network' => 'n', 'exchange_rate' => '1', 'status' => 'active',
    ]);

    $group = GroupPair::query()->create(['name' => 'Crypto']);

    $pair = Pair::query()->create([
        'currency_id_in'  => $curIn->id,
        'currency_id_out' => $curOut->id,
        'group_id'        => $group->id,
        'is_active'       => true,
    ]);

    $bill = new Bill();
    $bill->user_id     = $user->id;
    $bill->currency_id = $curOut->id;
    $bill->balance     = $initialBalance;
    $bill->save();

    return compact('user', 'pair', 'bill');
}

it('reserves exactly amount * price for a market buy and ignores mismatched client total', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));

    $service = app(OrderService::class);
    $order = $service->createOrder($user->id, $bill, $pair, [
        'side'   => 'buy',
        'type'   => 'market',
        'price'  => '100',
        'amount' => '2',
        'total'  => '999999', // malicious / bogus — must be ignored
    ]);

    assertNumericEqual('200.0000000000', $order->total);
    $bill->refresh();
    assertNumericEqual('9800.0000000000', $bill->balance);
});

it('rejects a market order when balance is insufficient', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('100'));
    $service = app(OrderService::class);

    expect(fn () => $service->createOrder($user->id, $bill, $pair, [
        'side' => 'buy', 'type' => 'market', 'price' => '100', 'amount' => '2',
    ]))->toThrow(InvalidArgumentException::class, 'Insufficient balance');

    $bill->refresh();
    assertNumericEqual('100', $bill->balance); // unchanged
});

it('enforces entry_total = entry_price * quantity invariant on fill', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));
    $service = app(OrderService::class);

    $order = $service->createOrder($user->id, $bill, $pair, [
        'side' => 'buy', 'type' => 'market', 'price' => '100', 'amount' => '3',
    ]);
    // Reserved 300. Fill happens at a slightly different price (e.g. market moved).
    $service->fillOrder($order->fresh(), '101');

    $pos = Position::query()->where('user_id', $user->id)->firstOrFail();
    assertNumericEqual('101.0000000000', $pos->entry_price);
    assertNumericEqual('3.0000000000', $pos->quantity);
    assertNumericEqual('303.0000000000', $pos->entry_total);

    // Delta of 3 (303-300) should have been taken from bill.
    $bill->refresh();
    assertNumericEqual('9697.0000000000', $bill->balance); // 10000 - 303
});

it('returns entry + pnl on full close of a profitable BUY', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));
    $service = app(OrderService::class);

    $order = $service->createOrder($user->id, $bill, $pair, [
        'side' => 'buy', 'type' => 'market', 'price' => '100', 'amount' => '2',
    ]);
    $service->fillOrder($order->fresh(), '100');
    $pos = Position::query()->where('user_id', $user->id)->firstOrFail();

    $service->closePosition($pos, '120');

    $bill->refresh();
    // Entry cost 200 returned + (120-100)*2 = 40 pnl → total 240 back.
    // Start 10000 - 200 reserved + 240 return = 10040.
    assertNumericEqual('10040.0000000000', $bill->balance);

    $pos->refresh();
    expect($pos->status)->toBe('closed');
    assertNumericEqual('40.0000000000', $pos->realized_pnl);
    assertNumericEqual('120.0000000000', $pos->close_price);
});

it('computes correct PnL on full close of a losing SELL (short)', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));
    $service = app(OrderService::class);

    $order = $service->createOrder($user->id, $bill, $pair, [
        'side' => 'sell', 'type' => 'market', 'price' => '100', 'amount' => '5',
    ]);
    $service->fillOrder($order->fresh(), '100');
    $pos = Position::query()->where('user_id', $user->id)->firstOrFail();

    // Short went against us: close at 110, sold at 100 → loss of 10*5 = 50.
    $service->closePosition($pos, '110');

    $bill->refresh();
    // Reserved 500, return 500 + (100-110)*5 = 500 - 50 = 450.
    // Start 10000 - 500 + 450 = 9950.
    assertNumericEqual('9950.0000000000', $bill->balance);

    $pos->refresh();
    assertNumericEqual('-50.0000000000', $pos->realized_pnl);
});

it('handles partial close proportionally and preserves remaining entry_total', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));
    $service = app(OrderService::class);

    $order = $service->createOrder($user->id, $bill, $pair, [
        'side' => 'buy', 'type' => 'market', 'price' => '100', 'amount' => '4',
    ]);
    $service->fillOrder($order->fresh(), '100');
    $pos = Position::query()->where('user_id', $user->id)->firstOrFail();

    // Close 1 of 4 units at 150 → closed entry = 100, pnl = 50, return 150.
    $snapshot = $service->closePosition($pos, '150', '1');

    $bill->refresh();
    // 10000 - 400 reserved + 150 return = 9750.
    assertNumericEqual('9750.0000000000', $bill->balance);

    expect($snapshot->status)->toBe('partial');
    assertNumericEqual('50.0000000000', $snapshot->realized_pnl);

    $pos->refresh();
    expect($pos->status)->toBe('open');
    assertNumericEqual('3.0000000000', $pos->quantity);
    // Remaining entry_total must equal entry_price * remaining_qty (100 * 3 = 300).
    assertNumericEqual('300.0000000000', $pos->entry_total);
});

it('clamps oversized close quantity to available', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));
    $service = app(OrderService::class);

    $order = $service->createOrder($user->id, $bill, $pair, [
        'side' => 'buy', 'type' => 'market', 'price' => '100', 'amount' => '2',
    ]);
    $service->fillOrder($order->fresh(), '100');
    $pos = Position::query()->where('user_id', $user->id)->firstOrFail();

    $service->closePosition($pos, '110', '9999'); // ask for more than held

    $pos->refresh();
    expect($pos->status)->toBe('closed');
    // pnl should be for full 2 units: (110-100)*2 = 20.
    assertNumericEqual('20.0000000000', $pos->realized_pnl);
});

it('refunds exactly the reserved total when a queued limit order is cancelled', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));
    $service = app(OrderService::class);

    $order = $service->createOrder($user->id, $bill, $pair, [
        'side' => 'buy', 'type' => 'limit', 'price' => '50', 'amount' => '3',
    ]);
    $bill->refresh();
    assertNumericEqual('9850.0000000000', $bill->balance);

    $service->cancelOrder($order);
    $bill->refresh();
    assertNumericEqual('10000.0000000000', $bill->balance);
});

it('rejects close with non-positive price', function (): void {
    [$user, $pair, $bill] = array_values(makeFixtures('10000'));
    $service = app(OrderService::class);

    $order = $service->createOrder($user->id, $bill, $pair, [
        'side' => 'buy', 'type' => 'market', 'price' => '100', 'amount' => '1',
    ]);
    $service->fillOrder($order->fresh(), '100');
    $pos = Position::query()->where('user_id', $user->id)->firstOrFail();

    expect(fn () => $service->closePosition($pos, '0'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->closePosition($pos, '-1'))
        ->toThrow(InvalidArgumentException::class);
});
