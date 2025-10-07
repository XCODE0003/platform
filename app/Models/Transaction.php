<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'currency_id',
        'user_id',
        'amount',
        'type',
        'status',
    ];
}
