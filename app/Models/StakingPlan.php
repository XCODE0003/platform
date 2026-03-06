<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StakingPlan extends Model
{
    protected $fillable = [
        'currency_id', 'name', 'apy_percent', 'duration_days',
        'min_amount', 'max_amount', 'is_active',
    ];

    protected $casts = [
        'apy_percent'  => 'float',
        'min_amount'   => 'float',
        'max_amount'   => 'float',
        'is_active'    => 'boolean',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function stakings()
    {
        return $this->hasMany(Staking::class);
    }

    /** Reward for staking `$amount` on this plan */
    public function calculateReward(float $amount): float
    {
        $yearBasisDays = (int) Setting::get('staking_year_basis_days', 365);
        if ($yearBasisDays <= 0) {
            $yearBasisDays = 365;
        }

        return round(
            $amount * ($this->apy_percent / 100) * ($this->duration_days / $yearBasisDays),
            10
        );
    }
}
