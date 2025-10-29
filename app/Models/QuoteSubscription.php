<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteSubscription extends Model
{
    protected $fillable = ['pair_id', 'resolution', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function pair()
    {
        return $this->belongsTo(Pair::class);
    }
}

