<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Promocode extends Model
{
    protected $fillable = [
        'code',
        'amount',
        'currency_id',
        'expiration_date',
        'is_active',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'is_active'       => 'boolean',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'promocode_users')->withPivot('used_at');
    }

    public function usedBy(int $userId): bool
    {
        return DB::table('promocode_users')
            ->where('promocode_id', $this->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
