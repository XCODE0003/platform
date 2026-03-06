<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spread extends Model
{
    protected $fillable = [
        'user_id',
        'currency_id_in',
        'spread_value',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'is_active'  => 'boolean',
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
