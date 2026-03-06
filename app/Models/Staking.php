<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staking extends Model
{
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id', 'staking_plan_id', 'wallet_id',
        'amount', 'apy_rate', 'duration_days', 'reward_amount',
        'started_at', 'ends_at', 'status', 'claimed_at',
    ];

    protected $casts = [
        'amount'        => 'float',
        'apy_rate'      => 'float',
        'reward_amount' => 'float',
        'started_at'    => 'datetime',
        'ends_at'       => 'datetime',
        'claimed_at'    => 'datetime',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function plan()       { return $this->belongsTo(StakingPlan::class, 'staking_plan_id'); }
    public function wallet()     { return $this->belongsTo(UserWallet::class, 'wallet_id'); }

    public function isClaimable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && now()->gte($this->ends_at);
    }
}
