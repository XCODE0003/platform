<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    protected $fillable = [
        'code',
        'amount',
        'currency_id',
        'expiration_date',
        'is_active',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
