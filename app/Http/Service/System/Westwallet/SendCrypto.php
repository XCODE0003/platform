<?php

namespace App\Http\Service\System\Westwallet;

use WestWallet\WestWallet\Client;
use WestWallet\WestWallet\CurrencyNotFoundException;
use Illuminate\Support\Facades\Log;

class SendCrypto
{
    public function sendCrypto($currency, $amount, $address, $description)
    {
        $client = new Client(env("WESTWALLET_PUBLIC_KEY"), env("WESTWALLET_SECRET_KEY"));

        try {
            $client->createWithdrawal($currency, $amount, $address, '', $description);
            return true;
        } catch(CurrencyNotFoundException $e) {
            Log::error("Error sending crypto: " . $e->getMessage());
            return false;
        }
    }
}
