<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\PortfolioInvestment;
use App\Models\Setting;
use App\Models\UserWallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PotrfolioController extends Controller
{
    /**
     * Format any numeric value as a plain decimal string with the given
     * scale. Plain `(string)` casting on small floats produces scientific
     * notation (e.g. "1.2538E-5") which bcmath rejects as not well-formed.
     */
    private function bcStr(float|int|string|null $value, int $scale = 8): string
    {
        return number_format((float) $value, $scale, '.', '');
    }

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

            // Enforce the per-lot lock: only units whose lock has expired may
            // be sold/withdrawn. Units bought less than `portfolio_lock_days`
            // ago stay locked. Balance not covered by any lot (legacy holdings
            // created before lot tracking) is treated as unlocked.
            $lots = PortfolioInvestment::query()
                ->where('wallet_id', $wallet->id)
                ->where('remaining', '>', 0)
                ->orderBy('purchased_at')
                ->lockForUpdate()
                ->get();

            $now = Carbon::now();
            $lockedUnits   = 0.0;
            $unlockedInLots = 0.0;
            $totalInLots   = 0.0;
            $nextUnlockAt  = null;
            foreach ($lots as $lot) {
                $rem = (float) $lot->remaining;
                $totalInLots += $rem;
                if ($lot->locked_until && $lot->locked_until->gt($now)) {
                    $lockedUnits += $rem;
                    if ($nextUnlockAt === null || $lot->locked_until->lt($nextUnlockAt)) {
                        $nextUnlockAt = $lot->locked_until;
                    }
                } else {
                    $unlockedInLots += $rem;
                }
            }
            // Holdings not represented by any lot row are unlocked legacy funds.
            $legacyUnlocked = max(0.0, (float) $wallet->balance - $totalInLots);
            $available = round($legacyUnlocked + $unlockedInLots, 8);

            if ($amount > $available + 1e-9) {
                $msg = $lockedUnits > 0 && $nextUnlockAt
                    ? sprintf(
                        'Only %s available now. %s is locked until %s.',
                        rtrim(rtrim(number_format($available, 8, '.', ''), '0'), '.'),
                        rtrim(rtrim(number_format($lockedUnits, 8, '.', ''), '0'), '.'),
                        $nextUnlockAt->format('Y-m-d'),
                    )
                    : 'Insufficient unlocked portfolio balance.';
                throw ValidationException::withMessages(['amount' => $msg]);
            }

            // Consume unlocked lots FIFO (after the implicit legacy portion).
            $toConsume = max(0.0, $amount - $legacyUnlocked);
            foreach ($lots as $lot) {
                if ($toConsume <= 1e-9) {
                    break;
                }
                if ($lot->locked_until && $lot->locked_until->gt($now)) {
                    continue; // still locked, skip
                }
                $rem = (float) $lot->remaining;
                $take = min($rem, $toConsume);
                $lot->remaining = $this->bcStr($rem - $take, 18);
                $lot->save();
                $toConsume = round($toConsume - $take, 8);
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

            // Reduce cost basis proportionally to the fraction withdrawn.
            // Average-cost accounting: if the user pulls 30% of holdings,
            // they realise 30% of accumulated cost. Avoids tracking lots.
            $prevBalance = (float) $wallet->balance;
            $prevInvested = (float) $wallet->total_invested_usd;
            $newBalance = max(0.0, $prevBalance - $amount);
            $newInvested = $prevBalance > 0
                ? round($prevInvested * ($newBalance / $prevBalance), 8)
                : 0.0;

            // Deduct full amount (in wallet currency) from wallet
            $wallet->balance = bcsub($this->bcStr($wallet->balance), $this->bcStr($amount), 8);
            $wallet->total_invested_usd = $this->bcStr($newInvested);
            $wallet->save();

            // Credit converted USD amount to trading account
            $bill->balance = bcadd($this->bcStr($bill->balance), $this->bcStr($netAmountConverted), 8);
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

            // USD-equivalent of what's leaving the bill — that's the cost
            // basis added to the wallet. Bill currency may not be USD, so
            // multiply by its rate. (Bills are USD today, but this keeps
            // the math correct if that ever changes.)
            $billRate = (float) (Currency::find($bill->currency_id)?->exchange_rate ?? 1);
            $investedUsdDelta = round($amount * $billRate, 8);

            $bill->balance = bcsub($this->bcStr($bill->balance), $this->bcStr($amount), 8);
            $bill->save();

            $wallet->balance = bcadd($this->bcStr($wallet->balance), $this->bcStr($walletAmount), 8);
            $wallet->total_invested_usd = bcadd(
                $this->bcStr($wallet->total_invested_usd),
                $this->bcStr($investedUsdDelta),
                8,
            );
            $wallet->save();

            // Record the purchase as a locked lot. Sale of these units is
            // blocked until locked_until (purchase date + configured term).
            $lockDays = max(0, (int) Setting::get('portfolio_lock_days', 365));
            $now = Carbon::now();
            PortfolioInvestment::create([
                'user_id'      => $user->id,
                'wallet_id'    => $wallet->id,
                'currency_id'  => $wallet->currency_id,
                'amount'       => $this->bcStr($walletAmount, 18),
                'remaining'    => $this->bcStr($walletAmount, 18),
                'invested_usd' => $this->bcStr($investedUsdDelta),
                'purchased_at' => $now,
                'locked_until' => $now->copy()->addDays($lockDays),
            ]);
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
