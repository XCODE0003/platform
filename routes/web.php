<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Service\User\CalculateTotalBalance;

Route::get('/', function () {
    return redirect()->route('trade');
    // return Inertia::render('App/Home');
})->name('home');
Route::middleware('auth')->group(function () {

    Route::get('/trade', [App\Http\Controllers\User\TradeController::class, 'show'])->name('trade');
    Route::get('/assets', function () {
        $user = Auth::user();
        $portfolioWallets = $user->wallets->load('currency');
        $deposit = $user->DepositWallets->load('currency');
        $totalBalance = (new CalculateTotalBalance())->calculate($user);
        $bills = $user->bills;
        return Inertia::render('User/Assets', [
            'portfolioWallets' => $portfolioWallets,
            'depositWallets' => $deposit,
            'bills' => $bills,
            'totalBalance' => $totalBalance,
        ]);
    })->name('assets');
    Route::get('/account', [App\Http\Controllers\User\AccountController::class, 'show'])
        ->middleware('auth')
        ->name('account');

    Route::post('/account/change-email', [App\Http\Controllers\User\AccountController::class, 'changeEmail'])
        ->middleware('auth')
        ->name('account.change-email');

    Route::post('/account/confirm-email-change', [App\Http\Controllers\User\AccountController::class, 'confirmEmailChange'])
        ->middleware('auth')
        ->name('account.confirm-email-change');

    Route::post('/account/change-password', [App\Http\Controllers\User\AccountController::class, 'changePassword'])
        ->middleware('auth')
        ->name('account.change-password');

    Route::post('/account/enable-2fa', [App\Http\Controllers\User\AccountController::class, 'enable2FA'])
        ->middleware('auth')
        ->name('account.enable-2fa');

    Route::post('/account/disable-2fa', [App\Http\Controllers\User\AccountController::class, 'disable2FA'])
        ->middleware('auth')
        ->name('account.disable-2fa');

    Route::post('/account/activate-promocode', [App\Http\Controllers\User\AccountController::class, 'activatePromocode'])
        ->middleware('auth')
        ->name('account.activate-promocode');

    Route::post('/account/withdraw', [App\Http\Controllers\User\AccountController::class, 'withdraw'])
        ->middleware('auth')
        ->name('account.withdraw');

    Route::post('/trade/order', [App\Http\Controllers\User\TradeController::class, 'createOrder'])
        ->middleware('auth')
        ->name('trade.create-order');

    Route::post('/trade/orders/{orderId}/cancel', [App\Http\Controllers\User\TradeController::class, 'cancelOrder'])
        ->middleware('auth')
        ->name('trade.cancel-order');
    Route::get('/about', function () {
        return Inertia::render('App/About');
    })->name('about');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
