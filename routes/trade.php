<?php

use Illuminate\Support\Facades\Route;


Route::get('/api/trade/orders', [App\Http\Controllers\User\OrderController::class, 'index'])
->middleware('auth')
->name('trade.orders.index');
Route::post('/api/trade/orders', [App\Http\Controllers\User\OrderController::class, 'store'])
->middleware('auth')
->name('trade.orders.store');
Route::post('/api/trade/orders/{orderId}/cancel', [App\Http\Controllers\User\OrderController::class, 'cancel'])
->middleware('auth')
->name('trade.orders.cancel');
// NOTE: The user-facing `fill` endpoint was removed — allowing a user to choose
// their own fill price opened arbitrary PnL manipulation. Fills are now driven
// exclusively by server-side signals (CheckLimitOrdersCommand and onTick).

// Socket tick endpoint — protected via X-Tick-Secret header (config('services.trade_tick.secret')).
Route::post('/api/trade/tick', [App\Http\Controllers\User\OrderController::class, 'onTick'])
->name('trade.orders.tick');

// Ensure quotes relay TTL for pair/resolution
Route::post('/api/quotes/ensure', [App\Http\Controllers\User\QuotesController::class, 'ensureRelay'])
->middleware('auth')
->name('quotes.ensure');

// Close position at market price
Route::post('/api/trade/positions/{positionId}/close', [App\Http\Controllers\User\OrderController::class, 'closePosition'])
->middleware('auth')
->name('trade.positions.close');

// Update TP / SL on open position
Route::patch('/api/trade/positions/{positionId}/tpsl', [App\Http\Controllers\User\OrderController::class, 'updateTpSl'])
->middleware('auth')
->name('trade.positions.tpsl');