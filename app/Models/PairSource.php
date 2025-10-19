<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PairSource extends Model
{
    protected $fillable = [
        'pair_id', 'provider', 'provider_symbol', 'priority', 'status', 'validated_at'
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function pair()
    {
        return $this->belongsTo(Pair::class);
    }
}


