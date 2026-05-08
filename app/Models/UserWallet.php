<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    protected $fillable = [
        'currency_id',
        'balance',
        'pending_balance',
        'total_invested_usd',
        'user_id',
    ];

    protected $appends = ['avg_buy_price_usd', 'profit_usd'];

    /**
     * Average USD cost per unit of this currency, computed from cumulative
     * invested amount and current balance. Returns null when balance is
     * empty (no meaningful average exists).
     */
    public function getAvgBuyPriceUsdAttribute(): ?float
    {
        $balance = (float) $this->balance;
        if ($balance <= 0) {
            return null;
        }
        return (float) $this->total_invested_usd / $balance;
    }

    /**
     * Unrealised profit/loss in USD: (current USD value of holdings) minus
     * (invested USD). Uses the currency's live exchange_rate, which keeps
     * the figure tracking market price without requiring per-tick writes
     * to the wallet row.
     */
    public function getProfitUsdAttribute(): float
    {
        $rate = (float) ($this->currency?->exchange_rate ?? 0);
        $balance = (float) $this->balance;
        $currentUsd = $balance * $rate;
        return round($currentUsd - (float) $this->total_invested_usd, 8);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
