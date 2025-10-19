<?php

namespace App\Http\Service\User;

use App\Models\User;
use App\Models\Currency;
use App\Models\UserWallet;
use App\Models\Bill;
use Illuminate\Support\Facades\Auth;

class CreateWallets
{
    public function createWallets(User $user)
    {
        $currencies = Currency::all();
        foreach ($currencies as $currency) {
            UserWallet::create([
                'currency_id' => $currency->id,
                'user_id' => $user->id,
                'balance' => 0,
                'pending_balance' => 0,
            ]);
        }
        Bill::create([
            'user_id' => $user->id,
            'currency_id' => Currency::where('symbol', 'USD')->first()->id,
            'balance' => 0,
            'name' => 'Main bill',
        ]);

        return true;
    }
}