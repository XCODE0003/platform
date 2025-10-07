<?php
namespace App\Http\Service\User;

use App\Models\User;

class CalculateTotalBalance
{
    public function calculate(User $user)
    {
        $totalBalance = 0;
        $wallets = $user->wallets->where('balance', '>', 0)->load('currency');

        foreach ($wallets as $wallet) {
            $totalBalance += $wallet->balance * $wallet->currency->exchange_rate;
        }
        return $totalBalance;
    }
}
