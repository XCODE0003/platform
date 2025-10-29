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
Route::post('/api/trade/orders/{orderId}/fill', [App\Http\Controllers\User\OrderController::class, 'fill'])
->middleware('auth')
->name('trade.orders.fill');
// Socket tick endpoint (no auth here; consider protecting via a token/IP allowlist)
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