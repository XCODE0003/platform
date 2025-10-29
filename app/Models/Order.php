<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pair_id',
        'bill_id',
        'side',
        'type',
        'tif',
        'post_only',
        'price',
        'stop_price',
        'amount',
        'total',
        'stops_mode',
        'take_profit',
        'stop_loss',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function pair()
    {
        return $this->belongsTo(Pair::class);
    }
}


