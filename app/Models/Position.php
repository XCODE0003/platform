<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'user_id', 'pair_id', 'bill_id', 'side', 'entry_price', 'quantity', 'entry_total',
        'take_profit', 'stop_loss', 'status', 'close_price', 'close_total', 'realized_pnl',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function pair() { return $this->belongsTo(Pair::class); }
    public function bill() { return $this->belongsTo(Bill::class); }
}



