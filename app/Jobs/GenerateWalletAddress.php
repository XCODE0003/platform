<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\UserWallet;
use App\Models\User;
use App\Http\Service\System\Westwallet\GenerateWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateWalletAddress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $currency;

    public function __construct(User $user, Currency $currency)
    {
        $this->user = $user;
        $this->currency = $currency;

    }

    public function handle()
    {
        $generateWallet = new GenerateWallet();
        $address = $generateWallet->generateWallet($this->currency->symbol, env("WESTWALLET_IPN_URL"), $this->user->id);

        if ($address !== null && $address !== false) {
            $this->user->DepositWallets()->create([
                'currency_id' => $this->currency->id,
                'address' => $address
            ]);
            Log::info('Wallet address generated successfully', [
                'user_id' => $this->user->id,
                'currency' => $this->currency->symbol
            ]);
        } else {
            Log::error('Failed to generate wallet address', [
                'user_id' => $this->user->id,
                'currency' => $this->currency->symbol
            ]);

            if ($this->attempts() < 3) {
                $this->release(60 * $this->attempts());
            }
        }
    }
}