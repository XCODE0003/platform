<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'code',
        'icon',
        'network',
        'exchange_rate',
        'status',
        'is_deposit',
        'min_deposit_amount',
        'portfolio_fee_percent',
        'portfolio_fee_fixed',
        'address_regex',


    ];
}
