<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\CreateOrderRequest;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Position;
use App\Models\Pair;
use App\Services\MarketData\MarketPriceResolver;
use App\Services\Trade\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $service,
        private readonly MarketPriceResolver $prices,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = Order::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();
        $positions = Position::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();
        return response()->json([
            'orders' => $orders,
            'positions' => $positions,
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $bill = Bill::query()->whereKey($validated['bill_id'])->where('user_id', $user->id)->firstOrFail();
        $pair = Pair::query()->whereKey($validated['pair_id'])->firstOrFail();

        // For market orders, the execution price is always resolved server-side.
        // The client-submitted `price` field is ignored here — it cannot be trusted
        // as a source of truth for money movement.
        if ($validated['type'] === 'market') {
            try {
                $marketPrice = $this->prices->resolve($pair);
            } catch (InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            $validated['price'] = $marketPrice;
        }
        // For limit/stop orders the user-supplied price IS the trigger level
        // (limit price or stop price). Execution still fills at the triggering
        // market tick later — trusted via CheckLimitOrdersCommand / onTick.

        try {
            $order = $this->service->createOrder(
                userId: $user->id,
                bill: $bill,
                pair: $pair,
                data: $validated,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        // Market orders fill immediately at the server-resolved price.
        if ($order->type === 'market') {
            $order = $this->service->fillOrder($order->fresh(), (string) $validated['price']);
        }

        return response()->json([
            'order' => $order->fresh(),
            'bills' => $this->serializeBills($user),
        ], 201);
    }

    public function cancel(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = Order::query()->where('user_id', $user->id)->whereKey($orderId)->firstOrFail();
        $this->service->cancelOrder($order);
        return response()->json([
            'status' => 'ok',
            'bills' => $this->serializeBills($user),
        ]);
    }

    // Socket tick endpoint. Requires a shared secret header to avoid
    // unauthenticated callers injecting arbitrary prices that would trigger
    // TP/SL closes or limit-order fills. The secret lives in env and is not
    // exposed to the client bundle.
    public function onTick(Request $request): JsonResponse
    {
        $expected = (string) config('services.trade_tick.secret', '');
        $provided = (string) $request->header('X-Tick-Secret', '');
        if ($expected === '' || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'pair_id' => ['required', 'integer', 'exists:pairs,id'],
            'price'   => ['required', 'numeric', 'gt:0'],
        ]);

        $pairId = (int) $validated['pair_id'];
        $price  = (string) $validated['price'];

        $open = Order::query()
            ->where('pair_id', $pairId)
            ->whereIn('status', ['open', 'queued'])
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($open as $order) {
            $shouldFill = false;
            if ($order->type === 'limit') {
                if ($order->side === 'buy'  && bccomp($price, (string) $order->price, 10) <= 0) $shouldFill = true;
                if ($order->side === 'sell' && bccomp($price, (string) $order->price, 10) >= 0) $shouldFill = true;
            } elseif ($order->type === 'stop') {
                if ($order->side === 'buy'  && bccomp($price, (string) $order->stop_price, 10) >= 0) $shouldFill = true;
                if ($order->side === 'sell' && bccomp($price, (string) $order->stop_price, 10) <= 0) $shouldFill = true;
            }
            if ($shouldFill) {
                $this->service->fillOrder($order, $price);
            }
        }

        $positions = Position::query()
            ->where('pair_id', $pairId)
            ->where('status', 'open')
            ->where(function ($q): void {
                $q->whereNotNull('take_profit')->orWhereNotNull('stop_loss');
            })
            ->get();

        foreach ($positions as $position) {
            $reason = null;
            if ($position->take_profit) {
                if ($position->side === 'buy'  && bccomp($price, (string) $position->take_profit, 10) >= 0) $reason = 'take_profit';
                if ($position->side === 'sell' && bccomp($price, (string) $position->take_profit, 10) <= 0) $reason = 'take_profit';
            }
            if ($reason === null && $position->stop_loss) {
                if ($position->side === 'buy'  && bccomp($price, (string) $position->stop_loss, 10) <= 0) $reason = 'stop_loss';
                if ($position->side === 'sell' && bccomp($price, (string) $position->stop_loss, 10) >= 0) $reason = 'stop_loss';
            }
            if ($reason !== null) {
                $this->service->closePosition($position, $price, null, $reason);
            }
        }

        return response()->json(['filled' => true]);
    }

    public function updateTpSl(Request $request, int $positionId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'take_profit' => ['nullable', 'numeric', 'gt:0'],
            'stop_loss'   => ['nullable', 'numeric', 'gt:0'],
        ]);
        $position = Position::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->whereKey($positionId)
            ->firstOrFail();

        $position->take_profit = $validated['take_profit'] ?? null;
        $position->stop_loss   = $validated['stop_loss']   ?? null;
        $position->save();

        return response()->json($position);
    }

    public function closePosition(Request $request, int $positionId): JsonResponse
    {
        $user = $request->user();
        // User can only choose HOW MUCH to close, not AT WHAT price.
        // Price is always resolved on the server to prevent PnL manipulation.
        $validated = $request->validate([
            'quantity' => ['sometimes', 'numeric', 'gt:0'],
        ]);
        $position = Position::query()->where('user_id', $user->id)->whereKey($positionId)->firstOrFail();
        $pair = $position->pair()->firstOrFail();

        try {
            $price = $this->prices->resolve($pair);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $result = $this->service->closePosition(
            $position,
            $price,
            isset($validated['quantity']) ? (string) $validated['quantity'] : null,
        );
        return response()->json([
            ...$result->toArray(),
            'bills' => $this->serializeBills($user),
        ]);
    }

    /**
     * Load the latest bills with currency relation for the authenticated user.
     */
    private function serializeBills($user): Collection
    {
        return $user->bills()->with('currency')->get();
    }
}
