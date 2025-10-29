<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trade\CreateOrderRequest;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Position;
use App\Models\Pair;
use App\Services\Trade\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $service) {}

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

        $order = $this->service->createOrder(userId: $user->id, bill: $bill, pair: $pair, data: $validated);
        return response()->json([
            'order' => $order,
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

    public function fill(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'gt:0'],
        ]);
        $order = Order::query()->where('user_id', $user->id)->whereKey($orderId)->firstOrFail();
        $filled = $this->service->fillOrder($order, (string) $validated['price']);
        return response()->json($filled);
    }

    // Endpoint for socket pusher to tick prices and auto-close eligible orders
    public function onTick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pair_id' => ['required', 'integer', 'exists:pairs,id'],
            'price' => ['required', 'numeric', 'gt:0'],
        ]);

        $pairId = (int) $validated['pair_id'];
        $price = (string) $validated['price'];

        // Market orders: immediate fill at price; Stop: if trigger passed; Limit: if price reached
        $open = Order::query()
            ->where('pair_id', $pairId)
            ->whereIn('status', ['open','queued'])
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($open as $order) {
            $shouldFill = false;
            if ($order->type === 'market') {
                $shouldFill = true; // immediate fill
            } elseif ($order->type === 'limit') {
                if ($order->side === 'buy' && bccomp($price, (string) $order->price, 10) <= 0) $shouldFill = true;
                if ($order->side === 'sell' && bccomp($price, (string) $order->price, 10) >= 0) $shouldFill = true;
            } elseif ($order->type === 'stop') {
                if ($order->side === 'buy' && bccomp($price, (string) $order->stop_price, 10) >= 0) $shouldFill = true;
                if ($order->side === 'sell' && bccomp($price, (string) $order->stop_price, 10) <= 0) $shouldFill = true;
            }

            if ($shouldFill) {
                $this->service->fillOrder($order, $price);
            }
        }

        return response()->json(['filled' => true]);
    }

    public function closePosition(Request $request, int $positionId): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'gt:0'],
        ]);
        $position = Position::query()->where('user_id', $user->id)->whereKey($positionId)->firstOrFail();
        $closed = $this->service->closePosition($position, (string) $validated['price']);
        return response()->json($closed);
    }

    /**
     * Load the latest bills with currency relation for the authenticated user.
     */
    private function serializeBills($user): Collection
    {
        return $user->bills()->with('currency')->get();
    }
}


