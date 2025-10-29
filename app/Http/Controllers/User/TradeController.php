<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Pair;
use App\Models\GroupPair;
class TradeController extends Controller
{
    /**
     * Display the trading page.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();
        $bills = $user->bills->load('currency');
        // Получаем активные группы с их парами и валютами
        $groups = GroupPair::where('is_active', true)
            ->with([
                'pairs' => function ($query) {
                    $query->where('is_active', true)
                        ->with(['currencyIn', 'currencyOut']);
                }
            ])
            ->get();

        // Формируем структуру: Группа -> pairs -> pair -> currency_out -> currency
        $tradingPairs = $groups->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'is_active' => $group->is_active,
                'pairs' => $group->pairs->map(function ($pair) {
                    return [
                        'id' => $pair->id,
                        'is_active' => $pair->is_active,
                        'currency_in' => [
                            'id' => $pair->currencyIn->id,
                            'name' => $pair->currencyIn->name,
                            'symbol' => $pair->currencyIn->symbol,
                            'code' => $pair->currencyIn->code,
                            'icon' => $pair->currencyIn->icon,
                        ],
                        'currency_out' => [
                            'id' => $pair->currencyOut->id,
                            'name' => $pair->currencyOut->name,
                            'symbol' => $pair->currencyOut->symbol,
                            'code' => $pair->currencyOut->code,
                            'icon' => $pair->currencyOut->icon,
                        ],
                    ];
                })->values()->all()
            ];
        })->values()->all();

        return Inertia::render('User/Trade1', [
            'tradingPairs' => $tradingPairs,
            'bills' => $bills,
            // 'userBalances' => $this->getUserBalances($user),
            // 'openOrders' => $this->getOpenOrders($user),
            // 'tradeHistory' => $this->getTradeHistory($user),
            // 'recentTrades' => $this->getRecentTrades(),
            // 'orderBook' => $this->getOrderBook(),
        ]);
    }

    public function showTest(Request $request): Response
    {
        $user = $request->user();

        // Получаем активные группы с их парами и валютами
        $groups = GroupPair::where('is_active', true)
            ->with([
                'pairs' => function ($query) {
                    $query->where('is_active', true)
                        ->with(['currencyIn', 'currencyOut']);
                }
            ])
            ->get();

        // Формируем структуру: Группа -> pairs -> pair -> currency_out -> currency
        $tradingPairs = $groups->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'is_active' => $group->is_active,
                'pairs' => $group->pairs->map(function ($pair) {
                    return [
                        'id' => $pair->id,
                        'is_active' => $pair->is_active,
                        'currency_in' => [
                            'id' => $pair->currencyIn->id,
                            'name' => $pair->currencyIn->name,
                            'symbol' => $pair->currencyIn->symbol,
                            'code' => $pair->currencyIn->code,
                            'icon' => $pair->currencyIn->icon,
                        ],
                        'currency_out' => [
                            'id' => $pair->currencyOut->id,
                            'name' => $pair->currencyOut->name,
                            'symbol' => $pair->currencyOut->symbol,
                            'code' => $pair->currencyOut->code,
                            'icon' => $pair->currencyOut->icon,
                        ],
                    ];
                })->values()->all()
            ];
        })->values()->all();

        return Inertia::render('User/Trade_test', [
            'tradingPairs' => $tradingPairs,
            // 'userBalances' => $this->getUserBalances($user),
            // 'openOrders' => $this->getOpenOrders($user),
            // 'tradeHistory' => $this->getTradeHistory($user),
            // 'recentTrades' => $this->getRecentTrades(),
            // 'orderBook' => $this->getOrderBook(),
        ]);
    }
    // legacy placeholders removed; new endpoints in OrderController

    /**
     * Get available trading pairs.
     */
    private function getTradingPairs(): array
    {
        return [
            ['symbol' => 'BTCUSDT', 'name' => 'BTC/USDT', 'price' => '43250.00'],
            ['symbol' => 'ETHUSDT', 'name' => 'ETH/USDT', 'price' => '2580.00'],
            ['symbol' => 'ADAUSDT', 'name' => 'ADA/USDT', 'price' => '0.45'],
        ];
    }

    /**
     * Get user balances.
     */
    private function getUserBalances($user): array
    {
        // Получаем балансы пользователя
        $wallets = $user->wallets()->with('currency')->get();

        return $wallets->map(function ($wallet) {
            return [
                'currency' => $wallet->currency->name,
                'symbol' => $wallet->currency->code,
                'balance' => $wallet->balance,
                'available' => $wallet->balance - $wallet->pending_balance,
            ];
        })->toArray();
    }

    /**
     * Get user's open orders.
     */
    private function getOpenOrders($user): array
    {
        // Здесь должна быть логика получения открытых ордеров
        return [
            [
                'id' => 1,
                'date' => '2025-01-06 13:43:57',
                'pair' => 'BTC/USDT',
                'type' => 'Limit',
                'side' => 'Buy',
                'price' => '26481.13',
                'quantity' => '0.685630',
                'total' => '18156.16',
            ],
        ];
    }

    /**
     * Get user's trade history.
     */
    private function getTradeHistory($user): array
    {
        // Здесь должна быть логика получения истории торгов
        return [
            [
                'date' => '2025-01-06 13:43:57',
                'pair' => 'BTC/USDT',
                'type' => 'Limit',
                'side' => 'Buy',
                'price' => '26481.13',
                'quantity' => '0.685630',
                'total' => '18156.16',
                'status' => 'Completed',
            ],
        ];
    }

    /**
     * Get recent trades for the current pair.
     */
    private function getRecentTrades(): array
    {
        // Здесь должна быть логика получения недавних сделок
        return [
            [
                'price' => '31113.04',
                'quantity' => '0.229460',
                'time' => '15:23:57',
                'side' => 'buy',
            ],
        ];
    }

    /**
     * Get order book data.
     */
    private function getOrderBook(): array
    {
        return [
            'bids' => [
                ['price' => '31113.04', 'quantity' => '0.229460', 'total' => '7135.50'],
            ],
            'asks' => [
                ['price' => '31120.00', 'quantity' => '0.150000', 'total' => '4668.00'],
            ],
        ];
    }
}
