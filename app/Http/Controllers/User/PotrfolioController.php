<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\UserWallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PotrfolioController extends Controller
{
    /**
     * Transfer funds FROM portfolio wallet TO trading bill (with fee).
     */
    public function portfolioToAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'wallet_id' => ['required', 'integer'],
            'bill_id'   => ['required', 'integer'],
            'amount'    => ['required', 'numeric', 'gt:0'],
        ]);

        $user   = $request->user();
        $amount = (float) $request->input('amount');

        DB::transaction(function () use ($user, $request, $amount): void {
            $wallet = $user->wallets()
                ->whereKey($request->input('wallet_id'))
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                throw ValidationException::withMessages([
                    'wallet_id' => 'Portfolio wallet not found.',
                ]);
            }

            if ((float) $wallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient portfolio balance.',
                ]);
            }

            $bill = $user->bills()
                ->whereKey($request->input('bill_id'))
                ->lockForUpdate()
                ->first();

            if (!$bill) {
                throw ValidationException::withMessages([
                    'bill_id' => 'Trading account not found.',
                ]);
            }

            if ($bill->demo) {
                throw ValidationException::withMessages([
                    'bill_id' => 'Cannot transfer to a demo account.',
                ]);
            }

            // Calculate fee from global settings (in wallet currency units)
            $feePercent = (float) Setting::get('portfolio_fee_percent', 0);
            $feeFixed   = (float) Setting::get('portfolio_fee_fixed',   0);
            $fee        = round($amount * ($feePercent / 100), 8) + $feeFixed;
            $netAmount  = round($amount - $fee, 8);

            if ($netAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount is too small after fees.',
                ]);
            }

            // Convert net amount to the bill's currency (USD) using exchange rate
            $exchangeRate = (float) (Currency::find($wallet->currency_id)?->exchange_rate ?? 1);
            $netAmountConverted = round($netAmount * $exchangeRate, 8);

            // Deduct full amount (in wallet currency) from wallet
            $wallet->balance = bcsub((string) $wallet->balance, (string) $amount, 8);
            $wallet->save();

            // Credit converted USD amount to trading account
            $bill->balance = bcadd((string) $bill->balance, (string) $netAmountConverted, 8);
            $bill->save();
        });

        return back()->with('success', 'Successfully transferred to trading account.');
    }

    /**
     * Transfer funds FROM trading bill TO portfolio wallet.
     */
    public function accountToPortfolio(Request $request): RedirectResponse
    {
        $request->validate([
            'bill_id'   => ['required', 'integer'],
            'wallet_id' => ['required', 'integer'],
            'amount'    => ['required', 'numeric', 'gt:0'],
        ]);

        $user   = $request->user();
        $amount = (float) $request->input('amount');

        DB::transaction(function () use ($user, $request, $amount): void {
            $bill = $user->bills()
                ->whereKey($request->input('bill_id'))
                ->lockForUpdate()
                ->first();

            if (!$bill) {
                throw ValidationException::withMessages([
                    'bill_id' => 'Trading account not found.',
                ]);
            }

            if ($bill->demo) {
                throw ValidationException::withMessages([
                    'bill_id' => 'Cannot transfer from a demo account.',
                ]);
            }

            if ((float) $bill->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient trading account balance.',
                ]);
            }

            $wallet = $user->wallets()
                ->whereKey($request->input('wallet_id'))
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                throw ValidationException::withMessages([
                    'wallet_id' => 'Portfolio wallet not found.',
                ]);
            }

            // Convert amount from bill currency to wallet currency if they differ
            if ($bill->currency_id !== $wallet->currency_id) {
                $billRate   = (float) (Currency::find($bill->currency_id)?->exchange_rate   ?? 1);
                $walletRate = (float) (Currency::find($wallet->currency_id)?->exchange_rate ?? 1);
                $walletAmount = $walletRate > 0
                    ? round($amount * $billRate / $walletRate, 8)
                    : $amount;
            } else {
                $walletAmount = $amount;
            }

            $bill->balance = bcsub((string) $bill->balance, (string) $amount, 8);
            $bill->save();

            $wallet->balance = bcadd((string) $wallet->balance, (string) $walletAmount, 8);
            $wallet->save();
        });

        return back()->with('success', 'Successfully transferred to portfolio.');
    }

    public function invest()
    {
        //
    }

    public function get()
    {
        //
    }
}
