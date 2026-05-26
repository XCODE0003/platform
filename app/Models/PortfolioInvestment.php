<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioInvestment extends Model
{
    protected $fillable = [
        'user_id',
        'wallet_id',
        'currency_id',
        'amount',
        'remaining',
        'invested_usd',
        'purchased_at',
        'locked_until',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
