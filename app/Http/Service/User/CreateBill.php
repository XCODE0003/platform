<?php

namespace App\Http\Service\User;

use App\Models\Currency;

class CreateBill
{
    public function createUsdBill($user, $bill_name, $is_demo)
    {
        $usd_curr = Currency::where('code', 'USD')->first();
        $balance = 0;
        if($is_demo){
            $balance = 10000;
        }
        $bill = $user->bills()->create([
            'currency_id' => $usd_curr->id,
            'demo' => $is_demo,
            'name' => $bill_name,
            'user_id' => $user->id,
            'balance' => $balance
        ]);

        return $bill;
    }
}
