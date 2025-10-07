<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositAddress extends Model
{

    protected $fillable = [
        'address',
        'currency_id',
        'user_id',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
