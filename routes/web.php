<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Service\User\CalculateTotalBalance;
use App\Http\Controllers\User\TradeController;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\StakingPlan;
use App\Models\Staking;

Route::get('/', function () {
    return redirect()->route('trade');
    // return Inertia::render('App/Home');
})->name('home');
Route::middleware('auth')->group(function () {

    Route::get('/trade', [App\Http\Controllers\User\TradeController::class, 'show'])->name('trade');
    Route::get('/trade-test', [App\Http\Controllers\User\TradeController::class, 'showTest'])->name('trade-test');
    Route::get('/assets', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $portfolioWallets = $user->wallets()->with('currency')->get();

        // Map of currency_id => asset_class derived from pairs (where the
        // currency is the base/in side). Used by the portfolio UI to group
        // wallets into Crypto / Stocks / Indices tabs without changing the
        // currency schema.
        $currencyAssetClass = \App\Models\Pair::query()
            ->select('currency_id_in', 'asset_class')
            ->whereNotNull('asset_class')
            ->whereIn('currency_id_in', $portfolioWallets->pluck('currency_id'))
            ->get()
            ->groupBy('currency_id_in')
            ->map(fn ($rows) => $rows->first()->asset_class);

        // Per-wallet lock aggregation: how much of each portfolio holding is
        // still locked (bought < portfolio_lock_days ago) vs available to sell.
        $now = now();
        $lotsByWallet = \App\Models\PortfolioInvestment::query()
            ->where('user_id', $user->id)
            ->where('remaining', '>', 0)
            ->get()
            ->groupBy('wallet_id');

        $portfolioWallets = $portfolioWallets->map(function ($w) use ($currencyAssetClass, $lotsByWallet, $now) {
            $w->asset_class = $currencyAssetClass[$w->currency_id] ?? 'crypto';

            $lots = $lotsByWallet[$w->id] ?? collect();
            $locked = 0.0;
            $totalInLots = 0.0;
            $nextUnlock = null;
            foreach ($lots as $lot) {
                $rem = (float) $lot->remaining;
                $totalInLots += $rem;
                if ($lot->locked_until && $lot->locked_until->gt($now)) {
                    $locked += $rem;
                    if ($nextUnlock === null || $lot->locked_until->lt($nextUnlock)) {
                        $nextUnlock = $lot->locked_until;
                    }
                }
            }
            $w->locked_amount   = round($locked, 8);
            $w->available_amount = round(max(0.0, (float) $w->balance - $locked), 8);
            $w->next_unlock_at  = $nextUnlock?->toIso8601String();
            return $w;
        });

        $personalAddresses = $user->depositWallets()
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->pluck('address', 'currency_id');

        $deposit = Currency::query()
            ->where('status', 'active')
            ->where(function ($q) use ($personalAddresses) {
                $q->whereNotNull('deposit_address')
                  ->where('deposit_address', '!=', '');
                if ($personalAddresses->isNotEmpty()) {
                    $q->orWhereIn('id', $personalAddresses->keys());
                }
            })
            ->orderBy('symbol')
            ->get()
            ->map(fn (Currency $c) => [
                'id'       => $c->id,
                'address'  => $personalAddresses[$c->id] ?? $c->deposit_address,
                'memo'     => $c->deposit_memo,
                'currency' => $c,
            ])
            ->filter(fn (array $row) => !empty($row['address']))
            ->values();
        $totalBalancePortfolio = (new CalculateTotalBalance())->calculate($user);
        $bills = $user->bills()->with('currency')->get();
        $totalBalanceAssets = $bills->sum(function ($bill) {
            return $bill->balance + ($bill->pending_balance ?? 0);
        });
        $withdraws = $user->withdraws()->with('currency')->latest()->take(50)->get();
        return Inertia::render('User/Assets', [
            'portfolioWallets'       => $portfolioWallets,
            'depositWallets'         => $deposit,
            'bills'                  => $bills,
            'totalBalanceAssets'     => $totalBalanceAssets,
            'totalBalancePortfolio'  => $totalBalancePortfolio,
            'withdraws'              => $withdraws,
            'portfolioFeePercent'    => (float) Setting::get('portfolio_fee_percent', 0),
            'portfolioFeeFixed'      => (float) Setting::get('portfolio_fee_fixed',   0),
            'portfolioLockDays'      => (int) Setting::get('portfolio_lock_days', 365),
            'stakingEnabled'         => (bool) Setting::get('staking_enabled', 1),
            'stakingYearBasisDays'   => (int) Setting::get('staking_year_basis_days', 365),
            'cardDepositDetails'     => (string) ($user->card_deposit_details ?: Setting::get('card_deposit_details', '')),
            'stakingPlans'           => StakingPlan::where('is_active', true)->with('currency')->get(),
            'userStakings'           => Staking::where('user_id', $user->id)
                ->with(['plan.currency'])
                ->orderByDesc('created_at')
                ->get(),
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

    // Trading endpoints

    Route::get('/about', function () {
        return Inertia::render('App/About');
    })->name('about');

    // Binance proxy (CORS-safe)
    Route::get('/api/binance/exchangeInfo', [App\Http\Controllers\User\BinanceProxyController::class, 'exchangeInfo'])->name('binance.exchangeInfo');
    Route::get('/api/binance/klines', [App\Http\Controllers\User\BinanceProxyController::class, 'klines'])->name('binance.klines');

    // Short aliases for manual testing
    Route::get('/exchangeInfo', [App\Http\Controllers\User\BinanceProxyController::class, 'exchangeInfo']);
    Route::get('/klines', [App\Http\Controllers\User\BinanceProxyController::class, 'klines']);

    // Unified market data endpoint
    Route::get('/api/market/pair', [App\Http\Controllers\User\MarketDataController::class, 'pairInfo'])->name('market.pair');
    Route::get('/api/market/bars', [App\Http\Controllers\User\MarketDataController::class, 'bars'])->name('market.bars');

    Route::get('/api/support/ticket', [App\Http\Controllers\User\TicketController::class, 'current'])->name('support.ticket');
    Route::post('/api/support/tickets', [App\Http\Controllers\User\TicketController::class, 'store'])->name('support.tickets.store');
    Route::post('/api/support/messages', [App\Http\Controllers\User\TicketController::class, 'addMessage'])->name('support.messages.store');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/trade.php';
require __DIR__ . '/assets.php';
require __DIR__ . '/portfolio.php';
