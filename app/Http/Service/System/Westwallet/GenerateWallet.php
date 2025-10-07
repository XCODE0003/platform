<?php

namespace App\Http\Service\System\Westwallet;

use WestWallet\WestWallet\Client;
use WestWallet\WestWallet\CurrencyNotFoundException;
use Illuminate\Support\Facades\Log;

class GenerateWallet
{
    public function generateWallet($currency, $ipnURL = "", $label = "")
    {
        $client = new Client(env("WESTWALLET_PUBLIC_KEY"), env("WESTWALLET_SECRET_KEY"));

        try {
            $address = $client->generateAddress($currency, $ipnURL, $label);
            return $address['address'];
        } catch(CurrencyNotFoundException $e) {
            Log::error($e->getMessage());
            return null;
        }
    }
}
