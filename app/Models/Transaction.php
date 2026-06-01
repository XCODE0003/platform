<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'currency_id',
        'user_id',
        'bill_id',
        'amount',
        'type',
        'status',
        'comment',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
