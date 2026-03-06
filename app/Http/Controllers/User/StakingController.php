<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Staking;
use App\Models\StakingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StakingController extends Controller
{
    public function start(Request $request): RedirectResponse
    {
        if (!(bool) Setting::get('staking_enabled', 1)) {
            throw ValidationException::withMessages([
                'plan_id' => 'Staking is temporarily disabled by administrator.',
            ]);
        }

        $request->validate([
            'plan_id' => ['required', 'integer'],
            'amount'  => ['required', 'numeric', 'gt:0'],
        ]);

        $user   = $request->user();
        $amount = (float) $request->input('amount');

        DB::transaction(function () use ($user, $request, $amount): void {
            $plan = StakingPlan::where('id', $request->input('plan_id'))
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$plan) {
                throw ValidationException::withMessages(['plan_id' => 'Staking plan not found or inactive.']);
            }

            if ($amount < (float) $plan->min_amount) {
                throw ValidationException::withMessages([
                    'amount' => "Minimum staking amount is {$plan->min_amount}.",
                ]);
            }

            if ($plan->max_amount !== null && $amount > (float) $plan->max_amount) {
                throw ValidationException::withMessages([
                    'amount' => "Maximum staking amount is {$plan->max_amount}.",
                ]);
            }

            $wallet = $user->wallets()
                ->where('currency_id', $plan->currency_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet || (float) $wallet->balance < $amount) {
                throw ValidationException::withMessages(['amount' => 'Insufficient wallet balance.']);
            }

            $reward = $plan->calculateReward($amount);

            $wallet->balance = bcsub((string) $wallet->balance, (string) $amount, 10);
            $wallet->save();

            Staking::create([
                'user_id'         => $user->id,
                'staking_plan_id' => $plan->id,
                'wallet_id'       => $wallet->id,
                'amount'          => $amount,
                'apy_rate'        => $plan->apy_percent,
                'duration_days'   => $plan->duration_days,
                'reward_amount'   => $reward,
                'started_at'      => now(),
                'ends_at'         => now()->addDays($plan->duration_days),
                'status'          => Staking::STATUS_ACTIVE,
            ]);
        });

        return back()->with('success', 'Staking started successfully.');
    }

    public function claim(Request $request, int $id): RedirectResponse
    {
        $user    = $request->user();

        DB::transaction(function () use ($user, $id): void {
            $staking = Staking::where('id', $id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$staking) {
                throw ValidationException::withMessages(['id' => 'Staking not found.']);
            }

            if (!$staking->isClaimable()) {
                throw ValidationException::withMessages(['id' => 'Staking is not yet claimable.']);
            }

            $wallet = $user->wallets()
                ->where('id', $staking->wallet_id)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                throw ValidationException::withMessages(['id' => 'Wallet not found.']);
            }

            $total = bcadd((string) $staking->amount, (string) $staking->reward_amount, 10);
            $wallet->balance = bcadd((string) $wallet->balance, $total, 10);
            $wallet->save();

            $staking->status     = Staking::STATUS_COMPLETED;
            $staking->claimed_at = now();
            $staking->save();
        });

        return back()->with('success', 'Rewards claimed successfully.');
    }
}
