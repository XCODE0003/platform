<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spread extends Model
{
    protected $fillable = [
        'user_id',
        'currency_id_in',
        'currency_id_out',
        'spread_value',
        'is_active',
    ];

    public function currency_in()
    {
        return $this->belongsTo(Currency::class, 'currency_id_in');
    }

    public function currency_out()
    {
        return $this->belongsTo(Currency::class, 'currency_id_out');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
